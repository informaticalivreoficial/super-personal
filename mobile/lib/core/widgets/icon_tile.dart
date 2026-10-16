import 'package:flutter/material.dart';

import 'app_tone.dart';

/// Quadradinho arredondado com um ícone — assinatura visual do app.
///
/// Usado nos atalhos, KPIs, itens de treino e estados vazios.
class IconTile extends StatelessWidget {
  const IconTile({
    super.key,
    required this.icon,
    this.tone = AppTone.brand,
    this.size = 44,
    this.iconSize,
    this.radius = 14,
  });

  final IconData icon;
  final AppTone tone;
  final double size;
  final double? iconSize;
  final double radius;

  @override
  Widget build(BuildContext context) {
    return Container(
      width: size,
      height: size,
      decoration: BoxDecoration(
        color: tone.background,
        borderRadius: BorderRadius.circular(radius),
      ),
      child: Icon(
        icon,
        size: iconSize ?? size * 0.5,
        color: tone.foreground,
      ),
    );
  }
}
