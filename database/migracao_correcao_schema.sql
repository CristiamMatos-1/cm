-- =====================================================================
-- MIGRAÇÃO DE CORREÇÃO DO SCHEMA (banco JÁ EXISTENTE / em produção)
--
-- Seguro para rodar mais de uma vez (idempotente): só cria/adiciona o que
-- ainda não existe e NÃO apaga nenhum dado.
--
-- Como executar (phpMyAdmin):
--   1. FAÇA BACKUP: aba "Exportar" > "Rápido" > SQL.
--   2. Selecione o banco do sistema na coluna da esquerda.
--   3. Aba "Importar" > escolha este arquivo > "Importar".
--      (ou aba "SQL": cole todo o conteúdo e clique em "Executar")
--
-- O que corrige:
--   * users: colunas permissoes, responsavel_nome, cep, logradouro,
--     numero, complemento, bairro, cidade, estado
--   * tickets: colunas v2 (programador/engenheiro, valores, pagamento,
--     closed_at) e valor 'Servico Avulso' em tipo_servico
--   * budgets: colunas v2 (valor_total, validade, token, rejeição),
--     renomeia valor -> valor_total se ainda for o schema antigo
--   * tabelas ausentes: budget_items, avulso_services,
--     projetos_software, financeiro_contabil (e demais, se faltarem)
--   * gera token de autorização para orçamentos antigos sem token
--   * LGPD: histórico de orçamentos (budget_history), solicitações de
--     titulares (lgpd_requests), log de auditoria (audit_log), colunas de
--     consentimento/anonimização em users e encarregado em companies
-- =====================================================================

SET NAMES utf8mb4;

-- ---------------------------------------------------------------------
-- 1. Tabelas (só cria as que não existem)
-- ---------------------------------------------------------------------

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
    consentimento_em DATETIME NULL, -- LGPD: data/hora do aceite da Política de Privacidade
    consentimento_versao VARCHAR(20) NULL,
    consentimento_ip VARCHAR(45) NULL,
    anonimizado_em DATETIME NULL, -- LGPD: preenchido quando o titular é anonimizado
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Tabela de Configurações da Empresa (Módulo 5)
CREATE TABLE IF NOT EXISTS companies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    razao_social VARCHAR(150) NOT NULL,
    cnpj VARCHAR(20) NOT NULL UNIQUE,
    logo_url VARCHAR(255),
    encarregado_nome VARCHAR(150) NULL, -- LGPD: encarregado pelo tratamento de dados (DPO)
    email_privacidade VARCHAR(150) NULL, -- LGPD: canal de contato do titular
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

-- Histórico (trilha de auditoria) das decisões e reaberturas de orçamentos.
-- Nunca é apagado ao reabrir um orçamento: mantém quem/quando/de onde.
CREATE TABLE IF NOT EXISTS budget_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    budget_id INT NOT NULL,
    acao VARCHAR(20) NOT NULL, -- criado | aprovado | rejeitado | reaberto | reativado | expirado | nova_versao
    status_anterior VARCHAR(20) NULL,
    status_novo VARCHAR(20) NULL,
    usuario_id INT NULL,
    origem VARCHAR(20) NOT NULL DEFAULT 'sistema', -- admin | tecnico | cliente | link_publico | sistema
    motivo TEXT NULL,
    ip_address VARCHAR(45) NULL,
    user_agent VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_budget_history_budget (budget_id),
    FOREIGN KEY (budget_id) REFERENCES budgets(id) ON DELETE CASCADE,
    FOREIGN KEY (usuario_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Solicitações de titulares de dados (LGPD, art. 18)
CREATE TABLE IF NOT EXISTS lgpd_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    tipo VARCHAR(30) NOT NULL, -- acesso | correcao | anonimizacao | portabilidade | revogacao_consentimento | outro
    mensagem TEXT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'aberta', -- aberta | atendida | negada
    resposta TEXT NULL,
    atendido_por INT NULL,
    atendido_em DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_lgpd_requests_status (status),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (atendido_por) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Registro de ações sensíveis (exportação, anonimização, exclusão de dados pessoais)
CREATE TABLE IF NOT EXISTS audit_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NULL,
    acao VARCHAR(60) NOT NULL,
    entidade VARCHAR(40) NOT NULL,
    entidade_id INT NULL,
    detalhes TEXT NULL,
    ip_address VARCHAR(45) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_audit_log_entidade (entidade, entidade_id),
    FOREIGN KEY (usuario_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 2. users
-- ---------------------------------------------------------------------
SET @s := (SELECT IF(COUNT(*) = 0, 'ALTER TABLE `users` ADD COLUMN `permissoes` TEXT NULL', 'DO 0') FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'permissoes');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;
SET @s := (SELECT IF(COUNT(*) = 0, 'ALTER TABLE `users` ADD COLUMN `responsavel_nome` VARCHAR(150) NULL', 'DO 0') FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'responsavel_nome');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;
SET @s := (SELECT IF(COUNT(*) = 0, 'ALTER TABLE `users` ADD COLUMN `cep` VARCHAR(10) NULL', 'DO 0') FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'cep');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;
SET @s := (SELECT IF(COUNT(*) = 0, 'ALTER TABLE `users` ADD COLUMN `logradouro` VARCHAR(255) NULL', 'DO 0') FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'logradouro');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;
SET @s := (SELECT IF(COUNT(*) = 0, 'ALTER TABLE `users` ADD COLUMN `numero` VARCHAR(20) NULL', 'DO 0') FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'numero');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;
SET @s := (SELECT IF(COUNT(*) = 0, 'ALTER TABLE `users` ADD COLUMN `complemento` VARCHAR(100) NULL', 'DO 0') FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'complemento');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;
SET @s := (SELECT IF(COUNT(*) = 0, 'ALTER TABLE `users` ADD COLUMN `bairro` VARCHAR(100) NULL', 'DO 0') FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'bairro');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;
SET @s := (SELECT IF(COUNT(*) = 0, 'ALTER TABLE `users` ADD COLUMN `cidade` VARCHAR(100) NULL', 'DO 0') FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'cidade');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;
SET @s := (SELECT IF(COUNT(*) = 0, 'ALTER TABLE `users` ADD COLUMN `estado` VARCHAR(2) NULL', 'DO 0') FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'estado');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

SET @s := (SELECT IF(COUNT(*) = 0, 'ALTER TABLE `users` ADD COLUMN `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP', 'DO 0') FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'updated_at');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

-- LGPD: consentimento e anonimização
SET @s := (SELECT IF(COUNT(*) = 0, 'ALTER TABLE `users` ADD COLUMN `consentimento_em` DATETIME NULL', 'DO 0') FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'consentimento_em');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;
SET @s := (SELECT IF(COUNT(*) = 0, 'ALTER TABLE `users` ADD COLUMN `consentimento_versao` VARCHAR(20) NULL', 'DO 0') FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'consentimento_versao');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;
SET @s := (SELECT IF(COUNT(*) = 0, 'ALTER TABLE `users` ADD COLUMN `consentimento_ip` VARCHAR(45) NULL', 'DO 0') FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'consentimento_ip');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;
SET @s := (SELECT IF(COUNT(*) = 0, 'ALTER TABLE `users` ADD COLUMN `anonimizado_em` DATETIME NULL', 'DO 0') FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'anonimizado_em');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

-- ---------------------------------------------------------------------
-- 3. companies
-- ---------------------------------------------------------------------
SET @s := (SELECT IF(COUNT(*) = 0, 'ALTER TABLE `companies` ADD COLUMN `logo_url` VARCHAR(255) NULL', 'DO 0') FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'companies' AND COLUMN_NAME = 'logo_url');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;
SET @s := (SELECT IF(COUNT(*) = 0, 'ALTER TABLE `companies` ADD COLUMN `encarregado_nome` VARCHAR(150) NULL', 'DO 0') FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'companies' AND COLUMN_NAME = 'encarregado_nome');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;
SET @s := (SELECT IF(COUNT(*) = 0, 'ALTER TABLE `companies` ADD COLUMN `email_privacidade` VARCHAR(150) NULL', 'DO 0') FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'companies' AND COLUMN_NAME = 'email_privacidade');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

-- ---------------------------------------------------------------------
-- 4. tickets
-- ---------------------------------------------------------------------
SET @s := (SELECT IF(COUNT(*) = 0, 'ALTER TABLE `tickets` ADD COLUMN `programador_id` INT NULL', 'DO 0') FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tickets' AND COLUMN_NAME = 'programador_id');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;
SET @s := (SELECT IF(COUNT(*) = 0, 'ALTER TABLE `tickets` ADD COLUMN `engenheiro_id` INT NULL', 'DO 0') FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tickets' AND COLUMN_NAME = 'engenheiro_id');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;
SET @s := (SELECT IF(COUNT(*) = 0, 'ALTER TABLE `tickets` ADD COLUMN `atendimento` ENUM(''remoto'', ''presencial'') NULL', 'DO 0') FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tickets' AND COLUMN_NAME = 'atendimento');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;
SET @s := (SELECT IF(COUNT(*) = 0, 'ALTER TABLE `tickets` ADD COLUMN `relatorio_final` TEXT NULL', 'DO 0') FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tickets' AND COLUMN_NAME = 'relatorio_final');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;
SET @s := (SELECT IF(COUNT(*) = 0, 'ALTER TABLE `tickets` ADD COLUMN `valor_pecas` DECIMAL(10,2) NULL', 'DO 0') FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tickets' AND COLUMN_NAME = 'valor_pecas');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;
SET @s := (SELECT IF(COUNT(*) = 0, 'ALTER TABLE `tickets` ADD COLUMN `valor_mao_obra` DECIMAL(10,2) NULL', 'DO 0') FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tickets' AND COLUMN_NAME = 'valor_mao_obra');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;
SET @s := (SELECT IF(COUNT(*) = 0, 'ALTER TABLE `tickets` ADD COLUMN `valor_servico` DECIMAL(10,2) NULL', 'DO 0') FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tickets' AND COLUMN_NAME = 'valor_servico');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;
SET @s := (SELECT IF(COUNT(*) = 0, 'ALTER TABLE `tickets` ADD COLUMN `forma_pagamento` VARCHAR(50) NULL', 'DO 0') FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tickets' AND COLUMN_NAME = 'forma_pagamento');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;
SET @s := (SELECT IF(COUNT(*) = 0, 'ALTER TABLE `tickets` ADD COLUMN `autorizado_por` VARCHAR(100) NULL', 'DO 0') FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tickets' AND COLUMN_NAME = 'autorizado_por');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;
SET @s := (SELECT IF(COUNT(*) = 0, 'ALTER TABLE `tickets` ADD COLUMN `data_autorizacao` DATETIME NULL', 'DO 0') FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tickets' AND COLUMN_NAME = 'data_autorizacao');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;
SET @s := (SELECT IF(COUNT(*) = 0, 'ALTER TABLE `tickets` ADD COLUMN `closed_at` DATETIME NULL', 'DO 0') FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tickets' AND COLUMN_NAME = 'closed_at');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;
SET @s := (SELECT IF((SELECT COUNT(*) FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tickets' AND COLUMN_NAME = 'programador_id' AND REFERENCED_TABLE_NAME = 'users') = 0, 'ALTER TABLE `tickets` ADD CONSTRAINT `fk_tickets_programador` FOREIGN KEY (`programador_id`) REFERENCES `users`(`id`) ON DELETE SET NULL', 'DO 0'));
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;
SET @s := (SELECT IF((SELECT COUNT(*) FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tickets' AND COLUMN_NAME = 'engenheiro_id' AND REFERENCED_TABLE_NAME = 'users') = 0, 'ALTER TABLE `tickets` ADD CONSTRAINT `fk_tickets_engenheiro` FOREIGN KEY (`engenheiro_id`) REFERENCES `users`(`id`) ON DELETE SET NULL', 'DO 0'));
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

SET @s := (SELECT IF(COUNT(*) = 0, 'ALTER TABLE `tickets` ADD COLUMN `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP', 'DO 0') FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tickets' AND COLUMN_NAME = 'updated_at');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

-- Amplia os ENUMs (apenas acrescenta valores; não perde dados)
ALTER TABLE `tickets` MODIFY `tipo_servico` ENUM('Criacao de Sistema', 'Manutencao em Sistema', 'Manutencao em Computador', 'Envio para Analise', 'Execucao', 'Servico Avulso') NOT NULL;
ALTER TABLE `tickets` MODIFY `status` ENUM('aberto', 'andamento', 'em_analise', 'em_execucao', 'esperando_peca', 'finalizado', 'rejeitado') DEFAULT 'aberto';

-- ---------------------------------------------------------------------
-- 5. budgets
-- ---------------------------------------------------------------------
-- Schema antigo: coluna 'valor' passa a se chamar 'valor_total'
SET @s := (SELECT IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'budgets' AND COLUMN_NAME = 'valor') > 0 AND (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'budgets' AND COLUMN_NAME = 'valor_total') = 0, 'ALTER TABLE `budgets` CHANGE `valor` `valor_total` DECIMAL(10, 2) NOT NULL DEFAULT 0', 'DO 0'));
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;
SET @s := (SELECT IF(COUNT(*) = 0, 'ALTER TABLE `budgets` ADD COLUMN `titulo` VARCHAR(150) NOT NULL DEFAULT ''''', 'DO 0') FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'budgets' AND COLUMN_NAME = 'titulo');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;
SET @s := (SELECT IF(COUNT(*) = 0, 'ALTER TABLE `budgets` ADD COLUMN `valor_total` DECIMAL(10, 2) NOT NULL DEFAULT 0', 'DO 0') FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'budgets' AND COLUMN_NAME = 'valor_total');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;
SET @s := (SELECT IF(COUNT(*) = 0, 'ALTER TABLE `budgets` ADD COLUMN `valor_pecas` DECIMAL(10, 2) NULL', 'DO 0') FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'budgets' AND COLUMN_NAME = 'valor_pecas');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;
SET @s := (SELECT IF(COUNT(*) = 0, 'ALTER TABLE `budgets` ADD COLUMN `valor_mao_obra` DECIMAL(10, 2) NULL', 'DO 0') FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'budgets' AND COLUMN_NAME = 'valor_mao_obra');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;
SET @s := (SELECT IF(COUNT(*) = 0, 'ALTER TABLE `budgets` ADD COLUMN `data_validade` DATE NULL', 'DO 0') FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'budgets' AND COLUMN_NAME = 'data_validade');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;
SET @s := (SELECT IF(COUNT(*) = 0, 'ALTER TABLE `budgets` ADD COLUMN `token_autorizacao` VARCHAR(255) NULL', 'DO 0') FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'budgets' AND COLUMN_NAME = 'token_autorizacao');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;
SET @s := (SELECT IF(COUNT(*) = 0, 'ALTER TABLE `budgets` ADD COLUMN `autorizado_por` INT NULL', 'DO 0') FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'budgets' AND COLUMN_NAME = 'autorizado_por');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;
SET @s := (SELECT IF(COUNT(*) = 0, 'ALTER TABLE `budgets` ADD COLUMN `data_autorizacao` DATETIME NULL', 'DO 0') FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'budgets' AND COLUMN_NAME = 'data_autorizacao');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;
SET @s := (SELECT IF(COUNT(*) = 0, 'ALTER TABLE `budgets` ADD COLUMN `rejeitado_por` INT NULL', 'DO 0') FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'budgets' AND COLUMN_NAME = 'rejeitado_por');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;
SET @s := (SELECT IF(COUNT(*) = 0, 'ALTER TABLE `budgets` ADD COLUMN `data_rejeicao` DATETIME NULL', 'DO 0') FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'budgets' AND COLUMN_NAME = 'data_rejeicao');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;
SET @s := (SELECT IF(COUNT(*) = 0, 'ALTER TABLE `budgets` ADD COLUMN `motivo_rejeicao` TEXT NULL', 'DO 0') FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'budgets' AND COLUMN_NAME = 'motivo_rejeicao');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;
SET @s := (SELECT IF(COUNT(*) = 0, 'ALTER TABLE `budgets` ADD COLUMN `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP', 'DO 0') FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'budgets' AND COLUMN_NAME = 'updated_at');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;
SET @s := (SELECT IF((SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'budgets' AND COLUMN_NAME = 'token_autorizacao' AND NON_UNIQUE = 0) = 0, 'ALTER TABLE `budgets` ADD UNIQUE KEY `uq_budgets_token` (`token_autorizacao`)', 'DO 0'));
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

ALTER TABLE `budgets` MODIFY `status` ENUM('pendente', 'aprovado', 'rejeitado', 'expirado') DEFAULT 'pendente';

-- Orçamentos antigos sem título ganham um título padrão
UPDATE `budgets` SET `titulo` = CONCAT('Orçamento #', id) WHERE `titulo` IS NULL OR `titulo` = '';

-- Orçamentos antigos sem token ganham um token (mesmo formato: 64 hex)
UPDATE `budgets` SET `token_autorizacao` = SHA2(CONCAT(id, UUID(), RAND()), 256) WHERE `token_autorizacao` IS NULL OR `token_autorizacao` = '';

-- Histórico: registra, uma única vez, as decisões já existentes (sem IP, que não era coletado)
INSERT INTO `budget_history` (`budget_id`, `acao`, `status_anterior`, `status_novo`, `usuario_id`, `origem`, `motivo`, `created_at`)
SELECT b.id, 'aprovado', 'pendente', 'aprovado', b.autorizado_por, 'sistema', 'Registro migrado do sistema anterior', COALESCE(b.data_autorizacao, b.created_at)
FROM `budgets` b WHERE b.status = 'aprovado' AND NOT EXISTS (SELECT 1 FROM `budget_history` h WHERE h.budget_id = b.id);
INSERT INTO `budget_history` (`budget_id`, `acao`, `status_anterior`, `status_novo`, `usuario_id`, `origem`, `motivo`, `created_at`)
SELECT b.id, 'rejeitado', 'pendente', 'rejeitado', b.rejeitado_por, 'sistema', COALESCE(b.motivo_rejeicao, 'Registro migrado do sistema anterior'), COALESCE(b.data_rejeicao, b.created_at)
FROM `budgets` b WHERE b.status = 'rejeitado' AND NOT EXISTS (SELECT 1 FROM `budget_history` h WHERE h.budget_id = b.id);

-- ---------------------------------------------------------------------
-- 6. OPCIONAL - recuperar orçamentos aprovados que o sistema antigo
--    marcou como 'expirado' por engano (bug corrigido nesta versão).
--    Rode primeiro o SELECT para conferir; se a lista estiver correta,
--    remova o '-- ' do UPDATE e execute.
-- ---------------------------------------------------------------------
-- SELECT id, titulo, status, data_autorizacao, data_rejeicao FROM budgets WHERE status = 'expirado' AND data_autorizacao IS NOT NULL AND data_rejeicao IS NULL;
-- UPDATE budgets SET status = 'aprovado' WHERE status = 'expirado' AND data_autorizacao IS NOT NULL AND data_rejeicao IS NULL;

