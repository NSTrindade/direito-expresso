<?php
require_once __DIR__ . '/Database.php';

class Categoria
{
    public static function all(): array
    {
        return Database::getConnection()
            ->query('SELECT * FROM Categoria ORDER BY nome')
            ->fetchAll();
    }

    public static function find(int $idCategoria): ?array
    {
        $stmt = Database::getConnection()->prepare('SELECT * FROM Categoria WHERE idCategoria = ?');
        $stmt->execute([$idCategoria]);
        $linha = $stmt->fetch();
        return $linha ?: null;
    }

    /** Usado pelo TriagemController para casar a "categoria_sugerida" da IA com uma categoria real do banco. */
    public static function findByNomeAproximado(string $nome): ?array
    {
        $stmt = Database::getConnection()->prepare('SELECT * FROM Categoria WHERE nome LIKE ? LIMIT 1');
        $stmt->execute(['%' . $nome . '%']);
        $linha = $stmt->fetch();
        return $linha ?: null;
    }

    public static function create(string $nome, ?string $descricao): int
    {
        $stmt = Database::getConnection()->prepare(
            'INSERT INTO Categoria (nome, descricao) VALUES (?, ?)'
        );
        $stmt->execute([$nome, $descricao]);
        return (int) Database::getConnection()->lastInsertId();
    }

    public static function update(int $idCategoria, string $nome, ?string $descricao): bool
    {
        $stmt = Database::getConnection()->prepare(
            'UPDATE Categoria SET nome = ?, descricao = ? WHERE idCategoria = ?'
        );
        return $stmt->execute([$nome, $descricao, $idCategoria]);
    }

    public static function delete(int $idCategoria): bool
    {
        $stmt = Database::getConnection()->prepare('DELETE FROM Categoria WHERE idCategoria = ?');
        return $stmt->execute([$idCategoria]);
    }
}
