# Arquitetura — Super Personal (Fase 1)

> Documento técnico da base API-first. Visão de produto e histórico: `AGENTS.md`.
> Referência de endpoints: `docs/api.md`.

## Stack

- PHP 8.3 / Laravel 10 / Laravel Sail
- MariaDB (host `mariadb`, db `superpersonal`)
- Sanctum (tokens pessoais de longa duração); papéis em `users.role`
  (`spatie/laravel-permission` **removido** — ver "Perfis")
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

- **Fonte da verdade** = coluna `role` + middleware `role`
  (`App\Http\Middleware\EnsureUserRole`, alias registrado no Kernel).
- **`spatie/laravel-permission` foi removido** (2026-10-08): pacote, provider em
  `config/app.php`, `config/permission.php`, migration `create_permission_tables`
  e os helpers legados `User::isSuperAdmin/isAdmin/isManager/isEmployee` +
  trait `HasRoles`. Todas as páginas legadas (usuários/time/posts/settings)
  migraram para `users.role` + `UserPolicy` com `before()` de admin.
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
- **Dashboard** — `/admin` (`App\Livewire\Dashboard\Dashboard`) bifurca por
  perfil: professor vê os KPIs do próprio tenant (`teacherDashboard()`);
  **admin vê a visão da plataforma** (`isPlatformAdmin()` →
  `DashboardService::platformDashboard()`): professores (total/ativos/novos
  no mês), alunos (total/ativos), assinaturas (ativas/trial/em atraso/sem
  assinatura + receita mensal = soma dos planos ativos), treinos hoje,
  pagamentos/assinaturas em atraso e acesso rápido (Professores, Novo
  professor, Modalidades, Configurações). A API `GET /teacher/dashboard`
  continua em `teacherDashboard()` (inalterada).
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
    `TrainingSessionService` (mesmos métodos da API).
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
- Navegação: `side-navigation` com itens do domínio (Painel, Alunos, Planos,
  Pagamentos, **Biblioteca**; Settings só para admin); `top-navigation` sem
  notificações fake nem `users.edit`.
- **Itens de sessão (composição do treino)** — `PlanShow` + componente aninhado
  `app/Livewire/Dashboard/Plans/SessionItems.php` (linha expandida na tabela de
  sessões, `expandedItemsId`/`toggleItems`):
  - CRUD completo (create/edit/delete/move) reaproveitando a API de serviços
    (`TrainingSessionService::storeItem/updateItem/deleteItem/moveItem`) e a
    Form Request única `StoreSessionItemRequest` (padrão `StorePaymentRequest`).
  - Ordenação: `sort_order` normalizado 1..N via `renumberItems` (método `move`).
  - Bug corrigido: default de `training_session_items.type` era `'main'`
    (inexistente no enum) — migration `2026_10_08_100001` troca o default para
    `'work'` (SQL bruto com guard de driver, sem doctrine/dbal); o app grava
    `type` sempre explicitamente (`SessionItemType::WORK`).
  - Escopo de `exercise_id` em todas as Form Requests de sessão/itens:
    global (`teacher_id` null) ou do tenant, `deleted_at` null.
- **Biblioteca (modalidades + exercícios)** — menu "Biblioteca":
  - **Modalidades (`sports`)** — catálogo **global** (compartilhado por todos
    os tenants). Mutações **exclusivas do admin** de plataforma:
    `SportPolicy::before()` + rotas `sports.*` com `role:admin`;
    `SportService::delete` lança `ValidationException` se houver exercises/
    sessions vinculados.
  - **Exercícios (`exercises`)** — professor vê os próprios + globais somente
    leitura; admin vê/edita todos (ownership preservado no update —
    `ExerciseService::store` seta `teacher_id` de `resolveTenantId()`;
    admin → `null` = global). Validação unique por dono
    (`unique` com `whereNull('deleted_at')` e escopo por teacher).
  - Componentes `Sports/{SportIndex,SportForm}` e
    `Exercises/{ExerciseIndex,ExerciseForm}` + Form Requests
    `Store/Update{Sport,Exercise}Request` + `SportService`/`ExerciseService`.
- **Acompanhamento do aluno** — `GET /admin/alunos/{id}/acompanhamento`
  (`students.tracking`) → `Students\StudentTracking` (botão no cabeçalho do
  `StudentShow`):
  - KPIs: execuções concluídas no mês, aderência ao plano ativo
    (concluídas/(total − canceladas)), última execução e nº de avaliações.
  - **Avaliações** (`student_progress`): histórico paginado (paginator
    `avaliacoes`) + formulário "Nova avaliação" validado por
    `StoreProgressRequest`; escrita via `StudentProgressService::store`
    (também usado pela API `Teacher\ProgressController::store` — caminho
    único; `teacher_id`/`student_id` vêm do aluno).
  - **Execuções** (`training_executions`): histórico paginado (paginator
    `execucoes`) com status, duração (segundos) e distância (metros)
    formatados; duração/distância são unidades canônicas da API.
  - Cuidados: `TrainingPlan::sessions()` é `HasManyThrough` (retorno
    corrigido) e as colunas `status` são qualificadas
    (`training_sessions.status`) porque `training_weeks` também tem `status`.
- **Observações e mensagens do aluno** — dois cards aninhados no `StudentShow`
  (`@livewire('dashboard.students.student-*', ['student' => $student], ...)`):
  - `StudentNotes` — CRUD de `student_notes` (listagem paginada `notas`,
    criação/exclusão) com `StoreStudentNoteRequest`, `StudentNoteService`
    (ownership pelo aluno) e `StudentNotePolicy` (before() admin);
    visibilidade `private`/`shared` (`NoteVisibility`).
  - `StudentMessages` — envia `TeacherMessage` (notificação database) para o
    `user` do aluno com `SendMessageRequest` (`title`/`message`); payload da
    `data`: title, message, student_id, teacher_id, sent_by. Lista as últimas
    10 enviadas; aluno sem usuário → card avisa e bloqueia envio. A leitura
    pelo aluno é a API `GET /api/v1/student/notifications` (app Android).
- **Professores (tenants) pela plataforma** — `/admin/professores`
  (grupo `role:admin`, menu "Plataforma" no sidebar):
  - `ProfessorIndex` (busca por nome/e-mail, `students_count`, toggle
    ativo/inativo) e `ProfessorForm` (criar/editar) com `StoreTeacherRequest`
    / `UpdateTeacherRequest` (e-mail único com ignore do próprio user);
  - `TeacherService`: `store` cria `users.role=teacher` + perfil `teachers`
    em transaction (o tenant nasce aqui); `update` (senha opcional);
    `toggleActive` sincroniza `teachers.active` + `users.status` (login
    bloqueado); `ensureProfile` idempotente;
  - `TeacherPolicy` — tudo exclusivo do `isPlatformAdmin()`;
  - **Fix**: a tela legada de usuários (`Users\Form::create/update`) chama
    `ensureProfile` quando o papel é `teacher` — antes o professor criado
    ali ficava sem tenant e o painel dele ficava vazio.
- **Assinaturas do professor (billing manual)** — tabela `subscriptions`
  (Fase 1) ganhou uso no admin:
  - card aninhado `Professores\ProfessorSubscription` no `ProfessorForm`
    (edição): cria/atualiza a assinatura (plano, status
    `SubscriptionStatus`, valor mensal, início/fim do teste/fim);
    validação por `StoreSubscriptionRequest` (reusada nos dois caminhos);
  - `SubscriptionService` registra `cancelled_at` automaticamente ao salvar
    status `cancelled` (rastro manual de billing — sem gateway);
  - coluna "Assinatura" no `ProfessorIndex` com badge por status;
  - autorização herda `TeacherPolicy::update` (exclusivo do admin).

### Notificações e lembretes automáticos

- **Comando** `notifications:send-reminders` (`App\Console\Commands\SendReminders`),
  agendado no `App\Console\Kernel` via `dailyAt('07:00')->withoutOverlapping()`
  (rodando sem auth → global scope `tenant` não filtra: comando de plataforma):
  - `PaymentDueSoon` — pagamento **pendente** com vencimento em até 3 dias
    (mensagem "vence hoje" / "vence em X dia(s)");
  - `PaymentOverdue` — vencido (pendente/overdue) com `due_date` no passado;
  - `TrainingToday` — sessão **planejada** para hoje;
  - `TrainingMissed` — sessão de ontem ainda planejada;
  - payload `data`: `title`/`message` (pt-BR) + ids (`payment_id`/`session_id`,
    `student_id`, `teacher_id`); pula aluno sem conta ou com login bloqueado;
  - **deduplicação**: consulta `notifications.type` +
    `data->payment_id`/`data->session_id` → reexecução segura (cron pode rodar
    mais de uma vez no dia);
  - entrega pelo canal `database` (`BaseNotification::via()`); push Android
    entra no `via()` no futuro. Leitura do aluno: API existente
    `GET /api/v1/student/notifications`.
- **`NewTrainingAvailable` por evento** (gatilho de publicação de plano):
  - `App\Events\TrainingPlanPublished` (payload: plano) + listener
    `App\Listeners\SendNewTrainingAvailableNotification` registrado no
    `EventServiceProvider::$listen`;
  - disparado pelo **`TrainingPlanService`** (caminho único de escrita —
    API e painel compartilham): `store` se o plano já nasce `ACTIVE`;
    `update` **somente na virada não-ativo→ativo** (rascunho→ativo ou
    reativação) — editar um plano já ativo não reavisa;
  - payload `data`: title/message (pt-BR), plan_id, student_id, teacher_id,
    status; aluno sem conta/bloqueado é pulado pelo listener.

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
  - `PanelRoutesSmokeTest` — as 26 rotas do domínio `/admin/*` retornam 200
    para teacher (cria ConfigTableSeeder; pegou o 500 de Configurações);
    fase admin cobre as rotas exclusivas de plataforma (`/admin/usuarios/{id}/editar`,
    `/admin/usuarios/{id}/visualizar`, `/admin/modalidades*`); teacher recebe
    403 em `/admin/modalidades`.
  - `SessionItemCrudTest` (9) — CRUD/move/renumber de itens, validação,
    isolamento por tenant.
  - `SportCrudTest` (11) — CRUD admin-only, 403 para teacher, bloqueio de
    exclusão com vínculos, isolamento global.
  - `ExerciseCrudTest` (11) — professor só edita os próprios, globais somente
    leitura, admin edita todos, unique por dono, 404 cross-tenant.
  - `StudentTrackingTest` (11) — página de acompanhamento (resumo, aderência
    50%, estados vazios), acesso (login/papel/404 cross-tenant), registro de
    avaliação (ownership, data, ranges), AuthorizationException no componente
    cross-tenant, admin preservando `teacher_id` do aluno.
  - `StudentNotesTest` (7) — CRUD de observações na ficha do aluno
    (criação/validação/exclusão, 404 cross-component, cross-tenant, admin).
  - `StudentMessagesTest` (5) — envio de mensagem (payload da notificação),
    validação, card no `StudentShow`, aluno sem usuário, cross-tenant.
  - `ProfessorCrudTest` (11) — gestão de professores/tenants pelo admin
    (rotas admin/403 professor/guest, criação com tenant nascido, validações,
    update com ignore de e-mail + senha opcional, toggle ativo/inativo, acesso
    do novo professor ao painel, idempotência do `ensureProfile`).
  - **Console**: `SendRemindersTest` (11) — lembretes automáticos (janelas de
    vencimento, hoje/amanhã, pagado/concluído não notifica, aluno sem conta
    pulado, idempotência com 2 execuções).
  - **Notificações**: `NewTrainingAvailableTest` (8) — plano publicado por
    evento (criar ativo, publicar rascunho, editar sem reaviso, reativar
    avisa de novo, aluno sem conta, fim a fim via API e painel).
  - `SubscriptionManagementTest` (7) — assinaturas do professor no admin
    (badge no index, card na edição, criação/atualização, validação,
    `cancelled_at` automático, 403 para professor).
  - `PlatformDashboardTest` (3) — dashboard da plataforma do admin (KPIs,
    receita, agregação cross-tenant via service, professor vê o próprio).
  - **Total: 165 testes / 494 assertions** (23 API + 123 painel + 11 console
    + 8 notificações).
- Testes legados Pest/Volt do starter foram **removidos** (Pest não instalado,
  páginas Volt inexistentes).
- **Pint: 100% limpo** (`vendor/bin/pint --test` passa) — o legado do starter
  (~37 arquivos) foi formatado em 2026-10-08.

## Limpeza de legado (2026-10-08)

- **`spatie/laravel-permission` removido** (`composer remove` + config/provider/
  migration + `HasRoles` do `User`); páginas de usuários des-spatiadas:
  `Users::render` filtra `role = teacher` ("clientes"), `Time::render` filtra
  `role = admin`, `Form` grada `users.role` direto (radios teacher/admin),
  `ViewUser`/`Users` ganharam `Gate::authorize` que faltava, `Posts`/`PostForm`
  filtram autores por papel, `settings.blade.php` usa `isPlatformAdmin()`.
  `UsersTableSeeder` reescrito sem spatie.
- **Órfãos removidos**: `app/Providers/VoltServiceProvider.php`,
  `app/View/Components/AppLayout.php`, `app/View/Components/GuestLayout.php`
  (só `Icon.php` sobra em `app/View/Components`), `routes/auth.php` (não era
  carregado). Nenhuma referência em views/rotas/config.
- Migration legada `add_role_to_users_table` é segura sem as tabelas spatie
  (guard com `Schema::hasTable('roles')`).
- **Fix**: `public array $roleLabels` não pode inicializar com chamada de
  método (`UserRole::labels()` não é expressão constante) — setado no `mount()`.

## Fora do escopo atual

- Próximo incremento da Fase 2: product pass para remover as páginas
  legadas de blog/usuarios/settings, ou início da Fase 3 (app Android sobre
  a API REST), ou refinos do MVP (ex.: página de assinatura vista pelo
  próprio professor).
- Páginas legadas de blog/usuarios/settings permanecem fora do menu do SaaS
  (foram des-spatiadas, mas podem ser removidas num futuro product pass).
- Swagger (docs manuais), refresh token, gateway de pagamento,
  integrações Strava/Garmin.
- App Android (Fase 3).
