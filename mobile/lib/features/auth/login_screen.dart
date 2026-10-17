import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:package_info_plus/package_info_plus.dart';

import '../../core/api/api_exception.dart';
import '../../core/theme/app_colors.dart';
import '../../core/widgets/app_header.dart';
import '../../core/widgets/app_tone.dart';
import '../../core/widgets/brand_mark.dart';
import '../../core/widgets/empty_state.dart';
import '../auth/auth_controller.dart';

/// Login do aluno (e-mail + senha) — `POST /auth/login`.
class LoginScreen extends ConsumerStatefulWidget {
  const LoginScreen({super.key});

  @override
  ConsumerState<LoginScreen> createState() => _LoginScreenState();
}

class _LoginScreenState extends ConsumerState<LoginScreen> {
  final TextEditingController _emailController = TextEditingController();
  final TextEditingController _passwordController = TextEditingController();
  String? _error;
  bool _loading = false;
  bool _obscurePassword = true;

  @override
  void dispose() {
    _emailController.dispose();
    _passwordController.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    final email = _emailController.text.trim();
    final password = _passwordController.text;

    if (email.isEmpty || password.isEmpty) {
      setState(() => _error = 'Informe e-mail e senha.');
      return;
    }

    setState(() {
      _error = null;
      _loading = true;
    });

    try {
      await ref.read(authControllerProvider.notifier).login(
            email: email,
            password: password,
          );
      // Sucesso: o guard do router redireciona para a home.
    } on ApiException catch (error) {
      if (!mounted) return;
      setState(() => _error = error.message);
    } catch (_) {
      // Qualquer erro inesperado (rede, parse...) também vira mensagem.
      if (!mounted) return;
      setState(
        () => _error = 'Não foi possível entrar. Tente novamente.',
      );
    } finally {
      if (mounted) {
        setState(() => _loading = false);
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    return Scaffold(
      body: ListView(
        physics: const AlwaysScrollableScrollPhysics(),
        padding: EdgeInsets.zero,
        children: [
          const _LoginHeader(),
          Padding(
            padding: const EdgeInsets.fromLTRB(20, 26, 20, 32),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                Text('Entrar na sua conta', style: theme.textTheme.titleMedium),
                const SizedBox(height: 4),
                Text(
                  'Use o e-mail e a senha que você cadastrou no app.',
                  style: theme.textTheme.bodySmall,
                ),
                const SizedBox(height: 20),
                TextField(
                  controller: _emailController,
                  keyboardType: TextInputType.emailAddress,
                  textInputAction: TextInputAction.next,
                  autofillHints: const [AutofillHints.email],
                  decoration: const InputDecoration(
                    labelText: 'E-mail',
                    hintText: 'voce@email.com',
                    prefixIcon: Icon(Icons.mail_outline_rounded),
                  ),
                ),
                const SizedBox(height: 14),
                TextField(
                  controller: _passwordController,
                  obscureText: _obscurePassword,
                  autofillHints: const [AutofillHints.password],
                  onSubmitted: (_) => _submit(),
                  decoration: InputDecoration(
                    labelText: 'Senha',
                    prefixIcon: const Icon(Icons.lock_outline_rounded),
                    suffixIcon: IconButton(
                      tooltip: _obscurePassword
                          ? 'Mostrar senha'
                          : 'Ocultar senha',
                      icon: Icon(
                        _obscurePassword
                            ? Icons.visibility_outlined
                            : Icons.visibility_off_outlined,
                      ),
                      onPressed: () => setState(
                        () => _obscurePassword = !_obscurePassword,
                      ),
                    ),
                  ),
                ),
                if (_error != null) ...[
                  const SizedBox(height: 16),
                  AlertCard(
                    icon: Icons.error_outline_rounded,
                    tone: AppTone.danger,
                    title: 'Não foi possível entrar',
                    message: _error,
                  ),
                ],
                const SizedBox(height: 22),
                FilledButton(
                  onPressed: _loading ? null : _submit,
                  child: _loading
                      ? const SizedBox(
                          height: 20,
                          width: 20,
                          child: CircularProgressIndicator(
                            strokeWidth: 2,
                            color: Colors.white,
                          ),
                        )
                      : const Text('Entrar'),
                ),
                const SizedBox(height: 26),
                const _InviteHint(),
                const SizedBox(height: 28),
                const Center(child: _AppVersionLabel()),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

/// Topo do login: logomarca (ou nome, sem logomarca) e promessa do produto.
class _LoginHeader extends StatelessWidget {
  const _LoginHeader();

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    return AppHeader(
      padding: const EdgeInsets.fromLTRB(20, 36, 20, 34),
      child: Column(
        children: [
          // Com a logomarca no bundle o nome não aparece: a marca fala por si.
          const BrandMark(),
          const SizedBox(height: 18),
          Text(
            'Seus treinos na palma da mão',
            textAlign: TextAlign.center,
            style: theme.textTheme.bodySmall!.copyWith(
              color: Colors.white.withValues(alpha: 0.75),
            ),
          ),
        ],
      ),
    );
  }
}

/// Versão do app no rodapé (lida do bundle nativo).
///
/// Sem o plugin disponível (testes de widget sem mock/desktop), nada aparece.
class _AppVersionLabel extends StatefulWidget {
  const _AppVersionLabel();

  @override
  State<_AppVersionLabel> createState() => _AppVersionLabelState();
}

class _AppVersionLabelState extends State<_AppVersionLabel> {
  String? _version;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    try {
      final info = await PackageInfo.fromPlatform();
      if (!mounted) return;

      setState(() {
        _version = info.buildNumber.isEmpty
            ? 'versão ${info.version}'
            : 'versão ${info.version} (${info.buildNumber})';
      });
    } catch (_) {
      // Sem o plugin (teste/desktop): o rodapé simplesmente não aparece.
    }
  }

  @override
  Widget build(BuildContext context) {
    final version = _version;

    if (version == null) {
      return const SizedBox.shrink();
    }

    return Text(
      version,
      style: Theme.of(context).textTheme.labelSmall!.copyWith(
            color: AppColors.textMuted,
          ),
    );
  }
}

/// Lembrete de que a conta do aluno nasce de um convite do treinador.
class _InviteHint extends StatelessWidget {
  const _InviteHint();

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: AppColors.surfaceMuted,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: AppColors.border),
      ),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Icon(
            Icons.info_outline_rounded,
            size: 20,
            color: AppColors.textSecondary,
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text('Ainda não tem acesso?', style: theme.textTheme.titleSmall),
                const SizedBox(height: 2),
                Text(
                  'Peça o código de convite ao seu treinador e crie sua conta '
                  'no primeiro acesso do app.',
                  style: theme.textTheme.bodySmall,
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}
