<?php
require_once __DIR__ . '/../Models/Usuario.php';

/**
 * Login/logout do painel administrativo (RF_05).
 * O Cidadão nunca passa por aqui — só o Administrador.
 */
class AuthController
{
    public static function login(): void
    {
        renderView('admin/login', [
            'erro' => null,
        ]);
    }

    public static function processarLogin(): void
    {
        $email = trim($_POST['email'] ?? '');
        $senha = $_POST['senha'] ?? '';

        $usuario = Usuario::verificarCredenciais($email, $senha);

        if (!$usuario) {
            renderView('admin/login', [
                'erro' => 'E-mail ou senha inválidos.',
            ]);
            return;
        }

        $_SESSION['admin_id'] = $usuario['idUsuario'];
        $_SESSION['admin_nome'] = $usuario['nome'];

        redirecionarPara('admin.php?page=dashboard');
    }

    public static function logout(): void
    {
        unset($_SESSION['admin_id'], $_SESSION['admin_nome']);
        redirecionarPara('admin.php?page=login');
    }

    /** Chamado no topo de admin.php para toda página exceto login. */
    public static function exigirLogin(): void
    {
        if (empty($_SESSION['admin_id'])) {
            redirecionarPara('admin.php?page=login');
        }
    }
}
