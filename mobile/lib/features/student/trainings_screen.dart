import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import 'formatters.dart';
import 'models/training_session.dart';
import 'student_providers.dart';

/// Lista de treinos do aluno com filtro por status e paginação.
class TrainingsScreen extends ConsumerStatefulWidget {
  const TrainingsScreen({super.key, this.initialStatus});

  /// Filtro inicial vindo da rota (`planned`, `completed`, `skipped`).
  final String? initialStatus;

  @override
  ConsumerState<TrainingsScreen> createState() => _TrainingsScreenState();
}

class _TrainingsScreenState extends ConsumerState<TrainingsScreen>
    with SingleTickerProviderStateMixin {
  late final TabController _tabs;
  final _scrollController = ScrollController();

  // Índice da aba → status da API (null = todos).
  static const _statuses = <String?>[null, 'planned', 'completed', 'skipped'];

  @override
  void initState() {
    super.initState();
    final initialIndex =
        _statuses.indexOf(widget.initialStatus).clamp(0, _statuses.length - 1);
    _tabs = TabController(length: _statuses.length, vsync: this, initialIndex: initialIndex)
      ..addListener(() {
        if (!_tabs.indexIsChanging) setState(() {});
      });
    _scrollController.addListener(_onScroll);
  }

  void _onScroll() {
    if (!_scrollController.hasClients) return;
    final position = _scrollController.position;
    if (position.pixels >= position.maxScrollExtent - 200) {
      final controller = ref.read(trainingsProvider(status).notifier);
      controller.loadMore();
    }
  }

  String? get status => _statuses[_tabs.index];

  @override
  void dispose() {
    _tabs.dispose();
    _scrollController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final trainings = ref.watch(trainingsProvider(status));

    return Scaffold(
      appBar: AppBar(
        title: const Text('Treinos'),
        bottom: TabBar(
          controller: _tabs,
          isScrollable: true,
          tabs: const [
            Tab(text: 'Todos'),
            Tab(text: 'Planejados'),
            Tab(text: 'Concluídos'),
            Tab(text: 'Pulados'),
          ],
        ),
      ),
      body: trainings.when(
        loading: () => const Center(child: CircularProgressIndicator()),
        error: (error, _) => _ErrorRetry(
          message: error.toString(),
          onRetry: () => ref.read(trainingsProvider(status).notifier).refresh(),
        ),
        data: (items) {
          if (items.isEmpty) {
            return const _Empty();
          }

          final controller = ref.read(trainingsProvider(status).notifier);

          return RefreshIndicator(
            onRefresh: () =>
                ref.read(trainingsProvider(status).notifier).refresh(),
            child: ListView.separated(
              controller: _scrollController,
              physics: const AlwaysScrollableScrollPhysics(),
              padding: const EdgeInsets.symmetric(vertical: 8),
              itemCount: items.length + (controller.hasMore ? 1 : 0),
              separatorBuilder: (_, _) => const Divider(height: 1),
              itemBuilder: (context, index) {
                if (index >= items.length) {
                  return const Padding(
                    padding: EdgeInsets.all(16),
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
}

/// Tile reutilizável de sessão (lista e calendário).
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
    final scheme = Theme.of(context).colorScheme;

    return ListTile(
      leading: _StatusAvatar(session: session),
      title: Text(session.title),
      subtitle: Text(
        [
          if (session.scheduledDateLabel.isNotEmpty) session.scheduledDateLabel,
          if (session.sport?.name != null) session.sport!.name,
          if (session.estimatedDuration != null)
            formatDuration(session.estimatedDuration!),
        ].join(' · '),
      ),
      trailing: session.statusLabel != null
          ? Chip(
              label: Text(session.statusLabel!),
              visualDensity: VisualDensity.compact,
              backgroundColor: switch (session.status) {
                'completed' => scheme.primaryContainer,
                'skipped' => scheme.surfaceContainerHighest,
                _ => scheme.tertiaryContainer,
              },
            )
          : const Icon(Icons.chevron_right),
      onTap: onTap,
    );
  }
}

class _StatusAvatar extends StatelessWidget {
  const _StatusAvatar({required this.session});

  final TrainingSession session;

  @override
  Widget build(BuildContext context) {
    final scheme = Theme.of(context).colorScheme;
    final (icon, color) = switch (session.status) {
      'completed' => (Icons.check, scheme.primary),
      'skipped' => (Icons.remove, scheme.outline),
      _ => (Icons.schedule, scheme.tertiary),
    };

    return CircleAvatar(
      backgroundColor: color.withValues(alpha: 0.15),
      foregroundColor: color,
      child: Icon(icon, size: 20),
    );
  }
}

class _Empty extends StatelessWidget {
  const _Empty();

  @override
  Widget build(BuildContext context) {
    return Center(
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          Icon(Icons.fitness_center,
              size: 48, color: Theme.of(context).colorScheme.outline),
          const SizedBox(height: 12),
          const Text('Nenhum treino por aqui.'),
          const SizedBox(height: 4),
          Text(
            'Seu treinador ainda não enviou treinos.',
            style: Theme.of(context).textTheme.bodySmall,
          ),
        ],
      ),
    );
  }
}

class _ErrorRetry extends StatelessWidget {
  const _ErrorRetry({required this.message, required this.onRetry});

  final String message;
  final VoidCallback onRetry;

  @override
  Widget build(BuildContext context) {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(24),
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(Icons.cloud_off,
                size: 48, color: Theme.of(context).colorScheme.outline),
            const SizedBox(height: 12),
            Text(message, textAlign: TextAlign.center),
            const SizedBox(height: 12),
            FilledButton(
                onPressed: onRetry, child: const Text('Tentar novamente')),
          ],
        ),
      ),
    );
  }
}
