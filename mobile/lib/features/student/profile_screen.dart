import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../core/api/api_exception.dart';
import 'models/student_profile.dart';
import 'student_providers.dart';

/// Dias da semana no formato da API (1 = domingo … 7 = sábado).
final _weekdays = const [
  (value: 1, label: 'D'),
  (value: 2, label: 'S'),
  (value: 3, label: 'T'),
  (value: 4, label: 'Q'),
  (value: 5, label: 'Q'),
  (value: 6, label: 'S'),
  (value: 7, label: 'S'),
];

final _genders = const [
  (value: 'male', label: 'Masculino'),
  (value: 'female', label: 'Feminino'),
  (value: 'other', label: 'Outro'),
];

/// Perfil do aluno: edita nome, telefone, gênero, altura e dias disponíveis.
class ProfileScreen extends ConsumerStatefulWidget {
  const ProfileScreen({super.key});

  @override
  ConsumerState<ProfileScreen> createState() => _ProfileScreenState();
}

class _ProfileScreenState extends ConsumerState<ProfileScreen> {
  final _formKey = GlobalKey<FormState>();
  final _name = TextEditingController();
  final _phone = TextEditingController();
  final _height = TextEditingController();
  String? _gender;
  final Set<int> _days = {};
  bool _initialized = false;
  bool _saving = false;

  @override
  void dispose() {
    _name.dispose();
    _phone.dispose();
    _height.dispose();
    super.dispose();
  }

  void _prefill(StudentProfile profile) {
    if (_initialized) return;
    _initialized = true;
    _name.text = profile.name;
    _phone.text = profile.phone ?? '';
    _height.text = profile.height?.toString() ?? '';
    _gender = profile.gender;
    _days.addAll(profile.availableDays);
  }

  Future<void> _save(StudentProfile profile) async {
    if (!(_formKey.currentState?.validate() ?? false)) return;

    setState(() => _saving = true);
    try {
      final height = int.tryParse(_height.text.trim());
      await ref.read(profileProvider.notifier).save(
            ProfileUpdateData(
              name: _name.text.trim(),
              phone: _phone.text.trim().isEmpty ? null : _phone.text.trim(),
              gender: _gender,
              height: height,
              availableDays: _days.toList()..sort(),
            ),
          );
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Perfil atualizado.')),
      );
      Navigator.of(context).pop();
    } on ApiException catch (error) {
      if (!mounted) return;
      ScaffoldMessenger.of(context)
          .showSnackBar(SnackBar(content: Text(error.message)));
    } finally {
      if (mounted) setState(() => _saving = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final profile = ref.watch(profileProvider);

    return Scaffold(
      appBar: AppBar(title: const Text('Meu perfil')),
      body: profile.when(
        loading: () => const Center(child: CircularProgressIndicator()),
        error: (error, _) => Center(
          child: Padding(
            padding: const EdgeInsets.all(24),
            child: Column(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                const Icon(Icons.cloud_off, size: 48),
                const SizedBox(height: 12),
                Text(error.toString(), textAlign: TextAlign.center),
                const SizedBox(height: 12),
                FilledButton(
                  onPressed: () => ref.invalidate(profileProvider),
                  child: const Text('Tentar novamente'),
                ),
              ],
            ),
          ),
        ),
        data: (data) {
          _prefill(data);
          return _ProfileForm(
            formKey: _formKey,
            name: _name,
            phone: _phone,
            height: _height,
            gender: _gender,
            days: _days,
            saving: _saving,
            email: data.email,
            onGenderChanged: (value) => setState(() => _gender = value),
            onToggleDay: (day) => setState(() {
              _days.contains(day) ? _days.remove(day) : _days.add(day);
            }),
            onSave: () => _save(data),
          );
        },
      ),
    );
  }
}

class _ProfileForm extends StatelessWidget {
  const _ProfileForm({
    required this.formKey,
    required this.name,
    required this.phone,
    required this.height,
    required this.gender,
    required this.days,
    required this.saving,
    required this.email,
    required this.onGenderChanged,
    required this.onToggleDay,
    required this.onSave,
  });

  final GlobalKey<FormState> formKey;
  final TextEditingController name;
  final TextEditingController phone;
  final TextEditingController height;
  final String? gender;
  final Set<int> days;
  final bool saving;
  final String email;
  final ValueChanged<String?> onGenderChanged;
  final ValueChanged<int> onToggleDay;
  final VoidCallback onSave;

  @override
  Widget build(BuildContext context) {
    final scheme = Theme.of(context).colorScheme;

    return ListView(
      padding: const EdgeInsets.all(16),
      children: [
        Card(
          child: ListTile(
            leading: CircleAvatar(
              child: Text(
                name.text.trim().isEmpty
                    ? '?'
                    : name.text.trim()[0].toUpperCase(),
              ),
            ),
            title: Text(email),
            subtitle: const Text('E-mail de acesso (não editável aqui)'),
          ),
        ),
        const SizedBox(height: 16),
        Form(
          key: formKey,
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              TextFormField(
                controller: name,
                textCapitalization: TextCapitalization.words,
                decoration: const InputDecoration(
                  labelText: 'Nome',
                  border: OutlineInputBorder(),
                ),
                validator: (value) =>
                    (value == null || value.trim().isEmpty)
                        ? 'Informe seu nome.'
                        : null,
              ),
              const SizedBox(height: 12),
              TextFormField(
                controller: phone,
                keyboardType: TextInputType.phone,
                decoration: const InputDecoration(
                  labelText: 'Telefone',
                  border: OutlineInputBorder(),
                ),
              ),
              const SizedBox(height: 12),
              DropdownButtonFormField<String>(
                initialValue: gender,
                decoration: const InputDecoration(
                  labelText: 'Gênero',
                  border: OutlineInputBorder(),
                ),
                items: [
                  for (final option in _genders)
                    DropdownMenuItem(
                        value: option.value, child: Text(option.label)),
                ],
                onChanged: onGenderChanged,
              ),
              const SizedBox(height: 12),
              TextFormField(
                controller: height,
                keyboardType: TextInputType.number,
                decoration: const InputDecoration(
                  labelText: 'Altura (cm)',
                  border: OutlineInputBorder(),
                ),
                validator: (value) {
                  final text = value?.trim() ?? '';
                  if (text.isEmpty) return null;
                  final parsed = int.tryParse(text);
                  if (parsed == null || parsed < 50 || parsed > 260) {
                    return 'Altura deve ser entre 50 e 260 cm.';
                  }
                  return null;
                },
              ),
              const SizedBox(height: 16),
              Text('Dias disponíveis',
                  style: Theme.of(context).textTheme.titleSmall),
              const SizedBox(height: 4),
              Text(
                'Toque nos dias em que você costuma treinar.',
                style: Theme.of(context).textTheme.bodySmall,
              ),
              const SizedBox(height: 8),
              Wrap(
                spacing: 8,
                runSpacing: 8,
                children: [
                  for (final day in _weekdays)
                    FilterChip(
                      label: Text(day.label),
                      selected: days.contains(day.value),
                      onSelected: (_) => onToggleDay(day.value),
                    ),
                ],
              ),
            ],
          ),
        ),
        const SizedBox(height: 24),
        FilledButton.icon(
          onPressed: saving ? null : onSave,
          icon: saving
              ? const SizedBox(
                  width: 18,
                  height: 18,
                  child: CircularProgressIndicator(strokeWidth: 2),
                )
              : const Icon(Icons.save_outlined),
          label: Text(saving ? 'Salvando…' : 'Salvar perfil'),
        ),
        const SizedBox(height: 8),
        Text(
          'Dados de treino e avaliação são gerenciados pelo seu treinador.',
          style: Theme.of(context).textTheme.bodySmall?.copyWith(
                color: scheme.outline,
              ),
        ),
      ],
    );
  }
}
