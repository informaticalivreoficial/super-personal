import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../auth/auth_controller.dart';
import '../auth/user.dart';
import '../student/student_providers.dart';

String _initials(String? name) {
  if (name == null || name.trim().isEmpty) return '?';
  return name.trim()[0].toUpperCase();
}

/// Home do aluno: saudação, acesso rápido e navegação para as telas.
class HomeScreen extends ConsumerWidget {
  const HomeScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final User? user = ref.watch(authControllerProvider).value;
    final dashboard = ref.watch(dashboardProvider);
    final unread = dashboard.value?.unreadNotifications ?? 0;
    final scheme = Theme.of(context).colorScheme;

    return Scaffold(
      appBar: AppBar(
        title: const Text('SportPlan'),
        actions: [
          IconButton(
            tooltip: 'Notificações',
            icon: Badge(
              isLabelVisible: unread > 0,
              label: Text('$unread'),
              child: const Icon(Icons.notifications_outlined),
            ),
            onPressed: () => context.push('/notificacoes'),
          ),
          IconButton(
            tooltip: 'Sair',
            icon: const Icon(Icons.logout),
            onPressed: () =>
                ref.read(authControllerProvider.notifier).logout(),
          ),
        ],
      ),
      body: RefreshIndicator(
        onRefresh: () => ref.read(dashboardProvider.notifier).refresh(),
        child: ListView(
          physics: const AlwaysScrollableScrollPhysics(),
          padding: const EdgeInsets.all(16),
          children: [
            Card(
              color: scheme.primaryContainer,
              child: Padding(
                padding: const EdgeInsets.all(16),
                child: Row(
                  children: [
                    CircleAvatar(child: Text(_initials(user?.name))),
                    const SizedBox(width: 16),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            user?.name ?? '',
                            style: Theme.of(context).textTheme.titleMedium,
                          ),
                          Text(
                            user?.email ?? '',
                            style: Theme.of(context).textTheme.bodySmall,
                          ),
                        ],
                      ),
                    ),
                  ],
                ),
              ),
            ),
            const SizedBox(height: 16),
            Card(
              child: ListTile(
                leading: const Icon(Icons.dashboard_outlined),
                title: const Text('Meu painel'),
                subtitle:
                    const Text('Treinos de hoje, plano ativo e pendências'),
                trailing: const Icon(Icons.chevron_right),
                onTap: () => context.push('/painel'),
              ),
            ),
            Card(
              child: ListTile(
                leading: const Icon(Icons.play_circle_outline),
                title: const Text('Treinos'),
                subtitle:
                    const Text('Iniciar, concluir e registrar métricas'),
                trailing: const Icon(Icons.chevron_right),
                onTap: () => context.push('/treinos'),
              ),
            ),
            Card(
              child: ListTile(
                leading: const Icon(Icons.calendar_month),
                title: const Text('Calendário'),
                subtitle: const Text('Sessões programadas'),
                trailing: const Icon(Icons.chevron_right),
                onTap: () => context.push('/calendario'),
              ),
            ),
            Card(
              child: ListTile(
                leading: const Icon(Icons.menu_book_outlined),
                title: const Text('Planos'),
                subtitle: const Text('Semanas e sessões dos planos ativos'),
                trailing: const Icon(Icons.chevron_right),
                onTap: () => context.push('/planos'),
              ),
            ),
            Card(
              child: ListTile(
                leading: const Icon(Icons.show_chart),
                title: const Text('Progresso'),
                subtitle: const Text('Avaliações e evolução'),
                trailing: const Icon(Icons.chevron_right),
                onTap: () => context.push('/progresso'),
              ),
            ),
            Card(
              child: ListTile(
                leading: const Icon(Icons.receipt_long),
                title: const Text('Pagamentos'),
                subtitle: const Text('Histórico de cobranças'),
                trailing: const Icon(Icons.chevron_right),
                onTap: () => context.push('/pagamentos'),
              ),
            ),
            Card(
              child: ListTile(
                leading: const Icon(Icons.notifications_outlined),
                title: const Text('Notificações'),
                subtitle: const Text('Avisos do treinador'),
                trailing: Badge(
                  isLabelVisible: unread > 0,
                  label: Text('$unread'),
                  child: const Icon(Icons.chevron_right),
                ),
                onTap: () => context.push('/notificacoes'),
              ),
            ),
          ],
        ),
      ),
    );
  }
}
