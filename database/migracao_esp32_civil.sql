-- =====================================================================
-- Direito Expresso — categoria "Civil" para o totem físico (ESP32)
-- Rode com o banco do site selecionado: Aiven (defaultdb) no DBeaver, ou totem_juridico no phpMyAdmin.
-- Pode rodar mais de uma vez: não duplica nada.
--
-- Os 4 botões do ESP32 são Consumidor, Civil, Moradia e Trabalho.
-- A categoria "Serviços Públicos" continua existindo no site.
-- =====================================================================


-- Categoria Civil
INSERT INTO Categoria (nome, descricao)
SELECT 'Civil', 'Contratos e dívidas entre pessoas, indenizações, acidentes, família e herança.' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM Categoria WHERE nome = 'Civil');

-- Termos de exemplo (como no seed.sql: cadastrem os definitivos pelo painel)
INSERT IGNORE INTO TermoJuridico (idCategoria, termoTecnico, explicacaoColoquial, proxPassoSugerido)
SELECT idCategoria, 'Danos morais',
  'É uma compensação em dinheiro quando alguém causa a você um sofrimento, constrangimento ou humilhação injusta, além de um eventual prejuízo material.',
  'Guarde provas (mensagens, fotos, documentos, testemunhas). Em causas de até 20 salários mínimos você pode procurar o Juizado Especial Cível sem advogado; se não puder pagar um advogado, procure a Defensoria Pública.'
FROM Categoria WHERE nome = 'Civil';

INSERT IGNORE INTO TermoJuridico (idCategoria, termoTecnico, explicacaoColoquial, proxPassoSugerido)
SELECT idCategoria, 'Pensão alimentícia',
  'É o valor que um dos pais (ou outro parente) deve pagar para ajudar no sustento de quem precisa, como um filho menor de idade.',
  'Procure a Defensoria Pública levando seus documentos, os da criança e comprovantes de gastos para pedir ou revisar a pensão.'
FROM Categoria WHERE nome = 'Civil';

-- Ligações termo <-> órgão
INSERT IGNORE INTO TermoOrgao (idTermo, idOrgao, observacao)
SELECT t.idTermo, o.idOrgao, 'Causas de até 20 salários mínimos podem ser abertas sem advogado.'
FROM TermoJuridico t JOIN OrgaoCompetente o ON o.nome = 'Juizado Especial Cível'
WHERE t.termoTecnico = 'Danos morais';

INSERT IGNORE INTO TermoOrgao (idTermo, idOrgao, observacao)
SELECT t.idTermo, o.idOrgao, 'Atendimento gratuito para quem não pode pagar advogado.'
FROM TermoJuridico t JOIN OrgaoCompetente o ON o.nome = 'Defensoria Pública do Estado de SP'
WHERE t.termoTecnico IN ('Danos morais', 'Pensão alimentícia');
