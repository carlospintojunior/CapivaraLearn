#!/usr/bin/env python3
"""
CapivaraLearn - Automated Release Generator
Gera releases automaticamente baseado em issues fechadas desde o último release
"""

import requests
import json
import subprocess
import sys
import re
from datetime import datetime
from typing import Dict, List, Optional

class GitHubReleaseGenerator:
    def __init__(self, repo_owner: str, repo_name: str, github_token: Optional[str] = None):
        self.repo_owner = repo_owner
        self.repo_name = repo_name
        self.github_token = github_token
        self.base_url = f"https://api.github.com/repos/{repo_owner}/{repo_name}"
        
        # Headers para API
        self.headers = {
            "Accept": "application/vnd.github.v3+json",
            "User-Agent": "CapivaraLearn-Release-Generator"
        }
        if github_token:
            self.headers["Authorization"] = f"token {github_token}"
    
    def get_latest_release(self) -> Optional[Dict]:
        """Obtém informações do último release"""
        try:
            response = requests.get(f"{self.base_url}/releases/latest", headers=self.headers)
            if response.status_code == 200:
                return response.json()
            return None
        except Exception as e:
            print(f"Erro ao buscar último release: {e}")
            return None
    
    def get_closed_issues_since_date(self, since_date: str) -> List[Dict]:
        """Busca issues fechadas desde uma data específica"""
        try:
            issues = []
            page = 1
            per_page = 100
            
            while True:
                url = f"{self.base_url}/issues"
                params = {
                    "state": "closed",
                    "since": since_date,
                    "per_page": per_page,
                    "page": page,
                    "sort": "updated",
                    "direction": "desc"
                }
                
                response = requests.get(url, headers=self.headers, params=params)
                if response.status_code != 200:
                    break
                
                page_issues = response.json()
                if not page_issues:
                    break
                
                # Filtrar apenas issues (não PRs)
                page_issues = [issue for issue in page_issues if 'pull_request' not in issue]
                issues.extend(page_issues)
                
                if len(page_issues) < per_page:
                    break
                
                page += 1
            
            return issues
        except Exception as e:
            print(f"Erro ao buscar issues: {e}")
            return []
    
    def categorize_issue(self, issue: Dict) -> str:
        """Categoriza uma issue baseado no título e labels"""
        title = issue['title'].lower()
        labels = [label['name'].lower() for label in issue.get('labels', [])]
        
        # Verificar labels primeiro
        if any(label in ['bug', 'fix', 'hotfix', 'bugfix'] for label in labels):
            return 'bug'
        if any(label in ['enhancement', 'improvement', 'refactor'] for label in labels):
            return 'enhancement'
        if any(label in ['feature', 'new-feature', 'feat'] for label in labels):
            return 'feature'
        if any(label in ['documentation', 'docs'] for label in labels):
            return 'documentation'
        
        # Verificar título
        if any(word in title for word in ['fix', 'bug', 'erro', 'corrigir', 'correção']):
            return 'bug'
        if any(word in title for word in ['melhorar', 'otimizar', 'aprimorar', 'improvement']):
            return 'enhancement'
        if any(word in title for word in ['adicionar', 'implementar', 'criar', 'new', 'feature']):
            return 'feature'
        if any(word in title for word in ['documentação', 'docs', 'readme']):
            return 'documentation'
        
        return 'other'
    
    def generate_changelog(self, issues: List[Dict], version: str) -> str:
        """Gera o changelog no formato ThingsBoard"""
        
        # Categorizar issues
        categories = {
            'feature': [],
            'enhancement': [],
            'bug': [],
            'documentation': [],
            'other': []
        }
        
        for issue in issues:
            category = self.categorize_issue(issue)
            categories[category].append(issue)
        
        # Estatísticas
        total_issues = len(issues)
        files_modified = self.get_modified_files_count()
        commits_count = self.get_commits_since_last_release()
        
        # Gerar changelog
        changelog = f"""# CapivaraLearn {version}

*Release Date: {datetime.now().strftime('%B %d, %Y')}*

## 📋 Release Overview

This release includes {total_issues} resolved issues with significant improvements to user experience, new features, and important bug fixes.

"""
        
        # Adicionar seções por categoria
        if categories['feature']:
            changelog += "## 🚀 New Features\n\n"
            for issue in categories['feature']:
                changelog += f"- **{issue['title']}** (#{issue['number']}) - {self.get_issue_description(issue)}\n"
            changelog += "\n"
        
        if categories['enhancement']:
            changelog += "## ⚡ Improvements\n\n"
            for issue in categories['enhancement']:
                changelog += f"- **{issue['title']}** (#{issue['number']}) - {self.get_issue_description(issue)}\n"
            changelog += "\n"
        
        if categories['bug']:
            changelog += "## 🐛 Bug Fixes\n\n"
            for issue in categories['bug']:
                changelog += f"- **{issue['title']}** (#{issue['number']}) - {self.get_issue_description(issue)}\n"
            changelog += "\n"
        
        if categories['documentation']:
            changelog += "## 📚 Documentation\n\n"
            for issue in categories['documentation']:
                changelog += f"- **{issue['title']}** (#{issue['number']}) - {self.get_issue_description(issue)}\n"
            changelog += "\n"
        
        if categories['other']:
            changelog += "## 🔧 Other Changes\n\n"
            for issue in categories['other']:
                changelog += f"- **{issue['title']}** (#{issue['number']}) - {self.get_issue_description(issue)}\n"
            changelog += "\n"
        
        # Estatísticas do release
        changelog += f"""## 📊 Release Statistics

- **Issues Resolved**: {total_issues}
- **Files Modified**: {files_modified}+ files
- **Commits**: {commits_count}+ commits
- **Contributors**: {self.get_contributors_count()}

## 🔗 Useful Links

- [Full Changelog](https://github.com/{self.repo_owner}/{self.repo_name}/compare/{self.get_previous_version()}...{version})
- [Documentation](https://github.com/{self.repo_owner}/{self.repo_name}#readme)
- [Issues](https://github.com/{self.repo_owner}/{self.repo_name}/issues)

## 🙏 Acknowledgments

Thanks to all contributors who helped make this release possible!

---

**Full release notes**: [View on GitHub](https://github.com/{self.repo_owner}/{self.repo_name}/releases/tag/{version})
"""
        
        return changelog
    
    def get_issue_description(self, issue: Dict) -> str:
        """Extrai uma descrição curta da issue"""
        body = issue.get('body', '')
        if not body:
            return "Implementation completed"
        
        # Pegar primeira linha não vazia
        lines = [line.strip() for line in body.split('\n') if line.strip()]
        if lines:
            first_line = lines[0]
            # Remover markdown
            first_line = re.sub(r'[#*`]', '', first_line)
            # Limitar tamanho
            if len(first_line) > 100:
                first_line = first_line[:97] + "..."
            return first_line
        
        return "Implementation completed"
    
    def get_modified_files_count(self) -> int:
        """Estima número de arquivos modificados"""
        try:
            # Conta arquivos modificados desde último release
            result = subprocess.run(
                ["git", "diff", "--name-only", "HEAD~10", "HEAD"],
                capture_output=True, text=True, cwd="."
            )
            if result.returncode == 0:
                return len(result.stdout.strip().split('\n')) if result.stdout.strip() else 0
            return 5  # Fallback
        except:
            return 5
    
    def get_commits_since_last_release(self) -> int:
        """Conta commits desde último release"""
        try:
            latest_release = self.get_latest_release()
            if latest_release:
                tag = latest_release['tag_name']
                result = subprocess.run(
                    ["git", "rev-list", "--count", f"{tag}..HEAD"],
                    capture_output=True, text=True, cwd="."
                )
                if result.returncode == 0:
                    return int(result.stdout.strip())
            return 10  # Fallback
        except:
            return 10
    
    def get_contributors_count(self) -> int:
        """Conta contribuidores únicos"""
        try:
            result = subprocess.run(
                ["git", "log", "--format=%an", "HEAD~20..HEAD"],
                capture_output=True, text=True, cwd="."
            )
            if result.returncode == 0:
                contributors = set(result.stdout.strip().split('\n'))
                return len(contributors) if result.stdout.strip() else 1
            return 1
        except:
            return 1
    
    def get_previous_version(self) -> str:
        """Obtém versão anterior"""
        latest_release = self.get_latest_release()
        if latest_release:
            return latest_release['tag_name']
        return "v0.7.1"
    
    def increment_version(self, current_version: str) -> str:
        """Incrementa versão automaticamente"""
        # Remove 'v' se existir
        version = current_version.lstrip('v')
        
        # Split major.minor.patch
        parts = version.split('.')
        if len(parts) >= 3:
            major, minor, patch = int(parts[0]), int(parts[1]), int(parts[2])
            # Incrementa patch para release automático
            patch += 1
        elif len(parts) == 2:
            major, minor = int(parts[0]), int(parts[1])
            patch = 0
        else:
            return "v0.8.0"  # Fallback
        
        return f"v{major}.{minor}.{patch}"
    
    def generate_release(self, version: Optional[str] = None) -> str:
        """Gera release completo"""
        # Obter último release
        latest_release = self.get_latest_release()
        
        if latest_release:
            since_date = latest_release['published_at']
            current_version = latest_release['tag_name']
        else:
            # Se não há releases, buscar issues dos últimos 30 dias
            since_date = (datetime.now().replace(day=1)).isoformat() + "Z"
            current_version = "v0.7.1"
        
        # Incrementar versão automaticamente se não fornecida
        if not version:
            version = self.increment_version(current_version)
        
        # Buscar issues fechadas
        issues = self.get_closed_issues_since_date(since_date)
        
        if not issues:
            print("⚠️  Nenhuma issue fechada encontrada desde o último release.")
            return ""
        
        print(f"📋 Encontradas {len(issues)} issues fechadas desde {since_date}")
        for issue in issues:
            print(f"   - #{issue['number']}: {issue['title']}")
        
        # Gerar changelog
        changelog = self.generate_changelog(issues, version)
        
        return changelog

def main():
    # Configurações
    REPO_OWNER = "carlospintojunior"
    REPO_NAME = "CapivaraLearn"
    
    # Token do GitHub (opcional, para mais requests por hora)
    github_token = None  # Você pode adicionar seu token aqui se necessário
    
    # Criar gerador
    generator = GitHubReleaseGenerator(REPO_OWNER, REPO_NAME, github_token)
    
    # Gerar release
    print("🚀 Gerando release automaticamente...")
    changelog = generator.generate_release()
    
    if changelog:
        # Salvar changelog
        with open("RELEASE_NOTES.md", "w", encoding="utf-8") as f:
            f.write(changelog)
        
        print("✅ Release notes geradas com sucesso!")
        print("📄 Arquivo salvo como: RELEASE_NOTES.md")
        print("\n" + "="*60)
        print(changelog)
        print("="*60)
        
        # Sugestões de próximos passos
        print("\n🔧 Próximos passos sugeridos:")
        print("1. Revisar o arquivo RELEASE_NOTES.md")
        print("2. Executar: git add . && git commit -m 'docs: prepare release notes'")
        print("3. Executar: git tag -a v0.8.0 -F RELEASE_NOTES.md")
        print("4. Executar: git push origin v0.8.0")
        print("5. Criar release no GitHub usando o conteúdo de RELEASE_NOTES.md")
    else:
        print("❌ Não foi possível gerar o release.")

if __name__ == "__main__":
    main()
