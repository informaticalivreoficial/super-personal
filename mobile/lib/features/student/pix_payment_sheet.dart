import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:qr_flutter/qr_flutter.dart';

import '../../core/api/api_exception.dart';
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
    final scheme = Theme.of(context).colorScheme;
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
                  Icon(Icons.qr_code_2, color: scheme.primary),
                  const SizedBox(width: 8),
                  Expanded(
                    child: Text(
                      'Pagar com Pix',
                      style: Theme.of(context).textTheme.titleLarge,
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 4),
              Text(
                '${widget.payment.description} · ${widget.payment.amountLabel}',
                style: TextStyle(color: scheme.onSurfaceVariant),
              ),
              const SizedBox(height: 16),
              if (_loading)
                const Padding(
                  padding: EdgeInsets.symmetric(vertical: 48),
                  child: Center(child: CircularProgressIndicator()),
                )
              else if (checkout != null) ...[
                Center(
                  child: Container(
                    padding: const EdgeInsets.all(12),
                    decoration: BoxDecoration(
                      color: Colors.white,
                      borderRadius: BorderRadius.circular(12),
                    ),
                    child: QrImageView(
                      data: checkout.qrCode,
                      version: QrVersions.auto,
                      size: 220,
                      backgroundColor: Colors.white,
                    ),
                  ),
                ),
                const SizedBox(height: 12),
                Text(
                  'Abra o aplicativo do seu banco, escolha pagar com Pix e '
                  'aponte a câmera para o QR Code.',
                  textAlign: TextAlign.center,
                  style: TextStyle(color: scheme.onSurfaceVariant, fontSize: 13),
                ),
                const SizedBox(height: 12),
                OutlinedButton.icon(
                  onPressed: _copyCode,
                  icon: const Icon(Icons.copy, size: 18),
                  label: const Text('Copiar código Pix'),
                ),
                const SizedBox(height: 8),
                FilledButton.icon(
                  onPressed: () => Navigator.of(context).pop(true),
                  icon: const Icon(Icons.check, size: 18),
                  label: const Text('Já efetuei o pagamento'),
                ),
                const SizedBox(height: 8),
                Text(
                  'A confirmação é automática: o status muda assim que o '
                  'pagamento for identificado.',
                  textAlign: TextAlign.center,
                  style: TextStyle(color: scheme.onSurfaceVariant, fontSize: 12),
                ),
              ] else ...[
                Text(
                  _error ?? 'Não foi possível gerar o QR Code.',
                  textAlign: TextAlign.center,
                  style: TextStyle(color: scheme.error),
                ),
                const SizedBox(height: 16),
              ],
              if (_cpfError != null) ...[
                const SizedBox(height: 8),
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
                const SizedBox(height: 12),
                FilledButton(
                  onPressed: _generate,
                  child: const Text('Gerar QR Code'),
                ),
              ] else if (!_loading && checkout == null)
                OutlinedButton.icon(
                  onPressed: _generate,
                  icon: const Icon(Icons.refresh, size: 18),
                  label: const Text('Tentar novamente'),
                ),
            ],
          ),
        ),
      ),
    );
  }
}
