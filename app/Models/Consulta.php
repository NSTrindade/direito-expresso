<?php
require_once __DIR__ . '/Database.php';

/**
 * Histórico de cada interação de triagem (tabela transacional do MER).
 * idUsuario e idTotem são opcionais de propósito — ver nota em
 * database/schema.sql sobre por que o cidadão não precisa de login.
 */
class Consulta
{
    public static function registrar(
        int $idTermo,
        string $canalOrigem,
        ?int $idTotem = null,
        ?int $idUsuario = null,
        ?string $resultadoApresentado = null
    ): int {
        $stmt = Database::getConnection()->prepare(
            'INSERT INTO Consulta (idUsuario, idTotem, idTermo, canalOrigem, resultadoApresentado)
             VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([$idUsuario, $idTotem, $idTermo, $canalOrigem, $resultadoApresentado]);
        return (int) Database::getConnection()->lastInsertId();
    }

    public static function recentes(int $limite = 50): array
    {
        $sql = 'SELECT c.*, t.termoTecnico
                FROM Consulta c
                JOIN TermoJuridico t ON t.idTermo = c.idTermo
                ORDER BY c.dataHora DESC
                LIMIT ?';
        $stmt = Database::getConnection()->prepare($sql);
        $stmt->bindValue(1, $limite, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /** Ranking dos termos mais consultados — útil para o dashboard do admin. */
    public static function termosMaisConsultados(int $limite = 10): array
    {
        $sql = 'SELECT t.termoTecnico, COUNT(*) AS total
                FROM Consulta c
                JOIN TermoJuridico t ON t.idTermo = c.idTermo
                GROUP BY c.idTermo
                ORDER BY total DESC
                LIMIT ?';
        $stmt = Database::getConnection()->prepare($sql);
        $stmt->bindValue(1, $limite, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
