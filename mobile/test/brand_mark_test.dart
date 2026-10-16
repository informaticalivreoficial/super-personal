import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:super_personal/core/theme/app_theme.dart';
import 'package:super_personal/core/widgets/brand_mark.dart';

Widget _boot(Widget child) =>
    MaterialApp(theme: AppTheme.build(), home: Scaffold(body: child));

void main() {
  group('BrandMark', () {
    testWidgets('com a logomarca mostra a arte e não o nome', (tester) async {
      await tester.pumpWidget(_boot(const BrandMark()));
      await tester.pumpAndSettle();

      expect(tester.takeException(), isNull);
      expect(find.byType(Image), findsOneWidget);
      expect(find.text(BrandMark.productName), findsNothing);
    });

    testWidgets('sem logomarca cai no ícone + nome do produto',
        (tester) async {
      await tester.pumpWidget(
        _boot(const BrandMark(asset: 'assets/nao-existe.png')),
      );
      await tester.pumpAndSettle();

      // O erro do asset é tratado pelo próprio widget (não vaza como exceção).
      expect(tester.takeException(), isNull);
      expect(find.text(BrandMark.productName), findsOneWidget);
      expect(find.byIcon(Icons.fitness_center_rounded), findsOneWidget);
    });
  });
}
