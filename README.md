<p align="center">
  <img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="320" alt="Laravel Logo">
</p>

<p align="center">
  <img src="https://img.shields.io/badge/Laravel-10.x-FF2D20?style=for-the-badge&logo=laravel&logoColor=white" alt="Laravel">
  <img src="https://img.shields.io/badge/Livewire-3.x-FB70A9?style=for-the-badge&logo=livewire&logoColor=white" alt="Livewire">
  <img src="https://img.shields.io/badge/Alpine.js-3.x-77C1D2?style=for-the-badge&logo=alpine.js&logoColor=white" alt="Alpine.js">
  <img src="https://img.shields.io/badge/TailwindCSS-3.x-38B2AC?style=for-the-badge&logo=tailwind-css&logoColor=white" alt="TailwindCSS">
  <img src="https://img.shields.io/badge/MySQL-8.x-4479A1?style=for-the-badge&logo=mysql&logoColor=white" alt="MySQL">
  <img src="https://img.shields.io/badge/PHP-8.1+-777BB4?style=for-the-badge&logo=php&logoColor=white" alt="PHP">
</p>

---

# 🚀 Laravel StarterKit — pt_BR

StarterKit completo em **português brasileiro** com as principais ferramentas já configuradas e prontas para uso. Ideal para iniciar projetos administrativos com autenticação, controle de acesso por roles e CRUD reativo.

---

## ✨ Funcionalidades

- 🔐 **Autenticação** completa (login, logout, sessão)
- 👥 **Controle de acesso** com [Spatie Laravel Permission](https://spatie.be/docs/laravel-permission) (roles: `super-admin`, `admin`, `manager`, `employee`)
- 🛡️ **Policies** por role com hierarquia de permissões
- ⚡ **Livewire 3** — componentes reativos sem sair do PHP
- 🏔️ **Alpine.js** — reatividade leve no front
- 🎨 **TailwindCSS 3** — utilitários CSS com classes customizadas
- 🖥️ **AdminLTE 3** — layout admin responsivo já integrado
- 🌎 **pt_BR** — locale, datas, validações e mensagens em português
- 🌱 **Seeders** com usuários e roles pré-configurados
- 🏭 **Factories** para geração de dados fake

---

## 🛠️ Stack

| Tecnologia | Versão |
|---|---|
| PHP | ^8.3 |
| Laravel | ^10.0 |
| Livewire | ^3.0 |
| Alpine.js | ^3.0 |
| TailwindCSS | ^3.2 |
| AdminLTE | ^3.2 |
| Spatie Permission | ^6.0 |
| MySQL | ^8.0 |
| Vite | ^4.0 |

---

## ⚡ Instalação

### Pré-requisitos

- PHP >= 8.3
- Composer
- Node.js >= 18
- MySQL >= 8.0

### Passo a passo

**1. Clone o repositório**

```bash
git clone https://github.com/seu-usuario/laravel-10-init.git
cd laravel-10-init
```

**2. Instale as dependências PHP**

```bash
composer install
```

**3. Configure o ambiente**

```bash
cp .env.example .env
php artisan key:generate
```

Edite o `.env` com suas credenciais:

```env
APP_NAME="Meu Projeto"
APP_URL=http://localhost

DB_DATABASE=laravel_starterkit
DB_USERNAME=root
DB_PASSWORD=

ADMIN_NOME="Super Admin"
ADMIN_EMAIL=admin@app.com
ADMIN_PASS=password
```

**4. Instale as dependências Node**

```bash
npm install
```

**5. Publique os assets**

```bash
php artisan adminlte:install
php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"
```

**6. Execute as migrations e seeders**

```bash
php artisan migrate --seed
```

**7. Compile os assets**

```bash
npm run build
# ou em modo desenvolvimento:
npm run dev
```

**8. Inicie o servidor**

```bash
php artisan serve
```

Acesse: [http://localhost:8000](http://localhost:8000)

---

## 🔑 Credenciais Padrão

| Role | E-mail | Senha |
|---|---|---|
| Super Admin | admin@app.com | password |
| Admin | admin_fake@app.com | password |
| Manager | manager@app.com | password |
| Employee | employee@app.com | password |

> As credenciais do Super Admin são definidas via `.env` (`ADMIN_EMAIL`, `ADMIN_PASS`).

---

## 👥 Roles e Permissões

| Ação | Super Admin | Admin | Manager | Employee |
|---|:---:|:---:|:---:|:---:|
| Ver todos os usuários | ✅ | ✅ | ❌ | ❌ |
| Ver employees | ✅ | ✅ | ✅ | ❌ |
| Criar usuários | ✅ | ✅ | ✅ | ❌ |
| Editar qualquer usuário | ✅ | ✅ | ❌ | ❌ |
| Editar próprio perfil | ✅ | ✅ | ✅ | ✅ |
| Deletar usuários | ✅ | ✅ | ❌ | ❌ |
| Deletar a si mesmo | ❌ | ❌ | ❌ | ❌ |

---

## 📁 Estrutura Relevante

```
app/
├── Http/
│   ├── Controllers/
│   │   └── Web/
│   │       └── SiteController.php
│   │       
│   Livewire/
│   ├── Dashboard/
│   │   ├── Posts/
│       │   └── QuickStats.php
│       └── Reports/
│           └── UserList.php
├── Models/
│   └── User.php
└── Policies/
    └── UserPolicy.php

database/
├── migrations/
└── seeders/
    ├── DatabaseSeeder.php
    └── UsersTableSeeder.php

resources/
├── views/
│   ├── auth/
│   ├── layouts/
│   ├── livewire/
│   └── dashboard.blade.php
├── css/app.css
└── js/app.js
```

---

## 🧩 Componentes Livewire

### `UserList`
CRUD completo de usuários com busca em tempo real, ordenação, paginação, modal criar/editar e confirmação de exclusão.

### `RecentActivities`
Lista os últimos usuários cadastrados no sistema.

### `QuickStats`
Cards reativos com contagem de usuários (total, hoje, mês atual).

---

## 🎨 Classes CSS Utilitárias

```blade
{{-- Cards --}}
<div class="card-sk">
    <div class="card-sk-header">Título</div>
    <div class="card-sk-body">Conteúdo</div>
</div>

{{-- Botões --}}
<button class="btn-primary">Primário</button>
<button class="btn-secondary">Secundário</button>
<button class="btn-danger">Perigo</button>

{{-- Inputs --}}
<label class="form-label-sk">Label</label>
<input class="form-input-sk" type="text">

{{-- Alertas --}}
<div class="alert-success">Sucesso!</div>
<div class="alert-error">Erro!</div>
<div class="alert-warning">Atenção!</div>
<div class="alert-info">Informação</div>
```

---

## 📡 Eventos Livewire

### Toast de notificação

```php
// Em qualquer componente Livewire:
$this->dispatch('notify', type: 'success', message: 'Salvo com sucesso!');
$this->dispatch('notify', type: 'error',   message: 'Algo deu errado.');
$this->dispatch('notify', type: 'warning', message: 'Atenção!');
$this->dispatch('notify', type: 'info',    message: 'Informação.');
```

---

## 🤝 Contribuindo

1. Faça um fork do projeto
2. Crie sua branch (`git checkout -b feature/minha-feature`)
3. Commit suas mudanças (`git commit -m 'feat: minha feature'`)
4. Push para a branch (`git push origin feature/minha-feature`)
5. Abra um Pull Request

---

## 📝 Licença

Este projeto está sob a licença [MIT](LICENSE).

---

<p align="center">Feito com ❤️ e muito ☕</p>
