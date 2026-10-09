/// Avaliação física do aluno (`StudentProgressResource`).
///
/// Unidades: peso/gordura em kg/%, FC em bpm, paces como texto.
class StudentProgress {
  const StudentProgress({
    required this.id,
    required this.recordedAt,
    this.weight,
    this.bodyFat,
    this.restingHeartRate,
    this.maxHeartRate,
    this.ftp,
    this.runningPace,
    this.swimmingPace,
    this.notes,
  });

  final int id;
  final DateTime? recordedAt;
  final double? weight;
  final double? bodyFat;
  final int? restingHeartRate;
  final int? maxHeartRate;
  final int? ftp;
  final String? runningPace;
  final String? swimmingPace;
  final String? notes;

  factory StudentProgress.fromJson(Map<String, dynamic> json) =>
      StudentProgress(
        id: (json['id'] as num?)?.toInt() ?? 0,
        recordedAt: DateTime.tryParse(json['recorded_at'] as String? ?? ''),
        weight: (json['weight'] as num?)?.toDouble(),
        bodyFat: (json['body_fat'] as num?)?.toDouble(),
        restingHeartRate: (json['resting_heart_rate'] as num?)?.toInt(),
        maxHeartRate: (json['max_heart_rate'] as num?)?.toInt(),
        ftp: (json['ftp'] as num?)?.toInt(),
        runningPace: json['running_pace'] as String?,
        swimmingPace: json['swimming_pace'] as String?,
        notes: json['notes'] as String?,
      );

  /// Data formatada `dd/mm/aaaa`.
  String get recordedAtLabel {
    final date = recordedAt;
    if (date == null) return '';
    final day = date.day.toString().padLeft(2, '0');
    final month = date.month.toString().padLeft(2, '0');
    return '$day/$month/${date.year}';
  }
}
