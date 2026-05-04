-- ─── configuracao/esquema.sql ────────────────────────────────────────────────
-- Script de criação do banco de dados e da tabela de fornecedores.
-- Execute este arquivo UMA única vez antes de usar a aplicação.
-- Comando: mysql -u root -p < configuracao/esquema.sql
-- ─────────────────────────────────────────────────────────────────────────────

CREATE DATABASE IF NOT EXISTS cadastro_bebidas
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE cadastro_bebidas;

CREATE TABLE IF NOT EXISTS fornecedores (
    id               INT          AUTO_INCREMENT PRIMARY KEY        COMMENT 'Chave primária gerada automaticamente',
    nome_fornecedor  VARCHAR(150) NOT NULL                          COMMENT 'Razão social ou nome fantasia',
    endereco         VARCHAR(255) NOT NULL                          COMMENT 'Logradouro, número e complemento',
    bairro           VARCHAR(100) NOT NULL                          COMMENT 'Bairro do fornecedor',
    estado           CHAR(2)      NOT NULL                          COMMENT 'Sigla da UF (ex.: SP)',
    nome_bebida      VARCHAR(150) NOT NULL                          COMMENT 'Nome comercial da bebida analisada',
    distribuidora    VARCHAR(150) NOT NULL                          COMMENT 'Empresa distribuidora responsável',
    status_analise   TINYINT(1)   NOT NULL DEFAULT 0               COMMENT '0 = adulterado | 1 = aprovado',
    criado_em        DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Data e hora do registro'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
