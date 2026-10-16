import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../core/theme/app_colors.dart';
import '../../core/widgets/app_card.dart';
import '../../core/widgets/app_tone.dart';
import '../../core/widgets/empty_state.dart';
import '../../core/widgets/icon_tile.dart';
import '../../core/widgets/skeleton.dart';
import '../../core/widgets/status_chip.dart';
import 'models/payment.dart';
import 'pix_payment_sheet.dart';
import 'student_providers.dart';

/// Pagamentos do aluno (resumo do que está em aberto + histórico).
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

  /// Gera o Pix da cobrança online e, ao voltar, recarrega a lista.
  Future<void> _openPix(Payment payment) async {
    final paid = await showPixPaymentSheet(context, payment);
    if (paid != true || !mounted) return;

    await ref.read(paymentsProvider.notifier).refresh();
    if (!mounted) return;

    ScaffoldMessenger.of(context).showSnackBar(
      const SnackBar(
        content: Text(
          'Pagamento em conferência. O status muda para "Pago" assim que '
          'for identificado.',
        ),
      ),
    );
  }

  Future<void> _refresh() => ref.read(paymentsProvider.notifier).refresh();

  @override
  Widget build(BuildContext context) {
    final payments = ref.watch(paymentsProvider);

    return Scaffold(
      appBar: AppBar(title: const Text('Pagamentos')),
      body: payments.when(
        loading: () => const SkeletonCardList(count: 5),
        error: (error, _) => RefreshableState(
          onRefresh: _refresh,
          child: ErrorState(message: error.toString(), onRetry: _refresh),
        ),
        data: (items) {
          if (items.isEmpty) {
            return RefreshableState(
              onRefresh: _refresh,
              child: const EmptyState(
                icon: Icons.receipt_long_rounded,
                title: 'Nenhuma cobrança por aqui',
                message: 'Quando seu treinador lançar uma cobrança, ela '
                    'aparece nesta tela.',
              ),
            );
          }

          final controller = ref.read(paymentsProvider.notifier);

          return RefreshIndicator(
            onRefresh: _refresh,
            child: ListView.separated(
              controller: _scrollController,
              physics: const AlwaysScrollableScrollPhysics(),
              padding: const EdgeInsets.fromLTRB(16, 16, 16, 28),
              // O resumo é o primeiro item da lista (index 0).
              itemCount: items.length + 1 + (controller.hasMore ? 1 : 0),
              separatorBuilder: (_, _) => const SizedBox(height: 12),
              itemBuilder: (context, index) {
                if (index == 0) return _PaymentsSummary(items: items);

                final item = index - 1;

                if (item >= items.length) {
                  return const Padding(
                    padding: EdgeInsets.symmetric(vertical: 16),
                    child: Center(child: CircularProgressIndicator()),
                  );
                }

                return _PaymentCard(
                  payment: items[item],
                  onPay: () => _openPix(items[item]),
                );
              },
            ),
          );
        },
      ),
    );
  }
}

/// Resumo do que está em aberto (destacado no topo da lista).
class _PaymentsSummary extends StatelessWidget {
  const _PaymentsSummary({required this.items});

  final List<Payment> items;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final abertas = items
        .where((item) => item.status == 'pending' || item.status == 'overdue')
        .toList();
    final total = abertas.fold<double>(0, (soma, item) => soma + item.amount);
    final emDia = abertas.isEmpty;

    return AppCard(
      color: emDia ? AppColors.successSoft : AppColors.warningSoft,
      borderColor:
          (emDia ? AppTone.success : AppTone.warning).foreground.withValues(alpha: 0.18),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              IconTile(
                icon: emDia
                    ? Icons.check_circle_outline_rounded
                    : Icons.schedule_rounded,
                tone: emDia ? AppTone.success : AppTone.warning,
                size: 48,
                radius: 15,
              ),
              const SizedBox(width: 14),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      emDia ? 'Tudo em dia!' : 'Em aberto',
                      style: theme.textTheme.titleSmall,
                    ),
                    const SizedBox(height: 2),
                    Text(
                      _money(total),
                      style: theme.textTheme.headlineSmall,
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                    ),
                  ],
                ),
              ),
            ],
          ),
          const SizedBox(height: 10),
          Text(
            emDia
                ? 'Você não tem cobranças aguardando pagamento.'
                : '${abertas.length} ${abertas.length == 1 ? 'cobrança aguardando' : 'cobranças aguardando'} '
                    'pagamento. Toque em "Pagar com Pix" para pagar pelo app.',
            style: theme.textTheme.bodySmall!
                .copyWith(color: AppColors.textSecondary),
          ),
        ],
      ),
    );
  }
}

/// Card de uma cobrança (valor em destaque, status e ação de pagar).
class _PaymentCard extends StatelessWidget {
  const _PaymentCard({required this.payment, required this.onPay});

  final Payment payment;
  final VoidCallback onPay;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    return AppCard(
      padding: const EdgeInsets.all(14),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              IconTile(
                icon: _icon,
                tone: toneForStatus(payment.status),
                size: 44,
              ),
              const SizedBox(width: 14),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      payment.description,
                      style: theme.textTheme.titleSmall,
                      maxLines: 2,
                      overflow: TextOverflow.ellipsis,
                    ),
                    if (_meta.isNotEmpty) ...[
                      const SizedBox(height: 3),
                      Text(_meta, style: theme.textTheme.bodySmall),
                    ],
                  ],
                ),
              ),
              const SizedBox(width: 8),
              Text(
                payment.amountLabel,
                style: theme.textTheme.titleMedium,
              ),
            ],
          ),
          const SizedBox(height: 12),
          Wrap(
            spacing: 8,
            runSpacing: 8,
            children: [
              StatusChip(
                label: payment.statusLabel ?? payment.status,
                status: payment.status,
              ),
              if (payment.channel == 'online' && payment.channelLabel != null)
                StatusChip(
                  label: payment.channelLabel!,
                  tone: AppTone.info,
                  icon: Icons.smartphone_rounded,
                ),
            ],
          ),
          if (payment.payable) ...[
            const SizedBox(height: 14),
            SizedBox(
              width: double.infinity,
              child: FilledButton.icon(
                onPressed: onPay,
                icon: const Icon(Icons.qr_code_2_rounded, size: 20),
                label: const Text('Pagar com Pix'),
              ),
            ),
          ],
        ],
      ),
    );
  }

  IconData get _icon => switch (payment.status) {
        'paid' => Icons.check_circle_outline_rounded,
        'overdue' => Icons.error_outline_rounded,
        'pending' => Icons.schedule_rounded,
        'cancelled' || 'canceled' => Icons.cancel_outlined,
        _ => Icons.attach_money_rounded,
      };

  /// Vencimento/pagamento + forma de pagamento registrada.
  String get _meta {
    final partes = <String>[
      if (payment.paidAt != null) 'Pago em ${_date(payment.paidAt!)}',
      if (payment.paidAt == null && payment.dueDate != null)
        'Vence ${_date(payment.dueDate!)}',
      ?payment.paymentMethodLabel,
    ];

    return partes.join(' · ');
  }

  static String _date(DateTime date) =>
      '${_two(date.day)}/${_two(date.month)}/${date.year}';

  static String _two(int value) => value.toString().padLeft(2, '0');
}

/// Valor em reais: `R$ 1.234,56`.
String _money(double value) {
  final parts = value.toStringAsFixed(2).split('.');
  final comPontos = parts.first.replaceAllMapped(
    RegExp(r'(\d)(?=(\d{3})+$)'),
    (m) => '${m[1]}.',
  );

  return 'R\$ $comPontos,${parts.last}';
}
