# CapivaraLearn - Checklist de Produção

## ✅ Pré-Lançamento

### Servidor e Infraestrutura
- [ ] Ubuntu 24.04.2 LTS instalado
- [ ] Mínimo 2GB RAM disponível
- [ ] 10GB+ espaço em disco livre
- [ ] Domínio configurado (DNS apontando para o servidor)
- [ ] SSL/HTTPS configurado (Let's Encrypt recomendado)
- [ ] Firewall configurado (UFW)

### Serviços Base
- [ ] PHP 8.2 + extensões necessárias
- [ ] MariaDB otimizado para 2GB RAM
- [ ] Nginx configurado com cache
- [ ] PHP-FPM otimizado (max 8 processos)
- [ ] Arquivo swap de 1GB criado

### CapivaraLearn
- [ ] Código baixado/atualizado da branch main
- [ ] Composer dependencies instaladas
- [ ] arquivo `config.php` configurado
- [ ] Banco de dados inicializado
- [ ] Permissões corretas (www-data)
- [ ] Diretórios logs/cache/backup criados

### Segurança
- [ ] Senhas fortes configuradas (MySQL, usuários)
- [ ] Arquivos sensíveis protegidos (.htaccess/nginx)
- [ ] Headers de segurança configurados
- [ ] SSL/TLS funcionando
- [ ] Backups automáticos configurados

### Testes Funcionais
- [ ] Página inicial carrega sem erros
- [ ] Registro de usuário funciona
- [ ] Login/logout funciona
- [ ] Dashboard carrega corretamente
- [ ] Criação de universidades funciona
- [ ] Criação de cursos funciona
- [ ] Sistema de módulos funciona
- [ ] Upload de arquivos funciona
- [ ] Logs são gravados corretamente

### Performance
- [ ] Tempo de carregamento < 3 segundos
- [ ] Cache funciona (nginx + browser)
- [ ] Uso de RAM < 1.5GB em operação normal
- [ ] Banco de dados otimizado (índices)
- [ ] Arquivos estáticos servidos pelo nginx

### Monitoramento
- [ ] Logs configurados e funcionando
- [ ] Backup automático funcionando
- [ ] Script de monitoramento instalado
- [ ] Alertas configurados (opcional)

## 🚀 Comandos de Verificação

### Status dos Serviços
```bash
sudo systemctl status nginx php8.2-fpm mariadb
```

### Teste de Conectividade
```bash
curl -I http://seu-dominio.com
```

### Verificar Recursos
```bash
free -h
df -h
top
```

### Teste do Banco
```bash
mysql -u capivaralearn -p -e "SELECT COUNT(*) FROM usuarios;"
```

### Verificar Logs
```bash
tail -f /var/www/capivaralearn/logs/sistema.log
tail -f /var/log/nginx/capivaralearn_error.log
```

### Teste de Backup
```bash
sudo /usr/local/bin/backup-capivaralearn.sh
ls -la /var/backups/capivaralearn/
```

## 🔧 Configurações de Produção

### PHP (php.ini)
```ini
memory_limit = 128M
max_execution_time = 300
max_input_vars = 3000
post_max_size = 64M
upload_max_filesize = 32M
display_errors = Off
log_errors = On
```

### PHP-FPM (www.conf)
```ini
pm = dynamic
pm.max_children = 8
pm.start_servers = 2
pm.min_spare_servers = 1
pm.max_spare_servers = 3
pm.max_requests = 500
```

### MariaDB (my.cnf)
```ini
innodb_buffer_pool_size = 512M
max_connections = 50
query_cache_size = 32M
tmp_table_size = 32M
max_heap_table_size = 32M
```

## 📈 Métricas de Sucesso

### Performance Esperada
- **Tempo de resposta:** < 2 segundos
- **Uso de RAM:** < 1.5GB (80% dos 2GB)
- **Uso de CPU:** < 50% em operação normal
- **Usuários simultâneos:** 20-30 usuários

### Capacidade Estimada
- **Usuários cadastrados:** 1000+
- **Cursos ativos:** 100+
- **Módulos/lições:** 1000+
- **Armazenamento:** 5GB para conteúdo

## 🎯 Pós-Lançamento

### Primeiros Dias
- [ ] Monitorar logs de erro constantemente
- [ ] Verificar performance sob carga real
- [ ] Acompanhar registros de usuários
- [ ] Testar funcionalidades críticas
- [ ] Verificar backups diários

### Primeira Semana
- [ ] Analisar métricas de uso
- [ ] Otimizar consultas lentas (se houver)
- [ ] Ajustar configurações se necessário
- [ ] Documentar problemas encontrados
- [ ] Coletar feedback dos usuários

### Primeiro Mês
- [ ] Análise completa de performance
- [ ] Planejamento de melhorias
- [ ] Verificação de integridade dos backups
- [ ] Análise de logs de segurança
- [ ] Atualização da documentação

## 🚨 Plano de Contingência

### Em caso de problemas:

1. **Site fora do ar**
   ```bash
   sudo systemctl restart nginx php8.2-fpm
   ```

2. **Banco de dados com problemas**
   ```bash
   sudo systemctl restart mariadb
   ```

3. **Restaurar backup**
   ```bash
   # Parar serviços
   sudo systemctl stop nginx php8.2-fpm
   
   # Restaurar banco
   mysql -u capivaralearn -p capivaralearn < /var/backups/capivaralearn/db_YYYYMMDD_HHMMSS.sql
   
   # Restaurar arquivos
   cd /var/www && sudo tar -xzf /var/backups/capivaralearn/files_YYYYMMDD_HHMMSS.tar.gz
   
   # Reiniciar serviços
   sudo systemctl start php8.2-fpm nginx
   ```

4. **Falta de memória**
   ```bash
   # Reduzir processos PHP
   sudo nano /etc/php/8.2/fpm/pool.d/www.conf
   # pm.max_children = 4
   sudo systemctl restart php8.2-fpm
   ```

## 📞 Contatos de Emergência

- **Desenvolvedor:** [Inserir contato]
- **Servidor/Hosting:** [Inserir contato]
- **Domínio:** [Inserir contato]

---

**🎉 Seu CapivaraLearn está pronto para produção!**

Versão 0.7.0 - Community Model
100% Gratuito com Contribuições Voluntárias
