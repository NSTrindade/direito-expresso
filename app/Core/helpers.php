<?php
/**
 * Funções pequenas e compartilhadas pelos três front controllers
 * (public/index.php, totem.php, admin.php) e por todos os Controllers.
 */

function renderView(string $view, array $dados = []): void
{
    extract($dados);
    $caminho = dirname(__DIR__) . '/Views/' . $view . '.php';

    if (!file_exists($caminho)) {
        http_response_code(500);
        echo "View não encontrada: {$view}";
        return;
    }

    require $caminho;
}

/** Escapa texto para saída segura em HTML (proteção contra XSS). */
function e(?string $valor): string
{
    return htmlspecialchars($valor ?? '', ENT_QUOTES, 'UTF-8');
}

function redirecionarPara(string $url): void
{
    header('Location: ' . $url);
    exit;
}

function definirMensagem(string $tipo, string $texto): void
{
    $_SESSION['flash'] = ['tipo' => $tipo, 'texto' => $texto];
}

function consumirMensagem(): ?array
{
    if (!empty($_SESSION['flash'])) {
        $mensagem = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $mensagem;
    }
    return null;
}

/** Formata "2026-09-17" -> "17/09/2026" nas Views, sem precisar de DateTime toda hora. */
function dataBr(?string $dataIso): string
{
    if (!$dataIso) {
        return '';
    }
    $timestamp = strtotime($dataIso);
    return $timestamp ? date('d/m/Y', $timestamp) : '';
}
