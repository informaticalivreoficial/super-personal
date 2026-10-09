import 'training_session.dart';

/// Plano de treino do aluno (`TrainingPlanResource`).
class TrainingPlan {
  const TrainingPlan({
    required this.id,
    required this.name,
    this.description,
    this.goal,
    this.startDate,
    this.endDate,
    this.status = '',
    this.statusLabel,
    this.notes,
    this.weeks = const [],
  });

  final int id;
  final String name;
  final String? description;
  final String? goal;
  final DateTime? startDate;
  final DateTime? endDate;
  final String status;
  final String? statusLabel;
  final String? notes;
  final List<TrainingWeek> weeks;

  factory TrainingPlan.fromJson(Map<String, dynamic> json) => TrainingPlan(
        id: (json['id'] as num?)?.toInt() ?? 0,
        name: json['name'] as String? ?? '',
        description: json['description'] as String?,
        goal: json['goal'] as String?,
        startDate: DateTime.tryParse(json['start_date'] as String? ?? ''),
        endDate: DateTime.tryParse(json['end_date'] as String? ?? ''),
        status: json['status'] as String? ?? '',
        statusLabel: json['status_label'] as String?,
        notes: json['notes'] as String?,
        weeks: (json['weeks'] as List<dynamic>? ?? const [])
            .whereType<Map<String, dynamic>>()
            .map(TrainingWeek.fromJson)
            .toList(growable: false),
      );
}

/// Semana de um plano (`TrainingWeekResource`).
class TrainingWeek {
  const TrainingWeek({
    required this.id,
    required this.trainingPlanId,
    required this.weekNumber,
    this.name,
    this.startDate,
    this.endDate,
    this.objective,
    this.status = '',
    this.statusLabel,
    this.sessions = const [],
  });

  final int id;
  final int trainingPlanId;
  final int weekNumber;
  final String? name;
  final DateTime? startDate;
  final DateTime? endDate;
  final String? objective;
  final String status;
  final String? statusLabel;
  final List<TrainingSession> sessions;

  factory TrainingWeek.fromJson(Map<String, dynamic> json) => TrainingWeek(
        id: (json['id'] as num?)?.toInt() ?? 0,
        trainingPlanId: (json['training_plan_id'] as num?)?.toInt() ?? 0,
        weekNumber: (json['week_number'] as num?)?.toInt() ?? 0,
        name: json['name'] as String?,
        startDate: DateTime.tryParse(json['start_date'] as String? ?? ''),
        endDate: DateTime.tryParse(json['end_date'] as String? ?? ''),
        objective: json['objective'] as String?,
        status: json['status'] as String? ?? '',
        statusLabel: json['status_label'] as String?,
        sessions: (json['sessions'] as List<dynamic>? ?? const [])
            .whereType<Map<String, dynamic>>()
            .map(TrainingSession.fromJson)
            .toList(growable: false),
      );
}
