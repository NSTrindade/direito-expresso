<?php
/**
 * Carrega variáveis do .env (sem depender de Composer/vlucas) e devolve
 * a configuração da aplicação como array. Chamado por Database.php,
 * GeminiService.php e pela View do totem — por isso este arquivo
 * precisa ser seguro para ser incluído mais de uma vez na mesma
 * requisição (daí o function_exists() e o cache em $GLOBALS abaixo).
 */

if (!function_exists('direitoExpresso_loadEnv')) {
    function direitoExpresso_loadEnv(string $path): void
    {
        if (!is_readable($path)) {
            return;
        }

        foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $linha) {
            $linha = trim($linha);
            if ($linha === '' || str_starts_with($linha, '#')) {
                continue;
            }

            [$chave, $valor] = array_pad(explode('=', $linha, 2), 2, '');
            $chave = trim($chave);
            $valor = trim($valor, " \t\n\r\0\x0B\"'");

            if ($chave !== '' && getenv($chave) === false) {
                putenv("{$chave}={$valor}");
                $_ENV[$chave] = $valor;
            }
        }
    }
}

if (!isset($GLOBALS['__direitoExpressoConfig'])) {
    direitoExpresso_loadEnv(dirname(__DIR__, 2) . '/.env');

    $GLOBALS['__direitoExpressoConfig'] = [
        'db' => [
            'host'    => getenv('DB_HOST') ?: '127.0.0.1',
            'port'    => getenv('DB_PORT') ?: '3306',
            'name'    => getenv('DB_NAME') ?: 'totem_juridico',
            'user'    => getenv('DB_USER') ?: 'root',
            'pass'    => getenv('DB_PASS') ?: '',
            'charset' => 'utf8mb4',
            'ssl'     => in_array(strtolower((string) getenv('DB_SSL')), ['1', 'true', 'yes'], true),
            'ssl_ca'  => getenv('DB_SSL_CA') ?: '',
        ],
        'gemini' => [
            'api_key'  => getenv('GEMINI_API_KEY') ?: '',
            'model'    => getenv('GEMINI_MODEL') ?: 'gemini-3.6-flash',
            'endpoint' => 'https://generativelanguage.googleapis.com/v1beta/models/%s:generateContent?key=%s',
        ],
        'app' => [
            'env'                    => getenv('APP_ENV') ?: 'development',
            'totem_timeout_segundos' => 90, // inatividade no Kiosk Mode antes de resetar a tela
        ],
    ];
}

return $GLOBALS['__direitoExpressoConfig'];