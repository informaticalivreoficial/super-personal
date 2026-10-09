import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import 'trainings_screen.dart';
import 'student_providers.dart';

/// Calendário do mês corrente com as sessões programadas.
class CalendarScreen extends ConsumerWidget {
  const CalendarScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final calendar = ref.watch(calendarProvider);
    final now = DateTime.now();

    return Scaffold(
      appBar: AppBar(
        title: Text(_monthLabel(now)),
        actions: [
          IconButton(
            tooltip: 'Atualizar',
            icon: const Icon(Icons.refresh),
            onPressed: () => ref.read(calendarProvider.notifier).refresh(),
          ),
        ],
      ),
      body: calendar.when(
        loading: () => const Center(child: CircularProgressIndicator()),
        error: (error, _) => Center(
          child: Padding(
            padding: const EdgeInsets.all(24),
            child: Column(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                Icon(Icons.cloud_off,
                    size: 48,
                    color: Theme.of(context).colorScheme.outline),
                const SizedBox(height: 12),
                Text(error.toString(), textAlign: TextAlign.center),
                const SizedBox(height: 12),
                FilledButton(
                  onPressed: () =>
                      ref.read(calendarProvider.notifier).refresh(),
                  child: const Text('Tentar novamente'),
                ),
              ],
            ),
          ),
        ),
        data: (sessions) {
          if (sessions.isEmpty) {
            return const Center(
              child: Column(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  Icon(Icons.event_busy, size: 48),
                  SizedBox(height: 12),
                  Text('Nenhuma sessão neste mês.'),
                ],
              ),
            );
          }

          // Agrupa por dia (a API já devolve ordenado por data).
          final byDay = <String, List<dynamic>>{};
          for (final session in sessions) {
            final key = session.scheduledDateLabel;
            byDay.putIfAbsent(key, () => []).add(session);
          }

          return RefreshIndicator(
            onRefresh: () => ref.read(calendarProvider.notifier).refresh(),
            child: ListView(
              physics: const AlwaysScrollableScrollPhysics(),
              padding: const EdgeInsets.all(8),
              children: [
                for (final entry in byDay.entries) ...[
                  Padding(
                    padding: const EdgeInsets.fromLTRB(8, 12, 8, 4),
                    child: Text(
                      entry.key,
                      style: Theme.of(context).textTheme.titleSmall,
                    ),
                  ),
                  ...entry.value.map(
                    (session) => TrainingListTile(
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

  static String _monthLabel(DateTime date) =>
      '${_months[date.month - 1]} de ${date.year}';
}
