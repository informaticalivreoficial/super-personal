import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../core/api/api_exception.dart';
import '../auth/auth_controller.dart';
import 'email_change_sheet.dart';
import 'student_providers.dart';

/// Conta e acesso (e-mail de login e senha).
///
/// Fica em uma tela própria, separada de "Meu perfil" (dados de treino), para
/// o aluno não misturar cadastro de treino com credenciais de acesso.
class AccountScreen extends ConsumerStatefulWidget {
  const AccountScreen({super.key});

  @override
  ConsumerState<AccountScreen> createState() => _AccountScreenState();
}

class _AccountScreenState extends ConsumerState<AccountScreen> {
  /// Troca do e-mail de acesso: o app pede o novo endereço, a API manda um
  /// código para ele e o endereço só passa a valer depois da confirmação.
  Future<void> _changeEmail(String? pendingEmail) async {
    final changed = await showEmailChangeSheet(
      context,
      pendingEmail: pendingEmail,
    );
    if (!mounted || !changed) return;

    // `students.email` acompanha o login: recarrega o cadastro do aluno.
    ref.invalidate(profileProvider);
    ScaffoldMessenger.of(context)
        .showSnackBar(const SnackBar(content: Text('E-mail atualizado.')));
  }

  /// Desiste de uma troca de e-mail pendente (sem confirmação do código).
  Future<void> _cancelEmailChange() async {
    try {
      await ref.read(profileProvider.notifier).cancelEmailChange();
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Alteração de e-mail cancelada.')),
      );
    } on ApiException catch (error) {
      if (!mounted) return;
      ScaffoldMessenger.of(context)
          .showSnackBar(SnackBar(content: Text(error.message)));
    }
  }

  /// Troca a senha (exige a atual). Devolve `true` quando dá certo para o card
  /// limpar os campos.
  Future<bool> _changePassword({
    required String currentPassword,
    required String password,
    required String confirmation,
  }) async {
    try {
      await ref
          .read(profileProvider.notifier)
          .changePassword(
            currentPassword: currentPassword,
            password: password,
            confirmation: confirmation,
          );
      if (!mounted) return true;
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text(
            'Senha atualizada. As outras sessões foram encerradas.',
          ),
        ),
      );

      return true;
    } on ApiException catch (error) {
      if (!mounted) return false;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(
            error.fieldErrors['current_password'] ??
                error.fieldErrors['password'] ??
                error.message,
          ),
        ),
      );

      return false;
    }
  }

  @override
  Widget build(BuildContext context) {
    final user = ref.watch(authControllerProvider).value;

    return Scaffold(
      appBar: AppBar(title: const Text('Conta e acesso')),
      body: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          _EmailCard(
            email: user?.email ?? '',
            pendingEmail: user?.pendingEmail,
            onChangeEmail: () => _changeEmail(user?.pendingEmail),
            onCancelEmailChange: _cancelEmailChange,
          ),
          const SizedBox(height: 16),
          _PasswordCard(onSubmit: _changePassword),
          const SizedBox(height: 16),
          Text(
            'O e-mail daqui é o seu login no app. Trocar a senha encerra as '
            'outras sessões abertas.',
            style: Theme.of(context).textTheme.bodySmall
                ?.copyWith(color: Theme.of(context).colorScheme.outline),
          ),
        ],
      ),
    );
  }
}

/// E-mail de acesso: mostra o endereço atual e conduz a troca com código.
class _EmailCard extends StatelessWidget {
  const _EmailCard({
    required this.email,
    required this.pendingEmail,
    required this.onChangeEmail,
    required this.onCancelEmailChange,
  });

  final String email;
  final String? pendingEmail;
  final VoidCallback onChangeEmail;
  final VoidCallback onCancelEmailChange;

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
                Icon(Icons.alternate_email, size: 18, color: scheme.primary),
                const SizedBox(width: 8),
                Text(
                  'E-mail de acesso',
                  style: Theme.of(context).textTheme.titleSmall,
                ),
              ],
            ),
            const SizedBox(height: 8),
            Text(email, style: Theme.of(context).textTheme.bodyMedium),
            if (pendingEmail != null) ...[
              const SizedBox(height: 12),
              Container(
                padding: const EdgeInsets.all(12),
                decoration: BoxDecoration(
                  color: Colors.amber.shade50,
                  borderRadius: BorderRadius.circular(8),
                  border: Border.all(color: Colors.amber.shade200),
                ),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Row(
                      children: [
                        Icon(
                          Icons.mark_email_unread_outlined,
                          size: 16,
                          color: Colors.amber.shade800,
                        ),
                        const SizedBox(width: 6),
                        Expanded(
                          child: Text(
                            'Aguardando confirmação de $pendingEmail',
                            style: Theme.of(context).textTheme.bodyMedium
                                ?.copyWith(color: Colors.amber.shade900),
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 4),
                    Text(
                      'O endereço novo só passa a valer depois do código.',
                      style: Theme.of(context).textTheme.bodySmall,
                    ),
                    Row(
                      children: [
                        TextButton(
                          onPressed: onChangeEmail,
                          child: const Text('Digitar código'),
                        ),
                        TextButton(
                          onPressed: onCancelEmailChange,
                          child: const Text('Cancelar troca'),
                        ),
                      ],
                    ),
                  ],
                ),
              ),
            ] else
              TextButton.icon(
                onPressed: onChangeEmail,
                icon: const Icon(Icons.edit_outlined, size: 16),
                label: const Text('Alterar e-mail'),
              ),
            Text(
              'A troca precisa ser confirmada por um código enviado ao novo '
              'e-mail.',
              style: Theme.of(context).textTheme.bodySmall
                  ?.copyWith(color: scheme.outline),
            ),
          ],
        ),
      ),
    );
  }
}

/// Troca de senha: pede a senha atual, a nova (8+) e a confirmação. A troca
/// derruba as outras sessões abertas (a deste aparelho continua).
class _PasswordCard extends StatefulWidget {
  const _PasswordCard({required this.onSubmit});

  final Future<bool> Function({
    required String currentPassword,
    required String password,
    required String confirmation,
  })
  onSubmit;

  @override
  State<_PasswordCard> createState() => _PasswordCardState();
}

class _PasswordCardState extends State<_PasswordCard> {
  final _formKey = GlobalKey<FormState>();
  final _current = TextEditingController();
  final _password = TextEditingController();
  final _confirmation = TextEditingController();
  bool _saving = false;
  bool _showCurrent = false;
  bool _showPassword = false;
  bool _showConfirmation = false;

  @override
  void dispose() {
    _current.dispose();
    _password.dispose();
    _confirmation.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    if (!(_formKey.currentState?.validate() ?? false)) return;

    setState(() => _saving = true);

    final ok = await widget.onSubmit(
      currentPassword: _current.text,
      password: _password.text,
      confirmation: _confirmation.text,
    );

    if (!mounted) return;

    if (ok) {
      _current.clear();
      _password.clear();
      _confirmation.clear();
    }

    setState(() => _saving = false);
  }

  @override
  Widget build(BuildContext context) {
    final scheme = Theme.of(context).colorScheme;

    return Card(
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Form(
          key: _formKey,
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                children: [
                  Icon(Icons.lock_outline, size: 18, color: scheme.primary),
                  const SizedBox(width: 8),
                  Text(
                    'Alterar senha',
                    style: Theme.of(context).textTheme.titleSmall,
                  ),
                ],
              ),
              const SizedBox(height: 12),
              TextFormField(
                controller: _current,
                obscureText: !_showCurrent,
                decoration: InputDecoration(
                  labelText: 'Senha atual',
                  border: const OutlineInputBorder(),
                  suffixIcon: IconButton(
                    tooltip: _showCurrent ? 'Ocultar senha' : 'Mostrar senha',
                    icon: Icon(
                      _showCurrent
                          ? Icons.visibility_off_outlined
                          : Icons.visibility_outlined,
                    ),
                    onPressed: () =>
                        setState(() => _showCurrent = !_showCurrent),
                  ),
                ),
                validator: (value) => (value == null || value.isEmpty)
                    ? 'Informe sua senha atual.'
                    : null,
              ),
              const SizedBox(height: 12),
              TextFormField(
                controller: _password,
                obscureText: !_showPassword,
                decoration: InputDecoration(
                  labelText: 'Nova senha',
                  helperText: 'Pelo menos 8 caracteres.',
                  border: const OutlineInputBorder(),
                  suffixIcon: IconButton(
                    tooltip: _showPassword ? 'Ocultar senha' : 'Mostrar senha',
                    icon: Icon(
                      _showPassword
                          ? Icons.visibility_off_outlined
                          : Icons.visibility_outlined,
                    ),
                    onPressed: () =>
                        setState(() => _showPassword = !_showPassword),
                  ),
                ),
                validator: (value) {
                  final text = value ?? '';
                  if (text.length < 8) {
                    return 'A nova senha precisa ter pelo menos 8 caracteres.';
                  }
                  if (text == _current.text) {
                    return 'A nova senha precisa ser diferente da atual.';
                  }

                  return null;
                },
              ),
              const SizedBox(height: 12),
              TextFormField(
                controller: _confirmation,
                obscureText: !_showConfirmation,
                decoration: InputDecoration(
                  labelText: 'Confirmar nova senha',
                  border: const OutlineInputBorder(),
                  suffixIcon: IconButton(
                    tooltip: _showConfirmation
                        ? 'Ocultar senha'
                        : 'Mostrar senha',
                    icon: Icon(
                      _showConfirmation
                          ? Icons.visibility_off_outlined
                          : Icons.visibility_outlined,
                    ),
                    onPressed: () =>
                        setState(() => _showConfirmation = !_showConfirmation),
                  ),
                ),
                validator: (value) => value == _password.text
                    ? null
                    : 'A confirmação não confere com a nova senha.',
              ),
              const SizedBox(height: 16),
              FilledButton.tonalIcon(
                onPressed: _saving ? null : _submit,
                icon: _saving
                    ? const SizedBox(
                        width: 18,
                        height: 18,
                        child: CircularProgressIndicator(strokeWidth: 2),
                      )
                    : const Icon(Icons.password_outlined),
                label: Text(_saving ? 'Alterando…' : 'Salvar nova senha'),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
