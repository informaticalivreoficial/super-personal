import 'package:flutter/material.dart';

/// Logomarca do produto no topo das telas.
///
/// A arte vive em `assets/images/logo.png` (horizontal, fundo branco). Como o
/// cabeçalho das telas é escuro, a arte entra sobre uma placa branca
/// arredondada — o branco da arte se funde com a placa.
///
/// **Sem logomarca no bundle** (arte removida/substituída), o widget cai no
/// ícone + nome [productName] — ou seja, o nome "Super Personal" só aparece
/// quando não há arte para mostrar.
class BrandMark extends StatelessWidget {
  const BrandMark({super.key, this.asset = assetPath, this.height = 62});

  /// Arte da marca.
  static const assetPath = 'assets/images/logo.png';

  /// Nome mostrado no fallback (sem logomarca).
  static const productName = 'Super Personal';

  final String asset;

  /// Altura da arte exibida (a largura acompanha a proporção da imagem).
  final double height;

  @override
  Widget build(BuildContext context) {
    return Image.asset(
      asset,
      height: height,
      fit: BoxFit.contain,
      // A placa é aplicada só na arte: o fallback desenha o próprio bloco.
      frameBuilder: (context, child, frame, wasSynchronouslyLoaded) =>
          _Plate(child: child),
      errorBuilder: (context, error, stackTrace) => const _FallbackMark(),
    );
  }
}

/// Placa clara atrás da arte (o fundo da arte é branco, a tela é escura).
class _Plate extends StatelessWidget {
  const _Plate({required this.child});

  final Widget child;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 18, vertical: 14),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
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
