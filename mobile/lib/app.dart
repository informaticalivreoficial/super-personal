import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import 'core/router/app_router.dart';

/// Raiz do app: tema (accent teal do SaaS) + roteador.
///
/// A sessão decide o destino inicial (splash → login → home) no guard do
/// `routerProvider`.
class SuperPersonalApp extends ConsumerWidget {
  const SuperPersonalApp({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final router = ref.watch(routerProvider);

    return MaterialApp.router(
      title: 'Super Personal',
      debugShowCheckedModeBanner: false,
      theme: ThemeData(
        useMaterial3: true,
        colorScheme: ColorScheme.fromSeed(seedColor: const Color(0xFF0D9488)),
      ),
      routerConfig: router,
    );
  }
}
