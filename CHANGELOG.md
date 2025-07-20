# 📋 CapivaraLearn - Changelog

Todas as alterações notáveis deste projeto serão documentadas neste arquivo.

O formato é baseado em [Keep a Changelog](https://keepachangelog.com/pt-BR/1.0.0/),
e este projeto adere ao [Semantic Versioning](https://semver.org/lang/pt-BR/).

## [1.1.0] - 2025-07-19

### ✅ Adicionado
- **Sistema de Backup e Importação de Grades Curriculares**
  - Exportação completa de curso em formato JSON
  - Importação de estrutura curricular entre usuários
  - Preservação de hierarquia: Curso → Disciplinas → Tópicos → Unidades
  - Interface intuitiva para backup/restore
  - Validação de arquivos e tratamento de erros
  - Estatísticas de importação/exportação
- Botões "Cancelar" em todas as páginas de edição CRUD
- Campo semestre ocultado na tela de disciplinas
- Links para backup/importação no dashboard

### 🔧 Corrigido
- Caracteres "+" indesejados na tabela de cursos
- Falta de opção cancelar nas edições de disciplinas, tópicos e unidades
- Layout de botões nas páginas CRUD (melhor espaçamento)

## [1.0.0] - 2025-07-19

### ✅ Adicionado
- Sistema de versionamento da aplicação
- Exibição de versão no footer das páginas
- Exibição de versão na sidebar do dashboard
- Configuração de fuso horário (America/Sao_Paulo)
- Campo de status/conclusão nas disciplinas
- Correção de campos da tabela matriculas (numero_matricula, progresso, nota_final)
- Sistema de logs com Monolog
- Validação de chaves estrangeiras nas matrículas

### 🔧 Corrigido
- Erro 500 em enrollments_simple.php
- Problemas de JOIN SQL nas consultas
- Violação de restrição de chave estrangeira
- Horário do dashboard (estava 5h adiantado)
- Alinhamento entre DatabaseSchema.md e install.php

### 🗂️ Estrutura
- Tabelas: usuarios, universidades, cursos, disciplinas, topicos, unidades_aprendizagem, matriculas
- Sistema de autenticação e autorização
- CRUD completo para todas as entidades
- Sistema de logs centralizado

---

## Tipos de Mudanças
- `✅ Adicionado` para novas funcionalidades
- `🔧 Corrigido` para correções de bugs
- `📝 Alterado` para mudanças em funcionalidades existentes
- `🗑️ Removido` para funcionalidades removidas
- `🔒 Segurança` em caso de vulnerabilidades
- `⚡ Performance` para melhorias de performance

---

*Formato baseado em [Keep a Changelog](https://keepachangelog.com/pt-BR/1.0.0/)*
