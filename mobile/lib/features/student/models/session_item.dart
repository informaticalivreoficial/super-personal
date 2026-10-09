/// Item de uma sessão de treino (`TrainingSessionItemResource`).
class SessionItem {
  const SessionItem({
    required this.id,
    required this.trainingSessionId,
    this.exerciseId,
    this.type = '',
    this.typeLabel,
    this.title = '',
    this.description,
    this.duration,
    this.distance,
    this.repetitions,
    this.sets,
    this.rest,
    this.target,
    this.intensity,
    this.sortOrder = 0,
  });

  final int id;
  final int trainingSessionId;
  final int? exerciseId;
  final String type;
  final String? typeLabel;
  final String title;
  final String? description;
  final int? duration;
  final int? distance;
  final int? repetitions;
  final int? sets;
  final String? rest;
  final String? target;
  final String? intensity;
  final int sortOrder;

  factory SessionItem.fromJson(Map<String, dynamic> json) => SessionItem(
        id: (json['id'] as num?)?.toInt() ?? 0,
        trainingSessionId: (json['training_session_id'] as num?)?.toInt() ?? 0,
        exerciseId: (json['exercise_id'] as num?)?.toInt(),
        type: json['type'] as String? ?? '',
        typeLabel: json['type_label'] as String?,
        title: json['title'] as String? ?? '',
        description: json['description'] as String?,
        duration: (json['duration'] as num?)?.toInt(),
        distance: (json['distance'] as num?)?.toInt(),
        repetitions: (json['repetitions'] as num?)?.toInt(),
        sets: (json['sets'] as num?)?.toInt(),
        rest: json['rest'] as String?,
        target: json['target'] as String?,
        intensity: json['intensity'] as String?,
        sortOrder: (json['sort_order'] as num?)?.toInt() ?? 0,
      );
}
