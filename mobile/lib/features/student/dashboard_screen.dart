import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import 'student_providers.dart';

/// Resumo do aluno: treinos de hoje, plano ativo, pagamentos e avisos.
class StudentDashboardScreen extends ConsumerWidget {
  const StudentDashboardScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final dashboard = ref.watch(dashboardProvider);

    return Scaffold(
      appBar: AppBar(title: const Text('Meu painel')),
      body: RefreshIndicator(
        onRefresh: () => ref.read(dashboardProvider.notifier).refresh(),
        child: dashboard.when(
          loading: () => const _LoadingList(),
          error: (error, _) => _ErrorList(
            message: error.toString(),
            onRetry: () => ref.read(dashboardProvider.notifier).refresh(),
          ),
          data: (data) => ListView(
            physics: const AlwaysScrollableScrollPhysics(),
            padding: const EdgeInsets.all(16),
            children: [
              Text(
                'Olá, ${data.student.name}!',
                style: Theme.of(context).textTheme.headlineSmall,
              ),
              if (data.student.goal != null &&
                  data.student.goal!.isNotEmpty) ...[
                const SizedBox(height: 4),
                Text(
                  'Objetivo: ${data.student.goal}',
                  style: Theme.of(context).textTheme.bodyMedium,
                ),
              ],
              const SizedBox(height: 16),
              Row(
                children: [
                  Expanded(
                    child: _StatCard(
                      icon: Icons.today,
                      label: 'Hoje',
                      value: '${data.trainings.today}',
                      subtitle: data.trainings.today == 1
                          ? 'treino pendente'
                          : 'treinos pendentes',
                    ),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: _StatCard(
                      icon: Icons.check_circle_outline,
                      label: 'No mês',
                      value: '${data.trainings.completedThisMonth}',
                      subtitle: 'concluídos',
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 12),
              Row(
                children: [
                  Expanded(
                    child: _StatCard(
                      icon: Icons.menu_book_outlined,
                      label: 'Planos',
                      value: '${data.activePlans}',
                      subtitle: 'ativos',
                    ),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: _StatCard(
                      icon: Icons.notifications_outlined,
                      label: 'Avisos',
                      value: '${data.unreadNotifications}',
                      subtitle: 'não lidos',
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 20),
              if (data.payments.pendingCount > 0)
                Card(
                  color: Theme.of(context).colorScheme.errorContainer,
                  child: ListTile(
                    leading: const Icon(Icons.attach_money),
                    title: Text(
                      '${data.payments.pendingCount} '
                      '${data.payments.pendingCount == 1 ? 'pagamento pendente' : 'pagamentos pendentes'}',
                    ),
                    subtitle: Text('Total: R\$ ${data.payments.pendingAmount.toStringAsFixed(2)}'),
                    trailing: const Icon(Icons.chevron_right),
                    onTap: () => context.go('/pagamentos'),
                  ),
                ),
              const SizedBox(height: 8),
              Card(
                child: ListTile(
                  leading: const Icon(Icons.play_circle_outline),
                  title: const Text('Treinos'),
                  subtitle: const Text('Iniciar, concluir e registrar métricas'),
                  trailing: const Icon(Icons.chevron_right),
                  onTap: () => context.go('/treinos'),
                ),
              ),
              Card(
                child: ListTile(
                  leading: const Icon(Icons.calendar_month),
                  title: const Text('Calendário'),
                  subtitle: const Text('Sessões programadas do mês'),
                  trailing: const Icon(Icons.chevron_right),
                  onTap: () => context.go('/calendario'),
                ),
              ),
              Card(
                child: ListTile(
                  leading: const Icon(Icons.show_chart),
                  title: const Text('Progresso'),
                  subtitle: const Text('Avaliações e evolução'),
                  trailing: const Icon(Icons.chevron_right),
                  onTap: () => context.go('/progresso'),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _StatCard extends StatelessWidget {
  const _StatCard({
    required this.icon,
    required this.label,
    required this.value,
    required this.subtitle,
  });

  final IconData icon;
  final String label;
  final String value;
  final String subtitle;

  @override
  Widget build(BuildContext context) {
    final scheme = Theme.of(context).colorScheme;

    return Card(
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Icon(icon, size: 18, color: scheme.primary),
                const SizedBox(width: 6),
                Text(label, style: Theme.of(context).textTheme.labelMedium),
              ],
            ),
            const SizedBox(height: 8),
            Text(value, style: Theme.of(context).textTheme.headlineSmall),
            Text(subtitle, style: Theme.of(context).textTheme.bodySmall),
          ],
        ),
      ),
    );
  }
}

class _LoadingList extends StatelessWidget {
  const _LoadingList();

  @override
  Widget build(BuildContext context) {
    return ListView(
      padding: const EdgeInsets.all(16),
      children: const [
        SizedBox(height: 48),
        Center(child: CircularProgressIndicator()),
      ],
    );
  }
}

class _ErrorList extends StatelessWidget {
  const _ErrorList({required this.message, required this.onRetry});

  final String message;
  final VoidCallback onRetry;

  @override
  Widget build(BuildContext context) {
    return ListView(
      padding: const EdgeInsets.all(16),
      children: [
        const SizedBox(height: 48),
        Icon(Icons.cloud_off,
            size: 48, color: Theme.of(context).colorScheme.outline),
        const SizedBox(height: 12),
        Text(message, textAlign: TextAlign.center),
        const SizedBox(height: 12),
        FilledButton(onPressed: onRetry, child: const Text('Tentar novamente')),
      ],
    );
  }
}
