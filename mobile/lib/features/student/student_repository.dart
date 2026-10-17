import 'package:dio/dio.dart';

import '../../core/api/api_client.dart';
import '../../core/api/api_exception.dart';
import 'models/dashboard.dart';
import 'models/notification.dart';
import 'models/payment.dart';
import 'models/progress.dart';
import 'models/training_plan.dart';
import 'models/training_session.dart';
import 'models/execution.dart';
import 'models/student_profile.dart';

/// Página de recursos paginados da API (`data` + `meta` do Laravel).
class PagedResult<T> {
  const PagedResult({required this.items, this.currentPage = 1, this.lastPage = 1});

  final List<T> items;
  final int currentPage;
  final int lastPage;

  bool get hasMore => currentPage < lastPage;

  factory PagedResult.fromJson(
    Map<String, dynamic> json,
    T Function(Map<String, dynamic>) fromJson,
  ) {
    final data = json['data'];
    final meta = json['meta'] as Map<String, dynamic>? ?? const {};

    return PagedResult(
      items: (data is List ? data : const [])
          .whereType<Map<String, dynamic>>()
          .map(fromJson)
          .toList(growable: false),
      currentPage: (meta['current_page'] as num?)?.toInt() ?? 1,
      lastPage: (meta['last_page'] as num?)?.toInt() ?? 1,
    );
  }
}

/// Chamadas do aluno autenticado (`/student/*`).
class StudentRepository {
  StudentRepository(this._client);

  final ApiClient _client;

  Future<Map<String, dynamic>> _get(String path,
      [Map<String, dynamic>? query]) async {
    try {
      final response =
          await _client.dio.get(path, queryParameters: query);
      final data = response.data;
      // Coleções e resources vêm envelopados em `data`; payloads simples não.
      if (data is Map<String, dynamic> && data.containsKey('data')) {
        return data;
      }
      if (data is Map<String, dynamic>) {
        return {'data': data};
      }
      return {'data': data};
    } on DioException catch (error) {
      throw ApiException.fromDio(error);
    }
  }

  /// Resumo do aluno em `GET /student/dashboard`.
  Future<StudentDashboard> dashboard() async {
    final json = await _get('student/dashboard');
    return StudentDashboard.fromJson(json['data'] as Map<String, dynamic>);
  }

  /// Perfil do aluno em `GET /student/profile`.
  Future<StudentProfile> profile() async {
    final json = await _get('student/profile');
    return StudentProfile.fromJson(json['data'] as Map<String, dynamic>);
  }

  /// Atualiza o próprio perfil em `PUT /student/profile`.
  Future<StudentProfile> updateProfile(ProfileUpdateData data) async {
    try {
      final response = await _client.dio.put(
        'student/profile',
        data: data.toMap(),
      );
      final body = response.data;
      final map = body is Map<String, dynamic> && body['data'] is Map<String, dynamic>
          ? body['data'] as Map<String, dynamic>
          : body as Map<String, dynamic>;
      return StudentProfile.fromJson(map);
    } on DioException catch (error) {
      throw ApiException.fromDio(error);
    }
  }

  /// Envia a foto de perfil (multipart: campo `photo`) em
  /// `POST /student/profile/photo`.
  Future<StudentProfile> uploadProfilePhoto(String filePath) async {
    try {
      final form = FormData.fromMap({
        'photo': await MultipartFile.fromFile(filePath),
      });
      final response = await _client.dio.post(
        'student/profile/photo',
        data: form,
      );
      return _unwrapProfile(response.data);
    } on DioException catch (error) {
      throw ApiException.fromDio(error);
    }
  }

  /// Remove a foto de perfil em `DELETE /student/profile/photo`.
  Future<StudentProfile> removeProfilePhoto() async {
    try {
      final response = await _client.dio.delete('student/profile/photo');
      return _unwrapProfile(response.data);
    } on DioException catch (error) {
      throw ApiException.fromDio(error);
    }
  }

  /// Pede a troca do e-mail de acesso em `POST /student/profile/email`.
  ///
  /// O e-mail atual continua valendo: a API envia um código de 6 dígitos para
  /// o endereço novo e devolve o endereço pendente.
  Future<String> requestEmailChange(String email) async {
    try {
      final response = await _client.dio.post(
        'student/profile/email',
        data: {'email': email},
      );
      final body = response.data as Map<String, dynamic>;

      return body['pending_email'] as String? ?? email;
    } on DioException catch (error) {
      throw ApiException.fromDio(error);
    }
  }

  /// Confirma o código e efetiva o novo e-mail
  /// (`POST /student/profile/email/confirm`).
  Future<void> confirmEmailChange(String code) async {
    try {
      await _client.dio.post(
        'student/profile/email/confirm',
        data: {'code': code},
      );
    } on DioException catch (error) {
      throw ApiException.fromDio(error);
    }
  }

  /// Desiste da troca de e-mail em andamento (`DELETE /student/profile/email`).
  Future<void> cancelEmailChange() async {
    try {
      await _client.dio.delete('student/profile/email');
    } on DioException catch (error) {
      throw ApiException.fromDio(error);
    }
  }

  /// Troca a senha em `PUT /student/profile/password` (a atual é obrigatória e
  /// as outras sessões caem).
  Future<void> changePassword({
    required String currentPassword,
    required String password,
    required String confirmation,
  }) async {
    try {
      await _client.dio.put(
        'student/profile/password',
        data: {
          'current_password': currentPassword,
          'password': password,
          'password_confirmation': confirmation,
        },
      );
    } on DioException catch (error) {
      throw ApiException.fromDio(error);
    }
  }

  StudentProfile _unwrapProfile(dynamic body) {
    if (body is Map<String, dynamic> && body['data'] is Map<String, dynamic>) {
      return StudentProfile.fromJson(body['data'] as Map<String, dynamic>);
    }
    return StudentProfile.fromJson(body as Map<String, dynamic>);
  }

  /// Planos ativos em `GET /student/training-plans`.
  Future<List<TrainingPlan>> trainingPlans() async {
    final json = await _get('student/training-plans');
    return (json['data'] as List<dynamic>? ?? const [])
        .whereType<Map<String, dynamic>>()
        .map(TrainingPlan.fromJson)
        .toList(growable: false);
  }

  /// Plano completo (semanas/sessões/itens) em `GET /student/training-plans/{id}`.
  Future<TrainingPlan> trainingPlan(int planId) async {
    final json = await _get('student/training-plans/$planId');
    return TrainingPlan.fromJson(json['data'] as Map<String, dynamic>);
  }

  /// Sessões do período em `GET /student/calendar` (padrão: mês atual).
  Future<List<TrainingSession>> calendar({String? from, String? to}) async {
    final json = await _get('student/calendar', {
      'from': ?from,
      'to': ?to,
    });
    return (json['data'] as List<dynamic>? ?? const [])
        .whereType<Map<String, dynamic>>()
        .map(TrainingSession.fromJson)
        .toList(growable: false);
  }

  /// Sessões paginadas em `GET /student/trainings` (mais recentes primeiro).
  Future<PagedResult<TrainingSession>> trainings({int page = 1, String? status}) {
    return _paged(
      'student/trainings',
      {'page': page, 'status': ?status},
      TrainingSession.fromJson,
    );
  }

  /// Sessão única com itens/execução em `GET /student/trainings/{id}`.
  Future<TrainingSession> training(int sessionId) async {
    final json = await _get('student/trainings/$sessionId');
    return TrainingSession.fromJson(json['data'] as Map<String, dynamic>);
  }

  /// Inicia o treino (idempotente) em `POST /student/trainings/{id}/start`.
  Future<TrainingExecution> startTraining(int sessionId) async {
    return _postExecution('student/trainings/$sessionId/start');
  }

  /// Conclui o treino com métricas em `POST /student/trainings/{id}/complete`.
  Future<TrainingExecution> completeTraining(
      int sessionId, ExecutionData data) {
    return _postExecution('student/trainings/$sessionId/complete', data);
  }

  /// Registro manual de métricas (mesmo ciclo do complete).
  Future<TrainingExecution> saveExecution(
      int sessionId, ExecutionData data) {
    return _postExecution('student/trainings/$sessionId/execution', data);
  }

  /// Marca a sessão como pulada em `POST /student/trainings/{id}/skip`.
  Future<TrainingSession> skipTraining(int sessionId) async {
    final json = await _post('student/trainings/$sessionId/skip');
    return TrainingSession.fromJson(json['data'] as Map<String, dynamic>);
  }

  /// Histórico de avaliações em `GET /student/progress`.
  Future<PagedResult<StudentProgress>> progress({int page = 1}) {
    return _paged(
      'student/progress',
      {'page': page},
      StudentProgress.fromJson,
    );
  }

  /// Pagamentos em `GET /student/payments`.
  Future<PagedResult<Payment>> payments({int page = 1, String? status}) {
    return _paged(
      'student/payments',
      {'page': page, 'status': ?status},
      Payment.fromJson,
    );
  }

  /// Notificações em `GET /student/notifications`.
  Future<PagedResult<AppNotification>> notifications({int page = 1}) {
    return _paged(
      'student/notifications',
      {'page': page},
      AppNotification.fromJson,
    );
  }

  /// Marca uma notificação como lida
  /// (`POST /student/notifications/{id}/read`).
  Future<AppNotification> markNotificationRead(String id) async {
    final json = await _post('student/notifications/$id/read');

    return AppNotification.fromJson(json['data'] as Map<String, dynamic>);
  }

  /// Marca todas as notificações como lidas
  /// (`POST /student/notifications/read-all`). Devolve quantas foram marcadas.
  Future<int> markAllNotificationsRead() async {
    final json = await _post('student/notifications/read-all');
    final data = json['data'];

    return data is Map<String, dynamic>
        ? (data['marked'] as num?)?.toInt() ?? 0
        : 0;
  }

  /// Gera o Pix da cobrança online em
  /// `POST /student/payments/{id}/checkout`.
  ///
  /// [cpf] só é necessário quando o aluno ainda não tem CPF no cadastro
  /// (a API responde 422 em `errors.cpf` nesse caso).
  Future<PaymentCheckout> checkoutPayment(int paymentId, {String? cpf}) async {
    final digits = cpf?.replaceAll(RegExp(r'\D'), '');
    final json = await _post(
      'student/payments/$paymentId/checkout',
      digits == null || digits.isEmpty ? null : {'cpf': digits},
    );

    return PaymentCheckout.fromJson(json);
  }

  Future<PagedResult<T>> _paged<T>(
    String path,
    Map<String, dynamic> query,
    T Function(Map<String, dynamic>) fromJson,
  ) async {
    try {
      final response = await _client.dio.get(path, queryParameters: query);
      return PagedResult.fromJson(
        response.data as Map<String, dynamic>,
        fromJson,
      );
    } on DioException catch (error) {
      throw ApiException.fromDio(error);
    }
  }

  Future<Map<String, dynamic>> _post(String path,
      [Map<String, dynamic>? data]) async {
    try {
      final response = await _client.dio.post(path, data: data);
      final body = response.data;
      return body is Map<String, dynamic> ? body : {'data': body};
    } on DioException catch (error) {
      throw ApiException.fromDio(error);
    }
  }

  Future<TrainingExecution> _postExecution(String path,
      [ExecutionData? data]) async {
    final json = await _post(path, data?.toMap());
    return TrainingExecution.fromJson(json['data'] as Map<String, dynamic>);
  }
}
