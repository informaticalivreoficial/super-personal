import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../auth/auth_controller.dart';
import '../auth/user.dart';

String _initials(String? name) {
  if (name == null || name.trim().isEmpty) return '?';
  return name.trim()[0].toUpperCase();
}

/// Home provisória do aluno: mostra a sessão e antecipa as próximas telas.
class HomeScreen extends ConsumerWidget {
  const HomeScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final User? user = ref.watch(authControllerProvider).value;
    final scheme = Theme.of(context).colorScheme;

    return Scaffold(
      appBar: AppBar(
        title: const Text('Super Personal'),
        actions: [
          IconButton(
            tooltip: 'Sair',
            icon: const Icon(Icons.logout),
            onPressed: () =>
                ref.read(authControllerProvider.notifier).logout(),
          ),
        ],
      ),
      body: ListView(
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
          Text(
            'Próximas telas',
            style: Theme.of(context).textTheme.titleMedium,
          ),
          const SizedBox(height: 8),
          const _PlaceholderTile(
            icon: Icons.dashboard_outlined,
            title: 'Dashboard',
            subtitle: 'Treinos de hoje, plano ativo e pendências',
          ),
          const _PlaceholderTile(
            icon: Icons.calendar_month,
            title: 'Calendário',
            subtitle: 'Sessões programadas',
          ),
          const _PlaceholderTile(
            icon: Icons.play_circle_outline,
            title: 'Treinos',
            subtitle: 'Iniciar, concluir e registrar métricas',
          ),
          const _PlaceholderTile(
            icon: Icons.show_chart,
            title: 'Progresso',
            subtitle: 'Avaliações e evolução',
          ),
          const _PlaceholderTile(
            icon: Icons.notifications_outlined,
            title: 'Notificações',
            subtitle: 'Avisos do professor',
          ),
        ],
      ),
    );
  }
}

class _PlaceholderTile extends StatelessWidget {
  const _PlaceholderTile({
    required this.icon,
    required this.title,
    required this.subtitle,
  });

  final IconData icon;
  final String title;
  final String subtitle;

  @override
  Widget build(BuildContext context) {
    return Card(
      child: ListTile(
        leading: Icon(icon),
        title: Text(title),
        subtitle: Text(subtitle),
      ),
    );
  }
}
