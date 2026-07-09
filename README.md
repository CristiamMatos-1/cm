# cm
Helpdesk

## Configuração de ambiente

Defina as variáveis de ambiente no servidor antes do deploy:

- `BASE_URL` (ex.: `/cm`)
- `APP_DEBUG` (`true` ou `false`)
- `SESSION_COOKIE_SECURE` (`true` em produção HTTPS)
- `DB_HOST`
- `DB_NAME`
- `DB_USER`
- `DB_PASS`
- `DB_CHARSET` (opcional, padrão `utf8mb4`)

## Deploy

1. Publicar os arquivos no servidor web.
2. Configurar o apontamento para `index.php` como front controller.
3. Configurar as variáveis de ambiente da aplicação.
4. Importar `/home/runner/work/cm/cm/database.sql` no banco.
5. Garantir permissões de escrita em `/home/runner/work/cm/cm/uploads`.
