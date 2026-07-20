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
