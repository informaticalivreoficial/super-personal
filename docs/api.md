# API REST — SportPlan v1

> Base URL: `http://laravel.test/api/v1`
> Arquitetura e regras de tenant: `docs/architecture.md`.

## Convenções

- **Formato**: JSON (`Content-Type: application/json`).
- **Autenticação**: Bearer token (Sanctum) em `Authorization`.
- **Wrap**: respostas com dados usam `{"data": ...}`; listas paginadas
  `{"data": [...], "links": {...}, "meta": {...}}`.
- **Unidades**: distância em metros (int), duração em segundos (int),
  pace/alvos em string.
- **Erros**: `401` sem token/token inválido · `403` papel errado ou sem
  permissão · `404` recurso fora do tenant · `422` validação
  (`{"message": ..., "errors": {campo: [msgs]}}`).
- **Rota fora do papel**: middleware `role:teacher` / `role:student` → `403`.

## Status codes

| Código | Quando |
|---|---|
| 200 | leitura/atualização concluída |
| 201 | criação (também usado ao concluir treino, pois cria a execução) |
| 204 | remoção (`DELETE`) |
| 401 / 403 / 404 / 422 / 429 | conforme tabela acima |

---

## Autenticação

### `POST /auth/login` — throttled (10/min)

```json
{ "email": "joao@superpersonal.test", "password": "password" }
```

**200**
```json
{
  "token": "2|...",
  "token_type": "Bearer",
  "user": { "id": 82, "name": "João Silva", "email": "...", "role": "teacher", "teacher": {...} }
}
```

Erros: `401` credenciais inválidas · `403` conta inativa · `429` muitas tentativas.

### `POST /auth/student-register` — throttled (10/min) · registro do aluno no app

Fluxo de convite: o treinador cadastra o aluno (painel ou API) → o sistema gera
um `invite_code` (8 letras/números sem ambíguos) → o treinador compartilha →
o aluno cria a própria conta. Nome e e-mail vêm do cadastro
(`students.email` é o login no app).

```json
{ "invite_code": "K7M2P9QX", "password": "senha-segura-123", "password_confirmation": "senha-segura-123" }
```

**201** — mesmo shape do login: `{token, token_type, user}` com
`user.role = "student"` (o app sai logado). O código é consumido
(`invite_code` → `null`) e `students.user_id` passa a apontar para a conta.

Erros `422` (`invite_code`): código inválido, código já consumido, aluno já
possui conta, aluno inativo ou e-mail já em uso por outra conta.
(`password`): senha com menos de 8 caracteres ou confirmação divergente.

### `POST /auth/logout` — revoga o token atual → `200`

### `GET /auth/me` — usuário autenticado → `200 {data: {...}}`

---

## Aluno (`role:student`) — futuro app Android

> Pré-requisito de acesso: o aluno se registra em `POST /auth/student-register`
> com o `invite_code` recebido do treinador e, a partir daí, usa
> `POST /auth/login` com `students.email` + senha definida no cadastro.

| Método | Rota | Descrição |
|---|---|---|
| GET | `/student/dashboard` | resumo: treinos de hoje/próximos, plano ativo, pagamentos pendentes, notificações não lidas |
| GET | `/student/profile` | perfil do aluno |
| PUT | `/student/profile` | atualiza perfil (name, phone, gender, height, document, birth_date, available_days) |
| PUT | `/student/profile/password` | troca a senha (`current_password` + `password` + `password_confirmation`) |
| POST | `/student/profile/email` | pede a troca do e-mail (`email`) — envia código ao endereço novo |
| POST | `/student/profile/email/confirm` | confirma a troca (`code` de 6 dígitos) |
| DELETE | `/student/profile/email` | cancela a troca de e-mail em andamento |
| GET | `/student/training-plans` | planos ativos do aluno (paginado) |
| GET | `/student/training-plans/{plan}` | plano com semanas, sessões e itens |
| GET | `/student/calendar` | sessões ordenadas por `scheduled_date` (paginado) |
| GET | `/student/trainings` | sessões do aluno, com `execution` anexada (paginado) |
| GET | `/student/trainings/{session}` | sessão única + itens + execução |
| POST | `/student/trainings/{session}/start` | inicia (idempotente; reaproveita execução em andamento) |
| POST | `/student/trainings/{session}/complete` | conclui; cria/atualiza execução e marca sessão `completed` |
| POST | `/student/trainings/{session}/skip` | marca sessão como pulada |
| POST | `/student/trainings/{session}/execution` | registro manual de métricas (mesmo ciclo do complete) |
| GET | `/student/progress` | histórico de progresso (paginado) |
| GET | `/student/payments` | pagamentos do aluno (paginado; `payable` indica a cobrança online em aberto) |
| POST | `/student/payments/{payment}/checkout` | gera o Pix da cobrança **online** (`{cpf?}` opcional; retorna `qr_code` copia e cola + `qr_image`) |
| GET | `/student/notifications` | notificações (database) |
| POST | `/student/notifications/{notification}/read` | marca **uma** notificação do aluno como lida (retorna o recurso com `read_at`; id de outro usuário → `404`) |
| POST | `/student/notifications/read-all` | marca **todas** as não lidas como lidas (`{"data":{"marked": N}}`) |

##### Notificações: marcar como lida

O app marca como lida **ao tocar no aviso** (individual) e pelo botão "marcar
todas como lidas" (lote). A busca parte de `auth()->user()->notifications()`,
então o aluno só alcança as próprias notificações — a de outro usuário (ou um id
que não seja UUID) responde `404`. Com a leitura, o contador de não lidas do
`GET /student/dashboard` cai.

##### Erros do checkout Pix

- `422` com `errors.cpf` — o aluno não tem CPF no cadastro (`students.document`)
  e não enviou `cpf`: o app pede o dado e refaz a chamada.
- `422` com `message` — falha do gateway (conexão/recusa); é erro de negócio,
  não `500` (o app exibe a mensagem).
- `422` com `errors.payment` — cobrança manual, cancelada ou já paga.
- `403`/`404` — cobrança de outro aluno (mesmo tenant) / de outro treinador.

A confirmação do pagamento chega pelo webhook do gateway (`pay-{id}`): o aluno
recarrega `GET /student/payments` e vê o novo status (não há endpoint de
reconsulta pelo app). A cobrança **manual** não é pagável no app (`payable`
fica `false` e o Pix é recusado).

##### Perfil do aluno (`PUT /student/profile`)

- `name` é sincronizado com a conta (`users.name`): o app mostra a home a
  partir da própria sessão.
- `document` (CPF, com ou sem máscara) é validado com **11 dígitos** quando
  informado — é o `tax_id` enviado ao gateway no Pix.
- `birth_date` no formato `aaaa-mm-dd`; **menor de 18 anos é recusado**
  (`422 errors.birth_date`). O app exige a data no formulário de perfil.
- `email`, `teacher_id`, pesos/avaliação e `active` são ignorados (não estão
  nas regras) — o e-mail só muda pelo fluxo com código abaixo.

##### Troca do e-mail de acesso (com validação)

1. `POST /student/profile/email` com `{email}` → `200 {message, pending_email}`
   e um **código de 6 dígitos** é enviado para o endereço novo (`users.email`
   continua o antigo). Erros: `422 errors.email` (e-mail igual ao atual ou já
   em uso por outra conta/aluno).
2. `POST /student/profile/email/confirm` com `{code}` → `200 {message, user}`
   (a `UserResource` do aluno, já com o e-mail novo). A confirmação atualiza
   `users.email` e o cadastro do aluno (`students.email`).
   Erros: `422 errors.code` (código errado, expirado ou tentativas esgotadas).
3. `DELETE /student/profile/email` desiste do pedido pendente.

Regras: código vale **15 minutos**, com no máximo **5 tentativas** erradas
(depois disso é preciso pedir outro); ficam suspensas as rotas
`POST|PUT profile/email*` e `profile/password` com throttle de 6/min; `GET
/auth/me` devolve `pending_email` (null quando não há troca em andamento).

##### Troca de senha (`PUT /student/profile/password`)

- Exige `current_password` (conferida com `Hash::check`) — erro em
  `422 errors.current_password`;
- `password` com `min:8` + `confirmed` — erro em `422 errors.password`;
- a troca **revoga as outras sessões** (tokens Sanctum), mantendo o token que
  fez o pedido.

### Payload de execução (`complete` / `execution`)

```json
{
  "duration": 3600,
  "distance": 12000,
  "average_heart_rate": 150,
  "max_heart_rate": 175,
  "average_pace": "5:00",
  "average_power": 210,
  "perceived_effort": 7,
  "feeling": "good",
  "notes": "Treino concluído."
}
```

Todos os campos são opcionais (mínimo aceito é a conclusão em si).

---

## Treinador (`role:teacher`)

### Dashboard

| Método | Roto | Descrição |
|---|---|---|
| GET | `/teacher/dashboard` | alunos, planos ativos, treinos (hoje/semana/pendentes), pagamentos |

### Alunos

| Método | Rota | Descrição |
|---|---|---|
| GET | `/teacher/students` | lista paginada (filtros: `search`, `status` — ver controller) |
| POST | `/teacher/students` | cria aluno (**sem `user_id`** — gera `invite_code` para o aluno se registrar) |
| GET | `/teacher/students/{student}` | aluno único |
| PUT | `/teacher/students/{student}` | atualiza |
| DELETE | `/teacher/students/{student}` | soft delete |

Campos aceitos (store/update): `name`, `email` (único por tenant), `phone`,
`birth_date`, `gender`, `document`, `profile_photo`, `height`,
`initial_weight`, `current_weight`, `target_weight`, `goal`,
`fitness_level`, `training_experience`, `available_days[]`, `status`, `notes`.

### Planos de treino

| Método | Rota | Descrição |
|---|---|---|
| GET | `/teacher/students/{student}/training-plans` | planos do aluno |
| POST | `/teacher/students/{student}/training-plans` | cria plano (`name`, `start_date` obrigatórios) |
| GET | `/teacher/training-plans/{plan}` | plano com semanas/sessões/itens |
| PUT | `/teacher/training-plans/{plan}` | atualiza |
| DELETE | `/teacher/training-plans/{plan}` | remove (cascata: semanas→sessões→itens/execuções) |
| POST | `/teacher/training-plans/{plan}/weeks` | cria semana (`week_number`, `start_date`, `end_date`) |
| POST | `/teacher/training-weeks/{week}/sessions` | cria sessão (`sport_id`, `title`, `scheduled_date`) |
| PUT | `/teacher/training-sessions/{session}` | atualiza sessão |
| DELETE | `/teacher/training-sessions/{session}` | remove sessão |

### Execuções, progresso e pagamentos

| Método | Rota | Descrição |
|---|---|---|
| GET | `/teacher/students/{student}/executions` | execuções do aluno |
| GET | `/teacher/students/{student}/progress` | histórico de progresso |
| POST | `/teacher/students/{student}/progress` | registra medição (`recorded_at` obrigatório + peso, gordura, medidas, FC, FTP, paces) |
| GET | `/teacher/students/{student}/payments` | pagamentos (filtro `status`) |
| POST | `/teacher/students/{student}/payments` | cria (`description`, `amount`, `due_date`, `channel` = `manual`\|`online` + `status`, `payment_method`, `paid_at`, `notes`) |
| PUT | `/teacher/payments/{payment}` | atualiza |
| DELETE | `/teacher/payments/{payment}` | remove |

#### Canais de cobrança

- `manual` (**padrão**): paga por fora (transferência); o treinador confirma. Limite de **10 por mês** por treinador (`422` no `channel` ao exceder).
- `online`: paga pelo app no gateway da plataforma. A plataforma retém a **taxa** (`fee_amount`, `config.fee_percent`, padrão 5%) e o treinador recebe `net_amount` via repasse. `payable: true` enquanto `pending`/`overdue`.

O recurso `payment` expõe `channel`, `channel_label`, `fee_amount`, `net_amount`, `gateway_status` e `payable`.

---

## Exemplos cURL

```bash
# Login
TOKEN=$(curl -s -X POST http://laravel.test/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"joao@superpersonal.test","password":"password"}' | jq -r .token)

# Lista de alunos (treinador)
curl -s http://laravel.test/api/v1/teacher/students \
  -H "Authorization: Bearer $TOKEN"

# Concluir treino (aluno)
curl -s -X POST http://laravel.test/api/v1/student/trainings/1/complete \
  -H "Authorization: Bearer $ALUNO_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"duration":3600,"distance":12000,"perceived_effort":7}'
```

## Dados de desenvolvimento (seeders)

| Perfil | E-mail | Senha |
|---|---|---|
| Treinador | `joao@superpersonal.test` | `password` |
| Alunos | `marcos@`, `ana@`, `pedro@superpersonal.test` | `password` |
| Admin | `ADMIN_EMAIL` do `.env` | definido no seeder |
