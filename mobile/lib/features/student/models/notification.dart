/// Notificação database do aluno (`NotificationResource`).
///
/// `data` traz `title`/`message` (pt-BR) preenchidos pelos services.
class AppNotification {
  const AppNotification({
    required this.id,
    this.type = '',
    this.data = const {},
    this.readAt,
    this.createdAt,
  });

  final int id;
  final String type;
  final Map<String, dynamic> data;
  final DateTime? readAt;
  final DateTime? createdAt;

  bool get isRead => readAt != null;

  String get title => data['title'] as String? ?? 'Notificação';
  String get message => data['message'] as String? ?? '';

  factory AppNotification.fromJson(Map<String, dynamic> json) =>
      AppNotification(
        id: (json['id'] as num?)?.toInt() ?? 0,
        type: json['type'] as String? ?? '',
        data: json['data'] is Map<String, dynamic>
            ? json['data'] as Map<String, dynamic>
            : const {},
        readAt: DateTime.tryParse(json['read_at'] as String? ?? ''),
        createdAt: DateTime.tryParse(json['created_at'] as String? ?? ''),
      );
}
