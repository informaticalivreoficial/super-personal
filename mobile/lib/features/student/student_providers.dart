import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../core/providers.dart';
import 'models/dashboard.dart';
import 'models/notification.dart';
import 'models/payment.dart';
import 'models/progress.dart';
import 'models/training_plan.dart';
import 'models/training_session.dart';
import 'student_repository.dart';

/// Repositório do aluno autenticado.
final studentRepositoryProvider = Provider<StudentRepository>(
  (ref) => StudentRepository(ref.watch(apiClientProvider)),
);

/// Base de uma página paginada com "carregar mais".
///
/// Mantém os itens já carregados e o controle de página; [refresh] recomeça.
abstract class PagedController<T> extends AsyncNotifier<List<T>> {
  PagedController({required this.pageSize});

  /// Tamanho de página pedido à API.
  final int pageSize;

  /// Carrega uma página do servidor (páginas começam em 1).
  Future<PagedResult<T>> fetchPage(int page);

  int _page = 0;
  bool _hasMore = true;

  bool get hasMore => _hasMore;

  @override
  Future<List<T>> build() async {
    _page = 0;
    _hasMore = true;
    final result = await fetchPage(1);
    _track(result);
    return result.items;
  }

  /// Carrega a próxima página (no-op quando acabou ou já em andamento).
  Future<void> loadMore() async {
    if (!_hasMore || state.isLoading) return;
    final current = List<T>.of(state.value ?? const []);
    state = const AsyncValue.loading();
    state = await AsyncValue.guard(() async {
      final result = await fetchPage(_page + 1);
      _track(result);
      return [...current, ...result.items];
    });
  }

  /// Recomeça do zero (pull-to-refresh).
  Future<void> refresh() async {
    state = const AsyncValue.loading();
    state = await AsyncValue.guard(() async {
      _page = 0;
      _hasMore = true;
      final result = await fetchPage(1);
      _track(result);
      return result.items;
    });
  }

  void _track(PagedResult<T> result) {
    _page = result.currentPage;
    _hasMore = result.hasMore;
  }
}

/// Treinos do aluno (lista paginada, mais recentes primeiro).
///
/// [status] filtra por status da sessão (`planned`, `completed`, `skipped`).
class TrainingsController extends PagedController<TrainingSession> {
  TrainingsController(this.status) : super(pageSize: 15);

  final String? status;

  @override
  Future<PagedResult<TrainingSession>> fetchPage(int page) => ref
      .read(studentRepositoryProvider)
      .trainings(page: page, status: status);
}

final trainingsProvider = AsyncNotifierProvider.family<
    TrainingsController, List<TrainingSession>, String?>(
  TrainingsController.new,
);

/// Avaliações de progresso (paginado).
class ProgressController extends PagedController<StudentProgress> {
  ProgressController() : super(pageSize: 15);

  @override
  Future<PagedResult<StudentProgress>> fetchPage(int page) =>
      ref.read(studentRepositoryProvider).progress(page: page);
}

final progressProvider =
    AsyncNotifierProvider<ProgressController, List<StudentProgress>>(
  ProgressController.new,
);

/// Pagamentos (paginado).
class PaymentsController extends PagedController<Payment> {
  PaymentsController() : super(pageSize: 15);

  @override
  Future<PagedResult<Payment>> fetchPage(int page) =>
      ref.read(studentRepositoryProvider).payments(page: page);
}

final paymentsProvider =
    AsyncNotifierProvider<PaymentsController, List<Payment>>(
  PaymentsController.new,
);

/// Notificações (paginado).
class NotificationsController extends PagedController<AppNotification> {
  NotificationsController() : super(pageSize: 15);

  @override
  Future<PagedResult<AppNotification>> fetchPage(int page) =>
      ref.read(studentRepositoryProvider).notifications(page: page);
}

final notificationsProvider =
    AsyncNotifierProvider<NotificationsController, List<AppNotification>>(
  NotificationsController.new,
);

/// Resumo do dashboard (refresh manual via [refresh]).
final dashboardProvider = AsyncNotifierProvider<DashboardController,
    StudentDashboard>(DashboardController.new);

class DashboardController extends AsyncNotifier<StudentDashboard> {
  @override
  Future<StudentDashboard> build() =>
      ref.read(studentRepositoryProvider).dashboard();

  Future<void> refresh() async {
    state = const AsyncValue.loading();
    state = await AsyncValue.guard(
      () => ref.read(studentRepositoryProvider).dashboard(),
    );
  }
}

/// Planos ativos do aluno (lista simples, sem paginação).
final trainingPlansProvider = AsyncNotifierProvider<TrainingPlansController,
    List<TrainingPlan>>(TrainingPlansController.new);

class TrainingPlansController extends AsyncNotifier<List<TrainingPlan>> {
  @override
  Future<List<TrainingPlan>> build() =>
      ref.read(studentRepositoryProvider).trainingPlans();

  /// Plano com semanas/sessões/itens.
  Future<TrainingPlan> detail(int planId) =>
      ref.read(studentRepositoryProvider).trainingPlan(planId);
}

/// Calendário do mês corrente (refresh manual).
final calendarProvider =
    AsyncNotifierProvider<CalendarController, List<TrainingSession>>(
  CalendarController.new,
);

class CalendarController extends AsyncNotifier<List<TrainingSession>> {
  @override
  Future<List<TrainingSession>> build() =>
      ref.read(studentRepositoryProvider).calendar();

  Future<void> refresh() async {
    state = const AsyncValue.loading();
    state = await AsyncValue.guard(
      () => ref.read(studentRepositoryProvider).calendar(),
    );
  }
}
