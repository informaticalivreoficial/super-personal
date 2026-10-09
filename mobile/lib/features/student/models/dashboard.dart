/// Resumo do aluno em `GET /student/dashboard` (`DashboardService::studentDashboard`).
class StudentDashboard {
  const StudentDashboard({
    required this.student,
    required this.trainings,
    required this.activePlans,
    required this.payments,
    required this.unreadNotifications,
  });

  final DashboardStudent student;
  final DashboardTrainings trainings;
  final int activePlans;
  final DashboardPayments payments;
  final int unreadNotifications;

  factory StudentDashboard.fromJson(Map<String, dynamic> json) =>
      StudentDashboard(
        student: DashboardStudent.fromJson(
          json['student'] as Map<String, dynamic>? ?? const {},
        ),
        trainings: DashboardTrainings.fromJson(
          json['trainings'] as Map<String, dynamic>? ?? const {},
        ),
        activePlans: (json['active_plans'] as num?)?.toInt() ?? 0,
        payments: DashboardPayments.fromJson(
          json['payments'] as Map<String, dynamic>? ?? const {},
        ),
        unreadNotifications:
            (json['unread_notifications'] as num?)?.toInt() ?? 0,
      );
}

/// Recorte do aluno no dashboard (id, nome e objetivo).
class DashboardStudent {
  const DashboardStudent({required this.id, required this.name, this.goal});

  final int id;
  final String name;
  final String? goal;

  factory DashboardStudent.fromJson(Map<String, dynamic> json) =>
      DashboardStudent(
        id: (json['id'] as num?)?.toInt() ?? 0,
        name: json['name'] as String? ?? '',
        goal: json['goal'] as String?,
      );
}

/// Contagens de treinos do dashboard.
class DashboardTrainings {
  const DashboardTrainings({
    required this.today,
    required this.next,
    required this.completedThisMonth,
  });

  final int today;
  final int next;
  final int completedThisMonth;

  factory DashboardTrainings.fromJson(Map<String, dynamic> json) =>
      DashboardTrainings(
        today: (json['today'] as num?)?.toInt() ?? 0,
        next: (json['next'] as num?)?.toInt() ?? 0,
        completedThisMonth: (json['completed_this_month'] as num?)?.toInt() ?? 0,
      );
}

/// Pendências financeiras do aluno no dashboard.
class DashboardPayments {
  const DashboardPayments({required this.pendingCount, required this.pendingAmount});

  final int pendingCount;
  final double pendingAmount;

  factory DashboardPayments.fromJson(Map<String, dynamic> json) =>
      DashboardPayments(
        pendingCount: (json['pending_count'] as num?)?.toInt() ?? 0,
        pendingAmount: (json['pending_amount'] as num?)?.toDouble() ?? 0,
      );
}
