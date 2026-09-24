<?php
/**
 * Integração com a API do Gemini 1.5 Flash (seção 7.5.3 do pré-projeto).
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
    public function traduzir(string $relatoCidadao, ?string $categoriaContexto = null): array
    {
        if ($this->apiKey === '') {
            throw new GeminiServiceException('GEMINI_API_KEY não configurada no .env.');
        }

        $payload = $this->montarPayload($relatoCidadao, $categoriaContexto);
        $url = sprintf($this->endpointTemplate, $this->model, $this->apiKey);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
            CURLOPT_POSTFIELDS     => json_encode($payload, JSON_UNESCAPED_UNICODE),
            CURLOPT_TIMEOUT        => 15,
        ]);

        $respostaBruta = curl_exec($ch);
        $erroCurl = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($respostaBruta === false || $respostaBruta === '') {
            echo "ERRO DE CONEXÃO (cURL): " . $erroCurl;
            exit;
        }

        if ($httpCode !== 200) {
            echo "ERRO DO GEMINI (HTTP " . $httpCode . "): " . $respostaBruta;
            exit;
        }

        $resposta = json_decode($respostaBruta, true);
        $textoJson = $resposta['candidates'][0]['content']['parts'][0]['text'] ?? null;

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

        return $traducao;
    }

    private function montarSystemInstruction(): string
    {
        return 'Você é o motor de tradução jurídica do sistema público "Direito Expresso". '
             . 'Você aplica os eixos do Pacto Nacional do Judiciário pela Linguagem Simples (CNJ). '
             . 'Você NÃO é advogado, não emite parecer de risco processual e não inventa leis, artigos '
             . 'ou números de processo. Sua única função é: '
             . '(1) identificar o termo ou situação jurídica central do relato do cidadão; '
             . '(2) traduzir esse termo para um português coloquial, curto, sem jargão; '
             . '(3) sugerir um próximo passo prático e realista; '
             . '(4) indicar a categoria do problema (Consumidor, Trabalho, Moradia, Serviços Públicos ou Outro) '
             . 'e o nome de um órgão público real do Brasil mais adequado para buscar ajuda '
             . '(ex: Procon, Defensoria Pública, Justiça do Trabalho, Juizado Especial Cível). '
             . 'Se o relato for ambíguo ou não parecer uma questão jurídica, responda com honestidade '
             . 'mesmo assim, sugerindo a categoria "Outro" e indicando a Defensoria Pública em caso de dúvida.';
    }

    private function montarPayload(string $relatoCidadao, ?string $categoriaContexto): array
    {
        $contexto = $categoriaContexto
            ? "Categoria já selecionada pelo cidadão no totem: {$categoriaContexto}.\n"
            : '';

        return [
            'system_instruction' => [
                'parts' => [['text' => $this->montarSystemInstruction()]],
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
                    ],
                    'required' => [
                        'termo_identificado',
                        'explicacao_coloquial',
                        'proximo_passo_sugerido',
                        'categoria_sugerida',
                        'orgao_sugerido',
                    ],
                ],
            ],
        ];
    }
}
