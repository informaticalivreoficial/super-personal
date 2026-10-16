import 'package:flutter/material.dart';

import '../theme/app_colors.dart';

/// Cabeçalho em gradiente da marca (marrom → laranja), com cantos inferiores
/// arredondados. É o "topo" das telas principais do aluno.
class AppHeader extends StatelessWidget {
  const AppHeader({
    super.key,
    required this.child,
    this.padding = const EdgeInsets.fromLTRB(20, 12, 20, 24),
    this.radius = 26,
  });

  final Widget child;
  final EdgeInsetsGeometry padding;
  final double radius;

  @override
  Widget build(BuildContext context) {
    return Container(
      width: double.infinity,
      decoration: BoxDecoration(
        gradient: AppColors.headerGradient,
        borderRadius: BorderRadius.vertical(bottom: Radius.circular(radius)),
      ),
      child: SafeArea(
        bottom: false,
        child: Padding(padding: padding, child: child),
      ),
    );
  }
}
