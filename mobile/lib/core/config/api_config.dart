/// Endereços da API por ambiente (definidos na compilação via `--dart-define`).
///
/// O padrão `10.0.2.2` funciona do emulador Android para a máquina host.
/// Em aparelho físico, informe o IP do notebook na rede local:
///
/// ```sh
/// flutter run --dart-define=API_BASE_URL=http://192.168.0.10
/// ```
class ApiConfig {
  static const String baseUrl = String.fromEnvironment(
    'API_BASE_URL',
    defaultValue: 'http://10.0.2.2',
  );

  /// API versionada (raiz `/api/v1` do Laravel).
  static const String v1 = '$baseUrl/api/v1';
}
