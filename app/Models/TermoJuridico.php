<?php
require_once __DIR__ . '/Database.php';

/**
 * O "dicionário de triagem" do sistema (seção 7.5.2 do pré-projeto).
 * TriagemController consulta findByTermoTecnico() ANTES de acionar a
 * IA — só chama o GeminiService se o termo ainda não existir aqui.
 */
class TermoJuridico
{
    public static function all(): array
    {
        $sql = 'SELECT t.*, c.nome AS categoriaNome
                FROM TermoJuridico t
                JOIN Categoria c ON c.idCategoria = t.idCategoria
                ORDER BY t.termoTecnico';
        return Database::getConnection()->query($sql)->fetchAll();
    }

    public static function find(int $idTermo): ?array
    {
        $stmt = Database::getConnection()->prepare('SELECT * FROM TermoJuridico WHERE idTermo = ?');
        $stmt->execute([$idTermo]);
        $linha = $stmt->fetch();
        return $linha ?: null;
    }

    public static function findByCategoria(int $idCategoria): array
    {
        $stmt = Database::getConnection()->prepare(
            'SELECT * FROM TermoJuridico WHERE idCategoria = ? ORDER BY termoTecnico'
        );
        $stmt->execute([$idCategoria]);
        return $stmt->fetchAll();
    }

    /**
     * Busca exata (case-insensitive) por termo técnico — é o "short-circuit"
     * citado na seção 7.5.2: se encontrar aqui, a IA nem é chamada.
     */
    public static function findByTermoTecnico(string $termo): ?array
    {
        $stmt = Database::getConnection()->prepare(
            'SELECT * FROM TermoJuridico WHERE LOWER(termoTecnico) = LOWER(?) LIMIT 1'
        );
        $stmt->execute([trim($termo)]);
        $linha = $stmt->fetch();
        return $linha ?: null;
    }

    /** Busca livre por texto — usada na página de consultas do site. */
    public static function search(string $q): array
    {
        $like = '%' . $q . '%';
        $sql = 'SELECT t.*, c.nome AS categoriaNome
                FROM TermoJuridico t
                JOIN Categoria c ON c.idCategoria = t.idCategoria
                WHERE t.termoTecnico LIKE ? OR t.explicacaoColoquial LIKE ?
                ORDER BY t.termoTecnico
                LIMIT 50';
        $stmt = Database::getConnection()->prepare($sql);
        $stmt->execute([$like, $like]);
        return $stmt->fetchAll();
    }

    public static function create(int $idCategoria, string $termoTecnico, string $explicacaoColoquial, ?string $proxPassoSugerido): int
    {
        $stmt = Database::getConnection()->prepare(
            'INSERT INTO TermoJuridico (idCategoria, termoTecnico, explicacaoColoquial, proxPassoSugerido)
             VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([$idCategoria, $termoTecnico, $explicacaoColoquial, $proxPassoSugerido]);
        return (int) Database::getConnection()->lastInsertId();
    }

    public static function update(int $idTermo, int $idCategoria, string $termoTecnico, string $explicacaoColoquial, ?string $proxPassoSugerido): bool
    {
        $stmt = Database::getConnection()->prepare(
            'UPDATE TermoJuridico
             SET idCategoria = ?, termoTecnico = ?, explicacaoColoquial = ?, proxPassoSugerido = ?
             WHERE idTermo = ?'
        );
        return $stmt->execute([$idCategoria, $termoTecnico, $explicacaoColoquial, $proxPassoSugerido, $idTermo]);
    }

    public static function delete(int $idTermo): bool
    {
        $stmt = Database::getConnection()->prepare('DELETE FROM TermoJuridico WHERE idTermo = ?');
        return $stmt->execute([$idTermo]);
    }

    /** Órgãos competentes vinculados a um termo (via TermoOrgao). */
    public static function getOrgaos(int $idTermo): array
    {
        $sql = 'SELECT o.*, tor.observacao
                FROM TermoOrgao tor
                JOIN OrgaoCompetente o ON o.idOrgao = tor.idOrgao
                WHERE tor.idTermo = ?';
        $stmt = Database::getConnection()->prepare($sql);
        $stmt->execute([$idTermo]);
        return $stmt->fetchAll();
    }

    /** Leis vinculadas a um termo (via TermoLei). */
    public static function getLeis(int $idTermo): array
    {
        $sql = 'SELECT l.*
                FROM TermoLei tl
                JOIN Lei l ON l.idLei = tl.idLei
                WHERE tl.idTermo = ?';
        $stmt = Database::getConnection()->prepare($sql);
        $stmt->execute([$idTermo]);
        return $stmt->fetchAll();
    }

    public static function linkOrgao(int $idTermo, int $idOrgao, ?string $observacao = null): bool
    {
        $stmt = Database::getConnection()->prepare(
            'INSERT IGNORE INTO TermoOrgao (idTermo, idOrgao, observacao) VALUES (?, ?, ?)'
        );
        return $stmt->execute([$idTermo, $idOrgao, $observacao]);
    }

    public static function linkLei(int $idTermo, int $idLei): bool
    {
        $stmt = Database::getConnection()->prepare(
            'INSERT IGNORE INTO TermoLei (idTermo, idLei) VALUES (?, ?)'
        );
        return $stmt->execute([$idTermo, $idLei]);
    }
}
