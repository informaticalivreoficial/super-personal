import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:image_picker/image_picker.dart';

import '../../core/api/api_exception.dart';
import 'cpf_input_formatter.dart';
import 'formatters.dart';
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

/// Perfil do aluno: edita nome, telefone, CPF, data de nascimento, gênero,
/// altura e dias disponíveis.
class ProfileScreen extends ConsumerStatefulWidget {
  const ProfileScreen({super.key});

  @override
  ConsumerState<ProfileScreen> createState() => _ProfileScreenState();
}

class _ProfileScreenState extends ConsumerState<ProfileScreen> {
  final _formKey = GlobalKey<FormState>();
  final _name = TextEditingController();
  final _phone = TextEditingController();
  final _cpf = TextEditingController();
  final _birthDateText = TextEditingController();
  final _height = TextEditingController();
  DateTime? _birthDate;
  String? _gender;
  final Set<int> _days = {};
  bool _initialized = false;
  bool _saving = false;
  bool _uploadingPhoto = false;

  @override
  void dispose() {
    _name.dispose();
    _phone.dispose();
    _cpf.dispose();
    _birthDateText.dispose();
    _height.dispose();
    super.dispose();
  }

  void _prefill(StudentProfile profile) {
    if (_initialized) return;
    _initialized = true;
    _name.text = profile.name;
    _phone.text = profile.phone ?? '';
    _cpf.text = formatCpf(profile.document);
    _birthDate = profile.birthDate;
    _birthDateText.text = _birthDate == null ? '' : formatDate(_birthDate!);
    _height.text = profile.height?.toString() ?? '';
    _gender = profile.gender;
    _days.addAll(profile.availableDays);
  }

  /// Seletor de data limitado a maiores de 18 anos (a API valida de novo).
  Future<void> _pickBirthDate() async {
    final today = DateTime.now();
    final limit = DateTime(today.year - 18, today.month, today.day);

    final picked = await showDatePicker(
      context: context,
      initialDate:
          _birthDate ?? DateTime(today.year - 30, today.month, today.day),
      firstDate: DateTime(1900),
      lastDate: limit,
      helpText: 'Data de nascimento',
    );

    if (picked != null && mounted) {
      setState(() {
        _birthDate = picked;
        _birthDateText.text = formatDate(picked);
      });
    }
  }

  Future<void> _save(StudentProfile profile) async {
    if (!(_formKey.currentState?.validate() ?? false)) return;

    setState(() => _saving = true);
    try {
      final height = int.tryParse(_height.text.trim());
      final cpf = cpfDigits(_cpf.text);
      await ref
          .read(profileProvider.notifier)
          .save(
            ProfileUpdateData(
              name: _name.text.trim(),
              phone: _phone.text.trim().isEmpty ? null : _phone.text.trim(),
              gender: _gender,
              height: height,
              document: cpf.isEmpty ? null : cpf,
              birthDate: _birthDate,
              availableDays: _days.toList()..sort(),
            ),
          );
      if (!mounted) return;
      ScaffoldMessenger.of(context)
          .showSnackBar(const SnackBar(content: Text('Perfil atualizado.')));
      Navigator.of(context).pop();
    } on ApiException catch (error) {
      if (!mounted) return;
      ScaffoldMessenger.of(context)
          .showSnackBar(SnackBar(content: Text(error.message)));
    } finally {
      if (mounted) setState(() => _saving = false);
    }
  }

  Future<void> _pickAndUploadPhoto(ImageSource source) async {
    final picker = ImagePicker();
    final picked = await picker.pickImage(
      source: source,
      maxWidth: 1024,
      imageQuality: 85,
    );
    if (picked == null) return;

    setState(() => _uploadingPhoto = true);
    try {
      await ref.read(profileProvider.notifier).uploadPhoto(picked.path);
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Foto de perfil atualizada.')),
      );
    } on ApiException catch (error) {
      if (!mounted) return;
      ScaffoldMessenger.of(context)
          .showSnackBar(SnackBar(content: Text(error.message)));
    } finally {
      if (mounted) setState(() => _uploadingPhoto = false);
    }
  }

  Future<void> _removePhoto() async {
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Remover foto?'),
        content: const Text('Sua foto de perfil será apagada.'),
        actions: [
          TextButton(
            onPressed: () => Navigator.of(context).pop(false),
            child: const Text('Cancelar'),
          ),
          FilledButton(
            onPressed: () => Navigator.of(context).pop(true),
            child: const Text('Remover'),
          ),
        ],
      ),
    );
    if (confirmed != true || !mounted) return;

    setState(() => _uploadingPhoto = true);
    try {
      await ref.read(profileProvider.notifier).removePhoto();
      if (!mounted) return;
      ScaffoldMessenger.of(context)
          .showSnackBar(const SnackBar(content: Text('Foto removida.')));
    } on ApiException catch (error) {
      if (!mounted) return;
      ScaffoldMessenger.of(context)
          .showSnackBar(SnackBar(content: Text(error.message)));
    } finally {
      if (mounted) setState(() => _uploadingPhoto = false);
    }
  }

  void _showPhotoSheet(String? photoUrl) {
    showModalBottomSheet<void>(
      context: context,
      builder: (context) => SafeArea(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            ListTile(
              leading: const Icon(Icons.photo_camera_outlined),
              title: const Text('Tirar foto'),
              onTap: () {
                Navigator.of(context).pop();
                _pickAndUploadPhoto(ImageSource.camera);
              },
            ),
            ListTile(
              leading: const Icon(Icons.photo_library_outlined),
              title: const Text('Escolher da galeria'),
              onTap: () {
                Navigator.of(context).pop();
                _pickAndUploadPhoto(ImageSource.gallery);
              },
            ),
            if (photoUrl != null)
              ListTile(
                leading: const Icon(Icons.delete_outline),
                title: const Text('Remover foto'),
                onTap: () {
                  Navigator.of(context).pop();
                  _removePhoto();
                },
              ),
          ],
        ),
      ),
    );
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
            cpf: _cpf,
            birthDate: _birthDate,
            birthDateText: _birthDateText,
            height: _height,
            gender: _gender,
            days: _days,
            saving: _saving,
            email: data.email,
            photoUrl: data.photoUrl,
            uploadingPhoto: _uploadingPhoto,
            onChangePhoto: () => _showPhotoSheet(data.photoUrl),
            onPickBirthDate: _pickBirthDate,
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
    required this.cpf,
    required this.birthDate,
    required this.birthDateText,
    required this.height,
    required this.gender,
    required this.days,
    required this.saving,
    required this.email,
    required this.photoUrl,
    required this.uploadingPhoto,
    required this.onChangePhoto,
    required this.onPickBirthDate,
    required this.onGenderChanged,
    required this.onToggleDay,
    required this.onSave,
  });

  final GlobalKey<FormState> formKey;
  final TextEditingController name;
  final TextEditingController phone;
  final TextEditingController cpf;
  final DateTime? birthDate;
  final TextEditingController birthDateText;
  final TextEditingController height;
  final String? gender;
  final Set<int> days;
  final bool saving;
  final String email;
  final String? photoUrl;
  final bool uploadingPhoto;
  final VoidCallback onChangePhoto;
  final VoidCallback onPickBirthDate;
  final ValueChanged<String?> onGenderChanged;
  final ValueChanged<int> onToggleDay;
  final VoidCallback onSave;

  @override
  Widget build(BuildContext context) {
    final scheme = Theme.of(context).colorScheme;
    final initials = name.text.trim().isEmpty
        ? '?'
        : name.text.trim()[0].toUpperCase();

    return ListView(
      padding: const EdgeInsets.all(16),
      children: [
        Card(
          child: Padding(
            padding: const EdgeInsets.all(16),
            child: Row(
              children: [
                Stack(
                  clipBehavior: Clip.none,
                  children: [
                    CircleAvatar(
                      radius: 32,
                      backgroundImage: photoUrl != null
                          ? NetworkImage(photoUrl!)
                          : null,
                      child: photoUrl == null ? Text(initials) : null,
                    ),
                    Positioned(
                      right: -4,
                      bottom: -4,
                      child: Material(
                        color: scheme.primary,
                        shape: const CircleBorder(),
                        child: InkWell(
                          customBorder: const CircleBorder(),
                          onTap: uploadingPhoto ? null : onChangePhoto,
                          child: uploadingPhoto
                              ? const Padding(
                                  padding: EdgeInsets.all(8),
                                  child: SizedBox(
                                    width: 14,
                                    height: 14,
                                    child: CircularProgressIndicator(
                                      strokeWidth: 2,
                                      color: Colors.white,
                                    ),
                                  ),
                                )
                              : const Padding(
                                  padding: EdgeInsets.all(6),
                                  child: Icon(
                                    Icons.camera_alt,
                                    size: 16,
                                    color: Colors.white,
                                  ),
                                ),
                        ),
                      ),
                    ),
                  ],
                ),
                const SizedBox(width: 16),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        email,
                        style: Theme.of(context).textTheme.bodyMedium,
                      ),
                      const SizedBox(height: 2),
                      Text(
                        'Foto e dados do seu cadastro no treino',
                        style: Theme.of(context).textTheme.bodySmall
                            ?.copyWith(color: scheme.outline),
                      ),
                      TextButton.icon(
                        onPressed: uploadingPhoto ? null : onChangePhoto,
                        icon: const Icon(Icons.edit_outlined, size: 16),
                        label: Text(
                          photoUrl == null ? 'Adicionar foto' : 'Alterar foto',
                        ),
                      ),
                    ],
                  ),
                ),
              ],
            ),
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
                validator: (value) => (value == null || value.trim().isEmpty)
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
              TextFormField(
                controller: cpf,
                keyboardType: TextInputType.number,
                inputFormatters: [
                  FilteringTextInputFormatter.digitsOnly,
                  const CpfInputFormatter(),
                ],
                decoration: const InputDecoration(
                  labelText: 'CPF',
                  helperText: 'Usado para gerar o Pix das cobranças online.',
                  border: OutlineInputBorder(),
                ),
                validator: (value) {
                  final digits = cpfDigits(value);
                  if (digits.isEmpty) return null;
                  return digits.length == 11
                      ? null
                      : 'Informe um CPF válido (11 dígitos).';
                },
              ),
              const SizedBox(height: 12),
              TextFormField(
                readOnly: true,
                onTap: onPickBirthDate,
                decoration: InputDecoration(
                  labelText: 'Data de nascimento',
                  helperText: 'É preciso ter 18 anos ou mais para usar o app.',
                  hintText: 'dd/mm/aaaa',
                  border: const OutlineInputBorder(),
                  suffixIcon: const Icon(Icons.calendar_today_outlined),
                ),
                controller: birthDateText,
                validator: (_) {
                  if (birthDate == null) {
                    return 'Informe sua data de nascimento.';
                  }
                  return ageInYears(birthDate!) >= 18
                      ? null
                      : 'É preciso ter 18 anos ou mais para usar o aplicativo.';
                },
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
                      value: option.value,
                      child: Text(option.label),
                    ),
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
              Text(
                'Dias disponíveis',
                style: Theme.of(context).textTheme.titleSmall,
              ),
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
          style: Theme.of(context).textTheme.bodySmall
              ?.copyWith(color: scheme.outline),
        ),
        const SizedBox(height: 16),
        Card(
          child: ListTile(
            leading: Icon(Icons.lock_outline, color: scheme.primary),
            title: const Text('E-mail e senha'),
            subtitle: const Text('Dados de acesso ao aplicativo'),
            trailing: const Icon(Icons.chevron_right),
            onTap: () => context.push('/conta'),
          ),
        ),
      ],
    );
  }
}
