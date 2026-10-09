import 'package:dio/dio.dart';

/// Erro de acesso à API com mensagem pronta em pt-BR para a interface.
class ApiException implements Exception {
  ApiException(this.message, {this.statusCode, this.fieldErrors = const {}});

  /// Mensagem pronta para exibir ao usuário.
  final String message;

  final int? statusCode;

  /// Erros de validação (422): campo → primeira mensagem.
  final Map<String, String> fieldErrors;

  bool get isUnauthorized => statusCode == 401;

  /// Converte [DioException] (4xx/5xx/timeouts) em mensagem de usuário.
  ///
  /// O dio lança exceção para status fora de 200–299 e, quando há resposta,
  /// o corpo JSON já vem parseado em `error.response?.data`.
  factory ApiException.fromDio(DioException error) {
    final response = error.response;
    final status = response?.statusCode;
    final body = response?.data;

    if (body is Map<String, dynamic>) {
      final message = body['message'];
      if (message is String && message.isNotEmpty) {
        return ApiException(
          message,
          statusCode: status,
          fieldErrors: _fieldErrors(body),
        );
      }

      if (status == 422) {
        return ApiException(
          'Dados inválidos. Verifique os campos.',
          statusCode: status,
          fieldErrors: _fieldErrors(body),
        );
      }
    }

    if (status == 401) {
      return ApiException('Credenciais inválidas.', statusCode: 401);
    }
    if (status == 403) {
      return ApiException('Acesso negado.', statusCode: 403);
    }
    if (status == 429) {
      return ApiException(
        'Muitas tentativas. Tente novamente em instantes.',
        statusCode: 429,
      );
    }
    if (status != null && status >= 500) {
      return ApiException('Erro no servidor. Tente novamente.', statusCode: status);
    }

    // Sem resposta (offline, timeout, DNS...).
    return ApiException('Sem conexão com o servidor. Verifique sua internet.');
  }

  static Map<String, String> _fieldErrors(Map<String, dynamic> body) {
    final errors = body['errors'];
    if (errors is! Map<String, dynamic>) {
      return const {};
    }

    return errors.map((field, value) {
      if (value is List && value.isNotEmpty && value.first is String) {
        return MapEntry(field, value.first as String);
      }
      return MapEntry(field, 'Valor inválido.');
    });
  }

  @override
  String toString() => message;
}
