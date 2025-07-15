# 🚀 Guia de Instalação - CapivaraLearn

## Preparação do Sistema

### 1. Reinicialização do Banco de Dados

Para reiniciar o banco de dados do zero:

```bash
# 1. Acesse o MySQL
mysql -u root -p

# 2. Remova o banco existente (se houver)
DROP DATABASE IF EXISTS capivaralearn;

# 3. Crie um novo banco limpo
CREATE DATABASE capivaralearn CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

# 4. Saia do MySQL
exit
```

### 2. Executar a Instalação

1. **Abra o navegador** e acesse: `http://localhost/CapivaraLearn/install.php`

2. **Execute a instalação** seguindo os passos na tela

3. **Verificar logs** em `logs/sistema.log` para acompanhar o processo

## 📋 Estrutura do Banco Criada

O instalador criará automaticamente as seguintes tabelas:

### Tabelas Principais:
- ✅ `usuarios` - Usuários do sistema (com campos de termos de uso)
- ✅ `universidades` - Universidades/Instituições
- ✅ `cursos` - Cursos das universidades
- ✅ `disciplinas` - Disciplinas dos cursos (com campo `concluido`)
- ✅ `topicos` - Tópicos das disciplinas
- ✅ `unidades_aprendizagem` - Unidades de aprendizagem
- ✅ `matriculas` - Matrículas dos usuários em cursos

### Tabelas de Apoio:
- ✅ `email_tokens` - Tokens de verificação de email
- ✅ `email_log` - Log de emails enviados
- ✅ `configuracoes_usuario` - Configurações do usuário

## 🔧 Recursos Implementados

### Termos de Uso:
- ✅ Campo `termos_aceitos` na tabela `usuarios`
- ✅ Campo `data_aceitacao_termos` para auditoria
- ✅ Campo `versao_termos_aceitos` para controle de versão
- ✅ Validação obrigatória no cadastro

### Funcionalidades Completas:
- ✅ Sistema de email com verificação
- ✅ Estrutura hierárquica (Universidade → Curso → Disciplina → Tópico → Unidade)
- ✅ Campos de conclusão para disciplinas
- ✅ Sistema de matrículas
- ✅ Configurações personalizáveis por usuário
- ✅ Conformidade com LGPD

## 🧪 Testes Pós-Instalação

### 1. Verificar Estrutura do Banco:
```sql
-- Verificar se todas as tabelas foram criadas
SHOW TABLES;

-- Verificar estrutura da tabela usuarios
DESCRIBE usuarios;

-- Verificar se os campos de termos foram criados
SELECT termos_aceitos, data_aceitacao_termos, versao_termos_aceitos FROM usuarios LIMIT 1;
```

### 2. Testar Funcionalidades:
1. **Cadastro de usuário** com concordância de termos
2. **Verificação de email** 
3. **Login no sistema**
4. **Navegação no dashboard**

### 3. Verificar Logs:
```bash
# Ver logs do sistema
tail -f logs/sistema.log

# Ver logs de erro do PHP
tail -f logs/php_errors.log
```

## 🎯 Próximos Passos

Após a instalação bem-sucedida:

1. **Teste o cadastro** em `login.php`
2. **Verifique se os termos** abrem em `termos_uso.html`
3. **Confirme a validação** do checkbox de termos
4. **Teste o fluxo completo** de cadastro → verificação → login

## 🛠️ Troubleshooting

### Erro: "could not find driver"
```bash
# Instalar driver PDO MySQL
sudo apt install php-mysql
sudo service apache2 restart
```

### Erro: "Permission denied" em logs
```bash
# Corrigir permissões
sudo chmod -R 777 logs/
```

### Erro: "Database connection failed"
```bash
# Verificar se MySQL está rodando
sudo systemctl status mysql
# ou para XAMPP:
sudo /opt/lampp/lampp status
```

## 📝 Observações Importantes

1. **Backup**: Sempre faça backup antes de reinstalar
2. **Logs**: Monitore os logs durante a instalação
3. **Permissões**: Certifique-se que as pastas têm permissões corretas
4. **Termos**: Os termos de uso são obrigatórios para novos usuários

---

**Versão do Schema**: 1.0  
**Última atualização**: Janeiro 2025  
**Arquivo de referência**: `DatabaseSchema.md`
