<?php
require_once __DIR__ . '/Database.php';

class OrgaoCompetente
{
    public static function all(): array
    {
        return Database::getConnection()
            ->query('SELECT * FROM OrgaoCompetente ORDER BY nome')
            ->fetchAll();
    }

    public static function find(int $idOrgao): ?array
    {
        $stmt = Database::getConnection()->prepare('SELECT * FROM OrgaoCompetente WHERE idOrgao = ?');
        $stmt->execute([$idOrgao]);
        $linha = $stmt->fetch();
        return $linha ?: null;
    }

    /** Usado pelo TriagemController para casar o texto sugerido pela IA com um órgão real do banco. */
    public static function findByNomeAproximado(string $nome): ?array
    {
        $stmt = Database::getConnection()->prepare(
            'SELECT * FROM OrgaoCompetente WHERE nome LIKE ? LIMIT 1'
        );
        $stmt->execute(['%' . $nome . '%']);
        $linha = $stmt->fetch();
        return $linha ?: null;
    }

    public static function create(string $nome, ?string $tipoOrgao, ?string $endereco, ?string $telefone, ?string $site): int
    {
        $stmt = Database::getConnection()->prepare(
            'INSERT INTO OrgaoCompetente (nome, tipoOrgao, endereco, telefone, site) VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([$nome, $tipoOrgao, $endereco, $telefone, $site]);
        return (int) Database::getConnection()->lastInsertId();
    }

    public static function update(int $idOrgao, string $nome, ?string $tipoOrgao, ?string $endereco, ?string $telefone, ?string $site): bool
    {
        $stmt = Database::getConnection()->prepare(
            'UPDATE OrgaoCompetente SET nome = ?, tipoOrgao = ?, endereco = ?, telefone = ?, site = ? WHERE idOrgao = ?'
        );
        return $stmt->execute([$nome, $tipoOrgao, $endereco, $telefone, $site, $idOrgao]);
    }

    public static function delete(int $idOrgao): bool
    {
        $stmt = Database::getConnection()->prepare('DELETE FROM OrgaoCompetente WHERE idOrgao = ?');
        return $stmt->execute([$idOrgao]);
    }
}
