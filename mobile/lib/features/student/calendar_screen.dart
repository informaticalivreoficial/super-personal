import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../core/theme/app_colors.dart';
import '../../core/widgets/empty_state.dart';
import '../../core/widgets/skeleton.dart';
import 'models/training_session.dart';
import 'student_providers.dart';
import 'trainings_screen.dart';

/// Calendário do mês corrente com as sessões programadas.
class CalendarScreen extends ConsumerWidget {
  const CalendarScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final calendar = ref.watch(calendarProvider);
    final now = DateTime.now();
    Future<void> refresh() => ref.read(calendarProvider.notifier).refresh();

    return Scaffold(
      appBar: AppBar(
        title: Text(_monthLabel(now)),
        actions: [
          IconButton(
            tooltip: 'Atualizar',
            icon: const Icon(Icons.refresh_rounded),
            onPressed: refresh,
          ),
        ],
      ),
      body: calendar.when(
        loading: () => const SkeletonCardList(count: 5),
        error: (error, _) => RefreshableState(
          onRefresh: refresh,
          child: ErrorState(message: error.toString(), onRetry: refresh),
        ),
        data: (sessions) {
          if (sessions.isEmpty) {
            return RefreshableState(
              onRefresh: refresh,
              child: const EmptyState(
                icon: Icons.event_available_rounded,
                title: 'Nada agendado neste mês',
                message: 'Quando seu treinador programar sessões para este '
                    'mês, elas aparecem aqui dia por dia.',
              ),
            );
          }

          // Agrupa por dia (a API já devolve ordenado por data).
          final porDia = <String, List<TrainingSession>>{};
          for (final session in sessions) {
            final data = session.scheduledDate;
            final chave = data == null
                ? 'Sem data'
                : '${data.year}-${_two(data.month)}-${_two(data.day)}';
            porDia.putIfAbsent(chave, () => []).add(session);
          }

          return RefreshIndicator(
            onRefresh: refresh,
            child: ListView(
              physics: const AlwaysScrollableScrollPhysics(),
              padding: const EdgeInsets.fromLTRB(16, 16, 16, 28),
              children: [
                for (final entry in porDia.entries) ...[
                  _DayHeader(
                    date: entry.value.first.scheduledDate,
                    count: entry.value.length,
                  ),
                  for (final session in entry.value)
                    Padding(
                      padding: const EdgeInsets.only(bottom: 12),
                      child: TrainingListTile(
                        session: session,
                        onTap: () => context.push('/treinos/${session.id}'),
                      ),
                    ),
                ],
              ],
            ),
          );
        },
      ),
    );
  }

  static const _months = [
    'janeiro',
    'fevereiro',
    'março',
    'abril',
    'maio',
    'junho',
    'julho',
    'agosto',
    'setembro',
    'outubro',
    'novembro',
    'dezembro',
  ];

  static const _weekdays = [
    'Segunda-feira',
    'Terça-feira',
    'Quarta-feira',
    'Quinta-feira',
    'Sexta-feira',
    'Sábado',
    'Domingo',
  ];

  static String _monthLabel(DateTime date) {
    final mes = _months[date.month - 1];
    return '${mes[0].toUpperCase()}${mes.substring(1)} de ${date.year}';
  }

  static String _two(int value) => value.toString().padLeft(2, '0');
}

/// Cabeçalho do dia (`Hoje`, `Amanhã` ou `Quarta-feira, 16 de outubro`).
class _DayHeader extends StatelessWidget {
  const _DayHeader({required this.date, required this.count});

  final DateTime? date;
  final int count;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final label = _label;

    return Padding(
      padding: const EdgeInsets.fromLTRB(4, 6, 4, 10),
      child: Row(
        children: [
          Container(
            width: 10,
            height: 10,
            decoration: BoxDecoration(
              color: label == 'Hoje' ? AppColors.brand500 : AppColors.borderStrong,
              shape: BoxShape.circle,
            ),
          ),
          const SizedBox(width: 10),
          Expanded(
            child: Text(
              label,
              style: theme.textTheme.titleSmall!.copyWith(
                color: label == 'Hoje'
                    ? AppColors.brand700
                    : AppColors.textPrimary,
              ),
            ),
          ),
          Text(
            '$count ${count == 1 ? 'sessão' : 'sessões'}',
            style: theme.textTheme.labelSmall!
                .copyWith(color: AppColors.textMuted),
          ),
        ],
      ),
    );
  }

  String get _label {
    final data = date;
    if (data == null) return 'Sem data';

    final hoje = DateTime.now();
    final dia = DateTime(data.year, data.month, data.day);
    final base = DateTime(hoje.year, hoje.month, hoje.day);
    final diff = dia.difference(base).inDays;

    if (diff == 0) return 'Hoje';
    if (diff == 1) return 'Amanhã';
    if (diff == -1) return 'Ontem';

    final mes = CalendarScreen._months[data.month - 1];
    final semana = CalendarScreen._weekdays[data.weekday - 1];

    return '$semana, ${data.day} de $mes';
  }
}
