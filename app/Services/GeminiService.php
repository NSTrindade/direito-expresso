<?php
/**
 * Integração com a API do Gemini (seção 7.5.3 do pré-projeto).
 * O Gemini 1.5 Flash foi desligado pelo Google em set/2025; o modelo agora
 * vem de GEMINI_MODEL no .env (padrão: gemini-3.6-flash).
 *
 * Usa Geração Controlada (responseMimeType + responseSchema) para
 * garantir que a resposta sempre venha em JSON válido, e temperature
 * baixa para reduzir alucinações — exatamente como documentado na
 * tabela da seção 7.5.3.1.
 *
 * NOTA DE PROJETO: o pré-projeto lista os campos de saída como
 * "problema_resumido", "traducao_lei" e "id_orgao_destino". Trocamos
 * "id_orgao_destino" (um ID numérico) por "orgao_sugerido" (o NOME do
 * órgão, como texto). Motivo: pedir para o modelo "advinhar" uma chave
 * primária do nosso banco é o tipo de alucinação que a própria seção
 * 7.5.3.1 alerta para evitar — o LLM não tem como saber os IDs reais
 * das nossas tabelas. Em vez disso, o Controller casa o nome sugerido
 * com OrgaoCompetente::findByNomeAproximado() para achar o ID de verdade.
 * O schema também usa só propriedades STRING (sem arrays em subníveis),
 * respeitando a mesma seção quanto ao erro HTTP 400 "Invalid JSON payload".
 */

class GeminiServiceException extends RuntimeException
{
}

class GeminiService
{
    private string $apiKey;
    private string $model;
    private string $endpointTemplate;

    public function __construct()
    {
        $config = require dirname(__DIR__) . '/Config/config.php';
        $this->apiKey = $config['gemini']['api_key'];
        $this->model = $config['gemini']['model'];
        $this->endpointTemplate = $config['gemini']['endpoint'];
    }

    /**
     * Envia o relato do cidadão para o Gemini e devolve a tradução
     * estruturada. Lança GeminiServiceException em qualquer falha —
     * quem chama decide o fallback (Fluxo Alternativo do caso de uso
     * RF_03: exibir aviso e voltar para a consulta textual).
     *
     * @return array{
     *   termo_identificado: string,
     *   explicacao_coloquial: string,
     *   proximo_passo_sugerido: string,
     *   categoria_sugerida: string,
     *   orgao_sugerido: string,
     * }
     */
    public function traduzir(string $relatoCidadao, ?string $categoriaContexto = null, array $categoriasDisponiveis = [], array $leisCadastradas = []): array
    {
        // a chave de exemplo do .env.example conta como "sem chave"
        if ($this->apiKey === '' || str_starts_with($this->apiKey, 'coloque_')) {
            throw new GeminiServiceException('GEMINI_API_KEY não configurada no .env.');
        }

        $payload = $this->montarPayload($relatoCidadao, $categoriaContexto, $categoriasDisponiveis, $leisCadastradas);
        $corpo = json_encode($payload, JSON_UNESCAPED_UNICODE);

        // Fila de modelos: o principal (GEMINI_MODEL) e, se ele estiver lotado
        // ("high demand", HTTP 503), com limite atingido (429), fora do ar (500)
        // ou inexistente (404), os modelos reserva — por padrão os Flash estáveis
        // mais leves. Troque a fila em GEMINI_MODELOS_RESERVA (separados por vírgula).
        $reservas = getenv('GEMINI_MODELOS_RESERVA') ?: 'gemini-3.5-flash-lite,gemini-3.5-flash,gemini-3.1-flash-lite';
        $modelos = array_values(array_unique(array_filter(array_map('trim',
            array_merge([$this->model], explode(',', $reservas))))));

        // Tempo total que o cidadão espera, somando todas as tentativas.
        $prazo = microtime(true) + 50;
        $erros = [];
        $respostaBruta = null;

        foreach ($modelos as $modelo) {
            $restante = (int) floor($prazo - microtime(true));
            if ($restante < 5) {
                break;
            }
            [$httpCode, $respostaBruta, $erroCurl] = $this->chamarModelo($modelo, $corpo, min(25, $restante));

            // Lotado agora: uma segunda chance rápida no mesmo modelo antes de trocar.
            if (in_array($httpCode, [429, 503], true) && $prazo - microtime(true) > 8) {
                sleep(2);
                [$httpCode, $respostaBruta, $erroCurl] = $this->chamarModelo($modelo, $corpo, min(20, (int) floor($prazo - microtime(true))));
            }

            if ($httpCode === 200 && $respostaBruta) {
                break;
            }

            $erros[] = $respostaBruta === false || $respostaBruta === ''
                ? "{$modelo}: falha de conexão ({$erroCurl})"
                : "{$modelo}: HTTP {$httpCode} " . self::mensagemDeErro((string) $respostaBruta);
            $respostaBruta = null;

            // Erros que outro modelo não resolve (chave inválida, pedido malformado): para aqui.
            if (in_array($httpCode, [400, 401, 403], true)) {
                break;
            }
        }

        if ($respostaBruta === null) {
            throw new GeminiServiceException('Gemini indisponível — ' . implode(' | ', $erros));
        }

        // Registra quem respondeu (Render → Logs, busque "respondeu com").
        error_log('[Direito Expresso] Gemini respondeu com ' . $modelo
            . ($modelo !== $this->model ? ' (reserva; ' . implode(' | ', $erros) . ')' : ''));

        $resposta = json_decode($respostaBruta, true);
        // A resposta pode vir dividida em várias "parts" (ex.: assinatura do raciocínio
        // + texto). Junta todo o texto, em vez de ler só a primeira.
        $textoJson = null;
        foreach ($resposta['candidates'][0]['content']['parts'] ?? [] as $parte) {
            if (isset($parte['text']) && empty($parte['thought'])) {
                $textoJson = ($textoJson ?? '') . $parte['text'];
            }
        }

        if ($textoJson === null) {
            throw new GeminiServiceException('Resposta do Gemini em formato inesperado (sem candidates[0]).');
        }

        $traducao = json_decode($textoJson, true);

        $camposObrigatorios = [
            'termo_identificado',
            'explicacao_coloquial',
            'proximo_passo_sugerido',
            'categoria_sugerida',
            'orgao_sugerido',
        ];

        if (!is_array($traducao) || array_diff($camposObrigatorios, array_keys($traducao)) !== []) {
            throw new GeminiServiceException('JSON da tradução veio malformado ou incompleto.');
        }

        // Base legal: lista de normas; aceita só itens com número de norma reconhecível.
        $traducao['base_legal'] = self::filtrarBaseLegal($traducao['base_legal'] ?? []);

        return $traducao;
    }

    /** Faz uma chamada a um modelo. Devolve [código HTTP, corpo da resposta, erro do curl]. */
    private function chamarModelo(string $modelo, string $corpo, int $timeout): array
    {
        $url = sprintf($this->endpointTemplate, rawurlencode($modelo), $this->apiKey);
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
            CURLOPT_POSTFIELDS     => $corpo,
            CURLOPT_CONNECTTIMEOUT => 10,
            // Os modelos Gemini 3 "pensam" antes de responder: relatos reais
            // podem levar mais de 15 s.
            CURLOPT_TIMEOUT        => max(5, $timeout),
        ]);
        $respostaBruta = curl_exec($ch);
        $erroCurl = curl_error($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return [$httpCode, $respostaBruta, $erroCurl];
    }

    /** Pega só o campo "message" do erro do Gemini, para o log ficar curto. */
    private static function mensagemDeErro(string $corpo): string
    {
        $json = json_decode($corpo, true);
        return mb_substr((string) ($json['error']['message'] ?? $corpo), 0, 160, 'UTF-8');
    }

    /** Mantém no máximo 3 normas com número de norma brasileira reconhecível. */
    private static function filtrarBaseLegal($lista): array
    {
        $validas = [];
        foreach (is_array($lista) ? $lista : [] as $item) {
            $numero = trim((string) ($item['numero_lei'] ?? ''));
            if ($numero === '' || !preg_match('/\b(Lei|Decreto|Constitui|C[oó]digo|Medida Provis|Emenda)/iu', $numero)) {
                continue;
            }
            $validas[mb_strtolower($numero, 'UTF-8')] = [
                'numero_lei' => mb_substr($numero, 0, 50, 'UTF-8'),
                'tipo_norma' => mb_substr(trim((string) ($item['tipo_norma'] ?? 'Lei Federal')) ?: 'Lei Federal', 0, 50, 'UTF-8'),
                'ementa'     => trim((string) ($item['ementa'] ?? '')),
            ];
        }
        return array_slice(array_values($validas), 0, 3);
    }

    private function montarSystemInstruction(array $categoriasDisponiveis = [], array $leisCadastradas = []): string
    {
        // Leis já cadastradas pelo painel: a IA deve preferir estas (mesmo texto),
        // para reaproveitar o cadastro em vez de criar duplicatas.
        $listaLeis = $leisCadastradas
            ? 'Leis já cadastradas no sistema (prefira estas, escrevendo o número exatamente igual): '
              . implode('; ', $leisCadastradas) . '. '
            : '';

        // As categorias vêm do banco: cadastrar uma categoria nova no painel já a coloca aqui.
        $listaCategorias = $categoriasDisponiveis
            ? implode(', ', $categoriasDisponiveis)
            : 'Consumidor, Civil, Trabalho, Moradia, Serviços Públicos';

        return 'Você é o motor de tradução jurídica do sistema público "Direito Expresso". '
             . 'Você aplica os eixos do Pacto Nacional do Judiciário pela Linguagem Simples (CNJ). '
             . 'Você NÃO é advogado, não emite parecer de risco processual e não inventa leis, artigos '
             . 'ou números de processo. Sua única função é: '
             . '(1) identificar o termo ou situação jurídica central do relato do cidadão; '
             . '(2) traduzir esse termo para um português coloquial, curto, sem jargão; '
             . '(3) sugerir um próximo passo prático e realista; '
             . "(4) indicar a categoria do problema, escrevendo exatamente um destes nomes: {$listaCategorias} ou Outro "
             . '(Civil = contratos e dívidas entre pessoas, indenizações, acidentes, família e herança) '
             . 'e o nome de um órgão público real do Brasil mais adequado para buscar ajuda '
             . '(ex: Procon, Defensoria Pública, Justiça do Trabalho, Juizado Especial Cível). '
             . '(5) indicar a base legal: de 1 a 3 normas brasileiras em vigor que fundamentam a orientação, '
             . 'cada uma com numero_lei no formato oficial (ex: "Lei nº 8.078/1990", "Decreto-Lei nº 5.452/1943", '
             . '"Constituição Federal"), tipo_norma (ex: "Lei Federal") e ementa curta (ex: "Código de Defesa do Consumidor"). '
             . $listaLeis
             . 'Cite somente normas que você tem certeza que existem; nunca invente números de lei. '
             . 'Se o relato for ambíguo ou não parecer uma questão jurídica, responda com honestidade '
             . 'mesmo assim, sugerindo a categoria "Outro" e indicando a Defensoria Pública em caso de dúvida.';
    }

    private function montarPayload(string $relatoCidadao, ?string $categoriaContexto, array $categoriasDisponiveis = [], array $leisCadastradas = []): array
    {
        $contexto = $categoriaContexto
            ? "Categoria já selecionada pelo cidadão no totem: {$categoriaContexto}.\n"
            : '';

        return [
            'system_instruction' => [
                'parts' => [['text' => $this->montarSystemInstruction($categoriasDisponiveis, $leisCadastradas)]],
            ],
            'contents' => [
                [
                    'role'  => 'user',
                    'parts' => [['text' => $contexto . "Relato do cidadão: {$relatoCidadao}"]],
                ],
            ],
            'generationConfig' => [
                'temperature'      => 0.1,
                'responseMimeType' => 'application/json',
                'responseSchema'   => [
                    'type'       => 'OBJECT',
                    'properties' => [
                        'termo_identificado'     => ['type' => 'STRING'],
                        'explicacao_coloquial'   => ['type' => 'STRING'],
                        'proximo_passo_sugerido' => ['type' => 'STRING'],
                        'categoria_sugerida'     => ['type' => 'STRING'],
                        'orgao_sugerido'         => ['type' => 'STRING'],
                        'base_legal'             => [
                            'type'  => 'ARRAY',
                            'items' => [
                                'type'       => 'OBJECT',
                                'properties' => [
                                    'numero_lei' => ['type' => 'STRING'],
                                    'tipo_norma' => ['type' => 'STRING'],
                                    'ementa'     => ['type' => 'STRING'],
                                ],
                                'required' => ['numero_lei', 'tipo_norma', 'ementa'],
                            ],
                        ],
                    ],
                    'required' => [
                        'termo_identificado',
                        'explicacao_coloquial',
                        'proximo_passo_sugerido',
                        'categoria_sugerida',
                        'orgao_sugerido',
                        'base_legal',
                    ],
                ],
            ],
        ];
    }
}
