import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../core/theme/app_colors.dart';
import '../../core/widgets/app_card.dart';
import '../../core/widgets/empty_state.dart';
import '../../core/widgets/skeleton.dart';
import '../../core/widgets/sport_icon.dart';
import '../../core/widgets/status_chip.dart';
import 'formatters.dart';
import 'models/training_session.dart';
import 'student_providers.dart';

/// Filtros da lista (rótulo → status da API).
const _filters = <({String label, String? status})>[
  (label: 'Todos', status: null),
  (label: 'Planejados', status: 'planned'),
  (label: 'Concluídos', status: 'completed'),
  (label: 'Pulados', status: 'skipped'),
];

/// Lista de treinos do aluno com filtro por status e paginação.
class TrainingsScreen extends ConsumerStatefulWidget {
  const TrainingsScreen({super.key, this.initialStatus});

  /// Filtro inicial vindo da rota (`planned`, `completed`, `skipped`).
  final String? initialStatus;

  @override
  ConsumerState<TrainingsScreen> createState() => _TrainingsScreenState();
}

class _TrainingsScreenState extends ConsumerState<TrainingsScreen> {
  late int _index;
  final _scrollController = ScrollController();

  @override
  void initState() {
    super.initState();
    final inicial = _filters.indexWhere((f) => f.status == widget.initialStatus);
    _index = inicial < 0 ? 0 : inicial;
    _scrollController.addListener(_onScroll);
  }

  void _onScroll() {
    if (!_scrollController.hasClients) return;
    final position = _scrollController.position;
    if (position.pixels >= position.maxScrollExtent - 200) {
      ref.read(trainingsProvider(status).notifier).loadMore();
    }
  }

  String? get status => _filters[_index].status;

  @override
  void dispose() {
    _scrollController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final trainings = ref.watch(trainingsProvider(status));

    return Scaffold(
      appBar: AppBar(
        title: const Text('Meus treinos'),
        bottom: PreferredSize(
          preferredSize: const Size.fromHeight(56),
          child: Align(
            alignment: Alignment.centerLeft,
            child: SingleChildScrollView(
              scrollDirection: Axis.horizontal,
              padding: const EdgeInsets.fromLTRB(16, 0, 16, 12),
              child: Row(
                children: [
                  for (var i = 0; i < _filters.length; i++) ...[
                    if (i > 0) const SizedBox(width: 8),
                    ChoiceChip(
                      label: Text(_filters[i].label),
                      selected: _index == i,
                      onSelected: (_) => setState(() => _index = i),
                      labelStyle: Theme.of(context)
                          .textTheme
                          .labelMedium!
                          .copyWith(
                            color: _index == i
                                ? AppColors.brand800
                                : AppColors.textSecondary,
                          ),
                    ),
                  ],
                ],
              ),
            ),
          ),
        ),
      ),
      body: trainings.when(
        loading: () => const SkeletonCardList(count: 5),
        error: (error, _) => RefreshableState(
          onRefresh: () => ref.read(trainingsProvider(status).notifier).refresh(),
          child: ErrorState(
            message: error.toString(),
            onRetry: () =>
                ref.read(trainingsProvider(status).notifier).refresh(),
          ),
        ),
        data: (items) {
          if (items.isEmpty) {
            return RefreshableState(
              onRefresh: () =>
                  ref.read(trainingsProvider(status).notifier).refresh(),
              child: EmptyState(
                icon: Icons.fitness_center_rounded,
                title: _emptyTitle,
                message: _emptyMessage,
              ),
            );
          }

          final controller = ref.read(trainingsProvider(status).notifier);

          return RefreshIndicator(
            onRefresh: () =>
                ref.read(trainingsProvider(status).notifier).refresh(),
            child: ListView.separated(
              controller: _scrollController,
              physics: const AlwaysScrollableScrollPhysics(),
              padding: const EdgeInsets.fromLTRB(16, 16, 16, 28),
              itemCount: items.length + (controller.hasMore ? 1 : 0),
              separatorBuilder: (_, _) => const SizedBox(height: 12),
              itemBuilder: (context, index) {
                if (index >= items.length) {
                  return const Padding(
                    padding: EdgeInsets.symmetric(vertical: 16),
                    child: Center(child: CircularProgressIndicator()),
                  );
                }
                final session = items[index];
                return TrainingListTile(
                  session: session,
                  onTap: () => context.push('/treinos/${session.id}'),
                );
              },
            ),
          );
        },
      ),
    );
  }

  String get _emptyTitle => switch (status) {
        'planned' => 'Nenhum treino agendado',
        'completed' => 'Nenhum treino concluído',
        'skipped' => 'Nenhum treino pulado',
        _ => 'Nenhum treino por aqui',
      };

  String get _emptyMessage => switch (status) {
        'planned' => 'Quando seu treinador enviar o próximo treino, ele aparece aqui.',
        'completed' => 'Assim que você concluir um treino, ele fica registrado aqui.',
        'skipped' => 'Você não pulou nenhum treino. Bom ritmo!',
        _ => 'Seu treinador ainda não enviou treinos.',
      };
}

/// Card de sessão (lista de treinos e calendário).
class TrainingListTile extends StatelessWidget {
  const TrainingListTile({
    super.key,
    required this.session,
    this.onTap,
  });

  final TrainingSession session;
  final VoidCallback? onTap;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final meta = _metaParts.join(' · ');

    return AppCard(
      onTap: onTap,
      padding: const EdgeInsets.all(14),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          SportIcon(name: session.sport?.name, size: 46),
          const SizedBox(width: 14),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  session.title,
                  style: theme.textTheme.titleSmall,
                  maxLines: 2,
                  overflow: TextOverflow.ellipsis,
                ),
                if (meta.isNotEmpty) ...[
                  const SizedBox(height: 4),
                  Text(meta, style: theme.textTheme.bodySmall),
                ],
                if (session.statusLabel != null) ...[
                  const SizedBox(height: 10),
                  StatusChip(
                    label: session.statusLabel!,
                    status: session.status,
                  ),
                ],
              ],
            ),
          ),
          thenChevron,
        ],
      ),
    );
  }

  static const thenChevron =
      Icon(Icons.chevron_right_rounded, color: AppColors.textMuted, size: 22);

  List<String> get _metaParts => [
        ?_dateLabel,
        ?session.sport?.name,
        if (session.estimatedDuration != null &&
            session.estimatedDuration! > 0)
          formatDuration(session.estimatedDuration!),
        if (session.distance != null && session.distance! > 0)
          formatDistance(session.distance!),
      ];

  /// Data amigável: `Hoje`, `Amanhã` ou `dd/mm/aaaa`.
  String? get _dateLabel {
    final date = session.scheduledDate;
    if (date == null) return null;

    final hoje = DateTime.now();
    final alvo = DateTime(date.year, date.month, date.day);
    final base = DateTime(hoje.year, hoje.month, hoje.day);
    final diff = alvo.difference(base).inDays;

    if (diff == 0) return 'Hoje';
    if (diff == 1) return 'Amanhã';
    if (diff == -1) return 'Ontem';

    return session.scheduledDateLabel;
  }
}
