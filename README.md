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

## Banco de dados

| Situação | Arquivo | Como usar |
|---|---|---|
| Instalação nova (banco vazio) | `database.sql` | phpMyAdmin > selecionar o banco > Importar |
| Sistema já em produção | `database/migracao_correcao_schema.sql` | **Backup** > phpMyAdmin > selecionar o banco > Importar |

A migração é idempotente (pode rodar mais de uma vez) e não apaga dados. Ela adiciona as colunas de `users` (`permissoes`, `cep`, `logradouro`, `numero`, `complemento`, `bairro`, `cidade`, `estado`, `responsavel_nome`), as colunas v2 de `tickets` e `budgets`, o valor `Servico Avulso` em `tickets.tipo_servico`, e cria as tabelas ausentes (`budget_items`, `avulso_services`, `projetos_software`, `financeiro_contabil`, `admin_logs`) e os índices usados pela Visão Geral. Orçamentos antigos sem token ganham um token de autorização.

## Deploy pelo cPanel (Git Version Control)

O arquivo `.cpanel.yml` copia o código para `/home2/coninfom/public_html/cm` (sem `.git`, `database/`, `database.sql`, `README.md`, zips e sem tocar em `uploads/`).

1. cPanel > **Git Version Control** > **Manage** no repositório.
2. Aba **Pull or Deploy** > **Update from Remote** (traz os commits do GitHub).
3. Na mesma aba, **Deploy HEAD Commit** (executa o `.cpanel.yml`).

Também há o deploy automático por GitHub Actions (`.github/workflows/deploy-cpanel-sftp.yml`) a cada push em `main`.

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

- **Visão Geral do administrador** em `/admin` (e `/admin/dashboard`), restrita ao perfil `admin`
- **KPIs**: Chamados (total/abertos/andamento/encerrados), Orçamentos (emitidos/pendentes/aceitos/rejeitados), Financeiro (a receber, a pagar, saldo geral e faturamento do mês) e Administração (clientes, novos registros, status do sistema)
- **Gráficos (Chart.js)**: chamados abertos x encerrados (30 dias, linhas/barras), conversão de orçamentos (donut) e fluxo de caixa (6 meses)
- **Tabelas**: últimos chamados, próximos vencimentos financeiros (7 dias) e logs administrativos
- **Logs administrativos**: tabela `admin_logs`, gravada por `AuditLogModel::record()` em ações críticas (exclusões, decisões de orçamento, usuários, lançamentos, logins de funcionários)
- **Performance**: consultas com agregação condicional, filtros de data por intervalo e índices (ver migração); a fonte financeira é `financeiro_contabil`
- **Definições**: "encerrado" = `finalizado` + `rejeitado`; "em andamento" = `andamento`, `em_analise`, `em_execucao`, `esperando_peca`; conversão = aceitos / (aceitos + rejeitados); o uptime só aparece se o host permitir ler `/proc/uptime`

### 💰 Orçamentos com Aprovação via WhatsApp

**Funcionalidades:**
- Cada orçamento recebe um **token de autorização único**
- **Link de impressão** com logo e dados da empresa
- **Envio via WhatsApp** com link direto de aprovação/rejeição
- **Data de validade** com expiração automática
- **Reativação manual** pelo administrador se necessário

**Endpoints:**
- `GET /admin` ou `GET /admin/dashboard` - Visão Geral do administrador
- `GET /admin/imprimirOrcamentoNovo/{id}` - Imprimir orçamento (novo formato com itens)
- `POST /admin/adicionarItemOrcamento` - Adicionar item ao orçamento
- `POST /admin/removerItemOrcamento/{item_id}` - Remover item do orçamento (CSRF)
- `GET /admin/enviarWhatsAppOrcamento/{id}` - Gerar link WhatsApp e redirecionar
- `POST /admin/aprovarOrcamento/{id}` / `POST /admin/rejeitarOrcamento/{id}` - Decisão do admin (AJAX JSON, CSRF, justificativa opcional/obrigatória na rejeição)
- `POST /client/responderOrcamento/{id}` - Decisão do cliente logado
- `POST /admin/reativarOrcamento/{id}` - Reativar orçamento expirado
- `POST /admin/excluirOrcamento/{id}` - Excluir orçamento (admin, bloqueado se aprovado)
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
- `POST /admin/excluirServicoAvulso/{id}` - Excluir serviço (CSRF)

> Ações destrutivas (`excluir*`, `enviarEmail*`, `assumirChamado`, `analisarIA`) agora exigem POST com `csrf_token`.

### ✅ Fluxo de Aprovação/Rejeição

- Decisão transacional (`SELECT ... FOR UPDATE` + `UPDATE ... WHERE status='pendente'`); orçamentos já finalizados retornam HTTP 409.
- Registra `data_aprovacao`/`data_rejeicao`, usuário e motivo.
- Resposta JSON em requisições AJAX (`X-Requested-With`); a listagem atualiza badge, contadores e filtros em tempo real (`assets/js/budgets.js`, `assets/js/ui.js`).
- Badges: verde = aprovado, vermelho = rejeitado, amarelo = pendente, cinza = expirado.
- Defina `APP_DEBUG=1` no ambiente para exibir erros PHP (desligado por padrão).

### 🔒 Segurança e Validação

- **Tokens únicos por orçamento**: `bin2hex(random_bytes(32))`
- **Validação de expiração**: Status automático 'expirado' para orçamentos vencidos
- **Sanitização de entrada**: Todos os formulários usam `Security::sanitizeInput()`
- **CSRF Protection**: Todos os formulários possuem token CSRF
- **Autorização sem login**: Clientes podem aprovar/rejeitar via link seguro

### 📝 Novos Modelos

**DashboardModel.php**
- `getTicketKpis()`, `getBudgetKpis()`, `getFinanceKpis()`, `getUserKpis()`, `getSystemStatus()` - Cards de resumo
- `getTicketFlow()`, `getCashFlow()` - Séries dos gráficos
- `getRecentTickets()`, `getUpcomingDues()`, `getRecentAdminLogs()` - Tabelas de atividade recente

**AuditLogModel.php**
- `AuditLogModel::record($acao, $descricao, $entidade, $entidadeId, $nivel)` - Grava em `admin_logs` sem interromper o fluxo em caso de falha

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

- `admin/dashboard.php` - Visão Geral (Tailwind + Chart.js) com KPIs, gráficos e tabelas
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

**Passo 3:** Acessar `/admin` (Visão Geral) para visualizar o novo painel

### 🚀 Próximas Melhorias

- [ ] Relatórios de orçamentos por período
- [ ] Integração com e-mail para notificações automáticas
- [ ] Histórico de alterações de orçamentos
- [ ] Backup automático de documentos
- [ ] Controle de acesso granular para orçamentos
- [ ] Compartilhamento de orçamentos entre usuários

