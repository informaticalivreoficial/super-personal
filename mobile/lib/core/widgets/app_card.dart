import 'package:flutter/material.dart';

import '../theme/app_colors.dart';
import '../theme/app_theme.dart';

/// Card padrão do app: branco, canto arredondado e borda suave.
///
/// Substitui `Card`/`ListTile` soltos para o app inteiro ter o mesmo ritmo
/// visual. Com [onTap] ganha ripple e semântica de botão.
class AppCard extends StatelessWidget {
  const AppCard({
    super.key,
    required this.child,
    this.onTap,
    this.padding = const EdgeInsets.all(16),
    this.color,
    this.borderColor,
    this.radius = AppTheme.radius,
  });

  final Widget child;
  final VoidCallback? onTap;
  final EdgeInsetsGeometry padding;
  final Color? color;
  final Color? borderColor;
  final double radius;

  @override
  Widget build(BuildContext context) {
    final shape = BorderRadius.circular(radius);

    return Material(
      color: color ?? AppColors.surface,
      borderRadius: shape,
      clipBehavior: Clip.antiAlias,
      child: Ink(
        decoration: BoxDecoration(
          borderRadius: shape,
          border: Border.all(color: borderColor ?? AppColors.border),
        ),
        child: InkWell(
          onTap: onTap,
          child: Padding(padding: padding, child: child),
        ),
      ),
    );
  }
}
