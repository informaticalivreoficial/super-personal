/// Pagamento do aluno (`PaymentResource`).
///
/// `amount` vem em reais (float); `due_date` em `aaaa-mm-dd`.
class Payment {
  const Payment({
    required this.id,
    required this.description,
    required this.amount,
    this.dueDate,
    this.paidAt,
    this.status = '',
    this.statusLabel,
    this.paymentMethod,
    this.paymentMethodLabel,
    this.notes,
  });

  final int id;
  final String description;
  final double amount;
  final DateTime? dueDate;
  final DateTime? paidAt;
  final String status;
  final String? statusLabel;
  final String? paymentMethod;
  final String? paymentMethodLabel;
  final String? notes;

  factory Payment.fromJson(Map<String, dynamic> json) => Payment(
        id: (json['id'] as num?)?.toInt() ?? 0,
        description: json['description'] as String? ?? '',
        amount: (json['amount'] as num?)?.toDouble() ?? 0,
        dueDate: DateTime.tryParse(json['due_date'] as String? ?? ''),
        paidAt: DateTime.tryParse(json['paid_at'] as String? ?? ''),
        status: json['status'] as String? ?? '',
        statusLabel: json['status_label'] as String?,
        paymentMethod: json['payment_method'] as String?,
        paymentMethodLabel: json['payment_method_label'] as String?,
        notes: json['notes'] as String?,
      );

  /// Valor formatado em reais: `R$ 1.234,56`.
  String get amountLabel {
    final fixed = amount.toStringAsFixed(2);
    final parts = fixed.split('.');
    final reais = _thousands(parts.first);
    return 'R\$ $reais,${parts[1]}';
  }

  static String _thousands(String digits) {
    final buffer = StringBuffer();
    for (var i = 0; i < digits.length; i++) {
      if (i > 0 && (digits.length - i) % 3 == 0) {
        buffer.write('.');
      }
      buffer.write(digits[i]);
    }
    return buffer.toString();
  }
}
