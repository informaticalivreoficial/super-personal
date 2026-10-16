import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../core/theme/app_colors.dart';
import '../../core/widgets/app_card.dart';
import '../../core/widgets/app_header.dart';
import '../../core/widgets/app_tone.dart';
import '../../core/widgets/empty_state.dart';
import '../../core/widgets/icon_tile.dart';
import '../../core/widgets/skeleton.dart';
import '../auth/auth_controller.dart';
import '../auth/user.dart';
import '../student/models/dashboard.dart';
import '../student/student_providers.dart';

String _initials(String? name) {
  if (name == null || name.trim().isEmpty) return '?';
  return name.trim()[0].toUpperCase();
}

/// Atalhos do menu (ícone, título, subtítulo e rota).
const _shortcuts = <({IconData icon, String title, String subtitle, String route})>[
  (
    icon: Icons.dashboard_rounded,
    title: 'Meu painel',
    subtitle: 'Resumo e pendências',
    route: '/painel',
  ),
  (
    icon: Icons.directions_run_rounded,
    title: 'Treinos',
    subtitle: 'Executar e registrar',
    route: '/treinos',
  ),
  (
    icon: Icons.calendar_month_rounded,
    title: 'Calendário',
    subtitle: 'Sessões do mês',
    route: '/calendario',
  ),
  (
    icon: Icons.menu_book_rounded,
    title: 'Planos',
    subtitle: 'Semanas e sessões',
    route: '/planos',
  ),
  (
    icon: Icons.insights_rounded,
    title: 'Progresso',
    subtitle: 'Avaliações e evolução',
    route: '/progresso',
  ),
  (
    icon: Icons.receipt_long_rounded,
    title: 'Pagamentos',
    subtitle: 'Cobranças e Pix',
    route: '/pagamentos',
  ),
];

/// Home do aluno: saudação, resumo do dia e atalhos.
class HomeScreen extends ConsumerWidget {
  const HomeScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final User? user = ref.watch(authControllerProvider).value;
    final dashboard = ref.watch(dashboardProvider);
    final data = dashboard.value;
    final unread = data?.unreadNotifications ?? 0;
    final photoUrl = ref.watch(profileProvider).value?.photoUrl;

    return Scaffold(
      body: RefreshIndicator(
        onRefresh: () => ref.read(dashboardProvider.notifier).refresh(),
        child: ListView(
          physics: const AlwaysScrollableScrollPhysics(),
          padding: EdgeInsets.zero,
          children: [
            _Header(
              name: user?.name,
              photoUrl: photoUrl,
              unread: unread,
              data: data,
              loading: dashboard.isLoading,
              onNotifications: () => context.push('/notificacoes'),
              onLogout: () => ref.read(authControllerProvider.notifier).logout(),
            ),
            Padding(
              padding: const EdgeInsets.fromLTRB(16, 18, 16, 28),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  if (dashboard.hasError)
                    Padding(
                      padding: const EdgeInsets.only(bottom: 16),
                      child: AlertCard(
                        icon: Icons.cloud_off_rounded,
                        tone: AppTone.danger,
                        title: 'Não conseguimos carregar seu resumo',
                        message: 'Confira sua conexão e tente de novo.',
                        actionLabel: 'Tentar novamente',
                        onAction: () =>
                            ref.read(dashboardProvider.notifier).refresh(),
                      ),
                    ),
                  if (data != null && data.payments.pendingCount > 0)
                    Padding(
                      padding: const EdgeInsets.only(bottom: 16),
                      child: AlertCard(
                        icon: Icons.attach_money_rounded,
                        tone: AppTone.warning,
                        title: data.payments.pendingCount == 1
                            ? 'Você tem 1 cobrança em aberto'
                            : 'Você tem ${data.payments.pendingCount} cobranças em aberto',
                        message:
                            'Total de ${_money(data.payments.pendingAmount)}. Toque para pagar no app.',
                        actionLabel: 'Ver cobranças',
                        onAction: () => context.push('/pagamentos'),
                        onTap: () => context.push('/pagamentos'),
                      ),
                    ),
                  const Row(
                    children: [
                      Expanded(
                        child: Text(
                          'Acesso rápido',
                          style: TextStyle(
                            fontSize: 16,
                            fontWeight: FontWeight.w700,
                          ),
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 12),
                  ..._shortcutRows(context),
                  const SizedBox(height: 20),
                  AlertCard(
                    icon: Icons.lightbulb_outline_rounded,
                    tone: AppTone.info,
                    title: 'Dica',
                    message:
                        'Depois do treino, registre duração, distância e como você se sentiu. '
                        'Seu treinador acompanha sua evolução por aqui.',
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }

  /// Atalhos em pares (duas colunas).
  List<Widget> _shortcutRows(BuildContext context) {
    final rows = <Widget>[];

    for (var i = 0; i < _shortcuts.length; i += 2) {
      final left = _shortcuts[i];
      final right = i + 1 < _shortcuts.length ? _shortcuts[i + 1] : null;

      rows.add(
        Padding(
          padding: EdgeInsets.only(bottom: i + 2 < _shortcuts.length ? 12 : 0),
          // `IntrinsicHeight` dá uma altura definida à linha: dentro do
          // `ListView` a altura máxima é infinita e o `stretch` não tem o que
          // esticar (quebrava o layout e a tela ficava em branco).
          child: IntrinsicHeight(
            child: Row(
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                Expanded(child: _ShortcutCard(shortcut: left)),
                const SizedBox(width: 12),
                Expanded(
                  child: right == null
                      ? const SizedBox.shrink()
                      : _ShortcutCard(shortcut: right),
                ),
              ],
            ),
          ),
        ),
      );
    }

    return rows;
  }
}

/// Cabeçalho em gradiente: saudação, avatar, ações e números do dia.
class _Header extends StatelessWidget {
  const _Header({
    required this.name,
    required this.photoUrl,
    required this.unread,
    required this.data,
    required this.loading,
    required this.onNotifications,
    required this.onLogout,
  });

  final String? name;
  final String? photoUrl;
  final int unread;
  final StudentDashboard? data;
  final bool loading;
  final VoidCallback onNotifications;
  final VoidCallback onLogout;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final firstName = (name ?? '').trim().split(' ').first;

    return AppHeader(
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          Row(
            children: [
              _Avatar(
                photoUrl: photoUrl,
                initial: _initials(name),
                onTap: () => context.push('/perfil'),
              ),
              const SizedBox(width: 14),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      firstName.isEmpty ? 'Olá!' : 'Olá, $firstName!',
                      style: theme.textTheme.titleLarge!.copyWith(
                        color: Colors.white,
                        fontSize: 19,
                      ),
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                    ),
                    const SizedBox(height: 2),
                    Text(
                      'Bora treinar hoje?',
                      style: theme.textTheme.bodySmall!.copyWith(
                        color: Colors.white.withValues(alpha: 0.72),
                      ),
                    ),
                  ],
                ),
              ),
              _HeaderAction(
                icon: Icons.notifications_none_rounded,
                tooltip: 'Notificações',
                badge: unread,
                onTap: onNotifications,
              ),
              const SizedBox(width: 8),
              _HeaderAction(
                icon: Icons.logout_rounded,
                tooltip: 'Sair',
                onTap: onLogout,
              ),
            ],
          ),
          const SizedBox(height: 20),
          if (loading && data == null)
            const Row(
              children: [
                Expanded(child: SkeletonBox(height: 62, radius: 16)),
                SizedBox(width: 10),
                Expanded(child: SkeletonBox(height: 62, radius: 16)),
                SizedBox(width: 10),
                Expanded(child: SkeletonBox(height: 62, radius: 16)),
              ],
            )
          else
            Row(
              children: [
                Expanded(
                  child: _HeaderStat(
                    label: 'Hoje',
                    value: '${data?.trainings.today ?? 0}',
                    hint: 'treinos',
                  ),
                ),
                const SizedBox(width: 10),
                Expanded(
                  child: _HeaderStat(
                    label: 'Próximos',
                    value: '${data?.trainings.next ?? 0}',
                    hint: 'agendados',
                  ),
                ),
                const SizedBox(width: 10),
                Expanded(
                  child: _HeaderStat(
                    label: 'No mês',
                    value: '${data?.trainings.completedThisMonth ?? 0}',
                    hint: 'concluídos',
                  ),
                ),
              ],
            ),
        ],
      ),
    );
  }
}

class _Avatar extends StatelessWidget {
  const _Avatar({
    required this.photoUrl,
    required this.initial,
    required this.onTap,
  });

  final String? photoUrl;
  final String initial;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: onTap,
      child: Container(
        padding: const EdgeInsets.all(2.5),
        decoration: BoxDecoration(
          shape: BoxShape.circle,
          border: Border.all(color: Colors.white.withValues(alpha: 0.55), width: 2),
        ),
        child: CircleAvatar(
          radius: 21,
          backgroundColor: AppColors.brand400,
          foregroundImage: photoUrl == null ? null : NetworkImage(photoUrl!),
          child: Text(
            initial,
            style: const TextStyle(
              color: AppColors.brand950,
              fontWeight: FontWeight.w800,
              fontSize: 17,
            ),
          ),
        ),
      ),
    );
  }
}

class _HeaderAction extends StatelessWidget {
  const _HeaderAction({
    required this.icon,
    required this.tooltip,
    required this.onTap,
    this.badge = 0,
  });

  final IconData icon;
  final String tooltip;
  final VoidCallback onTap;
  final int badge;

  @override
  Widget build(BuildContext context) {
    return Tooltip(
      message: tooltip,
      child: Material(
        color: Colors.white.withValues(alpha: 0.14),
        shape: const CircleBorder(),
        clipBehavior: Clip.antiAlias,
        child: InkWell(
          onTap: onTap,
          child: SizedBox(
            width: 42,
            height: 42,
            child: Center(
              child: Badge(
                isLabelVisible: badge > 0,
                label: Text('$badge'),
                backgroundColor: AppColors.brand400,
                textColor: AppColors.brand950,
                child: Icon(icon, color: Colors.white, size: 22),
              ),
            ),
          ),
        ),
      ),
    );
  }
}

/// Número do topo do cabeçalho (fundo translúcido).
class _HeaderStat extends StatelessWidget {
  const _HeaderStat({
    required this.label,
    required this.value,
    required this.hint,
  });

  final String label;
  final String value;
  final String hint;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
      decoration: BoxDecoration(
        color: Colors.white.withValues(alpha: 0.12),
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: Colors.white.withValues(alpha: 0.16)),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        mainAxisSize: MainAxisSize.min,
        children: [
          Text(
            value,
            style: theme.textTheme.titleLarge!.copyWith(
              color: Colors.white,
              fontSize: 20,
            ),
          ),
          Text(
            label,
            style: theme.textTheme.labelSmall!.copyWith(
              color: Colors.white.withValues(alpha: 0.92),
            ),
          ),
          Text(
            hint,
            style: theme.textTheme.labelSmall!.copyWith(
              color: Colors.white.withValues(alpha: 0.58),
              fontWeight: FontWeight.w500,
              fontSize: 10,
            ),
          ),
        ],
      ),
    );
  }
}

/// Card de atalho do menu.
class _ShortcutCard extends StatelessWidget {
  const _ShortcutCard({required this.shortcut});

  final ({IconData icon, String title, String subtitle, String route}) shortcut;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    return AppCard(
      onTap: () => context.push(shortcut.route),
      padding: const EdgeInsets.all(14),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          IconTile(icon: shortcut.icon, size: 40, radius: 13),
          const SizedBox(height: 12),
          Text(shortcut.title, style: theme.textTheme.titleSmall),
          const SizedBox(height: 2),
          Text(
            shortcut.subtitle,
            style: theme.textTheme.bodySmall!
                .copyWith(color: AppColors.textMuted, fontSize: 11.5),
            maxLines: 2,
            overflow: TextOverflow.ellipsis,
          ),
        ],
      ),
    );
  }
}

String _money(double value) {
  final parts = value.toStringAsFixed(2).split('.');
  final inteiros = parts.first;
  final comPontos = inteiros.replaceAllMapped(
    RegExp(r'(\d)(?=(\d{3})+$)'),
    (m) => '${m[1]}.',
  );
  return 'R\$ $comPontos,${parts.last}';
}
