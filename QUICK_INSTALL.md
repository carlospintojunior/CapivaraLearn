# CapivaraLearn - Instalação Rápida

## 🚀 Instalação Automatizada (Recomendada)

Para Ubuntu 24.04.2 LTS com 2GB RAM:

```bash
# Baixar e executar o instalador
wget https://raw.githubusercontent.com/carlospintojunior/CapivaraLearn/main/install-ubuntu.sh
chmod +x install-ubuntu.sh
./install-ubuntu.sh
```

**O script automaticamente:**
- ✅ Instala PHP 8.2, MariaDB, Nginx
- ✅ Configura banco de dados
- ✅ Otimiza para 2GB RAM
- ✅ Configura SSL (opcional)
- ✅ Configura backup automático
- ✅ Testa instalação

## 📖 Instalação Manual

Para instalação detalhada passo-a-passo, consulte: **[INSTALL.md](INSTALL.md)**

## ⚡ Pós-Instalação

1. **Acesse:** `http://seu-servidor.com`
2. **Registre** o primeiro usuário (será admin)
3. **Configure** email nas configurações (opcional)
4. **Teste** criação de cursos e módulos

## 🔧 Comandos Úteis

```bash
# Verificar status dos serviços
sudo systemctl status nginx php8.2-fpm mariadb

# Ver logs do sistema
tail -f /var/www/capivaralearn/logs/sistema.log

# Backup manual
sudo /usr/local/bin/backup-capivaralearn.sh

# Atualizar sistema
cd /var/www/capivaralearn && sudo git pull
```

## 🆘 Suporte

- **Documentação completa:** [INSTALL.md](INSTALL.md)
- **Problemas:** GitHub Issues
- **Modelo:** 100% Gratuito + Contribuições Voluntárias
