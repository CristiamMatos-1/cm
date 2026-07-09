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
