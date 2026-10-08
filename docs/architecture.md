# Arquitetura — Super Personal (Fase 1)

> Documento técnico da base API-first. Visão de produto e histórico: `AGENTS.md`.
> Referência de endpoints: `docs/api.md`.

## Stack

- PHP 8.3 / Laravel 10 / Laravel Sail
- MariaDB (host `mariadb`, db `superpersonal`)
- Sanctum (tokens pessoais de longa duração), spatie/laravel-permission legado
- Comandos: `vendor/bin/sail artisan ...`, testes: `vendor/bin/phpunit`

## Multi-tenancy (3 camadas)

O **tenant é o professor** (`teachers.id`). Todo dado de domínio carrega `teacher_id`.

1. **Coluna `teacher_id`** em Students, TrainingPlans, TrainingWeeks, TrainingSessions,
   TrainingExecutions, StudentProgress, StudentNotes, Payments, Subscriptions.
2. **Global scope** — trait `App\Traits\BelongsToTeacher` (scope nomeado `tenant`)
   aplicado aos models de domínio. Filtra automaticamente toda query Eloquent quando
   há usuário autenticado; seeders/console rodam sem filtro.
3. **Policies** — `App\Policies\*` com `before()` que dá bypass para admin de plataforma
   e valida `teacher_id` / propriedade do aluno para os demais perfis.

Responsabilidade de `teacher_id`: **sempre definida pelos Services**
(`StudentService`, `TrainingPlanService`, `TrainingSessionService`,
`TrainingExecutionService`, `PaymentService`) — nunca vem do request.

### Resolução de tenant (`User::resolveTenantId()`)

| Retorno | Significado |
|---|---|
| `null` | Admin de plataforma (`role = admin`) — sem filtro de tenant |
| `0` | Perfil não encontrado — nada visível |
| `>0` | id do professor — escopo ativo |

`shouldUse('tenant')` só aplica o global scope quando `auth()->check()`.

## Perfis (papel = coluna `users.role`)

Enum `App\Enums\UserRole`: `admin`, `teacher`, `student`.

- **Fonte da verdade da Fase 1** = coluna `role` + middleware `role`
  (`App\Http\Middleware\EnsureUserRole`, alias registrado no Kernel).
- spatie/laravel-permission permanece **intocado** (roles legadas do starter);
  reconciliar/limpar na Fase 2 do painel.
- Guarda de rota: `auth:sanctum` + `role:teacher` / `role:student` em
  `routes/api.php`.

## Estrutura da API

- Prefixo `/api/v1` (`routes/api.php`), controllers em `app/Http/Controllers/Api/V1`
  (`AuthController`, `Student\*`, `Teacher\*`, base `ApiController`).
- **Form Requests** em `app/Http/Requests` (validação sempre via request).
- **Resources** em `app/Http/Resources` (formato único de resposta, wrap `data`).
- **Services** concentram regras de negócio e escrita transacional
  (`app/Services`).
- Unidades canônicas: distância em **metros** (int), duração/rest em **segundos**
  (int), pace/alvos em string.

## Banco de dados

Migrations `database/migrations/2026_10_07_*` (domínio SaaS) após as legadas.

### Diagrama (simplificado)

```
users ──1:1── teachers ──┬──1:N── students ──┬──1:N── training_plans ──1:N── training_weeks ──1:N── training_sessions ──1:N── training_session_items
                         │                   │                                                    └──1:N── training_executions
                         │                   ├──1:N── student_progress
                         │                   ├──1:N── student_notes
                         │                   ├──1:N── payments
                         │                   └──1:1── (user.aluno)
                         ├──1:N── exercises (por teacher)
                         └──1:N── subscriptions
sports ──1:N── training_sessions (restrict)
```

### Regras de FK

| Relacionamento | onDelete |
|---|---|
| weeks→plan, sessions→week, items/executions→session | `cascade` (filhos puros) |
| students→(plans, payments, progress, notes, sessions) | `cascade` |
| students.teacher_id | `restrict` |
| students.user_id | `nullOnDelete` |
| exercises.sport_id, sessions.sport_id | `restrict` |
| sessions.exercise_id, items.exercise_id | `nullOnDelete` |
| teachers.user_id | `cascade` |

### Escolhas de modelagem

- **Sem tabela `student_profiles`** — dados do aluno ficam em `students`
  (evita duplicação com o perfil legado).
- `training_session_items` existe (composição do treino).
- `student_progress` = **histórico imutável** (somente insert; estados atuais
  são os campos atuais de `students`).
- `users`: coluna `role` adicionada com backfill a partir das roles spatie.

## Autenticação

- Sanctum token por dispositivo (`POST /api/v1/auth/login` → `plainTextToken`).
- **Sem refresh token** (token de longa duração; logout revoga).
- Login com RateLimiter manual (6 tentativas/chave, janela 60s) e
  `throttle:10,1` na rota.

## Painel web (Fase 2 — em andamento)

- Rotas `/admin/*` com middleware `['auth', 'role:teacher,admin']`.
  **Student não acessa** o painel (usa o app Android via API); middleware
  `verified` removido (sem `MustVerifyEmail` no MVP).
- **Auto-cadastro** (`/auth/register`) cria conta de professor: `users.role = teacher`
  + perfil `teachers` + redirect `/admin`. Alunos são cadastrados pelo professor.
- Login do painel (`App\Livewire\Auth\Login`) bloqueia: senha inválida,
  usuário inativo (`status != 1`), student, professor inativo (`teachers.active = false`).
- Assets Vite: `vendor/bin/sail npm install && vendor/bin/sail npm run build`
  (sem `public/build` as views 500 com "Vite manifest not found").
- **CRUD de Alunos** — componentes full-page em `app/Livewire/Dashboard/Students/`:
  - `StudentIndex` (busca/paginação/toggle ativo/exclusão lógica), `StudentForm`
    (create+edit), `StudentShow` (dados + plano ativo + pagamentos).
  - Validação reaproveita `StoreStudentRequest`/`UpdateStudentRequest` via trait
    `App\Traits\ValidatesWithFormRequest` (`Request::create` + `Validator`).
  - `teacher_id` setado só pelo `StudentService`; service `abort(403)` se não houver
    tenant (admin da plataforma não cria alunos).
  - **mount não tipa `?Student`** — o container do Livewire instancia model vazio
    e o policy derruba a render com 403; usar `mount($student = null)` + `instanceof`.
  - `UpdateStudentRequest` aceita `student_id` no payload (fallback do model binding
    da rota, que não existe em contexto Livewire).
- **Planos de treino** — `app/Livewire/Dashboard/Plans/`:
  - `PlanIndex` (busca/exclusão), `PlanForm` (create com select de aluno; edit com
    aluno fixo), `PlanShow` (detalhe + **CRUD de semanas** e **CRUD de sessões**).
  - Reaproveita `StoreTrainingPlanRequest`/`UpdateTrainingPlanRequest`,
    `StoreWeekRequest` (fallback `plan_id`/`week_id` no payload) e
    `StoreTrainingSessionRequest`/`UpdateTrainingSessionRequest`.
  - `TrainingPlanService` ganhou `updateWeek`/`deleteWeek`; sessões via
    `TrainingSessionService` (sem `items` no painel — só na API por ora).
  - Erros de Form Request com dados em propriedade-array são **prefixados**
    (`week.week_number`) pelo trait — senão o Livewire filtra e a view não exibe.
  - Aluno de outro tenant no form → `Student::find` com escopo → erro de validação
    (não cria plano no tenant errado).
- **Pagamentos** — `app/Livewire/Dashboard/Payments/`:
  - `PaymentIndex` (busca, filtro por status, "marcar como pago", exclusão),
    `PaymentForm` (create/edit; aluno fixo na edição).
  - Reaproveita `StorePaymentRequest` (store e update, igual à API);
    `PaymentService` preenche/limpa `paid_at` conforme status.
- `AppServiceProvider` compartilha `config` (`Config::first() ?? new Config()`)
  em propriedade de instância (**não static** — static contaminava os testes).
- Navegação: `side-navigation` só com itens do domínio (Painel, Alunos, Planos,
  Pagamentos; Settings só para admin); `top-navigation` sem notificações fake
  nem `users.edit`.

### Visual — painel 100% Tailwind (sem AdminLTE)

- **AdminLTE, jQuery, FontAwesome e o diretório `public/theme/` (12 MB) foram
  removidos.** Imagens do tema migraram para `public/images/` (referências
  `theme/images` → `images` em `app/` e `resources/`).
- Layouts:
  - `components/layouts/app.blade.php` — estado global `Alpine.store('nav')`
    (`mini` persistido em `localStorage['navMini']`, `mobile`), listeners
    globais `swal:*`/`swal:confirm`, integração Quill (`x-data="quillEditor"`),
    `@stack('scripts')`. Alpine vem do bundle do Livewire 3; SweetAlert2 e
    flatpickr entram pelo bundle Vite (`resources/js/app.js`).
  - `components/layouts/guest.blade.php` — auth (login/registro) em card central.
- **Sistema de ícones**: `<x-icon name="..." class="h-4 w-4" />` — componentes
  gerados pelo `scripts/generate-icons.py` a partir do pacote npm `heroicons`
  (83 nomes, inclui aliases FA: `times`→`x-mark`, `search`→`magnifying-glass`...).
  `name` DEVE ser literal (Blade trata `:name` como binding PHP).
- **Camada de compatibilidade** em `resources/css/app.css` (`@layer components`):
  classes herdadas do AdminLTE/Bootstrap continuam válidas (`card`, `btn-*`,
  `form-*`, `table*`, `badge*`, `alert*`, `info-box*`, `row`/`col-*`...), o que
  permitiu migrar as views legadas sem tocar em bindings. Cores de fundo
  legadas (`bg-danger` etc.) NÃO existem — usar utilitários Tailwind.
- Toast global sem CDN: `livewire/components/toastr-notification.blade.php`
  (Alpine, escuta `window` `toast`/`toastr`, normaliza detail array/objeto e
  `session('toast')`); `dispatch('toast')` do PHP já aparece sozinho.
- Confirmação de exclusão: `<x-confirm-delete>` / partial
  `components/confirm-delete.blade.php` (`$deleteId` + `wire:click="delete"`).
- Paginação Livewire no tema `tailwind` (8 componentes).
- Convenções de página: cabeçalho com botão voltar (rota index) + ícone teal +
  subtítulo `Categoria / Ação`; tabelas em card com `overflow-x-auto`;
  `wire:loading` em botões de salvar.
- Views legadas (settings, sitemap, users, posts, reports) migradas para o
  mesmo padrão; scripts `toastr`/jQuery removidos; Chart.js carregado por CDN
  só em `reports/posts`. Órfãos removidos: `roles/*`, `permissions/*`,
  `reports/dashboard-stats`, `Users/Create`.

## Testes

- `phpunit.xml` → SQLite `:memory:`, `RefreshDatabase`.
- Suíte Fase 1 em `tests/Feature/Api`:
  - `AuthTest` — login/logout/401/403 por papel.
  - `TeacherIsolationTest` — professor não vê/edita dados de outro (404/403).
  - `StudentTrainingTest` — aluno vê só os próprios treinos, `complete` cria
    execução vinculada ao aluno correto, execução visível só ao professor dono.
- Suíte do painel em `tests/Feature/Panel`:
  - `PanelAccessTest` — redirecionamento de guest, acesso por papel (teacher/admin
    200, student 403), login por papel (student/inativo bloqueados), registro cria tenant.
  - `StudentCrudTest` — listagem isolada por tenant, criação/edição com Form Requests,
    email duplicado, 404 cross-tenant na rota, AuthorizationException no Livewire,
    exclusão lógica, admin sem CRUD de aluno, smoke HTTP das 4 rotas.
  - `TrainingPlanCrudTest` — isolamento de planos, criação com aluno do tenant,
    semanas duplicadas, sessões (modalidade obrigatória), 404 cross-tenant, smoke.
  - `PaymentCrudTest` — isolamento, `paid_at` automático, marcar como pago,
    404 cross-tenant, smoke das 3 rotas.
  - `PanelRoutesSmokeTest` — as 25 rotas `/admin/*` retornam 200 para teacher
    (cria Config + roles spatie legadas; pegou o 500 de Configurações).
  - **Total: 69 testes / 186 assertions** (23 API + 46 painel).
- Testes legados Pest/Volt do starter foram **removidos** (Pest não instalado,
  páginas Volt inexistentes).

## Fora do escopo atual

- Próximos incrementos da Fase 2: edição de `items` de sessão no painel
  (composição avançada), gestão de exercícios/modalidades,
  reconciliar roles spatie; Pint pendente em ~31 arquivos legados do starter.
- Swagger (docs manuais), refresh token, gateway de pagamento,
  integrações Strava/Garmin.
- App Android (Fase 3).
