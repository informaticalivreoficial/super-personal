import 'package:flutter/material.dart';

import '../theme/app_colors.dart';
import 'app_card.dart';
import 'app_tone.dart';
import 'icon_tile.dart';

/// Card de indicador: ícone, valor grande e legenda.
class StatCard extends StatelessWidget {
  const StatCard({
    super.key,
    required this.icon,
    required this.label,
    required this.value,
    this.caption,
    this.tone = AppTone.brand,
    this.onTap,
  });

  final IconData icon;
  final String label;
  final String value;
  final String? caption;
  final AppTone tone;
  final VoidCallback? onTap;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    return AppCard(
      onTap: onTap,
      padding: const EdgeInsets.all(14),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        mainAxisSize: MainAxisSize.min,
        children: [
          Row(
            children: [
              IconTile(icon: icon, tone: tone, size: 34, radius: 11),
              const Spacer(),
              if (onTap != null)
                const Icon(Icons.chevron_right,
                    size: 18, color: AppColors.textMuted),
            ],
          ),
          const SizedBox(height: 12),
          Text(
            value,
            style: theme.textTheme.headlineSmall!.copyWith(height: 1),
            maxLines: 1,
            overflow: TextOverflow.ellipsis,
          ),
          const SizedBox(height: 2),
          Text(
            label,
            style: theme.textTheme.labelMedium!
                .copyWith(color: AppColors.textSecondary),
            maxLines: 1,
            overflow: TextOverflow.ellipsis,
          ),
          if (caption != null) ...[
            const SizedBox(height: 2),
            Text(
              caption!,
              style: theme.textTheme.bodySmall!
                  .copyWith(color: AppColors.textMuted, fontSize: 11.5),
              maxLines: 2,
            ),
          ],
        ],
      ),
    );
  }
}
