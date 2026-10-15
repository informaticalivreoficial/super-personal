# Arquitetura — SportPlan (Fase 1)

> Documento técnico da base API-first. Visão de produto e histórico: `AGENTS.md`.
> Referência de endpoints: `docs/api.md`.

## Stack

- PHP 8.3 / Laravel 10 / Laravel Sail
- MariaDB (host `mariadb`, db `superpersonal`)
- Sanctum (tokens pessoais de longa duração); papéis em `users.role`
  (`spatie/laravel-permission` **removido** — ver "Perfis")
- Comandos: `vendor/bin/sail artisan ...`, testes: `vendor/bin/phpunit`

## Multi-tenancy (3 camadas)

O **tenant é o treinador** (`teachers.id`). Todo dado de domínio carrega `teacher_id`.

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
| `>0` | id do treinador — escopo ativo |

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
- **Auto-cadastro** (`/auth/register`) cria conta de treinador: `users.role = teacher`
  + perfil `teachers` + redirect `/admin`. Alunos são cadastrados pelo treinador.
- Login do painel (`App\Livewire\Auth\Login`) bloqueia: senha inválida,
  usuário inativo (`status != 1`), student, treinador inativo (`teachers.active = false`).
- Assets Vite: `vendor/bin/sail npm install && vendor/bin/sail npm run build`
  (sem `public/build` as views 500 com "Vite manifest not found").
- **Dashboard** — `/admin` (`App\Livewire\Dashboard\Dashboard`) bifurca por
  perfil: treinador vê os KPIs do próprio tenant (`teacherDashboard()`);
  **admin vê a visão da plataforma** (`isPlatformAdmin()` →
  `DashboardService::platformDashboard()`): treinadores (total/ativos/novos
  no mês), alunos (total/ativos), assinaturas (ativas/trial/em atraso/sem
  assinatura + receita mensal = soma dos planos ativos), treinos hoje,
  pagamentos/assinaturas em atraso e acesso rápido (Treinadores, Novo
  treinador, Modalidades, Configurações). A API `GET /teacher/dashboard`
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
  - **Exercícios (`exercises`)** — treinador vê os próprios + globais somente
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
- **Treinadores (tenants) pela plataforma** — `/admin/professores`
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
    `ensureProfile` quando o papel é `teacher` — antes o treinador criado
    ali ficava sem tenant e o painel dele ficava vazio.
- **Assinaturas do treinador (billing da plataforma) — checkout transparente
  PagBank** (evolução de 2026-10-13; gateway trocado de Mercado Pago para
  **PagBank** em 2026-10-15; antes disso era só billing manual):
  - **Catálogo `saas_plans`** (global, admin-only): `name/slug/description`,
    `max_students` (**null = ilimitado**), preços por período
    (`price_monthly/quarterly/annual`), `active`, `sort_order`.
    Seeder `SaasPlanSeeder` com o catálogo inicial — **Gratuito** (10
    alunos, R$ 0), **Intermediário** (30 alunos, R$ 49,90/134,70/478,80) e
    **Ilimitado** (R$ 99,90/269,70/958,80); valores editáveis no painel;
    enum `BillingPeriod` (monthly/quarterly/annual, `labels()` + `months()`);
    CRUD admin em `/admin/assinaturas/planos`
    (`SaasPlans\{SaasPlanIndex,SaasPlanForm}`, `SaasPlanService` com
    auto-slug em colisão — `gratuito-2` — e exclusão bloqueada com
    assinaturas vinculadas, `SaasPlanPolicy`, `SaasPlanRequest`).
  - **`subscriptions` expandida**: `plan_id` (FK `saas_plans`,
    nullOnDelete — coluna legada `plan` string removida), `period`
    (`BillingPeriod`), `gateway` (`manual|pagbank`), `payment_id`
    (id do pedido no gateway; a coluna `preference_id` do gateway anterior
    foi **removida** na migration `2026_10_15_100001`); novo status
    **`PENDING`** ("Aguardando pagamento") no enum `SubscriptionStatus`;
    helper `externalReference()` = `sub-{id}` (usado no `reference_id` do
    pedido), `isCurrentlyActive()` e `maxStudents()`.
  - **`PagBankService`** (config `services.pagbank` ←
    `PAGBANK_TOKEN/PAGBANK_PUBLIC_KEY/PAGBANK_SANDBOX/PAGBANK_WEBHOOK_URL`):
    cliente HTTP (`Http::baseUrl` + `withToken`, timeout 20) contra
    `https://sandbox.api.pagseguro.com` ou `https://api.pagseguro.com`;
    **valores em centavos**; `isConfigured()`, `publicKey()` (usa a env quando
    definida, senão busca em `GET /public-keys/card` e guarda em cache por
    12 h), `createCardOrder()`, `createPixOrder()`, `fetchOrder()`,
    `rejectionMessage()` e `normalizeOrder()` (id, `reference_id`, status do
    gateway, `charge_id`, `code`/`message`, `qr_code_text`/`qr_code_image`).
    Erros do gateway são logados com o detalhe técnico e re-lançados como
    `RuntimeException` **neutra** (nada de marca do parceiro no texto que o
    cliente vê).
  - **Fluxo checkout transparente — cartão** (o usuário **nunca sai do
    painel**): a página `/admin/assinatura` renderiza um modal com o form de
    cartão (`resources/js/payment-card.js`, componente Alpine
    `x-data="cardPayment({ publicKey, amount, cpfId })"`) — o SDK de
    criptografia é carregado sob demanda (`ensureSdk()` com dedupe, timeout
    de 15 s) e `PagSeguro.encryptCard()` gera o `encryptedCard` **no
    navegador** (número/validade/CVV nunca são enviados em claro). O CPF do
    titular fica num campo próprio (fora do `wire:ignore`, com `x-mask`);
    o submit chama `$wire.call('payWithCard', { encrypted, holder,
    installments, cpf })`.
  - **Pagamento flui** por `SubscriptionService` (caminho único de escrita):
    `payWithCard(Teacher, SaasPlan, BillingPeriod, array $card)` aplica
    plano/período/PENDING **antes** da chamada ao gateway guardando um
    **snapshot** (`getAttributes()`) — `PAID` → ativa; `WAITING`/
    `IN_ANALYSIS` → grava `payment_id` e espera o webhook; recusa/erro →
    `restoreSnapshot()` (quem tinha plano vigente não perde nada) e mensagem
    pt-BR de `rejectionMessage()` (mapa por código: saldo, antifraude, razão
    do emissor, cartão inválido, + fallback por texto). Validado por
    `PayWithCardRequest` (`encrypted`, `holder`, `installments` 1–12, `cpf`
    com 11 dígitos).
  - **Fluxo checkout transparente — Pix**: `payWithPix()` cria o pedido com o
    array **`qr_codes[]`** no topo (`amount.value` em centavos +
    `expiration_date`); a resposta (201) traz `qr_codes[].text` (copia e cola) e
    os links `QRCODE.PNG`/`QRCODE.BASE64`. O caminho
    `charges[].payment_method.pix` **não** é usado: ele exige chave Pix
    cadastrada na conta e responde `40010 PIX_KEY_NOT_FOUND` (confirmado no
    sandbox). O modal desenha o QR **localmente em SVG** com `simple-qrcode` a
    partir do copia e cola (nada do parceiro é carregado na página), mostra o
    textarea e o botão "Copiar código Pix". A assinatura fica PENDING até a
    confirmação.
    - **O Pix exige o CPF do pagador** (`customer.tax_id`; sem ele o gateway
      responde `40001 "must not be null"`). O `payer()` monta a identificação
      podando o CPF para dígitos (preferindo o CPF digitado no cartão e, no
      Pix, o `users.cpf` do cadastro) e `payWithPix()` **aborta antes de gerar
      o pedido** (com mensagem pedindo o CPF em "Meu perfil") quando ele não
      existe — nada é gravado. É o que motivou o cadastro obrigatório do
      treinador (ver "Cadastro obrigatório do treinador").
    - **As duas abas do modal ficam SEMPRE no DOM** (`id="pay-panel-card"` /
      `id="pay-panel-pix"`, alternando só a classe `hidden` via
      `@class(['hidden' => ...])`). Trocar os blocos por `@if` fazia o morph do
      Livewire reaproveitar o `<div>` do cartão (mesma posição na árvore) e o
      `wire:ignore` preservar o DOM antigo — clicar em "Pix" continuava mostrando
      o formulário do cartão. Bônus: o cartão digitado não se perde ao alternar.
      O `x-data="{ copied: false }"` do copia e cola fica num wrapper que contém
      o `<textarea x-ref="pixCode">` **e** o botão (irmão): com o `x-data` no
      próprio textarea o `$refs` ficava fora do alcance do botão.
  - **Webhook** público `GET|POST /webhooks/pagbank`
    (`Webhooks\PagBankWebhookController`, isento de CSRF): o corpo da
    notificação **não é fonte de verdade** — o controller extrai o id do
    pedido (ou o `reference_id`) e **reconsulta** `GET /orders/{id}` via
    `SubscriptionService::syncFromOrder()`. Sem id/referência identificável a
    notificação é apenas ignorada (200). A validação por assinatura de
    payload foi descartada de propósito (o PagBank não envia o corpo
    assinado de forma documentada para este fluxo) — documento em
    `docs/architecture.md`/controller.
  - **`applyOrder()`** (aplicação de estado, idempotente e conservadora):
    `PAID` → ativa (`ends_at` = hoje + meses do período via
    `addMonthsNoOverflow`); `DECLINED` só marca PAST_DUE se a assinatura
    estiver **PENDING**; `REFUNDED`/`IN_DISPUTE`/`CANCELED` só marcam
    PAST_DUE se a assinatura estiver ACTIVE **e** o `payment_id` for o do
    pedido do evento (evita falso past_due em assinatura restaurada).
  - **`SubscriptionService`** também tem: `assignFreePlan(Teacher)`
    (idempotente, sem downgrades — chamado no `TeacherService::store` e no
    `Register`: **todo novo treinador nasce no plano gratuito**) e
    `activateFreePlan()/subscribe()` (plano gratuito ativa na hora, sem
    gateway).
  - **Enforcement de alunos**: `StudentService::store` bloqueia com
    `ValidationException` quando a assinatura **vigente**
    (`isCurrentlyActive()`) tem limite definido e o tenant já atingiu
    (conta alunos não-softdeleted; sem assinatura não há bloqueio);
    upgrade é sugerido na própria mensagem ("Minha assinatura").
  - **Admin**: índice `/admin/assinaturas`
    (`Subscriptions\SubscriptionIndex`: busca por treinador/e-mail, filtro
    de status, badges, gateway — "Manual"/"Online" (sem expor a marca do
    parceiro) —, vigência, link "Gerenciar" → `professors.edit`); card
    `ProfessorSubscription` com **dropdown do catálogo** + período +
    auto-preço do plano ao trocar plano/período; sidebar "Plataforma" com
    "Assinaturas" e "Planos do SaaS".
  - **Treinador — self-service** (`/admin/assinatura`, `role:teacher`):
    card da assinatura atual (plano, período, valor, vigência, nota
    "Aguardando pagamento" quando PENDING) + **catálogo de planos ativos**
    em cards com botões Mensal/Trimestral/Anual (gratuito = "Usar plano
    gratuito", ativa na hora) — plano pago abre o **modal de checkout**
    (`MySubscription::subscribe` → `openPaymentForm` → abas Cartão/Pix) e
    `payWithCard`/`payWithPix` finalizam; o botão "Já efetuei o pagamento"
    (`checkPayment`) reconsulta o pedido quando o webhook não chega
    (ambiente local sem URL pública). O cliente **nunca** sai do painel.
  - **Ambiente**: em dev (`PAGBANK_SANDBOX=true`, `APP_URL` HTTP) o pedido é
    criado sem `notification_urls` (o gateway exige HTTPS) — por isso o
    botão de conferência manual; `PAGBANK_WEBHOOK_URL` define a URL de
    produção. O Pix **não** exige chave Pix cadastrada na conta (o pedido com
    `qr_codes[]` é criado normalmente no sandbox; validado com o token de
    teste).
  - **Refino anterior mantido**: link "Minha assinatura" no sidebar só
    para `isTeacher()`; admin continua ajustando manualmente no
    `ProfessorForm` (inclusive voltando para trial/cancelada).

### Meu perfil (conta do usuário logado)

- **Página** `/admin/meu-perfil` (`App\Livewire\Dashboard\MyProfile`, rota
  `profile.show` dentro do grupo `role:teacher,admin` — teacher e admin
  acessam): edição dos **próprios** dados da conta em 5 cards — "Foto e
  dados pessoais" (avatar com upload/remoção, nome, e-mail, e-mail
  adicional, nascimento d/m/Y), "Documentos" (CPF, RG, órgão expedidor),
  "Contato" (telefone fixo, celular, WhatsApp), "Endereço" (ViaCEP) e
  "Alterar senha" (placeholder "Deixe os dois campos em branco para
  manter a senha atual").
- **Escrita** via `ProfileService::update()` (caminho único, transaction):
  atualiza os campos pessoais/documentos/contato/endereço em `users`
  (mutators do model tratam formatação: birthday d/m/Y→Y-m-d, telefones e
  CEP limpos, UF em maiúscula), `password` hash só se preenchido, `avatar`
  (caminho resolvido pelo componente em `users/{id}/avatar.ext`, arquivo
  antigo apagado só após save; rollback do novo upload se validação de
  textos falhar) e, quando o usuário tem perfil `teachers`, **sincroniza**
  `teachers.name` e `teachers.phone` (fonte do cadastro do tenant/landing).
  Slug e `landing_title` NÃO são tocados aqui (conteúdo do `LandingForm`).
- **Validação** por `UpdateProfileRequest` (reusada via
  `ValidatesWithFormRequest`): nome/e-mail obrigatórios, e-mail único com
  `ignore(user_id)` do próprio id, data `date_format:d/m/Y`, telefones com
  regex pt-BR, UF 2 letras, senha `nullable|min:8|confirmed` — mensagens em
  pt-BR; upload do avatar (`image|max:2048`) validado no componente.
  **Treinador**: `cpf` (11 dígitos, aceita máscara — o valor vira `tax_id` do
  gateway) e `cell_phone` são **obrigatórios**; para o admin seguem opcionais.
- **UX**: prefill preferindo `users.phone` com fallback para
  `teachers.phone` (admin edita o phone do tenant no `ProfessorForm`);
  senha nunca re-aparece preenchida após salvar; toast de sucesso; link
  "Meu perfil" no sidebar para teacher e admin (bloco "Conta", acima de
  "Minha página pública").

### Cadastro obrigatório do treinador (primeiro acesso)

- **Problema**: o parceiro de pagamentos recusa a cobrança sem a
  identificação do pagador (`customer.tax_id` = CPF) — era o `400` `40001
  "must not be null"` ao gerar o **Pix** (o cartão já coletava o CPF no
  formulário). Treinador sem CPF no cadastro = assinatura impossível.
- **Gate**: middleware **`App\Http\Middleware\EnsureProfileIsCompleted`**
  (alias `profile.complete`, aplicado ao grupo `/admin` depois de `role`):
  se o usuário é **treinador** e `User::missingProfileFields()` não está
  vazio (CPF **e** celular), qualquer rota `/admin/*` redireciona para
  `/admin/completar-cadastro`. O **admin da plataforma** (sem tenant e sem
  cobrança própria) não é afetado, e a própria rota de completar não
  redireciona em loop.
- **Página** `/admin/completar-cadastro` (`profile.complete`): é o **mesmo**
  componente `MyProfile` em modo obrigatório (`#[Locked] bool $completing`,
  ligado pela rota / parâmetro de mount) — banner "Só falta completar seus
  dados…" com a lista do que falta, sem botão de voltar, CTA "Salvar e
  começar a usar" e, ao salvar, toast + redirect para `/admin`. O modo não
  altera a validação (as regras de CPF/celular do treinador valem nas duas
  telas) — nenhum dado é gravado sem CPF/celular.
- **Gatilho por papel, não por rota**: qualquer conta criada em
  `/auth/register` (auto-cadastro) ou pelo admin em `/admin/professores`
  nasce sem CPF e cai no gate no primeiro acesso.
- **Nota de implementação**: `ValidatesWithFormRequest` agora injeta
  container + **user resolver** na FormRequest criada via `Request::create`
  — antes `$request->user()` era `null` no caminho Livewire e as rules que
  dependem do papel do usuário logado nunca entravam.

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
  - `TeacherIsolationTest` — treinador não vê/edita dados de outro (404/403).
  - `StudentTrainingTest` — aluno vê só os próprios treinos, `complete` cria
    execução vinculada ao aluno correto, execução visível só ao treinador dono.
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
  - `PanelRoutesSmokeTest` — as 17 rotas do domínio `/admin/*` retornam 200
    para teacher (cria ConfigTableSeeder; pegou o 500 de Configurações);
    fase admin cobre as rotas exclusivas de plataforma (`/admin/modalidades*`
    e `/admin/professores*`); teacher recebe 403 em ambas. Rotas legadas de
    posts/usuarios/sitemap saíram do smoke no product pass (incremento 11).
  - `SessionItemCrudTest` (9) — CRUD/move/renumber de itens, validação,
    isolamento por tenant.
  - `SportCrudTest` (11) — CRUD admin-only, 403 para teacher, bloqueio de
    exclusão com vínculos, isolamento global.
  - `ExerciseCrudTest` (11) — treinador só edita os próprios, globais somente
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
    (rotas admin/403 treinador/guest, criação com tenant nascido, validações,
    update com ignore de e-mail + senha opcional, toggle ativo/inativo, acesso
    do novo treinador ao painel, idempotência do `ensureProfile`).
  - **Console**: `SendRemindersTest` (11) — lembretes automáticos (janelas de
    vencimento, hoje/amanhã, pagado/concluído não notifica, aluno sem conta
    pulado, idempotência com 2 execuções).
  - **Notificações**: `NewTrainingAvailableTest` (8) — plano publicado por
    evento (criar ativo, publicar rascunho, editar sem reaviso, reativar
    avisa de novo, aluno sem conta, fim a fim via API e painel).
  - `SubscriptionManagementTest` (7) — assinaturas do treinador no admin
    (badge no index, card na edição, criação/atualização, validação,
    `cancelled_at` automático, 403 para treinador).
  - `PlatformDashboardTest` (3) — dashboard da plataforma do admin (KPIs,
    receita, agregação cross-tenant via service, treinador vê o próprio).
  - `LandingFormTest` (32) — página "Minha página pública" (prefill, salvar,
    slug gerado/reservado/duplicado/inválido, próprio slug não é tratado como
    duplicado, desligar opt-in esconde a landing, admin edita qualquer tenant,
    403/redirect de rota, policy de isolamento, teacher sem perfil 403,
    uploads de identidade no tenant, substituição/apagamento de imagem,
    upload não-imagem rejeitado, rollback sem uploads órfãos, previews,
    upload/substituição/remoção de foto de perfil, contador de acessos).
  - **Web** (`tests/Feature/Web`): `TeacherLandingTest` (20) — landing pública
    (render para guest com CTA/SEO, só modalidades do dono, visitante logado
    como outro tenant vê o conteúdo completo, 404 para não-publicado/inativo/
    slug inexistente, slug gerado do nome, reservado/duplicado com sufixo,
    `wa.me` sem DDI duplicado, página sem bio/whatsapp, foto,
    redes sociais + endereço por opt-in, identidade visual, `landing_title`
    no hero/SEO com fallback para o nome, incremento de `views` por visita
    pública sem contar o dono, landing oculta não incrementa).
  - `MyProfileTest` (14) — página "Meu perfil" (guest redirect, prefill,
    salvar sincronizando users+teachers, unique de e-mail com ignore próprio,
    senha opcional/hashed/min-8/confirmed, documentos/contato/endereço
    completos (birthday d/m/Y→Y-m-d, CEP limpo, UF maiúscula), formato de
    data/UF, upload/remoção de avatar, avatar não-imagem rejeitado, validações,
    admin sem perfil de treinador, link no sidebar).
  - `SaasPlanCrudTest` (9) — CRUD do catálogo (admin-only/403 teacher),
    auto-slug e sufixo em colisão (`gratuito-2`), exclusão bloqueada com
    assinaturas, seeder idempotente, plano gratuito automático e sem
    downgrade.
  - `StudentLimitTest` (7) — enforcement por plano (gratuito bloqueia o 11º
    aluno, ilimitado/liberado sem assinatura/cancelada não bloqueia, soft
    delete libera vaga, erro no `StudentForm`).
  - `ProfileCompletionGateTest` (11) — treinador sem CPF/celular é
    redirecionado de qualquer rota do painel para `/admin/completar-cadastro`,
    banner com a lista do que falta, só celular faltando também bloqueia,
    treinador completo usa o painel, admin não é afetado, salvar manda para o
    painel (CPF gravado só com dígitos) e sem CPF/celular não grava nada,
    CPF inválido é rejeitado com mensagem, marcação obrigatória do treinador e
    guest → login.
  - `MySubscriptionTest` (24) — catálogo do treinador, assinar gratuito
    ativa na hora, plano pago abre o modal sem gravar nada (sem PENDING
    órfão), campos de cartão + aba Pix no modal, `payWithCard`
    aprovado/recusado/em análise (payload criptografado conferido),
    validação de token/parcelas/CPF, isolamento entre treinadores, Pix com
    QR Code (payload do Pix com `customer.tax_id` = CPF do cadastro, só
    dígitos), **sem CPF o Pix falha antes do gateway** com mensagem útil e sem
    gravar PENDING (regressão do `40001 must not be null`), **os dois painéis
    sempre no DOM** (regressão do `wire:ignore` +
    morph) e **escopo Alpine do copia e cola** (regressão do `$refs`),
    `checkPayment` confirmando/aguardando, gateway indisponível
    (nada é gravado, erro amigável), `cancelPayment`, assinatura cancelada,
    admin bloqueado (403), guest, sidebar.
  - **Services** (`tests/Feature/Services`): `PagBankServiceTest` (13) —
    `isConfigured`, chave pública (env, `GET /public-keys/card` + cache,
    `null` em falha), payload de cartão (centavos, parcelas, `tax_id`,
    `notification_urls` só com HTTPS), erro 400 → `RuntimeException`
    neutra, pedido Pix pelo **`qr_codes[]`** (payload conferido + regressão
    contra `charges[].payment_method.pix`), QR lido também de
    `charges[].qr_code`, pedido sem copia e cola, `fetchOrder`
    normalizado/nulo e mapa de mensagens de recusa.
  - `SubscriptionManagementTest` (8) — card do admin com catálogo/Período,
    auto-preço ao trocar plano, validações (plan_id/status/amount),
    cancelamento com rastro.
  - **Total: 354 testes / 1237 assertions** (42 API + 234 painel + 11 console
    + 8 notificações + 46 web + 13 services).
  - **Webhooks**: `PagBankWebhookTest` (11) — notificação com id do pedido
    ativa a assinatura, anual +12 meses, `WAITING` mantém PENDING,
    `DECLINED` com PENDING → past_due, `DECLINED` não derruba assinatura
    vigente, `REFUNDED` de pedido pago → past_due, notificação só com
    `reference_id` usa o `payment_id` guardado, referência desconhecida é
    ignorada, sem identificação não consulta o gateway (200), GET aceito e
    rota isenta de CSRF.
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
- **Product pass (incremento 11)** — páginas legadas **removidas do painel**
  (29 arquivos): componentes/views/rotas de posts (5 + `Reports\Posts`),
  usuarios/clientes/time/visualizar (4) e sitemap-generator; comandos
  `posts:clean-old`/`posts:purge-deleted`/`sitemap:generate` + 3 linhas de
  agendamento; `UserPolicy` (só era usada pelos componentes de usuários);
   seeders `UsersTableSeeder`, `CatPostsTableSeeder`, `PostsTableSeeder`;
   comentários mortos do `SideNavigation`. **Mantidos na época**: models
   `Post`/`CatPost` + migrations/factories e o site público — depois
   **removidos em 2026-10-09** (ver "Blog do site público removido").
   `Settings` (`Config` compartilhado em todas as views pelo AppServiceProvider)
   permanece. Painel `/admin` ficou só com 23 rotas de domínio.

## Fase 3 — app Android (iniciada — contrato da API)

- **Item 1 (2026-10-09) — credencial do aluno via código de convite**:
  - Migration `invite_code` em `students` (8 chars, alfabeto sem ambíguos,
    único; backfill para alunos sem conta).
  - `StudentService::store` gera o código na criação (API e painel usam o
    mesmo service); `regenerateInviteCode` troca (bloqueado se já há conta);
    `registerWithInvite` cria `users.role = student` (e-mail =
    `students.email`), vincula `user_id`, consome o código — em transaction.
  - **API**: `POST /api/v1/auth/student-register` (público, throttle 10/min,
    payload `{invite_code, password, password_confirmation}`) → `201` com
    `{token, token_type, user}` (app sai logado); `StudentResource` expõe
    `invite_code` ao treinador.
  - **Validação antecipada**: `StoreStudentRequest`/`UpdateStudentRequest`
    rejeitam e-mail já existente em `users` (conflito detectado no cadastro,
    não no registro do aluno).
  - **Painel**: card "Acesso no app" na ficha do aluno (`StudentShow`) —
    código + copiar + "Gerar novo código" (toast); aluno com conta mostra
    "Conta ativa".
  - **Testes**: `StudentRegisterTest` (10) + `StudentInviteTest` (6).

### Item 2 (2026-10-09) — app do aluno em Flutter (`mobile/`)

- **Decisão de stack**: Flutter 3.47.1 stable (snap `/snap/flutter/bin/flutter`)
  em monorepo na raiz (`mobile/`, `--org br.com.superpersonal
  --project-name super_personal --platforms android,ios`) com **dio 5.11 +
  Riverpod 3 (AsyncNotifier) + GoRouter 18 + flutter_secure_storage**.
- **Ambiente**: Android SDK `~/Android/Sdk`; **JDK 21 portátil** (Temurin) em
  `~/tools/jdk-21` — exportar **a cada comando** (não persiste entre chamadas
  do shell):
  `export JAVA_HOME=$HOME/tools/jdk-21; export PATH=$JAVA_HOME/bin:$PATH`.
- **Camada core** (`mobile/lib/core/`):
  - `config/api_config.dart` — base URL por `--dart-define=API_BASE_URL`
    (default `http://10.0.2.2` p/ emulador; aparelho físico usa o IP do
    notebook, ex.: `http://192.168.15.82`, mesma Wi-Fi e Sail no ar);
  - `api/api_client.dart` — dio + interceptor Bearer + `onUnauthorized`
    (401 derruba a sessão; dio lança `DioException` fora de 2xx);
  - `api/api_exception.dart` — `ApiException.fromDio` com mensagens pt-BR e
    `fieldErrors` (422);
  - `storage/token_storage.dart` — `TokenStorage` abstrato
    (`SecureTokenStorage` no aparelho / `InMemoryTokenStorage` em teste);
  - `router/app_router.dart` — GoRouter com `refreshListenable` =
    `ValueNotifier<AsyncValue>` escutando `authControllerProvider`
    (guard splash → login → home).
- **Auth** (`mobile/lib/features/auth/`): `User` (recorte do `UserResource`),
  `AuthRepository` (`POST /auth/login` grava token; `GET /auth/me`
  desembrulha `data`; `POST /auth/logout` descarta token mesmo com falha),
  `AuthController extends AsyncNotifier<User?>` (build = token ? `me()` :
  null; `login` sem loading global p/ o guard não pular p/ splash no submit).
- **Telas**: `LoginScreen` (e-mail+senha, spinner local, erros da API),
  `SplashScreen` (restauração de sessão) e `HomeScreen` (saudação + tiles das
  próximas telas: Dashboard/Calendário/Treinos/Progresso/Notificações).
- **Plataforma**: label "SportPlan" + `usesCleartextTraffic` (Android);
  `NSAllowsArbitraryLoads` (iOS ATS) — HTTP de dev.
- **Qualidade**: `dart analyze` = *No issues found*; `flutter test` = 6/6
  (`test/auth_repository_test.dart` com `HttpClientAdapter` fake: login ok,
  401, 422, offline, `me()`, logout); `flutter build apk --debug` concluído
  (`mobile/build/app/outputs/flutter-apk/app-debug.apk`).
- **Comandos úteis** (em `mobile/`):
  ```bash
  export JAVA_HOME=$HOME/tools/jdk-21; export PATH=$JAVA_HOME/bin:$PATH
  dart analyze && flutter test
  flutter run -d <serial> --dart-define=API_BASE_URL=http://<IP-do-notebook>
  flutter build apk --debug
  ```
  > USB direto exige regra udev (Samsung `04e8`) ou `adb` como root; sem
  > isso `adb devices` mostra `no permissions`.

## Landing do SaaS — home pública `/` (2026-10-13)

- **`SiteController::home()`** reescrito: a home deixou de ser o stub "Home
  Page" do starter e virou a **landing de marketing do SaaS** (view
  standalone `web/default/home.blade.php`, server-rendered, mesmo DNA
  visual da landing dos treinadores — dark `brand-950`, títulos Barlow
  Condensed, laranja `brand-*`; SEO via `Seo` com título/descrição de
  conversão). Passa `plans` = catálogo `saas_plans` ativos (ordenados).
- **Seções** (uma página, sem enrolação): header sticky (logo, âncoras
  Por que/Como funciona/Planos/Dúvidas, Entrar + "Criar conta grátis") →
  hero "Menos planilha. Mais treino." com **mockup do app em CSS puro**
  (sem fotos de banco: card de sessão, aderência, notificação) → chips de
  modalidades → grade de 6 diferenciais → "Como funciona" (3 passos
  numerados) → **planos com toggle Mensal/Trimestral/Anual** (Alpine via
  CDN — página standalone; `x-cloak` + preços do banco com
  `priceFor(period)`; card do meio destacado "Mais popular") → seção do
  app do aluno → FAQ (`<details>` nativo, 6 perguntas) → CTA final →
  rodapé. CTAs apontam para `route('register')` (auto-cadastro de
  treinador) e `route('login')`.
- **Robustez**: seção de planos só renderiza se houver catálogo ativo
  (banco sem `saas_plans` → página sem a seção, nunca 500);
  `$config->app_name` em todos os títulos.
- **Testes**: `tests/Feature/Web/HomeLandingTest` (5) — guest 200 com
  CTAs/seções, links de registro/login, planos do catálogo (nomes,
  preço, "Mais popular"), seção oculta sem catálogo, head SEO + ano.

## Páginas legais — Termos de Uso e Política de Privacidade (2026-10-15)

- **Rotas públicas** `GET /termos` (`web.terms`) e `GET /privacidade`
  (`web.privacy`) → `SiteController::terms()`/`privacy()` (método privado
  `legal()` compartilhado), view standalone `web/{template}/legal.blade.php`
  (server-rendered p/ SEO, DNA visual do site: dark `brand-950`, Barlow
  Condensed, laranja `brand-*`) + fragmentos de **texto padrão** em
  `web/{template}/legal/{terms,privacy}.blade.php`.
- **Conteúdo editável pelo admin**: os campos já existentes
  `config.terms_condicions` e `config.privacy_policy` (editor Quill em
  Configurações) sobrescrevem o texto padrão; a "Última atualização" mostra
  `updated_at` da Config quando há conteúdo customizado e a data atual quando
  é o texto do código. Fallback em `template` vazio (`?: 'default'`).
- **Conteúdo sem texto = vazio**: o editor grava `<p><br></p>`/`&nbsp;`
  quando o campo é apenas aberto e salvo — isso é detectado
  (`strip_tags` + `html_entity_decode` + nbsp) e cai no texto padrão, para a
  página legal **nunca** ficar em branco.
- **Texto padrão (rascunho versionado, revisável pelo usuário)**: Termos com
  9 seções (aceitação, cadastro/conta, planos e pagamento pré-pago + parceiro
  de pagamentos sem citar marca, **treinador é controlador dos dados dos
  alunos** / plataforma é operadora, uso aceitável, propriedade intelectual,
  disponibilidade, alterações, lei BR + contato por e-mail da Config);
  Privacidade com 11 seções LGPD (controlador/operador, dados tratados
  incluindo avaliações e execuções, finalidades e bases legais,
  compartilhamento com operadores sem venda de dados, cookies essenciais,
  retenção, direitos do titular — art. 18, segurança/multi-tenant, menores
  com consentimento do responsável, alterações, contato).
- **Links**: rodapé da home (`Termos`/`Privacidade`) e aviso de aceite no
  formulário de registro ("Ao criar sua conta você concorda com os Termos de
  Uso e a Política de Privacidade", links em nova aba para não perder o form);
  as páginas legais têm rodapé com Início/Termos/Privacidade/Entrar.
- **GOTCHA do Blade**: `@if` **colado a uma palavra** (`Plataforma@if (...)`)
  não é compilado (o `\B` da diretiva exige não-fronteira) e o `@endif`
  correspondente estoura "unexpected token endif" na view compilada —
  sempre com espaço/quebra de linha antes das diretivas (mesma família do
  gotcha do `@assets` em comentário JS).
- **Testes**: `tests/Feature/Web/LegalPagesTest` (10) — guest 200 nas duas
  páginas com o texto padrão, conteúdo das Configurações prevalecendo, fallback
  com campo vazio/`null`, `<p><br></p>` ignorado, `template` vazio, SEO + links
  públicos, privacidade linkando termos, rodapé da home e aviso no registro.

## Blog do site público removido (2026-10-09)

- O SaaS não tem blog: as 7 rotas legadas (`/blog`, `/blog/artigo/{slug}`,
  `/blog/categoria/{slug}`, `/noticias`, `/noticia/{slug}`,
  `/noticias/categoria/{slug}`, `/pagina/{slug}`) apontavam para métodos
  **inexistentes** no `SiteController` (só existe `home()`) — davam 500 se
  acessadas. Removidas; o site público ficou só com `/` (`web.home`).
- **Removidos**: models `Post`, `CatPost`, `PostGb`; enum `PostType`;
  factories `PostFactory`/`CatPostFactory`; migrations de criação (2021);
  relação `User::posts()`. Migration nova
  `2026_10_09_100001_drop_blog_tables` dropa `post_gb`, `posts` e `cat_post`
  (ordem das FKs; no banco novo é no-op).
- **Mantidos**: `SiteController::home()` + views `web/default/*`, `Seo`,
  `Settings`/`Config` e o link "Ver site" do topbar.
- Verificação: varredura sem referências órfãs, `/blog` → 404, `/` → 200,
  suíte completa verde.

## Landing page pública por treinador (2026-10-12)

- **URL**: `GET /treinador/{slug}` (rota `web.treinador`, pública sem auth) →
  `TeacherLandingController` (`__invoke`) + view standalone
  `resources/views/web/treinador.blade.php` — HTML server-renderizado (sem
  Livewire) p/ SEO, com `Seo`, paleta `brand` e Vite. Obs.: o grupo de rotas
  públicas tem `namespace => Web` legado → action em array
  (`[Controller::class, '__invoke']`), senão o namespace é prefixado e dá 500.
- **Colunas novas em `teachers`** (migration `2026_10_12_000001`): `slug`
  (unique + backfill do nome), `public_profile` (bool, **default false = opt-in**),
  `tagline`, `whatsapp`. O backfill espelha `Teacher::RESERVED_SLUGS`
  (migrations não dependem de código vivo).
- **Conteúdo extra** (migration `2026_10_12_100001`): identidade visual
  (`logo` cabeçalho, `favicon` aba, `metaimg` og:image — accessors `*_url`
  via disco public), redes sociais (`facebook`, `twitter`, `instagram`,
  `youtube`, `linkedin`) e endereço (`zipcode` com accessor `#####-###`,
  `street`, `number`, `complement`, `neighborhood`, `state`, `city`) com
  **opt-in de privacidade** `display_address` (default false — só aparece na
  landing se o treinador marcar). Escrita via `TeacherService::updateLanding`;
  upload das imagens no componente Livewire (`WithFileUploads`, validação
  `image` + tamanho por campo, nome canônico `teachers/{id}/{campo}.ext`,
  arquivo antigo apagado só após save bem-sucedido, rollback dos novos se a
  validação de textos falhar).
- **Título personalizado** (migration `2026_10_13_000001`): coluna
  `teachers.landing_title` (nullable, 120) — o treinador pode exibir o nome
  da empresa/marca no grande título do hero (e no `<title>`/og:title) em vez
  do próprio nome; vazio = `name` (comportamento padrão). CTA final e
  demais usos continuam com o nome real. Campo no `LandingForm`.
- **Consulta de CEP (ViaCEP)**: componente Alpine global `x-data="viaCep"`
  registrado em `resources/js/via-cep.js` (listener `alpine:init` em
  `window.Alpine` — o Livewire 3 dá boot em `DOMContentLoaded`, então o
  módulo Vite registra a tempo). Uso genérico: embrulhe qualquer grupo de
  endereço com o componente Blade `<x-via-cep>`
  (`resources/views/components/via-cep.blade.php`) e marque os inputs com
  `data-via-cep="zipcode|street|neighborhood|city|state"` — ao completar
  8 dígitos (change/Enter) busca `viacep.com.br/ws/{cep}/json/`, preenche
  rua/bairro/cidade/UF, dispara `input` (bubbles) para `wire:model` e mostra
  toast no erro (mesmo canal do `ToastrNotification`). Primeiro uso: card
  "Endereço" do `LandingForm`.
- **Slug**: gerado no hook `saving` do model via `Teacher::makeUniqueSlug()`
  (unicidade + palavras reservadas → sufixo `-2`, ex.: `admin-2`); a
  `UpdateLandingRequest` valida formato/reservado/unique (ignore do próprio id).
  URL centralizada no accessor `$teacher->landing_url` (se um dia virar
  subdomínio, muda num lugar só).
- **O que é público**: existe só com `public_profile` + `active` (404 caso
  contrário). Conteúdo = nome, tagline, bio, CREF/especialidade, foto
  (accessor `profile_photo_url`), **modalidades distintas das sessões do
  próprio tenant** (subquery com `withoutGlobalScope('tenant')` — a página
  pertence ao dono mesmo para visitante logado como outro treinador) e CTA
  WhatsApp (`wa.me` com +55 implícito se sem DDI). **Nunca** alunos, treinos
  ou pagamentos.
- **Foto de perfil no form**: coluna `teachers.profile_photo` (já existente)
  ganhou upload no card "Identidade visual" do `LandingForm` (mesmo fluxo
  do logo/favicon/metaimg: nome canônico `teachers/{id}/profile_photo.ext`,
  validação `image|max:2048` no componente, arquivo antigo apagado só após
  save OK, flags `removeProfilePhoto` + preview circular).
- **Métrica de acessos**: coluna `teachers.views` (default 0, **não**
  fillable — migration `2026_10_13_100001`) incrementada no
  `TeacherLandingController` a cada página pública renderizada; a visita do
  próprio dono logado **não conta** (preview não infla). Exibida no banner
  do `LandingForm` ("👁 N acessos") e exposta na API via `TeacherResource`
  (`views` + `landing_url`) — base para mensuração futura.
- **Painel**: `GET /admin/minha-pagina` (`landing.mine`, `role:teacher`) e
  `GET /admin/professores/{teacher}/pagina` (`professors.landing`, `role:admin`)
  → um único componente `Dashboard\LandingForm`; escrita via
  `TeacherService::updateLanding()` (slug vazio é gerado do nome).
  Sidebar ganhou "Minha página pública" (só teacher) com badge de status e
  preview da URL pública.
- **Permissão**: `TeacherPolicy::updateLanding()` — admin (qualquer tenant) ou
  o próprio treinador; professor A nunca edita a página do B (middleware de
  rota + Gate + testes). Slug vazio → gerado do nome.
- **Fix da sidebar**: "Minha assinatura" estava aninhada dentro do submenu
  "Biblioteca" (só aparecia naquela página) — saiu para item de topo junto
  com "Minha página pública".
- **Loop de crescimento**: rodapé da landing traz
  "Quero minha página assim → criar conta de treinador" (`/auth/register`).

## Fora do escopo atual

- ~~Product pass do blog~~ **concluído por completo** (2026-10-09): além das
  páginas do painel (2026-10-08), models/rotas/migrations do blog do site
  público foram removidos. `Settings` e o site público (home) permanecem.
- Próximos passos da Fase 3: rodar o app no aparelho/visualizar login,
  telas do aluno (dashboard, planos, treinos com start/complete, progresso)
  sobre a API já existente.
- Swagger (docs manuais), refresh token, gateway de pagamento,
  integrações Strava/Garmin.
- Push FCM (notificações hoje são canal `database`; entra na etapa mobile).
