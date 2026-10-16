import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../core/theme/app_colors.dart';
import '../../core/widgets/app_card.dart';
import '../../core/widgets/app_tone.dart';
import '../../core/widgets/empty_state.dart';
import '../../core/widgets/icon_tile.dart';
import '../../core/widgets/section_header.dart';
import '../../core/widgets/skeleton.dart';
import 'formatters.dart';
import 'models/progress.dart';
import 'student_providers.dart';

/// Histórico de avaliações físicas do aluno (paginado).
class ProgressScreen extends ConsumerStatefulWidget {
  const ProgressScreen({super.key});

  @override
  ConsumerState<ProgressScreen> createState() => _ProgressScreenState();
}

class _ProgressScreenState extends ConsumerState<ProgressScreen> {
  final _scrollController = ScrollController();

  @override
  void initState() {
    super.initState();
    _scrollController.addListener(() {
      if (!_scrollController.hasClients) return;
      final position = _scrollController.position;
      if (position.pixels >= position.maxScrollExtent - 200) {
        ref.read(progressProvider.notifier).loadMore();
      }
    });
  }

  @override
  void dispose() {
    _scrollController.dispose();
    super.dispose();
  }

  Future<void> _refresh() => ref.read(progressProvider.notifier).refresh();

  @override
  Widget build(BuildContext context) {
    final progress = ref.watch(progressProvider);

    return Scaffold(
      appBar: AppBar(title: const Text('Meu progresso')),
      body: progress.when(
        loading: () => const SkeletonCardList(count: 5),
        error: (error, _) => RefreshableState(
          onRefresh: _refresh,
          child: ErrorState(message: error.toString(), onRetry: _refresh),
        ),
        data: (items) {
          if (items.isEmpty) {
            return RefreshableState(
              onRefresh: _refresh,
              child: const EmptyState(
                icon: Icons.monitor_heart_outlined,
                title: 'Nenhuma avaliação ainda',
                message: 'Seu treinador registra suas avaliações aqui: peso, '
                    'gordura, FC de repouso, FTP e ritmos.',
              ),
            );
          }

          final controller = ref.read(progressProvider.notifier);

          return RefreshIndicator(
            onRefresh: _refresh,
            child: ListView.separated(
              controller: _scrollController,
              physics: const AlwaysScrollableScrollPhysics(),
              padding: const EdgeInsets.fromLTRB(16, 16, 16, 28),
              itemCount: items.length + 1 + (controller.hasMore ? 1 : 0),
              separatorBuilder: (_, _) => const SizedBox(height: 12),
              itemBuilder: (context, index) {
                if (index == 0) {
                  return SectionHeader(
                    title: 'Suas avaliações',
                    subtitle: items.length == 1
                        ? '1 avaliação registrada'
                        : '${items.length} avaliações registradas',
                  );
                }

                final item = index - 1;

                if (item >= items.length) {
                  return const Padding(
                    padding: EdgeInsets.symmetric(vertical: 16),
                    child: Center(child: CircularProgressIndicator()),
                  );
                }

                final entry = items[item];

                return _ProgressCard(
                  entry: entry,
                  onTap: () => showProgressDetails(context, entry),
                );
              },
            ),
          );
        },
      ),
    );
  }
}

/// Card de uma avaliação (data + métricas em pílulas).
class _ProgressCard extends StatelessWidget {
  const _ProgressCard({required this.entry, required this.onTap});

  final StudentProgress entry;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final metricas = metricPills(entry);

    return AppCard(
      onTap: onTap,
      padding: const EdgeInsets.all(14),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const IconTile(icon: Icons.insights_rounded, size: 44),
          const SizedBox(width: 14),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  entry.recordedAtLabel.isEmpty
                      ? 'Avaliação'
                      : entry.recordedAtLabel,
                  style: theme.textTheme.titleSmall,
                ),
                const SizedBox(height: 8),
                if (metricas.isEmpty)
                  Text('Sem métricas registradas.', style: theme.textTheme.bodySmall)
                else
                  Wrap(
                    spacing: 8,
                    runSpacing: 8,
                    children: [
                      for (final metrica in metricas) _MetricPill(label: metrica),
                    ],
                  ),
              ],
            ),
          ),
          const Icon(
            Icons.chevron_right_rounded,
            color: AppColors.textMuted,
            size: 22,
          ),
        ],
      ),
    );
  }
}

/// Pílula com uma métrica (`78,5 kg`, `12,0%`, `58 bpm`).
class _MetricPill extends StatelessWidget {
  const _MetricPill({required this.label});

  final String label;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
      decoration: BoxDecoration(
        color: AppColors.surfaceMuted,
        borderRadius: BorderRadius.circular(999),
        border: Border.all(color: AppColors.border),
      ),
      child: Text(
        label,
        style: Theme.of(context).textTheme.labelSmall!.copyWith(
              color: AppColors.textSecondary,
              fontWeight: FontWeight.w600,
            ),
      ),
    );
  }
}

/// Métricas da avaliação no formato das pílulas (`Peso 78,5 kg`...).
List<String> metricPills(StudentProgress entry) => [
      if (entry.weight != null)
        'Peso ${formatDecimal(entry.weight!)} kg',
      if (entry.bodyFat != null)
        'Gordura ${formatDecimal(entry.bodyFat!)}%',
      if (entry.restingHeartRate != null)
        'FC rep. ${entry.restingHeartRate} bpm',
      if (entry.ftp != null) 'FTP ${entry.ftp} W',
      if (entry.runningPace != null) entry.runningPace!,
    ];

/// Detalhes completos de uma avaliação (bottom sheet).
Future<void> showProgressDetails(BuildContext context, StudentProgress entry) {
  final theme = Theme.of(context);

  return showModalBottomSheet<void>(
    context: context,
    isScrollControlled: true,
    builder: (context) => SafeArea(
      child: Padding(
        padding: const EdgeInsets.fromLTRB(20, 4, 20, 20),
        child: SingleChildScrollView(
          child: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                children: [
                  const IconTile(icon: Icons.insights_rounded, size: 46),
                  const SizedBox(width: 14),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text('Avaliação', style: theme.textTheme.titleLarge),
                        const SizedBox(height: 2),
                        Text(
                          entry.recordedAtLabel.isEmpty
                              ? 'Sem data'
                              : entry.recordedAtLabel,
                          style: theme.textTheme.bodySmall,
                        ),
                      ],
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 16),
              AppCard(
                padding: const EdgeInsets.symmetric(
                  horizontal: 16,
                  vertical: 6,
                ),
                child: Column(
                  children: [
                    if (entry.weight != null)
                      _DetailRow(
                        icon: Icons.monitor_weight_outlined,
                        label: 'Peso',
                        value: '${formatDecimal(entry.weight!)} kg',
                      ),
                    if (entry.bodyFat != null)
                      _DetailRow(
                        icon: Icons.pie_chart_outline_rounded,
                        label: 'Gordura corporal',
                        value: '${formatDecimal(entry.bodyFat!)}%',
                      ),
                    if (entry.restingHeartRate != null)
                      _DetailRow(
                        icon: Icons.favorite_border_rounded,
                        label: 'FC repouso',
                        value: '${entry.restingHeartRate} bpm',
                      ),
                    if (entry.maxHeartRate != null)
                      _DetailRow(
                        icon: Icons.monitor_heart_outlined,
                        label: 'FC máxima',
                        value: '${entry.maxHeartRate} bpm',
                      ),
                    if (entry.ftp != null)
                      _DetailRow(
                        icon: Icons.bolt_rounded,
                        label: 'FTP',
                        value: '${entry.ftp} W',
                      ),
                    if (entry.runningPace != null)
                      _DetailRow(
                        icon: Icons.directions_run_rounded,
                        label: 'Ritmo de corrida',
                        value: entry.runningPace!,
                      ),
                    if (entry.swimmingPace != null)
                      _DetailRow(
                        icon: Icons.pool_rounded,
                        label: 'Ritmo de natação',
                        value: entry.swimmingPace!,
                      ),
                  ],
                ),
              ),
              if (entry.notes != null && entry.notes!.isNotEmpty) ...[
                const SizedBox(height: 14),
                AlertCard(
                  icon: Icons.notes_rounded,
                  tone: AppTone.neutral,
                  title: 'Observações',
                  message: entry.notes,
                ),
              ],
            ],
          ),
        ),
      ),
    ),
  );
}

class _DetailRow extends StatelessWidget {
  const _DetailRow({
    required this.icon,
    required this.label,
    required this.value,
  });

  final IconData icon;
  final String label;
  final String value;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 10),
      child: Row(
        children: [
          Icon(icon, size: 18, color: AppColors.textMuted),
          const SizedBox(width: 10),
          Expanded(
            child: Text(
              label,
              style: theme.textTheme.bodySmall!
                  .copyWith(color: AppColors.textSecondary),
            ),
          ),
          const SizedBox(width: 12),
          Text(value, style: theme.textTheme.titleSmall),
        ],
      ),
    );
  }
}
