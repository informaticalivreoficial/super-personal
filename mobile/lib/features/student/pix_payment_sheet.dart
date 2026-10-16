import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:qr_flutter/qr_flutter.dart';

import '../../core/api/api_exception.dart';
import '../../core/theme/app_colors.dart';
import '../../core/widgets/app_tone.dart';
import '../../core/widgets/empty_state.dart';
import '../../core/widgets/icon_tile.dart';
import '../../core/widgets/skeleton.dart';
import 'cpf_input_formatter.dart';
import 'models/payment.dart';
import 'student_providers.dart';

/// Abre o Pix da cobrança online num bottom sheet.
///
/// Devolve `true` quando o aluno toca em "Já efetuei o pagamento" (o chamador
/// recarrega a lista — o status muda pelo webhook do gateway).
Future<bool?> showPixPaymentSheet(BuildContext context, Payment payment) {
  return showModalBottomSheet<bool>(
    context: context,
    isScrollControlled: true,
    showDragHandle: true,
    builder: (_) => PixPaymentSheet(payment: payment),
  );
}

/// Gera e exibe o QR Code Pix (desenhado localmente a partir do copia e cola).
class PixPaymentSheet extends ConsumerStatefulWidget {
  const PixPaymentSheet({super.key, required this.payment});

  final Payment payment;

  @override
  ConsumerState<PixPaymentSheet> createState() => _PixPaymentSheetState();
}

class _PixPaymentSheetState extends ConsumerState<PixPaymentSheet> {
  final _cpfController = TextEditingController();

  PaymentCheckout? _checkout;
  String? _error;
  String? _cpfError;
  bool _loading = true;

  @override
  void initState() {
    super.initState();
    _generate();
  }

  @override
  void dispose() {
    _cpfController.dispose();
    super.dispose();
  }

  Future<void> _generate() async {
    setState(() {
      _loading = true;
      _error = null;
      _cpfError = null;
    });

    try {
      final checkout = await ref
          .read(studentRepositoryProvider)
          .checkoutPayment(widget.payment.id, cpf: _cpfController.text);

      if (!mounted) return;
      setState(() {
        _checkout = checkout;
        _loading = false;
      });
    } on ApiException catch (error) {
      if (!mounted) return;
      setState(() {
        _loading = false;
        // Sem CPF no cadastro a API responde 422 em `cpf`: pedimos o dado.
        _cpfError = error.fieldErrors['cpf'];
        _error = _cpfError == null ? error.message : null;
      });
    }
  }

  Future<void> _copyCode() async {
    final code = _checkout?.qrCode;
    if (code == null || code.isEmpty) return;

    await Clipboard.setData(ClipboardData(text: code));
    if (!mounted) return;

    ScaffoldMessenger.of(context).showSnackBar(
      const SnackBar(content: Text('Código Pix copiado.')),
    );
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final checkout = _checkout;

    return SafeArea(
      child: Padding(
        padding: const EdgeInsets.fromLTRB(20, 4, 20, 20),
        child: SingleChildScrollView(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            mainAxisSize: MainAxisSize.min,
            children: [
              Row(
                children: [
                  const IconTile(icon: Icons.qr_code_2_rounded, size: 46),
                  const SizedBox(width: 14),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text('Pagar com Pix', style: theme.textTheme.titleLarge),
                        const SizedBox(height: 2),
                        Text(
                          widget.payment.description,
                          style: theme.textTheme.bodySmall,
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                        ),
                      ],
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 18),
              if (_loading)
                const _LoadingState()
              else if (checkout != null) ...[
                Center(
                  child: Text(
                    widget.payment.amountLabel,
                    style: theme.textTheme.headlineMedium,
                  ),
                ),
                const SizedBox(height: 16),
                Center(
                  child: Container(
                    padding: const EdgeInsets.all(14),
                    decoration: BoxDecoration(
                      color: Colors.white,
                      borderRadius: BorderRadius.circular(20),
                      border: Border.all(color: AppColors.border),
                    ),
                    child: QrImageView(
                      data: checkout.qrCode,
                      version: QrVersions.auto,
                      size: 216,
                      backgroundColor: Colors.white,
                    ),
                  ),
                ),
                const SizedBox(height: 16),
                Text(
                  'Abra o aplicativo do seu banco, escolha pagar com Pix e '
                  'aponte a câmera para o QR Code.',
                  textAlign: TextAlign.center,
                  style: theme.textTheme.bodySmall,
                ),
                const SizedBox(height: 16),
                OutlinedButton.icon(
                  onPressed: _copyCode,
                  icon: const Icon(Icons.copy_rounded, size: 18),
                  label: const Text('Copiar código Pix'),
                ),
                const SizedBox(height: 10),
                FilledButton.icon(
                  onPressed: () => Navigator.of(context).pop(true),
                  icon: const Icon(Icons.check_rounded, size: 20),
                  label: const Text('Já efetuei o pagamento'),
                ),
                const SizedBox(height: 12),
                Text(
                  'A confirmação é automática: o status muda assim que o '
                  'pagamento for identificado.',
                  textAlign: TextAlign.center,
                  style: theme.textTheme.labelSmall!
                      .copyWith(color: AppColors.textMuted),
                ),
              ] else ...[
                AlertCard(
                  icon: Icons.error_outline_rounded,
                  tone: AppTone.danger,
                  title: 'Não foi possível gerar o QR Code',
                  message: _error ??
                      'Confira sua conexão e tente novamente em instantes.',
                ),
              ],
              if (_cpfError != null) ...[
                const SizedBox(height: 16),
                TextField(
                  controller: _cpfController,
                  keyboardType: TextInputType.number,
                  inputFormatters: [
                    FilteringTextInputFormatter.digitsOnly,
                    const CpfInputFormatter(),
                  ],
                  decoration: InputDecoration(
                    labelText: 'CPF do titular',
                    helperText:
                        'Dá para salvar no seu perfil (Meu perfil → CPF).',
                    errorText: _cpfError,
                    prefixIcon: const Icon(Icons.badge_outlined),
                  ),
                ),
                const SizedBox(height: 14),
                FilledButton(
                  onPressed: _generate,
                  child: const Text('Gerar QR Code'),
                ),
              ] else if (!_loading && checkout == null) ...[
                const SizedBox(height: 14),
                OutlinedButton.icon(
                  onPressed: _generate,
                  icon: const Icon(Icons.refresh_rounded, size: 18),
                  label: const Text('Tentar novamente'),
                ),
              ],
            ],
          ),
        ),
      ),
    );
  }
}

/// Placeholder enquanto o pedido é criado no gateway.
class _LoadingState extends StatelessWidget {
  const _LoadingState();

  @override
  Widget build(BuildContext context) {
    return Column(
      children: [
        const SkeletonBox(width: 120, height: 26, radius: 10),
        const SizedBox(height: 16),
        Container(
          padding: const EdgeInsets.all(14),
          decoration: BoxDecoration(
            color: AppColors.surface,
            borderRadius: BorderRadius.circular(20),
            border: Border.all(color: AppColors.border),
          ),
          child: const SkeletonBox(width: 216, height: 216, radius: 16),
        ),
        const SizedBox(height: 16),
        Text(
          'Gerando seu QR Code...',
          textAlign: TextAlign.center,
          style: Theme.of(context).textTheme.bodySmall,
        ),
      ],
    );
  }
}
