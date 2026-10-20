<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <title>Ficha do aluno — {{ $student->name }}</title>
    <style>
        /* Dompdf: CSS 2.1 (tabelas/floats). Sem flex/grid/gradiente. */
        @page {
            margin: 28pt 28pt 46pt 28pt;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 9.5pt;
            color: #2b211a;
        }

        table {
            border-collapse: collapse;
        }

        /* --- Cabeçalho ---------------------------------------------------- */
        .hero-left {
            background: #431300;
            color: #fff;
            padding: 16pt 16pt 14pt;
            vertical-align: middle;
        }

        .hero-right {
            background: #431300;
            padding: 16pt 16pt 14pt;
            text-align: right;
            vertical-align: middle;
        }

        .hero-strip {
            height: 4pt;
            background: #ffa506;
        }

        .logo-plate {
            display: inline-block;
            background: #fff;
            padding: 4pt 7pt;
            margin-bottom: 8pt;
        }

        .logo {
            height: 24pt;
        }

        .brand-fallback {
            margin-bottom: 8pt;
            font-size: 10pt;
            font-weight: bold;
            letter-spacing: 1.4pt;
            text-transform: uppercase;
            color: #ffd68f;
        }

        .kicker {
            font-size: 7.5pt;
            letter-spacing: 2pt;
            text-transform: uppercase;
            color: #ffd68f;
        }

        .student-name {
            margin: 3pt 0 5pt;
            font-size: 21pt;
            font-weight: bold;
            line-height: 1.15;
            color: #ffffff;
        }

        .hero-meta {
            font-size: 8.5pt;
            color: #f5e8da;
        }

        .chip {
            display: inline-block;
            padding: 1.5pt 6pt;
            font-size: 7.5pt;
            font-weight: bold;
            border-radius: 7pt;
        }

        .chip-on {
            background: #dcfce7;
            color: #166534;
        }

        .chip-off {
            background: #f3f4f6;
            color: #4b5563;
        }

        .photo {
            width: 96pt;
            height: 96pt;
            border: 3pt solid #ffffff;
        }

        .photo-fallback {
            width: 96pt;
            height: 96pt;
            border: 3pt solid #ffffff;
            background: #823c01;
            color: #ffe8c1;
            font-size: 30pt;
            font-weight: bold;
            text-align: center;
            line-height: 90pt;
        }

        /* --- Faixa de contato --------------------------------------------- */
        .contact td {
            background: #fffaf3;
            border-bottom: 1pt solid #e7e0d8;
            padding: 7pt 12pt;
            vertical-align: top;
        }

        .c-label {
            font-size: 7pt;
            letter-spacing: 1pt;
            text-transform: uppercase;
            color: #8a7a6d;
        }

        .c-value {
            margin-top: 2pt;
            font-size: 9pt;
            font-weight: bold;
            color: #2b211a;
        }

        /* --- Seções ------------------------------------------------------- */
        .section {
            margin: 14pt 0 7pt;
            padding-bottom: 3pt;
            border-bottom: 1pt solid #e7e0d8;
            font-size: 8pt;
            font-weight: bold;
            letter-spacing: 1.4pt;
            text-transform: uppercase;
            color: #823c01;
        }

        .layout td {
            vertical-align: top;
        }

        .box {
            border: 0.8pt solid #e7e0d8;
            background: #ffffff;
            padding: 9pt 11pt;
        }

        .box-title {
            margin-bottom: 6pt;
            font-size: 9pt;
            font-weight: bold;
            color: #823c01;
        }

        .info {
            width: 100%;
        }

        .info td {
            padding: 4pt 0;
            border-bottom: 0.6pt solid #f3efe9;
            vertical-align: top;
        }

        .info .k {
            width: 42%;
            padding-right: 8pt;
            font-size: 8.5pt;
            color: #8a7a6d;
        }

        .info .v {
            font-size: 9pt;
            font-weight: bold;
            color: #2b211a;
        }

        .note {
            margin-top: 6pt;
            font-size: 8.5pt;
            line-height: 1.45;
            color: #4b423a;
        }

        /* --- Métricas ----------------------------------------------------- */
        .metrics td {
            width: 25%;
            padding: 8pt 6pt;
            border-right: 0.8pt solid #e7e0d8;
            text-align: center;
            vertical-align: middle;
        }

        .metrics td:last-child {
            border-right: 0;
        }

        .m-value {
            font-size: 13pt;
            font-weight: bold;
            color: #823c01;
        }

        .m-label {
            margin-top: 2pt;
            font-size: 7pt;
            letter-spacing: 1pt;
            text-transform: uppercase;
            color: #8a7a6d;
        }

        /* --- Tabela de pagamentos ----------------------------------------- */
        .data {
            width: 100%;
        }

        .data th {
            background: #fdf4e9;
            border-bottom: 0.8pt solid #e7e0d8;
            padding: 5pt 7pt;
            font-size: 7.5pt;
            letter-spacing: 0.8pt;
            text-transform: uppercase;
            color: #823c01;
            text-align: left;
        }

        .data td {
            padding: 5pt 7pt;
            border-bottom: 0.6pt solid #f3efe9;
            font-size: 8.8pt;
        }

        .data .right {
            text-align: right;
        }

        .data .center {
            text-align: center;
        }

        .badge {
            display: inline-block;
            padding: 1.5pt 6pt;
            font-size: 7pt;
            font-weight: bold;
            border-radius: 7pt;
        }

        .badge-paid {
            background: #dcfce7;
            color: #166534;
        }

        .badge-pending {
            background: #fef3c7;
            color: #92400e;
        }

        .badge-overdue {
            background: #fee2e2;
            color: #991b1b;
        }

        .badge-cancelled {
            background: #f3f4f6;
            color: #4b5563;
        }

        .empty {
            padding: 8pt 0;
            font-size: 8.5pt;
            color: #8a7a6d;
        }

        .keep {
            page-break-inside: avoid;
        }

        /* --- Rodapé (repetido em todas as páginas) ------------------------ */
        .footer {
            position: fixed;
            left: 0;
            right: 0;
            bottom: -30pt;
            padding-top: 5pt;
            border-top: 0.8pt solid #e7e0d8;
            font-size: 7.5pt;
            color: #9a8c80;
        }

        .footer .right {
            text-align: right;
        }
    </style>
</head>
<body>

    <div class="footer">
        <table width="100%">
            <tr>
                <td>
                    {{ $teacher?->name ?? config('app.name') }}
                    @if ($teacher?->cref)
                        · CREF {{ $teacher->cref }}
                    @endif
                </td>
                <td class="right">
                    Ficha gerada em {{ $generatedAt->format('d/m/Y \à\s H:i') }}
                </td>
            </tr>
        </table>
    </div>

    {{-- Cabeçalho: marca do treinador + identificação do aluno + foto --}}
    <table width="100%">
        <tr>
            <td class="hero-left" width="66%">
                @if ($teacherLogo)
                    <div class="logo-plate"><img src="{{ $teacherLogo }}" class="logo" alt=""></div>
                @else
                    <div class="brand-fallback">{{ $teacher?->name ?? config('app.name') }}</div>
                @endif

                <div class="kicker">Ficha do aluno</div>
                <div class="student-name">{{ $student->name }}</div>

                <div class="hero-meta">
                    <span class="chip {{ $student->active ? 'chip-on' : 'chip-off' }}">
                        {{ $student->active ? 'Ativo' : 'Inativo' }}
                    </span>
                    &nbsp; {{ $levels[$student->fitness_level?->value] ?? 'Sem nível' }}
                    @if ($age)
                        &nbsp;·&nbsp; {{ $age }} anos
                    @endif
                    @if ($student->started_at)
                        &nbsp;·&nbsp; aluno desde {{ $student->started_at->format('d/m/Y') }}
                    @endif
                </div>
            </td>
            <td class="hero-right" width="34%">
                @if ($photo)
                    <img src="{{ $photo }}" class="photo" alt="Foto de {{ $student->name }}">
                @else
                    <div class="photo-fallback">{{ $initials }}</div>
                @endif
            </td>
        </tr>
    </table>
    <div class="hero-strip"></div>

    <table class="contact" width="100%">
        <tr>
            <td width="34%">
                <div class="c-label">E-mail</div>
                <div class="c-value">{{ $student->email ?: '—' }}</div>
            </td>
            <td width="33%">
                <div class="c-label">Telefone</div>
                <div class="c-value">{{ $student->phone ?: '—' }}</div>
            </td>
            <td width="33%">
                <div class="c-label">Objetivo</div>
                <div class="c-value">{{ $student->goal ?: '—' }}</div>
            </td>
        </tr>
    </table>

    {{-- Dados pessoais e físicos lado a lado --}}
    <table class="layout" width="100%">
        <tr>
            <td width="49%">
                <div class="section">Dados pessoais</div>
                <div class="box keep">
                    <table class="info">
                        <tr>
                            <td class="k">Nascimento</td>
                            <td class="v">{{ $student->birth_date?->format('d/m/Y') ?? '—' }}</td>
                        </tr>
                        <tr>
                            <td class="k">Sexo</td>
                            <td class="v">{{ $genders[$student->gender?->value] ?? '—' }}</td>
                        </tr>
                        <tr>
                            <td class="k">Documento</td>
                            <td class="v">{{ \App\Support\Document::format($student->document) ?: '—' }}</td>
                        </tr>
                        <tr>
                            <td class="k">Início dos treinos</td>
                            <td class="v">{{ $student->started_at?->format('d/m/Y') ?? '—' }}</td>
                        </tr>
                        <tr>
                            <td class="k">Dias disponíveis</td>
                            <td class="v">{{ $availableDays ? implode(', ', $availableDays) : '—' }}</td>
                        </tr>
                    </table>
                </div>
            </td>
            <td width="2%"></td>
            <td width="49%">
                <div class="section">Dados físicos</div>
                <div class="box keep">
                    <table class="info">
                        <tr>
                            <td class="k">Altura</td>
                            <td class="v">{{ $student->height ? $student->height.' cm' : '—' }}</td>
                        </tr>
                        <tr>
                            <td class="k">Peso inicial</td>
                            <td class="v">{{ $student->initial_weight ? number_format((float) $student->initial_weight, 1, ',', '.').' kg' : '—' }}</td>
                        </tr>
                        <tr>
                            <td class="k">Peso atual</td>
                            <td class="v">{{ $student->current_weight ? number_format((float) $student->current_weight, 1, ',', '.').' kg' : '—' }}</td>
                        </tr>
                        <tr>
                            <td class="k">Peso meta</td>
                            <td class="v">{{ $student->target_weight ? number_format((float) $student->target_weight, 1, ',', '.').' kg' : '—' }}</td>
                        </tr>
                        <tr>
                            <td class="k">Nível / experiência</td>
                            <td class="v">
                                {{ $levels[$student->fitness_level?->value] ?? '—' }}
                                @if ($student->training_experience)
                                    · {{ $student->training_experience }}
                                @endif
                            </td>
                        </tr>
                    </table>
                </div>
            </td>
        </tr>
    </table>

    {{-- Plano ativo e última avaliação --}}
    <table class="layout" width="100%">
        <tr>
            <td width="49%">
                <div class="section">Plano ativo</div>
                <div class="box keep">
                    @if ($activePlan)
                        <div class="box-title">{{ $activePlan->name }}</div>
                        <table class="info">
                            <tr>
                                <td class="k">Objetivo</td>
                                <td class="v">{{ $activePlan->goal ?: '—' }}</td>
                            </tr>
                            <tr>
                                <td class="k">Período</td>
                                <td class="v">
                                    {{ $activePlan->start_date?->format('d/m/Y') ?? '—' }}
                                    @if ($activePlan->end_date)
                                        → {{ $activePlan->end_date->format('d/m/Y') }}
                                    @endif
                                </td>
                            </tr>
                        </table>
                    @else
                        <div class="empty">Nenhum plano de treino ativo.</div>
                    @endif
                </div>
            </td>
            <td width="2%"></td>
            <td width="49%">
                <div class="section">Última avaliação</div>
                <div class="box keep">
                    @if ($latestProgress)
                        <table class="metrics" width="100%">
                            <tr>
                                <td>
                                    <div class="m-value">{{ $latestProgress->weight ? number_format((float) $latestProgress->weight, 1, ',', '.') : '—' }}</div>
                                    <div class="m-label">Peso (kg)</div>
                                </td>
                                <td>
                                    <div class="m-value">{{ $latestProgress->body_fat ? number_format((float) $latestProgress->body_fat, 1, ',', '.') : '—' }}</div>
                                    <div class="m-label">Gordura (%)</div>
                                </td>
                                <td>
                                    <div class="m-value">{{ $latestProgress->resting_heart_rate ?: '—' }}</div>
                                    <div class="m-label">FC repouso</div>
                                </td>
                                <td>
                                    <div class="m-value">{{ $latestProgress->ftp ?: '—' }}</div>
                                    <div class="m-label">FTP (W)</div>
                                </td>
                            </tr>
                        </table>
                        <div class="note">
                            Avaliação de {{ $latestProgress->recorded_at?->format('d/m/Y') ?? '—' }}
                            @if ($latestProgress->notes)
                                · {{ $latestProgress->notes }}
                            @endif
                        </div>
                    @else
                        <div class="empty">Nenhuma avaliação registrada.</div>
                    @endif
                </div>
            </td>
        </tr>
    </table>

    {{-- Últimos pagamentos --}}
    <div class="section">Últimos pagamentos</div>
    <div class="box keep">
        @if ($recentPayments->count())
            <table class="data">
                <thead>
                    <tr>
                        <th>Descrição</th>
                        <th>Vencimento</th>
                        <th class="right">Valor</th>
                        <th class="center">Situação</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($recentPayments as $payment)
                        <tr>
                            <td>{{ $payment->description }}</td>
                            <td>{{ $payment->due_date?->format('d/m/Y') ?? '—' }}</td>
                            <td class="right">R$ {{ number_format((float) $payment->amount, 2, ',', '.') }}</td>
                            <td class="center">
                                @php
                                    $status = $payment->status->value;
                                    $badge = match ($status) {
                                        'paid' => 'badge-paid',
                                        'overdue' => 'badge-overdue',
                                        'cancelled' => 'badge-cancelled',
                                        default => 'badge-pending',
                                    };
                                @endphp
                                <span class="badge {{ $badge }}">{{ $paymentStatus[$status] }}</span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <div class="empty">Nenhuma cobrança registrada.</div>
        @endif
    </div>

    @if ($student->observations)
        <div class="section">Observações</div>
        <div class="box keep">
            <div class="note" style="margin-top: 0">{{ $student->observations }}</div>
        </div>
    @endif

</body>
</html>
