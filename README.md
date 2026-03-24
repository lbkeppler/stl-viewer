# 🧊 STL Viewer — Sistema Web de Visualização de Arquivos STL

Sistema web completo para visualização de arquivos STL no browser, com
controle de acesso por usuário e senha, gerenciamento de projetos e
upload de modelos 3D. Sem necessidade de instalar nenhum software adicional.

![PHP](https://img.shields.io/badge/PHP-8.0+-777BB4?style=flat-square&logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-5.7+-4479A1?style=flat-square&logo=mysql&logoColor=white)
![Three.js](https://img.shields.io/badge/Three.js-r128-black?style=flat-square&logo=three.js&logoColor=white)
![License](https://img.shields.io/badge/Licença-MIT-green?style=flat-square)

---

## 📋 Índice

- [Funcionalidades](#-funcionalidades)
- [Stack Tecnológica](#-stack-tecnológica)
- [Requisitos](#-requisitos)
- [Instalação](#-instalação)
- [Estrutura do Projeto](#-estrutura-do-projeto)
- [Uso](#-uso)
- [Visualizador 3D](#-visualizador-3d)
- [Segurança](#-segurança)
- [Solução de Problemas](#-solução-de-problemas)
- [Licença](#-licença)

---

## ✨ Funcionalidades

### Administrador
- Login seguro com senha criptografada (bcrypt)
- Dashboard com estatísticas gerais do sistema
- Criação, edição e remoção de usuários
- Criação, edição e remoção de projetos
- Upload de arquivos STL por projeto (até 100MB)
- Upload via drag & drop ou seleção de arquivo
- Controle granular de permissões: define quais usuários
  acessam quais projetos
- Visualização prévia de qualquer arquivo STL cadastrado

### Usuário Final
- Login seguro
- Visualização apenas dos projetos liberados pelo administrador
- Seleção do arquivo STL desejado dentro do projeto
- Visualização completa do modelo 3D no browser,
  sem instalação de software

### Visualizador 3D
- Renderização com Three.js (WebGL)
- Modos: Sólido, Wireframe e Sólido + Wireframe
- Iluminação profissional com 4 fontes de luz
- Rotação, pan e zoom com o mouse
- Reset de câmera automático
- Grid e eixos XYZ toggleáveis
- Personalização de cor do modelo e do fundo
- Painel de informações: triângulos, dimensões e tamanho
- Suporte a tela cheia
- Atalhos de teclado
- Suporte a arquivos grandes via Range Request

---

## 🛠 Stack Tecnológica

| Camada       | Tecnologia                          |
|--------------|-------------------------------------|
| Backend      | PHP 8.0+ (puro, sem frameworks)     |
| Banco        | MySQL 5.7+ / MariaDB 10.3+          |
| Frontend     | HTML5, CSS3, JavaScript ES6+        |
| Renderização | Three.js r128 (WebGL)               |
| Servidor     | Apache 2.4+ com mod_rewrite         |
| Segurança    | PDO, bcrypt, sessões PHP, .htaccess |

---

## 📦 Requisitos

- PHP 8.0 ou superior
- Extensões PHP: `pdo`, `pdo_mysql`, `fileinfo`
- MySQL 5.7+ ou MariaDB 10.3+
- Apache com `mod_rewrite` habilitado
- HTTPS recomendado em produção
- Navegador moderno com suporte a WebGL
  (Chrome 90+, Firefox 88+, Edge 90+, Safari 14+)

---

## 🚀 Instalação

### 1. Clone ou faça upload dos arquivos

```bash
git clone https://github.com/lbkeppler/stl-viewer.git
```

Ou faça upload de todos os arquivos para o seu servidor via FTP/SFTP.

### 2. Crie o banco de dados

Acesse o phpMyAdmin ou execute via terminal:

```bash
mysql -u root -p < sql/install.sql
```

Isso criará o banco `stl_viewer` com todas as tabelas e o
usuário administrador padrão.

### 3. Configure o sistema

Edite o arquivo `config/database.php`:

```php
define('DB_HOST', 'localhost');              // host do banco
define('DB_NAME', 'stl_viewer');             // nome do banco
define('DB_USER', 'seu_usuario');            // usuário do banco
define('DB_PASS', 'sua_senha');              // senha do banco

define('BASE_URL', 'https://seudominio.com'); // sem barra no final!
define('UPLOAD_DIR', __DIR__ . '/../uploads/stl/');
define('MAX_FILE_SIZE', 100 * 1024 * 1024);  // 100MB
```

> ⚠️ **Atenção:** O `BASE_URL` não deve ter barra `/` no final.

### 4. Permissões de pasta

```bash
chmod 755 uploads/stl/
```

### 5. Primeiro acesso

Acesse o sistema pelo browser:

```
https://seudominio.com
```

Credenciais padrão:

```
E-mail: admin@sistema.com
Senha:  Admin@123
```

> 🔐 **Troque a senha imediatamente após o primeiro login.**

---

## 📁 Estrutura do Projeto

```
stl-viewer/
├── index.php                  # Página de login
├── dashboard.php              # Painel do usuário final
├── viewer.php                 # Visualizador STL
├── logout.php                 # Encerra sessão
├── .htaccess                  # Segurança e configurações Apache
│
├── config/
│   └── database.php           # Configuração do banco e constantes
│
├── includes/
│   ├── auth.php               # Autenticação e controle de sessão
│   ├── functions.php          # Funções CRUD gerais
│   ├── header.php             # Navbar global
│   └── footer.php             # Footer global
│
├── admin/
│   ├── index.php              # Dashboard administrativo
│   ├── users.php              # Gerenciar usuários
│   ├── projects.php           # Gerenciar projetos + upload + permissões
│   └── upload.php             # Endpoint upload AJAX alternativo
│
├── api/
│   ├── get_files.php          # Endpoint: lista arquivos de um projeto
│   └── serve_stl.php          # Endpoint: serve arquivo STL com autenticação
│
├── assets/
│   ├── css/style.css          # Estilos globais do sistema
│   └── js/viewer.js           # Utilitários do visualizador
│
├── uploads/
│   └── stl/
│       └── .htaccess          # Bloqueia execução de scripts nos uploads
│
└── sql/
    └── install.sql            # Script de instalação do banco de dados
```

---

## 📖 Uso

### Fluxo do Administrador

1. Faça login com as credenciais de administrador
2. Acesse **Usuários** e crie os usuários do sistema
3. Acesse **Projetos** e crie um novo projeto
4. Dentro do projeto clique em **Gerenciar**:
   - Faça upload dos arquivos `.STL` do projeto
   - Na seção **Permissões**, conceda acesso aos usuários desejados
5. O usuário já pode logar e visualizar o projeto

### Fluxo do Usuário Final

1. Faça login com suas credenciais
2. Na tela inicial aparecerão todos os projetos liberados para você
3. Clique em **Abrir Projeto**
4. Selecione o arquivo STL desejado
5. Clique em **Visualizar** — o modelo abre no browser

---

## 🎮 Visualizador 3D

### Controles do Mouse

| Ação            | Controle                        |
|-----------------|---------------------------------|
| Rotacionar      | Botão esquerdo + arrastar       |
| Mover (pan)     | Botão direito + arrastar        |
| Zoom            | Scroll do mouse                 |

### Atalhos de Teclado

| Tecla | Ação                  |
|-------|-----------------------|
| `R`   | Reset da câmera       |
| `W`   | Modo wireframe        |
| `S`   | Modo sólido           |
| `B`   | Modo sólido+wireframe |
| `G`   | Mostrar/ocultar grid  |
| `A`   | Mostrar/ocultar eixos |
| `F`   | Tela cheia            |

### Painel de Informações

O painel no canto inferior esquerdo exibe em tempo real:
- Nome do arquivo
- Tamanho em disco
- Número de triângulos da malha
- Dimensões nos eixos X, Y e Z em milímetros

---

## 🔒 Segurança

- Senhas armazenadas com **bcrypt** (cost 12)
- Proteção contra **session fixation** (regenerate_id no login)
- Todas as queries usam **PDO com prepared statements**
- Arquivos STL servidos via endpoint PHP autenticado,
  nunca expostos diretamente
- Pasta `uploads/stl/` com execução de scripts bloqueada
- Pastas `config/` e `includes/` bloqueadas via `.htaccess`
- Headers de segurança: `X-Content-Type-Options`,
  `X-Frame-Options`, `X-XSS-Protection`
- Listagem de diretórios desabilitada (`Options -Indexes`)
- Validação de extensão e tamanho no upload
- Verificação de permissão em todas as rotas protegidas

---

## 🔧 Solução de Problemas

### Login não funciona após instalação
Verifique se o `BASE_URL` em `config/database.php` **não possui
barra no final**:
```php
// ✕ Errado
define('BASE_URL', 'https://seudominio.com/');

// ✓ Correto
define('BASE_URL', 'https://seudominio.com');
```

### Senha do admin não funciona
Crie o arquivo `fix_admin.php` temporariamente na raiz:
```php
<?php
require_once __DIR__ . '/config/database.php';
$hash = password_hash('Admin@123', PASSWORD_BCRYPT, ['cost' => 12]);
$db   = getDB();
$db->prepare("UPDATE users SET password = ? WHERE email = 'admin@sistema.com'")
   ->execute([$hash]);
echo "Senha atualizada!";
```
Acesse pelo browser e **apague o arquivo em seguida**.

### Arquivo STL não carrega no visualizador
- Verifique se o arquivo não está corrompido abrindo em
  outro software (ex: MeshLab, PrusaSlicer)
- Confirme que o tamanho não excede 100MB
- Verifique se a pasta `uploads/stl/` tem permissão de escrita
- Cheque se o PHP tem as configurações de upload corretas

### Erro de conexão com banco na Hostinger
O host do banco na Hostinger raramente é `localhost`. Acesse
**hpanel.hostinger.com → Hospedagem → Bancos de Dados → MySQL**
e copie o host exato exibido no painel.

### Upload falha silenciosamente
Adicione ao `php.ini` ou `.htaccess`:
```apache
php_value upload_max_filesize 100M
php_value post_max_size 105M
php_value max_execution_time 120
php_value memory_limit 256M
```

---

## 📄 Licença

Este projeto está licenciado sob a [MIT License](LICENSE).

```
MIT License

Copyright (c) 2024

Permission is hereby granted, free of charge, to any person obtaining
a copy of this software and associated documentation files (the
"Software"), to deal in the Software without restriction, including
without limitation the rights to use, copy, modify, merge, publish,
distribute, sublicense, and/or sell copies of the Software, and to
permit persons to whom the Software is furnished to do so, subject to
the following conditions:

The above copyright notice and this permission notice shall be included
in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE
LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION
OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION
WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
```

---

<p align="center">
  Desenvolvido com ☕ e Three.js
</p>
