import 'package:flutter/material.dart';
import 'package:flutter/services.dart';

import 'app_colors.dart';

/// Tema do app do aluno (Material 3) — base do "design system" do mobile.
///
/// Tudo que é visual nasce aqui: paleta, tipografia (Plus Jakarta Sans),
/// cantos, alturas de botão, campos, chips, cards, bottom sheets e snackbars.
/// As telas não devem hardcodear cor/tamanho — se faltar algo, acrescente um
/// token aqui.
abstract final class AppTheme {
  /// Família empacotada em `assets/fonts` (5 pesos, subset latim).
  static const fontFamily = 'PlusJakartaSans';

  static const double radius = 18;
  static const double radiusSmall = 12;
  static const double radiusPill = 999;

  static ThemeData build() {
    final scheme = ColorScheme.fromSeed(
      seedColor: AppColors.brand500,
      brightness: Brightness.light,
    ).copyWith(
      primary: AppColors.brand600,
      onPrimary: Colors.white,
      primaryContainer: AppColors.brand100,
      onPrimaryContainer: AppColors.brand800,
      secondary: AppColors.brand700,
      onSecondary: Colors.white,
      secondaryContainer: AppColors.brand50,
      onSecondaryContainer: AppColors.brand800,
      tertiary: AppColors.brand400,
      onTertiary: AppColors.brand950,
      tertiaryContainer: AppColors.brand100,
      onTertiaryContainer: AppColors.brand800,
      error: AppColors.danger,
      onError: Colors.white,
      errorContainer: AppColors.dangerSoft,
      onErrorContainer: Color(0xFF7F1D1D),
      surface: AppColors.surface,
      onSurface: AppColors.textPrimary,
      onSurfaceVariant: AppColors.textSecondary,
      surfaceContainerLowest: Colors.white,
      surfaceContainerLow: Color(0xFFFBF8F4),
      surfaceContainer: AppColors.surfaceMuted,
      surfaceContainerHigh: Color(0xFFEFE9E2),
      surfaceContainerHighest: AppColors.neutralSoft,
      outline: AppColors.borderStrong,
      outlineVariant: AppColors.border,
      shadow: Color(0x1A241A15),
    );

    final base = Typography.material2021().black.apply(
          fontFamily: fontFamily,
          bodyColor: AppColors.textPrimary,
          displayColor: AppColors.textPrimary,
        );

    final textTheme = base.copyWith(
      displaySmall: base.displaySmall!.copyWith(
        fontSize: 34,
        fontWeight: FontWeight.w800,
        letterSpacing: -0.8,
        height: 1.1,
      ),
      headlineMedium: base.headlineMedium!.copyWith(
        fontSize: 26,
        fontWeight: FontWeight.w800,
        letterSpacing: -0.6,
      ),
      headlineSmall: base.headlineSmall!.copyWith(
        fontSize: 22,
        fontWeight: FontWeight.w800,
        letterSpacing: -0.4,
      ),
      titleLarge: base.titleLarge!.copyWith(
        fontSize: 18,
        fontWeight: FontWeight.w700,
        letterSpacing: -0.2,
      ),
      titleMedium: base.titleMedium!.copyWith(
        fontSize: 16,
        fontWeight: FontWeight.w700,
      ),
      titleSmall: base.titleSmall!.copyWith(
        fontSize: 14,
        fontWeight: FontWeight.w600,
      ),
      bodyLarge: base.bodyLarge!.copyWith(fontSize: 15, height: 1.45),
      bodyMedium: base.bodyMedium!.copyWith(fontSize: 14, height: 1.45),
      bodySmall: base.bodySmall!.copyWith(
        fontSize: 12.5,
        height: 1.4,
        color: AppColors.textSecondary,
      ),
      labelLarge: base.labelLarge!.copyWith(
        fontSize: 15,
        fontWeight: FontWeight.w700,
      ),
      labelMedium: base.labelMedium!.copyWith(
        fontSize: 12.5,
        fontWeight: FontWeight.w600,
        letterSpacing: 0.1,
      ),
      labelSmall: base.labelSmall!.copyWith(
        fontSize: 11,
        fontWeight: FontWeight.w600,
        letterSpacing: 0.2,
      ),
    );

    final border = OutlineInputBorder(
      borderRadius: BorderRadius.circular(radiusSmall + 2),
      borderSide: const BorderSide(color: AppColors.border),
    );

    return ThemeData(
      useMaterial3: true,
      colorScheme: scheme,
      fontFamily: fontFamily,
      textTheme: textTheme,
      scaffoldBackgroundColor: AppColors.background,
      splashFactory: InkSparkle.splashFactory,
      visualDensity: VisualDensity.standard,

      // ── AppBar: chapada, com um fio de borda embaixo ─────────────────────
      appBarTheme: AppBarTheme(
        backgroundColor: AppColors.surface,
        foregroundColor: AppColors.textPrimary,
        surfaceTintColor: Colors.transparent,
        elevation: 0,
        scrolledUnderElevation: 0,
        centerTitle: false,
        titleTextStyle: textTheme.titleLarge,
        iconTheme: const IconThemeData(color: AppColors.textPrimary, size: 22),
        actionsIconTheme:
            const IconThemeData(color: AppColors.textPrimary, size: 22),
        shape: const Border(bottom: BorderSide(color: AppColors.border)),
        systemOverlayStyle: SystemUiOverlayStyle.dark,
      ),

      // ── Cards ────────────────────────────────────────────────────────────
      cardTheme: CardThemeData(
        color: AppColors.surface,
        surfaceTintColor: Colors.transparent,
        elevation: 0,
        margin: EdgeInsets.zero,
        clipBehavior: Clip.antiAlias,
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(radius),
          side: const BorderSide(color: AppColors.border),
        ),
      ),

      // ── Botões ───────────────────────────────────────────────────────────
      filledButtonTheme: FilledButtonThemeData(
        style: FilledButton.styleFrom(
          backgroundColor: AppColors.brand600,
          foregroundColor: Colors.white,
          disabledBackgroundColor: AppColors.borderStrong,
          disabledForegroundColor: Colors.white,
          minimumSize: const Size(0, 50),
          padding: const EdgeInsets.symmetric(horizontal: 20),
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(radiusSmall + 2),
          ),
          textStyle: textTheme.labelLarge,
        ),
      ),
      outlinedButtonTheme: OutlinedButtonThemeData(
        style: OutlinedButton.styleFrom(
          foregroundColor: AppColors.brand700,
          side: const BorderSide(color: AppColors.borderStrong),
          minimumSize: const Size(0, 48),
          padding: const EdgeInsets.symmetric(horizontal: 18),
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(radiusSmall + 2),
          ),
          textStyle: textTheme.labelLarge,
        ),
      ),
      textButtonTheme: TextButtonThemeData(
        style: TextButton.styleFrom(
          foregroundColor: AppColors.brand700,
          textStyle: textTheme.labelLarge?.copyWith(fontSize: 14),
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(radiusSmall),
          ),
        ),
      ),
      iconButtonTheme: IconButtonThemeData(
        style: IconButton.styleFrom(
          foregroundColor: AppColors.textSecondary,
          highlightColor: AppColors.brand50,
        ),
      ),

      // ── Campos ───────────────────────────────────────────────────────────
      inputDecorationTheme: InputDecorationTheme(
        filled: true,
        fillColor: AppColors.surface,
        border: border,
        enabledBorder: border,
        disabledBorder: border.copyWith(
          borderSide: const BorderSide(color: AppColors.border),
        ),
        focusedBorder: border.copyWith(
          borderSide: const BorderSide(color: AppColors.brand500, width: 1.6),
        ),
        errorBorder: border.copyWith(
          borderSide: const BorderSide(color: AppColors.danger),
        ),
        focusedErrorBorder: border.copyWith(
          borderSide: const BorderSide(color: AppColors.danger, width: 1.6),
        ),
        contentPadding:
            const EdgeInsets.symmetric(horizontal: 16, vertical: 15),
        hintStyle: const TextStyle(color: AppColors.textMuted, fontSize: 15),
        labelStyle: const TextStyle(color: AppColors.textSecondary),
        floatingLabelStyle: const TextStyle(color: AppColors.brand700),
        errorStyle: const TextStyle(color: AppColors.danger, fontSize: 12),
        helperStyle: const TextStyle(color: AppColors.textMuted, fontSize: 12),
        prefixIconColor: AppColors.textMuted,
        suffixIconColor: AppColors.textMuted,
      ),

      // ── Chips / filtros ──────────────────────────────────────────────────
      chipTheme: ChipThemeData(
        backgroundColor: AppColors.surface,
        selectedColor: AppColors.brand100,
        disabledColor: AppColors.neutralSoft,
        side: const BorderSide(color: AppColors.border),
        shape: const StadiumBorder(),
        labelStyle: textTheme.labelMedium!
            .copyWith(color: AppColors.textSecondary),
        secondaryLabelStyle:
            textTheme.labelMedium!.copyWith(color: AppColors.brand800),
        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
        showCheckmark: false,
      ),

      // ── Listas / divisores ───────────────────────────────────────────────
      listTileTheme: ListTileThemeData(
        iconColor: AppColors.brand600,
        textColor: AppColors.textPrimary,
        subtitleTextStyle: textTheme.bodySmall,
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(radiusSmall),
        ),
        contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 4),
      ),
      dividerTheme: const DividerThemeData(
        color: AppColors.border,
        thickness: 1,
        space: 1,
      ),

      // ── Feedback ─────────────────────────────────────────────────────────
      snackBarTheme: SnackBarThemeData(
        behavior: SnackBarBehavior.floating,
        backgroundColor: AppColors.brand950,
        contentTextStyle: textTheme.bodyMedium!.copyWith(color: Colors.white),
        actionTextColor: AppColors.brand300,
        elevation: 2,
        insetPadding: const EdgeInsets.all(16),
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(radiusSmall + 2),
        ),
      ),
      bottomSheetTheme: const BottomSheetThemeData(
        backgroundColor: AppColors.surface,
        surfaceTintColor: Colors.transparent,
        showDragHandle: true,
        dragHandleColor: AppColors.borderStrong,
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
        ),
      ),
      dialogTheme: DialogThemeData(
        backgroundColor: AppColors.surface,
        surfaceTintColor: Colors.transparent,
        elevation: 3,
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(20),
        ),
        titleTextStyle: textTheme.titleLarge,
        contentTextStyle: textTheme.bodyMedium,
      ),
      progressIndicatorTheme: const ProgressIndicatorThemeData(
        color: AppColors.brand500,
        linearTrackColor: AppColors.brand100,
        circularTrackColor: AppColors.brand100,
      ),
      badgeTheme: BadgeThemeData(
        backgroundColor: AppColors.brand500,
        textColor: Colors.white,
        textStyle: textTheme.labelSmall!.copyWith(color: Colors.white),
      ),
      tooltipTheme: TooltipThemeData(
        decoration: BoxDecoration(
          color: AppColors.brand950,
          borderRadius: BorderRadius.circular(8),
        ),
        textStyle: textTheme.bodySmall!.copyWith(color: Colors.white),
      ),
      tabBarTheme: TabBarThemeData(
        labelColor: AppColors.brand700,
        unselectedLabelColor: AppColors.textSecondary,
        indicatorColor: AppColors.brand600,
        indicatorSize: TabBarIndicatorSize.label,
        dividerColor: AppColors.border,
        labelStyle: textTheme.labelLarge?.copyWith(fontSize: 14),
        unselectedLabelStyle:
            textTheme.labelLarge?.copyWith(fontSize: 14, fontWeight: FontWeight.w500),
      ),
      checkboxTheme: CheckboxThemeData(
        fillColor: WidgetStateProperty.resolveWith(
          (states) => states.contains(WidgetState.selected)
              ? AppColors.brand600
              : Colors.transparent,
        ),
        side: const BorderSide(color: AppColors.borderStrong, width: 1.5),
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(6),
        ),
      ),
      switchTheme: SwitchThemeData(
        thumbColor: WidgetStateProperty.resolveWith(
          (states) => states.contains(WidgetState.selected)
              ? Colors.white
              : AppColors.surface,
        ),
        trackColor: WidgetStateProperty.resolveWith(
          (states) => states.contains(WidgetState.selected)
              ? AppColors.brand500
              : AppColors.borderStrong,
        ),
      ),
      datePickerTheme: DatePickerThemeData(
        backgroundColor: AppColors.surface,
        surfaceTintColor: Colors.transparent,
        headerBackgroundColor: AppColors.brand700,
        headerForegroundColor: Colors.white,
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(20),
        ),
      ),
    );
  }
}
