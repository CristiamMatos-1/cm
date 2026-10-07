-- =====================================================================
-- MÓDULO DE ORDENS DE SERVIÇO (OS), CONSULTORIA E PROJETOS DE INFRAESTRUTURA
--
-- Seguro para rodar mais de uma vez (idempotente): só cria o que ainda não
-- existe e NÃO altera nem apaga dados das tabelas atuais.
--
-- Como executar (phpMyAdmin):
--   1. FAÇA BACKUP: aba "Exportar" > "Rápido" > SQL.
--   2. Selecione o banco do sistema > aba "Importar" > este arquivo.
--
-- Requisitos: MySQL 5.7+ / MariaDB 10.2+ (colunas geradas STORED).
-- As mesmas tabelas já constam em database.sql (instalação nova).
--
-- Modelo (normalizado):
--
--   users 1──N service_orders 1──0..1 service_order_maintenance   (tipo manutenção)
--                             1──0..1 service_order_consulting     (tipo projeto/consultoria/redes)
--                             1──N    service_order_access_tokens  (link seguro do cliente)
--                             1──N    service_order_logs           (auditoria append-only)
--                             1──N    service_order_notifications  (avisos à equipe)
--
-- Convenção do projeto: a PK de cada tabela se chama "id" (id_os da especificação
-- = service_orders.id). "codigo" (OS-AAAA-000123) é o identificador legível ao cliente.
-- =====================================================================

SET NAMES utf8mb4;

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
