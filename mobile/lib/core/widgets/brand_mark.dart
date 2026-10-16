import 'package:flutter/material.dart';

/// Logomarca do produto no topo das telas.
///
/// A arte vive em `assets/icon/app_icon.png` (mesmo arquivo do ícone do
/// launcher: quadrada, fundo creme). Como o cabeçalho das telas é escuro, a
/// arte entra sobre uma placa clara arredondada.
///
/// **Sem logomarca no bundle** (arte removida/substituída), o widget cai no
/// ícone + nome [productName] — ou seja, o nome "Super Personal" só aparece
/// quando não há arte para mostrar.
class BrandMark extends StatelessWidget {
  const BrandMark({super.key, this.asset = assetPath, this.size = 96});

  /// Arte da marca (padrão: a do ícone do app).
  static const assetPath = 'assets/icon/app_icon.png';

  /// Nome mostrado no fallback (sem logomarca).
  static const productName = 'Super Personal';

  final String asset;

  /// Lado da placa da logomarca.
  final double size;

  @override
  Widget build(BuildContext context) {
    return Image.asset(
      asset,
      width: size,
      height: size,
      fit: BoxFit.contain,
      // Decodifica no tamanho exibido (3x para telas densas).
      cacheWidth: (size * 3).round(),
      // A placa é aplicada só na arte: o fallback desenha o próprio bloco.
      frameBuilder: (context, child, frame, wasSynchronouslyLoaded) =>
          _Plate(size: size, child: child),
      errorBuilder: (context, error, stackTrace) => const _FallbackMark(),
    );
  }
}

/// Placa clara atrás da arte (o fundo da arte é creme, a tela é escura).
class _Plate extends StatelessWidget {
  const _Plate({required this.size, required this.child});

  final double size;
  final Widget child;

  @override
  Widget build(BuildContext context) {
    return Container(
      width: size,
      height: size,
      padding: const EdgeInsets.all(4),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(size * 0.24),
      ),
      clipBehavior: Clip.antiAlias,
      child: child,
    );
  }
}

/// Marca sem logomarca: bloco com o ícone e o nome do produto embaixo.
class _FallbackMark extends StatelessWidget {
  const _FallbackMark();

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    return Column(
      mainAxisSize: MainAxisSize.min,
      children: [
        Container(
          width: 64,
          height: 64,
          decoration: BoxDecoration(
            color: Colors.white.withValues(alpha: 0.14),
            borderRadius: BorderRadius.circular(20),
            border: Border.all(color: Colors.white.withValues(alpha: 0.18)),
          ),
          child: const Icon(
            Icons.fitness_center_rounded,
            color: Colors.white,
            size: 32,
          ),
        ),
        const SizedBox(height: 14),
        Text(
          BrandMark.productName,
          textAlign: TextAlign.center,
          style: theme.textTheme.headlineSmall!.copyWith(color: Colors.white),
        ),
      ],
    );
  }
}
