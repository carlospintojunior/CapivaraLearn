# Organização do GitHub Copilot Memory

Este documento explica como as notas do Copilot estão organizadas para o projeto CapivaraLearn e para outros projetos futuros.

## Os três escopos de memória

| Escopo | Caminho | Persiste ao formatar? | Disponível em outro computador? |
|---|---|---|---|
| **User memory** | `/memories/` | ✅ Sim | ✅ Sim (vinculado à conta GitHub) |
| **Session memory** | `/memories/session/` | ❌ Não | ❌ Não (temporário por conversa) |
| **Repo memory** | `/memories/repo/` | ❌ Não | ❌ Não (local no workspace) |

> O user memory é armazenado na nuvem do Copilot, vinculado à **conta GitHub**, não à máquina nem ao projeto.

---

## Arquivos criados para o CapivaraLearn

Todos com prefixo `capivara_` para não misturar com outros projetos:

| Arquivo | Conteúdo |
|---|---|
| `/memories/capivara_patterns.md` | Padrões de arquitetura PHP, estrutura do banco, padrões de sidebar, includes |
| `/memories/capivara_deploy_rules.md` | Regras críticas de deploy — o que NUNCA e SEMPRE fazer |
| `/memories/capivara_postmortem_2026-03-24.md` | Post-mortem completo do incidente de 24/Mar/2026 (1h+ de downtime) |
| `/memories/deploy_caution.md` | Aviso geral sobre deploy — vale para todos os projetos |

---

## Estratégia para múltiplos projetos

Use **prefixo por projeto** nos arquivos de user memory:

```
/memories/
├── capivara_patterns.md        ← CapivaraLearn: padrões de arquitetura
├── capivara_deploy_rules.md    ← CapivaraLearn: regras de deploy
├── capivara_postmortem_*.md    ← CapivaraLearn: incidentes
├── projeto2_patterns.md        ← Outro projeto
├── projeto3_deploy_rules.md    ← Outro projeto
└── deploy_caution.md           ← Aviso geral (todos os projetos)
```

O Copilot carrega **todos** os arquivos de `/memories/` automaticamente em qualquer conversa, então ao abrir qualquer projeto ele já conhece o contexto dos demais. Isso é uma vantagem (contexto rico), mas atenção: **não armazenar senhas ou dados sensíveis** no user memory.

---

## O que NÃO armazenar no /memories/

- Senhas, tokens, chaves de API
- Dados pessoais de usuários
- Qualquer coisa que não deveria ser visível em outros projetos/contextos

As credenciais ficam em `includes/environment.ini` (gitignored) somente no servidor e localmente.
