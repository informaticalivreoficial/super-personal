import '../config/api_config.dart';
import '../storage/token_storage.dart';
import 'package:dio/dio.dart';

/// Cliente HTTP da API v1.
///
/// Injeta o token Bearer em cada requisição e avisa a camada de auth
/// quando a API responde 401 (token expirado/revogado).
class ApiClient {
  ApiClient({required this.tokenStorage, String? baseUrl})
      : dio = Dio(
          BaseOptions(
            baseUrl: '${baseUrl ?? ApiConfig.v1}/',
            connectTimeout: const Duration(seconds: 10),
            receiveTimeout: const Duration(seconds: 15),
            headers: const {'Accept': 'application/json'},
          ),
        ) {
    dio.interceptors.add(
      InterceptorsWrapper(
        onRequest: (options, handler) async {
          final token = await tokenStorage.read();
          if (token != null && token.isNotEmpty) {
            options.headers['Authorization'] = 'Bearer $token';
          }
          handler.next(options);
        },
        onError: (error, handler) {
          if (error.response?.statusCode == 401) {
            onUnauthorized?.call();
          }
          handler.next(error);
        },
      ),
    );
  }

  final Dio dio;
  final TokenStorage tokenStorage;

  /// Chamado em qualquer 401 da API — o controller de auth derruba a sessão
  /// e o router redireciona para o login.
  void Function()? onUnauthorized;
}
