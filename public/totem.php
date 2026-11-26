<?php
/**
 * Front controller da versão TOTEM (Kiosk Mode).
 * Rotas: totem.php?page=inicio|categorias|duvidas|resultado|chatbot|area
 */
declare(strict_types=1);
session_start();

require_once __DIR__ . '/../app/Core/helpers.php';
require_once __DIR__ . '/../app/Controllers/TriagemController.php';

$pagina = $_GET['page'] ?? 'inicio';

switch ($pagina) {
    case 'categorias':
        TriagemController::totemCategorias();
        break;

    case 'duvidas':
        TriagemController::totemDuvidas();
        break;

    case 'area': // aberto pelos botões do ESP32 (ponte_esp32.py)
        TriagemController::totemArea();
        break;

    case 'resultado':
        TriagemController::totemResultado();
        break;

    case 'chatbot':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            TriagemController::totemChatbotPost();
        } else {
            TriagemController::totemChatbotGet();
        }
        break;

    case 'inicio':
    default:
        TriagemController::totemInicio();
        break;
}
