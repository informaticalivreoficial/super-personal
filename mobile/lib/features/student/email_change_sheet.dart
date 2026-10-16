import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../core/api/api_exception.dart';
import 'student_providers.dart';

/// Abre o fluxo de troca do e-mail de acesso.
///
/// Devolve `true` quando o novo endereço foi efetivado (o código chegou e foi
/// confirmado). Com [pendingEmail] o sheet já abre no passo do código (pedido
/// feito em outro momento).
Future<bool> showEmailChangeSheet(
  BuildContext context, {
  String? pendingEmail,
}) async {
  final changed = await showModalBottomSheet<bool>(
    context: context,
    isScrollControlled: true,
    builder: (sheetContext) => Padding(
      padding: EdgeInsets.only(
        bottom: MediaQuery.of(sheetContext).viewInsets.bottom,
      ),
      child: EmailChangeSheet(pendingEmail: pendingEmail),
    ),
  );

  return changed ?? false;
}

/// Passo 1: novo e-mail → a API envia o código.
/// Passo 2: código de 6 dígitos → o e-mail passa a valer.
class EmailChangeSheet extends ConsumerStatefulWidget {
  const EmailChangeSheet({super.key, this.pendingEmail});

  final String? pendingEmail;

  @override
  ConsumerState<EmailChangeSheet> createState() => _EmailChangeSheetState();
}

class _EmailChangeSheetState extends ConsumerState<EmailChangeSheet> {
  final _email = TextEditingController();
  final _code = TextEditingController();
  late int _step;
  String? _error;
  bool _loading = false;

  @override
  void initState() {
    super.initState();
    _step = widget.pendingEmail == null ? 1 : 2;
    _email.text = widget.pendingEmail ?? '';
  }

  @override
  void dispose() {
    _email.dispose();
    _code.dispose();
    super.dispose();
  }

  String get _target => widget.pendingEmail ?? _email.text.trim();

  bool get _validEmail =>
      RegExp(r'^[^@\s]+@[^@\s]+\.[^@\s]+$').hasMatch(_email.text.trim());

  Future<void> _sendCode() async {
    if (_step == 1 && !_validEmail) {
      setState(() => _error = 'Informe um e-mail válido.');
      return;
    }

    setState(() {
      _loading = true;
      _error = null;
    });

    try {
      final pending = await ref
          .read(profileProvider.notifier)
          .requestEmailChange(_email.text.trim());

      if (!mounted) return;
      setState(() {
        _step = 2;
        _email.text = pending;
      });
    } on ApiException catch (error) {
      if (!mounted) return;
      setState(() => _error = error.fieldErrors['email'] ?? error.message);
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  Future<void> _confirm() async {
    final code = _code.text.trim();

    if (code.length != 6) {
      setState(() => _error = 'O código tem 6 dígitos.');
      return;
    }

    setState(() {
      _loading = true;
      _error = null;
    });

    try {
      await ref.read(profileProvider.notifier).confirmEmailChange(code);

      if (!mounted) return;
      Navigator.of(context).pop(true);
    } on ApiException catch (error) {
      if (!mounted) return;
      setState(() => _error = error.fieldErrors['code'] ?? error.message);
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final scheme = Theme.of(context).colorScheme;

    return Padding(
      padding: const EdgeInsets.all(20),
      child: Column(
        mainAxisSize: MainAxisSize.min,
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          Row(
            children: [
              Icon(Icons.alternate_email, color: scheme.primary),
              const SizedBox(width: 8),
              Text(
                'Trocar e-mail de acesso',
                style: Theme.of(context).textTheme.titleMedium,
              ),
            ],
          ),
          const SizedBox(height: 12),
          if (_step == 1) ...[
            const Text(
              'Enviamos um código para o endereço novo. Seu e-mail atual '
              'continua valendo até você confirmar.',
            ),
            const SizedBox(height: 16),
            TextField(
              controller: _email,
              keyboardType: TextInputType.emailAddress,
              autocorrect: false,
              decoration: const InputDecoration(
                labelText: 'Novo e-mail',
                border: OutlineInputBorder(),
                prefixIcon: Icon(Icons.mail_outline),
              ),
            ),
            if (_error != null) ...[
              const SizedBox(height: 8),
              Text(_error!, style: TextStyle(color: scheme.error)),
            ],
            const SizedBox(height: 16),
            FilledButton.icon(
              onPressed: _loading ? null : _sendCode,
              icon: _loading
                  ? const SizedBox(
                      width: 18,
                      height: 18,
                      child: CircularProgressIndicator(strokeWidth: 2),
                    )
                  : const Icon(Icons.send_outlined),
              label: const Text('Enviar código'),
            ),
          ] else ...[
            Text(
              'Digite o código de 6 dígitos que enviamos para $_target.',
            ),
            const SizedBox(height: 16),
            TextField(
              controller: _code,
              keyboardType: TextInputType.number,
              inputFormatters: [
                FilteringTextInputFormatter.digitsOnly,
                LengthLimitingTextInputFormatter(6),
              ],
              decoration: const InputDecoration(
                labelText: 'Código',
                border: OutlineInputBorder(),
                prefixIcon: Icon(Icons.pin_outlined),
                counterText: '',
              ),
            ),
            if (_error != null) ...[
              const SizedBox(height: 8),
              Text(_error!, style: TextStyle(color: scheme.error)),
            ],
            const SizedBox(height: 16),
            FilledButton.icon(
              onPressed: _loading ? null : _confirm,
              icon: _loading
                  ? const SizedBox(
                      width: 18,
                      height: 18,
                      child: CircularProgressIndicator(strokeWidth: 2),
                    )
                  : const Icon(Icons.check_circle_outline),
              label: const Text('Confirmar e-mail'),
            ),
            if (widget.pendingEmail == null)
              TextButton(
                onPressed: _loading ? null : _sendCode,
                child: const Text('Reenviar código'),
              ),
          ],
          TextButton(
            onPressed: _loading ? null : () => Navigator.of(context).pop(false),
            child: const Text('Cancelar'),
          ),
        ],
      ),
    );
  }
}
