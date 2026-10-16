/// Usuário autenticado (recorte do `UserResource` da API que o app consome).
class User {
  const User({
    required this.id,
    required this.name,
    required this.email,
    required this.role,
    this.pendingEmail,
  });

  factory User.fromJson(Map<String, dynamic> json) => User(
        id: json['id'] as int,
        name: json['name'] as String? ?? '',
        email: json['email'] as String? ?? '',
        role: json['role'] as String? ?? '',
        pendingEmail: json['pending_email'] as String?,
      );

  final int id;
  final String name;
  final String email;

  /// Endereço novo aguardando o código de confirmação (`null` = nada pendente).
  final String? pendingEmail;

  /// `student` no app do aluno.
  final String role;
}
