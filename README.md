# Super Personal

SaaS de gestão de treinamentos para **treinadores, coaches e assessorias esportivas**: planos semanais, acompanhamento dos alunos, cobranças (Pix no app) e repasses — no lugar de planilhas e transferências manuais.

<p>
  <img src="https://img.shields.io/badge/PHP-8.3-777BB4?style=flat-square&logo=php&logoColor=white" alt="PHP 8.3">
  <img src="https://img.shields.io/badge/Laravel-10-FF2D20?style=flat-square&logo=laravel&logoColor=white" alt="Laravel 10">
  <img src="https://img.shields.io/badge/Livewire-3-FB70A9?style=flat-square&logo=livewire&logoColor=white" alt="Livewire 3">
  <img src="https://img.shields.io/badge/TailwindCSS-3-38B2AC?style=flat-square&logo=tailwind-css&logoColor=white" alt="TailwindCSS 3">
  <img src="https://img.shields.io/badge/MariaDB-10-003545?style=flat-square&logo=mariadb&logoColor=white" alt="MariaDB">
  <img src="https://img.shields.io/badge/Flutter-app%20do%20aluno-02569B?style=flat-square&logo=flutter&logoColor=white" alt="Flutter">
</p>

---

## Visão geral

O produto tem três frentes:

| Frente | Quem usa | O que faz |
|---|---|---|
| **Painel do treinador** | `teacher` (dono do tenant) | Alunos, planos de treino, agenda, acompanhamento, cobranças, financeiro, relatórios, landing pública |
| **Painel da plataforma** | `admin` | Treinadores, modalidades, exercícios globais, configurações, comissões e repasses |
| **App do aluno** (`mobile/`) | `student` | Treinos do dia, execução, calendário, avaliações, pagamentos Pix e notificações |

**Multi-tenancy:** banco compartilhado com `teacher_id` em todo dado de domínio (tenant = treinador). O isolamento tem três camadas: coluna + global scope (`App\Traits\BelongsToTeacher`) + Policies. O `teacher_id` é sempre definido pelos Services — nunca vem do request.

**Modelo de receita:** sem mensalidade. A plataforma retém uma comissão configurável (`config.fee_percent`, padrão **5%**) apenas nas **cobranças online** pagas pelo aluno (Pix); o líquido é repassado ao treinador via saques (`payouts`). Cobranças **manuais** não têm taxa e são limitadas a **10 por mês** por treinador.

### Principais recursos

- **Treinos** — planos com semanas, sessões e composição (exercícios, séries, ritmos); duplicação de plano para outro aluno e de semana no fim do plano; agenda semanal.
- **Acompanhamento** — execuções, avaliações físicas, aderência ao plano, observações e mensagens ao aluno.
- **Cobranças** — manual ou online; Pix gerado no app do aluno com confirmação automática por webhook.
- **Financeiro** — saldo disponível × a liberar (prazo configurável), extrato, formas de recebimento (Pix/banco) e repasses.
- **Relatórios** — financeiro por período com exportação CSV; auditoria das notificações enviadas.
- **Landing pública** — `/treinador/{slug}` com opt-in, identidade visual, redes sociais, endereço e métrica de acessos.
- **Notificações** — mensagens do treinador e lembretes automáticos (cobrança a vencer/em atraso, treino do dia/não realizado), lidos no app.

---

## Stack

| Camada | Tecnologia |
|---|---|
| Backend | PHP 8.3, Laravel 10 |
| Painel | Livewire 3, Blade, TailwindCSS 3, Alpine.js (via Livewire), Vite |
| Banco | MySQL/MariaDB (Sail: host `mariadb`, base `superpersonal`) |
| API | REST versionada em `/api/v1` + Laravel Sanctum (token pessoal) |
| Papéis | coluna `users.role` (`admin`, `teacher`, `student`) + middleware `role` |
| Pagamentos | PagBank (Pix das cobranças online do aluno) |
| PDF | `barryvdh/laravel-dompdf` |
| Mídia | disco padrão: `public` (local) ou `s3` (Cloudflare R2) |
| App | Flutter (`mobile/`), dio + Riverpod + GoRouter |
| Ambiente | Laravel Sail (Docker): app, MariaDB, phpMyAdmin, Mailpit, Memcached |

---

## Requisitos

- Docker + Docker Compose
- Composer
- Node.js 20+ e npm
- (Opcional, para o app) Flutter 3.x + JDK 21

> Os comandos abaixo usam `vendor/bin/sail` porque o alias `sail` não está no PATH.

---

## Instalação (ambiente local)

```bash
git clone git@github.com:informaticalivreoficial/super-personal.git
cd super-personal

composer install
cp .env.example .env
```

Ajuste o `.env` para o Sail (o `.env.example` é genérico):

```env
APP_NAME="Super Personal"
APP_URL=http://laravel.test

DB_CONNECTION=mysql
DB_HOST=mariadb
DB_PORT=3306
DB_DATABASE=superpersonal
DB_USERNAME=sail
DB_PASSWORD=password

MAIL_HOST=mailpit
MAIL_PORT=1025

FILESYSTEM_DISK=public
```

```bash
vendor/bin/sail up -d
vendor/bin/sail artisan key:generate
vendor/bin/sail artisan migrate --seed
vendor/bin/sail npm install
vendor/bin/sail npm run build
vendor/bin/sail artisan storage:link   # apenas com FILESYSTEM_DISK=public
```

Acesse:

| Serviço | URL |
|---|---|
| Aplicação | <http://laravel.test> |
| phpMyAdmin | <http://localhost:8080> |
| Mailpit (e-mails) | <http://localhost:8025> |

> Sem `public/build` as views falham com "Vite manifest not found" — rode `npm run build` (ou `npm run dev`).

### Credenciais do seed

| Perfil | E-mail | Senha |
|---|---|---|
| Admin da plataforma | `admin@superpersonal.test` | `password` |
| Treinador | `joao@superpersonal.test` | `password` |
| Aluno | `marcos@superpersonal.test` | `password` |

O admin pode ser sobrescrito por `ADMIN_NOME`, `ADMIN_EMAIL` e `ADMIN_PASS` no `.env`.

---

## Comandos úteis

```bash
vendor/bin/sail up -d                  # sobe os containers
vendor/bin/sail artisan migrate        # migrations
vendor/bin/sail artisan migrate:fresh --seed
vendor/bin/sail artisan test           # suíte PHPUnit
vendor/bin/pint                        # formata o código (PHP)
vendor/bin/pint --test                 # verifica formatação
vendor/bin/sail npm run build          # assets de produção
vendor/bin/sail artisan optimize:clear # limpa caches (config/route/view)
vendor/bin/sail artisan schedule:list  # agendamentos (lembretes diários 07:00)
```

### Qualidade

- PHP: `vendor/bin/pint --test` + `vendor/bin/sail artisan test` — **480 testes / 1.849 assertions**.
- Front: `vendor/bin/sail npm run build` quando houver mudança em JS/CSS/views com Alpine.
- App: `cd mobile && dart analyze && flutter test`.

Testes rodam em SQLite `:memory:` (`phpunit.xml`) e fixam `FILESYSTEM_DISK=public`, então nunca acessam o R2.

---

## Estrutura do repositório

```
app/
├── Enums/                 # enums de domínio com labels() em pt-BR
├── Http/
│   ├── Controllers/Api/   # API REST (/api/v1)
│   ├── Middleware/        # role, profile.complete
│   ├── Requests/          # validação (API e painel)
│   └── Resources/         # serialização da API
├── Livewire/Dashboard/    # painel do treinador e da plataforma
├── Models/                # domínio multi-tenant
├── Policies/              # autorização (auto-discovery)
├── Services/              # regras de negócio / escrita centralizada
└── Traits/                # BelongsToTeacher, WithToastr, ...

database/{migrations,seeders,factories}
docs/{architecture.md,api.md}
mobile/                    # app Flutter do aluno
resources/{css,js,views}   # painel, site público e landing do treinador
routes/{web.php,api.php}
tests/Feature/{Api,Panel,Web,Services,Console,Notifications,Storage}
```

---

## API REST

Base: `/api/v1`. Autenticação por **Bearer token** (Sanctum); o aluno entra com o **código de convite** gerado na ficha (`POST /auth/student-register`) e depois com e-mail/senha.

- Referência completa de endpoints: [`docs/api.md`](docs/api.md)
- Arquitetura, multi-tenancy e decisões: [`docs/architecture.md`](docs/architecture.md)

---

## App do aluno (Flutter)

```bash
cd mobile
flutter pub get
flutter run --dart-define=API_BASE_URL=http://192.168.0.10   # IP da máquina na LAN
```

Para gerar o APK:

```bash
flutter build apk --debug --dart-define=API_BASE_URL=http://192.168.0.10
```

> Emulador Android usa `http://10.0.2.2`. Se o build reclamar do Java, exporte o JDK 21 (`JAVA_HOME`) antes de rodar. Mais detalhes em [`mobile/README.md`](mobile/README.md).

---

## Armazenamento de mídia

Todo o código usa o **disco padrão** (`Storage::disk()`); a troca é feita por variável de ambiente:

```env
FILESYSTEM_DISK=public   # storage/app/public  (+ php artisan storage:link)
FILESYSTEM_DISK=s3       # Cloudflare R2 (R2_* no .env)
```

Com R2, preencha `R2_ACCOUNT_ID`, `R2_ACCESS_KEY_ID`, `R2_SECRET_ACCESS_KEY`, `R2_BUCKET`, `R2_URL`, `R2_ROOT` (`superpersonal`), `R2_REGION` (`auto`) e mantenha o bucket com acesso público. Os caminhos salvos no banco não mudam ao trocar de disco.

---

## Deploy

Push na `main` dispara o workflow [`.github/workflows/deploy.yml`](.github/workflows/deploy.yml), que acessa o servidor (aaPanel) e executa: `git pull`, `composer install`, `npm install && npm run build`, permissões e limpeza de caches.

O `.env` **não é versionado** — credenciais de banco, R2, PagBank e e-mail são configuradas diretamente no servidor. Após alterar o `.env`, rode `php artisan optimize:clear`.

---

## Licença

Projeto proprietário. Todos os direitos reservados.
