# Atualização v2.1 - Gerenciamento Completo de Clientes

## Novas Funcionalidades

### 1. **Gestão de Clientes Completa**
- ✅ Cadastro de novos clientes via administrador
- ✅ Anotações visíveis para o cliente (que o próprio cliente pode visualizar)
- ✅ Anotações internas (apenas para sua empresa)
- ✅ Perfil do cliente com todos os dados vinculados
- ✅ Visualização centralizada de contratos, orçamentos, chamados e serviços

### 2. **Novas Views**
- `novo_cliente.php` - Formulário para cadastrar novo cliente
- `editar_cliente.php` - **ATUALIZADO** com campos de anotações
- `visualizar_cliente.php` - Perfil completo do cliente com tabs para documentos

### 3. **Novo Model**
- `ClienteModel.php` - Gerenciamento específico de clientes com métodos para:
  - Buscar cliente por ID
  - Atualizar dados do cliente (incluindo anotações)
  - Buscar contratos, orçamentos, chamados, serviços e notas do cliente
  - Atualizar anotações separadamente

## Instruções de Instalação

### No cPanel/phpMyAdmin:

Executar o seguinte SQL para adicionar os novos campos à tabela `users`:

```sql
ALTER TABLE users ADD COLUMN IF NOT EXISTS responsavel_nome VARCHAR(150) AFTER perfil;
ALTER TABLE users ADD COLUMN IF NOT EXISTS cep VARCHAR(10) AFTER responsavel_nome;
ALTER TABLE users ADD COLUMN IF NOT EXISTS logradouro VARCHAR(150) AFTER cep;
ALTER TABLE users ADD COLUMN IF NOT EXISTS numero VARCHAR(10) AFTER logradouro;
ALTER TABLE users ADD COLUMN IF NOT EXISTS complemento VARCHAR(100) AFTER numero;
ALTER TABLE users ADD COLUMN IF NOT EXISTS bairro VARCHAR(100) AFTER complemento;
ALTER TABLE users ADD COLUMN IF NOT EXISTS cidade VARCHAR(100) AFTER bairro;
ALTER TABLE users ADD COLUMN IF NOT EXISTS estado VARCHAR(2) AFTER cidade;
ALTER TABLE users ADD COLUMN IF NOT EXISTS anotacoes_visivel LONGTEXT DEFAULT NULL COMMENT 'Anotações visíveis para o cliente' AFTER estado;
ALTER TABLE users ADD COLUMN IF NOT EXISTS anotacoes_interna LONGTEXT DEFAULT NULL COMMENT 'Anotações internas (apenas empresa)' AFTER anotacoes_visivel;
```

### No Admin do Sistema:

1. **Ir para:** `/admin/clientes`
2. **Clicar em:** "Novo Cliente"
3. **Preencher:**
   - Nome/Razão Social
   - CPF/CNPJ (único no sistema)
   - Email (único no sistema)
   - Telefone/WhatsApp
   - Responsável (para empresas)
   - Endereço (CEP auto-preenche os dados)
   - Anotações Visíveis (cliente vê)
   - Anotações Internas (empresa apenas)

## Fluxo de Uso

### Para o Administrador:
1. Criar novo cliente: `Admin > Clientes > Novo Cliente`
2. Editar cliente: Clique em "Editar" na lista
3. Visualizar perfil completo: Clique em "Visualizar" na lista
   - Vê todas as informações do cliente
   - Vê anotações visíveis e internas
   - Vê todos os contratos, orçamentos, chamados, serviços e notas vinculados

### Para o Cliente:
- Ao fazer login em sua conta, pode visualizar:
  - Suas anotações visíveis (mensagens da empresa)
  - Seus orçamentos, contratos e documentos
  - Seu perfil completo

## Estrutura do Banco de Dados

### Campos Adicionados à Tabela `users`:

| Campo | Tipo | Descrição |
|-------|------|-----------|
| `responsavel_nome` | VARCHAR(150) | Nome do responsável (para empresas) |
| `cep` | VARCHAR(10) | CEP do endereço |
| `logradouro` | VARCHAR(150) | Rua/Avenida |
| `numero` | VARCHAR(10) | Número do imóvel |
| `complemento` | VARCHAR(100) | Apto, sala, etc |
| `bairro` | VARCHAR(100) | Bairro |
| `cidade` | VARCHAR(100) | Cidade |
| `estado` | VARCHAR(2) | Estado (UF) |
| `anotacoes_visivel` | LONGTEXT | Anotações visíveis para o cliente |
| `anotacoes_interna` | LONGTEXT | Anotações internas (confidenciais) |

## APIs Internas

### ClienteModel

```php
$clienteModel = new ClienteModel();

// Buscar cliente
$cliente = $clienteModel->getClienteById($id);

// Listar todos os clientes
$clientes = $clienteModel->getAllClientes();

// Atualizar cliente
$clienteModel->updateCliente($id, [
    'nome' => 'Novo Nome',
    'email' => 'novo@email.com',
    'anotacoes_visivel' => 'Nota para o cliente',
    'anotacoes_interna' => 'Nota interna'
]);

// Buscar documentos do cliente
$contratos = $clienteModel->getContractsByCliente($id);
$orcamentos = $clienteModel->getBudgetsByCliente($id);
$chamados = $clienteModel->getTicketsByCliente($id);
$servicos = $clienteModel->getServicesByCliente($id);
$notas = $clienteModel->getNotesByCliente($id);

// Atualizar apenas as anotações
$clienteModel->updateAnotacoesVisivel($id, 'Nova nota');
$clienteModel->updateAnotacoesInterna($id, 'Nota interna');
```

## Rotas Adicionadas

- `GET /admin/clientes` - Lista de clientes (existente, atualizado)
- `GET /admin/novoCliente` - Formulário de novo cliente
- `POST /admin/salvarNovoCliente` - Salvar novo cliente
- `GET /admin/visualizarCliente/{id}` - Perfil completo do cliente
- `GET /admin/editarCliente/{id}` - Editar cliente (existente, atualizado)
- `POST /admin/salvarEdicaoCliente/{id}` - Salvar edição (existente, atualizado)

## Próximos Passos

- [ ] Enviar email com credenciais temporárias ao criar novo cliente
- [ ] Adicionar permissões de acesso por anotações internas
- [ ] Criar relatório de anotações por cliente
- [ ] Implementar filtros avançados na listagem de clientes

## Changelog

### v2.1
- ✅ Adicionado model ClienteModel
- ✅ Adicionados campos de anotações (visível e interna)
- ✅ Criada view nova_cliente.php
- ✅ Criada view visualizar_cliente.php
- ✅ Atualizada view editar_cliente.php
- ✅ Adicionadas ações no AdminController
- ✅ Visualização centralizada de documentos do cliente

---

**Desenvolvido com ❤️ por Copilot**
