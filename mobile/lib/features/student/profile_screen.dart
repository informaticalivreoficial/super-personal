import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:image_picker/image_picker.dart';

import '../../core/api/api_exception.dart';
import '../../core/theme/app_colors.dart';
import '../../core/widgets/app_card.dart';
import '../../core/widgets/app_tone.dart';
import '../../core/widgets/empty_state.dart';
import '../../core/widgets/icon_tile.dart';
import '../../core/widgets/section_header.dart';
import '../../core/widgets/skeleton.dart';
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
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Padding(
              padding: const EdgeInsets.fromLTRB(20, 4, 20, 8),
              child: Text(
                'Foto de perfil',
                style: Theme.of(context).textTheme.titleMedium,
              ),
            ),
            ListTile(
              leading: const IconTile(icon: Icons.photo_camera_outlined),
              title: const Text('Tirar foto'),
              onTap: () {
                Navigator.of(context).pop();
                _pickAndUploadPhoto(ImageSource.camera);
              },
            ),
            ListTile(
              leading: const IconTile(icon: Icons.photo_library_outlined),
              title: const Text('Escolher da galeria'),
              onTap: () {
                Navigator.of(context).pop();
                _pickAndUploadPhoto(ImageSource.gallery);
              },
            ),
            if (photoUrl != null)
              ListTile(
                leading: const IconTile(
                  icon: Icons.delete_outline_rounded,
                  tone: AppTone.danger,
                ),
                title: const Text('Remover foto'),
                onTap: () {
                  Navigator.of(context).pop();
                  _removePhoto();
                },
              ),
            const SizedBox(height: 8),
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
        loading: () => const SkeletonCardList(count: 4),
        error: (error, _) => RefreshableState(
          onRefresh: () async => ref.invalidate(profileProvider),
          child: ErrorState(
            message: error.toString(),
            onRetry: () => ref.invalidate(profileProvider),
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
    final theme = Theme.of(context);
    final initials = name.text.trim().isEmpty
        ? '?'
        : name.text.trim()[0].toUpperCase();

    return ListView(
      padding: const EdgeInsets.fromLTRB(16, 16, 16, 28),
      children: [
        _PhotoCard(
          initials: initials,
          name: name.text.trim(),
          email: email,
          photoUrl: photoUrl,
          uploading: uploadingPhoto,
          onChange: onChangePhoto,
        ),
        const SizedBox(height: 20),
        Form(
          key: formKey,
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              const SectionHeader(
                title: 'Dados pessoais',
                subtitle: 'Usados nos seus treinos e no Pix das cobranças.',
              ),
              AppCard(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    TextFormField(
                      controller: name,
                      textCapitalization: TextCapitalization.words,
                      decoration: const InputDecoration(labelText: 'Nome'),
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
                        helperText:
                            'Usado para gerar o Pix das cobranças online.',
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
                      decoration: const InputDecoration(
                        labelText: 'Data de nascimento',
                        helperText:
                            'É preciso ter 18 anos ou mais para usar o app.',
                        hintText: 'dd/mm/aaaa',
                        suffixIcon: Icon(Icons.calendar_today_outlined),
                      ),
                      controller: birthDateText,
                      validator: (_) {
                        if (birthDate == null) {
                          return 'Informe sua data de nascimento.';
                        }
                        return ageInYears(birthDate!) >= 18
                            ? null
                            : 'É preciso ter 18 anos ou mais para usar o '
                                  'aplicativo.';
                      },
                    ),
                    const SizedBox(height: 12),
                    DropdownButtonFormField<String>(
                      initialValue: gender,
                      decoration: const InputDecoration(labelText: 'Gênero'),
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
                  ],
                ),
              ),
              const SizedBox(height: 20),
              const SectionHeader(
                title: 'Rotina de treino',
                subtitle: 'Toque nos dias em que você costuma treinar.',
              ),
              AppCard(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text('Dias disponíveis', style: theme.textTheme.titleSmall),
                    const SizedBox(height: 12),
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
              SizedBox(
                width: double.infinity,
                child: FilledButton.icon(
                  onPressed: saving ? null : onSave,
                  icon: saving
                      ? const SizedBox(
                          width: 18,
                          height: 18,
                          child: CircularProgressIndicator(
                            strokeWidth: 2,
                            color: Colors.white,
                          ),
                        )
                      : const Icon(Icons.save_outlined),
                  label: Text(saving ? 'Salvando…' : 'Salvar perfil'),
                ),
              ),
            ],
          ),
        ),
        const SizedBox(height: 20),
        const AlertCard(
          icon: Icons.info_outline_rounded,
          tone: AppTone.neutral,
          title: 'Dados de treino',
          message: 'Pesos, avaliações e treinos são gerenciados pelo seu '
              'treinador.',
        ),
        const SizedBox(height: 12),
        AppCard(
          padding: EdgeInsets.zero,
          onTap: () => context.push('/conta'),
          child: ListTile(
            leading: const IconTile(icon: Icons.lock_outline_rounded),
            title: const Text('E-mail e senha'),
            subtitle: const Text('Dados de acesso ao aplicativo'),
            trailing: const Icon(Icons.chevron_right),
          ),
        ),
      ],
    );
  }
}

/// Cartão do topo: avatar (com botão de câmera) e a identificação do aluno.
class _PhotoCard extends StatelessWidget {
  const _PhotoCard({
    required this.initials,
    required this.name,
    required this.email,
    required this.photoUrl,
    required this.uploading,
    required this.onChange,
  });

  final String initials;
  final String name;
  final String email;
  final String? photoUrl;
  final bool uploading;
  final VoidCallback onChange;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    return AppCard(
      child: Row(
        children: [
          Stack(
            clipBehavior: Clip.none,
            children: [
              CircleAvatar(
                radius: 34,
                backgroundColor: AppColors.brand100,
                backgroundImage: photoUrl != null
                    ? NetworkImage(photoUrl!)
                    : null,
                child: photoUrl == null
                    ? Text(
                        initials,
                        style: theme.textTheme.titleLarge!
                            .copyWith(color: AppColors.brand700),
                      )
                    : null,
              ),
              Positioned(
                right: -2,
                bottom: -2,
                child: Material(
                  color: theme.colorScheme.primary,
                  shape: const CircleBorder(),
                  child: InkWell(
                    customBorder: const CircleBorder(),
                    onTap: uploading ? null : onChange,
                    child: uploading
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
                            padding: EdgeInsets.all(7),
                            child: Icon(
                              Icons.photo_camera_rounded,
                              size: 16,
                              color: Colors.white,
                            ),
                          ),
                  ),
                ),
              ),
            ],
          ),
          const SizedBox(width: 18),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  name.isEmpty ? 'Seu nome' : name,
                  style: theme.textTheme.titleMedium,
                ),
                const SizedBox(height: 2),
                Text(
                  email.isEmpty ? 'Sem e-mail cadastrado' : email,
                  style: theme.textTheme.bodySmall,
                ),
                const SizedBox(height: 6),
                OutlinedButton.icon(
                  onPressed: uploading ? null : onChange,
                  icon: const Icon(Icons.edit_outlined, size: 18),
                  label: Text(photoUrl == null ? 'Adicionar foto' : 'Trocar foto'),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}
