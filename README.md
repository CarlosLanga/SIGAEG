<img width="1672" height="941" alt="sigaeg-brandlogo" src="https://github.com/user-attachments/assets/03a88b0f-d2be-4a6c-a201-88f8b84b7c78" />

# SIGAEG – Sistema Integrado de Gestão Académica Armando Emílio Guebuza

## Sobre o Projecto

O **SIGAEG (Sistema Integrado de Gestão Académica Armando Emílio Guebuza)** é uma aplicação web desenvolvida para apoiar a gestão académica de instituições de ensino técnico-profissional moçambicanas, permitindo centralizar e automatizar diversos processos administrativos e pedagógicos numa única plataforma. A plataforma foi desenvolvida em homenagem ao Instituto Industrial e de Computação Armando Emílio Guebuza, localizado em Boane, Maputo, Moçambique - especializado na formação de técnicos médios em diversas áreas do mercado.

O sistema foi concebido com o objectivo de proporcionar uma gestão académica mais eficiente, reduzindo tarefas manuais e facilitando o acesso às informações por parte dos diferentes intervenientes da comunidade escolar. Através de uma interface intuitiva e organizada, o SIGAEG permite que administradores, formadores, formandos e encarregados de educação interajam com o sistema de acordo com as suas permissões.

O projecto foi desenvolvido utilizando tecnologias web modernas e encontra-se disponível online para demonstração.

---

# Demonstração

A aplicação pode ser acedida através do seguinte endereço:

**🔗 Link:** *https://sigaeg.gt.tc*

---

# Objectivos

O SIGAEG foi desenvolvido com os seguintes objectivos:

* Digitalizar os processos de gestão académica.
* Centralizar informações administrativas e pedagógicas numa única plataforma.
* Melhorar a comunicação entre administradores, formadores, formandos e encarregados de educação.
* Facilitar o acompanhamento do desempenho académico dos formandos.
* Reduzir o uso de processos manuais na administração escolar.
* Disponibilizar um ambiente moderno, organizado e de fácil utilização.

---

# Funcionalidades

O sistema disponibiliza um conjunto de funcionalidades destinadas à gestão completa da instituição de ensino.

## Gestão de Utilizadores

* Registo e autenticação de utilizadores.
* Gestão de diferentes perfis de acesso.
* Controlo de permissões conforme o tipo de utilizador.
* Recuperação e atualização de informações pessoais.

## Gestão Académica

O sistema permite administrar os principais elementos da estrutura académica, incluindo:

* Formandos
* Formadores
* Turmas
* Módulos
* Horários
* Avaliações
* Trabalhos académicos
* Presenças

Cada recurso foi desenvolvido para simplificar o acompanhamento das actividades lectivas e facilitar o trabalho administrativo.

## Comunicação

Para melhorar a interação entre os utilizadores, o sistema disponibiliza:

* Publicação de anúncios.
* Sistema de notificações.
* Sistema de divulgação de ficheiros internos.

## Gestão de Ficheiros

Permite armazenar e disponibilizar documentos importantes através de funcionalidades de upload e download de ficheiros.

## Dashboard

Cada utilizador possui um painel personalizado, onde pode consultar rapidamente as informações mais relevantes de acordo com o seu perfil.

---

# Perfis de Utilizador

O SIGAEG implementa um sistema de controlo de acesso baseado em perfis, garantindo que cada utilizador visualize apenas as funcionalidades correspondentes às suas responsabilidades.

## Administrador

Possui acesso completo ao sistema, podendo gerir utilizadores, turmas, módulos, horários, avaliações, presenças, anúncios, mensagens e demais configurações da plataforma.

## Formador

Responsável pela gestão das actividades pedagógicas, incluindo avaliações, presenças, trabalhos e acompanhamento dos formandos.

## Formando

Pode consultar horários, avaliações, presenças, trabalhos, anúncios, notificações e outras informações académicas.

## Encarregado de Educação

Tem acesso às informações académicas do formando sob sua responsabilidade, permitindo acompanhar o desempenho escolar de forma contínua.

---

# Tecnologias Utilizadas

## Backend

* PHP
* MySQL

## Frontend

* HTML5
* CSS3
* JavaScript
* jQuery

## Bibliotecas

* mPDF
* Font Awesome
* html5canvas
* Chart.js

## Ferramentas

* XAMPP
* Composer
* Git
* GitHub

---

# Estrutura do Projeto

```text
SIGAEG/
│
├── api/                Endpoints da aplicação
├── assets/             CSS, JavaScript, imagens e fontes
├── config/             Configurações do sistema
├── includes/           Componentes reutilizáveis
├── logs/               Registos do sistema
├── pages/              Interfaces da aplicação
├── uploads/            Ficheiros enviados pelos utilizadores
├── vendor/             Dependências instaladas pelo Composer
│
├── index.php           Página inicial
├── composer.json
└── README.md
```

A estrutura foi organizada de forma modular para facilitar a manutenção, reutilização de componentes e evolução futura da aplicação.

---

# Requisitos

Para executar o projecto localmente são necessários:

* PHP 8 ou superior;
* MySQL ou MariaDB;
* Apache;
* Composer.

---

# Instalação

## 1. Clonar o repositório

```bash
git clone https://github.com/SEU-USUARIO/SIGAEG.git
```

## 2. Aceder ao diretório

```bash
cd SIGAEG
```

## 3. Instalar as dependências

```bash
composer install
```

## 4. Importar a base de dados

Importe o ficheiro SQL disponibilizado no projecto utilizando o phpMyAdmin ou outra ferramenta compatível.

## 5. Configurar a ligação à base de dados

Actualize as credenciais no ficheiro correspondente à configuração da base de dados.

## 6. Executar o projecto

Coloque o projecto no diretório do servidor web (XAMPP, WAMP ou Laragon) e aceda ao sistema através do navegador.

---

# Estado do Projecto

O projecto encontra-se funcional e continua em evolução, podendo receber melhorias, optimizações e novas funcionalidades.

---

# Contribuições

Contribuições são bem-vindas.

Caso pretenda colaborar com o projecto:

1. Faça um Fork do repositório.
2. Crie uma nova branch para a funcionalidade ou correção.
3. Efectue as alterações necessárias.
4. Faça commit das alterações.
5. Envie um Pull Request.

---

# Autor

**Carlos Langa**

Desenvolvedor Web: *[(GitHub)](https://github.com/CarlosLanga)*

---

# Licença

Este projecto foi desenvolvido para fins académicos e educativos.

Caso pretenda reutilizar parte do código, recomenda-se a atribuição dos devidos créditos ao autor.
