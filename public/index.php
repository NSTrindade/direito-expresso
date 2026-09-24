<?php
/**
 * Front controller da versão WEB (site padrão).
 * Rotas: index.php?page=home|consultas|sobre|contato
 */
declare(strict_types=1);
session_start();

require_once __DIR__ . '/../app/Core/helpers.php';
require_once __DIR__ . '/../app/Controllers/HomeController.php';
require_once __DIR__ . '/../app/Controllers/TriagemController.php';

$pagina = $_GET['page'] ?? 'home';

switch ($pagina) {
    case 'consultas':
        TriagemController::webConsultas();
        break;

    case 'sobre':
        HomeController::sobre();
        break;

    case 'contato':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            HomeController::processarContato();
        } else {
            HomeController::contato();
        }
        break;

    case 'home':
    default:
        HomeController::index();
        break;
}
