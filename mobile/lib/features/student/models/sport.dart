/// Modalidade esportiva de uma sessão (`SportResource`).
class Sport {
  const Sport({required this.id, required this.name, this.icon});

  final int id;
  final String name;
  final String? icon;

  factory Sport.fromJson(Map<String, dynamic> json) => Sport(
        id: (json['id'] as num?)?.toInt() ?? 0,
        name: json['name'] as String? ?? '',
        icon: json['icon'] as String?,
      );
}
