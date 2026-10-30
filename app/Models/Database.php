<?php
/**
 * Conexão PDO única (singleton) com o banco totem_juridico.
 * Todos os Models chamam Database::getConnection() em vez de abrir
 * suas próprias conexões.
 */
class Database
{
    private static ?PDO $instance = null;

    public static function getConnection(): PDO
    {
        if (self::$instance === null) {
            $config = require dirname(__DIR__) . '/Config/config.php';
            $db = $config['db'];

            $dsn = "mysql:host={$db['host']};port={$db['port']};dbname={$db['name']};charset={$db['charset']}";

            $opcoes = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];

            // TLS para bancos na nuvem (no XAMPP local isso fica desligado).
            if ($db['ssl']) {
                if ($db['ssl_ca'] !== '') {
                    $opcoes[PDO::MYSQL_ATTR_SSL_CA] = $db['ssl_ca'];
                    $opcoes[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = true;
                } else {
                    $opcoes[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = false;
                }
            }

            self::$instance = new PDO($dsn, $db['user'], $db['pass'], $opcoes);
        }

        return self::$instance;
    }
}