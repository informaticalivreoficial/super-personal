import 'package:super_personal/core/api/api_client.dart';
import 'package:super_personal/core/api/api_exception.dart';
import 'package:super_personal/core/storage/token_storage.dart';
import 'package:super_personal/features/student/models/dashboard.dart';
import 'package:super_personal/features/student/models/notification.dart';
import 'package:super_personal/features/student/models/payment.dart';
import 'package:super_personal/features/student/models/progress.dart';
import 'package:super_personal/features/student/models/session_item.dart';
import 'package:super_personal/features/student/models/sport.dart';
import 'package:super_personal/features/student/models/student_profile.dart';
import 'package:super_personal/features/student/models/training_plan.dart';
import 'package:super_personal/features/student/models/training_session.dart';
import 'package:super_personal/features/student/student_repository.dart';

/// Resumo de exemplo: números pequenos e uma cobrança em aberto.
const dashboardFixture = StudentDashboard(
  student: DashboardStudent(id: 1, name: 'Renato Silva', goal: 'Emagrecer'),
  trainings: DashboardTrainings(today: 1, next: 2, completedThisMonth: 3),
  activePlans: 1,
  payments: DashboardPayments(pendingCount: 2, pendingAmount: 150),
  unreadNotifications: 3,
);

/// Cópia do resumo com os números trocados (para testar atualização).
StudentDashboard dashboardWith({
  int today = 1,
  int completedThisMonth = 3,
  int activePlans = 1,
  int pendingCount = 2,
  int unreadNotifications = 3,
}) =>
    StudentDashboard(
      student: dashboardFixture.student,
      trainings: DashboardTrainings(
        today: today,
        next: dashboardFixture.trainings.next,
        completedThisMonth: completedThisMonth,
      ),
      activePlans: activePlans,
      payments: DashboardPayments(
        pendingCount: pendingCount,
        pendingAmount: dashboardFixture.payments.pendingAmount,
      ),
      unreadNotifications: unreadNotifications,
    );

const profileFixture = StudentProfile(
  id: 1,
  name: 'Renato Silva',
  email: 'aluno@teste.dev',
);

/// Sessão de exemplo: planejada, com composição e recado do treinador.
final trainingFixture = TrainingSession(
  id: 1,
  trainingWeekId: 1,
  sportId: 2,
  title: 'Rodagem leve',
  description: 'Trote contínuo, conversando durante todo o percurso.',
  scheduledDate: DateTime.now(),
  estimatedDuration: 3600,
  distance: 8000,
  intensity: 'Moderada',
  targetPace: '5:30',
  instructions: 'Hidrate-se antes de sair.',
  coachNotes: 'Cuide do ritmo no início para não quebrar no final.',
  status: 'planned',
  statusLabel: 'Planejado',
  sport: const Sport(id: 2, name: 'Corrida'),
  items: const [
    SessionItem(
      id: 1,
      trainingSessionId: 1,
      type: 'warmup',
      typeLabel: 'Aquecimento',
      title: 'Trote leve',
      duration: 600,
      sortOrder: 1,
    ),
    SessionItem(
      id: 2,
      trainingSessionId: 1,
      type: 'work',
      typeLabel: 'Principal',
      title: '8x400m forte',
      repetitions: 8,
      distance: 400,
      target: '4:30/km',
      sortOrder: 2,
    ),
  ],
);

/// Plano de exemplo: 1 semana com a sessão do [trainingFixture].
final planFixture = TrainingPlan(
  id: 1,
  name: 'Plano de outubro',
  goal: 'Emagrecer',
  status: 'active',
  statusLabel: 'Ativo',
  weeks: [
    TrainingWeek(
      id: 1,
      trainingPlanId: 1,
      weekNumber: 1,
      objective: 'Base',
      sessions: [trainingFixture],
    ),
  ],
);

/// Avaliação física de exemplo.
StudentProgress progressFixture({int id = 1, String? notes}) => StudentProgress(
      id: id,
      recordedAt: DateTime(2026, 10, 10),
      weight: 78.5,
      bodyFat: 12,
      restingHeartRate: 58,
      ftp: 240,
      runningPace: '5:10/km',
      notes: notes,
    );

/// Cobrança de exemplo (o canal `online` + [payable] habilita o Pix).
Payment paymentFixture({
  int id = 1,
  String description = 'Mensalidade de outubro',
  double amount = 150,
  String status = 'pending',
  String? statusLabel = 'Pendente',
  String channel = 'manual',
  String? channelLabel,
  bool payable = false,
  DateTime? dueDate,
}) =>
    Payment(
      id: id,
      description: description,
      amount: amount,
      dueDate: dueDate ?? DateTime.now().add(const Duration(days: 5)),
      status: status,
      statusLabel: statusLabel,
      channel: channel,
      channelLabel: channelLabel,
      payable: payable,
    );

/// Notificação de exemplo ([id] define a ordem da lista; é UUID na API).
///
/// [readAt] preenchido = já lida (perde o destaque no app).
AppNotification notificationFixture(
  String id, {
  String title = 'Novo treino',
  DateTime? readAt,
}) =>
    AppNotification(
      id: id,
      data: {'title': title, 'message': 'Mensagem da notificação.'},
      readAt: readAt,
    );

/// Repositório fake (sem rede) com contadores e controles de falha.
class FakeStudentRepository extends StudentRepository {
  FakeStudentRepository({
    StudentDashboard? dashboard,
    StudentProfile? profile,
    List<AppNotification>? notifications,
    List<Payment>? payments,
    TrainingSession? training,
    List<TrainingPlan>? plans,
    List<TrainingSession>? calendar,
    List<StudentProgress>? progress,
  })  : nextDashboard = dashboard ?? dashboardFixture,
        nextProfile = profile ?? profileFixture,
        nextNotifications = notifications ?? const [],
        nextPayments = payments ?? const [],
        nextTraining = training ?? trainingFixture,
        nextPlans = plans ?? const [],
        nextCalendar = calendar ?? const [],
        nextProgress = progress ?? const [],
        super(ApiClient(tokenStorage: InMemoryTokenStorage()));

  /// Próxima resposta de cada chamada (pode ser trocada durante o teste).
  StudentDashboard nextDashboard;
  StudentProfile nextProfile;
  List<AppNotification> nextNotifications;
  List<Payment> nextPayments;
  TrainingSession nextTraining;
  List<TrainingPlan> nextPlans;
  List<TrainingSession> nextCalendar;
  List<StudentProgress> nextProgress;

  /// Simula queda de rede.
  bool failDashboard = false;
  bool failNotifications = false;

  /// Quantas vezes a API foi chamada.
  int dashboardCalls = 0;
  int notificationsCalls = 0;

  /// Quantas notificações foram marcadas como lidas neste fake.
  int markedReadCalls = 0;

  @override
  Future<StudentDashboard> dashboard() async {
    dashboardCalls++;

    if (failDashboard) {
      throw ApiException('Sem conexão com o servidor. Verifique sua internet.');
    }

    return nextDashboard;
  }

  @override
  Future<StudentProfile> profile() async => nextProfile;

  @override
  Future<TrainingSession> training(int sessionId) async => nextTraining;

  @override
  Future<PagedResult<AppNotification>> notifications({int page = 1}) async {
    notificationsCalls++;

    if (failNotifications) {
      throw ApiException('Sem conexão com o servidor. Verifique sua internet.');
    }

    return PagedResult(
      items: nextNotifications,
      currentPage: page,
      lastPage: 1,
    );
  }

  @override
  Future<AppNotification> markNotificationRead(String id) async {
    markedReadCalls++;

    final now = DateTime.now();
    nextNotifications = [
      for (final item in nextNotifications)
        item.id == id ? item.copyWith(readAt: now) : item,
    ];

    return nextNotifications.firstWhere((item) => item.id == id);
  }

  @override
  Future<int> markAllNotificationsRead() async {
    final unread = nextNotifications.where((item) => !item.isRead).toList();
    markedReadCalls += unread.length;

    final now = DateTime.now();
    nextNotifications = [
      for (final item in nextNotifications)
        item.isRead ? item : item.copyWith(readAt: now),
    ];

    return unread.length;
  }

  @override
  Future<PagedResult<Payment>> payments({int page = 1, String? status}) async =>
      PagedResult(items: nextPayments, currentPage: page, lastPage: 1);

  @override
  Future<List<TrainingPlan>> trainingPlans() async => nextPlans;

  @override
  Future<List<TrainingSession>> calendar({String? from, String? to}) async =>
      nextCalendar;

  @override
  Future<PagedResult<StudentProgress>> progress({int page = 1}) async =>
      PagedResult(items: nextProgress, currentPage: page, lastPage: 1);
}
