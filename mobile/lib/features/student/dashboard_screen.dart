import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../core/theme/app_colors.dart';
import '../../core/widgets/app_card.dart';
import '../../core/widgets/app_tone.dart';
import '../../core/widgets/empty_state.dart';
import '../../core/widgets/icon_tile.dart';
import '../../core/widgets/skeleton.dart';
import '../../core/widgets/stat_card.dart';
import 'models/dashboard.dart';
import 'student_providers.dart';

/// Resumo do aluno: números do momento, pendências e atalhos.
class StudentDashboardScreen extends ConsumerWidget {
  const StudentDashboardScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final dashboard = ref.watch(dashboardProvider);

    return Scaffold(
      appBar: AppBar(title: const Text('Meu painel')),
      body: dashboard.when(
        loading: () => const SkeletonCardList(count: 6),
        error: (error, _) => RefreshableState(
          onRefresh: () => ref.read(dashboardProvider.notifier).refresh(),
          child: ErrorState(
            message: error.toString(),
            onRetry: () => ref.read(dashboardProvider.notifier).refresh(),
          ),
        ),
        data: (data) => RefreshIndicator(
          onRefresh: () => ref.read(dashboardProvider.notifier).refresh(),
          child: ListView(
            physics: const AlwaysScrollableScrollPhysics(),
            padding: const EdgeInsets.fromLTRB(16, 18, 16, 28),
            children: [
              _Greeting(data: data),
              const SizedBox(height: 18),
              _Numbers(data: data, onOpenNotifications: () => context.push('/notificacoes')),
              if (data.payments.pendingCount > 0) ...[
                const SizedBox(height: 16),
                AlertCard(
                  icon: Icons.attach_money_rounded,
                  tone: AppTone.warning,
                  title: data.payments.pendingCount == 1
                      ? '1 cobrança em aberto'
                      : '${data.payments.pendingCount} cobranças em aberto',
                  message:
                      'Total de ${_money(data.payments.pendingAmount)}. Toque para ver as cobranças.',
                  actionLabel: 'Ver cobranças',
                  onAction: () => context.push('/pagamentos'),
                  onTap: () => context.push('/pagamentos'),
                ),
              ],
              const SizedBox(height: 24),
              const Text(
                'Ir para',
                style: TextStyle(fontSize: 16, fontWeight: FontWeight.w700),
              ),
              const SizedBox(height: 12),
              _LinkCard(
                icon: Icons.directions_run_rounded,
                title: 'Treinos',
                subtitle: 'Iniciar, concluir e registrar métricas',
                onTap: () => context.push('/treinos'),
              ),
              const SizedBox(height: 10),
              _LinkCard(
                icon: Icons.calendar_month_rounded,
                title: 'Calendário',
                subtitle: 'Sessões programadas do mês',
                onTap: () => context.push('/calendario'),
              ),
              const SizedBox(height: 10),
              _LinkCard(
                icon: Icons.insights_rounded,
                title: 'Progresso',
                subtitle: 'Avaliações do seu treinador',
                onTap: () => context.push('/progresso'),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

/// Saudação com o objetivo do aluno (quando cadastrado).
class _Greeting extends StatelessWidget {
  const _Greeting({required this.data});

  final StudentDashboard data;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final goal = data.student.goal?.trim();

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text('Olá, ${data.student.name}!', style: theme.textTheme.headlineSmall),
        const SizedBox(height: 6),
        if (goal != null && goal.isNotEmpty)
          Row(
            children: [
              const Icon(Icons.flag_rounded, size: 16, color: AppColors.brand600),
              const SizedBox(width: 6),
              Expanded(
                child: Text('Objetivo: $goal', style: theme.textTheme.bodyMedium),
              ),
            ],
          )
        else
          Text(
            'Seu resumo de treinos está aqui embaixo.',
            style: theme.textTheme.bodySmall,
          ),
      ],
    );
  }
}

/// Grade 2x2 de indicadores.
class _Numbers extends StatelessWidget {
  const _Numbers({required this.data, required this.onOpenNotifications});

  final StudentDashboard data;
  final VoidCallback onOpenNotifications;

  @override
  Widget build(BuildContext context) {
    final hoje = data.trainings.today;
    final unread = data.unreadNotifications;

    return Column(
      children: [
        // `IntrinsicHeight` dá altura definida à linha: dentro do `ListView`
        // a altura máxima é infinita e o `stretch` não tem o que esticar
        // (quebrava o layout e a tela ficava em branco).
        IntrinsicHeight(
          child: Row(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              Expanded(
                child: StatCard(
                  icon: Icons.today_rounded,
                  label: 'Hoje',
                  value: '$hoje',
                  caption: hoje == 1 ? 'treino pendente' : 'treinos pendentes',
                  tone: hoje > 0 ? AppTone.brand : AppTone.neutral,
                  onTap: () => context.push('/treinos?status=planned'),
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: StatCard(
                  icon: Icons.emoji_events_rounded,
                  label: 'Concluídos no mês',
                  value: '${data.trainings.completedThisMonth}',
                  caption: 'treinos concluídos',
                  tone: AppTone.success,
                  onTap: () => context.push('/treinos?status=completed'),
                ),
              ),
            ],
          ),
        ),
        const SizedBox(height: 12),
        IntrinsicHeight(
          child: Row(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              Expanded(
                child: StatCard(
                  icon: Icons.menu_book_rounded,
                  label: 'Planos ativos',
                  value: '${data.activePlans}',
                  caption: 'em andamento',
                  tone: AppTone.info,
                  onTap: () => context.push('/planos'),
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: StatCard(
                  icon: Icons.notifications_none_rounded,
                  label: 'Avisos',
                  value: '$unread',
                  caption: 'não lidos',
                  tone: unread > 0 ? AppTone.warning : AppTone.neutral,
                  onTap: onOpenNotifications,
                ),
              ),
            ],
          ),
        ),
      ],
    );
  }
}

/// Card de navegação com ícone, título e seta.
class _LinkCard extends StatelessWidget {
  const _LinkCard({
    required this.icon,
    required this.title,
    required this.subtitle,
    required this.onTap,
  });

  final IconData icon;
  final String title;
  final String subtitle;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    return AppCard(
      onTap: onTap,
      padding: const EdgeInsets.all(14),
      child: Row(
        children: [
          IconTile(icon: icon, size: 42, radius: 13),
          const SizedBox(width: 14),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(title, style: theme.textTheme.titleSmall),
                const SizedBox(height: 2),
                Text(subtitle, style: theme.textTheme.bodySmall),
              ],
            ),
          ),
          const Icon(Icons.chevron_right_rounded,
              color: AppColors.textMuted, size: 22),
        ],
      ),
    );
  }
}

String _money(double value) {
  final parts = value.toStringAsFixed(2).split('.');
  final comPontos = parts.first.replaceAllMapped(
    RegExp(r'(\d)(?=(\d{3})+$)'),
    (m) => '${m[1]}.',
  );
  return 'R\$ $comPontos,${parts.last}';
}
