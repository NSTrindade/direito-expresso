-- =====================================================================
-- Direito Expresso — Script de criação do banco de dados
-- Baseado no MER (seção 9.2 do pré-projeto): 9 entidades, todas
-- mantidas com os mesmos nomes de tabela/campo do documento original.
-- Compatível com MySQL 5.7+ / MariaDB (XAMPP).
-- =====================================================================

CREATE DATABASE IF NOT EXISTS totem_juridico
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE totem_juridico;

-- ---------------------------------------------------------------------
-- Categoria: classifica Lei e TermoJuridico (1,1 obrigatório em ambas)
-- ---------------------------------------------------------------------
CREATE TABLE Categoria (
  idCategoria INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nome        VARCHAR(100) NOT NULL,
  descricao   TEXT NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Lei: normas oficiais associadas a uma categoria
-- ---------------------------------------------------------------------
CREATE TABLE Lei (
  idLei           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  idCategoria     INT UNSIGNED NOT NULL,
  numeroLei       VARCHAR(50)  NOT NULL,
  tipoNorma       VARCHAR(50)  NOT NULL COMMENT 'Ex: Lei Federal, Decreto, Constituição',
  ementa          TEXT         NOT NULL,
  dataPublicacao  DATE         NULL,
  CONSTRAINT fk_lei_categoria
    FOREIGN KEY (idCategoria) REFERENCES Categoria(idCategoria)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- TermoJuridico: o "dicionário de triagem" — termo técnico traduzido
-- ---------------------------------------------------------------------
CREATE TABLE TermoJuridico (
  idTermo               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  idCategoria           INT UNSIGNED NOT NULL,
  termoTecnico          VARCHAR(150) NOT NULL,
  explicacaoColoquial   TEXT         NOT NULL,
  proxPassoSugerido     TEXT         NULL,
  dataCadastro          DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_termo_categoria
    FOREIGN KEY (idCategoria) REFERENCES Categoria(idCategoria)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  UNIQUE KEY uq_termo_tecnico (termoTecnico)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- OrgaoCompetente: Procon, Juizado Especial, Defensoria Pública, etc.
-- ---------------------------------------------------------------------
CREATE TABLE OrgaoCompetente (
  idOrgao     INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nome        VARCHAR(150) NOT NULL,
  tipoOrgao   VARCHAR(80)  NULL,
  endereco    VARCHAR(255) NULL,
  telefone    VARCHAR(30)  NULL,
  site        VARCHAR(255) NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- TermoLei: tabela associativa (N:N) entre TermoJuridico e Lei
-- ---------------------------------------------------------------------
CREATE TABLE TermoLei (
  idTermo INT UNSIGNED NOT NULL,
  idLei   INT UNSIGNED NOT NULL,
  PRIMARY KEY (idTermo, idLei),
  CONSTRAINT fk_termolei_termo
    FOREIGN KEY (idTermo) REFERENCES TermoJuridico(idTermo) ON DELETE CASCADE,
  CONSTRAINT fk_termolei_lei
    FOREIGN KEY (idLei) REFERENCES Lei(idLei) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- TermoOrgao: tabela associativa (N:N) entre TermoJuridico e OrgaoCompetente
-- ---------------------------------------------------------------------
CREATE TABLE TermoOrgao (
  idTermo     INT UNSIGNED NOT NULL,
  idOrgao     INT UNSIGNED NOT NULL,
  observacao  TEXT NULL,
  PRIMARY KEY (idTermo, idOrgao),
  CONSTRAINT fk_termoorgao_termo
    FOREIGN KEY (idTermo) REFERENCES TermoJuridico(idTermo) ON DELETE CASCADE,
  CONSTRAINT fk_termoorgao_orgao
    FOREIGN KEY (idOrgao) REFERENCES OrgaoCompetente(idOrgao) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Totem: cada equipamento físico instalado
-- ---------------------------------------------------------------------
CREATE TABLE Totem (
  idTotem         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  localizacao     VARCHAR(150) NOT NULL,
  status          ENUM('ativo','manutencao','inativo') NOT NULL DEFAULT 'ativo',
  dataInstalacao  DATE NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Usuario: administradores do painel web (RF_05/RF_06). O cidadão que
-- usa o totem ou o site NÃO precisa de conta — por isso Consulta.idUsuario
-- é opcional (ver nota abaixo).
-- ---------------------------------------------------------------------
CREATE TABLE Usuario (
  idUsuario     INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nome          VARCHAR(150) NOT NULL,
  email         VARCHAR(150) NOT NULL UNIQUE,
  senha         VARCHAR(255) NOT NULL COMMENT 'Hash gerado com password_hash() do PHP, nunca texto puro',
  tipoUsuario   ENUM('admin') NOT NULL DEFAULT 'admin',
  dataCadastro  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Consulta: histórico de cada interação de triagem
--
-- NOTA DE PROJETO (divergência intencional do MER original, seção 9.1):
-- o DER descreve idUsuario como obrigatório (1,1) em Consulta, mas os
-- casos de uso (RF_01 a RF_03) deixam claro que o Cidadão usa o totem
-- e o site SEM autenticação — só o Administrador faz login (RF_05).
-- Se idUsuario fosse NOT NULL, toda consulta anônima feita por um
-- cidadão quebraria a integridade referencial. Por isso idUsuario e
-- idTotem são NULLable aqui: ficam preenchidos quando a origem é
-- rastreável (ex: um totem específico), e nulos numa consulta feita
-- pelo site sem equipamento físico associado.
-- ---------------------------------------------------------------------
CREATE TABLE Consulta (
  idConsulta            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  idUsuario             INT UNSIGNED NULL,
  idTotem               INT UNSIGNED NULL,
  idTermo               INT UNSIGNED NOT NULL,
  canalOrigem           ENUM('totem','web') NOT NULL,
  dataHora              DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  resultadoApresentado  TEXT NULL,
  CONSTRAINT fk_consulta_usuario
    FOREIGN KEY (idUsuario) REFERENCES Usuario(idUsuario) ON DELETE SET NULL,
  CONSTRAINT fk_consulta_totem
    FOREIGN KEY (idTotem) REFERENCES Totem(idTotem) ON DELETE SET NULL,
  CONSTRAINT fk_consulta_termo
    FOREIGN KEY (idTermo) REFERENCES TermoJuridico(idTermo) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Índices de apoio às consultas mais frequentes do sistema
-- ---------------------------------------------------------------------
CREATE INDEX idx_termo_categoria   ON TermoJuridico(idCategoria);
CREATE INDEX idx_lei_categoria     ON Lei(idCategoria);
CREATE INDEX idx_consulta_termo    ON Consulta(idTermo);
CREATE INDEX idx_consulta_data     ON Consulta(dataHora);
CREATE INDEX idx_consulta_canal    ON Consulta(canalOrigem);
