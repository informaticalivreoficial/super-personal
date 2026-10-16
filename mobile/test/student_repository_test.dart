import 'package:dio/dio.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:super_personal/core/api/api_exception.dart';
import 'package:super_personal/features/student/models/payment.dart';
import 'package:super_personal/features/student/models/student_profile.dart';
import 'package:super_personal/features/student/student_repository.dart';

import 'support/api_test_helpers.dart';

void main() {
  group('StudentRepository.checkoutPayment', () {
    test('faz POST no checkout e devolve o Pix', () async {
      late RequestOptions captured;
      final client = clientWith((options) async {
        captured = options;
        return jsonResponse({
          'status': 'pending',
          'order_id': 'ORDE_1',
          'qr_code': '0002010102122658BR.COM...',
          'qr_image': 'https://gateway.test/qrcode/QRCO_1/png',
        }, 200);
      });

      final checkout =
          await StudentRepository(client).checkoutPayment(7, cpf: '123.456.789-00');

      expect(captured.method, 'POST');
      expect(captured.uri.path, '/api/v1/student/payments/7/checkout');
      // O CPF vai só com dígitos (o gateway exige 11 caracteres).
      expect((captured.data as Map)['cpf'], '12345678900');
      expect(checkout.orderId, 'ORDE_1');
      expect(checkout.qrCode, '0002010102122658BR.COM...');
    });

    test('sem CPF não envia corpo para o gateway', () async {
      late RequestOptions captured;
      final client = clientWith((options) async {
        captured = options;
        return jsonResponse({
          'status': 'pending',
          'order_id': 'ORDE_2',
          'qr_code': 'copia-e-cola',
        }, 200);
      });

      await StudentRepository(client).checkoutPayment(3);

      expect(captured.data, isNull);
    });

    test('422 sem CPF no cadastro vira erro no campo cpf', () async {
      final client = clientWith(
        (_) async => jsonResponse({
          'message': 'Dados inválidos.',
          'errors': {
            'cpf': ['Informe seu CPF para gerar o QR Code do Pix.'],
          },
        }, 422),
      );

      expect(
        () => StudentRepository(client).checkoutPayment(1),
        throwsA(
          isA<ApiException>().having(
            (e) => e.fieldErrors['cpf'],
            'fieldErrors[cpf]',
            'Informe seu CPF para gerar o QR Code do Pix.',
          ),
        ),
      );
    });

    test('falha do gateway (422) vira mensagem para o aluno', () async {
      final client = clientWith(
        (_) async => jsonResponse(
          {'message': 'Não foi possível processar o pagamento (400).'},
          422,
        ),
      );

      expect(
        () => StudentRepository(client).checkoutPayment(1),
        throwsA(
          isA<ApiException>().having(
            (e) => e.message,
            'message',
            'Não foi possível processar o pagamento (400).',
          ),
        ),
      );
    });
  });

  group('StudentRepository.updateProfile', () {
    test('envia CPF e data de nascimento em ISO', () async {
      late RequestOptions captured;
      final client = clientWith((options) async {
        captured = options;
        return jsonResponse({
          'data': {
            'id': 1,
            'name': 'Aluno Teste',
            'email': 'aluno@superpersonal.test',
            'document': '12345678900',
            'birth_date': '1990-05-10',
          },
        }, 200);
      });

      final profile = await StudentRepository(client).updateProfile(
        ProfileUpdateData(
          name: 'Aluno Teste',
          document: '12345678900',
          birthDate: DateTime(1990, 5, 10),
          availableDays: const [1, 3],
        ),
      );

      final body = captured.data as Map;
      expect(captured.method, 'PUT');
      expect(captured.uri.path, '/api/v1/student/profile');
      expect(body['document'], '12345678900');
      // A API valida a maioridade; o app manda sempre `aaaa-mm-dd`.
      expect(body['birth_date'], '1990-05-10');
      expect(profile.document, '12345678900');
      expect(profile.birthDate, DateTime(1990, 5, 10));
    });

    test('sem data de nascimento o campo vai nulo', () async {
      late RequestOptions captured;
      final client = clientWith((options) async {
        captured = options;
        return jsonResponse({
          'data': {'id': 1, 'name': 'Aluno', 'email': 'a@b.c'},
        }, 200);
      });

      await StudentRepository(client).updateProfile(
        const ProfileUpdateData(name: 'Aluno', availableDays: []),
      );

      final body = captured.data as Map;
      expect(body['birth_date'], isNull);
      expect(body['document'], isNull);
    });
  });

  group('Payment.fromJson', () {
    test('lê canal, rótulo e payable', () {
      final payment = Payment.fromJson({
        'id': 5,
        'description': 'Mensalidade',
        'amount': 200,
        'due_date': '2026-10-20',
        'status': 'pending',
        'status_label': 'Pendente',
        'channel': 'online',
        'channel_label': 'Online',
        'payable': true,
      });

      expect(payment.channel, 'online');
      expect(payment.channelLabel, 'Online');
      expect(payment.payable, isTrue);
      expect(payment.amountLabel, 'R\$ 200,00');
    });

    test('default é manual e não pagável no app', () {
      final payment = Payment.fromJson({
        'id': 6,
        'description': 'Mensalidade',
        'amount': 100,
        'status': 'paid',
      });

      expect(payment.channel, 'manual');
      expect(payment.channelLabel, isNull);
      expect(payment.payable, isFalse);
    });
  });
}
