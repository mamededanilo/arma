# Plano de Recuperação de Desastres — A.R.M.A

## 1. Estratégia de backup

| Item | Frequência | Local | Retenção |
|---|---|---|---|
| Dump SQL via web (`backup.php`) | Sob demanda | `/backups/` | manual |
| Dump SQL via cron (`cli/backup.php`) | Diário 02:00 | `/backups/` | 30 dias |
| Réplica externa | Diário | S3 / NAS / rsync | 90 dias |
| Snapshot do diretório completo `arma/` | Semanal | Storage offsite | 4 semanas |

### Comando recomendado de réplica offsite

```bash
# rsync para servidor de DR
rsync -avz /var/www/html/arma/backups/ dr-server:/srv/arma-backups/

# ou para AWS S3
aws s3 sync /var/www/html/arma/backups/ s3://meu-bucket/arma/ --delete
```

## 2. Cenários e RTO/RPO

| Cenário | RPO | RTO | Procedimento |
|---|---|---|---|
| Corrupção de tabela | 24h | 15min | Restore via `backup.php → Restaurar` |
| Perda do servidor | 24h | 1h | Reinstalar A.R.M.A + restaurar último dump |
| Migração MySQL → PostgreSQL | — | 30min | Dump no origem + instalar no destino com PG + upload do .sql |
| Comprometimento de credenciais | — | 5min | Trocar senhas em `users.php`; revogar usuário do banco |

## 3. Procedimento de restauração completa

```bash
# 1. Provisionar servidor novo (PHP + Apache/Nginx + MySQL/PG)
sudo apt install apache2 php php-mysql php-pgsql

# 2. Copiar pacote A.R.M.A
sudo cp -r arma/ /var/www/html/
sudo chown -R www-data:www-data /var/www/html/arma
sudo chmod -R 750 /var/www/html/arma/config /var/www/html/arma/backups

# 3. Criar banco vazio (mesmo nome do origem ou diferente)
mysql -u root -p -e "CREATE DATABASE arma CHARACTER SET utf8mb4;"

# 4. Acessar http://novo-servidor/arma/ → instalador
#    Usar credenciais admin/admin (será forçado a trocar)

# 5. Login → Backup / DR → "Restaurar a partir de upload"
#    Subir o arquivo .sql do último dump

# 6. Validar:
#    - Login com usuários originais (senhas restauradas)
#    - Listagem de ativos
#    - Logs de auditoria
#    - Health check funcionando

# 7. Excluir /var/www/html/arma/install/
```

## 4. Migração entre SGBDs

Os dumps gerados pelo A.R.M.A contêm apenas `INSERT`s portáveis (sem sintaxe específica de cada SGBD). Para portar:

1. Gere dump no origem (MySQL).
2. Instale A.R.M.A no destino apontando para PostgreSQL vazio (instalador cria schema na sintaxe correta).
3. Restaure o dump pelo painel de Backup.

## 5. Testes de DR

Recomendado executar trimestralmente:

- [ ] Restaurar backup em ambiente de homologação.
- [ ] Validar integridade dos dados (contagem de ativos, usuários, logs).
- [ ] Verificar que health check, login e auditoria funcionam.
- [ ] Cronometrar RTO real e atualizar este documento.

## 6. Contatos de emergência

| Papel | Nome | Contato |
|---|---|---|
| DBA | _preencher_ | _preencher_ |
| Sysadmin | _preencher_ | _preencher_ |
| Responsável A.R.M.A | _preencher_ | _preencher_ |
