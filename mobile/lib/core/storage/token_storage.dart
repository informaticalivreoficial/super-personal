import 'package:flutter_secure_storage/flutter_secure_storage.dart';

/// Armazenamento do token de acesso (Sanctum PAT).
abstract class TokenStorage {
  Future<String?> read();
  Future<void> write(String token);
  Future<void> delete();
}

/// Persistência segura no aparelho (Keystore no Android / Keychain no iOS).
class SecureTokenStorage implements TokenStorage {
  const SecureTokenStorage();

  static const String _key = 'api_token';
  final FlutterSecureStorage _storage = const FlutterSecureStorage();

  @override
  Future<String?> read() => _storage.read(key: _key);

  @override
  Future<void> write(String token) => _storage.write(key: _key, value: token);

  @override
  Future<void> delete() => _storage.delete(key: _key);
}

/// Em memória — usado nos testes.
class InMemoryTokenStorage implements TokenStorage {
  String? token;

  @override
  Future<String?> read() async => token;

  @override
  Future<void> write(String token) async {
    this.token = token;
  }

  @override
  Future<void> delete() async {
    token = null;
  }
}
