import 'package:dio/dio.dart';

import '../../core/api/api_client.dart';
import '../../core/api/api_exception.dart';
import 'user.dart';

/// Resultado do login: token persistido + usuário autenticado.
class LoginResult {
  const LoginResult({required this.token, required this.user});

  final String token;
  final User user;
}

/// Chamadas de autenticação da API (`/auth/*`).
class AuthRepository {
  AuthRepository(this._client);

  final ApiClient _client;

  /// Login em `POST /auth/login`; grava o token e devolve o usuário.
  Future<LoginResult> login({
    required String email,
    required String password,
  }) async {
    try {
      final response = await _client.dio.post(
        'auth/login',
        data: {'email': email, 'password': password},
      );
      final data = response.data as Map<String, dynamic>;
      final token = data['token'] as String;
      await _client.tokenStorage.write(token);

      return LoginResult(
        token: token,
        user: User.fromJson(data['user'] as Map<String, dynamic>),
      );
    } on DioException catch (error) {
      throw ApiException.fromDio(error);
    }
  }

  /// Sessão atual em `GET /auth/me` (token guardado no aparelho).
  Future<User> me() async {
    try {
      final response = await _client.dio.get('auth/me');
      final data = response.data as Map<String, dynamic>;

      return User.fromJson(data['data'] as Map<String, dynamic>);
    } on DioException catch (error) {
      throw ApiException.fromDio(error);
    }
  }

  /// Encerra a sessão no servidor e descarta o token local.
  Future<void> logout() async {
    try {
      await _client.dio.post('auth/logout');
    } on DioException {
      // Sessão já inválida no servidor — o token local sai de qualquer forma.
    } finally {
      await _client.tokenStorage.delete();
    }
  }
}
