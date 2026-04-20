# A.R.M.A — Aplicação de Registro e Mapeamento de Ativos

Versão PHP standalone (instalador web tipo CMS) pronta para `www/html/arma/`.

## Instalação rápida

1. Copie todos os arquivos para `/var/www/html/arma/` (ou equivalente).
2. Garanta permissões de escrita em `config/` e `backups/`:
   ```bash
   sudo chown -R www-data:www-data /var/www/html/arma
   sudo chmod -R 750 /var/www/html/arma/config /var/www/html/arma/backups
   ```
3. Crie um banco vazio no MySQL/MariaDB **ou** PostgreSQL:
   ```sql
   -- MySQL
   CREATE DATABASE arma CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   CREATE USER 'arma'@'localhost' IDENTIFIED BY 'senha-forte';
   GRANT ALL ON arma.* TO 'arma'@'localhost';
   ```
   ```sql
   -- PostgreSQL
   CREATE DATABASE arma;
   CREATE USER arma WITH PASSWORD 'senha-forte';
   GRANT ALL PRIVILEGES ON DATABASE arma TO arma;
   ```
4. Acesse `http://seu-servidor/arma/` no navegador. O instalador gráfico abrirá automaticamente.
5. Siga os 4 passos: Requisitos → Banco → Admin → Concluído.
6. **Após instalar, remova o diretório `install/`** do servidor.

## Credenciais padrão

- Usuário: `admin`
- Senha: `admin`
- Troca de senha **obrigatória** no primeiro acesso (quando admin/admin é mantido).

## Estrutura

```
arma/
├── index.php              # Painel principal
├── login.php              # Tela de login
├── change-password.php    # Troca de senha
├── users.php              # Gestão de usuários (admin)
├── assets.php             # Gestão de ativos e categorias (admin)
├── audit.php              # Logs de auditoria (admin)
├── backup.php             # Backup & DR (admin)
├── api/
│   ├── assets.php         # JSON de ativos
│   └── healthcheck.php    # Ping TCP de cada ativo
├── includes/              # Bootstrap, Auth, DB, Audit
├── install/               # Instalador (apagar após uso)
├── sql/                   # Schemas mysql e pgsql
├── cli/backup.php         # Script de backup via cron
├── assets/                # CSS e JS
├── config/config.php      # Gerado pelo instalador
├── backups/               # Dumps SQL gerados
└── docs/DISASTER_RECOVERY.md
```

## Backup automático (cron)

```cron
0 2 * * * php /var/www/html/arma/cli/backup.php >> /var/log/arma-backup.log 2>&1
```

## Disaster Recovery

Veja [`docs/DISASTER_RECOVERY.md`](docs/DISASTER_RECOVERY.md).
