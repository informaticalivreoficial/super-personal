import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:super_personal/core/api/api_exception.dart';
import 'package:super_personal/core/polling/live_refresh.dart';
import 'package:super_personal/features/student/student_providers.dart';

import 'support/fake_student_repository.dart';

/// Simula a troca de ciclo de vida do app (mesma via dos testes do framework).
Future<void> setLifecycle(AppLifecycleState state) async {
  final ByteData? message = const StringCodec().encodeMessage(state.toString());

  await TestDefaultBinaryMessengerBinding.instance.defaultBinaryMessenger
      .handlePlatformMessage('flutter/lifecycle', message, (_) {});
}

Widget _host(Widget child) =>
    ProviderScope(child: MaterialApp(home: Scaffold(body: child)));

/// Container com o repositório fake (sem rede).
ProviderContainer containerWith(FakeStudentRepository repository) {
  final container = ProviderContainer(
    overrides: [
      studentRepositoryProvider.overrideWith((ref) => repository),
    ],
  );
  addTearDown(container.dispose);

  return container;
}

void main() {
  group('LiveRefresh (polling)', () {
    testWidgets('atualiza a cada intervalo, sem buscar antes do primeiro',
        (tester) async {
      var ticks = 0;

      await tester.pumpWidget(_host(LiveRefresh(
        interval: const Duration(seconds: 10),
        onTick: (_) async => ticks++,
        child: const SizedBox(),
      )));

      expect(ticks, 0, reason: 'não busca nada na abertura (a tela já carrega)');

      await tester.pump(const Duration(seconds: 10));
      expect(ticks, 1);

      await tester.pump(const Duration(seconds: 20));
      expect(ticks, 3);
    });

    testWidgets('não roda quando desabilitado (sem sessão)', (tester) async {
      var ticks = 0;

      await tester.pumpWidget(_host(LiveRefresh(
        enabled: false,
        interval: const Duration(seconds: 10),
        onTick: (_) async => ticks++,
        child: const SizedBox(),
      )));

      await tester.pump(const Duration(seconds: 30));
      expect(ticks, 0);
    });

    testWidgets('começa a rodar quando habilita depois (login)', (tester) async {
      var ticks = 0;

      Widget tree(bool enabled) => _host(LiveRefresh(
            enabled: enabled,
            interval: const Duration(seconds: 10),
            onTick: (_) async => ticks++,
            child: const SizedBox(),
          ));

      await tester.pumpWidget(tree(false));
      await tester.pump(const Duration(seconds: 20));
      expect(ticks, 0);

      await tester.pumpWidget(tree(true));
      await tester.pump(const Duration(seconds: 10));
      expect(ticks, 1);
    });

    testWidgets('para no segundo plano e atualiza na volta', (tester) async {
      var ticks = 0;

      await tester.pumpWidget(_host(LiveRefresh(
        interval: const Duration(seconds: 10),
        onTick: (_) async => ticks++,
        child: const SizedBox(),
      )));

      await tester.pump(const Duration(seconds: 10));
      expect(ticks, 1);

      // App em segundo plano: nenhum ciclo roda.
      await setLifecycle(AppLifecycleState.inactive);
      await setLifecycle(AppLifecycleState.hidden);
      await setLifecycle(AppLifecycleState.paused);
      await tester.pump(const Duration(seconds: 30));
      expect(ticks, 1, reason: 'não gasta bateria com o app em segundo plano');

      // Voltou para a frente: atualiza na hora e retoma o ciclo.
      await setLifecycle(AppLifecycleState.hidden);
      await setLifecycle(AppLifecycleState.inactive);
      await setLifecycle(AppLifecycleState.resumed);
      await tester.pump();
      expect(ticks, 2, reason: 'ao voltar, o dado costuma estar velho');

      await tester.pump(const Duration(seconds: 10));
      expect(ticks, 3);
    });

    testWidgets('falha de um ciclo não interrompe os próximos', (tester) async {
      var ticks = 0;

      await tester.pumpWidget(_host(LiveRefresh(
        interval: const Duration(seconds: 10),
        onTick: (_) async {
          ticks++;
          throw ApiException('Sem conexão com o servidor.');
        },
        child: const SizedBox(),
      )));

      await tester.pump(const Duration(seconds: 10));
      expect(ticks, 1);
      expect(tester.takeException(), isNull, reason: 'polling não derruba a tela');

      await tester.pump(const Duration(seconds: 10));
      expect(ticks, 2);
    });
  });

  group('DashboardController.refreshSilently', () {
    test('troca os números sem passar por carregando', () async {
      final repository = FakeStudentRepository();
      final container = containerWith(repository);

      await container.read(dashboardProvider.future);

      var sawLoading = false;
      container.listen(dashboardProvider, (previous, next) {
        if (next.isLoading) sawLoading = true;
      });

      repository.nextDashboard = dashboardWith(today: 5, unreadNotifications: 9);
      await container.read(dashboardProvider.notifier).refreshSilently();

      final state = container.read(dashboardProvider);
      expect(state.value!.trainings.today, 5);
      expect(state.value!.unreadNotifications, 9);
      expect(sawLoading, isFalse, reason: 'nada de skeleton piscando');
    });

    test('sem rede mantém os dados que estão na tela', () async {
      final repository = FakeStudentRepository(
        dashboard: dashboardWith(today: 2),
      );
      final container = containerWith(repository);

      await container.read(dashboardProvider.future);

      repository.failDashboard = true;
      await container.read(dashboardProvider.notifier).refreshSilently();

      final state = container.read(dashboardProvider);
      expect(state.hasError, isFalse);
      expect(state.value!.trainings.today, 2);
    });
  });

  group('NotificationsController.refreshSilently', () {
    Future<List<int>> idsOf(ProviderContainer container) async =>
        (await container.read(notificationsProvider.future))
            .map((item) => item.id)
            .toList();

    test('acrescenta no topo só o que chegou depois', () async {
      final repository = FakeStudentRepository(
        notifications: [notificationFixture(2), notificationFixture(1)],
      );
      final container = containerWith(repository);

      expect(await idsOf(container), [2, 1]);

      repository.nextNotifications = [
        notificationFixture(3),
        notificationFixture(2),
        notificationFixture(1),
      ];

      await container.read(notificationsProvider.notifier).refreshSilently();

      expect(await idsOf(container), [3, 2, 1]);
    });

    test('não duplica quando nada é novo', () async {
      final repository = FakeStudentRepository(
        notifications: [notificationFixture(2), notificationFixture(1)],
      );
      final container = containerWith(repository);

      await idsOf(container);
      await container.read(notificationsProvider.notifier).refreshSilently();

      expect(await idsOf(container), [2, 1]);
    });

    test('sem rede mantém a lista que está na tela', () async {
      final repository = FakeStudentRepository(
        notifications: [notificationFixture(1)],
      );
      final container = containerWith(repository);

      await idsOf(container);

      repository.failNotifications = true;
      await container.read(notificationsProvider.notifier).refreshSilently();

      final state = container.read(notificationsProvider);
      expect(state.hasError, isFalse);
      expect(await idsOf(container), [1]);
    });
  });
}
