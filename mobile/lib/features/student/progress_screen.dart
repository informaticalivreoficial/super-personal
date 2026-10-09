import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

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

  @override
  Widget build(BuildContext context) {
    final progress = ref.watch(progressProvider);

    return Scaffold(
      appBar: AppBar(title: const Text('Meu progresso')),
      body: progress.when(
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
                  onPressed: () => ref.read(progressProvider.notifier).refresh(),
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
                  Icon(Icons.monitor_heart_outlined, size: 48),
                  SizedBox(height: 12),
                  Text('Nenhuma avaliação registrada.'),
                  SizedBox(height: 4),
                  Text(
                    'Seu treinador ainda não registrou avaliações.',
                    style: TextStyle(fontSize: 12),
                  ),
                ],
              ),
            );
          }

          final controller = ref.read(progressProvider.notifier);

          return RefreshIndicator(
            onRefresh: () => ref.read(progressProvider.notifier).refresh(),
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
                final entry = items[index];
                final metrics = <String>[
                  if (entry.weight != null)
                    'Peso ${entry.weight!.toStringAsFixed(1)} kg',
                  if (entry.bodyFat != null)
                    'Gordura ${entry.bodyFat!.toStringAsFixed(1)}%',
                  if (entry.restingHeartRate != null)
                    'FC rep. ${entry.restingHeartRate} bpm',
                  if (entry.ftp != null) 'FTP ${entry.ftp} W',
                  if (entry.runningPace != null) entry.runningPace!,
                ];

                return ListTile(
                  leading: CircleAvatar(
                    child: Text(entry.recordedAtLabel.isEmpty
                        ? '?'
                        : entry.recordedAtLabel.substring(0, 5)),
                  ),
                  title: Text(entry.recordedAtLabel),
                  subtitle: Text(
                    metrics.isEmpty ? 'Sem métricas' : metrics.join(' · '),
                    maxLines: 2,
                    overflow: TextOverflow.ellipsis,
                  ),
                  trailing: const Icon(Icons.chevron_right),
                  onTap: () => _showDetails(context, entry),
                );
              },
            ),
          );
        },
      ),
    );
  }

  void _showDetails(BuildContext context, StudentProgress entry) {
    showModalBottomSheet<void>(
      context: context,
      builder: (context) => Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text('Avaliação de ${entry.recordedAtLabel}',
                style: Theme.of(context).textTheme.titleLarge),
            const SizedBox(height: 12),
            if (entry.weight != null)
              _row('Peso', '${entry.weight!.toStringAsFixed(1)} kg'),
            if (entry.bodyFat != null)
              _row('Gordura corporal',
                  '${entry.bodyFat!.toStringAsFixed(1)}%'),
            if (entry.restingHeartRate != null)
              _row('FC repouso', '${entry.restingHeartRate} bpm'),
            if (entry.maxHeartRate != null)
              _row('FC máxima', '${entry.maxHeartRate} bpm'),
            if (entry.ftp != null) _row('FTP', '${entry.ftp} W'),
            if (entry.runningPace != null)
              _row('Ritmo corrida', entry.runningPace!),
            if (entry.swimmingPace != null)
              _row('Ritmo natação', entry.swimmingPace!),
            if (entry.notes != null && entry.notes!.isNotEmpty) ...[
              const SizedBox(height: 8),
              Text(entry.notes!),
            ],
            const SizedBox(height: 24),
          ],
        ),
      ),
    );
  }

  Widget _row(String label, String value) => Padding(
        padding: const EdgeInsets.symmetric(vertical: 4),
        child: Row(
          mainAxisAlignment: MainAxisAlignment.spaceBetween,
          children: [Text(label), Text(value)],
        ),
      );
}
