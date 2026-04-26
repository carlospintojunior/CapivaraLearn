# Sistema de Logs CapivaraLearn - Implementado com Sucesso!

## ✅ O que foi implementado:

### 1. **Sistema de logs com Monolog**
- Arquivo: `/includes/logger_config.php`
- Logs rotativos por data automaticamente
- Separação de logs por nível (INFO, WARNING, ERROR, DEBUG)
- Funções helper para facilitar o uso

### 2. **Integração nos arquivos existentes**
- ✅ `login.php` - Logs de autenticação
- ✅ `dashboard.php` - Logs de acesso ao dashboard
- ✅ `logout.php` - Logs de logout
- ✅ `crud/universities_simple.php` - Logs de operações CRUD

### 3. **Arquivos de log gerados**
- `/var/www/capivaralearn/logs/sistema.log` - Arquivo principal atualmente em uso
- `/var/www/capivaralearn/logs/sistema-YYYY-MM-DD.log` - Histórico rotacionado do log principal
- `/var/www/capivaralearn/logs/php_errors.log` - Logs de erro da aplicação via Monolog
- `/var/log/nginx/error.log` - Erros do servidor web e stderr do PHP-FPM
- `/var/log/nginx/access.log` - Log de acesso HTTP (inclui status 500)

### 4. **Tipos de logs registrados**
- **Login/Logout**: Tentativas de login, sucessos, falhas
- **Acesso às páginas**: Dashboard, CRUDs
- **Operações CRUD**: Criação, atualização, exclusão
- **Atividades do usuário**: Registradas no banco e arquivo

## 📋 Como usar:

### Funções disponíveis:
```php
logInfo('Mensagem', ['contexto' => 'dados']);
logWarning('Mensagem de aviso');
logError('Erro ocorrido');
logDebug('Informação de debug');
logActivity($userId, 'acao', 'detalhes', $pdo);
```

### Exemplo de uso:
```php
require_once 'includes/logger_config.php';

logInfo('Usuário acessou a página', [
    'user_id' => $_SESSION['user_id'],
    'page' => 'dashboard'
]);
```

## 🔍 Monitoramento dos logs:

### Comando para monitorar em tempo real:
```bash
tail -f /var/www/capivaralearn/logs/sistema.log
```

### Verificar logs específicos:
```bash
# Log principal atual
tail -n 200 /var/www/capivaralearn/logs/sistema.log

# Histórico do dia
cat /var/www/capivaralearn/logs/sistema-$(date +%Y-%m-%d).log

# Logs de erro da aplicação
cat /var/www/capivaralearn/logs/php_errors.log

# Erros HTTP 500 / stderr do PHP
tail -n 200 /var/log/nginx/error.log

# Requisições e status HTTP
grep 'forgot_password.php' /var/log/nginx/access.log | tail -n 20
```

## ⚠️ Observação importante sobre diagnóstico

- Os logs da aplicação estão sendo gravados em `/var/www/capivaralearn/logs/`.
- Para erros 500, o arquivo mais confiável costuma ser `/var/log/nginx/error.log`, porque ele captura stderr do PHP-FPM mesmo quando a aplicação não consegue completar a resposta.
- `php_errors.log` não substitui o log do servidor; ele cobre apenas erros enviados pelo logger da aplicação.

## 🚀 Sistema funcionando:

**URL de acesso:** https://capivaralearn.com.br/login.php

**Todas as operações estão sendo registradas nos logs:**
- ✅ Login/Logout
- ✅ Acesso às páginas
- ✅ Operações CRUD
- ✅ Erros do sistema

**O sistema original continua funcionando normalmente + logs detalhados!**
