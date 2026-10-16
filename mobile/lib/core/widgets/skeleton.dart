import 'package:flutter/material.dart';

import '../theme/app_colors.dart';

/// Bloco cinza pulsante usado nos placeholders de carregamento.
class SkeletonBox extends StatefulWidget {
  const SkeletonBox({
    super.key,
    this.width,
    this.height = 14,
    this.radius = 8,
  });

  final double? width;
  final double height;
  final double radius;

  @override
  State<SkeletonBox> createState() => _SkeletonBoxState();
}

class _SkeletonBoxState extends State<SkeletonBox>
    with SingleTickerProviderStateMixin {
  late final AnimationController _controller = AnimationController(
    vsync: this,
    duration: const Duration(milliseconds: 850),
  )..repeat(reverse: true);

  @override
  void dispose() {
    _controller.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return FadeTransition(
      opacity: Tween<double>(begin: 0.45, end: 1).animate(
        CurvedAnimation(parent: _controller, curve: Curves.easeInOut),
      ),
      child: Container(
        width: widget.width,
        height: widget.height,
        decoration: BoxDecoration(
          color: AppColors.neutralSoft,
          borderRadius: BorderRadius.circular(widget.radius),
        ),
      ),
    );
  }
}

/// Lista de cards "fantasma" (cabeçalho + 2 linhas).
class SkeletonCardList extends StatelessWidget {
  const SkeletonCardList({
    super.key,
    this.count = 5,
    this.padding = const EdgeInsets.fromLTRB(16, 16, 16, 24),
  });

  final int count;
  final EdgeInsetsGeometry padding;

  @override
  Widget build(BuildContext context) {
    return ListView.separated(
      padding: padding,
      physics: const AlwaysScrollableScrollPhysics(),
      itemCount: count,
      separatorBuilder: (_, _) => const SizedBox(height: 12),
      itemBuilder: (_, _) => Container(
        padding: const EdgeInsets.all(16),
        decoration: BoxDecoration(
          color: AppColors.surface,
          borderRadius: BorderRadius.circular(18),
          border: Border.all(color: AppColors.border),
        ),
        child: Row(
          children: [
            const SkeletonBox(width: 44, height: 44, radius: 14),
            const SizedBox(width: 14),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const SkeletonBox(height: 14, width: 160),
                  const SizedBox(height: 10),
                  SkeletonBox(height: 11, width: 110),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}
