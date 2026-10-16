/// Formatadores de unidades canônicas da API
/// (duração em segundos, distância em metros) e de datas.
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

/// Número decimal no padrão brasileiro (`78,5`).
String formatDecimal(double value, {int decimals = 1}) =>
    value.toStringAsFixed(decimals).replaceAll('.', ',');

/// Data → `10/05/1990`.
String formatDate(DateTime date) {
  final day = date.day.toString().padLeft(2, '0');
  final month = date.month.toString().padLeft(2, '0');
  return '$day/$month/${date.year}';
}

/// Data → `1990-05-10` (formato aceito pela API).
String isoDate(DateTime date) {
  final month = date.month.toString().padLeft(2, '0');
  final day = date.day.toString().padLeft(2, '0');
  return '${date.year}-$month-$day';
}

/// Idade em anos completos na data [on] (default: hoje).
int ageInYears(DateTime birthDate, {DateTime? on}) {
  final today = on ?? DateTime.now();
  var age = today.year - birthDate.year;
  final hadBirthday = today.month > birthDate.month ||
      (today.month == birthDate.month && today.day >= birthDate.day);
  if (!hadBirthday) age--;

  return age;
}

