import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:super_personal/core/theme/app_theme.dart';
import 'package:super_personal/core/widgets/stat_card.dart';
import 'package:super_personal/features/auth/auth_controller.dart';
import 'package:super_personal/features/home/home_screen.dart';
import 'package:super_personal/features/student/dashboard_screen.dart';
import 'package:super_personal/features/student/student_providers.dart';

import 'support/fake_auth.dart';
import 'support/fake_student_repository.dart';

Widget _boot(Widget screen) => ProviderScope(
      overrides: [
        authControllerProvider.overrideWith(FakeAuthController.new),
        studentRepositoryProvider.overrideWith((ref) => FakeStudentRepository()),
      ],
      child: MaterialApp(theme: AppTheme.build(), home: screen),
    );

void main() {
  group('Layout das telas do aluno', () {
    testWidgets('Home: grade de atalhos dentro do ListView (altura livre)',
        (tester) async {
      await tester.pumpWidget(_boot(const HomeScreen()));
      await tester.pumpAndSettle();

      // Regressão: `Row(crossAxisAlignment: stretch)` em altura ilimitada
      // quebrava o layout e a tela ficava em branco no aparelho.
      expect(
        tester.takeException(),
        isNull,
        reason: 'a grade de atalhos não pode quebrar o layout',
      );

      expect(find.text('Olá, Renato!'), findsOneWidget);
      expect(find.text('Acesso rápido'), findsOneWidget);
      expect(find.text('Meu painel'), findsOneWidget);
      expect(find.text('Pagamentos'), findsOneWidget);
      expect(find.text('Você tem 2 cobranças em aberto'), findsOneWidget);
    });

    testWidgets('Meu painel: cards da mesma linha com a mesma altura',
        (tester) async {
      await tester.pumpWidget(_boot(const StudentDashboardScreen()));
      await tester.pumpAndSettle();

      expect(tester.takeException(), isNull);
      expect(find.text('Hoje'), findsOneWidget);
      expect(find.text('Concluídos no mês'), findsOneWidget);

      Size sizeOf(String label) => tester.getSize(
            find.byWidgetPredicate((w) => w is StatCard && w.label == label),
          );

      // A intenção visual do `stretch` continua valendo após o conserto.
      expect(sizeOf('Hoje').height, sizeOf('Concluídos no mês').height);
      expect(sizeOf('Planos ativos').height, sizeOf('Avisos').height);
    });
  });
}
