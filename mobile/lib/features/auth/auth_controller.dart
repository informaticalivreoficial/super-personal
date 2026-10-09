import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../core/api/api_exception.dart';
import '../../core/providers.dart';
import 'auth_repository.dart';
import 'user.dart';

final authRepositoryProvider = Provider<AuthRepository>(
  (ref) => AuthRepository(ref.watch(apiClientProvider)),
);

/// Sessão atual do app.
///
/// - `AsyncValue.loading` enquanto a sessão é restaurada (splash);
/// - `null` = deslogado (login);
/// - [User] = logado (home).
final authControllerProvider =
    AsyncNotifierProvider<AuthController, User?>(AuthController.new);

class AuthController extends AsyncNotifier<User?> {
  @override
  Future<User?> build() async {
    // Qualquer 401 da API derruba a sessão (token expirado/revogado).
    ref.read(apiClientProvider).onUnauthorized = _handleUnauthorized;

    final token = await ref.read(tokenStorageProvider).read();
    if (token == null || token.isEmpty) {
      return null;
    }

    try {
      return await ref.read(authRepositoryProvider).me();
    } on ApiException {
      // 401/conta desativada/sem rede na abertura: volta pro login.
      return null;
    }
  }

  Future<void> login({
    required String email,
    required String password,
  }) async {
    // Sem estado de loading global: a tela de login controla o spinner,
    // evitando que o guard redirecione para o splash durante o submit.
    try {
      final result = await ref.read(authRepositoryProvider).login(
            email: email,
            password: password,
          );
      state = AsyncValue.data(result.user);
    } on ApiException {
      state = const AsyncValue.data(null);
      rethrow;
    }
  }

  Future<void> logout() async {
    await ref.read(authRepositoryProvider).logout();
    state = const AsyncValue.data(null);
  }

  void _handleUnauthorized() {
    if (ref.mounted) {
      state = const AsyncValue.data(null);
    }
  }
}
