import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../core/providers.dart';
import '../auth/auth_controller.dart';
import 'models/dashboard.dart';
import 'models/notification.dart';
import 'models/payment.dart';
import 'models/progress.dart';
import 'models/student_profile.dart';
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

  /// Recarrega a **primeira página** sem passar por `loading` e acrescenta no
  /// topo só o que chegou depois (polling com a tela aberta e, no futuro, push).
  ///
  /// - não mexe em `_page`/`_hasMore`: a lista já carregada continua válida e o
  ///   "carregar mais" segue de onde parou;
  /// - sem lista na tela não faz nada (quem carrega é a própria tela);
  /// - falha de rede mantém o que está na tela.
  Future<void> refreshSilently() async {
    final current = state.value;

    if (current == null) {
      return;
    }

    try {
      final page = await fetchPage(1);
      final known = current.map((item) => item.id).toSet();
      final newItems =
          page.items.where((item) => !known.contains(item.id)).toList();

      if (newItems.isEmpty || !ref.mounted) {
        return;
      }

      state = AsyncValue.data([...newItems, ...current]);
    } catch (_) {
      // Sem rede: o próximo ciclo tenta de novo.
    }
  }
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

  /// Atualiza o resumo **sem** passar por `loading` (polling com o app aberto):
  /// mantém o que está na tela — nada de skeleton piscando a cada ciclo — e
  /// ignora falha de rede.
  Future<void> refreshSilently() async {
    try {
      final data = await ref.read(studentRepositoryProvider).dashboard();

      if (ref.mounted) {
        state = AsyncValue.data(data);
      }
    } catch (_) {
      // Sem rede: o próximo ciclo tenta de novo.
    }
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

/// Perfil do aluno (carrega em `build`; [save] atualiza e retorna o novo estado).
final profileProvider =
    AsyncNotifierProvider<ProfileController, StudentProfile>(
  ProfileController.new,
);

class ProfileController extends AsyncNotifier<StudentProfile> {
  @override
  Future<StudentProfile> build() =>
      ref.read(studentRepositoryProvider).profile();

  Future<StudentProfile> save(ProfileUpdateData data) async {
    final updated =
        await ref.read(studentRepositoryProvider).updateProfile(data);
    state = AsyncValue.data(updated);
    // Nome do aluno também vive na conta (`users.name`): a home lê a sessão.
    await ref.read(authControllerProvider.notifier).reload();

    return updated;
  }

  /// Envia a foto (caminho local de arquivo) e atualiza o estado.
  Future<StudentProfile> uploadPhoto(String filePath) async {
    final updated =
        await ref.read(studentRepositoryProvider).uploadProfilePhoto(filePath);
    state = AsyncValue.data(updated);
    return updated;
  }

  /// Remove a foto e atualiza o estado.
  Future<StudentProfile> removePhoto() async {
    final updated =
        await ref.read(studentRepositoryProvider).removeProfilePhoto();
    state = AsyncValue.data(updated);
    return updated;
  }

  /// Pede a troca do e-mail de acesso (código vai para o endereço novo).
  Future<String> requestEmailChange(String email) =>
      ref.read(studentRepositoryProvider).requestEmailChange(email);

  /// Confirma o código, efetiva o e-mail e recarrega a sessão (a home mostra
  /// o endereço novo).
  Future<void> confirmEmailChange(String code) async {
    await ref.read(studentRepositoryProvider).confirmEmailChange(code);
    await ref.read(authControllerProvider.notifier).reload();
  }

  /// Desiste da troca de e-mail em andamento.
  Future<void> cancelEmailChange() async {
    await ref.read(studentRepositoryProvider).cancelEmailChange();
    await ref.read(authControllerProvider.notifier).reload();
  }

  /// Troca a senha (exige a atual).
  Future<void> changePassword({
    required String currentPassword,
    required String password,
    required String confirmation,
  }) =>
      ref.read(studentRepositoryProvider).changePassword(
            currentPassword: currentPassword,
            password: password,
            confirmation: confirmation,
          );
}
