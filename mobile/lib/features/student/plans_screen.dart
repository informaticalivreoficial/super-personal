import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../core/theme/app_colors.dart';
import '../../core/widgets/app_card.dart';
import '../../core/widgets/app_tone.dart';
import '../../core/widgets/empty_state.dart';
import '../../core/widgets/icon_tile.dart';
import '../../core/widgets/section_header.dart';
import '../../core/widgets/skeleton.dart';
import '../../core/widgets/status_chip.dart';
import 'models/training_plan.dart';
import 'models/training_session.dart';
import 'student_providers.dart';

/// Planos ativos do aluno com semanas e sessões.
class PlansScreen extends ConsumerWidget {
  const PlansScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final plans = ref.watch(trainingPlansProvider);
    Future<void> refresh() async => ref.invalidate(trainingPlansProvider);

    return Scaffold(
      appBar: AppBar(title: const Text('Meus planos')),
      body: plans.when(
        loading: () => const SkeletonCardList(count: 3),
        error: (error, _) => RefreshableState(
          onRefresh: refresh,
          child: ErrorState(message: error.toString(), onRetry: refresh),
        ),
        data: (items) {
          if (items.isEmpty) {
            return RefreshableState(
              onRefresh: refresh,
              child: const EmptyState(
                icon: Icons.menu_book_rounded,
                title: 'Nenhum plano ativo',
                message: 'Quando seu treinador publicar um plano de treino, '
                    'ele aparece aqui com as semanas e as sessões.',
              ),
            );
          }

          return RefreshIndicator(
            onRefresh: refresh,
            child: ListView(
              physics: const AlwaysScrollableScrollPhysics(),
              padding: const EdgeInsets.fromLTRB(16, 16, 16, 28),
              children: [
                SectionHeader(
                  title: 'Seus planos',
                  subtitle: items.length == 1
                      ? '1 plano ativo'
                      : '${items.length} planos ativos',
                ),
                for (final plan in items)
                  Padding(
                    padding: const EdgeInsets.only(bottom: 12),
                    child: _PlanCard(plan: plan),
                  ),
              ],
            ),
          );
        },
      ),
    );
  }
}

/// Card do plano com as semanas e sessões recolhíveis.
class _PlanCard extends StatelessWidget {
  const _PlanCard({required this.plan});

  final TrainingPlan plan;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final totalSessions =
        plan.weeks.fold<int>(0, (total, week) => total + week.sessions.length);

    final meta = [
      if (plan.goal != null && plan.goal!.isNotEmpty) plan.goal!,
      '${plan.weeks.length} ${plan.weeks.length == 1 ? 'semana' : 'semanas'}',
      '$totalSessions ${totalSessions == 1 ? 'sessão' : 'sessões'}',
    ].join(' · ');

    return AppCard(
      padding: EdgeInsets.zero,
      child: Theme(
        // Sem as linhas divisórias padrão do ExpansionTile.
        data: theme.copyWith(dividerColor: Colors.transparent),
        child: ExpansionTile(
          tilePadding: const EdgeInsets.fromLTRB(14, 6, 10, 6),
          childrenPadding: const EdgeInsets.only(bottom: 10),
          iconColor: AppColors.brand700,
          collapsedIconColor: AppColors.textMuted,
          leading: const IconTile(icon: Icons.menu_book_rounded, size: 44),
          title: Text(plan.name, style: theme.textTheme.titleSmall),
          subtitle: Padding(
            padding: const EdgeInsets.only(top: 3),
            child: Text(meta, style: theme.textTheme.bodySmall),
          ),
          children: [
            if (plan.statusLabel != null)
              Align(
                alignment: Alignment.centerLeft,
                child: Padding(
                  padding: const EdgeInsets.fromLTRB(14, 0, 14, 10),
                  child: StatusChip(
                    label: plan.statusLabel!,
                    status: plan.status,
                  ),
                ),
              ),
            for (final week in plan.weeks) _WeekTile(week: week),
          ],
        ),
      ),
    );
  }
}

/// Semana dentro do plano (sessões recolhíveis).
class _WeekTile extends StatelessWidget {
  const _WeekTile({required this.week});

  final TrainingWeek week;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final nome = week.name?.isNotEmpty == true
        ? week.name!
        : 'Semana ${week.weekNumber}';

    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 10),
      child: Theme(
        data: theme.copyWith(dividerColor: Colors.transparent),
        child: ExpansionTile(
          dense: true,
          tilePadding: const EdgeInsets.symmetric(horizontal: 4),
          childrenPadding: const EdgeInsets.only(bottom: 6),
          iconColor: AppColors.brand700,
          collapsedIconColor: AppColors.textMuted,
          leading: Container(
            width: 28,
            height: 28,
            alignment: Alignment.center,
            decoration: const BoxDecoration(
              color: AppColors.brand100,
              shape: BoxShape.circle,
            ),
            child: Text(
              '${week.weekNumber}',
              style: theme.textTheme.labelSmall!.copyWith(
                color: AppColors.brand800,
                fontWeight: FontWeight.w800,
              ),
            ),
          ),
          title: Text(nome, style: theme.textTheme.titleSmall),
          subtitle: Text(
            [
              if (week.objective != null && week.objective!.isNotEmpty)
                week.objective!,
              '${week.sessions.length} '
                  '${week.sessions.length == 1 ? 'sessão' : 'sessões'}',
            ].join(' · '),
            style: theme.textTheme.bodySmall,
          ),
          children: [
            for (final session in week.sessions) _SessionRow(session: session),
          ],
        ),
      ),
    );
  }
}

/// Linha de uma sessão dentro da semana (abre o detalhe do treino).
class _SessionRow extends StatelessWidget {
  const _SessionRow({required this.session});

  final TrainingSession session;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final tone = toneForStatus(session.status);
    final meta = [
      if (session.scheduledDateLabel.isNotEmpty) session.scheduledDateLabel,
      if (session.sport?.name != null) session.sport!.name,
    ].join(' · ');

    return InkWell(
      onTap: () => context.push('/treinos/${session.id}'),
      child: Padding(
        padding: const EdgeInsets.fromLTRB(8, 8, 6, 8),
        child: Row(
          children: [
            Icon(_icon, size: 20, color: tone.foreground),
            const SizedBox(width: 12),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(session.title, style: theme.textTheme.bodyMedium),
                  if (meta.isNotEmpty)
                    Text(meta, style: theme.textTheme.bodySmall),
                ],
              ),
            ),
            const Icon(
              Icons.chevron_right_rounded,
              size: 20,
              color: AppColors.textMuted,
            ),
          ],
        ),
      ),
    );
  }

  IconData get _icon => switch (session.status) {
        'completed' => Icons.check_circle_rounded,
        'skipped' => Icons.remove_circle_outline_rounded,
        _ => Icons.schedule_rounded,
      };
}
