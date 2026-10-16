import 'package:flutter/material.dart';
import 'package:flutter_localizations/flutter_localizations.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import 'core/polling/live_refresh.dart';
import 'core/router/app_router.dart';
import 'core/theme/app_theme.dart';
import 'features/auth/auth_controller.dart';
import 'features/student/student_providers.dart';

/// Raiz do app: tema (design system em `core/theme`) + roteador.
///
/// A sessão decide o destino inicial (splash → login → home) no guard do
/// `routerProvider`.
class SuperPersonalApp extends ConsumerWidget {
  const SuperPersonalApp({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final router = ref.watch(routerProvider);
    final loggedIn = ref.watch(authControllerProvider).value != null;

    return MaterialApp.router(
      title: 'Super Personal',
      debugShowCheckedModeBanner: false,
      // App todo em pt-BR: os widgets do Material (calendário, "cancelar",
      // meses) saem em português.
      locale: const Locale('pt', 'BR'),
      supportedLocales: const [Locale('pt', 'BR')],
      localizationsDelegates: const [
        GlobalMaterialLocalizations.delegate,
        GlobalWidgetsLocalizations.delegate,
        GlobalCupertinoLocalizations.delegate,
      ],
      theme: AppTheme.build(),
      routerConfig: router,
      // Polling: com o app aberto o resumo (avisos não lidos, cobranças em
      // aberto, números do dia) se atualiza sozinho. A API não tem websocket;
      // com o app fechado quem avisa é o push (FCM).
      builder: (context, child) => LiveRefresh(
        enabled: loggedIn,
        onTick: (ref) => ref.read(dashboardProvider.notifier).refreshSilently(),
        child: child ?? const SizedBox.shrink(),
      ),
    );
  }
}
