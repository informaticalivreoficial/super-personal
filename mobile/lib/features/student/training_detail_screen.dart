import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../core/api/api_exception.dart';
import '../../core/theme/app_colors.dart';
import '../../core/widgets/app_card.dart';
import '../../core/widgets/app_header.dart';
import '../../core/widgets/app_tone.dart';
import '../../core/widgets/empty_state.dart';
import '../../core/widgets/section_header.dart';
import '../../core/widgets/skeleton.dart';
import '../../core/widgets/sport_icon.dart';
import '../../core/widgets/status_chip.dart';
import 'formatters.dart';
import 'models/execution.dart';
import 'models/session_item.dart';
import 'models/training_session.dart';
import 'student_providers.dart';

/// Sessão única (com itens e execução) em `GET /student/trainings/{id}`.
final trainingDetailProvider =
    FutureProvider.autoDispose.family<TrainingSession, int>(
  (ref, sessionId) =>
      ref.read(studentRepositoryProvider).training(sessionId),
);

/// Detalhe do treino: informações, itens e ações (iniciar/concluir/pular).
class TrainingDetailScreen extends ConsumerWidget {
  const TrainingDetailScreen({super.key, required this.sessionId});

  final int sessionId;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final detail = ref.watch(trainingDetailProvider(sessionId));

    return Scaffold(
      appBar: AppBar(title: const Text('Treino')),
      body: detail.when(
        loading: () => const SkeletonCardList(count: 4),
        error: (error, _) => RefreshableState(
          onRefresh: () async =>
              ref.invalidate(trainingDetailProvider(sessionId)),
          child: ErrorState(
            message: error.toString(),
            onRetry: () => ref.invalidate(trainingDetailProvider(sessionId)),
          ),
        ),
        data: (session) => _DetailBody(
          session: session,
          onChanged: () =>
              ref.invalidate(trainingDetailProvider(sessionId)),
        ),
      ),
    );
  }
}

class _DetailBody extends ConsumerWidget {
  const _DetailBody({required this.session, required this.onChanged});

  final TrainingSession session;
  final VoidCallback onChanged;

  Future<void> _run(
    BuildContext context,
    WidgetRef ref,
    Future<void> Function() action, {
    String successMessage = 'Treino atualizado.',
  }) async {
    final messenger = ScaffoldMessenger.of(context);
    try {
      await action();
      onChanged();
      messenger.showSnackBar(SnackBar(content: Text(successMessage)));
    } on ApiException catch (error) {
      messenger.showSnackBar(SnackBar(content: Text(error.message)));
    }
  }

  Future<void> _start(BuildContext context, WidgetRef ref) => _run(
        context,
        ref,
        () => ref
            .read(studentRepositoryProvider)
            .startTraining(session.id),
        successMessage: 'Treino iniciado. Boa treino!',
      );

  Future<void> _skip(BuildContext context, WidgetRef ref) async {
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Pular este treino?'),
        content: const Text('A sessão será marcada como pulada.'),
        actions: [
          TextButton(
            onPressed: () => Navigator.of(context).pop(false),
            child: const Text('Cancelar'),
          ),
          FilledButton(
            onPressed: () => Navigator.of(context).pop(true),
            child: const Text('Pular treino'),
          ),
        ],
      ),
    );
    if (confirmed != true || !context.mounted) return;

    await _run(
      context,
      ref,
      () => ref.read(studentRepositoryProvider).skipTraining(session.id),
      successMessage: 'Treino marcado como pulado.',
    );
  }

  Future<void> _complete(BuildContext context, WidgetRef ref) async {
    final data = await showExecutionSheet(context, session);
    if (data == null || !context.mounted) return;

    await _run(
      context,
      ref,
      () => ref
          .read(studentRepositoryProvider)
          .completeTraining(session.id, data),
      successMessage: 'Treino concluído. Parabéns!',
    );
  }

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final execution = session.execution;
    final planned = session.isPlanned;
    final inProgress = execution?.status == 'in_progress';
    final temRegistro = execution != null && _hasMetrics(execution);

    return RefreshIndicator(
      onRefresh: () async => onChanged(),
      child: ListView(
        physics: const AlwaysScrollableScrollPhysics(),
        padding: EdgeInsets.zero,
        children: [
          _Hero(session: session),
          Padding(
            padding: const EdgeInsets.fromLTRB(16, 18, 16, 32),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                if (session.description != null &&
                    session.description!.isNotEmpty) ...[
                  const SectionHeader(title: 'Sobre o treino'),
                  AppCard(
                    child: Text(
                      session.description!,
                      style: Theme.of(context).textTheme.bodyMedium,
                    ),
                  ),
                  const SizedBox(height: 18),
                ],
                if (session.coachNotes != null &&
                    session.coachNotes!.isNotEmpty) ...[
                  AlertCard(
                    icon: Icons.chat_bubble_outline_rounded,
                    tone: AppTone.info,
                    title: 'Recado do seu treinador',
                    message: session.coachNotes,
                  ),
                  const SizedBox(height: 18),
                ],
                if (planned) ...[
                  FilledButton.icon(
                    onPressed: () => _start(context, ref),
                    icon: const Icon(Icons.play_arrow_rounded),
                    label: Text(
                      inProgress ? 'Continuar treino' : 'Iniciar treino',
                    ),
                  ),
                  const SizedBox(height: 10),
                  FilledButton.icon(
                    onPressed: () => _complete(context, ref),
                    icon: const Icon(Icons.check_rounded),
                    label: const Text('Concluir treino'),
                    style: FilledButton.styleFrom(
                      backgroundColor: AppColors.success,
                    ),
                  ),
                  const SizedBox(height: 10),
                  OutlinedButton.icon(
                    onPressed: () => _skip(context, ref),
                    icon: const Icon(Icons.skip_next_rounded),
                    label: const Text('Pular treino'),
                  ),
                ] else if (session.isCompleted) ...[
                  OutlinedButton.icon(
                    onPressed: () => _complete(context, ref),
                    icon: const Icon(Icons.edit_outlined),
                    label: const Text('Editar registro'),
                  ),
                ],
                if (temRegistro) ...[
                  const SizedBox(height: 18),
                  const SectionHeader(title: 'Seu registro'),
                  AppCard(
                    padding: const EdgeInsets.symmetric(
                      horizontal: 16,
                      vertical: 6,
                    ),
                    child: Column(
                      children: [
                        if (execution.duration != null)
                          _MetricRow(
                            icon: Icons.timer_outlined,
                            label: 'Duração',
                            value: formatDuration(execution.duration!),
                          ),
                        if (execution.distance != null)
                          _MetricRow(
                            icon: Icons.straighten_rounded,
                            label: 'Distância',
                            value: formatDistance(execution.distance!),
                          ),
                        if (execution.averageHeartRate != null)
                          _MetricRow(
                            icon: Icons.favorite_border_rounded,
                            label: 'FC média',
                            value: '${execution.averageHeartRate} bpm',
                          ),
                        if (execution.maxHeartRate != null)
                          _MetricRow(
                            icon: Icons.monitor_heart_outlined,
                            label: 'FC máxima',
                            value: '${execution.maxHeartRate} bpm',
                          ),
                        if (execution.averagePace != null)
                          _MetricRow(
                            icon: Icons.speed_rounded,
                            label: 'Ritmo médio',
                            value: execution.averagePace!,
                          ),
                        if (execution.perceivedEffort != null)
                          _MetricRow(
                            icon: Icons.bolt_rounded,
                            label: 'Esforço percebido',
                            value: '${execution.perceivedEffort}/10',
                          ),
                        if (execution.feeling != null)
                          _MetricRow(
                            icon: Icons.mood_rounded,
                            label: 'Sensação',
                            value: execution.feeling!,
                          ),
                        if (execution.notes != null &&
                            execution.notes!.isNotEmpty)
                          _MetricRow(
                            icon: Icons.notes_rounded,
                            label: 'Observações',
                            value: execution.notes!,
                          ),
                      ],
                    ),
                  ),
                ],
                if (session.items.isNotEmpty) ...[
                  const SizedBox(height: 18),
                  SectionHeader(
                    title: 'Composição do treino',
                    subtitle: session.items.length == 1
                        ? '1 item'
                        : '${session.items.length} itens',
                  ),
                  for (final item in session.items)
                    Padding(
                      padding: const EdgeInsets.only(bottom: 10),
                      child: _ItemCard(item: item),
                    ),
                ],
                if (session.instructions != null &&
                    session.instructions!.isNotEmpty) ...[
                  const SizedBox(height: 8),
                  const SectionHeader(title: 'Instruções'),
                  AppCard(
                    child: Text(
                      session.instructions!,
                      style: Theme.of(context).textTheme.bodyMedium,
                    ),
                  ),
                ],
              ],
            ),
          ),
        ],
      ),
    );
  }

  static bool _hasMetrics(TrainingExecution execution) =>
      execution.duration != null ||
      execution.distance != null ||
      execution.averageHeartRate != null ||
      execution.maxHeartRate != null ||
      execution.perceivedEffort != null ||
      (execution.notes?.isNotEmpty ?? false);
}

/// Topo em gradiente: modalidade, título, status e dados do treino.
class _Hero extends StatelessWidget {
  const _Hero({required this.session});

  final TrainingSession session;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final visual = sportVisual(session.sport?.name);

    return AppHeader(
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Container(
                width: 54,
                height: 54,
                decoration: BoxDecoration(
                  color: Colors.white.withValues(alpha: 0.14),
                  borderRadius: BorderRadius.circular(17),
                  border:
                      Border.all(color: Colors.white.withValues(alpha: 0.18)),
                ),
                child: Icon(visual.icon, color: Colors.white, size: 28),
              ),
              const SizedBox(width: 14),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    if (session.scheduledDateLabel.isNotEmpty)
                      Text(
                        session.scheduledDateLabel,
                        style: theme.textTheme.labelSmall!.copyWith(
                          color: Colors.white.withValues(alpha: 0.72),
                        ),
                      ),
                    const SizedBox(height: 2),
                    Text(
                      session.title,
                      style: theme.textTheme.titleLarge!
                          .copyWith(color: Colors.white, letterSpacing: -0.3),
                    ),
                  ],
                ),
              ),
            ],
          ),
          if (session.statusLabel != null) ...[
            const SizedBox(height: 14),
            StatusChip(
              label: session.statusLabel!,
              status: session.status,
            ),
          ],
          if (_pills.isNotEmpty) ...[
            const SizedBox(height: 14),
            Wrap(
              spacing: 8,
              runSpacing: 8,
              children: [for (final pill in _pills) _HeroPill(pill: pill)],
            ),
          ],
        ],
      ),
    );
  }

  List<({IconData icon, String text})> get _pills => [
        if (session.sport?.name != null)
          (icon: Icons.sports_rounded, text: session.sport!.name),
        if (session.estimatedDuration != null &&
            session.estimatedDuration! > 0)
          (
            icon: Icons.timer_outlined,
            text: formatDuration(session.estimatedDuration!),
          ),
        if (session.distance != null && session.distance! > 0)
          (
            icon: Icons.straighten_rounded,
            text: formatDistance(session.distance!),
          ),
        if (session.intensity != null)
          (icon: Icons.bolt_rounded, text: session.intensity!),
        if (session.targetPace != null)
          (icon: Icons.speed_rounded, text: 'Ritmo ${session.targetPace}'),
      ];
}

/// Pílula translúcida do cabeçalho (modalidade, duração...).
class _HeroPill extends StatelessWidget {
  const _HeroPill({required this.pill});

  final ({IconData icon, String text}) pill;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
      decoration: BoxDecoration(
        color: Colors.white.withValues(alpha: 0.12),
        borderRadius: BorderRadius.circular(999),
        border: Border.all(color: Colors.white.withValues(alpha: 0.16)),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(pill.icon, size: 14, color: Colors.white.withValues(alpha: 0.85)),
          const SizedBox(width: 5),
          Text(
            pill.text,
            style: Theme.of(context).textTheme.labelSmall!.copyWith(
                  color: Colors.white,
                  fontWeight: FontWeight.w600,
                ),
          ),
        ],
      ),
    );
  }
}

/// Um item da composição do treino (com o número da ordem).
class _ItemCard extends StatelessWidget {
  const _ItemCard({required this.item});

  final SessionItem item;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final meta = _metaParts.join(' · ');

    return AppCard(
      padding: const EdgeInsets.all(14),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Container(
            width: 30,
            height: 30,
            alignment: Alignment.center,
            decoration: const BoxDecoration(
              color: AppColors.brand100,
              shape: BoxShape.circle,
            ),
            child: Text(
              '${item.sortOrder}',
              style: theme.textTheme.labelSmall!.copyWith(
                color: AppColors.brand800,
                fontWeight: FontWeight.w800,
              ),
            ),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(item.title, style: theme.textTheme.titleSmall),
                if (meta.isNotEmpty) ...[
                  const SizedBox(height: 3),
                  Text(meta, style: theme.textTheme.bodySmall),
                ],
              ],
            ),
          ),
        ],
      ),
    );
  }

  List<String> get _metaParts => [
        ?item.typeLabel,
        if (item.repetitions != null) '${item.repetitions} reps',
        if (item.sets != null) '${item.sets} séries',
        if (item.duration != null) formatDuration(item.duration!),
        if (item.distance != null) formatDistance(item.distance!),
        ?item.target,
      ];
}

/// Formulário de métricas da execução (duração, distância, FC, esforço).
///
/// Retorna `null` se cancelado; campos vazios viram `null` no payload.
Future<ExecutionData?> showExecutionSheet(
  BuildContext context,
  TrainingSession session,
) {
  return showModalBottomSheet<ExecutionData>(
    context: context,
    isScrollControlled: true,
    builder: (context) => Padding(
      padding: EdgeInsets.only(
        left: 16,
        right: 16,
        top: 4,
        bottom: MediaQuery.of(context).viewInsets.bottom + 20,
      ),
      // Em tela pequena com o teclado aberto o formulário precisa rolar.
      child: SingleChildScrollView(child: _ExecutionForm(session: session)),
    ),
  );
}

class _ExecutionForm extends StatefulWidget {
  const _ExecutionForm({required this.session});

  final TrainingSession session;

  @override
  State<_ExecutionForm> createState() => _ExecutionFormState();
}

class _ExecutionFormState extends State<_ExecutionForm> {
  final _formKey = GlobalKey<FormState>();
  late final TextEditingController _duration;
  late final TextEditingController _distance;
  late final TextEditingController _heartRate;
  late final TextEditingController _maxHeartRate;
  late final TextEditingController _pace;
  late final TextEditingController _effort;
  late final TextEditingController _notes;
  final _feelings = const [
    'Ótimo',
    'Bom',
    'Regular',
    'Ruim',
  ];
  String? _feeling;

  @override
  void initState() {
    super.initState();
    final execution = widget.session.execution;
    // A API guarda segundos; o form exibe minutos.
    final durationMinutes =
        execution?.duration != null ? execution!.duration! ~/ 60 : null;
    _duration = TextEditingController(
        text: durationMinutes?.toString() ?? '');
    _distance = TextEditingController(
        text: execution?.distance?.toString() ?? '');
    _heartRate = TextEditingController(
        text: execution?.averageHeartRate?.toString() ?? '');
    _maxHeartRate = TextEditingController(
        text: execution?.maxHeartRate?.toString() ?? '');
    _pace = TextEditingController(text: execution?.averagePace ?? '');
    _effort = TextEditingController(
        text: execution?.perceivedEffort?.toString() ?? '');
    _notes = TextEditingController(text: execution?.notes ?? '');
    _feeling = execution?.feeling;
  }

  @override
  void dispose() {
    _duration.dispose();
    _distance.dispose();
    _heartRate.dispose();
    _maxHeartRate.dispose();
    _pace.dispose();
    _effort.dispose();
    _notes.dispose();
    super.dispose();
  }

  int? _parseInt(
    TextEditingController controller,
    String field, {
    int? min,
    int? max,
  }) {
    final text = controller.text.trim();
    if (text.isEmpty) return null;
    final value = int.tryParse(text);
    if (value == null || value < 0) {
      throw ValidationException('$field deve ser um número inteiro.');
    }
    if (min != null && value < min) {
      throw ValidationException('$field deve ser no mínimo $min.');
    }
    if (max != null && value > max) {
      throw ValidationException('$field deve ser no máximo $max.');
    }
    return value;
  }

  void _submit() {
    if (!(_formKey.currentState?.validate() ?? false)) return;

    try {
      // A API espera duração em segundos; o form pede em minutos.
      final minutes = _parseInt(_duration, 'Duração');
      final data = ExecutionData(
        duration: minutes != null ? minutes * 60 : null,
        distance: _parseInt(_distance, 'Distância'),
        averageHeartRate: _parseInt(_heartRate, 'FC média'),
        maxHeartRate: _parseInt(_maxHeartRate, 'FC máxima'),
        averagePace:
            _pace.text.trim().isEmpty ? null : _pace.text.trim(),
        perceivedEffort:
            _parseInt(_effort, 'Esforço', min: 0, max: 10),
        feeling: _feeling,
        notes: _notes.text.trim().isEmpty ? null : _notes.text.trim(),
      );
      Navigator.of(context).pop(data);
    } on ValidationException catch (error) {
      ScaffoldMessenger.of(context)
          .showSnackBar(SnackBar(content: Text(error.message)));
    }
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    return Form(
      key: _formKey,
      child: Column(
        mainAxisSize: MainAxisSize.min,
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              const Icon(Icons.insights_rounded, color: AppColors.brand600),
              const SizedBox(width: 8),
              Expanded(
                child: Text(
                  'Registrar treino',
                  style: theme.textTheme.titleLarge,
                ),
              ),
            ],
          ),
          const SizedBox(height: 4),
          Text(
            'Todos os campos são opcionais — preencha o que você tem.',
            style: theme.textTheme.bodySmall,
          ),
          const SizedBox(height: 18),
          Row(
            children: [
              Expanded(
                child: TextFormField(
                  controller: _duration,
                  keyboardType: TextInputType.number,
                  decoration: const InputDecoration(
                    labelText: 'Duração',
                    suffixText: 'min',
                  ),
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: TextFormField(
                  controller: _distance,
                  keyboardType: TextInputType.number,
                  decoration: const InputDecoration(
                    labelText: 'Distância',
                    suffixText: 'm',
                  ),
                ),
              ),
            ],
          ),
          const SizedBox(height: 12),
          Row(
            children: [
              Expanded(
                child: TextFormField(
                  controller: _heartRate,
                  keyboardType: TextInputType.number,
                  decoration: const InputDecoration(
                    labelText: 'FC média',
                    suffixText: 'bpm',
                  ),
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: TextFormField(
                  controller: _maxHeartRate,
                  keyboardType: TextInputType.number,
                  decoration: const InputDecoration(
                    labelText: 'FC máxima',
                    suffixText: 'bpm',
                  ),
                ),
              ),
            ],
          ),
          const SizedBox(height: 12),
          Row(
            children: [
              Expanded(
                child: TextFormField(
                  controller: _pace,
                  decoration: const InputDecoration(
                    labelText: 'Ritmo',
                    hintText: 'ex.: 5:00',
                  ),
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: TextFormField(
                  controller: _effort,
                  keyboardType: TextInputType.number,
                  decoration: const InputDecoration(
                    labelText: 'Esforço',
                    suffixText: '0–10',
                  ),
                ),
              ),
            ],
          ),
          const SizedBox(height: 12),
          Text(
            'Como você se sentiu?',
            style: theme.textTheme.labelMedium,
          ),
          const SizedBox(height: 8),
          Wrap(
            spacing: 8,
            children: [
              for (final feeling in _feelings)
                ChoiceChip(
                  label: Text(feeling),
                  selected: _feeling == feeling,
                  onSelected: (_) => setState(
                    () => _feeling = _feeling == feeling ? null : feeling,
                  ),
                ),
            ],
          ),
          const SizedBox(height: 14),
          TextFormField(
            controller: _notes,
            maxLines: 3,
            decoration: const InputDecoration(
              labelText: 'Observações',
              hintText: 'Alguma dor, imprevisto, sensação do treino...',
            ),
          ),
          const SizedBox(height: 16),
          SizedBox(
            width: double.infinity,
            child: FilledButton.icon(
              onPressed: _submit,
              icon: const Icon(Icons.check_rounded),
              label: const Text('Salvar registro'),
            ),
          ),
        ],
      ),
    );
  }
}

/// Linha de métrica registrada (ícone, rótulo e valor).
class _MetricRow extends StatelessWidget {
  const _MetricRow({
    required this.icon,
    required this.label,
    required this.value,
  });

  final IconData icon;
  final String label;
  final String value;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 10),
      child: Row(
        children: [
          Icon(icon, size: 18, color: AppColors.textMuted),
          const SizedBox(width: 10),
          Expanded(
            child: Text(
              label,
              style: theme.textTheme.bodySmall!
                  .copyWith(color: AppColors.textSecondary),
            ),
          ),
          const SizedBox(width: 12),
          Flexible(
            child: Text(
              value,
              textAlign: TextAlign.right,
              style: theme.textTheme.titleSmall,
            ),
          ),
        ],
      ),
    );
  }
}

/// Erro de validação local do formulário (não é da API).
class ValidationException implements Exception {
  const ValidationException(this.message);

  final String message;
}
