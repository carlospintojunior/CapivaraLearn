# 🔄 Quick Sync Guide - CapivaraLearn

## Fluxo de Trabalho Recomendado

### 1. Desenvolvimento
- Trabalhe sempre no diretório: `/home/carlos/Documents/GitHub/CapivaraLearn`
- Faça suas modificações nos arquivos de desenvolvimento
- Teste localmente quando possível

### 2. Sincronização para Produção
Após fazer qualquer modificação, execute:

```bash
cd /home/carlos/Documents/GitHub/CapivaraLearn
./sync_to_xampp.sh
```

**OU** use os comandos manuais:
```bash
cd /home/carlos/Documents/GitHub/CapivaraLearn

sudo rm -r /opt/lampp/htdocs/CapivaraLearn
sudo cp -r . /opt/lampp/htdocs/CapivaraLearn
sudo chown -R daemon:daemon /opt/lampp/htdocs/CapivaraLearn 
sudo chmod -R 644 /opt/lampp/htdocs/CapivaraLearn 
sudo find /opt/lampp/htdocs/CapivaraLearn -type d -exec chmod 755 {} \;
sudo mkdir -p /opt/lampp/htdocs/CapivaraLearn/logs
sudo chmod 777 /opt/lampp/htdocs/CapivaraLearn/logs
sudo touch /opt/lampp/htdocs/CapivaraLearn/logs/php_errors.log
sudo chmod 666 /opt/lampp/htdocs/CapivaraLearn/logs/php_errors.log
```

### 3. Verificação
- Acesse: http://localhost/CapivaraLearn/
- Teste as funcionalidades modificadas
- Verifique logs em caso de erro: `tail /opt/lampp/htdocs/CapivaraLearn/logs/php_errors.log`

## ✅ Status do Sistema

- **Erro "Database::insert() undefined"**: ✅ CORRIGIDO
- **Sincronização desenvolvimento→produção**: ✅ FUNCIONANDO
- **Script automático**: ✅ DISPONÍVEL (`sync_to_xampp.sh`)
- **Permissões XAMPP**: ✅ CONFIGURADAS
- **Database CRUD**: ✅ FUNCIONANDO
- **UniversityService**: ✅ FUNCIONANDO

## 🚨 Importante

**SEMPRE** execute a sincronização após modificar arquivos no diretório de desenvolvimento.
**NUNCA** edite arquivos diretamente em `/opt/lampp/htdocs/CapivaraLearn/` - suas alterações serão perdidas na próxima sincronização.

---
*Última atualização: 07 Jun 2025*
