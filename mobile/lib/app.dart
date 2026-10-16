import 'package:flutter/material.dart';
import 'package:flutter_localizations/flutter_localizations.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import 'core/router/app_router.dart';

/// Raiz do app: tema (marca Super Personal, laranja #E96B18) + roteador.
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
      // App todo em pt-BR: os widgets do Material (calendário, "cancelar",
      // meses) saem em português.
      locale: const Locale('pt', 'BR'),
      supportedLocales: const [Locale('pt', 'BR')],
      localizationsDelegates: const [
        GlobalMaterialLocalizations.delegate,
        GlobalWidgetsLocalizations.delegate,
        GlobalCupertinoLocalizations.delegate,
      ],
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
