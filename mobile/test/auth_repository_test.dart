import 'package:dio/dio.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:super_personal/core/api/api_exception.dart';
import 'package:super_personal/features/auth/auth_repository.dart';

import 'support/api_test_helpers.dart';

const _userJson = {
  'id': 7,
  'name': 'Aluno Teste',
  'email': 'aluno@superpersonal.test',
  'role': 'student',
};

void main() {
  group('AuthRepository.login', () {
    test('grava o token e devolve o usuário', () async {
      late RequestOptions captured;
      final client = clientWith((options) async {
        captured = options;
        return jsonResponse({
          'token': 'tok-123',
          'token_type': 'Bearer',
          'user': _userJson,
        }, 200);
      });

      final result = await AuthRepository(client).login(
        email: 'aluno@superpersonal.test',
        password: 'senha-segura-123',
      );

      expect(result.token, 'tok-123');
      expect(result.user.id, 7);
      expect(result.user.role, 'student');
      expect(await client.tokenStorage.read(), 'tok-123');
      expect(captured.uri.path, '/api/v1/auth/login');
    });

    test('401 vira ApiException com mensagem da API', () async {
      final client = clientWith(
        (_) async => jsonResponse({'message': 'Credenciais inválidas.'}, 401),
      );

      expect(
        () => AuthRepository(client).login(
          email: 'aluno@superpersonal.test',
          password: 'errada',
        ),
        throwsA(
          isA<ApiException>()
              .having((e) => e.message, 'message', 'Credenciais inválidas.')
              .having((e) => e.statusCode, 'statusCode', 401)
              .having((e) => e.isUnauthorized, 'isUnauthorized', true),
        ),
      );
    });

    test('422 expõe erros por campo', () async {
      final client = clientWith(
        (_) async => jsonResponse({
          'message': 'Dados inválidos.',
          'errors': {
            'email': ['Informe um e-mail válido.'],
          },
        }, 422),
      );

      expect(
        () => AuthRepository(client).login(email: 'x', password: 'y'),
        throwsA(
          isA<ApiException>().having(
            (e) => e.fieldErrors['email'],
            'fieldErrors[email]',
            'Informe um e-mail válido.',
          ),
        ),
      );
    });

    test('sem resposta (offline) vira mensagem de conexão', () async {
      final client = clientWith((_) async {
        throw DioException.connectionTimeout(
          timeout: const Duration(seconds: 1),
          requestOptions: RequestOptions(path: 'auth/login'),
        );
      });

      expect(
        () => AuthRepository(client).login(email: 'x', password: 'y'),
        throwsA(
          isA<ApiException>().having(
            (e) => e.message,
            'message',
            'Sem conexão com o servidor. Verifique sua internet.',
          ),
        ),
      );
    });
  });

  group('AuthRepository.me', () {
    test('desembrulha {data: ...} do recurso', () async {
      final client = clientWith(
        (_) async => jsonResponse({'data': _userJson}, 200),
      );
      await client.tokenStorage.write('tok-123');

      final user = await AuthRepository(client).me();

      expect(user.name, 'Aluno Teste');
      expect(user.email, 'aluno@superpersonal.test');
    });
  });

  group('AuthRepository.logout', () {
    test('sempre descarta o token local, mesmo com falha na API', () async {
      final client = clientWith(
        (_) async => jsonResponse({'message': 'Erro.'}, 500),
      );
      await client.tokenStorage.write('tok-123');

      await AuthRepository(client).logout();

      expect(await client.tokenStorage.read(), isNull);
    });
  });
}
