<?php
require_once __DIR__ . '/../Models/Categoria.php';
require_once __DIR__ . '/../Models/Lei.php';
require_once __DIR__ . '/../Models/TermoJuridico.php';
require_once __DIR__ . '/../Models/OrgaoCompetente.php';
require_once __DIR__ . '/../Models/Usuario.php';
require_once __DIR__ . '/../Models/Consulta.php';

/**
 * Painel administrativo (RF_04, RF_05, RF_06). Todo método aqui só é
 * alcançável depois de AuthController::exigirLogin() em admin.php.
 */
class AdminController
{
    public static function dashboard(): void
    {
        renderView('admin/dashboard', [
            'usuarioLogado' => $_SESSION['admin_nome'] ?? '',
            'totalCategorias' => count(Categoria::all()),
            'totalTermos' => count(TermoJuridico::all()),
            'consultasRecentes' => Consulta::recentes(10),
            'termosMaisConsultados' => Consulta::termosMaisConsultados(5),
            'mensagem' => consumirMensagem(),
        ]);
    }

    // -------------------------------------------------------------
    // Categorias
    // -------------------------------------------------------------
    public static function categorias(): void
    {
        $acao = $_GET['acao'] ?? 'listar';
        $metodo = $_SERVER['REQUEST_METHOD'];

        if ($acao === 'criar' && $metodo === 'POST') {
            Categoria::create(trim($_POST['nome']), self::vazioParaNull($_POST['descricao'] ?? ''));
            definirMensagem('sucesso', 'Categoria criada com sucesso.');
            redirecionarPara('admin.php?page=categorias');
        }

        if ($acao === 'editar' && $metodo === 'POST') {
            Categoria::update((int) $_POST['idCategoria'], trim($_POST['nome']), self::vazioParaNull($_POST['descricao'] ?? ''));
            definirMensagem('sucesso', 'Categoria atualizada com sucesso.');
            redirecionarPara('admin.php?page=categorias');
        }

        if ($acao === 'excluir' && $metodo === 'POST') {
            Categoria::delete((int) $_POST['idCategoria']);
            definirMensagem('sucesso', 'Categoria removida.');
            redirecionarPara('admin.php?page=categorias');
        }

        renderView('admin/categorias', [
            'usuarioLogado' => $_SESSION['admin_nome'] ?? '',
            'categorias' => Categoria::all(),
            'categoriaEmEdicao' => $acao === 'editar' && isset($_GET['id']) ? Categoria::find((int) $_GET['id']) : null,
            'mensagem' => consumirMensagem(),
        ]);
    }

    // -------------------------------------------------------------
    // Leis
    // -------------------------------------------------------------
    public static function leis(): void
    {
        $acao = $_GET['acao'] ?? 'listar';
        $metodo = $_SERVER['REQUEST_METHOD'];

        if ($acao === 'criar' && $metodo === 'POST') {
            Lei::create(
                (int) $_POST['idCategoria'],
                trim($_POST['numeroLei']),
                trim($_POST['tipoNorma']),
                trim($_POST['ementa']),
                self::vazioParaNull($_POST['dataPublicacao'] ?? '')
            );
            definirMensagem('sucesso', 'Lei cadastrada com sucesso.');
            redirecionarPara('admin.php?page=leis');
        }

        if ($acao === 'editar' && $metodo === 'POST') {
            Lei::update(
                (int) $_POST['idLei'],
                (int) $_POST['idCategoria'],
                trim($_POST['numeroLei']),
                trim($_POST['tipoNorma']),
                trim($_POST['ementa']),
                self::vazioParaNull($_POST['dataPublicacao'] ?? '')
            );
            definirMensagem('sucesso', 'Lei atualizada com sucesso.');
            redirecionarPara('admin.php?page=leis');
        }

        if ($acao === 'excluir' && $metodo === 'POST') {
            Lei::delete((int) $_POST['idLei']);
            definirMensagem('sucesso', 'Lei removida.');
            redirecionarPara('admin.php?page=leis');
        }

        renderView('admin/leis', [
            'usuarioLogado' => $_SESSION['admin_nome'] ?? '',
            'leis' => Lei::all(),
            'categorias' => Categoria::all(),
            'leiEmEdicao' => $acao === 'editar' && isset($_GET['id']) ? Lei::find((int) $_GET['id']) : null,
            'mensagem' => consumirMensagem(),
        ]);
    }

    // -------------------------------------------------------------
    // Órgãos competentes
    // -------------------------------------------------------------
    public static function orgaos(): void
    {
        $acao = $_GET['acao'] ?? 'listar';
        $metodo = $_SERVER['REQUEST_METHOD'];

        if ($acao === 'criar' && $metodo === 'POST') {
            OrgaoCompetente::create(
                trim($_POST['nome']),
                self::vazioParaNull($_POST['tipoOrgao'] ?? ''),
                self::vazioParaNull($_POST['endereco'] ?? ''),
                self::vazioParaNull($_POST['telefone'] ?? ''),
                self::vazioParaNull($_POST['site'] ?? '')
            );
            definirMensagem('sucesso', 'Órgão cadastrado com sucesso.');
            redirecionarPara('admin.php?page=orgaos');
        }

        if ($acao === 'editar' && $metodo === 'POST') {
            OrgaoCompetente::update(
                (int) $_POST['idOrgao'],
                trim($_POST['nome']),
                self::vazioParaNull($_POST['tipoOrgao'] ?? ''),
                self::vazioParaNull($_POST['endereco'] ?? ''),
                self::vazioParaNull($_POST['telefone'] ?? ''),
                self::vazioParaNull($_POST['site'] ?? '')
            );
            definirMensagem('sucesso', 'Órgão atualizado com sucesso.');
            redirecionarPara('admin.php?page=orgaos');
        }

        if ($acao === 'excluir' && $metodo === 'POST') {
            OrgaoCompetente::delete((int) $_POST['idOrgao']);
            definirMensagem('sucesso', 'Órgão removido.');
            redirecionarPara('admin.php?page=orgaos');
        }

        renderView('admin/orgaos', [
            'usuarioLogado' => $_SESSION['admin_nome'] ?? '',
            'orgaos' => OrgaoCompetente::all(),
            'orgaoEmEdicao' => $acao === 'editar' && isset($_GET['id']) ? OrgaoCompetente::find((int) $_GET['id']) : null,
            'mensagem' => consumirMensagem(),
        ]);
    }

    // -------------------------------------------------------------
    // Termos jurídicos (o dicionário de triagem)
    // -------------------------------------------------------------
    public static function termos(): void
    {
        $acao = $_GET['acao'] ?? 'listar';
        $metodo = $_SERVER['REQUEST_METHOD'];

        if ($acao === 'criar' && $metodo === 'POST') {
            $idTermo = TermoJuridico::create(
                (int) $_POST['idCategoria'],
                trim($_POST['termoTecnico']),
                trim($_POST['explicacaoColoquial']),
                self::vazioParaNull($_POST['proxPassoSugerido'] ?? '')
            );
            self::salvarVinculos($idTermo);
            definirMensagem('sucesso', 'Termo cadastrado com sucesso.');
            redirecionarPara('admin.php?page=termos');
        }

        if ($acao === 'editar' && $metodo === 'POST') {
            $idTermo = (int) $_POST['idTermo'];
            TermoJuridico::update(
                $idTermo,
                (int) $_POST['idCategoria'],
                trim($_POST['termoTecnico']),
                trim($_POST['explicacaoColoquial']),
                self::vazioParaNull($_POST['proxPassoSugerido'] ?? '')
            );
            self::salvarVinculos($idTermo);
            definirMensagem('sucesso', 'Termo atualizado com sucesso.');
            redirecionarPara('admin.php?page=termos');
        }

        if ($acao === 'excluir' && $metodo === 'POST') {
            TermoJuridico::delete((int) $_POST['idTermo']);
            definirMensagem('sucesso', 'Termo removido.');
            redirecionarPara('admin.php?page=termos');
        }

        $termoEmEdicao = null;
        $orgaosVinculados = [];
        $leisVinculadas = [];

        if ($acao === 'editar' && isset($_GET['id'])) {
            $idTermo = (int) $_GET['id'];
            $termoEmEdicao = TermoJuridico::find($idTermo);
            $orgaosVinculados = array_column(TermoJuridico::getOrgaos($idTermo), 'idOrgao');
            $leisVinculadas = array_column(TermoJuridico::getLeis($idTermo), 'idLei');
        }

        renderView('admin/termos', [
            'usuarioLogado' => $_SESSION['admin_nome'] ?? '',
            'termos' => TermoJuridico::all(),
            'categorias' => Categoria::all(),
            'orgaosTodos' => OrgaoCompetente::all(),
            'leisTodas' => Lei::all(),
            'termoEmEdicao' => $termoEmEdicao,
            'orgaosVinculados' => $orgaosVinculados,
            'leisVinculadas' => $leisVinculadas,
            'mensagem' => consumirMensagem(),
        ]);
    }

    /** Salva os checkboxes de órgãos/leis marcados no formulário de termo. */
    private static function salvarVinculos(int $idTermo): void
    {
        foreach ($_POST['orgaos'] ?? [] as $idOrgao) {
            TermoJuridico::linkOrgao($idTermo, (int) $idOrgao);
        }
        foreach ($_POST['leis'] ?? [] as $idLei) {
            TermoJuridico::linkLei($idTermo, (int) $idLei);
        }
    }

    // -------------------------------------------------------------
    // Usuários administradores (RF_06)
    // -------------------------------------------------------------
    public static function usuarios(): void
    {
        $acao = $_GET['acao'] ?? 'listar';
        $metodo = $_SERVER['REQUEST_METHOD'];

        if ($acao === 'criar' && $metodo === 'POST') {
            if (Usuario::findByEmail(trim($_POST['email']))) {
                definirMensagem('erro', 'Já existe um usuário com esse e-mail.');
                redirecionarPara('admin.php?page=usuarios');
            }
            Usuario::create(trim($_POST['nome']), trim($_POST['email']), $_POST['senha']);
            definirMensagem('sucesso', 'Usuário criado com sucesso.');
            redirecionarPara('admin.php?page=usuarios');
        }

        if ($acao === 'editar' && $metodo === 'POST') {
            Usuario::update((int) $_POST['idUsuario'], trim($_POST['nome']), trim($_POST['email']));
            if (!empty($_POST['senha'])) {
                Usuario::updateSenha((int) $_POST['idUsuario'], $_POST['senha']);
            }
            definirMensagem('sucesso', 'Usuário atualizado com sucesso.');
            redirecionarPara('admin.php?page=usuarios');
        }

        if ($acao === 'excluir' && $metodo === 'POST') {
            if ((int) $_POST['idUsuario'] === (int) $_SESSION['admin_id']) {
                definirMensagem('erro', 'Você não pode excluir o próprio usuário logado.');
            } else {
                Usuario::delete((int) $_POST['idUsuario']);
                definirMensagem('sucesso', 'Usuário removido.');
            }
            redirecionarPara('admin.php?page=usuarios');
        }

        renderView('admin/usuarios', [
            'usuarioLogado' => $_SESSION['admin_nome'] ?? '',
            'usuarios' => Usuario::all(),
            'usuarioEmEdicao' => $acao === 'editar' && isset($_GET['id']) ? Usuario::find((int) $_GET['id']) : null,
            'mensagem' => consumirMensagem(),
        ]);
    }

    private static function vazioParaNull(string $valor): ?string
    {
        $valor = trim($valor);
        return $valor === '' ? null : $valor;
    }
}
