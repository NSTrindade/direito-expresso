-- =====================================================================
-- Direito Expresso — remove categorias, órgãos e leis DUPLICADOS
-- (aparecem quando o seed.sql é rodado mais de uma vez no mesmo banco)
--
-- Para cada nome repetido fica o cadastro mais antigo (menor id). Termos,
-- leis e ligações que apontavam para a cópia passam a apontar para o
-- original; depois a cópia é apagada. Nenhum termo é perdido.
--
-- Rode no DBeaver (Aiven/defaultdb) com Alt+X. Pode rodar mais de uma vez.
-- NÃO rode o seed.sql de novo neste banco.
-- =====================================================================

-- ---------------- Categorias (mesmo nome) ----------------
DROP TEMPORARY TABLE IF EXISTS map_categoria;
CREATE TEMPORARY TABLE map_categoria (dup INT UNSIGNED PRIMARY KEY, manter INT UNSIGNED NOT NULL);
INSERT INTO map_categoria (dup, manter)
SELECT c.idCategoria, k.manter
FROM Categoria c
JOIN (SELECT nome, MIN(idCategoria) AS manter FROM Categoria GROUP BY nome) k ON k.nome = c.nome
WHERE c.idCategoria <> k.manter;

UPDATE TermoJuridico t JOIN map_categoria m ON t.idCategoria = m.dup SET t.idCategoria = m.manter;
UPDATE Lei l JOIN map_categoria m ON l.idCategoria = m.dup SET l.idCategoria = m.manter;
DELETE c FROM Categoria c JOIN map_categoria m ON c.idCategoria = m.dup;

-- ---------------- Órgãos (mesmo nome) ----------------
DROP TEMPORARY TABLE IF EXISTS map_orgao;
CREATE TEMPORARY TABLE map_orgao (dup INT UNSIGNED PRIMARY KEY, manter INT UNSIGNED NOT NULL);
INSERT INTO map_orgao (dup, manter)
SELECT o.idOrgao, k.manter
FROM OrgaoCompetente o
JOIN (SELECT nome, MIN(idOrgao) AS manter FROM OrgaoCompetente GROUP BY nome) k ON k.nome = o.nome
WHERE o.idOrgao <> k.manter;

INSERT IGNORE INTO TermoOrgao (idTermo, idOrgao, observacao)
SELECT x.idTermo, m.manter, x.observacao
FROM TermoOrgao x JOIN map_orgao m ON x.idOrgao = m.dup;
DELETE o FROM OrgaoCompetente o JOIN map_orgao m ON o.idOrgao = m.dup;   -- ligações antigas saem junto (CASCADE)

-- ---------------- Leis (mesmo número) ----------------
DROP TEMPORARY TABLE IF EXISTS map_lei;
CREATE TEMPORARY TABLE map_lei (dup INT UNSIGNED PRIMARY KEY, manter INT UNSIGNED NOT NULL);
INSERT INTO map_lei (dup, manter)
SELECT l.idLei, k.manter
FROM Lei l
JOIN (SELECT numeroLei, MIN(idLei) AS manter FROM Lei GROUP BY numeroLei) k ON k.numeroLei = l.numeroLei
WHERE l.idLei <> k.manter;

INSERT IGNORE INTO TermoLei (idTermo, idLei)
SELECT x.idTermo, m.manter
FROM TermoLei x JOIN map_lei m ON x.idLei = m.dup;
DELETE l FROM Lei l JOIN map_lei m ON l.idLei = m.dup;   -- ligações antigas saem junto (CASCADE)

-- ---------------- Conferência: tudo deve voltar com 0 linhas ----------------
SELECT 'Categoria' AS tabela, nome AS repetido, COUNT(*) AS vezes FROM Categoria GROUP BY nome HAVING COUNT(*) > 1
UNION ALL
SELECT 'OrgaoCompetente', nome, COUNT(*) FROM OrgaoCompetente GROUP BY nome HAVING COUNT(*) > 1
UNION ALL
SELECT 'Lei', numeroLei, COUNT(*) FROM Lei GROUP BY numeroLei HAVING COUNT(*) > 1;
