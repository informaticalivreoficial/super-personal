/// Execução do aluno em uma sessão (`TrainingExecutionResource`).
///
/// Unidades da API: duração em segundos, distância em metros.
class TrainingExecution {
  const TrainingExecution({
    required this.id,
    required this.sessionId,
    this.startedAt,
    this.completedAt,
    this.duration,
    this.distance,
    this.averageHeartRate,
    this.maxHeartRate,
    this.averagePace,
    this.averagePower,
    this.perceivedEffort,
    this.feeling,
    this.notes,
    required this.status,
    required this.statusLabel,
  });

  final int id;
  final int sessionId;
  final DateTime? startedAt;
  final DateTime? completedAt;
  final int? duration;
  final int? distance;
  final int? averageHeartRate;
  final int? maxHeartRate;
  final String? averagePace;
  final int? averagePower;
  final int? perceivedEffort;
  final String? feeling;
  final String? notes;
  final String status;
  final String statusLabel;

  factory TrainingExecution.fromJson(Map<String, dynamic> json) =>
      TrainingExecution(
        id: (json['id'] as num?)?.toInt() ?? 0,
        sessionId: (json['training_session_id'] as num?)?.toInt() ?? 0,
        startedAt: DateTime.tryParse(json['started_at'] as String? ?? ''),
        completedAt: DateTime.tryParse(json['completed_at'] as String? ?? ''),
        duration: (json['duration'] as num?)?.toInt(),
        distance: (json['distance'] as num?)?.toInt(),
        averageHeartRate: (json['average_heart_rate'] as num?)?.toInt(),
        maxHeartRate: (json['max_heart_rate'] as num?)?.toInt(),
        averagePace: json['average_pace'] as String?,
        averagePower: (json['average_power'] as num?)?.toInt(),
        perceivedEffort: (json['perceived_effort'] as num?)?.toInt(),
        feeling: json['feeling'] as String?,
        notes: json['notes'] as String?,
        status: json['status'] as String? ?? '',
        statusLabel: json['status_label'] as String? ?? '',
      );
}

/// Dados informados ao concluir/editar uma execução (`CompleteTrainingRequest`).
///
/// Todos os campos são opcionais na API; nulos são omitidos no payload.
class ExecutionData {
  const ExecutionData({
    this.duration,
    this.distance,
    this.averageHeartRate,
    this.maxHeartRate,
    this.averagePace,
    this.averagePower,
    this.perceivedEffort,
    this.feeling,
    this.notes,
  });

  final int? duration;
  final int? distance;
  final int? averageHeartRate;
  final int? maxHeartRate;
  final String? averagePace;
  final int? averagePower;
  final int? perceivedEffort;
  final String? feeling;
  final String? notes;

  Map<String, dynamic> toMap() => {
        if (duration != null) 'duration': duration,
        if (distance != null) 'distance': distance,
        if (averageHeartRate != null) 'average_heart_rate': averageHeartRate,
        if (maxHeartRate != null) 'max_heart_rate': maxHeartRate,
        if (averagePace != null) 'average_pace': averagePace,
        if (averagePower != null) 'average_power': averagePower,
        if (perceivedEffort != null) 'perceived_effort': perceivedEffort,
        if (feeling != null) 'feeling': feeling,
        if (notes != null) 'notes': notes,
      };
}
