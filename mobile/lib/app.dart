import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import 'core/router/app_router.dart';

/// Raiz do app: tema (marca SportPlan, laranja #E96B18) + roteador.
///
/// A sessão decide o destino inicial (splash → login → home) no guard do
/// `routerProvider`.
class SportPlanApp extends ConsumerWidget {
  const SportPlanApp({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final router = ref.watch(routerProvider);

    return MaterialApp.router(
      title: 'SportPlan',
      debugShowCheckedModeBanner: false,
      theme: ThemeData(
        useMaterial3: true,
        // Seed da marca (logomarca laranja); o Material 3 gera a paleta
        // tonal acessível (botões com contraste AA) a partir dela.
        colorScheme: ColorScheme.fromSeed(seedColor: const Color(0xFFE96B18)),
      ),
      routerConfig: router,
    );
  }
}
