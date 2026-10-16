import 'package:flutter/material.dart';

/// Tela exibida enquanto a sessão é restaurada (build do AuthController).
///
/// O fundo usa a mesma cor da arte do app (`#FCFAEB`) que o ícone do launcher
/// e a abertura nativa — assim a sequência abrir → carregar → home não pisca.
class SplashScreen extends StatelessWidget {
  const SplashScreen({super.key});

  /// Fundo da marca (igual ao fundo da arte em `assets/icon/app_icon.png`).
  static const _background = Color(0xFFFCFAEB);

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: _background,
      body: Center(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Image.asset(
              'assets/icon/app_icon.png',
              width: 160,
              // Decodifica no tamanho exibido (3x para telas densas).
              cacheWidth: 480,
            ),
            const SizedBox(height: 24),
            const CircularProgressIndicator(),
            const SizedBox(height: 16),
            const Text('Carregando...'),
          ],
        ),
      ),
    );
  }
}
