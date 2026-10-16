import 'package:flutter/material.dart';

import '../theme/app_colors.dart';

/// Tons semânticos usados por chips, ícones e blocos de destaque.
enum AppTone { brand, success, warning, danger, info, neutral }

/// Cores (fundo suave + cor forte) de cada tom.
extension AppToneColors on AppTone {
  Color get background => switch (this) {
        AppTone.brand => AppColors.brand100,
        AppTone.success => AppColors.successSoft,
        AppTone.warning => AppColors.warningSoft,
        AppTone.danger => AppColors.dangerSoft,
        AppTone.info => AppColors.infoSoft,
        AppTone.neutral => AppColors.neutralSoft,
      };

  Color get foreground => switch (this) {
        AppTone.brand => AppColors.brand700,
        AppTone.success => AppColors.success,
        AppTone.warning => AppColors.warning,
        AppTone.danger => AppColors.danger,
        AppTone.info => AppColors.info,
        AppTone.neutral => AppColors.textSecondary,
      };
}

/// Traduz o status cru da API (`planned`, `completed`, `paid`...) em um tom.
AppTone toneForStatus(String? status) => switch (status) {
      'completed' || 'paid' || 'active' || 'approved' => AppTone.success,
      'skipped' || 'cancelled' || 'canceled' => AppTone.neutral,
      'overdue' || 'failed' || 'rejected' => AppTone.danger,
      'pending' || 'waiting' => AppTone.warning,
      'planned' || 'scheduled' => AppTone.info,
      _ => AppTone.brand,
    };
