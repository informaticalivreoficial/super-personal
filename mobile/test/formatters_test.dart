import 'package:flutter_test/flutter_test.dart';
import 'package:super_personal/features/student/cpf_input_formatter.dart';
import 'package:super_personal/features/student/formatters.dart';

void main() {
  group('datas', () {
    test('formatDate e isoDate', () {
      final date = DateTime(1990, 5, 10);

      expect(formatDate(date), '10/05/1990');
      expect(isoDate(date), '1990-05-10');
    });

    test('ageInYears conta anos completos', () {
      final birth = DateTime(1990, 5, 10);

      expect(ageInYears(birth, on: DateTime(2026, 5, 9)), 35);
      expect(ageInYears(birth, on: DateTime(2026, 5, 10)), 36);
      expect(ageInYears(birth, on: DateTime(2027, 5, 9)), 36);
    });

    test('ageInYears marca a virada dos 18', () {
      final birth = DateTime(2008, 10, 16);

      expect(ageInYears(birth, on: DateTime(2026, 10, 15)), 17);
      expect(ageInYears(birth, on: DateTime(2026, 10, 16)), 18);
    });
  });

  group('números em pt-BR', () {
    test('formatDecimal usa vírgula', () {
      expect(formatDecimal(78.5), '78,5');
      expect(formatDecimal(12), '12,0');
      expect(formatDecimal(78.54, decimals: 2), '78,54');
    });

    test('formatDistance alterna metro e quilômetro', () {
      expect(formatDistance(800), '800 m');
      expect(formatDistance(8000), '8,0 km');
      expect(formatDistance(12345), '12,3 km');
    });
  });

  group('CPF', () {
    test('formatCpf aplica a máscara e corta em 11 dígitos', () {
      expect(formatCpf('12345678900'), '123.456.789-00');
      expect(formatCpf('123.456.789-00'), '123.456.789-00');
      expect(formatCpf('1234'), '123.4');
      expect(formatCpf('12345678900123'), '123.456.789-00');
      expect(formatCpf(null), '');
      expect(formatCpf('abc'), '');
    });

    test('cpfDigits devolve só os números (payload da API)', () {
      expect(cpfDigits('123.456.789-00'), '12345678900');
      expect(cpfDigits(''), '');
    });

    test('CpfInputFormatter formata enquanto digita', () {
      const formatter = CpfInputFormatter();

      TextEditingValue type(String text) => formatter.formatEditUpdate(
            TextEditingValue.empty,
            TextEditingValue(text: text),
          );

      expect(type('123').text, '123');
      expect(type('1234').text, '123.4');
      expect(type('1234567').text, '123.456.7');
      expect(type('1234567890').text, '123.456.789-0');
      expect(type('123456789001').text, '123.456.789-00');
      // O cursor acompanha o fim do texto (máscara simples).
      expect(type('1234').selection.baseOffset, 5);
    });
  });
}
