# cm
Helpdesk

## Configuração do banco

Defina as variáveis de ambiente antes de iniciar a aplicação:

- `DB_HOST` (padrão: `localhost`)
- `DB_PORT` (padrão: `3306`)
- `DB_NAME` (obrigatória)
- `DB_USER` (obrigatória)
- `DB_PASSWORD` (opcional)

## Deploy com GitHub Actions (cPanel SFTP)

Workflow: `.github/workflows/deploy-cpanel-sftp.yml`

Configure os secrets do repositório:

- `SFTP_HOST`
- `SFTP_PORT` (opcional, padrão 22)
- `SFTP_USERNAME`
- `SFTP_PASSWORD`

Diretório remoto configurado: `/home2/coninfom/public_html/cm`

Observações para evitar erro 404:

- O `.htaccess` da raiz precisa estar publicado.
- O Apache do cPanel deve estar com `mod_rewrite` habilitado.
