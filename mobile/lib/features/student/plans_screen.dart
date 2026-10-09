import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import 'models/training_plan.dart';
import 'student_providers.dart';

/// Planos ativos do aluno com semanas e sessões.
class PlansScreen extends ConsumerWidget {
  const PlansScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final plans = ref.watch(trainingPlansProvider);

    return Scaffold(
      appBar: AppBar(title: const Text('Meus planos')),
      body: plans.when(
        loading: () => const Center(child: CircularProgressIndicator()),
        error: (error, _) => Center(
          child: Padding(
            padding: const EdgeInsets.all(24),
            child: Column(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                Icon(Icons.cloud_off,
                    size: 48, color: Theme.of(context).colorScheme.outline),
                const SizedBox(height: 12),
                Text(error.toString(), textAlign: TextAlign.center),
                const SizedBox(height: 12),
                FilledButton(
                  onPressed: () => ref.invalidate(trainingPlansProvider),
                  child: const Text('Tentar novamente'),
                ),
              ],
            ),
          ),
        ),
        data: (items) {
          if (items.isEmpty) {
            return const Center(
              child: Column(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  Icon(Icons.menu_book_outlined, size: 48),
                  SizedBox(height: 12),
                  Text('Nenhum plano ativo.'),
                  SizedBox(height: 4),
                  Text(
                    'Seu treinador ainda não enviou um plano.',
                    style: TextStyle(fontSize: 12),
                  ),
                ],
              ),
            );
          }

          return RefreshIndicator(
            onRefresh: () async => ref.invalidate(trainingPlansProvider),
            child: ListView(
              physics: const AlwaysScrollableScrollPhysics(),
              padding: const EdgeInsets.all(8),
              children: [
                for (final plan in items) _PlanCard(plan: plan),
              ],
            ),
          );
        },
      ),
    );
  }
}

class _PlanCard extends StatelessWidget {
  const _PlanCard({required this.plan});

  final TrainingPlan plan;

  @override
  Widget build(BuildContext context) {
    final totalSessions =
        plan.weeks.fold<int>(0, (total, week) => total + week.sessions.length);

    return Card(
      margin: const EdgeInsets.symmetric(vertical: 6),
      child: ExpansionTile(
        leading: const Icon(Icons.menu_book_outlined),
        title: Text(plan.name),
        subtitle: Text([
          if (plan.statusLabel != null) plan.statusLabel!,
          if (plan.goal != null && plan.goal!.isNotEmpty) plan.goal!,
          '${plan.weeks.length} semanas · $totalSessions sessões',
        ].join(' · ')),
        children: [
          for (final week in plan.weeks)
            ExpansionTile(
              dense: true,
              title: Text(
                week.name?.isNotEmpty == true
                    ? week.name!
                    : 'Semana ${week.weekNumber}',
              ),
              subtitle: Text([
                if (week.objective != null && week.objective!.isNotEmpty)
                  week.objective!,
                '${week.sessions.length} sessões',
              ].join(' · ')),
              children: [
                for (final session in week.sessions)
                  ListTile(
                    dense: true,
                    leading: Icon(
                      switch (session.status) {
                        'completed' => Icons.check_circle,
                        'skipped' => Icons.remove_circle_outline,
                        _ => Icons.schedule,
                      },
                      size: 20,
                    ),
                    title: Text(session.title),
                    subtitle: Text([
                      if (session.scheduledDateLabel.isNotEmpty)
                        session.scheduledDateLabel,
                      if (session.sport?.name != null)
                        session.sport!.name,
                    ].join(' · ')),
                    onTap: () => context.push('/treinos/${session.id}'),
                  ),
              ],
            ),
          const SizedBox(height: 8),
        ],
      ),
    );
  }
}
