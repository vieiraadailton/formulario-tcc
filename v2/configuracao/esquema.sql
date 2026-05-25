-- ─── configuracao/esquema.sql ────────────────────────────────────────────────

-- Arquivo para a criação do esquema do banco de dados utilizado no projeto de cadastro de bebidas.
─────────────────────────────────────────────────────────────────────────────

CREATE DATABASE IF NOT EXISTS cadastro_bebidas
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci; -- Conjunto de caracteres para aceitação dos caracteres especiais e emojis

USE cadastro_bebidas;

CREATE TABLE IF NOT EXISTS fornecedores (
    id INT AUTO_INCREMENT PRIMARY KEY, -- ID para consulta e diferenciação dos registros
    nome_fornecedorVARCHAR(150) NOT NULL, -- Nome do fornecedor, obrigatório
    endereco VARCHAR(255) NOT NULL, -- Endereço do fornecedor, obrigatório
    bairro VARCHAR(100) NOT NULL, -- Bairro do fornecedor, obrigatório
    estado CHAR(2) NOT NULL, -- Estado do fornecedor, obrigatório
    nome_bebida VARCHAR(150) NOT NULL, -- Nome da bebida fornecida, obrigatório
    distribuidora    VARCHAR(150) NOT NULL, -- Nome da distribuidora, obrigatório
    status_analise   TINYINT(1)   NOT NULL DEFAULT 0, --Status da análise, sendo 0 para reprovado e 1 para aprovado, obrigatório
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
