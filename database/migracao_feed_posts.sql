-- =====================================================================
-- MIGRAÇÃO: Feed de Postagens Rápidas (banco JÁ EXISTENTE / em produção)
--
-- Idempotente: pode ser executada mais de uma vez e NÃO apaga dados.
-- phpMyAdmin > selecione o banco > aba "Importar" (ou aba "SQL").
-- =====================================================================

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS feed_posts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    texto_conteudo TEXT NULL,
    tipo_midia ENUM('image', 'video', 'none') NOT NULL DEFAULT 'none',
    caminho_midia VARCHAR(255) NULL,
    data_criacao TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    status ENUM('publicado', 'rascunho') NOT NULL DEFAULT 'publicado',
    INDEX idx_feed_status_data (status, data_criacao, id)
) ENGINE=InnoDB;
