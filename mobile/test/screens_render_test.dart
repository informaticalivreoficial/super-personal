import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:super_personal/core/theme/app_theme.dart';
import 'package:super_personal/core/widgets/brand_mark.dart';
import 'package:super_personal/features/auth/auth_controller.dart';
import 'package:super_personal/features/auth/login_screen.dart';
import 'package:super_personal/features/student/account_screen.dart';
import 'package:super_personal/features/student/calendar_screen.dart';
import 'package:super_personal/features/student/models/sport.dart';
import 'package:super_personal/features/student/models/training_session.dart';
import 'package:super_personal/features/student/notifications_screen.dart';
import 'package:super_personal/features/student/payments_screen.dart';
import 'package:super_personal/features/student/plans_screen.dart';
import 'package:super_personal/features/student/profile_screen.dart';
import 'package:super_personal/features/student/progress_screen.dart';
import 'package:super_personal/features/student/student_providers.dart';
import 'package:super_personal/features/student/training_detail_screen.dart';

import 'support/fake_auth.dart';
import 'support/fake_student_repository.dart';

Widget _boot(
  Widget screen, {
  FakeStudentRepository? repository,
  AuthController Function()? auth,
}) =>
    ProviderScope(
      overrides: [
        authControllerProvider.overrideWith(auth ?? FakeAuthController.new),
        studentRepositoryProvider.overrideWith(
          (ref) => repository ?? FakeStudentRepository(),
        ),
      ],
      child: MaterialApp(theme: AppTheme.build(), home: screen),
    );

void main() {
  group('Login', () {
    testWidgets('renderiza a marca e o formulário', (tester) async {
      await tester.pumpWidget(_boot(const LoginScreen()));
      await tester.pumpAndSettle();

      expect(tester.takeException(), isNull);
      expect(find.byType(BrandMark), findsOneWidget);
      expect(find.text('Seus treinos na palma da mão'), findsOneWidget);
      // Com a logomarca no bundle, o nome não aparece.
      expect(find.text(BrandMark.productName), findsNothing);
      expect(find.text('E-mail'), findsOneWidget);
      expect(find.text('Senha'), findsOneWidget);
      expect(find.text('Entrar'), findsOneWidget);
      expect(find.text('Ainda não tem acesso?'), findsOneWidget);
    });

    testWidgets('sem e-mail/senha avisa o aluno', (tester) async {
      await tester.pumpWidget(_boot(const LoginScreen()));
      await tester.pumpAndSettle();

      await tester.tap(find.text('Entrar'));
      await tester.pumpAndSettle();

      expect(find.text('Não foi possível entrar'), findsOneWidget);
      expect(find.text('Informe e-mail e senha.'), findsOneWidget);
    });
  });

  group('Notificações', () {
    testWidgets('lista os avisos com título e mensagem', (tester) async {
      final repository = FakeStudentRepository(
        notifications: [
          notificationFixture('2', title: 'Novo treino disponível'),
          notificationFixture('1', title: 'Cobrança perto do vencimento'),
        ],
      );

      await tester.pumpWidget(
        _boot(const NotificationsScreen(), repository: repository),
      );
      await tester.pumpAndSettle();

      expect(tester.takeException(), isNull);
      expect(find.text('Novo treino disponível'), findsOneWidget);
      expect(find.text('Cobrança perto do vencimento'), findsOneWidget);
    });

    testWidgets('sem avisos mostra o estado vazio', (tester) async {
      await tester.pumpWidget(_boot(const NotificationsScreen()));
      await tester.pumpAndSettle();

      expect(find.text('Nenhum aviso por aqui'), findsOneWidget);
    });
  });

  group('Pagamentos', () {
    testWidgets('resume o que está em aberto e lista as cobranças',
        (tester) async {
      final repository = FakeStudentRepository(
        payments: [
          paymentFixture(id: 1, amount: 150),
          paymentFixture(
            id: 2,
            description: 'Avaliação física',
            amount: 50,
            status: 'paid',
            statusLabel: 'Pago',
          ),
        ],
      );

      await tester.pumpWidget(
        _boot(const PaymentsScreen(), repository: repository),
      );
      await tester.pumpAndSettle();

      expect(tester.takeException(), isNull);
      expect(find.text('Mensalidade de outubro'), findsOneWidget);
      expect(find.text('Avaliação física'), findsOneWidget);
      expect(find.text('Em aberto'), findsOneWidget);
      expect(find.text('R\$ 150,00'), findsWidgets);
    });

    testWidgets('cobrança online oferece o Pix; a manual não',
        (tester) async {
      final repository = FakeStudentRepository(
        payments: [
          paymentFixture(
            id: 1,
            description: 'Online',
            channel: 'online',
            channelLabel: 'Pelo app',
            payable: true,
          ),
          paymentFixture(id: 2, description: 'Manual'),
        ],
      );

      await tester.pumpWidget(
        _boot(const PaymentsScreen(), repository: repository),
      );
      await tester.pumpAndSettle();

      expect(find.text('Pagar com Pix'), findsOneWidget);
      expect(find.text('Pelo app'), findsOneWidget);
    });
  });

  group('Planos', () {
    testWidgets('lista o plano e abre as semanas com as sessões',
        (tester) async {
      final repository = FakeStudentRepository(plans: [planFixture]);

      await tester.pumpWidget(
        _boot(const PlansScreen(), repository: repository),
      );
      await tester.pumpAndSettle();

      expect(tester.takeException(), isNull);
      expect(find.text('Plano de outubro'), findsOneWidget);
      expect(find.text('Emagrecer · 1 semana · 1 sessão'), findsOneWidget);

      await tester.tap(find.text('Plano de outubro'));
      await tester.pumpAndSettle();

      expect(find.text('Semana 1'), findsOneWidget);
      expect(find.text('Base · 1 sessão'), findsOneWidget);

      await tester.tap(find.text('Semana 1'));
      await tester.pumpAndSettle();

      expect(find.text('Rodagem leve'), findsOneWidget);
    });

    testWidgets('sem plano ativo mostra o estado vazio', (tester) async {
      await tester.pumpWidget(_boot(const PlansScreen()));
      await tester.pumpAndSettle();

      expect(find.text('Nenhum plano ativo'), findsOneWidget);
    });
  });

  group('Calendário', () {
    testWidgets('agrupa as sessões por dia (hoje e amanhã)', (tester) async {
      final repository = FakeStudentRepository(
        calendar: [
          trainingFixture,
          TrainingSession(
            id: 7,
            trainingWeekId: 1,
            title: 'Pedal longo',
            scheduledDate: DateTime.now().add(const Duration(days: 1)),
            sport: const Sport(id: 4, name: 'Ciclismo'),
          ),
        ],
      );

      await tester.pumpWidget(
        _boot(const CalendarScreen(), repository: repository),
      );
      await tester.pumpAndSettle();

      expect(tester.takeException(), isNull);
      expect(find.text('Hoje'), findsOneWidget);
      expect(find.text('Amanhã'), findsOneWidget);
      expect(find.text('Rodagem leve'), findsOneWidget);
      expect(find.text('Pedal longo'), findsOneWidget);
    });

    testWidgets('mês sem sessões mostra o estado vazio', (tester) async {
      await tester.pumpWidget(_boot(const CalendarScreen()));
      await tester.pumpAndSettle();

      expect(find.text('Nada agendado neste mês'), findsOneWidget);
    });
  });

  group('Progresso', () {
    testWidgets('lista as avaliações com as métricas em pílulas',
        (tester) async {
      final repository = FakeStudentRepository(
        progress: [progressFixture(notes: 'Ganho de força no pedal.')],
      );

      await tester.pumpWidget(
        _boot(const ProgressScreen(), repository: repository),
      );
      await tester.pumpAndSettle();

      expect(tester.takeException(), isNull);
      expect(find.text('Suas avaliações'), findsOneWidget);
      expect(find.text('10/10/2026'), findsOneWidget);
      expect(find.text('Peso 78,5 kg'), findsOneWidget);
      expect(find.text('FC rep. 58 bpm'), findsOneWidget);

      await tester.tap(find.text('10/10/2026'));
      await tester.pumpAndSettle();

      expect(find.text('FC máxima'), findsNothing);
      expect(find.text('Ganho de força no pedal.'), findsOneWidget);
    });

    testWidgets('sem avaliação mostra o estado vazio', (tester) async {
      await tester.pumpWidget(_boot(const ProgressScreen()));
      await tester.pumpAndSettle();

      expect(find.text('Nenhuma avaliação ainda'), findsOneWidget);
    });
  });

  group('Perfil', () {
    testWidgets('renderiza os dados do aluno e o atalho de conta',
        (tester) async {
      await tester.pumpWidget(_boot(const ProfileScreen()));
      await tester.pumpAndSettle();

      expect(tester.takeException(), isNull);
      expect(find.text('Renato Silva'), findsWidgets);
      expect(find.text('Dados pessoais'), findsOneWidget);
      expect(find.text('CPF'), findsOneWidget);
      expect(find.text('Dias disponíveis'), findsOneWidget);
      expect(find.text('Salvar perfil'), findsOneWidget);

      // O atalho de conta fica no fim da rolagem.
      await tester.scrollUntilVisible(
        find.text('E-mail e senha'),
        220,
        scrollable: find.byType(Scrollable).first,
      );
      expect(find.text('E-mail e senha'), findsOneWidget);
    });
  });

  group('Conta e acesso', () {
    testWidgets('mostra o e-mail atual e o formulário de senha',
        (tester) async {
      await tester.pumpWidget(_boot(const AccountScreen()));
      await tester.pumpAndSettle();

      expect(tester.takeException(), isNull);
      expect(find.text('E-mail de acesso'), findsOneWidget);
      expect(find.text('aluno@teste.dev'), findsOneWidget);
      expect(find.text('Alterar e-mail'), findsOneWidget);
      expect(find.text('Senha atual'), findsOneWidget);
      expect(find.text('Salvar nova senha'), findsOneWidget);
    });

    testWidgets('com troca pendente avisa e oferece o código', (tester) async {
      await tester.pumpWidget(
        _boot(const AccountScreen(), auth: PendingEmailAuthController.new),
      );
      await tester.pumpAndSettle();

      expect(
        find.text('Aguardando confirmação de novo@teste.dev'),
        findsOneWidget,
      );
      expect(find.text('Digitar código'), findsOneWidget);
      expect(find.text('Cancelar troca'), findsOneWidget);
    });
  });

  group('Detalhe do treino', () {    testWidgets('mostra composição, recado e ações sem quebrar o layout',
        (tester) async {
      await tester.pumpWidget(_boot(const TrainingDetailScreen(sessionId: 1)));
      await tester.pumpAndSettle();

      expect(tester.takeException(), isNull);
      expect(find.text('Rodagem leve'), findsOneWidget);
      expect(find.text('Recado do seu treinador'), findsOneWidget);
      expect(find.text('Composição do treino'), findsOneWidget);
      expect(find.text('8x400m forte'), findsOneWidget);
      expect(find.text('Iniciar treino'), findsOneWidget);
      expect(find.text('Concluir treino'), findsOneWidget);
      expect(find.text('Pular treino'), findsOneWidget);
    });
  });
}
