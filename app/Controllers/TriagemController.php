<?php
require_once __DIR__ . '/../Models/Categoria.php';
require_once __DIR__ . '/../Models/TermoJuridico.php';
require_once __DIR__ . '/../Models/OrgaoCompetente.php';
require_once __DIR__ . '/../Models/Lei.php';
require_once __DIR__ . '/../Models/Consulta.php';
require_once __DIR__ . '/../Services/GeminiService.php';
require_once __DIR__ . '/../Services/Esp32Ponte.php';

/**
 * Controlador central da triagem — usado pelo site (busca livre) e
 * pelo totem (fluxo passo a passo RF_01, RF_03, RF_07, RF_08).
 */
class TriagemController
{
    // -------------------------------------------------------------
    // SITE (versão Web) — página de consultas / busca livre
    // -------------------------------------------------------------

    public static function webConsultas(): void
    {
        $q = trim($_GET['q'] ?? '');
        $idCategoria = isset($_GET['categoria']) && $_GET['categoria'] !== '' ? (int) $_GET['categoria'] : null;

        if ($q !== '') {
            $termos = TermoJuridico::search($q);
            $leis = Lei::search($q);
        } elseif ($idCategoria) {
            $termos = TermoJuridico::findByCategoria($idCategoria);
            $leis = [];
        } else {
            $termos = TermoJuridico::all();
            $leis = [];
        }

        renderView('web/consultas', [
            'q' => $q,
            'idCategoriaSelecionada' => $idCategoria,
            'categorias' => Categoria::all(),
            'termos' => $termos,
            'leis' => $leis,
        ]);
    }

    // -------------------------------------------------------------
    // TOTEM — Kiosk Mode
    // -------------------------------------------------------------

    /** Tela de descanso / boas-vindas. Sempre limpa o estado da triagem anterior. */
    public static function totemInicio(): void
    {
        unset($_SESSION['totem_chat'], $_SESSION['totem_area']);
        // o botão físico também volta para "FALE COM A IA"
        // (site no Render: o servidor não alcança a ponte e o navegador avisa — totem-esp32.js)
        $esp32Resetado = Esp32Ponte::resetar();
        renderView('totem/inicio', ['esp32Resetado' => $esp32Resetado]);
    }

    /**
     * Página aberta pelos botões do ESP32: totem.php?page=area&codigo=CONSUMIDOR
     * Procura a categoria pelo NOME (não depende do id do banco) e abre as dúvidas dela.
     */
    public static function totemArea(): void
    {
        $codigo = strtoupper(trim($_GET['codigo'] ?? ''));
        $nome = Esp32Ponte::AREAS[$codigo] ?? null;
        $categoria = $nome ? Categoria::findByNomeAproximado($nome) : null;

        if (!$categoria) {
            redirecionarPara('totem.php?page=categorias');
        }
        redirecionarPara('totem.php?page=duvidas&categoria=' . (int) $categoria['idCategoria']);
    }

    /** RF_07 — Selecionar Categoria. */
    public static function totemCategorias(): void
    {
        // Se vier uma categoria no endereço (ex.: ponte do ESP32 antiga:
        // totem.php?page=categorias&categoria=moradia), abre a área direto.
        $pedido = trim((string) ($_GET['categoria'] ?? ''));
        if ($pedido !== '') {
            if (ctype_digit($pedido)) {
                $categoria = Categoria::find((int) $pedido);
            } else {
                $codigo = Esp32Ponte::codigoPorNome($pedido);
                $categoria = Categoria::findByNomeAproximado($codigo ? Esp32Ponte::AREAS[$codigo] : $pedido);
            }
            if ($categoria) {
                redirecionarPara('totem.php?page=duvidas&categoria=' . (int) $categoria['idCategoria']);
            }
        }

        renderView('totem/categorias', [
            'categorias' => Categoria::all(),
        ]);
    }

    /** RF_01 — Consultar Dúvida / Direito (lista os termos daquela categoria). */
    public static function totemDuvidas(): void
    {
        $idCategoria = (int) ($_GET['categoria'] ?? 0);
        $categoria = Categoria::find($idCategoria);

        if (!$categoria) {
            redirecionarPara('totem.php?page=categorias');
        }

        renderView('totem/duvidas', [
            'categoria' => $categoria,
            'termos' => TermoJuridico::findByCategoria($idCategoria),
        ]);
    }

    /** RF_08 — Visualizar Orientação e Órgão Competente. */
    public static function totemResultado(): void
    {
        $idTermo = (int) ($_GET['termo'] ?? 0);
        $termo = TermoJuridico::find($idTermo);

        if (!$termo) {
            redirecionarPara('totem.php?page=categorias');
        }

        $orgaos = TermoJuridico::getOrgaos($idTermo);
        $leis = TermoJuridico::getLeis($idTermo);

        Consulta::registrar(
            idTermo: $idTermo,
            canalOrigem: 'totem',
            idTotem: self::idTotemPadrao(),
            resultadoApresentado: $termo['explicacaoColoquial']
        );

        renderView('totem/resultado', [
            'termo' => $termo,
            'orgaos' => $orgaos,
            'leis' => $leis,
        ]);
    }

    /** RF_03 — Interagir com a IA (Chatbot de Tradução). Tela GET: mostra a conversa. */
    public static function totemChatbotGet(): void
    {
        // ?novo=1 vem do botão "FALE COM A IA" do ESP32: começa uma conversa limpa
        if (isset($_GET['novo'])) {
            unset($_SESSION['totem_chat'], $_SESSION['totem_area'], $_SESSION['totem_chat_erro']);
        }

        $idCategoria = isset($_GET['categoria']) ? (int) $_GET['categoria'] : null;
        $categoria = $idCategoria ? Categoria::find($idCategoria) : null;

        renderView('totem/chatbot', [
            'categoria' => $categoria,
            'conversa' => $_SESSION['totem_chat'] ?? [],
            'erroApi' => $_SESSION['totem_chat_erro'] ?? null,
            // motivo técnico do erro da IA: só aparece fora de APP_ENV=production
            'erroDetalhe' => (getenv('APP_ENV') ?: 'development') !== 'production'
                ? ($_SESSION['totem_chat_erro_detalhe'] ?? null) : null,
            'areaSugerida' => $_SESSION['totem_area'] ?? null,
        ]);

        unset($_SESSION['totem_chat_erro'], $_SESSION['totem_chat_erro_detalhe']);
    }

    /**
     * RF_03 — processa uma mensagem do cidadão no chatbot.
     * Segue o diagrama de sequência da seção 8.3: primeiro tenta achar
     * o termo já cadastrado (cache); só chama a IA se não encontrar.
     * Padrão Post-Redirect-Get: sempre redireciona de volta pro GET,
     * pra evitar reenvio do formulário se o cidadão atualizar a página.
     */
    public static function totemChatbotPost(): void
    {
        $relato = trim($_POST['relato'] ?? '');
        $idCategoria = isset($_POST['categoria']) && $_POST['categoria'] !== '' ? (int) $_POST['categoria'] : null;
        $categoria = $idCategoria ? Categoria::find($idCategoria) : null;

        if ($relato === '') {
            redirecionarPara('totem.php?page=chatbot' . ($idCategoria ? "&categoria={$idCategoria}" : ''));
        }

        $_SESSION['totem_chat'] = $_SESSION['totem_chat'] ?? [];
        $_SESSION['totem_chat'][] = ['autor' => 'cidadao', 'texto' => $relato];

        // 1) Tenta o cache antes de gastar chamada de IA (seção 7.5.2 / 8.3)
        $termoCache = TermoJuridico::findByTermoTecnico($relato);

        if ($termoCache) {
            $idTermo = (int) $termoCache['idTermo'];
            $textoResposta = $termoCache['explicacaoColoquial'] . "\n\n👉 " . $termoCache['proxPassoSugerido'];

            $categoriaDoTermo = Categoria::find((int) $termoCache['idCategoria']);
            self::sugerirAreaNoTotem(Esp32Ponte::codigoPorNome($categoriaDoTermo['nome'] ?? null), 'cadastro');
        } else {
            try {
                $gemini = new GeminiService();
                $traducao = $gemini->traduzir(
                    $relato,
                    $categoria['nome'] ?? null,
                    array_column(Categoria::all(), 'nome')
                );

                $idCategoriaResolvida = self::resolverIdCategoria($traducao['categoria_sugerida'], $idCategoria);

                // Se a IA identificar um termo que já está no banco, reaproveita.
                // (termoTecnico é UNIQUE: cadastrar de novo derrubava o chat com erro 500)
                $termoExistente = TermoJuridico::findByTermoTecnico($traducao['termo_identificado']);
                $idTermo = $termoExistente
                    ? (int) $termoExistente['idTermo']
                    : TermoJuridico::create(
                        $idCategoriaResolvida,
                        $traducao['termo_identificado'],
                        $traducao['explicacao_coloquial'],
                        $traducao['proximo_passo_sugerido']
                    );

                $orgao = OrgaoCompetente::findByNomeAproximado($traducao['orgao_sugerido']);
                if ($orgao) {
                    TermoJuridico::linkOrgao($idTermo, (int) $orgao['idOrgao']);
                }

                $textoResposta = $traducao['explicacao_coloquial'] . "\n\n👉 " . $traducao['proximo_passo_sugerido']
                    . "\n\n📍 Procure: " . $traducao['orgao_sugerido'];

                self::sugerirAreaNoTotem(Esp32Ponte::codigoPorNome($traducao['categoria_sugerida']), 'ia');
            } catch (GeminiServiceException $e) {
                // Mesmo sem IA, o totem físico ainda indica a área pelas palavras do relato
                error_log('[Direito Expresso] Gemini: ' . $e->getMessage());
                $relatosDoCidadao = array_column(
                    array_filter($_SESSION['totem_chat'], fn($m) => $m['autor'] === 'cidadao'),
                    'texto'
                );
                self::sugerirAreaNoTotem(Esp32Ponte::classificarPorPalavras(implode("\n", $relatosDoCidadao)), 'palavras-chave');

                // Fluxo Alternativo do RF_03: avisa e sugere voltar pra consulta textual.
                $_SESSION['totem_chat_erro'] = 'A assistente virtual está temporariamente indisponível. '
                    . 'Tente novamente em instantes ou volte para a lista de dúvidas.';
                $_SESSION['totem_chat_erro_detalhe'] = self::resumirErro($e->getMessage());
                $_SESSION['totem_chat'][] = [
                    'autor' => 'ia',
                    'texto' => '⚠️ Não consegui me conectar à inteligência artificial agora. '
                        . 'Você pode tentar de novo ou usar o botão "Voltar" para ver as dúvidas já cadastradas.',
                ];
                redirecionarPara('totem.php?page=chatbot' . ($idCategoria ? "&categoria={$idCategoria}" : ''));
            }
        }

        $_SESSION['totem_chat'][] = ['autor' => 'ia', 'texto' => $textoResposta];

        Consulta::registrar(
            idTermo: $idTermo,
            canalOrigem: 'totem',
            idTotem: self::idTotemPadrao(),
            resultadoApresentado: $textoResposta
        );

        redirecionarPara('totem.php?page=chatbot' . ($idCategoria ? "&categoria={$idCategoria}" : ''));
    }

    // -------------------------------------------------------------
    // Auxiliares privados
    // -------------------------------------------------------------

    /**
     * Guarda a área sugerida (para o aviso na tela do chat) e manda o
     * código ao ESP32, que passa a mostrar o botão daquela área.
     * $codigo null = não deu para identificar (ESP32 mostra as 4 áreas).
     */
    /** Resume a mensagem de erro do Gemini (sem chave) para exibir em letra pequena. */
    private static function resumirErro(string $mensagem): string
    {
        if (preg_match('/"message"\s*:\s*"([^"]+)"/', $mensagem, $m)) {
            preg_match('/HTTP \d{3}/', $mensagem, $h);
            $mensagem = trim(($h[0] ?? '') . ' ' . $m[1]);
        }
        $mensagem = preg_replace('/(AIza|AQ\.)[A-Za-z0-9_.\-]+/', '[chave]', $mensagem);
        $mensagem = preg_replace('/\s+/', ' ', $mensagem);
        return mb_substr($mensagem, 0, 220, 'UTF-8');
    }

    private static function sugerirAreaNoTotem(?string $codigo, string $origem): void
    {
        $_SESSION['totem_area'] = [
            'codigo' => $codigo,
            'nome'   => $codigo ? Esp32Ponte::AREAS[$codigo] : null,
            'origem' => $origem,
            'enviadoAoTotem' => Esp32Ponte::enviarArea($codigo),
        ];
    }

    /**
     * Casa o nome de categoria sugerido pela IA com uma categoria real
     * do banco. Se não achar nenhuma parecida, cai de volta pra
     * categoria que o cidadão já tinha escolhido no totem (ou a
     * primeira categoria cadastrada, como último recurso).
     */
    private static function resolverIdCategoria(string $nomeSugerido, ?int $idCategoriaContexto): int
    {
        $categoria = Categoria::findByNomeAproximado($nomeSugerido);
        if ($categoria) {
            return (int) $categoria['idCategoria'];
        }

        if ($idCategoriaContexto) {
            return $idCategoriaContexto;
        }

        $todas = Categoria::all();
        return (int) $todas[0]['idCategoria'];
    }

    /**
     * ID do totem físico usado nos registros de Consulta feitos por
     * este equipamento. Em produção, cada totem guardaria o próprio ID
     * localmente (arquivo de config); aqui usamos o totem de exemplo
     * criado pelo seed.sql como padrão de demonstração.
     */
    private static function idTotemPadrao(): int
    {
        return 1;
    }
}
