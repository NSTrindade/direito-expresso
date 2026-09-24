<?php
/**
 * Front controller do painel ADMINISTRATIVO.
 * Rotas: admin.php?page=login|dashboard|categorias|leis|orgaos|termos|usuarios
 */
declare(strict_types=1);
session_start();

require_once __DIR__ . '/../app/Core/helpers.php';
require_once __DIR__ . '/../app/Controllers/AuthController.php';
require_once __DIR__ . '/../app/Controllers/AdminController.php';

$pagina = $_GET['page'] ?? 'dashboard';

if ($pagina === 'login') {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        AuthController::processarLogin();
    } else {
        AuthController::login();
    }
    exit;
}

if ($pagina === 'logout') {
    AuthController::logout();
    exit;
}

AuthController::exigirLogin();

switch ($pagina) {
    case 'categorias':
        AdminController::categorias();
        break;

    case 'leis':
        AdminController::leis();
        break;

    case 'orgaos':
        AdminController::orgaos();
        break;

    case 'termos':
        AdminController::termos();
        break;

    case 'usuarios':
        AdminController::usuarios();
        break;

    case 'dashboard':
    default:
        AdminController::dashboard();
        break;
}
