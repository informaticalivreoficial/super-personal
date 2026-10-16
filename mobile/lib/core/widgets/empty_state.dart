import 'package:flutter/material.dart';

import '../theme/app_colors.dart';
import 'app_tone.dart';
import 'icon_tile.dart';

/// Estado vazio amigável (ícone, título, explicação e ação opcional).
class EmptyState extends StatelessWidget {
  const EmptyState({
    super.key,
    required this.icon,
    required this.title,
    this.message,
    this.actionLabel,
    this.onAction,
    this.tone = AppTone.brand,
  });

  final IconData icon;
  final String title;
  final String? message;
  final String? actionLabel;
  final VoidCallback? onAction;
  final AppTone tone;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    return Center(
      child: Padding(
        padding: const EdgeInsets.symmetric(horizontal: 32, vertical: 24),
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          mainAxisSize: MainAxisSize.min,
          children: [
            IconTile(icon: icon, tone: tone, size: 72, radius: 24, iconSize: 34),
            const SizedBox(height: 16),
            Text(title, textAlign: TextAlign.center, style: theme.textTheme.titleMedium),
            if (message != null) ...[
              const SizedBox(height: 6),
              Text(
                message!,
                textAlign: TextAlign.center,
                style: theme.textTheme.bodySmall,
              ),
            ],
            if (actionLabel != null && onAction != null) ...[
              const SizedBox(height: 20),
              FilledButton(onPressed: onAction, child: Text(actionLabel!)),
            ],
          ],
        ),
      ),
    );
  }
}

/// Estado de erro com botão de tentar novamente.
class ErrorState extends StatelessWidget {
  const ErrorState({
    super.key,
    required this.message,
    required this.onRetry,
    this.title = 'Não foi possível carregar',
  });

  final String message;
  final VoidCallback onRetry;
  final String title;

  @override
  Widget build(BuildContext context) {
    return EmptyState(
      icon: Icons.cloud_off_rounded,
      tone: AppTone.danger,
      title: title,
      message: message,
      actionLabel: 'Tentar novamente',
      onAction: onRetry,
    );
  }
}

/// Envolve um estado vazio/erro permitindo o "puxar para atualizar".
class RefreshableState extends StatelessWidget {
  const RefreshableState({
    super.key,
    required this.child,
    required this.onRefresh,
  });

  final Widget child;
  final Future<void> Function() onRefresh;

  @override
  Widget build(BuildContext context) {
    return RefreshIndicator(
      onRefresh: onRefresh,
      child: LayoutBuilder(
        builder: (context, constraints) => SingleChildScrollView(
          physics: const AlwaysScrollableScrollPhysics(),
          child: ConstrainedBox(
            constraints: BoxConstraints(minHeight: constraints.maxHeight),
            child: child,
          ),
        ),
      ),
    );
  }
}

/// Bloco de destaque (aviso, pendência) com ícone e borda tonalizada.
class AlertCard extends StatelessWidget {
  const AlertCard({
    super.key,
    required this.icon,
    required this.title,
    this.message,
    this.tone = AppTone.warning,
    this.actionLabel,
    this.onAction,
    this.onTap,
  });

  final IconData icon;
  final String title;
  final String? message;
  final AppTone tone;
  final String? actionLabel;
  final VoidCallback? onAction;
  final VoidCallback? onTap;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    return Material(
      color: tone.background,
      borderRadius: BorderRadius.circular(16),
      clipBehavior: Clip.antiAlias,
      child: Ink(
        decoration: BoxDecoration(
          borderRadius: BorderRadius.circular(16),
          border: Border.all(color: tone.foreground.withValues(alpha: 0.18)),
        ),
        child: InkWell(
          onTap: onTap,
          child: Padding(
            padding: const EdgeInsets.all(14),
            child: Row(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Icon(icon, size: 22, color: tone.foreground),
                const SizedBox(width: 12),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        title,
                        style: theme.textTheme.titleSmall!
                            .copyWith(color: AppColors.textPrimary),
                      ),
                      if (message != null) ...[
                        const SizedBox(height: 2),
                        Text(
                          message!,
                          style: theme.textTheme.bodySmall!
                              .copyWith(color: AppColors.textSecondary),
                        ),
                      ],
                      if (actionLabel != null && onAction != null) ...[
                        const SizedBox(height: 10),
                        SizedBox(
                          height: 38,
                          child: FilledButton(
                            onPressed: onAction,
                            style: FilledButton.styleFrom(
                              backgroundColor: tone.foreground,
                              padding: const EdgeInsets.symmetric(horizontal: 16),
                            ),
                            child: Text(actionLabel!),
                          ),
                        ),
                      ],
                    ],
                  ),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}
