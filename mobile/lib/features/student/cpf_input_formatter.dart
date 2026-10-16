import 'package:flutter/services.dart';

/// Máscara de CPF (`###.###.###-##`) para os campos do app.
class CpfInputFormatter extends TextInputFormatter {
  const CpfInputFormatter();

  @override
  TextEditingValue formatEditUpdate(
    TextEditingValue oldValue,
    TextEditingValue newValue,
  ) {
    final text = formatCpf(newValue.text);

    return TextEditingValue(
      text: text,
      // O cursor acompanha o fim do texto (máscara simples, sem edição no meio).
      selection: TextSelection.collapsed(offset: text.length),
    );
  }
}

/// Só os dígitos (máximo 11), no formato `###.###.###-##`.
String formatCpf(String? value) {
  final digits = (value ?? '').replaceAll(RegExp(r'\D'), '');
  final limited = digits.length > 11 ? digits.substring(0, 11) : digits;
  final buffer = StringBuffer();

  for (var i = 0; i < limited.length; i++) {
    if (i == 3 || i == 6) buffer.write('.');
    if (i == 9) buffer.write('-');
    buffer.write(limited[i]);
  }

  return buffer.toString();
}

/// Só os dígitos de um CPF (`123.456.789-00` → `12345678900`).
String cpfDigits(String? value) =>
    (value ?? '').replaceAll(RegExp(r'\D'), '');
