# Arquitetura — SportPlan (Fase 1)

> Documento técnico da base API-first. Visão de produto e histórico: `AGENTS.md`.
> Referência de endpoints: `docs/api.md`.

## Stack

- PHP 8.3 / Laravel 10 / Laravel Sail
- MariaDB (host `mariadb`, db `superpersonal`)
- Sanctum (tokens pessoais com expiração configurável, padrão 30 dias); papéis em `users.role`
  (`spatie/laravel-permission` **removido** — ver "Perfis")
- Comandos: `vendor/bin/sail artisan ...`, testes: `vendor/bin/phpunit`
- PDF: `barryvdh/laravel-dompdf` (ficha do aluno; `config/dompdf.php` não publicado —
  defaults, fontes DejaVu embutidas)
- Mídia: disco **padrão** (`FILESYSTEM_DISK`) — `public` local em dev/testes ou
  `s3` (**Cloudflare R2**, via `league/flysystem-aws-s3-v3`) — ver
  "Armazenamento de mídia"

## Armazenamento de mídia (local ↔ Cloudflare R2) — 2026-10-19

Todo o produto grava/lê mídia pelo **disco padrão**: `Storage::disk()->...`
(uploads incluídos, sem nome de disco). O padrão é resolvido por
**`FILESYSTEM_DISK`** no `.env`:

| `FILESYSTEM_DISK` | Disco | Uso |
|---|---|---|
| `public` (default) | local | `storage/app/public` + symlink `/storage` (dev/testes) |
| `s3` | Cloudflare R2 | bucket público via `R2_*` (produção) |

Trocar o armazenamento é **só mudar o `.env`** — nenhum componente, Service ou
view conhece o nome do disco.

### Configuração (`config/filesystems.php`)

| Env | Uso |
|---|---|
| `FILESYSTEM_DISK` | `public` (local) ou `s3` (R2) |
| `R2_ACCOUNT_ID` | endpoint derivado: `https://{account}.r2.cloudflarestorage.com` |
| `R2_ACCESS_KEY_ID` / `R2_SECRET_ACCESS_KEY` | token de API do R2 (Object Read & Write) |
| `R2_BUCKET` | nome do bucket |
| `R2_ROOT` | prefixo dos objetos (ex.: `superpersonal`); a URL pública já o inclui |
| `R2_URL` | URL pública do bucket (`https://pub-....r2.dev` ou domínio próprio) |
| `R2_REGION` | `auto` |
| `R2_ENDPOINT` | opcional, sobrepõe o derivado do account id |
| `R2_USE_PATH_STYLE_ENDPOINT` | `false` para o R2 |

No disco `s3` o produto usa `visibility=public` e `throw=true` (credencial/rede
ruim estoura em vez de falhar em silêncio). O disco `public` continua
`storage/app/public` + symlink `/storage`.

**Testes**: `phpunit.xml` fixa `FILESYSTEM_DISK=public` — a suíte nunca fala
com o R2, mesmo que o `.env` de dev/produção esteja em `s3`. Os testes usam
`Storage::fake('public')`, que passa a ser o disco padrão.

### Pontos que dependem do driver

- `StudentResource::photoUrl()`: no disco remoto (`driver=s3`) devolve a URL
  absoluta de `Storage::disk()->url()`; no local mantém o **host da requisição**
  (`/storage/...`), necessário porque o app Android em dev acessa pelo IP da
  LAN (`APP_URL=laravel.test` não resolve no celular).
- `User::getUrlAvatarAttribute()`, `Settings` (7 previews),
  `Student::profilePhotoUrl()` e `Teacher` (logo/favicon/metaimg/foto) usam
  `Storage::disk()->url()`. Em `Settings` o `mount()` também usa o disco padrão
  (via `loadLogos()`) — nunca `asset('storage/...')` fixo, que quebrava os
  previews quando `FILESYSTEM_DISK=s3` (coberto por `SettingsImagesTest`).
- `Config` memoiza `exists()` por instância (o `AppServiceProvider` compartilha
  a mesma instância em todas as views): no R2 evita um HEAD por imagem a cada
  página.
- `StudentProfilePdfService` lê os bytes pelo disco padrão → funciona nos dois
  modos.
- **Uploads** passam `config('filesystems.default')` como disco explicitamente
  (3º argumento de `storeAs`/`storePubliclyAs`). Não usar o default implícito:
  o `TemporaryUploadedFile` do Livewire resolve para o disco **temporário**
  dele (`tmp-for-tests` em teste), e o arquivo final acabaria lá.

### Migração para o R2 (uma vez)

```bash
# 1. .env: FILESYSTEM_DISK=s3 + R2_* preenchidos
vendor/bin/sail artisan optimize:clear

# 2. copiar os arquivos atuais preservando os caminhos (sem storage/app/public/.gitignore)
aws s3 sync storage/app/public/ s3://$R2_BUCKET/$R2_ROOT/ \
  --endpoint-url "https://$R2_ACCOUNT_ID.r2.cloudflarestorage.com" \
  --exclude ".gitignore"
# ou: rclone sync storage/app/public/ r2:$R2_BUCKET/$R2_ROOT/ --exclude ".gitignore"

# 3. o bucket precisa de acesso público (r2.dev) ou domínio próprio em R2_URL
#    (sem isso as <img> recebem 403). `storage:link` deixa de ser necessário.
```

Coberto por `tests/Feature/Storage/MediaDiskTest` (3): disco local (`public`)
como padrão, URL do R2 com o prefixo `R2_ROOT` e `StudentResource` devolvendo a
URL remota (o host da requisição não vaza no modo R2).

## Configurações da plataforma (admin-only)

`/admin/configuracoes` (`settings`) edita o registro único de `config` (nome do
site, contatos, imagens, HTML legal, taxa da plataforma etc.). É uma tela **da
plataforma**, não do treinador:

- Rota com `role:admin` no grupo `/admin` **e** `Settings::boot()`/`mount()` com
  `abort_unless(auth()->user()?->isPlatformAdmin(), 403)` — protege também as
  chamadas Livewire (`update`, hooks de upload), não só o GET.
- `update()` usa **allowlist** (`Settings::EDITABLE_FIELDS` via `Arr::only`):
  `template`, `subdomain`, `status`, `rss`… nunca são gravados pelo formulário.
- Uploads: validados com `mimes:jpg,jpeg,png,webp|max:2048` **antes** do
  `storeAs`; nome canônico `config/{campo}.{ext}` com extensão derivada do MIME
  (`jpeg`→`jpg`), nunca do nome enviado. A imagem antiga só é apagada se o
  caminho casar `config/{arquivo}.{jpg|jpeg|png|webp}` (não toca mídia de
  tenant).
- HTML: `App\Support\HtmlSanitizer::richText()` nos textos legais (Quill) e
  `googleMapsEmbed()` no mapa (só HTTPS + host `google.com`/`google.com.br` com
  path `/maps`); a view legal também sanitiza na saída (defesa em profundidade).
- E-mails com `not_regex:/[\r\n]/` e redes/domínio com `url:http,https`.

Coberto por `SettingsSecurityTest` (10) e `HtmlSanitizerTest` (6).

## Hardening de segurança (P1) — 2026-10-20

Complemento do P0 das Configurações, cobrindo uploads, e-mail, webhook,
paginação, exportação e cabeçalhos:

- **Uploads**: `App\Support\MediaFile::extension()` deriva a extensão do
  **MIME real** (allowlist jpg/jpeg/png/webp/gif, fallback `jpg`) e é usada por
  `LandingForm`, `MyProfile` e `StudentService`; nunca o nome enviado pelo
  cliente. O arquivo só é gravado depois da validação `image|max`.
- **E-mail de acesso**: `ProfileService::update()` **não** altera mais o e-mail.
  No painel, `MyProfile` abre um modal que envia um código de 6 dígitos ao
  endereço novo (`ProfileService::requestEmailChange`), confirma
  (`confirmEmailChange`) ou cancela (`cancelEmailChange`); o e-mail atual só
  muda após o código. `UpdateProfileRequest` não tem mais a regra de e-mail.
- **Webhook PagBank** (`PagBankWebhookController`): apenas `POST`; allowlist
  opcional `PAGBANK_WEBHOOK_IPS` (403); assinatura
  `X-Authenticity-Token` = SHA-256 de `{token}-{corpo bruto}` (401);
  idempotência por hash do corpo em cache de 1h; sem token configurado só
  aceita fora de produção. O estado continua sendo reconsultado no gateway.
- **Paginação**: `App\Support\Pagination::perPage()` centraliza o
  `per_page` (padrão 15, teto 100) nos nove controllers da API que paginam.
- **CSV**: `FinancialReportService::csvSafe()` prefixa com apóstrofo células
  iniciadas por `=`, `+`, `-`, `@`, TAB ou CR (CSV injection), aplicado a aluno
  e descrição.
- **Sessão/API**: `SESSION_SECURE_COOKIE` assume `true` em produção;
  `SANCTUM_EXPIRATION` (padrão 43200 min) expira tokens; `AuthController::logout`
  só revoga `PersonalAccessToken` (tolera `TransientToken`).
- **Cabeçalhos**: `SecurityHeaders` (web/api) envia `nosniff`,
  `X-Frame-Options: SAMEORIGIN`, `Referrer-Policy`,
  `Permissions-Policy` e HSTS sob HTTPS.
- **Dependências**: `composer audit` foi de 10 advisories (4 pacotes) para 4
  advisories em `laravel/framework` (Laravel 10 EOL; XSS da debug page, path
  confusion em signed URL e CRLF na regra `email`). Correção definitiva exige
  upgrade de major, que o usuário fará manualmente.

Coberto por `MyProfileTest` (17, inclui o fluxo com código),
`PagBankWebhookTest` (15, inclui GET 405, assinatura, IP e idempotência),
`MediaFileTest` (2), `SecurityHeadersTest` (1), `FinancialReportTest` (14) e
`StudentNotificationsTest` (9). Suíte **525 testes / 1986 assertions**;
`vendor/bin/pint --test` limpo.

## Integridade de pagamentos (P2) — 2026-10-20

O painel não pode "fabricar" dinheiro nem alterar histórico financeiro:

- **Cobrança online só é paga pelo gateway.** `PaymentService::store`/`update`
  recusam `channel=online` com `status=paid`; cobrança online paga aceita
  apenas correções de texto (descrição/observações), nunca valor, canal,
  situação ou `paid_at`. `PaymentIndex::markAsPaid` responde com toast de erro
  para online e o botão "marcar pago" não aparece nessas linhas.
- **Cobrança paga não muda de canal** (manual ↔ online) e o valor de qualquer
  cobrança paga é imutável. Manual paga pode voltar para pendente — nesse caso
  `paid_at` é limpo.
- **Cobrança em repasse é histórico**: `update`/`delete` recusam quando
  `payout_id` existe; `delete` também recusa qualquer cobrança paga. O form
  esconde "Salvar" e explica o motivo; o índice converte a exceção em toast.
- **Webhook**: `syncFromOrder`/`findByExternalReference` só enxergam cobranças
  online (referência forjada para manual é ignorada); o valor do pedido
  (`amount_cents`, de `charges[]`, do topo ou somando `items[]`) precisa bater
  com `payments.amount`; aprovação repetida **não recalcula** taxa/líquido com
  um `fee_percent` novo; `CANCELED` de Pix pendente cancela só o pedido atual.
  A marca de idempotência do controller só é gravada após sucesso — falha
  transitória pode ser reenviada (500), duplicata responde 200.
- **Repasses**: `request` trava ( `lockForUpdate` ) as cobranças e o saque
  pendente — dois cliques/abas não geram dois saques; `markPaid`/`cancel` só
  aceitam `requested` (não pagam/cancelam duas vezes). Estorno de cobrança que
  estava em saque **pendente** recalcula o saque ou o cancela se ele ficar
  vazio; em saque já pago o vínculo é mantido (não há clawback automático).
- **UI**: em cobrança online paga, valor/canal/situação/`paid_at` ficam
  desabilitados com aviso; em cobrança já repassada o formulário fica somente
  leitura e o botão de salvar some.

Coberto por `tests/Feature/Panel/PaymentIntegrityTest` (15) — criação online
paga bloqueada, manual paga ainda permitida, valor/canal/status de paga,
online paga com observação, exclusão de paga, cobrança em repasse, índice não
marca online, referência manual ignorada, valor divergente, aprovação
repetida sem recalcular taxa, estorno em saque pendente (recalcula/cancela),
canal de paga bloqueado, saque pago não é pago/cancelado de novo.

## Multi-tenancy (3 camadas)

O **tenant é o treinador** (`teachers.id`). Todo dado de domínio carrega `teacher_id`.

1. **Coluna `teacher_id`** em Students, TrainingPlans, TrainingWeeks, TrainingSessions,
   TrainingExecutions, StudentProgress, StudentNotes, Payments, Payouts.
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
                         └──1:N── payouts (repasse do líquido das cobranças online)
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
  perfil: treinador vê a **central de trabalho do próprio tenant**
  (`teacherDashboard()`); **admin vê a visão da plataforma** (`isPlatformAdmin()`
  → `DashboardService::platformDashboard()`): treinadores (total/ativos/novos
  no mês), alunos (total/ativos), **comissões** (`commissions`: retido no mês,
  total e nº de cobranças online pagas — a receita do SaaS), **repasses**
  (`payouts`: solicitado a pagar, nº de solicitações e já repassado), treinos
  hoje, pagamentos em atraso e acesso rápido (Treinadores, Novo treinador,
  Modalidades, Repasses, Configurações).
  - **Painel do treinador** (`teacherDashboard(?Teacher $teacher = null)`,
    teacher do usuário autenticado por padrão; consultas sob o global scope
    `tenant`): linha 1 = KPIs do dia (alunos ativos, treinos hoje/semana,
    planos ativos/pendentes, **A receber** com o valor em atraso);
    linha 2 = **dinheiro** (`money`): a receber (`open_amount` = pendentes +
    vencidas), em atraso (`overdue_amount`), recebido no mês, **saldo para
    repasse** (`PayoutService::balance`), já repassado e a **cota de cobranças
    manuais** (`PaymentService::manualCountThisMonth` / `MANUAL_MONTHLY_LIMIT`
    = 10) com barra de progresso e aviso de cota cheia; e as **listas acionáveis**
    (`overdue_payments`: até 5, aluno/vencimento/valor/dias em atraso;
    `today_sessions`: até 8, aluno/modalidade/duração/status).
  - `overdue` (vencidas do painel) = status `overdue` **ou** pendente com
    `due_date` no passado — mesma definição do dashboard da plataforma e usada
    também em `payments.overdue_count` (contrato da API do professor).
  - A API `GET /teacher/dashboard` devolve `teacherDashboard()` inteiro: as
    chaves novas (`money` e as listas) são **aditivas** — o contrato antigo
    (`students`/`plans`/`trainings`/`payments`) não mudou.
- **Agenda semanal do treinador** — `/admin/agenda` (`agenda.index`,
  `role:teacher`; o admin não tem tenant/agenda própria) → componente full-page
  `App\Livewire\Dashboard\Agenda` + `AgendaService::week()`:
  - recorte de **segunda a domingo** com navegação (anterior / **Hoje** /
    próxima) e badge "Semana atual" quando é a semana corrente; rótulo do
    período em pt-BR ("13 a 19 de outubro de 2026", com mês/ano quando a
    semana cruza o mês);
  - **7 cards por dia** (Hoje / Amanhã / Ontem / nome do dia) com os treinos
    daquele dia; cada treino mostra aluno (link para a ficha), modalidade,
    duração (segundos → "1h30"), distância (m → km), intensidade, badge de
    status e o atalho "Abrir o plano" (`plans.show`);
  - **KPIs do recorte**: treinos, alunos distintos, concluídos, planejados
    (com pulados/cancelados na linha de apoio) e duração/distância totais;
  - **filtros** por aluno, modalidade e status (com "Limpar filtros") e
    **estados vazios distintos** — semana sem treino × filtro sem resultado;
  - `AgendaService::week(CarbonInterface $startOfWeek, array $filters)` é
    leitura pura sob o global scope `tenant` (a view não faz query).
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
  - **Ficha do aluno** (`students.show`) redesenhada em Tailwind: card de
    identificação com faixa em gradiente da marca, **foto de perfil** (ou iniciais
    via `Student::initials()`) e badges (ativo/inativo + nível), KPIs rápidos
    (idade/altura/peso atual/peso meta), duas colunas de dados ("Dados pessoais"
    e "Dados físicos e objetivo") alinhadas com `flex justify-between`, cards de
    "Acesso no app" (código de convite) e "Plano ativo", observações e os últimos
    pagamentos em card de largura cheia — antes a tela usava `dl.row` com
    `col-sm-*` e desalinhava; o componente entrega `photoUrl`/`initials`/`age`/
    `availableDays` (mapa de dias em pt-BR no próprio componente).
  - **Ficha em PDF**: botão "Baixar PDF" na ficha → `GET /admin/alunos/{student}/pdf`
    (`students.pdf`) → `App\Http\Controllers\Students\StudentProfilePdfController`
    (fora do Livewire; `Gate::authorize('view', $student)`) → `StudentProfilePdfService`
    (`make`/`download`/`filename`) com a view `resources/views/pdf/student-profile.blade.php`
    (A4, `barryvdh/laravel-dompdf`): cabeçalho da marca com foto, contatos,
    dados pessoais/físicos, plano ativo, última avaliação, **pagamentos com badge**,
    observações e rodapé fixo (treinador/CREF + data de geração) repetido por página.
    - Foto e logo do treinador são **embutidas em base64** (redimensionadas com o
      Intervention: foto 460px em JPEG, logo 300px em PNG) — o PDF não depende de
      rede nem do `APP_URL`; imagem ausente/corrompida cai em iniciais/nome.
    - Isolamento: binding do `Student` pelo global scope + Policy (cross-tenant →
      404); admin da plataforma baixa de qualquer tenant.
    - `Document::format()` formata CPF/CNPJ na exibição (banco guarda só dígitos).
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
  - **Duplicar treino** (`TrainingPlanService::duplicate`/`duplicateWeek`):
    - `duplicate($source, $target, ?$name)` copia **plano → semanas → sessões →
      composição** para outro aluno (aceita o mesmo aluno); a cópia nasce como
      **rascunho** (publicar é ação explícita → o aluno só é avisado na virada
      para `ACTIVE`, via `TrainingPlanPublished`) e mantém as **datas originais**
      (reaproveita a periodização; ajustar o calendário é passo à parte na edição);
    - `duplicateWeek($source)` repete o **microciclo no fim do próprio plano**:
      recebe o próximo Nº, copia sessões/composição e desloca as datas para logo
      após a última semana (mesma duração, sessões da origem deslocadas junto);
    - em ambas as cópias `status` é reiniciado (`week` → `pending`, sessão →
      `planned`) e o `student_id` passa a ser o do **plano de destino** — execuções
      (histórico) não são copiadas;
    - no painel: botão "Duplicar plano" no cabeçalho do `PlanShow` (form inline com
      select do aluno destino — o `Student::find` sob o escopo do tenant barra
      outro treinador com erro de validação — e nome opcional, default "«nome» (cópia)")
      e o ícone de duplicar em cada semana (`wire:confirm`).
- **Pagamentos** — `app/Livewire/Dashboard/Payments/`:
  - `PaymentIndex` (busca, filtro por status, "marcar como pago", exclusão),
    `PaymentForm` (create/edit; aluno fixo na edição).
  - Reaproveita `StorePaymentRequest` (store e update, igual à API);
    `PaymentService` preenche/limpa `paid_at` conforme status.
- **Relatório financeiro** — `/admin/relatorios/financeiro`
  (`reports.financial`, `role:teacher`) → `App\Livewire\Dashboard\Reports\FinancialReport`
  + `FinancialReportService` (leitura pura, agregação em PHP): KPIs do período
  (recebido/líquido/taxas, a receber, em atraso, cobranças por canal), quebra
  **por mês** (só pagas) e **por aluno** (ranking por líquido), lista paginada
  (15/pág.) e **Exportar CSV** (`reports.financial.export`,
  `FinancialReportExportController` — `streamDownload` com BOM e separador `;`).
  Competência = **data de vencimento**; "em atraso" = status `overdue` ou
  pendente com vencimento passado. Item "Relatório Financeiro" no sidebar (só
  teacher).
- **Auditoria das notificações** — `/admin/notificacoes`
  (`notifications.index`, `role:teacher`) →
  `App\Livewire\Dashboard\Notifications\NotificationIndex` +
  `StudentNotificationService` (leitura pura sobre `notifications`): KPIs
  (no recorte/não lidas/lidas), resumo por tipo clicável, filtros (busca por
  **caminho JSON** — acha palavra acentuada —, aluno, tipo, situação, de/até) e
  tabela somente leitura do que o aluno recebeu (mensagem do treinador, plano
  publicado e os lembretes automáticos), com situação de leitura. Item
  "Notificações" no sidebar (só teacher). O envio continua em
  `/admin/alunos/{id}/mensagens`.
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
- **Observações do aluno** — card aninhado no `StudentShow`
  (`@livewire('dashboard.students.student-notes', ['student' => $student], ...)`):
  - `StudentNotes` — CRUD de `student_notes` (listagem paginada `notas`,
    criação/exclusão) com `StoreStudentNoteRequest`, `StudentNoteService`
    (ownership pelo aluno) e `StudentNotePolicy` (before() admin);
    visibilidade `private`/`shared` (`NoteVisibility`).
- **Mensagens do aluno** — página dedicada
  `/admin/alunos/{student}/mensagens` (`students.messages`), componente
  full-page `StudentMessages` (saiu da ficha do aluno a pedido do usuário —
  o card antigo ficava fora do padrão da listagem):
  - `StudentMessageService` (caminho único de escrita/consulta) — `send()`
    (notificação database `TeacherMessage` para o `user` do aluno; payload
    `data`: title, message, student_id, teacher_id, sent_by),
    `paginate()` (filtros `search` por LIKE na coluna `data`, que cobre
    título e corpo, e `status` todas/não lidas/lidas; 10 por página no
    paginator `mensagens`), `counts()` (total/não lidas/lidas) e
    `unreadCountsByStudent()` (badge do ícone na listagem de alunos);
  - validação por `SendMessageRequest` (`title` máx. 120/`message` máx.
    2000) via trait `ValidatesWithFormRequest`; aluno sem usuário vinculado
    → `ValidationException` amigável e página sem formulário (o modal nem
    é renderizado);
  - o envio é um **modal** Alpine (padrão do painel: `x-show` + `x-cloak` +
    escape/clique fora) aberto por `openModal()` (limpa o formulário e
    dispara `open-message-modal`) e fechado por `message-sent` no envio;
  - leitura pelo aluno: API `GET /api/v1/student/notifications` (app).
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
### Modelo de receita: comissão por cobrança (sem assinatura) — 2026-10-16

> **Mudança de modelo de negócio**: o SaaS **não cobra assinatura/mensalidade**
> do treinador. A receita é a **comissão** retida em cada cobrança que o
> treinador cria e recebe **online** (paga pelo aluno no app) — ver
> "Cobrança do aluno (manual x online)".

- **Removido por completo** (tabelas dropadas na migration
  `2026_10_16_100006_drop_subscriptions_and_saas_plans_tables`, que também
  apagou as migrations de origem):
  - catálogo **`saas_plans`** (e `SaasPlanSeeder`/factory), enum
    `BillingPeriod`, model `SaasPlan`, `SaasPlanService`, `SaasPlanPolicy`,
    `SaasPlanRequest` e as telas `SaasPlans\{SaasPlanIndex,SaasPlanForm}`;
  - **`subscriptions`** (e factory), enum `SubscriptionStatus`,
    `SubscriptionService`, `StoreSubscriptionRequest`,
    `Professores\ProfessorSubscription` (card do admin), índice
    `Subscriptions\SubscriptionIndex` em `/admin/assinaturas` e a tela
    self-service `/admin/assinatura` (`MySubscription`, `subscription.show`);
  - o **plano gratuito automático** (`SubscriptionService::assignFreePlan` em
    `TeacherService::store` e no auto-cadastro) e o **limite de alunos por
    plano** (`StudentService::ensureStudentQuota`) — o treinador pode ter
    quantos alunos quiser;
  - a **coluna "Assinatura"** do índice de treinadores virou **"A repassar"**
    (saldo de repasse do tenant, `withSum` de `net_amount` das cobranças
    online pagas sem `payout_id`), clicável para `/admin/plataforma/repasses`.
- **Dependências ajustadas**: `Teacher` perdeu a relação `subscription()`;
  `DashboardService` perdeu `subscriptionSummary()` e trocou os KPIs de
  assinatura do admin por `commissions`/`payouts`; `PagBank`/webhook só
  tratam `pay-{id}` (o roteamento por prefixo deixou de existir);
  `SiteController::home()` não passa mais catálogo de planos e a landing
  (`web/default/home.blade.php`) ganhou a seção **`#precos`** estática
  ("Sem mensalidade. Você paga só quando recebe." — 5% por cobrança online,
  sem taxa no registro manual, repasse do líquido via Pix).
- **Rota do admin renomeada**: `/admin/assinaturas/repasses` →
  **`/admin/plataforma/repasses`** (nome `payouts.admin` inalterado; o
  `/admin/repasses` continua sendo o saldo do treinador).


### Cobrança do aluno (manual x online) — 2026-10-16

> Aqui é o aluno pagando o treinador (o SaaS não cobra assinatura — ver
> "Modelo de receita").

- **Modelo decidido**: receber na conta PagBank **da plataforma** e repassar
  ao treinador (sem split). A plataforma retém uma **taxa configurável**
  (`config.fee_percent`, padrão **5%**) **apenas nas cobranças online**; o
  treinador recebe o líquido (`net_amount`) via **repasse (`Payout`)**.
  Pix primeiro; cartão depois.
- **Canais** (`payments.channel`, enum `PaymentChannel`):
  - `manual` (**padrão**): cobrança paga por fora (transferência/comprovante);
    **sem taxa** e **limite de 10 por mês por treinador**
    (`PaymentService::MANUAL_MONTHLY_LIMIT`, `ValidationException` em
    `channel` ao exceder — contagem por `created_at` do mês).
  - `online`: paga pelo app no gateway; gera `fee_amount`/`net_amount` ao
    confirmar.
- **Colunas novas em `payments`**: `channel`, `fee_amount`, `net_amount`,
  `gateway`, `gateway_order_id`, `gateway_status`, `payout_id`.
- **`PaymentService`** (caminho único de escrita, API e painel):
  `store/update/delete`; `checkoutPix(Payment, {cpf?})` gera o Pix na conta da
  plataforma (`pay-{id}` como referência, `qr_codes[]`), grava
  `gateway_order_id`/`gateway_status` e mantém o status pendente;
  `syncFromOrder`/`applyOrder` aplicam a notificação **reconsultando o pedido**
  (o corpo nunca é fonte de verdade): `approved` → paga + taxa + líquido;
  `REFUNDED/IN_DISPUTE/CANCELED` de uma paga → `cancelled` e limpa
  taxa/líquido. CPF do pagador: do request ou `students.document`.
- **`PagBankWebhookController`** trata **somente** a cobrança do aluno
  (`pay-{id}` → `PaymentService::syncFromOrder`); sem identificação na
  notificação, responde 200 e ignora.
- **API**: `POST /student/payments/{payment}/checkout` (aluno, cobrança online)
  → `{status, order_id, qr_code, qr_image}`; `payment.channel` no
  `StorePaymentRequest`; `PaymentResource` expõe `channel`, `channel_label`,
  `fee_amount`, `net_amount`, `gateway_status`, `payable`.
  - **Erros**: sem CPF no cadastro e sem `cpf` no payload →
    `ValidationException` em `cpf` (422 com `errors.cpf`, **antes** do gateway);
    `RuntimeException` do gateway (conexão/recusa) é convertida em **422 com
    `message`** no controller (não 500) para o app exibir ao aluno.
- **`Payout`** (`payouts`, enum `PayoutStatus` = requested/paid/cancelled):
  repasse do líquido ao treinador (`amount` bruto, `fee_total`, `net_amount`,
  `paid_at`, `notes`, `payout_account_id` + `destination` = **campos da conta
  usada, congelados no saque**; `teacher_id` **fora do fillable** — setado pelo
  service).
- **`PayoutService`** (fonte de verdade dos buckets; `hold` = `Config::
  payoutHoldDays()`, padrão 7 dias a partir do `paid_at`): `releaseThreshold`,
  `awaitingPayments` (**disponível** = online paga, `paid_at <= threshold` e
  `payout_id` nulo), `releasingPayments` (**a liberar**), `balance`,
  `releasingTotal`, `paidTotal`/`pendingTotal`/`openPayments`, `request`
  (exige **conta de recebimento** e **um saque pendente por vez**; agrupa as
  disponíveis num `Payout` `requested` com `destination`; sem saldo →
  `ValidationException`), `markPaid`, `cancel` (devolve as cobranças ao saldo)
  e `resolveAccount` (conta escolhida ou a padrão).
- **`PayoutAccountService` + `teacher_payout_accounts`** (`TeacherPayoutAccount`,
  enum `PayoutAccountType` = PIX/BANK_ACCOUNT + `BankAccountType` =
  CHECKING/SAVINGS): CRUD das formas de recebimento com conta **padrão**
  (`setDefault` desmarca as outras), normalização da chave Pix (CPF/CNPJ só
  dígitos, e-mail/telefone em minúsculas, `''` → null) e `delete()` que
  **zera o vínculo nos saques antigos** (`payout_account_id` → null,
  `withoutGlobalScope` + `update` — independente do driver) e **promove a
  próxima** conta a padrão. `description()` monta o rótulo do saque
  (`'Pix (CPF) · 123.456.789-00'`, `'Banco do Brasil · Ag 1234 · Conta
  56789-0'`). A migration `2026_10_18_100004` **migrou** `teachers.pix_key`/
  `pix_key_type` para lá e dropou as colunas do treinador.
- **`PayoutStatementService`** (extrato do treinador, leitura pura sob o global
  scope): junta **créditos** (cobranças online pagas, com status disponível /
  a liberar / repassado) e **débitos** (`Payout` requested = "Aguardando",
  paid = "Pago"; `cancelled` fica fora), com `filters()` (`type` = all/credit/
  debit, datas com **dia inteiro** nas pontas e troca de datas invertidas),
  presets de período, `totals()`, `entries()` ordenado por data e
  `paginate()`/`paginateCollection()` (pageName `extrato`).
- **Painel — treinador: área "Financeiro"** em `GET /admin/repasses`
  (`Payouts\PayoutIndex`, `role:teacher` — a rota antiga foi mantida; só o
  rótulo do menu mudou) com **3 abas** (`showTab`, `#[On('show-tab')]`):
  - **Saldo e saque** (`PayoutBalance`): KPIs (disponível / a liberar /
    aguardando / já recebido) com o prazo do saque, tabela das cobranças
    liberadas, select da conta de destino (default pré-selecionado), campo de
    observação, **"Solicitar saque"**, banner do saque pendente e estados
    vazios (sem cobranças liberadas × sem conta cadastrada).
  - **Extrato** (`PayoutStatement`): presets + filtros (tipo, de/até) com
    "Limpar filtros", KPIs (entradas/saídas/recebido no período), tabela de
    créditos/débitos com badge de situação e paginação de 15 por página.
  - **Formas de recebimento** (`PayoutAccounts`): lista das contas com badge
    **"Padrão"**, `description()`, ações (tornar padrão / editar / excluir com
    `wire:confirm`) e modal de criação/edição (Pix **ou** conta bancária, com
    validação condicional `required_if` em `PayoutAccountRequest`). A **chave
    Pix é formatada conforme o tipo** (CPF/CNPJ pontuados e telefone
    `(DDD) número`): máscara dinâmica no formulário (`x-mask` reativo segue o
    select do tipo) e prefill formatado no PHP (`PixKeyType::format()`); o
    banco continua guardando só dígitos/E.164, normalizados pelo
    `PayoutAccountService`. O CPF/CNPJ do titular usa a máscara
    `document` (alterna CPF↔CNPJ) e `App\Support\Document` formata o prefill.
  - Fecha o ciclo: **card "Repasse" saiu de "Meu perfil"** (as telas de perfil
    não têm mais nenhuma referência a Pix) e o antigo card único de saldo
    virou o painel completo.
- **Painel — admin**: `GET /admin/plataforma/repasses`
  (`Payouts\AdminPayoutIndex`, `role:admin`, `payouts.admin`): KPIs (a repassar
  / **a liberar** / já repassado — o "a liberar" agrega `withoutGlobalScope`,
  pois é visão de plataforma), filtro de status, coluna **"Destino"** e as
  ações **"Marcar como pago"** / **cancelar** (`wire:confirm`); o índice de
  treinadores mostra a repassar (liberado) com a linha secundária "+ R$ X a
  liberar".
- **Painel — cobranças**: o form de pagamento ganhou o **canal**
  (manual/online) e mostra a taxa/líquido ao editar uma cobrança online paga;
  o índice mostra badge de canal, coluna de líquido/taxa, filtro por canal e
  os KPIs "cobranças manuais este mês (n/10)" e "disponível para saque
  (online)" com a linha "a liberar".
- **Dashboard do treinador**: `money.awaiting_payout_amount` (liberado) e
  `money.awaiting_release_amount` (a liberar) — o card virou **"Saldo para
  saque"** com "A liberar" condicional.
- **Prazo de liberação**: campo "Prazo de liberação do saque (dias)" em
  Configurações (admin) → `config.payout_hold_days` (validado 0–90).
- **Taxa da plataforma**: campo "Taxa das cobranças online (%)" em
  Configurações (admin) → `config.fee_percent` (validado 0–100).
- **Quota antiga descontinuada**: o limite de alunos por plano
  (`SaasPlan`/`Subscription`) foi removido em 2026-10-16 junto com o modelo de
  assinatura (ver "Modelo de receita: comissão por cobrança"). A tela "Pagar"
  (Pix) do app está concluída (ver "Telas do aluno no app").

### Financeiro do treinador: saque, extrato e formas de recebimento — 2026-10-19

- **Saque com prazo de liberação**: o líquido de cada cobrança online paga
  entra em **a liberar** e só fica **disponível** depois de `config.
  payout_hold_days` dias (padrão **7**, 0 = sem retenção) contados do `paid_at`.
  Fecha o risco de o aluno pedir estorno (o estorno reverte a cobrança, e uma
  cobrança já sacada não seria recuperável).
- **Formas de recebimento** (`teacher_payout_accounts`): o treinador mantém
  **várias** chaves Pix/contas bancárias com **uma padrão**; o saque grava a
  conta usada (`payout_account_id`) e o rótulo congelado (`destination`) no
  `Payout` — excluir a conta **não apaga o histórico**. O antigo campo único
  `teachers.pix_key` foi migrado para a tabela nova e removido.
- **Área `Financeiro`** (`/admin/repasses`, 3 abas): ver os bullets em
  "Cobrança do aluno (manual x online)" (§ `PayoutService`,
  `PayoutAccountService`, `PayoutStatementService` e os dois painéis).
- **Um saque pendente por vez**: evita duas solicitações concorrentes sobre as
  mesmas cobranças; o cancelamento pelo admin devolve as cobranças ao saldo.
  Seleção por cobrança (saque parcial) fica para uma fase seguinte; hoje o
  saque leva **tudo o que já liberou**.
- **Adiados**: comprovante de repasse e notificação ao treinador quando o
  admin marca o saque como pago.

### Relatório financeiro do treinador — 2026-10-17

- **Página**: `GET /admin/relatorios/financeiro` (`reports.financial`,
  `role:teacher`) → `App\Livewire\Dashboard\Reports\FinancialReport`
  (full-page, `viewAny` de `Payment`), exclusiva do treinador (o admin apura a
  plataforma em comissões/repasses).
- **`FinancialReportService`** (leitura pura sob o global scope `tenant`;
  nenhuma consulta recebe `teacher_id`): `filters()` (normaliza, **troca datas
  invertidas**, ignora enum inválido e `student_id` não numérico, default =
  mês corrente), `periodRange()` (presets `month`/`last_month`/`last_30`/
  `year`), `query()`/`payments()`, `summary()`, `byMonth()`, `byStudent()`
  (top 10 por líquido) e `csvRows()` (gerador, com cabeçalho).
  - **Competência = `due_date`** em tudo (lista, KPIs e quebras): é o que o
    treinador enxerga como "a mensalidade de outubro", paga ou não.
  - **"Em atraso"** = status `overdue` **ou** pendente com `due_date` no
    passado (mesma definição do dashboard/`payments.overdue_count`); canceladas
    ficam fora de todos os totais.
  - Líquido de cobrança sem `net_amount` cai no valor cheio (manual não tem
    taxa).
  - Intervalo com o **dia inteiro** nas duas pontas (`"Y-m-d 00:00:00"` a
    `"Y-m-d 23:59:59"`): o driver guarda a data com hora, então o limite
    superior só com a data deixaria o vencimento do último dia fora (pegou o
    teste).
  - Agregação **em PHP** (`rows()` carrega só as colunas necessárias) —
    portável entre o SQLite dos testes e o MariaDB do runtime.
- **Tela**: KPIs (recebido + líquido/taxas, a receber, em atraso, cobranças
  com manual/online/pagas), presets de período + "Período personalizado",
  filtros **aluno/canal/situação** com "Limpar filtros", tabela **por mês**
  (só aparece com mais de um mês), ranking **por aluno**, lista de cobranças
  paginada (15/pág., com badge de canal e situação, valor/taxa/líquido e
  "Pago em") e dois estados vazios (período vazio × filtro sem resultado).
- **Exportação CSV**: `GET /admin/relatorios/financeiro/exportar`
  (`reports.financial.export`) → `FinancialReportExportController` (rota +
  controller **fora do Livewire** — download é um GET simples), com os mesmos
  filtros na query string, `streamDownload` linha a linha, **BOM UTF-8** e
  separador **`;`** (Excel pt-BR), valores com vírgula decimal e nome
  `relatorio-financeiro-{de}-a-{ate}.csv`.
- **Sidebar**: item "Relatório Financeiro" (ícone `chart-bar`) só para
  `isTeacher()`.

### Auditoria das notificações enviadas — 2026-10-17

- **Página**: `GET /admin/notificacoes` (`notifications.index`, `role:teacher`)
  → `App\Livewire\Dashboard\Notifications\NotificationIndex` (full-page,
  `viewAny` de `Student`) — **somente leitura**: mostra o que os alunos
  receberam (mensagem do treinador, plano publicado e os lembretes automáticos
  de cobrança/treino). O envio continua na página de mensagens do aluno.
- **`StudentNotificationService`** (leitura pura): `TYPES` (classe FQCN do
  `type` => rótulo pt-BR, na ordem da lista), `STATUS_OPTIONS` (todas/não
  lidas/lidas), `filters()` (normaliza: tipo fora da lista e aluno não
  numérico ignorados, **datas invertidas trocadas**), `query()`/`paginate()`
  (15/pág., mais recentes primeiro, anexando o `student` de cada aviso),
  `counts()`, `typeCounts()`, `studentOptions()` e `typeLabel()`.
  - **Isolamento**: a notificação é entregue ao **usuário** do aluno, então a
    consulta parte dos `user_id` dos alunos do tenant (global scope) — a query
    nasce de `notifiable_id IN (...)`; `teacher_id` nunca vem do request.
    Avisos de e-mail (`EmailChangeCode`, canal `mail`) não entram: não passam
    pelo canal database.
  - **Busca por caminho JSON** (`data->title` / `data->message` com `LIKE`):
    a coluna guarda o JSON com `\u00e7` (unicode escapado), então um LIKE
    direto na coluna não acharia palavra acentuada ("cobrança") — o caminho
    JSON compara o valor já decodificado e funciona em SQLite e MariaDB.
  - Intervalo de datas com o **dia inteiro** nas duas pontas
    (`00:00:00`/`23:59:59`): o driver guarda `created_at` com hora.
- **Tela**: KPIs (avisos no recorte / aguardando leitura / já lidas), resumo
  por tipo (chips que aplicam o filtro), filtros (busca, aluno, tipo, situação,
  de/até) com "Limpar filtros", tabela com data-hora, aluno (link para a
  ficha), rótulo do tipo, título/corpo do aviso e situação de leitura (com a
  data da leitura) + paginação e dois estados vazios (sem aviso × filtro sem
  resultado).
- **Sidebar**: item "Notificações" (ícone `bell`) só para `isTeacher()`.
- **Testes**: `tests/Feature/Panel/NotificationAuditTest` (13) — listagem do
  tenant (mensagem real via `StudentMessageService` + lembrete), isolamento de
  outro treinador (nem no filtro de alunos), filtros (aluno/tipo/situação/
  busca com acento/período), indicadores e resumo por tipo, paginação
  (16 → 15+1), "Limpar filtros", estado vazio, filtros inválidos ignorados e
  acesso (guest → login, teacher 200 com link no menu, admin 403). Rota no
  `PanelRoutesSmokeTest` (24 rotas de teacher).
- **Gotcha de teste**: os rótulos dos tipos aparecem **sempre** no `<select>`
  de filtros — asserções de filtro devem usar o **corpo do aviso** (único por
  linha), nunca o título/rótulo do tipo (ex.: `assertDontSee('Cobrança a
  vencer')` falha porque o rótulo está na lista de opções). E `str_replace` do
  `html(true)` do Livewire remove o snapshot — comparar `html(true)` com
  `str_contains` é o mesmo caminho do `assertSee`.

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
- **Troca de e-mail/senha no painel**: o `ProfileService` ganhou também um
  fluxo de troca de e-mail **com código** (`requestEmailChange`/
  `confirmEmailChange`/`cancelEmailChange`, 15 min e 5 tentativas) e
  `changePassword` (derruba as outras sessões), que hoje são consumidos pela
  **API do aluno** (`/api/v1/student/profile/email*` e `/profile/password`).
  Na página do painel o e-mail continua sendo editado direto pelo próprio
  usuário (não há código ali).

### Cadastro obrigatório do treinador (primeiro acesso)

- **Problema**: o parceiro de pagamentos recusa a cobrança sem a
  identificação do pagador (`customer.tax_id` = CPF) — era o `400` `40001
  "must not be null"` ao gerar o **Pix** (o cartão já coletava o CPF no
  formulário). Treinador sem CPF no cadastro = cobrança online (Pix) impossível.
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
- **Como o aluno fica sabendo (decidido em 2026-10-16)**: a API **não tem
  websocket** — o app **consulta** (`GET /student/notifications` + o contador de
  não lidas do `GET /student/dashboard`) e, com o app aberto, o **polling**
  mantém o resumo fresco; com o app **fechado**, o aviso será por **push (FCM)**.
  - Polling no app (`mobile/lib/core/polling/live_refresh.dart`): widget
    `LiveRefresh` roda um `onTick` a cada **30 s** (badge/contadores do resumo,
    montado no `MaterialApp.router` do `app.dart`, só com sessão ativa) e a cada
    **20 s** na tela de notificações (mescla o que chegou no topo da lista);
  - para o ciclo quando o app sai do 1º plano e faz um tick imediato na volta;
  - os controllers ganharam `refreshSilently()`: **não** passam por `loading`
    (nada de skeleton piscando), não mexem em paginação e ignoram falha de rede
    (mantêm o que está na tela);
  - **FCM (próximo passo)**: precisa de um projeto Firebase (do usuário) —
    `google-services.json` no Android, service account no backend, tabela de
    tokens por dispositivo e um canal de notification no `via()`.
  - **Marcar como lida** (2026-10-17): a API ganhou
    `POST /student/notifications/{notification}/read` (individual) e
    `POST /student/notifications/read-all` (lote) — antes `read_at` nunca era
    preenchido e o badge do sininho só crescia. No app, **tocar no aviso** marca
    aquela notificação e o botão no topo da tela marca todas; os dois atualizam a
    lista na hora (sem recarregar) e revalidam o resumo (`refreshSilently()`), o
    que faz o contador de não lidas cair. A consulta é feita sobre
    `auth()->user()->notifications()` → notificação de outro usuário responde
    `404` (isolamento por usuário, não por tenant).
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
- **Mensagem do treinador** (`StudentMessages`, painel): notificação
  `TeacherMessage` gravada no ato do clique.
- **Como o aluno recebe hoje (sem push, sem tempo real)**: `BaseNotification::via()`
  devolve **só `['database']`** — não há broadcast (websocket) nem FCM, e a fila é
  `sync`. Ou seja: o registro é criado na hora (mensagem/plano) ou pelo cron das
  07:00 (cobrança/treino), mas o app do aluno **descobre quando consulta**:
  `GET /api/v1/student/notifications` (tela de notificações, com pull-to-refresh)
  e o contador de não lidas do `GET /student/dashboard` (badge da home). Se o app
  estiver aberto na home quando o treinador envia algo, o badge só muda depois de
  uma atualização/entrada na tela. Para "tempo real" as opções são: (a) *polling*
  curto no app enquanto está aberto; (b) websockets (Laravel Reverb + cliente
  Dart no app); (c) **FCM** (push com o app fechado) — este exige projeto Firebase
  e credencial de servidor no backend.

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
  permitiu migrar as views legadas sem tocar em bindings. A escala
  **`col-md-1..12` é completa** (2026-10-19: faltavam `col-md-1`, `col-md-7`,
  `col-md-8`, `col-md-10` e `col-md-11` — um `col-md-8` sem regra encolhia o
  input até não caber texto); `col-*`/`col-sm-*`/`col-lg-*` existem conforme o
  uso das views (rodar um scan antes de introduzir uma coluna nova). Cores de
  fundo legadas (`bg-danger` etc.) NÃO existem — usar utilitários Tailwind.
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
  - `StudentPaymentCheckoutTest` (9) — cobrança do aluno pela API: Pix da
    própria cobrança online, 403 para a cobrança de um colega do mesmo
    treinador e 404 cross-tenant (o binding resolve dentro do tenant),
    checkout de cobrança manual recusado (422), validação do CPF, aluno sem
    CPF no cadastro precisa enviá-lo (422 em `errors.cpf`, sem chamar o
    gateway), falha do gateway vira 422 com `message` (não 500), criação de
    cobrança online pelo treinador (resposta com `payable`) e bloqueio da 11ª
    cobrança manual do mês.
  - `StudentProfileTest` (8) — perfil do aluno: salva CPF (aceita máscara) e
    data de nascimento, `show` devolve os dois campos, menor de 18 é recusado
    (422), quem completa 18 hoje passa (borda), formato inválido é recusado,
    CPF exige 11 dígitos, os campos do treinador (e-mail, pesos, `active`) são
    ignorados e o nome é sincronizado com `users.name` (home do app).
  - `StudentEmailChangeTest` (12) — troca de e-mail com código: pedido guarda
    `pending_email` (e-mail atual segue valendo) e envia o código com hash para
    o endereço novo, confirmação atualiza `users.email` + `students.email` e
    limpa o pedido, código errado conta tentativa, código expirado e tentativas
    esgotadas exigem novo pedido, e-mail em uso (conta ou outro aluno) e igual
    ao atual recusados, endereço tomado entre pedido e confirmação recusado,
    cancelamento limpa o pedido e endpoints exigem autenticação.
  - `StudentPasswordChangeTest` (7) — troca de senha com a atual (e login com a
    nova), senha atual errada/ausente recusada, nova senha exige 8 caracteres e
    confirmação, as outras sessões são revogadas mantendo o token que pediu,
    treinador recebe 403 e endpoint exige autenticação.
- Suíte do painel em `tests/Feature/Panel`:
  - `PanelAccessTest` — redirecionamento de guest, acesso por papel (teacher/admin
    200, student 403), login por papel (student/inativo bloqueados), registro cria tenant.
  - `StudentCrudTest` — listagem isolada por tenant, criação/edição com Form Requests,
    email duplicado, 404 cross-tenant na rota, AuthorizationException no Livewire,
    exclusão lógica, admin sem CRUD de aluno, smoke HTTP das 4 rotas.
  - `StudentPdfTest` (8) — guest → login, download da ficha (200, `application/pdf`,
    nome `ficha-aluno-{slug}.pdf` e assinatura `%PDF`), treinador de outro tenant 404,
    admin baixa de qualquer tenant, conteúdo (dados pessoais/físicos, dias, plano
    ativo, pagamentos, observações, treinador/CREF), iniciais sem foto, foto
    embutida como `data:image/jpeg;base64` e link/`Baixar PDF`/foto na ficha.
  - `MediaDiskTest` (3, em `tests/Feature/Storage`) — disco `public` local por
    default, URL do R2 com o prefixo `R2_ROOT` e `StudentResource` com a URL
    remota (sem o host da requisição) — ver "Armazenamento de mídia".
  - `TrainingPlanCrudTest` — isolamento de planos, criação com aluno do tenant,
    semanas duplicadas, sessões (modalidade obrigatória), 404 cross-tenant, smoke.
  - `PlanDuplicateTest` (9) — duplicação de plano para outro aluno (semanas,
    sessões e composição copiadas; origem intacta), cópia nasce rascunho e **não
    notifica** o aluno destino, `status` das sessões reiniciado (concluída →
    planejada), nome default "«nome» (cópia)" com o campo vazio, aluno de outro
    treinador recusado na validação (nada criado), aluno obrigatório, duplicar
    semana (novo Nº no fim + datas deslocadas, sessões/composição junto), semana
    de outro tenant bloqueada (ModelNotFound) e controles na tela (form inline
    com o select de alunos e o reset ao fechar).
  - `PaymentCrudTest` — isolamento, `paid_at` automático, marcar como pago,
    404 cross-tenant, smoke das 3 rotas.
  - `PanelRoutesSmokeTest` — as 25 rotas do domínio `/admin/*` retornam 200
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
  - `StudentMessagesTest` (18) — página de mensagens: envio (payload da
    notificação + eventos `open-message-modal`/`message-sent`), validação,
    listagem/paginação (11 → 10+1), filtros (busca por título e corpo,
    situação lida/não lida, clearFilters e reset da página), indicadores
    (`counts`), card removido da ficha, aluno sem usuário (com o modal fora
    do DOM), acesso (guest/papel/404 cross-tenant/AuthorizationException),
    admin preservando ownership e badge de não lidas na listagem de alunos
    (sem vazar entre tenants).
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
  - `PlatformDashboardTest` (3) — dashboard da plataforma do admin (KPIs de
    comissão e repasses, agregação cross-tenant via service — manual paga não
    gera comissão, treinador vê o próprio painel).
  - `TeacherDashboardTest` (7) — painel do treinador: dinheiro (a receber incl.
    vencidas, atraso pela definição status `overdue`/pendente vencida, recebido
    no mês excluindo o mês anterior, **disponível × a liberar** pela soma do
    `net_amount` das online pagas conforme o `payout_hold_days`, cota manual
    n/10 com aviso quando cheia) e listas (limite
    de 5 cobranças em atraso / 8 treinos de hoje, vazios amigáveis), sem
    vazamento de outro tenant e com o admin ainda na visão da plataforma.
  - `AgendaTest` (9) — agenda semanal: listagem da semana do tenant agrupada
    por dia (KPIs, rótulo do período, treino de outra semana fora), navegação
    entre semanas (próxima/Hoje/anterior), filtros (aluno/modalidade/status/
    limpar), estados vazios (semana vazia × filtro sem resultado), sem
    vazamento de outro tenant, rota exclusiva do treinador (guest redirect,
    teacher 200, admin 403) e os totais do `AgendaService` (sessões/alunos/
    status/duração/distância, 7 dias começando na segunda).
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
  - `ProfileCompletionGateTest` (11) — treinador sem CPF/celular é
    redirecionado de qualquer rota do painel para `/admin/completar-cadastro`,
    banner com a lista do que falta, só celular faltando também bloqueia,
    treinador completo usa o painel, admin não é afetado, salvar manda para o
    painel (CPF gravado só com dígitos) e sem CPF/celular não grava nada,
    CPF inválido é rejeitado com mensagem, marcação obrigatória do treinador e
    guest → login.
  - **Services** (`tests/Feature/Services`): `PagBankServiceTest` (13) —
    `isConfigured`, chave pública (env, `GET /public-keys/card` + cache,
    `null` em falha), payload de cartão (centavos, parcelas, `tax_id`,
    `notification_urls` só com HTTPS), erro 400 → `RuntimeException`
    neutra, pedido Pix pelo **`qr_codes[]`** (payload conferido + regressão
    contra `charges[].payment_method.pix`), QR lido também de
    `charges[].qr_code`, pedido sem copia e cola, `fetchOrder`
    normalizado/nulo e mapa de mensagens de recusa.
  - `PaymentServiceTest` (13) — cobrança do aluno no service: canal `manual`
    por padrão, quota de 10 manuais/mês (11ª bloqueada com mensagem, online
    não conta, reseta no mês seguinte), `checkoutPix` (referência `pay-{id}`,
    centavos, `tax_id` do cadastro, CPF avulso, manual recusada e **sem CPF
    falha antes do gateway** sem gravar nada), `applyOrder` (taxa padrão 5% e
    `config.fee_percent` customizado, líquido, reversão de estorno limpa
    taxa/líquido, referência desconhecida ignorada) e `syncFromOrder` usando
    o `gateway_order_id`.
  - `PayoutFlowTest` (15) — financeiro do treinador e canal de cobrança:
    disponível só com online paga **fora do prazo** de liberação (manuais,
    pendentes, recentes e de outro tenant ficam fora), solicitar saque (agrupa
    as cobranças, bruto/taxas/líquido, **destino congelado** com a conta usada,
    observação), saque **sem conta** de recebimento recusado, **um saque
    pendente por vez**, sem saldo → toast de erro sem gravar, admin marca como
    pago, admin cancela (cobranças voltam ao saldo), 403 cruzado (treinador no
    admin / admin no treinador), form cria cobrança online (payable), quota
    manual erra no form sem gravar e o índice mostra canal + KPIs.
  - `PayoutAccountTest` (14) — formas de recebimento: criação de Pix (chave
    normalizada, conta nasce padrão) e de conta bancária, validação
    condicional por tipo, campos do modal por tipo (Pix × conta bancária),
    troca da conta padrão (desmarca a anterior), `description()` do Pix/conta
    nos rótulos (telefone formatado), edição com prefill **formatado** por
    tipo (CPF/CNPJ/telefone e documento do titular), exclusão com promoção da
    próxima a padrão, saque anterior **preservado** com o destino congelado
    (`payout_account_id` nulo), 404 cross-tenant e estado vazio.
  - `PayoutHoldTest` (14) — prazo de liberação: `payoutHoldDays` (default 7,
    configurável inclusive 0 = sem retenção, fallback de config ausente),
    bucket a liberar × disponível no service e no componente de saldo, saque
    considera só as liberadas, extrato com entradas/saídas/totais/filtros de
    tipo e período/paginação/"Limpar filtros"/presets, transição disponível →
    repassado após o saque e as 3 abas do `PayoutIndex` (`#[On('show-tab')]`).
  - `FinancialReportTest` (14) — relatório financeiro: página só com as
    cobranças do tenant (com controles), KPIs do período (recebido/líquido/
    taxas, a receber, em atraso, manual × online × pagas, cancelada fora),
    pendente com vencimento passado conta como atraso, quebras por mês e por
    aluno (ranking por líquido), filtros de canal/situação/aluno,
    presets de período + "Limpar filtros", datas invertidas trocadas e enum
    inválido ignorado no service, paginação 15/pág., exportação CSV (nome do
    arquivo, cabeçalho, valor com vírgula, status "Pago", sem dados de outro
    tenant e respeitando filtro de situação), datas no cabeçalho, data inválida
    caindo no mês corrente e acesso (guest → login, teacher 200, admin 403 nas
    duas rotas).
  - `NotificationAuditTest` (13) — auditoria das notificações enviadas:
    listagem do tenant (mensagem real via service + lembrete), isolamento de
    outro treinador, filtros de aluno/tipo/situação/busca acentuada/período,
    indicadores e resumo por tipo, paginação, "Limpar filtros", estado vazio,
    filtros inválidos ignorados e acesso (guest → login, teacher 200, admin
    403) — ver "Auditoria das notificações enviadas".
  - **Total: 525 testes / 1986 assertions**.
  - **Webhooks**: `PagBankWebhookTest` (15) — pedido pago marca a cobrança do
    aluno como paga com a taxa da plataforma (5% e `config.fee_percent`
    customizado), `WAITING` só registra o status do gateway, `REFUNDED` de
    cobrança paga reverte (cancelled + taxa/líquido nulos) e de pendente é
    ignorado, notificação só com `reference_id` usa o `gateway_order_id`
    guardado, referência desconhecida é ignorada, sem identificação não
    consulta o gateway (200), GET agora responde 405, assinatura/IP validados
    e corpo repetido é idempotente.
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
    (guard splash → login → home). As telas do aluno são **filhas de `/`**:
    navegar por elas (mesmo com `go`) mantém a home na pilha — ver
    "Navegação (pilha)" abaixo.
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

### Telas do aluno no app (itens 3 a 7)

- **Feature** `mobile/lib/features/student/`: models (`dashboard`,
  `training_session`, `training_plan` — que também define `TrainingWeek` —,
  `session_item`, `execution`, `sport`, `payment`, `notification`, `progress`,
  `profile`), `student_repository.dart` (todos os endpoints `/student/*`, com
  `PagedResult<T>`) e `student_providers.dart` (`PagedController` abstrato com
  `loadMore`/`refresh` + um controller por recurso).
- **Telas**: painel (KPIs), treinos (filtros em pílulas por status), detalhe do
  treino
  (itens, start/complete/skip, form de métricas com FC máxima e esforço 0–10),
  calendário, planos, progresso, pagamentos, notificações e **Meu perfil**
  (nome, telefone, **CPF com máscara**, **data de nascimento com seletor
  limitado a 18+**, gênero, altura, dias + foto).
- **Maioridade (18+)**: o formulário exige a data de nascimento (validação
  local com `ageInYears`) e a API valida de novo
  (`UpdateStudentProfileRequest`: `birth_date` `aaaa-mm-dd` + 18 anos). O CPF é
  gravado só com dígitos e passa a alimentar o Pix das cobranças online.
- **Idioma**: `flutter_localizations` com `locale: pt-BR` no `MaterialApp`
  (calendário e textos do Material em português).
- **Formatadores** (`formatters.dart`): `formatDuration`, `formatDistance`,
  `formatDate` (`dd/mm/aaaa`), `isoDate` (`aaaa-mm-dd`) e `ageInYears`;
  máscara de CPF em `cpf_input_formatter.dart` (`CpfInputFormatter`/`formatCpf`/
  `cpfDigits`, também usada no campo de CPF do Pix).
- **Pagamentos (2026-10-16)** — `payments_screen.dart` +
  `pix_payment_sheet.dart`: cobrança online em aberto (`payable`) mostra o
  botão **"Pagar com Pix"** → bottom sheet que chama
  `POST /student/payments/{id}/checkout` e desenha o QR Code **localmente**
  (`qr_flutter`, a partir do copia e cola — sem depender da imagem do gateway),
  com "Copiar código Pix" (`Clipboard`) e "Já efetuei o pagamento" (recarrega a
  lista; quem confirma é o webhook). Sem CPF no cadastro a API responde
  `422 errors.cpf` e o sheet revela o campo de CPF e refaz a chamada; falha do
  gateway (422 `message`) aparece como mensagem para o aluno.
- **Conta no app (2026-10-16)** — o acesso fica em uma **tela própria**
  (`/conta`, `AccountScreen` → "Conta e acesso", `account_screen.dart`),
  separada de "Meu perfil" (dados de treino) para não misturar cadastro com
  credenciais; chega-se lá por uma linha "E-mail e senha" no fim do perfil:
  - card **"E-mail de acesso"**: `showEmailChangeSheet` (`email_change_sheet.dart`)
    pede o endereço novo → a API envia o código de 6 dígitos → o aluno confirma
    (com "Reenviar código"). Com troca pendente o card mostra o aviso âmbar
    (`pending_email` da sessão) com "Digitar código" e "Cancelar troca";
  - card **"Alterar senha"** (`_PasswordCard`): senha atual + nova (8+) +
    confirmação, com olho de mostrar/ocultar, `PUT /student/profile/password`;
  - `User.pendingEmail` (do `UserResource`) e `AuthController.reload()`
    (recarrega a sessão sem passar por `loading`) fazem a home refletir o nome
    e o e-mail novos logo após salvar — o nome do aluno vive em `students` e em
    `users.name` (`StudentService::update` sincroniza os dois).
- **Navegação (pilha)**: no `app_router.dart` as telas do aluno são **filhas da
  home** (`routes:` aninhadas em `/`, com `treinos/:id` filha de `treinos`), e as
  telas navegam com `context.push(...)`. Assim toda tela empilhada tem a home
  embaixo: a `AppBar` mostra o **botão de voltar** e o "voltar" do Android
  retorna tela a tela em vez de **sair do app** (bug de rota plana + `context.go`,
  que substituía a pilha). Regressão em `test/router_navigation_test.dart`
  (home → Meu painel → Ver cobranças e o `popRoute` do sistema).
- **Qualidade**: `dart analyze` limpo; `flutter test` = **69/69**
  (`auth_repository_test` 6, `student_repository_test` 17, `formatters_test` 8,
  `live_refresh_test` 14, `screens_render_test` 17, `screens_layout_test` 2,
  `brand_mark_test` 2, `router_navigation_test` 3 — helpers/fakes compartilhados
  em `test/support/`: `api_test_helpers.dart`, `fake_auth.dart`,
  `fake_student_repository.dart`).

### Ícone e abertura do app (2026-10-16)

- **Arte**: `mobile/assets/icon/app_icon.png` — quadrada 1024×1024, fundo creme
  `#FCFAEB` (a arte é horizontal, 73%×52% do canvas, centrada).
- **Geração**: `flutter_launcher_icons` (dev dependency, configuração no fim do
  `pubspec.yaml`) — `cd mobile && dart run flutter_launcher_icons` escreve os 5
  `mipmap-*/ic_launcher.png`, o **ícone adaptativo** Android
  (`mipmap-anydpi-v26/ic_launcher.xml` + `drawable-*/ic_launcher_foreground.png`
  + `values/colors.xml` com `ic_launcher_background`) e os 16 ícones do iOS.
- **Ícone adaptativo**: o Android recorta ~61% do centro (máscara), então o
  foreground entra com `adaptive_icon_foreground_inset: 18` — a arte fica com
  raio ~29% (zona segura = 30,5%) e o fundo do ícone é o creme da própria arte,
  o que deixa a emenda do inset invisível.
- **Abertura sem flash**: `drawable/launch_background.xml` e
  `drawable-v21/launch_background.xml` usam `@color/splash_background`
  (`values/splash.xml` = `#FCFAEB`, arquivo separado porque `values/colors.xml`
  é reescrito pelo gerador) e a `SplashScreen` do Flutter mostra a própria arte
  (`Image.asset`) sobre a mesma cor.
- **Gotcha do gerador (iOS)**: o `flutter_launcher_icons` substitui por regex
  **qualquer** linha com `ASSETCATALOG` do `ios/Runner.xcodeproj/project.pbxproj`
  por `= AppIcon` — isso corrompe
  `ASSETCATALOG_COMPILER_GENERATE_SWIFT_ASSET_SYMBOL_EXTENSIONS` (espera
  `YES`/`NO`). Depois de rodar o gerador, conferir e voltar as linhas para
  `YES` (foi o que aconteceu aqui).

### Design system do app (2026-10-16)

- **Paleta** (`mobile/lib/core/theme/app_colors.dart`): a escala `brand` é a
  **mesma do painel web** (`tailwind.config.js` — laranja `brand-400/500` até o
  marrom `brand-950`), com neutros **quentes** (creme `#FCFAEB`, fundo
  `#F8F5F1`, texto `#241A15`) e tons semânticos (success/warning/danger/info com
  variante suave). O `headerGradient` (950→700) é o topo das telas principais.
- **Tema** (`app_theme.dart`, aplicado em `app.dart`): `ColorScheme.fromSeed`
  ajustado nos papéis que importam (primary `brand-600` = AA com branco),
  tipografia **Plus Jakarta Sans** e, em um só lugar, os componentes: AppBar
  chapada com fio de borda, card 18px com borda `#EDE5DC`, botões (altura 50,
  raio 14), campos preenchidos com foco laranja, chips em pílula, bottom sheet
  com drag handle, snackbar escuro flutuante, badge, checkbox/switch e o
  `DatePicker` com cabeçalho na cor da marca.
- **Fontes**: `mobile/assets/fonts/PlusJakartaSans-{Regular,Medium,SemiBold,Bold,ExtraBold}.ttf`
  — instâncias estáticas geradas da **variável** do Google Fonts com `fontTools`
  (`instantiateVariableFont` + `pyftsubset`), subset latim + acentos pt-BR:
  ~35 KB por peso (~170 KB no total). Licença OFL em `assets/fonts/OFL.txt`.
- **Widgets compartilhados** (`mobile/lib/core/widgets/`) — é o vocabulário que
  as telas usam, para não repetir estilo:
  - `AppCard` (card branco clicável com ripple), `SectionHeader` (título + ação);
  - `StatCard` (KPI com ícone em `IconTile`), `IconTile`, `StatusChip` +
    `AppTone`/`toneForStatus()` (status cru da API → tom e cor);
  - `EmptyState` / `ErrorState` / `RefreshableState` (estado vazio/erro ainda
    permite o "puxar para atualizar") / `AlertCard` (pendências e avisos);
  - `SkeletonBox` / `SkeletonCardList` (carregamento em blocos pulsantes, no
    lugar do spinner central);
  - `BrandMark` (logomarca: a arte de `assets/images/logo.png` — a imagem
    horizontal da marca — sobre uma placa branca arredondada, porque o cabeçalho
    é escuro e o fundo da arte é branco; a **altura** é o parâmetro (`height`,
    padrão 62) e a largura acompanha a proporção da imagem; **sem** a arte no
    bundle o widget cai no ícone + nome "Super Personal", ou seja o nome só
    aparece quando não há logomarca) e
    `SportIcon` (ícone e tom por modalidade, normalizando acentos do nome que vem
    da API) e `AppHeader` (gradiente da marca com cantos inferiores arredondados).
- **Telas redesenhadas** (todas as telas do aluno já usam o vocabulário):
  - **Home** (cabeçalho em gradiente com avatar/notificações/atualização, três
    números do dia, alerta de cobrança em aberto, atalhos em grade de 2 colunas
    e um card de dica);
  - **Meu painel** (KPIs 2x2 clicáveis + atalhos);
  - **Meus treinos** (filtros em pílulas — no lugar do `TabBar` — e cards com
    ícone da modalidade, meta "Hoje/Amanhã/Ontem · duração · distância" e selo de
    status);
  - **Detalhe do treino** (hero em gradiente com modalidade/status/meta,
    composição numerada, recado do treinador e as ações
    iniciar/concluir/pular; o formulário de execução virou um card rolável com
    "sensação" em `ChoiceChip`);
  - **Pagamentos** (resumo do que está em aberto no topo, cards com selo de
    status e botão "Pagar com Pix" só nas cobranças `payable`) e o
    **`pix_payment_sheet`** (QR branco, copiar código, campo de CPF quando a API
    responde `errors.cpf`);
  - **Notificações** (cards com ícone/tom por tipo e data "Hoje/Ontem/dd/mm/aaaa"
    — continua com o `LiveRefresh` de 20 s);
  - **Meus planos** (plano → semana → sessão em `ExpansionTile` encadeados,
    abrindo o detalhe do treino), **Calendário** (agrupado por dia com
    "Hoje/Amanhã/Ontem" e contagem) e **Meu progresso** (cards com as métricas em
    pílulas e bottom sheet com os detalhes/observações);
  - **Login** (logomarca — `assets/images/logo.png` — sobre placa branca,
    "Seus treinos na palma da mão", o formulário em card e a **versão do app** no
    rodapé, lida do bundle nativo com `package_info_plus`; o plugin é opcional em
    runtime: sem ele a linha simplesmente não aparece), **Meu perfil** (cartão de
    avatar com câmera, "Dados pessoais" e "Rotina de treino" em cards separados) e
    **Conta e acesso** (e-mail com troca por código e troca de senha).
  - **Números em pt-BR**: use `formatDecimal()` (`78,5`) e `formatDistance()`
    (`12,3 km`) em `formatters.dart` — `toStringAsFixed` sozinho imprime com
    ponto.
- **Regra**: tela **não** hardcodeia cor/tamanho — se faltar algo, acrescente um
  token no tema.
- **Testes de tela**: `mobile/test/screens_render_test.dart` (uma passada por
  tela: renderiza, mostra os textos principais e não estoura exceção) e
  `mobile/test/screens_layout_test.dart` (regressão do `Row`/`stretch`), sobre
  os fakes de `mobile/test/support/` (`fake_auth.dart`,
  `fake_student_repository.dart`).
- **Gotcha — tela em branco (bug real de 2026-10-16)**: `Row` com
  `crossAxisAlignment: CrossAxisAlignment.stretch` **dentro de área de altura
  livre** (item de `ListView`/`Column` em `ListView`) quebra o layout — a altura
  máxima é infinita, o eixo cruzado do `stretch` não tem o que esticar e o
  framework estoura (`!_debugDoingThisLayout` + cascata de `RenderBox was not
  laid out`); nenhum frame fecha e **a tela fica só com o fundo do scaffold**
  (parece "app que não abre", e o logcat não mostra o erro — ele aparece no
  console do Dart/`flutter attach`). Conserto: envolver a linha em
  `IntrinsicHeight` (mantém os cards da mesma linha com a mesma altura) ou usar
  `CrossAxisAlignment.start`. Coberto por `mobile/test/screens_layout_test.dart`
  (Home e Meu painel, com os providers dublados) — o teste falhava com ~6.000
  exceções antes do conserto.

## Landing do SaaS — home pública `/` (2026-10-13)

- **`SiteController::home()`** reescrito: a home deixou de ser o stub "Home
  Page" do starter e virou a **landing de marketing do SaaS** (view
  standalone `web/default/home.blade.php`, server-rendered, mesmo DNA
  visual da landing dos treinadores — dark `brand-950`, títulos Barlow
  Condensed, laranja `brand-*`; SEO via `Seo` com título/descrição de
  conversão). **Não** passa catálogo de planos (o modelo de assinatura foi
  removido em 2026-10-16 — ver "Modelo de receita: comissão por cobrança").
- **Seções** (uma página, sem enrolação): header sticky (logo, âncoras
  Por que/Como funciona/Preço/Dúvidas, Entrar + "Criar conta grátis") →
  hero "Menos planilha. Mais treino." com **mockup do app em CSS puro**
  (sem fotos de banco: card de sessão, aderência, notificação) → chips de
  modalidades → grade de 6 diferenciais → "Como funciona" (3 passos
  numerados) → seção **`#precos`** estática ("Sem mensalidade. Você paga só
  quando recebe."): 3 cards (Cadastro grátis com alunos ilimitados / Cobrança
  online com **5% por cobrança paga** e "Você recebe 95%" / Fora do app sem
  taxa no registro manual) → seção do app do aluno → FAQ (`<details>` nativo,
  6 perguntas) → CTA final → rodapé. CTAs apontam para `route('register')`
  (auto-cadastro de treinador) e `route('login')`. Sem Alpine/JS na página
  (o toggle de período saiu junto com o catálogo de planos).
- **Robustez**: a seção de preço é estática (não depende de banco);
  `$config->app_name` em todos os títulos.
- **Testes**: `tests/Feature/Web/HomeLandingTest` (5) — guest 200 com
  CTAs/seções, links de registro/login, preço por comissão (`5%`,
  "Você recebe 95%", `#precos`), ausência de qualquer menção ao modelo antigo
  ("Mais popular", "Assinar", "troque de plano"), head SEO + ano.

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
- **Fix da sidebar**: o item "Minha assinatura" (que ficava aninhado no
  submenu "Biblioteca") virou item de topo junto com "Minha página pública" —
  depois **removido** com o fim do modelo de assinatura (2026-10-16).
- **Loop de crescimento**: rodapé da landing traz
  "Quero minha página assim → criar conta de treinador" (`/auth/register`).

## Fora do escopo atual

- ~~Product pass do blog~~ **concluído por completo** (2026-10-09): além das
  páginas do painel (2026-10-08), models/rotas/migrations do blog do site
  público foram removidos. `Settings` e o site público (home) permanecem.
- Próximos passos da Fase 3: rodar o app no aparelho/visualizar login,
  telas do aluno (dashboard, planos, treinos com start/complete, progresso)
  sobre a API já existente.
- Swagger (docs manuais), refresh token, integrações Strava/Garmin.
- **Cobrança do aluno — concluído**: telas do painel (lançar online/manual com
  a quota, taxa/líquido, saldo e repasses do treinador, confirmação pelo
  admin), tela "Pagar" (Pix) no app e **fim do modelo de assinatura** (a quota
  antiga por `SaasPlan`/`Subscription` foi removida em 2026-10-16 — ver
  "Modelo de receita: comissão por cobrança").
- Cartão de crédito na cobrança **do aluno** (hoje só Pix) — a cobrança online
  ainda não aceita cartão.
- Push FCM (notificações hoje são canal `database`; entra na etapa mobile).
