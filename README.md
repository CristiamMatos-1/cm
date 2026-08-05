# cm
Helpdesk

## Configuração do banco

Defina as variáveis de ambiente antes de iniciar a aplicação:

- `DB_HOST` (padrão: `localhost`)
- `DB_PORT` (padrão: `3306`)
- `DB_NAME` (obrigatória)
- `DB_USER` (obrigatória)
- `DB_PASSWORD` (opcional)

## Deploy com GitHub Actions (cPanel FTP/SFTP)

Workflow: `.github/workflows/deploy-cpanel-sftp.yml`

Configure os secrets do repositório:

- `SFTP_HOST`
- `SFTP_USERNAME`
- `SFTP_PASSWORD`
- `DEPLOY_PROTOCOL` (opcional: `sftp` ou `ftp`)
- `DEPLOY_PORT` (opcional: se vazio usa 22 para SFTP e 21 para FTP)

Compatibilidade com configuração anterior:

- `SFTP_PORT` continua suportado (legado)

Diretório remoto configurado: `/home2/coninfom/public_html/cm`

Observações para evitar erro 404:

- O `.htaccess` da raiz precisa estar publicado.
- O Apache do cPanel deve estar com `mod_rewrite` habilitado.

## Atualização de banco (ambientes antigos)

Se o ambiente já existia antes dessas colunas de chamados, execute os `ALTER TABLE` abaixo no MySQL:

```sql
ALTER TABLE tickets ADD COLUMN programador_id INT NULL AFTER tecnico_id;
ALTER TABLE tickets ADD COLUMN engenheiro_id INT NULL AFTER programador_id;
ALTER TABLE tickets ADD COLUMN valor_pecas DECIMAL(10,2) NULL AFTER relatorio_final;
ALTER TABLE tickets ADD COLUMN valor_mao_obra DECIMAL(10,2) NULL AFTER valor_pecas;
ALTER TABLE tickets ADD COLUMN valor_servico DECIMAL(10,2) NULL AFTER valor_mao_obra;
ALTER TABLE tickets ADD COLUMN forma_pagamento VARCHAR(50) NULL AFTER valor_servico;
ALTER TABLE tickets ADD COLUMN autorizado_por VARCHAR(100) NULL AFTER forma_pagamento;
ALTER TABLE tickets ADD COLUMN data_autorizacao DATETIME NULL AFTER autorizado_por;
ALTER TABLE tickets ADD COLUMN closed_at DATETIME NULL AFTER data_autorizacao;
ALTER TABLE tickets ADD CONSTRAINT fk_tickets_programador FOREIGN KEY (programador_id) REFERENCES users(id) ON DELETE SET NULL;
ALTER TABLE tickets ADD CONSTRAINT fk_tickets_engenheiro FOREIGN KEY (engenheiro_id) REFERENCES users(id) ON DELETE SET NULL;
```

## Versão 2.0 - Novos Recursos Implementados

### 🎯 Melhorias em Orçamentos

#### Tabela de Orçamentos Melhorada
```sql
ALTER TABLE budgets ADD COLUMN data_validade DATE AFTER valor_mao_obra;
ALTER TABLE budgets ADD COLUMN token_autorizacao VARCHAR(255) UNIQUE AFTER data_validade;
ALTER TABLE budgets MODIFY status ENUM('pendente', 'aprovado', 'rejeitado', 'expirado') DEFAULT 'pendente';
ALTER TABLE budgets ADD COLUMN rejeitado_por INT AFTER autorizado_por;
ALTER TABLE budgets ADD COLUMN data_rejeicao DATETIME AFTER data_autorizacao;
ALTER TABLE budgets ADD COLUMN motivo_rejeicao TEXT AFTER data_rejeicao;
ALTER TABLE budgets RENAME COLUMN valor TO valor_total;
```

#### Nova Tabela de Itens de Orçamento
```sql
CREATE TABLE budget_items (
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
```

#### Nova Tabela de Serviços Avulsos
```sql
CREATE TABLE avulso_services (
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
```

### 📊 Dashboard Estatístico

- **Nova página de dashboard** em `/admin/dashboard`
- **Gráficos interativos** com Chart.js
- **KPIs principais**: Chamados, Orçamentos, Serviços Avulsos, Clientes
- **Resumo Financeiro**: Total de receitas, contratos, notas fiscais
- **Atividades Recentes**: Chamados, orçamentos e aprovações pendentes
- **Estatísticas por Status**: Visualização de orçamentos por status

### 💰 Orçamentos com Aprovação via WhatsApp

**Funcionalidades:**
- Cada orçamento recebe um **token de autorização único**
- **Link de impressão** com logo e dados da empresa
- **Envio via WhatsApp** com link direto de aprovação/rejeição
- **Data de validade** com expiração automática
- **Reativação manual** pelo administrador se necessário

**Endpoints:**
- `GET /admin/dashboard` - Visualizar dashboard
- `GET /admin/imprimirOrcamentoNovo/{id}` - Imprimir orçamento (novo formato com itens)
- `POST /admin/adicionarItemOrcamento` - Adicionar item ao orçamento
- `GET /admin/removerItemOrcamento/{item_id}` - Remover item do orçamento
- `GET /admin/enviarWhatsAppOrcamento/{id}` - Gerar link WhatsApp e redirecionar
- `GET /auth/autorizarOrcamento/{token}` - Página de autorização via link
- `POST /auth/aprovarOrcamento` - Aprovar orçamento via link
- `POST /auth/rejeitarOrcamento` - Rejeitar orçamento via link

### 📋 Gerenciamento de Serviços Avulsos

**Endpoints:**
- `GET /admin/servicosAvulsos` - Listar serviços avulsos
- `GET /admin/novoServicoAvulso` - Formulário novo serviço
- `POST /admin/salvarServicoAvulsoNovo` - Salvar novo serviço
- `GET /admin/editarServicoAvulso/{id}` - Editar serviço
- `POST /admin/salvarEdicaoServicoAvulso/{id}` - Salvar edição
- `GET /admin/excluirServicoAvulso/{id}` - Excluir serviço

### 🔒 Segurança e Validação

- **Tokens únicos por orçamento**: `bin2hex(random_bytes(32))`
- **Validação de expiração**: Status automático 'expirado' para orçamentos vencidos
- **Sanitização de entrada**: Todos os formulários usam `Security::sanitizeInput()`
- **CSRF Protection**: Todos os formulários possuem token CSRF
- **Autorização sem login**: Clientes podem aprovar/rejeitar via link seguro

### 📝 Novos Modelos

**DashboardModel.php**
- `getTicketStats()` - Estatísticas de chamados por status
- `getBudgetStats()` - Estatísticas de orçamentos
- `getAvulsoServiceStats()` - Estatísticas de serviços avulsos
- `getClientStats()` - Total de clientes cadastrados
- `getFinancialSummary()` - Resumo financeiro
- `getTicketsByClientChart()` - Dados para gráfico de chamados por cliente
- `getBudgetsByStatusChart()` - Dados para gráfico de status de orçamentos
- `getPendingApprovals()` - Orçamentos aguardando aprovação

**AvulsoServiceModel.php**
- CRUD completo para serviços avulsos
- Filtro por cliente
- Controle de status (pendente, concluído, cancelado)

### 🔄 Melhorias no OrcamentoModel

- Suporte a **itens separados** (peças, mão de obra, serviços)
- Cálculo **automático de totais**
- **Tokens de autorização** únicos por orçamento
- **Gestão de validade** com auto-expiração
- **Rejeição com motivo**

### 🎨 Novos Controladores

**DashboardController.php**
- Centraliza lógica de agregação de dados
- Atualiza status de orçamentos expirados automaticamente

**Extensões no AdminController**
- Métodos para gerenciar itens de orçamento
- Impressão com novo layout
- Integração com WhatsApp
- Gerenciamento completo de serviços avulsos

**Extensões no AuthController**
- Autorização segura via token
- Aprovação/Rejeição sem login

### 📄 Novas Views

- `admin/dashboard.php` - Dashboard com gráficos e KPIs
- `admin/imprimir_orcamento.php` - Impressão profissional com logo da empresa
- `auth/autorizar_orcamento.php` - Página de autorização via link
- `auth/sucesso.php` - Confirmação de sucesso
- `auth/erro.php` - Página de erro

### 📱 Integração WhatsApp

**Fluxo:**
1. Admin clica "Enviar WhatsApp"
2. Sistema gera link único com token
3. Link é compartilhado via WhatsApp.com
4. Cliente abre link seguro (sem necessidade de login)
5. Cliente aprova/rejeita com motivo opcional
6. Sistema registra resposta automaticamente

### ⚙️ Instalação / Atualização

**Passo 1:** Execute as migrations SQL no phpMyAdmin:

```sql
-- Se a tabela budgets ainda não existir (antes da v2.0)
CREATE TABLE budgets (
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

CREATE TABLE budget_items (
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

CREATE TABLE avulso_services (
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
```

**Passo 2:** Fazer deploy do código

**Passo 3:** Acessar `/admin/dashboard` para visualizar o novo painel

### 🚀 Próximas Melhorias

- [ ] Relatórios de orçamentos por período
- [ ] Integração com e-mail para notificações automáticas
- [ ] Histórico de alterações de orçamentos
- [ ] Backup automático de documentos
- [ ] Controle de acesso granular para orçamentos
- [ ] Compartilhamento de orçamentos entre usuários

