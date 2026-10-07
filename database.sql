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
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
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
    FOREIGN KEY (engenheiro_id) REFERENCES users(id) ON DELETE SET NULL
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
    FOREIGN KEY (rejeitado_por) REFERENCES users(id) ON DELETE SET NULL
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

-- =====================================================================
-- MÓDULO DE ORDENS DE SERVIÇO / CONSULTORIA / PROJETOS DE INFRAESTRUTURA
-- (mesmo conteúdo de database/migracao_modulo_os.sql)
-- =====================================================================

-- ---------------------------------------------------------------------
-- Ordem de Serviço / Projeto (entidade principal, comum a todos os tipos)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS service_orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    codigo VARCHAR(20) NULL,                         -- OS-2026-000123, preenchido logo após o INSERT
    cliente_id INT NOT NULL,
    tecnico_id INT NULL,
    engenheiro_id INT NULL,
    ticket_id INT NULL,                              -- chamado que originou a OS (opcional)
    tipo_servico ENUM('manutencao_hardware', 'infraestrutura_redes', 'projeto', 'consultoria') NOT NULL,
    status ENUM(
        'rascunho',
        'aguardando_diagnostico',
        'aguardando_aprovacao_cliente',
        'aprovado',
        'rejeitado',
        'em_execucao',
        'concluido'
    ) NOT NULL DEFAULT 'rascunho',
    titulo VARCHAR(150) NOT NULL,
    data_abertura DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    data_atualizacao DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    data_conclusao DATETIME NULL,
    decidido_em DATETIME NULL,                       -- última decisão do cliente (aprovou/rejeitou)
    motivo_rejeicao TEXT NULL,
    UNIQUE KEY uq_service_orders_codigo (codigo),
    KEY idx_os_status_abertura (status, data_abertura),
    KEY idx_os_cliente_status (cliente_id, status),
    KEY idx_os_tipo (tipo_servico),
    KEY idx_os_tecnico (tecnico_id),
    KEY idx_os_engenheiro (engenheiro_id),
    KEY idx_os_ticket (ticket_id),
    -- Cliente com OS não pode ser apagado por engano (documento fiscal/técnico).
    CONSTRAINT fk_os_cliente FOREIGN KEY (cliente_id) REFERENCES users(id) ON DELETE RESTRICT,
    CONSTRAINT fk_os_tecnico FOREIGN KEY (tecnico_id) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT fk_os_engenheiro FOREIGN KEY (engenheiro_id) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT fk_os_ticket FOREIGN KEY (ticket_id) REFERENCES tickets(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Módulo de Manutenção (tipo_servico = manutencao_hardware) - relação 1:1
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS service_order_maintenance (
    service_order_id INT NOT NULL PRIMARY KEY,
    equipamento_tipo ENUM('notebook', 'desktop', 'servidor', 'outro') NOT NULL,
    equipamento_descricao VARCHAR(150) NULL,         -- marca / modelo
    numero_serie VARCHAR(100) NULL,
    relato_defeito_cliente TEXT NOT NULL,
    diagnostico_tecnico TEXT NULL,
    valor_pecas DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
    valor_mao_de_obra DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
    valor_software DECIMAL(12, 2) NOT NULL DEFAULT 0.00,   -- licenças, formatação, etc.
    -- Soma automática: o banco garante que o total nunca diverge das parcelas.
    valor_total DECIMAL(12, 2) AS (valor_pecas + valor_mao_de_obra + valor_software) STORED,
    CONSTRAINT chk_maint_valores CHECK (valor_pecas >= 0 AND valor_mao_de_obra >= 0 AND valor_software >= 0),
    CONSTRAINT fk_maint_os FOREIGN KEY (service_order_id) REFERENCES service_orders(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Módulo de Consultoria e Engenharia (projeto, consultoria, infraestrutura/redes) - 1:1
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS service_order_consulting (
    service_order_id INT NOT NULL PRIMARY KEY,
    problema_apresentado TEXT NOT NULL,              -- visão do cliente / escopo inicial
    problema_diagnosticado TEXT NULL,
    solucao_proposta TEXT NULL,                      -- arquitetura e resolução
    relatorio_tecnico MEDIUMTEXT NULL,               -- topologia, VLAN/VPN, stack utilizado
    relatorio_engenheiro MEDIUMTEXT NULL,            -- parecer final, viabilidade, conformidade
    valor_consultoria DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
    valor_total DECIMAL(12, 2) AS (valor_consultoria) STORED,
    CONSTRAINT chk_consult_valor CHECK (valor_consultoria >= 0),
    CONSTRAINT fk_consult_os FOREIGN KEY (service_order_id) REFERENCES service_orders(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Links seguros do portal do cliente. Só o hash SHA-256 do token é gravado:
-- um vazamento do banco não expõe links válidos.
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS service_order_access_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    service_order_id INT NOT NULL,
    token_hash CHAR(64) NOT NULL,
    expira_em DATETIME NOT NULL,
    revogado_em DATETIME NULL,
    ultimo_acesso_em DATETIME NULL,
    criado_por INT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_os_token_hash (token_hash),
    KEY idx_os_token_os (service_order_id, revogado_em),
    CONSTRAINT fk_os_token_os FOREIGN KEY (service_order_id) REFERENCES service_orders(id) ON DELETE CASCADE,
    CONSTRAINT fk_os_token_user FOREIGN KEY (criado_por) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Auditoria (append-only): toda mudança de status e toda decisão do cliente,
-- com data/hora, ator, IP e user-agent. A aplicação só faz INSERT nesta tabela.
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS service_order_logs (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    service_order_id INT NOT NULL,
    ator_tipo ENUM('equipe', 'cliente', 'sistema') NOT NULL,
    usuario_id INT NULL,
    ator_nome VARCHAR(150) NOT NULL,                 -- cópia: o log sobrevive à exclusão do usuário
    acao VARCHAR(50) NOT NULL,                       -- os_criada, status_alterado, cliente_aprovou, ...
    status_anterior VARCHAR(40) NULL,
    status_novo VARCHAR(40) NULL,
    descricao VARCHAR(500) NULL,
    ip VARCHAR(45) NULL,
    user_agent VARCHAR(255) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_os_logs_os (service_order_id, id),
    KEY idx_os_logs_created (created_at),
    CONSTRAINT fk_os_logs_os FOREIGN KEY (service_order_id) REFERENCES service_orders(id) ON DELETE CASCADE,
    CONSTRAINT fk_os_logs_user FOREIGN KEY (usuario_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Notificações internas para a equipe (ex.: "cliente aprovou a OS-2026-000123")
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS service_order_notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    service_order_id INT NOT NULL,
    destinatario_id INT NOT NULL,
    tipo VARCHAR(40) NOT NULL,
    mensagem VARCHAR(255) NOT NULL,
    lida_em DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_os_notif_dest (destinatario_id, lida_em, id),
    KEY idx_os_notif_os (service_order_id),
    CONSTRAINT fk_os_notif_os FOREIGN KEY (service_order_id) REFERENCES service_orders(id) ON DELETE CASCADE,
    CONSTRAINT fk_os_notif_user FOREIGN KEY (destinatario_id) REFERENCES users(id) ON DELETE CASCADE
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
    FOREIGN KEY (ticket_id) REFERENCES tickets(id) ON DELETE SET NULL
) ENGINE=InnoDB;
