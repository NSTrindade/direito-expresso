-- =====================================================================
-- Direito Expresso — base legal nos termos que ainda não têm nenhuma lei
-- Rode com o banco do site selecionado (Aiven/defaultdb no DBeaver com Alt+X,
-- ou totem_juridico no phpMyAdmin). Pode rodar mais de uma vez.
--
-- 1) Cadastra o Código Civil (base da categoria Civil), se ainda não existir.
-- 2) Liga cada termo SEM lei à lei principal da sua área:
--    Consumidor -> CDC | Trabalho -> CLT | Moradia -> Lei do Inquilinato | Civil -> Código Civil
--    Termos de outras áreas continuam sem lei: ligue pelo painel administrativo.
-- =====================================================================

INSERT INTO Lei (idCategoria, numeroLei, tipoNorma, ementa, dataPublicacao)
SELECT c.idCategoria, 'Lei nº 10.406/2002', 'Lei Federal', 'Código Civil.', '2002-01-10'
FROM Categoria c
WHERE c.nome = 'Civil'
  AND NOT EXISTS (SELECT 1 FROM Lei WHERE numeroLei = 'Lei nº 10.406/2002');

INSERT IGNORE INTO TermoLei (idTermo, idLei)
SELECT t.idTermo, l.idLei
FROM TermoJuridico t
JOIN Lei l ON l.idCategoria = t.idCategoria
WHERE l.numeroLei IN ('Lei nº 8.078/1990', 'Decreto-Lei nº 5.452/1943', 'Lei nº 8.245/1991', 'Lei nº 10.406/2002')
  AND NOT EXISTS (SELECT 1 FROM TermoLei tl WHERE tl.idTermo = t.idTermo);

-- Conferência: termos e suas leis
SELECT t.termoTecnico, c.nome AS categoria, l.numeroLei
FROM TermoJuridico t
JOIN Categoria c ON c.idCategoria = t.idCategoria
LEFT JOIN TermoLei tl ON tl.idTermo = t.idTermo
LEFT JOIN Lei l ON l.idLei = tl.idLei
ORDER BY c.nome, t.termoTecnico;
