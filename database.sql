-- Script adaptado para cPanel (O banco deve ser criado pela interface do cPanel)
-- Selecione o banco de dados no phpMyAdmin antes de importar este arquivo.

-- Tabela de Usuários (RBAC)
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cpf_cnpj VARCHAR(20) NOT NULL UNIQUE,
    nome VARCHAR(150) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    telefone VARCHAR(20),
    senha VARCHAR(255) NOT NULL,
    perfil ENUM('admin', 'tecnico', 'cliente') NOT NULL DEFAULT 'cliente',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Tabela de Configurações da Empresa (Módulo 5)
CREATE TABLE companies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    razao_social VARCHAR(150) NOT NULL,
    cnpj VARCHAR(20) NOT NULL UNIQUE,
    logo_url VARCHAR(255),
    matriz_filial ENUM('matriz', 'filial', 'parceira') DEFAULT 'matriz',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Tabela de Fornecedores (Módulo 5)
CREATE TABLE suppliers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(150) NOT NULL,
    cnpj VARCHAR(20) UNIQUE,
    telefone VARCHAR(20),
    email VARCHAR(150),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Tabela de Ativos/Patrimônio (Módulo 5)
CREATE TABLE assets (
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
CREATE TABLE contracts (
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
CREATE TABLE invoices (
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
CREATE TABLE tickets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cliente_id INT NOT NULL,
    tecnico_id INT,
    tipo_servico ENUM('Criacao de Sistema', 'Manutencao em Sistema', 'Manutencao em Computador', 'Envio para Analise', 'Execucao') NOT NULL,
    descricao TEXT NOT NULL,
    atendimento ENUM('remoto', 'presencial'),
    status ENUM('aberto', 'andamento', 'finalizado') DEFAULT 'aberto',
    relatorio_final TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (cliente_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (tecnico_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Tabela de Mídias de Chamados (Fotos/Vídeos)
CREATE TABLE ticket_media (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ticket_id INT NOT NULL,
    user_id INT NOT NULL, -- Quem enviou (cliente ou tecnico)
    file_url VARCHAR(255) NOT NULL,
    tipo ENUM('imagem', 'video') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ticket_id) REFERENCES tickets(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;