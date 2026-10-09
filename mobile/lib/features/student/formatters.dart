/// Formatadores de unidades canônicas da API
/// (duração em segundos, distância em metros).
library;

/// Segundos → `1h05 min` / `45 min`.
String formatDuration(int seconds) {
  final hours = seconds ~/ 3600;
  final minutes = (seconds % 3600) ~/ 60;
  if (hours > 0) {
    return '$hours h ${minutes.toString().padLeft(2, '0')} min';
  }
  return '$minutes min';
}

/// Metros → `12,3 km` / `800 m`.
String formatDistance(int meters) {
  if (meters >= 1000) {
    final km = meters / 1000;
    final text = km.toStringAsFixed(1).replaceAll('.', ',');
    return '$text km';
  }
  return '$meters m';
}
