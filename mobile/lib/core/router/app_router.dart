import 'package:flutter/foundation.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../features/auth/auth_controller.dart';
import '../../features/auth/login_screen.dart';
import '../../features/auth/user.dart';
import '../../features/home/home_screen.dart';
import '../../features/home/splash_screen.dart';

/// Rotas do app com guard de sessão.
///
/// O guard reavalia sempre que [authControllerProvider] muda:
/// restaurando sessão → splash; deslogado → login; logado → home.
final routerProvider = Provider<GoRouter>((ref) {
  final session = ValueNotifier<AsyncValue<User?>>(const AsyncValue.loading());

  ref.listen(
    authControllerProvider,
    (_, next) => session.value = next,
  );
  // Inicializa o provider (dispara a restauração da sessão).
  session.value = ref.read(authControllerProvider);

  ref.onDispose(session.dispose);

  return GoRouter(
    initialLocation: '/splash',
    refreshListenable: session,
    redirect: (context, state) {
      final current = session.value;
      final location = state.matchedLocation;

      if (current.isLoading) {
        return location == '/splash' ? null : '/splash';
      }

      final loggedIn = current.value != null;
      if (loggedIn) {
        return location == '/' ? null : '/';
      }

      return location == '/login' ? null : '/login';
    },
    routes: [
      GoRoute(
        path: '/splash',
        builder: (context, state) => const SplashScreen(),
      ),
      GoRoute(
        path: '/login',
        builder: (context, state) => const LoginScreen(),
      ),
      GoRoute(
        path: '/',
        builder: (context, state) => const HomeScreen(),
      ),
    ],
  );
});
