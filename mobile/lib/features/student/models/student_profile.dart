import '../formatters.dart';

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
    this.document,
    this.birthDate,
    this.photoUrl,
    this.availableDays = const [],
  });

  final int id;
  final String name;
  final String email;
  final String? phone;
  final String? gender;
  final int? height;

  /// CPF (usado como `tax_id` no Pix das cobranças online).
  final String? document;
  final DateTime? birthDate;
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
        document: json['document'] as String?,
        birthDate: DateTime.tryParse(json['birth_date'] as String? ?? ''),
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
    this.document,
    this.birthDate,
    required this.availableDays,
  });

  final String name;
  final String? phone;
  final String? gender;
  final int? height;
  final String? document;
  final DateTime? birthDate;
  final List<int> availableDays;

  Map<String, dynamic> toMap() => {
        'name': name,
        'phone': phone,
        'gender': gender,
        'height': height,
        'document': document,
        // A API valida a maioridade (18+).
        'birth_date': birthDate == null ? null : isoDate(birthDate!),
        'available_days': availableDays,
      };
}

