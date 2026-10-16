import 'package:flutter/material.dart';

/// Paleta do app do aluno.
///
/// A escala `brand` é a mesma do painel web (`tailwind.config.js`), para os
/// dois produtos falarem a mesma língua visual: laranja vivo (`brand-400`) até
/// o marrom escuro da sidebar (`brand-950`). Os neutros são **quentes** (bege)
/// para combinar com o fundo creme da logomarca.
abstract final class AppColors {
  // ── Marca (laranja → marrom) ─────────────────────────────────────────────
  static const brand50 = Color(0xFFFFFAF3);
  static const brand100 = Color(0xFFFFF4E1);
  static const brand200 = Color(0xFFFFE8C1);
  static const brand300 = Color(0xFFFFD68F);
  static const brand400 = Color(0xFFFFA506);
  static const brand500 = Color(0xFFD58204);
  static const brand600 = Color(0xFFAC5F03);
  static const brand700 = Color(0xFF823C01);
  static const brand800 = Color(0xFF591900);
  static const brand900 = Color(0xFF431300);
  static const brand950 = Color(0xFF2C0C00);

  // ── Neutros quentes ──────────────────────────────────────────────────────
  /// Fundo creme da logomarca (splash).
  static const cream = Color(0xFFFCFAEB);

  /// Fundo das telas (levemente mais quente que o branco dos cards).
  static const background = Color(0xFFF8F5F1);

  static const surface = Color(0xFFFFFFFF);

  /// Fundo de blocos internos (chips de ícone, campos preenchidos).
  static const surfaceMuted = Color(0xFFF4EFE9);

  static const border = Color(0xFFEDE5DC);
  static const borderStrong = Color(0xFFDDD2C6);

  static const textPrimary = Color(0xFF241A15);
  static const textSecondary = Color(0xFF6B5E56);
  static const textMuted = Color(0xFF9A8C83);

  // ── Semânticas (status) ──────────────────────────────────────────────────
  static const success = Color(0xFF15803D);
  static const successSoft = Color(0xFFE7F6EC);

  static const warning = Color(0xFFB45309);
  static const warningSoft = Color(0xFFFDF3E3);

  static const danger = Color(0xFFB91C1C);
  static const dangerSoft = Color(0xFFFDECEC);

  static const info = Color(0xFF0369A1);
  static const infoSoft = Color(0xFFE8F4FB);

  static const neutralSoft = Color(0xFFF0EBE5);

  /// Gradiente do cabeçalho das telas (marrom profundo da marca).
  static const headerGradient = LinearGradient(
    begin: Alignment.topLeft,
    end: Alignment.bottomRight,
    colors: [brand950, brand800, brand700],
  );
}
