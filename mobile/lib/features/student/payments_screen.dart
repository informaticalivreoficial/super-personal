import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import 'student_providers.dart';

/// Pagamentos do aluno (histórico com status).
class PaymentsScreen extends ConsumerStatefulWidget {
  const PaymentsScreen({super.key});

  @override
  ConsumerState<PaymentsScreen> createState() => _PaymentsScreenState();
}

class _PaymentsScreenState extends ConsumerState<PaymentsScreen> {
  final _scrollController = ScrollController();

  @override
  void initState() {
    super.initState();
    _scrollController.addListener(() {
      if (!_scrollController.hasClients) return;
      final position = _scrollController.position;
      if (position.pixels >= position.maxScrollExtent - 200) {
        ref.read(paymentsProvider.notifier).loadMore();
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
    final payments = ref.watch(paymentsProvider);
    final scheme = Theme.of(context).colorScheme;

    return Scaffold(
      appBar: AppBar(title: const Text('Pagamentos')),
      body: payments.when(
        loading: () => const Center(child: CircularProgressIndicator()),
        error: (error, _) => Center(
          child: Padding(
            padding: const EdgeInsets.all(24),
            child: Column(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                Icon(Icons.cloud_off,
                    size: 48, color: scheme.outline),
                const SizedBox(height: 12),
                Text(error.toString(), textAlign: TextAlign.center),
                const SizedBox(height: 12),
                FilledButton(
                  onPressed: () =>
                      ref.read(paymentsProvider.notifier).refresh(),
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
                  Icon(Icons.receipt_long, size: 48),
                  SizedBox(height: 12),
                  Text('Nenhum pagamento registrado.'),
                ],
              ),
            );
          }

          final controller = ref.read(paymentsProvider.notifier);

          return RefreshIndicator(
            onRefresh: () => ref.read(paymentsProvider.notifier).refresh(),
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
                final payment = items[index];
                final statusColor = switch (payment.status) {
                  'paid' => scheme.primary,
                  'overdue' => scheme.error,
                  'pending' => scheme.tertiary,
                  _ => scheme.outline,
                };

                return ListTile(
                  leading: CircleAvatar(
                    backgroundColor: statusColor.withValues(alpha: 0.15),
                    foregroundColor: statusColor,
                    child: const Icon(Icons.attach_money, size: 20),
                  ),
                  title: Text(payment.description),
                  subtitle: Text([
                    payment.amountLabel,
                    if (payment.dueDate != null)
                      'Vence ${payment.dueDate!.day.toString().padLeft(2, '0')}/'
                          '${payment.dueDate!.month.toString().padLeft(2, '0')}/'
                          '${payment.dueDate!.year}',
                  ].join(' · ')),
                  trailing: Chip(
                    label: Text(payment.statusLabel ?? payment.status),
                    visualDensity: VisualDensity.compact,
                    backgroundColor: statusColor.withValues(alpha: 0.15),
                    side: BorderSide.none,
                  ),
                );
              },
            ),
          );
        },
      ),
    );
  }
}
