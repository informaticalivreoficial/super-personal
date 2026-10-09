import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../core/api/api_exception.dart';
import 'formatters.dart';
import 'models/execution.dart';
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
        loading: () => const Center(child: CircularProgressIndicator()),
        error: (error, _) => _ErrorView(
          message: error.toString(),
          onRetry: () =>
              ref.invalidate(trainingDetailProvider(sessionId)),
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
      () =>
          ref.read(studentRepositoryProvider).skipTraining(session.id),
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
    final scheme = Theme.of(context).colorScheme;
    final execution = session.execution;
    final planned = session.isPlanned;
    final inProgress = execution?.status == 'in_progress';

    return ListView(
      padding: const EdgeInsets.all(16),
      children: [
        Row(
          children: [
            Expanded(
              child: Text(
                session.title,
                style: Theme.of(context).textTheme.headlineSmall,
              ),
            ),
            if (session.statusLabel != null)
              Chip(
                label: Text(session.statusLabel!),
                backgroundColor: switch (session.status) {
                  'completed' => scheme.primaryContainer,
                  'skipped' => scheme.surfaceContainerHighest,
                  _ => scheme.tertiaryContainer,
                },
              ),
          ],
        ),
        const SizedBox(height: 8),
        Wrap(
          spacing: 16,
          runSpacing: 4,
          children: [
            if (session.scheduledDateLabel.isNotEmpty)
              _Meta(icon: Icons.event, text: session.scheduledDateLabel),
            if (session.sport?.name != null)
              _Meta(icon: Icons.sports, text: session.sport!.name),
            if (session.estimatedDuration != null)
              _Meta(
                  icon: Icons.timer_outlined,
                  text: formatDuration(session.estimatedDuration!)),
            if (session.distance != null)
              _Meta(
                  icon: Icons.straighten,
                  text: formatDistance(session.distance!)),
            if (session.intensity != null)
              _Meta(icon: Icons.bolt, text: session.intensity!),
            if (session.targetPace != null)
              _Meta(icon: Icons.speed, text: 'Ritmo ${session.targetPace}'),
          ],
        ),
        if (session.description != null &&
            session.description!.isNotEmpty) ...[
          const SizedBox(height: 12),
          Text(session.description!,
              style: Theme.of(context).textTheme.bodyMedium),
        ],
        if (session.coachNotes != null &&
            session.coachNotes!.isNotEmpty) ...[
          const SizedBox(height: 12),
          Card(
            color: scheme.secondaryContainer,
            child: Padding(
              padding: const EdgeInsets.all(12),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text('Observação do treinador',
                      style: Theme.of(context).textTheme.labelLarge),
                  const SizedBox(height: 4),
                  Text(session.coachNotes!),
                ],
              ),
            ),
          ),
        ],

        // Ações do treino.
        const SizedBox(height: 16),
        if (planned) ...[
          FilledButton.icon(
            onPressed: () => _start(context, ref),
            icon: const Icon(Icons.play_arrow),
            label: Text(inProgress ? 'Continuar treino' : 'Iniciar treino'),
          ),
          const SizedBox(height: 8),
          FilledButton.icon(
            onPressed: () => _complete(context, ref),
            icon: const Icon(Icons.check),
            label: const Text('Concluir treino'),
          ),
          const SizedBox(height: 8),
          OutlinedButton.icon(
            onPressed: () => _skip(context, ref),
            icon: const Icon(Icons.skip_next),
            label: const Text('Pular treino'),
          ),
        ] else if (session.isCompleted) ...[
          OutlinedButton.icon(
            onPressed: () => _complete(context, ref),
            icon: const Icon(Icons.edit_outlined),
            label: const Text('Editar registro'),
          ),
        ],

        // Métricas registradas.
        if (execution != null && _hasMetrics(execution)) ...[
          const SizedBox(height: 24),
          Text('Registro',
              style: Theme.of(context).textTheme.titleMedium),
          const SizedBox(height: 8),
          Card(
            child: Padding(
              padding: const EdgeInsets.all(12),
              child: Column(
                children: [
                  if (execution.duration != null)
                    _MetricRow(
                        label: 'Duração',
                        value: formatDuration(execution.duration!)),
                  if (execution.distance != null)
                    _MetricRow(
                        label: 'Distância',
                        value: formatDistance(execution.distance!)),
                  if (execution.averageHeartRate != null)
                    _MetricRow(
                        label: 'FC média',
                        value: '${execution.averageHeartRate} bpm'),
                  if (execution.maxHeartRate != null)
                    _MetricRow(
                        label: 'FC máx',
                        value: '${execution.maxHeartRate} bpm'),
                  if (execution.averagePace != null)
                    _MetricRow(
                        label: 'Ritmo médio',
                        value: execution.averagePace!),
                  if (execution.perceivedEffort != null)
                    _MetricRow(
                        label: 'Esforço percebido',
                        value: '${execution.perceivedEffort}/10'),
                  if (execution.feeling != null)
                    _MetricRow(
                        label: 'Sensação', value: execution.feeling!),
                  if (execution.notes != null &&
                      execution.notes!.isNotEmpty)
                    _MetricRow(
                        label: 'Observações', value: execution.notes!),
                ],
              ),
            ),
          ),
        ],

        // Composição do treino.
        if (session.items.isNotEmpty) ...[
          const SizedBox(height: 24),
          Text('Composição', style: Theme.of(context).textTheme.titleMedium),
          const SizedBox(height: 8),
          ...session.items.map(
            (item) => Card(
              margin: const EdgeInsets.only(bottom: 8),
              child: ListTile(
                dense: true,
                leading: CircleAvatar(
                  radius: 14,
                  child: Text('${item.sortOrder}',
                      style: Theme.of(context).textTheme.labelSmall),
                ),
                title: Text(item.title),
                subtitle: Text(
                  [
                    if (item.typeLabel != null) item.typeLabel!,
                    if (item.repetitions != null)
                      '${item.repetitions} reps',
                    if (item.sets != null) '${item.sets} séries',
                    if (item.duration != null)
                      formatDuration(item.duration!),
                    if (item.distance != null)
                      formatDistance(item.distance!),
                    if (item.target != null) item.target!,
                  ].join(' · '),
                ),
              ),
            ),
          ),
        ],
        if (session.instructions != null &&
            session.instructions!.isNotEmpty) ...[
          const SizedBox(height: 8),
          Text('Instruções',
              style: Theme.of(context).textTheme.titleMedium),
          const SizedBox(height: 4),
          Text(session.instructions!),
        ],
        const SizedBox(height: 32),
      ],
    );
  }

  static bool _hasMetrics(TrainingExecution execution) =>
      execution.duration != null ||
      execution.distance != null ||
      execution.averageHeartRate != null ||
      execution.perceivedEffort != null ||
      (execution.notes?.isNotEmpty ?? false);
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
        top: 16,
        bottom: MediaQuery.of(context).viewInsets.bottom + 16,
      ),
      child: _ExecutionForm(session: session),
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
    _pace.dispose();
    _effort.dispose();
    _notes.dispose();
    super.dispose();
  }

  int? _parseInt(TextEditingController controller, String field) {
    final text = controller.text.trim();
    if (text.isEmpty) return null;
    final value = int.tryParse(text);
    if (value == null || value < 0) {
      throw ValidationException('$field deve ser um número inteiro.');
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
        averagePace:
            _pace.text.trim().isEmpty ? null : _pace.text.trim(),
        perceivedEffort: _parseInt(_effort, 'Esforço'),
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
    return Form(
      key: _formKey,
      child: Column(
        mainAxisSize: MainAxisSize.min,
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            'Registrar treino',
            style: Theme.of(context).textTheme.titleLarge,
          ),
          const SizedBox(height: 4),
          Text(
            'Todos os campos são opcionais.',
            style: Theme.of(context).textTheme.bodySmall,
          ),
          const SizedBox(height: 16),
          Row(
            children: [
              Expanded(
                child: TextFormField(
                  controller: _duration,
                  keyboardType: TextInputType.number,
                  decoration: const InputDecoration(
                    labelText: 'Duração (min)',
                    border: OutlineInputBorder(),
                  ),
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: TextFormField(
                  controller: _distance,
                  keyboardType: TextInputType.number,
                  decoration: const InputDecoration(
                    labelText: 'Distância (m)',
                    border: OutlineInputBorder(),
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
                    labelText: 'FC média (bpm)',
                    border: OutlineInputBorder(),
                  ),
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: TextFormField(
                  controller: _pace,
                  decoration: const InputDecoration(
                    labelText: 'Ritmo (ex.: 5:00)',
                    border: OutlineInputBorder(),
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
                  controller: _effort,
                  keyboardType: TextInputType.number,
                  decoration: const InputDecoration(
                    labelText: 'Esforço (0–10)',
                    border: OutlineInputBorder(),
                  ),
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: DropdownButtonFormField<String>(
                  initialValue: _feeling,
                  decoration: const InputDecoration(
                    labelText: 'Sensação',
                    border: OutlineInputBorder(),
                  ),
                  items: [
                    for (final feeling in _feelings)
                      DropdownMenuItem(
                          value: feeling, child: Text(feeling)),
                  ],
                  onChanged: (value) => setState(() => _feeling = value),
                ),
              ),
            ],
          ),
          const SizedBox(height: 12),
          TextFormField(
            controller: _notes,
            maxLines: 2,
            decoration: const InputDecoration(
              labelText: 'Observações',
              border: OutlineInputBorder(),
            ),
          ),
          const SizedBox(height: 16),
          SizedBox(
            width: double.infinity,
            child: FilledButton(
              onPressed: _submit,
              child: const Text('Salvar registro'),
            ),
          ),
        ],
      ),
    );
  }
}

class _Meta extends StatelessWidget {
  const _Meta({required this.icon, required this.text});

  final IconData icon;
  final String text;

  @override
  Widget build(BuildContext context) {
    return Row(
      mainAxisSize: MainAxisSize.min,
      children: [
        Icon(icon, size: 16, color: Theme.of(context).colorScheme.outline),
        const SizedBox(width: 4),
        Text(text, style: Theme.of(context).textTheme.bodySmall),
      ],
    );
  }
}

class _MetricRow extends StatelessWidget {
  const _MetricRow({required this.label, required this.value});

  final String label;
  final String value;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 4),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Text(label, style: Theme.of(context).textTheme.bodyMedium),
          Text(value, style: Theme.of(context).textTheme.bodyMedium),
        ],
      ),
    );
  }
}

class _ErrorView extends StatelessWidget {
  const _ErrorView({required this.message, required this.onRetry});

  final String message;
  final VoidCallback onRetry;

  @override
  Widget build(BuildContext context) {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(24),
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(Icons.cloud_off,
                size: 48, color: Theme.of(context).colorScheme.outline),
            const SizedBox(height: 12),
            Text(message, textAlign: TextAlign.center),
            const SizedBox(height: 12),
            FilledButton(
                onPressed: onRetry, child: const Text('Tentar novamente')),
          ],
        ),
      ),
    );
  }
}

/// Erro de validação local do formulário (não é da API).
class ValidationException implements Exception {
  const ValidationException(this.message);

  final String message;
}
