import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../core/polling/live_refresh.dart';
import '../../core/theme/app_colors.dart';
import '../../core/widgets/app_card.dart';
import '../../core/widgets/app_tone.dart';
import '../../core/widgets/empty_state.dart';
import '../../core/widgets/icon_tile.dart';
import '../../core/widgets/skeleton.dart';
import 'models/notification.dart';
import 'student_providers.dart';

/// Notificações do aluno (mensagens do treinador e avisos automáticos).
class NotificationsScreen extends ConsumerStatefulWidget {
  const NotificationsScreen({super.key});

  @override
  ConsumerState<NotificationsScreen> createState() =>
      _NotificationsScreenState();
}

class _NotificationsScreenState extends ConsumerState<NotificationsScreen> {
  final _scrollController = ScrollController();

  @override
  void initState() {
    super.initState();
    _scrollController.addListener(() {
      if (!_scrollController.hasClients) return;
      final position = _scrollController.position;
      if (position.pixels >= position.maxScrollExtent - 200) {
        ref.read(notificationsProvider.notifier).loadMore();
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
    final notifications = ref.watch(notificationsProvider);

    return Scaffold(
      appBar: AppBar(title: const Text('Notificações')),
      // Com a tela aberta, o que chegar entra no topo da lista sozinho (o
      // pull-to-refresh continua valendo). Com o app fechado quem avisa é o
      // push (FCM).
      body: LiveRefresh(
        interval: const Duration(seconds: 20),
        onTick: (ref) =>
            ref.read(notificationsProvider.notifier).refreshSilently(),
        child: notifications.when(
          loading: () => const SkeletonCardList(count: 6),
          error: (error, _) => RefreshableState(
            onRefresh: () =>
                ref.read(notificationsProvider.notifier).refresh(),
            child: ErrorState(
              message: error.toString(),
              onRetry: () =>
                  ref.read(notificationsProvider.notifier).refresh(),
            ),
          ),
          data: (items) {
            if (items.isEmpty) {
              return RefreshableState(
                onRefresh: () =>
                    ref.read(notificationsProvider.notifier).refresh(),
                child: const EmptyState(
                  icon: Icons.notifications_none_rounded,
                  title: 'Nenhum aviso por aqui',
                  message: 'Mensagens do seu treinador e lembretes de treino e '
                      'de cobrança aparecem nesta tela.',
                ),
              );
            }

            final controller = ref.read(notificationsProvider.notifier);

            return RefreshIndicator(
              onRefresh: () =>
                  ref.read(notificationsProvider.notifier).refresh(),
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

                  return _NotificationCard(notification: items[index]);
                },
              ),
            );
          },
        ),
      ),
    );
  }
}

/// Card de uma notificação (não lida ganha destaque da marca).
class _NotificationCard extends StatelessWidget {
  const _NotificationCard({required this.notification});

  final AppNotification notification;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final unread = !notification.isRead;
    final visual = _visual(notification.type);

    return AppCard(
      color: unread ? AppColors.brand50 : null,
      borderColor: unread ? AppColors.brand200 : null,
      padding: const EdgeInsets.all(14),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          IconTile(
            icon: visual.icon,
            tone: unread ? visual.tone : AppTone.neutral,
            size: 44,
          ),
          const SizedBox(width: 14),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Expanded(
                      child: Text(
                        notification.title,
                        style: theme.textTheme.titleSmall!.copyWith(
                          fontWeight:
                              unread ? FontWeight.w700 : FontWeight.w600,
                        ),
                      ),
                    ),
                    if (unread) ...[
                      const SizedBox(width: 8),
                      Container(
                        width: 9,
                        height: 9,
                        margin: const EdgeInsets.only(top: 5),
                        decoration: const BoxDecoration(
                          color: AppColors.brand500,
                          shape: BoxShape.circle,
                        ),
                      ),
                    ],
                  ],
                ),
                if (notification.message.isNotEmpty) ...[
                  const SizedBox(height: 4),
                  Text(
                    notification.message,
                    style: theme.textTheme.bodySmall!.copyWith(
                      color: AppColors.textSecondary,
                    ),
                  ),
                ],
                if (notification.createdAt != null) ...[
                  const SizedBox(height: 8),
                  Row(
                    children: [
                      const Icon(
                        Icons.schedule_rounded,
                        size: 14,
                        color: AppColors.textMuted,
                      ),
                      const SizedBox(width: 4),
                      Text(
                        _when(notification.createdAt!),
                        style: theme.textTheme.labelSmall!.copyWith(
                          color: AppColors.textMuted,
                        ),
                      ),
                    ],
                  ),
                ],
              ],
            ),
          ),
        ],
      ),
    );
  }

  /// Ícone/tom pelo tipo cru da notificação (`App\Notifications\...`).
  static ({IconData icon, AppTone tone}) _visual(String type) {
    final tipo = type.toLowerCase();

    if (tipo.contains('message')) {
      return (icon: Icons.chat_bubble_outline_rounded, tone: AppTone.info);
    }
    if (tipo.contains('payment')) {
      return (icon: Icons.attach_money_rounded, tone: AppTone.warning);
    }
    if (tipo.contains('progress')) {
      return (icon: Icons.insights_rounded, tone: AppTone.success);
    }
    if (tipo.contains('training')) {
      return (icon: Icons.directions_run_rounded, tone: AppTone.brand);
    }

    return (icon: Icons.notifications_none_rounded, tone: AppTone.brand);
  }

  /// Data amigável: `Hoje 14:30`, `Ontem 09:05` ou `12/10/2026 08:00`.
  static String _when(DateTime date) {
    final local = date.toLocal();
    final hora =
        '${_two(local.hour)}:${_two(local.minute)}';
    final hoje = DateTime.now();
    final dia = DateTime(local.year, local.month, local.day);
    final base = DateTime(hoje.year, hoje.month, hoje.day);
    final diff = dia.difference(base).inDays;

    if (diff == 0) return 'Hoje $hora';
    if (diff == -1) return 'Ontem $hora';

    return '${_two(local.day)}/${_two(local.month)}/${local.year} $hora';
  }

  static String _two(int value) => value.toString().padLeft(2, '0');
}
