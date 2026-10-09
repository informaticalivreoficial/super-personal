import 'execution.dart';
import 'session_item.dart';
import 'sport.dart';

/// Sessão de treino do aluno (`TrainingSessionResource`).
///
/// Unidades da API: `estimated_duration` em segundos, `distance` em metros.
class TrainingSession {
  const TrainingSession({
    required this.id,
    required this.trainingWeekId,
    this.sportId,
    this.title = '',
    this.description,
    this.scheduledDate,
    this.estimatedDuration,
    this.distance,
    this.intensity,
    this.targetPace,
    this.targetHeartRate,
    this.targetPower,
    this.instructions,
    this.coachNotes,
    this.status = '',
    this.statusLabel,
    this.sortOrder = 0,
    this.sport,
    this.items = const [],
    this.execution,
  });

  final int id;
  final int trainingWeekId;
  final int? sportId;
  final String title;
  final String? description;
  final DateTime? scheduledDate;
  final int? estimatedDuration;
  final int? distance;
  final String? intensity;
  final String? targetPace;
  final String? targetHeartRate;
  final String? targetPower;
  final String? instructions;
  final String? coachNotes;
  final String status;
  final String? statusLabel;
  final int sortOrder;
  final Sport? sport;
  final List<SessionItem> items;
  final TrainingExecution? execution;

  factory TrainingSession.fromJson(Map<String, dynamic> json) =>
      TrainingSession(
        id: (json['id'] as num?)?.toInt() ?? 0,
        trainingWeekId: (json['training_week_id'] as num?)?.toInt() ?? 0,
        sportId: (json['sport_id'] as num?)?.toInt(),
        title: json['title'] as String? ?? '',
        description: json['description'] as String?,
        scheduledDate: DateTime.tryParse(json['scheduled_date'] as String? ?? ''),
        estimatedDuration: (json['estimated_duration'] as num?)?.toInt(),
        distance: (json['distance'] as num?)?.toInt(),
        intensity: json['intensity'] as String?,
        targetPace: json['target_pace'] as String?,
        targetHeartRate: json['target_heart_rate'] as String?,
        targetPower: json['target_power'] as String?,
        instructions: json['instructions'] as String?,
        coachNotes: json['coach_notes'] as String?,
        status: json['status'] as String? ?? '',
        statusLabel: json['status_label'] as String?,
        sortOrder: (json['sort_order'] as num?)?.toInt() ?? 0,
        sport: json['sport'] is Map<String, dynamic>
            ? Sport.fromJson(json['sport'] as Map<String, dynamic>)
            : null,
        items: (json['items'] as List<dynamic>? ?? const [])
            .whereType<Map<String, dynamic>>()
            .map(SessionItem.fromJson)
            .toList(growable: false),
        execution: json['execution'] is Map<String, dynamic>
            ? TrainingExecution.fromJson(json['execution'] as Map<String, dynamic>)
            : null,
      );

  bool get isPlanned => status == 'planned';
  bool get isCompleted => status == 'completed';
  bool get isSkipped => status == 'skipped';

  /// Data formatada `dd/mm/aaaa` (ou vazio sem data).
  String get scheduledDateLabel {
    final date = scheduledDate;
    if (date == null) return '';
    final day = date.day.toString().padLeft(2, '0');
    final month = date.month.toString().padLeft(2, '0');
    return '$day/$month/${date.year}';
  }
}
