import 'package:flutter/material.dart';

import 'app_tone.dart';

/// Selo de status compacto (pílula com fundo suave).
class StatusChip extends StatelessWidget {
  const StatusChip({
    super.key,
    required this.label,
    this.tone,
    this.status,
    this.icon,
  });

  final String label;

  /// Tom explícito; se ausente, é derivado de [status].
  final AppTone? tone;

  /// Status cru da API (`planned`, `completed`...).
  final String? status;

  final IconData? icon;

  @override
  Widget build(BuildContext context) {
    final resolved = tone ?? toneForStatus(status);

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
      decoration: BoxDecoration(
        color: resolved.background,
        borderRadius: BorderRadius.circular(999),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          if (icon != null) ...[
            Icon(icon, size: 13, color: resolved.foreground),
            const SizedBox(width: 4),
          ],
          Text(
            label,
            style: Theme.of(context).textTheme.labelSmall!.copyWith(
                  color: resolved.foreground,
                  fontWeight: FontWeight.w700,
                ),
          ),
        ],
      ),
    );
  }
}
