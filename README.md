# SIGAEG — Sistema Integrado de Gestão Académica Armando Emílio Guebuza

## Visão Geral

SIGAEG é um sistema web escolar desenvolvido em PHP/MySQL para gerenciar utilizadores, turmas, horários, módulos, avaliações, presenças, ficheiros, mensagens e anúncios. O projeto suporta múltiplos perfis de utilizador:

- Administrador
- Formador
- Formando
- Encarregado de Educação

A aplicação foi concebida para correr em ambiente local com XAMPP e também pode ser preparada para publicação num servidor web.

## Tecnologias

- PHP (servidor)
- MySQL / MariaDB
- HTML, CSS, JavaScript
- jQuery
- mPDF (biblioteca PHP usada via Composer)

## Estrutura do Projeto

- `index.php` — página de login / registo e ponto de entrada
- `config/` — configuração de base e conexão com a base de dados
- `api/` — endpoints backend para ações de CRUD, autenticação e preferências
- `includes/` — componentes partilhados, funções utilitárias, sidebar e menu
- `assets/` — CSS, JavaScript, fontes e imagens
- `pages/` — páginas de interface por perfil de utilizador
- `logs/` — registo de erros e ações do sistema
- `iicaeg_db.sql` / `iicaeg_db1.sql` — dumps de base de dados
- `.htaccess` — definições de documentos de erro para o Apache

## Funcionalidades Principais

- Autenticação com login e registo via código de convite
- Perfis de acesso baseados em níveis (1: Admin, 2: Formador, 3: Formando, 4: Encarregado)
- Dashboard e navegação dinâmica para cada tipo de utilizador
- Gestão de formandos, formadores, turmas, módulos e horários
- Criação e gestão de avaliações, pautas e resultados
- Registo de presenças e trabalhos académicos
- Upload/download de ficheiros e mensagens internas
- Preferências de tema e estado da sidebar guardadas em cookie/session
- Notificações e sistema de logs

## Requisitos

- PHP 8.x ou superior
- MySQL / MariaDB
- Apache com mod_rewrite (ou equivalente)
- Composer

## Instalação Local

1. Copie a pasta do projeto para a pasta do servidor local, por exemplo `htdocs/iicaeg_sistema`.
2. Importe a base de dados usando `iicaeg_db.sql` (ou `iicaeg_db1.sql` se aplicável).
3. Execute `composer install` na raiz do projeto para instalar a dependência `mpdf/mpdf`.
4. Atualize as configurações:
   - `config/config.php` — defina `BASE_URL` para a URL local ou de produção.
   - `config/db.php` — atualize `host`, `user`, `pass` e `db` para a sua base de dados.
5. Se usar Apache, verifique o ficheiro `.htaccess` e atualize os caminhos de erro se o site não estiver em `/iicaeg_sistema/`.

## Configuração Importante

### `config/config.php`

Defina `BASE_URL` com a URL correta do projeto.

Exemplo local:

```php
define('BASE_URL', 'http://localhost/iicaeg_sistema/');
```

Exemplo de produção num subdiretório:

```php
define('BASE_URL', 'https://seu-dominio.com/iicaeg_sistema/');
```

Se publicar na raiz do domínio, use:

```php
define('BASE_URL', 'https://seu-dominio.com/');
```

### `config/db.php`

Atualize as credenciais da base de dados:

```php
$host = 'localhost';
$user = 'root';
$pass = '';
$db = 'iicaeg_db';
```

### `.htaccess`

O ficheiro `.htaccess` contém regras de documentos de erro com caminhos absolutos:

```text
ErrorDocument 404 /iicaeg_sistema/pages/404.php
ErrorDocument 403 /iicaeg_sistema/pages/404.php
ErrorDocument 500 /iicaeg_sistema/pages/404.php
```

Se o site estiver instalado fora de `/iicaeg_sistema/`, atualize estes caminhos para o novo caminho ou para a raiz.

## Uso

1. Aceda a `index.php` no browser.
2. Faça login com as credenciais existentes ou use o registo com um código de convite.
3. O sistema redireciona automaticamente para o dashboard correto em função do nível de acesso.

## Credenciais de Teste

## Notas de Desenvolvimento

- A página usa cookies para guardar o tema (`iicaeg_tema`) e estado da sidebar (`iicaeg_sidebar`).
- A lógica de login com "lembrar de mim" grava um token em `usuarios.remember_token`.
- O registo (`api/entrar_cadastrar.php`) exige que o email esteja pré-cadastrado em `codigos_autorizados` e nos perfis de utilizador.
- A sidebar é renderizada em `includes/sidebar.php` e o menu em `includes/menu.php`.

## Como Contribuir

1. Faça um fork do projeto.
2. Crie uma branch com a sua melhoria: `git checkout -b feature/nome-da-funcionalidade`.
3. Faça commit das alterações.
4. Envie um pull request.
