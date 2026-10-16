import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:go_router/go_router.dart';
import 'package:super_personal/core/router/app_router.dart';
import 'package:super_personal/core/theme/app_theme.dart';
import 'package:super_personal/features/auth/auth_controller.dart';
import 'package:super_personal/features/student/student_providers.dart';

import 'support/fake_auth.dart';
import 'support/fake_student_repository.dart';

/// Simula o botão/gesto "voltar" do Android (o `popRoute` que o sistema envia
/// pelo canal `flutter/navigation`).
Future<void> systemBack(WidgetTester tester) async {
  await TestDefaultBinaryMessengerBinding.instance.defaultBinaryMessenger
      .handlePlatformMessage(
    'flutter/navigation',
    const JSONMethodCodec().encodeMethodCall(const MethodCall('popRoute')),
    (_) {},
  );
  await tester.pumpAndSettle();
}

/// Regressão do bug "não tem opção de voltar e o voltar do Android sai do
/// app": as telas do aluno são filhas da home no roteador, então elas sempre
/// empilham sobre a home (a AppBar mostra o voltar) e o voltar do Android
/// retorna tela a tela em vez de fechar o app.
void main() {
  group('Pilha de navegação', () {
    late ProviderContainer container;
    late GoRouter router;

    // Localização do topo da pilha (`currentConfiguration.uri` é a base).
    String path() => router
        .routerDelegate.currentConfiguration.matches.last.matchedLocation;

    Future<void> boot(WidgetTester tester) async {
      container = ProviderContainer(
        overrides: [
          authControllerProvider.overrideWith(FakeAuthController.new),
          studentRepositoryProvider.overrideWith(
            (ref) => FakeStudentRepository(),
          ),
        ],
      );
      addTearDown(container.dispose);

      router = container.read(routerProvider);

      await tester.pumpWidget(
        UncontrolledProviderScope(
          container: container,
          child: MaterialApp.router(
            theme: AppTheme.build(),
            routerConfig: router,
          ),
        ),
      );
      await tester.pumpAndSettle();
    }

    testWidgets('home → Meu painel → Ver cobranças volta tela a tela',
        (tester) async {
      await boot(tester);
      expect(path(), '/');
      expect(find.text('Olá, Renato!'), findsOneWidget);

      // home → Meu painel (atalho da home)
      await tester.tap(find.text('Meu painel'));
      await tester.pumpAndSettle();
      expect(path(), '/painel');
      // A home fica na pilha: a AppBar ganha o botão de voltar.
      expect(find.byType(BackButton), findsOneWidget);

      // Meu painel → Ver cobranças (card de pendência)
      await tester.tap(find.text('Ver cobranças'));
      await tester.pumpAndSettle();
      expect(path(), '/pagamentos');
      expect(find.byType(BackButton), findsOneWidget);

      // Voltar do Android: cobranças → painel (antes saía do app)
      await systemBack(tester);
      expect(path(), '/painel');

      // Voltar de novo: painel → home
      await systemBack(tester);
      expect(path(), '/');
      expect(router.canPop(), isFalse);
    });

    testWidgets('atalho direto da home também deixa a home na pilha',
        (tester) async {
      await boot(tester);

      await tester.ensureVisible(find.text('Progresso'));
      await tester.pumpAndSettle();
      await tester.tap(find.text('Progresso'));
      await tester.pumpAndSettle();
      expect(path(), '/progresso');
      expect(find.byType(BackButton), findsOneWidget);

      await systemBack(tester);
      expect(path(), '/');
    });

    testWidgets('login deslogado continua fora da pilha da home',
        (tester) async {
      container = ProviderContainer(
        overrides: [
          authControllerProvider.overrideWith(LoggedOutAuthController.new),
          studentRepositoryProvider.overrideWith(
            (ref) => FakeStudentRepository(),
          ),
        ],
      );
      addTearDown(container.dispose);

      router = container.read(routerProvider);

      await tester.pumpWidget(
        UncontrolledProviderScope(
          container: container,
          child: MaterialApp.router(
            theme: AppTheme.build(),
            routerConfig: router,
          ),
        ),
      );
      await tester.pumpAndSettle();

      expect(path(), '/login');
      expect(find.text('Entrar'), findsOneWidget);
      expect(find.byType(BackButton), findsNothing);
    });
  });
}
