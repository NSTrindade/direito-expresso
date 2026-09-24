<?php
require_once __DIR__ . '/Database.php';

class Lei
{
    public static function all(): array
    {
        $sql = 'SELECT l.*, c.nome AS categoriaNome
                FROM Lei l
                JOIN Categoria c ON c.idCategoria = l.idCategoria
                ORDER BY l.numeroLei';
        return Database::getConnection()->query($sql)->fetchAll();
    }

    public static function find(int $idLei): ?array
    {
        $stmt = Database::getConnection()->prepare('SELECT * FROM Lei WHERE idLei = ?');
        $stmt->execute([$idLei]);
        $linha = $stmt->fetch();
        return $linha ?: null;
    }

    /** Busca livre usada na página de consultas do site (por número ou ementa). */
    public static function search(string $termo): array
    {
        $like = '%' . $termo . '%';
        $sql = 'SELECT l.*, c.nome AS categoriaNome
                FROM Lei l
                JOIN Categoria c ON c.idCategoria = l.idCategoria
                WHERE l.numeroLei LIKE ? OR l.ementa LIKE ?
                ORDER BY l.dataPublicacao DESC
                LIMIT 50';
        $stmt = Database::getConnection()->prepare($sql);
        $stmt->execute([$like, $like]);
        return $stmt->fetchAll();
    }

    public static function create(int $idCategoria, string $numeroLei, string $tipoNorma, string $ementa, ?string $dataPublicacao): int
    {
        $stmt = Database::getConnection()->prepare(
            'INSERT INTO Lei (idCategoria, numeroLei, tipoNorma, ementa, dataPublicacao) VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([$idCategoria, $numeroLei, $tipoNorma, $ementa, $dataPublicacao]);
        return (int) Database::getConnection()->lastInsertId();
    }

    public static function update(int $idLei, int $idCategoria, string $numeroLei, string $tipoNorma, string $ementa, ?string $dataPublicacao): bool
    {
        $stmt = Database::getConnection()->prepare(
            'UPDATE Lei SET idCategoria = ?, numeroLei = ?, tipoNorma = ?, ementa = ?, dataPublicacao = ? WHERE idLei = ?'
        );
        return $stmt->execute([$idCategoria, $numeroLei, $tipoNorma, $ementa, $dataPublicacao, $idLei]);
    }

    public static function delete(int $idLei): bool
    {
        $stmt = Database::getConnection()->prepare('DELETE FROM Lei WHERE idLei = ?');
        return $stmt->execute([$idLei]);
    }
}
