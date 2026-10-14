/// Perfil do aluno autenticado (`GET/PUT /student/profile`).
///
/// Campos editáveis pelo próprio aluno; treino/avaliação são do treinador.
class StudentProfile {
  const StudentProfile({
    required this.id,
    required this.name,
    required this.email,
    this.phone,
    this.gender,
    this.height,
    this.photoUrl,
    this.availableDays = const [],
  });

  final int id;
  final String name;
  final String email;
  final String? phone;
  final String? gender;
  final int? height;
  final String? photoUrl;
  final List<int> availableDays;

  factory StudentProfile.fromJson(Map<String, dynamic> json) =>
      StudentProfile(
        id: (json['id'] as num?)?.toInt() ?? 0,
        name: json['name'] as String? ?? '',
        email: json['email'] as String? ?? '',
        phone: json['phone'] as String?,
        gender: json['gender'] as String?,
        height: (json['height'] as num?)?.toInt(),
        photoUrl: json['profile_photo_url'] as String?,
        availableDays: (json['available_days'] as List<dynamic>? ?? const [])
            .whereType<num>()
            .map((day) => day.toInt())
            .toList(growable: false),
      );
}

/// Payload da edição de perfil (`PUT /student/profile`).
class ProfileUpdateData {
  const ProfileUpdateData({
    required this.name,
    this.phone,
    this.gender,
    this.height,
    required this.availableDays,
  });

  final String name;
  final String? phone;
  final String? gender;
  final int? height;
  final List<int> availableDays;

  Map<String, dynamic> toMap() => {
        'name': name,
        'phone': phone,
        'gender': gender,
        'height': height,
        'available_days': availableDays,
      };
}
