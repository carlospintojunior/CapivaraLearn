# 🐾 CapivaraLearn

![image](https://github.com/user-attachments/assets/a164def9-f2ac-4444-b554-fcc810e5992b)

Sistema de planejamento de estudos modulares para EaD

## 🚀 Como usar

```bash
# Instalar dependências
npm install

# Executar em modo desenvolvimento
npm run dev
```

## 📱 Funcionalidades

- ✅ Gestão de módulos de estudo
- ✅ Controle de tópicos com datas
- ✅ Acompanhamento de progresso
- ✅ Export/Import de dados
- ✅ Interface responsiva

## 🤝 Contribuindo

1. Fork o projeto
2. Crie sua branch: `git checkout -b feature/nova-funcionalidade`
3. Commit suas mudanças: `git commit -m 'Adicionar nova funcionalidade'`
4. Push para a branch: `git push origin feature/nova-funcionalidade`
5. Abra um Pull Request


## Instalar o XAMPP (sugestão)

1. Baixe o XAMPP: https://www.apachefriends.org/download.html
1.1 cd Downloads/
1.2 wget https://sourceforge.net/projects/xampp/files/XAMPP%20Linux/8.2.12/xampp-linux-x64-8.2.12-0-installer.run
1.3 chmod +x xampp-linux-x64-8.2.12-0-installer.run 
1.4 sudo ./xampp-linux-x64-8.2.12-0-installer.run
obs: o XAMPP precisa estar em /opt/lampp


2. Instale o XAMPP
3. Abra o XAMPP
4. Clique em "Start Apache & MySQL"
5. Abra o navegador e acesse: `http://localhost:3000`


## 🔄 Sincronização Desenvolvimento → Produção

O CapivaraLearn utiliza um fluxo de trabalho onde o desenvolvimento é feito em `/home/carlos/Documents/GitHub/CapivaraLearn` e depois sincronizado para o XAMPP em `/opt/lampp/htdocs/CapivaraLearn`.

### Método 1: Script Automático (Recomendado)
```bash
cd /home/carlos/Documents/GitHub/CapivaraLearn
./sync_to_xampp.sh
```

### Método 2: Comandos Manuais
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

**⚠️ Importante:** Execute a sincronização sempre que fizer alterações no código de desenvolvimento!


Abra o navegador e acesse: `http://localhost/CapivaraLearn/install.php`

# Ir para o diretório do projeto
cd /opt/lampp/htdocs/CapivaraLearn

# Criar pasta de logs
sudo mkdir -p /opt/lampp/htdocs/CapivaraLearn/logs

# Dar permissões corretas
HTDOCS_OWNER=$(stat -c '%U:%G' /opt/lampp/htdocs)
echo "Proprietário detectado: $HTDOCS_OWNER"
sudo chown -R $HTDOCS_OWNER logs


Ah, agora entendi o problema! As permissões dos arquivos foram alteradas quando copiamos os arquivos usando sudo. Vamos corrigir as permissões de todos os arquivos do projeto no XAMPP para que você possa editá-los: 

Esse comando faz três coisas:
1. Muda o dono de todos os arquivos para seu usuário (`chown -R $USER:$USER`)
2. Define permissão 644 (leitura/escrita para dono, leitura para outros) para arquivos
3. Define permissão 755 (execução + leitura/escrita para dono, execução + leitura para outros) para diretórios

Agora você deve conseguir editar os arquivos normalmente no VS Code sem precisar de sudo. 

Se ainda aparecer o erro de método indefinido (`undefined method`), podemos tentar resolver, mas primeiro tente editar o arquivo novamente para ver se agora você consegue salvar normalmente.
~~~
sudo chown -R $USER:$USER /opt/lampp/htdocs/CapivaraLearn/ && sudo chmod -R 644 /opt/lampp/htdocs/CapivaraLearn/ && sudo find /opt/lampp/htdocs/CapivaraLearn/ -type d -exec chmod 755 {} \;
~~~


# Dar permissões corretas
sudo chmod -R 755 /opt/lampp/htdocs/CapivaraLearn/logs
(777) no desespero!

# Verificar se foi criada
ls -la logs/


Será que precisa:
sudo apt-get update && \
sudo apt-get install -y composer && \
cd /opt/lampp/htdocs/CapivaraLearn && \
sudo composer require phpmailer/phpmailer
?




# MariaDB

/opt/lampp/bin/mysql -u root capivaralearn 

/opt/lampp/bin/mysql -u root capivaralearn -e "SHOW TABLES;"
/opt/lampp/bin/mysql -u root capivaralearn -e "DESCRIBE inscricoes;"
/opt/lampp/bin/mysql -u root capivaralearn -e "SHOW TABLES LIKE '%usuario%';"
/opt/lampp/bin/mysql -u root capivaralearn -e "SHOW TABLES LIKE '%usuario_curso%';"

sudo cp -r . /opt/lampp/htdocs/CapivaraLearn
sudo rm /opt/lampp/htdocs/CapivaraLearn/logs/php_errors.log 

sudo mkdir -p /opt/lampp/htdocs/CapivaraLearn/logs && sudo chmod 777 /opt/lampp/htdocs/CapivaraLearn/logs && sudo touch /opt/lampp/htdocs/CapivaraLearn/logs/php_errors.log && sudo chmod 666 /opt/lampp/htdocs/CapivaraLearn/logs/php_errors.log


tail /opt/lampp/htdocs/CapivaraLearn/logs/php_errors.log


sudo /opt/lampp/xampp restart



pscp -i /home/carlos/Nextcloud/Documents/ppk/capivaralearn.ppk crud/learning_units_simple.php root@198.23.132.15:/var/www/capivaralearn/crud/learning_units_simple.php

pscp -batch -i /home/carlos/Nextcloud/Documents/ppk/capivaralearn.ppk crud/modules_simple.php root@198.23.132.15:/var/www/capivaralearn/crud/

## Premissas

Veja o arquivo: TechnicalPremises.md

