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
