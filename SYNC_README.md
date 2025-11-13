# 🔄 Script de Sincronização Melhorado

## 📋 Resumo das Melhorias

- `sync_to_xampp.sh`: mantém o ambiente local/XAMPP alinhado com o repositório.
- `sync_to_production.sh`: leva o mesmo fluxo (com preservação opcional) para o servidor público em `/var/www/capivaralearn`.

## 🎯 Arquivos que Podem Ser Preservados

### ✅ SEMPRE Preservados (automático):
```
logs/                    # Histórico de logs do sistema
├── php_errors.log
├── sistema.log
└── capivaralearn.log
```

### 🔧 Configurações (opcional - pergunta ao executar):
```
includes/
├── config.php           # Credenciais do banco de dados
└── environment.ini      # Variáveis de ambiente (SMTP, etc)
```

**Quando preservar?**
- ✅ Atualizações normais de código
- ✅ Correções de bugs
- ✅ Novas features

**Quando NÃO preservar?**
- ❌ Primeira instalação
- ❌ Mudanças no schema do banco
- ❌ Problemas de compatibilidade

### 💾 Dados de Usuário (opcional - pergunta ao executar):
```
backup/                  # Backups manuais/automáticos
cache/                   # Cache do sistema
```

**Quando preservar?**
- ✅ Sempre (a menos que queira limpar cache)

## 🚀 Como Usar

### Uso Básico (Interativo) – XAMPP:
```bash
cd /home/carlos/Documents/GitHub/CapivaraLearn
./sync_to_xampp.sh
```

O script perguntará:
```
Preservar configurações? (S/n): S
Preservar dados de usuário? (S/n): S
```

### Casos de Uso Comuns:

#### 1️⃣ Atualização Normal (Manter Tudo):
```bash
./sync_to_xampp.sh
# Responda: S, S
```
✅ Mantém config.php, environment.ini, backup/, cache/, logs/

#### 2️⃣ Primeira Instalação (Tudo Novo):
```bash
./sync_to_xampp.sh
# Responda: n, n
```
✅ Copia tudo novo (exceto logs que são sempre preservados)

#### 3️⃣ Resetar Configurações (Manter Dados):
```bash
./sync_to_xampp.sh
# Responda: n, S
```
✅ Novos config.php e environment.ini, mas mantém backup/ e cache/

#### 4️⃣ Limpar Cache (Manter Config):
```bash
./sync_to_xampp.sh
# Responda: S, n
```
✅ Mantém configurações, mas limpa cache/ e backup/

### Uso Básico (Interativo) – Produção:
```bash
cd /home/carlos/Documents/GitHub/CapivaraLearn
./sync_to_production.sh
```

O script perguntará as mesmas opções de preservação (configurações e dados de usuário) e cuida de:
- Fazer backup remoto (`logs/`, e opcionalmente `includes/config.php`, `includes/environment.ini`, `backup/`, `cache/`)
- Remover e recriar `/var/www/capivaralearn`
- Enviar um pacote compactado com o projeto
- Ajustar permissões (`www-data:www-data` por padrão)

**Personalização rápida:**
```bash
export SERVER_HOST="deploy@198.23.132.15"
export SERVER_PATH="/var/www/html/CapivaraLearn"
export SSH_KEY="$HOME/.ssh/capivaralearn.ppk"
./sync_to_production.sh
```

## 🔒 Segurança

O script usa um diretório temporário único para cada execução:
```bash
/tmp/capivaralearn_sync_backup_<PID>
```

Isso evita conflitos se você executar o script múltiplas vezes.

## ⚠️ Importante

### O Banco de Dados NÃO é Afetado!

O MySQL/MariaDB fica em `/opt/lampp/var/mysql/` e **não é tocado** pela sincronização.

Apenas os **arquivos PHP** são sincronizados.

## 📊 Exemplo de Saída

```
🔄 Iniciando sincronização CapivaraLearn...

📋 Opções de sincronização:

Deseja preservar arquivos de configuração do XAMPP?
  - includes/config.php (configurações do banco de dados)
  - includes/environment.ini (variáveis de ambiente)

Preservar configurações? (S/n): S

Deseja preservar arquivos de usuário?
  - backup/ (backups de dados)
  - cache/ (cache do sistema)

Preservar dados de usuário? (S/n): S

📂 Diretório de desenvolvimento: /home/carlos/Documents/GitHub/CapivaraLearn

💾 Fazendo backup dos logs existentes...
✅ Logs salvos
💾 Fazendo backup das configurações...
  ✓ config.php salvo
  ✓ environment.ini salvo
💾 Fazendo backup dos dados de usuário...
  ✓ backup/ salvo
  ✓ cache/ salvo

🗑️  Removendo instalação anterior...
📋 Copiando arquivos para XAMPP...

🔄 Restaurando arquivos preservados...
  ↩️  Restaurando logs...
  ✅ Logs restaurados
  ↩️  Restaurando configurações...
    ✓ config.php restaurado
    ✓ environment.ini restaurado
  ↩️  Restaurando dados de usuário...
    ✓ backup/ restaurado
    ✓ cache/ restaurado

🔐 Configurando proprietário (daemon:daemon)...
📄 Configurando permissões...

✅ Sincronização concluída com sucesso!

📊 Resumo:
   • Arquivos copiados: ✓
   • Logs preservados: ✓
   • Configurações preservadas: ✓
   • Dados de usuário preservados: ✓

🌐 Acesse: http://localhost/CapivaraLearn/
🌐 Produção: https://capivaralearn.com.br
```

## 🆘 Solução de Problemas

### Script não executa
```bash
chmod +x sync_to_xampp.sh
```

### Erro de permissões
Execute como está (o script usa sudo internamente)

### Perdeu config.php acidentalmente
```bash
# Rode o instalador
http://localhost/CapivaraLearn/install.php
```

## 📚 Documentação Completa

Veja `SYNC_GUIDE.md` para documentação detalhada.

---
*Script atualizado em: 12 Nov 2025*
