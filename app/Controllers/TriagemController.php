<?php
require_once __DIR__ . '/../Models/Categoria.php';
require_once __DIR__ . '/../Models/TermoJuridico.php';
require_once __DIR__ . '/../Models/OrgaoCompetente.php';
require_once __DIR__ . '/../Models/Lei.php';
require_once __DIR__ . '/../Models/Consulta.php';
require_once __DIR__ . '/../Services/GeminiService.php';

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
        unset($_SESSION['totem_chat']);
        renderView('totem/inicio', []);
    }

    /** RF_07 — Selecionar Categoria. */
    public static function totemCategorias(): void
    {
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
        $idCategoria = isset($_GET['categoria']) ? (int) $_GET['categoria'] : null;
        $categoria = $idCategoria ? Categoria::find($idCategoria) : null;

        renderView('totem/chatbot', [
            'categoria' => $categoria,
            'conversa' => $_SESSION['totem_chat'] ?? [],
            'erroApi' => $_SESSION['totem_chat_erro'] ?? null,
        ]);

        unset($_SESSION['totem_chat_erro']);
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
        } else {
            try {
                $gemini = new GeminiService();
                $traducao = $gemini->traduzir($relato, $categoria['nome'] ?? null);

                $idCategoriaResolvida = self::resolverIdCategoria($traducao['categoria_sugerida'], $idCategoria);

                $idTermo = TermoJuridico::create(
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
            } catch (GeminiServiceException $e) {
                // Fluxo Alternativo do RF_03: avisa e sugere voltar pra consulta textual.
                $_SESSION['totem_chat_erro'] = 'A assistente virtual está temporariamente indisponível. '
                    . 'Tente novamente em instantes ou volte para a lista de dúvidas.';
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
