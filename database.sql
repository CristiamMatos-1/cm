-- =====================================================================
-- INSTALAÇÃO NOVA (banco vazio) - Script adaptado para cPanel
--
-- 1. Crie o banco e o usuário no cPanel (MySQL Databases).
-- 2. No phpMyAdmin, selecione o banco e importe este arquivo.
--
-- ATENÇÃO: este arquivo é para banco NOVO. Se o sistema já está em
-- produção, NÃO reimporte: use database/migracao_correcao_schema.sql,
-- que atualiza o banco existente sem apagar dados.
-- =====================================================================

SET NAMES utf8mb4;

-- Tabela de Usuários (RBAC)
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cpf_cnpj VARCHAR(20) NOT NULL UNIQUE,
    nome VARCHAR(150) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    telefone VARCHAR(20),
    senha VARCHAR(255) NOT NULL,
    perfil ENUM('admin', 'tecnico', 'cliente') NOT NULL DEFAULT 'cliente',
    permissoes TEXT NULL, -- JSON com as permissões granulares de funcionários
    responsavel_nome VARCHAR(150) NULL,
    cep VARCHAR(10) NULL,
    logradouro VARCHAR(255) NULL,
    numero VARCHAR(20) NULL,
    complemento VARCHAR(100) NULL,
    bairro VARCHAR(100) NULL,
    cidade VARCHAR(100) NULL,
    estado VARCHAR(2) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_users_perfil_created (perfil, created_at)
) ENGINE=InnoDB;

-- Tabela de Configurações da Empresa (Módulo 5)
CREATE TABLE IF NOT EXISTS companies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    razao_social VARCHAR(150) NOT NULL,
    cnpj VARCHAR(20) NOT NULL UNIQUE,
    logo_url VARCHAR(255),
    matriz_filial ENUM('matriz', 'filial', 'parceira') DEFAULT 'matriz',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Tabela de Fornecedores (Módulo 5)
CREATE TABLE IF NOT EXISTS suppliers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(150) NOT NULL,
    cnpj VARCHAR(20) UNIQUE,
    telefone VARCHAR(20),
    email VARCHAR(150),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Tabela de Ativos/Patrimônio (Módulo 5)
CREATE TABLE IF NOT EXISTS assets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cliente_id INT, -- Se nulo, pertence à própria empresa prestadora
    fornecedor_id INT,
    nome_equipamento VARCHAR(150) NOT NULL,
    numero_serie VARCHAR(100) UNIQUE,
    data_compra DATE,
    data_venda DATE,
    garantia_meses INT DEFAULT 12,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (cliente_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (fornecedor_id) REFERENCES suppliers(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Tabela de Contratos (Módulo 4)
CREATE TABLE IF NOT EXISTS contracts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cliente_id INT NOT NULL,
    valor_mensal DECIMAL(10, 2) NOT NULL,
    data_inicio DATE NOT NULL,
    data_validade DATE NOT NULL,
    prazo_renovacao_anos INT NOT NULL DEFAULT 1,
    conteudo_sla TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (cliente_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Tabela de Notas Fiscais (Módulo 4)
CREATE TABLE IF NOT EXISTS invoices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cliente_id INT NOT NULL,
    contrato_id INT,
    numero_nf VARCHAR(50) NOT NULL,
    valor DECIMAL(10, 2) NOT NULL,
    data_emissao DATE NOT NULL,
    arquivo_url VARCHAR(255) NOT NULL, -- Caminho do PDF/XML
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (cliente_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (contrato_id) REFERENCES contracts(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Tabela de Chamados (Módulo 3)
CREATE TABLE IF NOT EXISTS tickets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cliente_id INT NOT NULL,
    tecnico_id INT,
    programador_id INT,
    engenheiro_id INT,
    tipo_servico ENUM('Criacao de Sistema', 'Manutencao em Sistema', 'Manutencao em Computador', 'Envio para Analise', 'Execucao', 'Servico Avulso') NOT NULL,
    descricao TEXT NOT NULL,
    atendimento ENUM('remoto', 'presencial'),
    status ENUM('aberto', 'andamento', 'em_analise', 'em_execucao', 'esperando_peca', 'finalizado', 'rejeitado') DEFAULT 'aberto',
    relatorio_final TEXT,
    valor_pecas DECIMAL(10,2) DEFAULT NULL,
    valor_mao_obra DECIMAL(10,2) DEFAULT NULL,
    valor_servico DECIMAL(10,2) DEFAULT NULL,
    forma_pagamento VARCHAR(50) DEFAULT NULL,
    autorizado_por VARCHAR(100) DEFAULT NULL,
    data_autorizacao DATETIME DEFAULT NULL,
    closed_at DATETIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (cliente_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (tecnico_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (programador_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (engenheiro_id) REFERENCES users(id) ON DELETE SET NULL,
    KEY idx_tickets_status_updated (status, updated_at),
    KEY idx_tickets_created_at (created_at)
) ENGINE=InnoDB;

-- Tabela de Mídias de Chamados (Fotos/Vídeos)
CREATE TABLE IF NOT EXISTS ticket_media (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ticket_id INT NOT NULL,
    user_id INT NOT NULL, -- Quem enviou (cliente ou tecnico)
    file_url VARCHAR(255) NOT NULL,
    tipo ENUM('imagem', 'video') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ticket_id) REFERENCES tickets(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Tabela de Orçamentos (Módulo 2)
CREATE TABLE IF NOT EXISTS budgets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cliente_id INT NOT NULL,
    ticket_id INT,
    titulo VARCHAR(150) NOT NULL,
    descricao TEXT NOT NULL,
    valor_total DECIMAL(10, 2) NOT NULL DEFAULT 0,
    valor_pecas DECIMAL(10, 2) DEFAULT NULL,
    valor_mao_obra DECIMAL(10, 2) DEFAULT NULL,
    data_validade DATE,
    status ENUM('pendente', 'aprovado', 'rejeitado', 'expirado') DEFAULT 'pendente',
    token_autorizacao VARCHAR(255) UNIQUE,
    autorizado_por INT,
    data_autorizacao DATETIME,
    rejeitado_por INT,
    data_rejeicao DATETIME,
    motivo_rejeicao TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (cliente_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (ticket_id) REFERENCES tickets(id) ON DELETE SET NULL,
    FOREIGN KEY (autorizado_por) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (rejeitado_por) REFERENCES users(id) ON DELETE SET NULL,
    KEY idx_budgets_status (status)
) ENGINE=InnoDB;

-- Tabela de Itens de Orçamento (Peças e Mão de Obra)
CREATE TABLE IF NOT EXISTS budget_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    budget_id INT NOT NULL,
    tipo ENUM('peca', 'mao_obra', 'servico') NOT NULL,
    descricao VARCHAR(255) NOT NULL,
    quantidade INT DEFAULT 1,
    valor_unitario DECIMAL(10, 2) NOT NULL,
    subtotal DECIMAL(10, 2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (budget_id) REFERENCES budgets(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Tabela de Serviços Avulsos (Módulo 3)
CREATE TABLE IF NOT EXISTS avulso_services (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cliente_id INT NOT NULL,
    descricao TEXT NOT NULL,
    valor DECIMAL(10, 2) NOT NULL,
    data_servico DATE NOT NULL,
    status ENUM('pendente', 'concluido', 'cancelado') DEFAULT 'pendente',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (cliente_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Tabela de Projetos de Software (Módulo 7)
CREATE TABLE IF NOT EXISTS projetos_software (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cliente_id INT NOT NULL,
    engenheiro_id INT NULL,
    nome_projeto VARCHAR(150) NOT NULL,
    descricao TEXT,
    documentacao TEXT,
    link_repositorio VARCHAR(255) DEFAULT NULL,
    link_producao VARCHAR(255) DEFAULT NULL,
    status ENUM('planejamento', 'desenvolvimento', 'testes', 'homologacao', 'producao', 'concluido') NOT NULL DEFAULT 'planejamento',
    data_inicio DATE DEFAULT NULL,
    data_previsao_fim DATE DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (cliente_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (engenheiro_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Tabela de Lançamentos Contábeis (Módulo 8)
CREATE TABLE IF NOT EXISTS financeiro_contabil (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tipo ENUM('receita', 'despesa') NOT NULL DEFAULT 'receita',
    descricao VARCHAR(255) NOT NULL,
    valor DECIMAL(10, 2) NOT NULL,
    data_vencimento DATE NOT NULL,
    data_pagamento DATE DEFAULT NULL,
    status ENUM('pendente', 'pago') NOT NULL DEFAULT 'pendente',
    cliente_fornecedor_id INT NULL,
    ticket_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (cliente_fornecedor_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (ticket_id) REFERENCES tickets(id) ON DELETE SET NULL,
    KEY idx_fin_status_tipo_venc (status, tipo, data_vencimento),
    KEY idx_fin_status_pagto (status, data_pagamento)
) ENGINE=InnoDB;

-- Tabela de Logs Administrativos (auditoria das ações críticas - Visão Geral)
CREATE TABLE IF NOT EXISTS admin_logs (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    user_name VARCHAR(150) NOT NULL DEFAULT 'Sistema', -- cópia do nome: o log sobrevive à exclusão do usuário
    acao VARCHAR(60) NOT NULL,
    entidade VARCHAR(60) NULL,
    entidade_id INT NULL,
    descricao VARCHAR(255) NOT NULL,
    nivel ENUM('info', 'aviso', 'critico') NOT NULL DEFAULT 'info',
    ip VARCHAR(45) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;
