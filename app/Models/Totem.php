<?php
require_once __DIR__ . '/Database.php';

class Totem
{
    public static function all(): array
    {
        return Database::getConnection()
            ->query('SELECT * FROM Totem ORDER BY localizacao')
            ->fetchAll();
    }

    public static function find(int $idTotem): ?array
    {
        $stmt = Database::getConnection()->prepare('SELECT * FROM Totem WHERE idTotem = ?');
        $stmt->execute([$idTotem]);
        $linha = $stmt->fetch();
        return $linha ?: null;
    }

    public static function create(string $localizacao, string $status = 'ativo'): int
    {
        $stmt = Database::getConnection()->prepare(
            'INSERT INTO Totem (localizacao, status, dataInstalacao) VALUES (?, ?, CURDATE())'
        );
        $stmt->execute([$localizacao, $status]);
        return (int) Database::getConnection()->lastInsertId();
    }

    public static function updateStatus(int $idTotem, string $status): bool
    {
        $stmt = Database::getConnection()->prepare('UPDATE Totem SET status = ? WHERE idTotem = ?');
        return $stmt->execute([$status, $idTotem]);
    }

    public static function delete(int $idTotem): bool
    {
        $stmt = Database::getConnection()->prepare('DELETE FROM Totem WHERE idTotem = ?');
        return $stmt->execute([$idTotem]);
    }
}
