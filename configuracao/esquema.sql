-- ─── configuracao/esquema.sql ────────────────────────────────────────────────
-- Execute UMA única vez para criar o banco e a tabela.
-- Comando: mysql -u root -p < configuracao/esquema.sql
-- ─────────────────────────────────────────────────────────────────────────────

CREATE DATABASE IF NOT EXISTS cadastro_bebidas
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE cadastro_bebidas;

CREATE TABLE IF NOT EXISTS fornecedores (
    id               INT          AUTO_INCREMENT PRIMARY KEY,
    nome_fornecedor  VARCHAR(150) NOT NULL,
    endereco         VARCHAR(255) NOT NULL,
    bairro           VARCHAR(100) NOT NULL,
    estado           CHAR(2)      NOT NULL                    COMMENT 'Sigla da UF',
    nome_bebida      VARCHAR(150) NOT NULL,
    distribuidora    VARCHAR(150) NOT NULL,
    status_analise   TINYINT(1)   NOT NULL DEFAULT 0          COMMENT '0=adulterado | 1=aprovado',
    criado_em        DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
