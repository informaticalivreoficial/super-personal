/// Notificação database do aluno (`NotificationResource`).
///
/// `data` traz `title`/`message` (pt-BR) preenchidos pelos services.
///
/// O `id` é **UUID** (string) — a tabela `notifications` do Laravel usa UUID
/// como chave. Converter para `int` faria todas as notificações colapsarem em
/// `0` e quebraria a deduplicação do polling (`refreshSilently`).
class AppNotification {
  const AppNotification({
    required this.id,
    this.type = '',
    this.data = const {},
    this.readAt,
    this.createdAt,
  });

  final String id;
  final String type;
  final Map<String, dynamic> data;
  final DateTime? readAt;
  final DateTime? createdAt;

  bool get isRead => readAt != null;

  String get title => data['title'] as String? ?? 'Notificação';
  String get message => data['message'] as String? ?? '';

  /// Cópia com o [`readAt`] informado (usada ao marcar como lida localmente).
  AppNotification copyWith({DateTime? readAt}) => AppNotification(
        id: id,
        type: type,
        data: data,
        readAt: readAt ?? this.readAt,
        createdAt: createdAt,
      );

  factory AppNotification.fromJson(Map<String, dynamic> json) =>
      AppNotification(
        id: json['id']?.toString() ?? '',
        type: json['type'] as String? ?? '',
        data: json['data'] is Map<String, dynamic>
            ? json['data'] as Map<String, dynamic>
            : const {},
        readAt: DateTime.tryParse(json['read_at'] as String? ?? ''),
        createdAt: DateTime.tryParse(json['created_at'] as String? ?? ''),
      );
}
