<?php
require_once __DIR__ . '/Database.php';

/**
 * Representa os administradores do painel web (RF_05/RF_06).
 * O cidadão que usa o totem ou o site NÃO tem conta aqui — só quem
 * gerencia o conteúdo jurídico precisa fazer login.
 */
class Usuario
{
    public static function all(): array
    {
        return Database::getConnection()
            ->query('SELECT idUsuario, nome, email, tipoUsuario, dataCadastro FROM Usuario ORDER BY nome')
            ->fetchAll();
    }

    public static function find(int $idUsuario): ?array
    {
        $stmt = Database::getConnection()->prepare(
            'SELECT idUsuario, nome, email, tipoUsuario, dataCadastro FROM Usuario WHERE idUsuario = ?'
        );
        $stmt->execute([$idUsuario]);
        $linha = $stmt->fetch();
        return $linha ?: null;
    }

    public static function findByEmail(string $email): ?array
    {
        $stmt = Database::getConnection()->prepare('SELECT * FROM Usuario WHERE email = ?');
        $stmt->execute([$email]);
        $linha = $stmt->fetch();
        return $linha ?: null;
    }

    public static function create(string $nome, string $email, string $senhaPlana): int
    {
        $hash = password_hash($senhaPlana, PASSWORD_DEFAULT);
        $stmt = Database::getConnection()->prepare(
            "INSERT INTO Usuario (nome, email, senha, tipoUsuario) VALUES (?, ?, ?, 'admin')"
        );
        $stmt->execute([$nome, $email, $hash]);
        return (int) Database::getConnection()->lastInsertId();
    }

    public static function update(int $idUsuario, string $nome, string $email): bool
    {
        $stmt = Database::getConnection()->prepare(
            'UPDATE Usuario SET nome = ?, email = ? WHERE idUsuario = ?'
        );
        return $stmt->execute([$nome, $email, $idUsuario]);
    }

    public static function updateSenha(int $idUsuario, string $novaSenhaPlana): bool
    {
        $hash = password_hash($novaSenhaPlana, PASSWORD_DEFAULT);
        $stmt = Database::getConnection()->prepare('UPDATE Usuario SET senha = ? WHERE idUsuario = ?');
        return $stmt->execute([$hash, $idUsuario]);
    }

    public static function delete(int $idUsuario): bool
    {
        $stmt = Database::getConnection()->prepare('DELETE FROM Usuario WHERE idUsuario = ?');
        return $stmt->execute([$idUsuario]);
    }

    /** Usado pelo AuthController no login: retorna o usuário (sem a senha) se as credenciais baterem. */
    public static function verificarCredenciais(string $email, string $senhaPlana): ?array
    {
        $usuario = self::findByEmail($email);
        if ($usuario && password_verify($senhaPlana, $usuario['senha'])) {
            unset($usuario['senha']);
            return $usuario;
        }
        return null;
    }
}
