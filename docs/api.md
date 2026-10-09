# API REST — Super Personal v1

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

Fluxo de convite: o professor cadastra o aluno (painel ou API) → o sistema gera
um `invite_code` (8 letras/números sem ambíguos) → o professor compartilha →
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
> com o `invite_code` recebido do professor e, a partir daí, usa
> `POST /auth/login` com `students.email` + senha definida no cadastro.

| Método | Rota | Descrição |
|---|---|---|
| GET | `/student/dashboard` | resumo: treinos de hoje/próximos, plano ativo, pagamentos pendentes, notificações não lidas |
| GET | `/student/profile` | perfil do aluno |
| PUT | `/student/profile` | atualiza perfil (name, phone, gender, height, available_days) |
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
| GET | `/student/payments` | pagamentos do aluno (paginado) |
| GET | `/student/notifications` | notificações (database) |

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

## Professor (`role:teacher`)

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
| POST | `/teacher/students/{student}/payments` | cria (`description`, `amount`, `due_date` + `status`, `payment_method`, `paid_at`, `notes`) |
| PUT | `/teacher/payments/{payment}` | atualiza |
| DELETE | `/teacher/payments/{payment}` | remove |

---

## Exemplos cURL

```bash
# Login
TOKEN=$(curl -s -X POST http://laravel.test/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"joao@superpersonal.test","password":"password"}' | jq -r .token)

# Lista de alunos (professor)
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
| Professor | `joao@superpersonal.test` | `password` |
| Alunos | `marcos@`, `ana@`, `pedro@superpersonal.test` | `password` |
| Admin | `ADMIN_EMAIL` do `.env` | definido no seeder |
