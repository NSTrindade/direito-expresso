-- =====================================================================
-- Direito Expresso — Dados de exemplo (seed)
-- Execute DEPOIS do schema.sql. Serve para já ter algo funcionando
-- na triagem (totem e site) antes de cadastrar o conteúdo real pelo
-- painel administrativo.
-- =====================================================================

USE totem_juridico;

-- ---------------------------------------------------------------------
-- Categorias de problema (triagem do cidadão — não confundir com os
-- filtros "Constituições/Códigos/Leis/Decretos" do buscador de leis;
-- ver observação sobre isso na resposta em chat)
-- ---------------------------------------------------------------------
INSERT INTO Categoria (nome, descricao) VALUES
  ('Consumidor',       'Problemas com compras, produtos com defeito, cobranças indevidas e serviços mal prestados.'),
  ('Trabalho',         'Dúvidas sobre direitos trabalhistas, demissão, carteira assinada e pagamento.'),
  ('Moradia',          'Questões de aluguel, despejo, condomínio e vizinhança.'),
  ('Serviços Públicos','Como acessar e reclamar de serviços públicos essenciais (água, luz, saúde, transporte).');

-- ---------------------------------------------------------------------
-- Órgãos competentes
-- ---------------------------------------------------------------------
INSERT INTO OrgaoCompetente (nome, tipoOrgao, endereco, telefone, site) VALUES
  ('Procon-SP',                          'Órgão de defesa do consumidor', 'Rua Barra Funda, 930 - São Paulo/SP', '151',            'https://www.procon.sp.gov.br'),
  ('Justiça do Trabalho (Vara do Trabalho)', 'Órgão judiciário',          'Consulte a vara mais próxima pelo CEP', '(11) 3150-2000', 'https://www.trtsp.jus.br'),
  ('Defensoria Pública do Estado de SP', 'Assistência jurídica gratuita', 'Consulte a unidade mais próxima',        '129',            'https://www.defensoria.sp.def.br'),
  ('Juizado Especial Cível',             'Órgão judiciário',              'Consulte o fórum mais próximo',          '(11) 3150-2000', 'https://www.tjsp.jus.br');

-- ---------------------------------------------------------------------
-- Leis de referência
-- ---------------------------------------------------------------------
INSERT INTO Lei (idCategoria, numeroLei, tipoNorma, ementa, dataPublicacao) VALUES
  (1, 'Lei nº 8.078/1990',        'Lei Federal', 'Código de Defesa do Consumidor.', '1990-09-11'),
  (2, 'Decreto-Lei nº 5.452/1943','Decreto-Lei', 'Consolidação das Leis do Trabalho (CLT).', '1943-05-01'),
  (3, 'Lei nº 8.245/1991',        'Lei Federal', 'Lei do Inquilinato — locação de imóveis urbanos.', '1991-10-18');

-- ---------------------------------------------------------------------
-- Dicionário de termos (o coração do sistema)
-- ---------------------------------------------------------------------
INSERT INTO TermoJuridico (idCategoria, termoTecnico, explicacaoColoquial, proxPassoSugerido) VALUES
  (1, 'Vício oculto',
      'É um defeito no produto ou serviço que você não conseguiria perceber olhando ou usando normalmente, e que só aparece depois de um tempo.',
      'Notifique a loja ou fabricante por escrito assim que perceber o defeito. Se não resolverem, você pode abrir uma reclamação no Procon.'),
  (2, 'Justa causa',
      'É quando a empresa demite o funcionário por causa de uma falta grave dele (como roubo ou agressão), e por isso ele perde direitos como o FGTS e o seguro-desemprego.',
      'Se você acha que foi demitido por justa causa sem motivo real, procure o Sindicato da sua categoria ou a Justiça do Trabalho.'),
  (3, 'Despejo',
      'É o processo pelo qual o proprietário pode retomar um imóvel alugado, geralmente por falta de pagamento ou fim do contrato.',
      'Você tem direito a ser avisado e a um prazo para sair ou se defender. Procure a Defensoria Pública se não puder pagar um advogado.'),
  (4, 'Liminar',
      'É uma decisão rápida e provisória de um juiz, dada antes do fim do processo, para evitar um prejuízo urgente enquanto o caso ainda está sendo analisado.',
      'Uma liminar normalmente é pedida por um advogado ou defensor público dentro de um processo já aberto.');

-- ---------------------------------------------------------------------
-- Ligações termo <-> lei
-- ---------------------------------------------------------------------
INSERT INTO TermoLei (idTermo, idLei) VALUES
  (1, 1), -- Vício oculto -> CDC
  (2, 2), -- Justa causa -> CLT
  (3, 3); -- Despejo -> Lei do Inquilinato

-- ---------------------------------------------------------------------
-- Ligações termo <-> órgão
-- ---------------------------------------------------------------------
INSERT INTO TermoOrgao (idTermo, idOrgao, observacao) VALUES
  (1, 1, 'Primeira instância: reclamação administrativa no Procon.'),
  (2, 2, 'Caso a negociação direta falhe, procurar a Vara do Trabalho.'),
  (3, 3, 'Buscar a Defensoria Pública caso não tenha condições de pagar advogado.'),
  (4, 4, 'Liminares em causas de menor complexidade tramitam no Juizado Especial Cível.');

-- ---------------------------------------------------------------------
-- Totem de exemplo
-- ---------------------------------------------------------------------
INSERT INTO Totem (localizacao, status, dataInstalacao) VALUES
  ('ETEC Zona Leste - Hall de entrada', 'ativo', CURDATE());

-- ---------------------------------------------------------------------
-- Usuário administrador inicial
-- IMPORTANTE: a senha abaixo é só um placeholder e NÃO é um hash válido.
-- Gere o hash real rodando este comando PHP (ex: em um arquivo à parte
-- ou no terminal com `php -a`):
--   echo password_hash('sua_senha_aqui', PASSWORD_DEFAULT);
-- e substitua o valor de "senha" abaixo pelo resultado antes de logar.
-- ---------------------------------------------------------------------
INSERT INTO Usuario (nome, email, senha, tipoUsuario) VALUES
  ('Administrador', 'admin@direitoexpresso.local', 'SUBSTITUA_POR_UM_HASH_REAL', 'admin');
