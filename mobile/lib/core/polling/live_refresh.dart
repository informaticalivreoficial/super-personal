import 'dart:async';

import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

/// Executa [onTick] periodicamente enquanto o app está aberto (1º plano).
///
/// O app do aluno não tem websocket: as notificações nascem no banco (mensagem
/// do treinador, plano publicado, Pix confirmado) ou no cron das 07:00, e o app
/// **descobre consultando**. Este widget é o polling — com o app fechado quem
/// avisa é o push (FCM).
///
/// - só roda com [enabled] (ex.: usuário logado);
/// - para o ciclo quando o app sai do 1º plano e faz um tick imediato quando
///   volta (é justamente aí que o dado costuma estar velho);
/// - nunca deixa exceção do [onTick] chegar ao timer (um tique que falha não
///   pode derrubar a tela nem interromper os próximos).
class LiveRefresh extends ConsumerStatefulWidget {
  const LiveRefresh({
    super.key,
    required this.onTick,
    required this.child,
    this.enabled = true,
    this.interval = const Duration(seconds: 30),
  });

  /// Ação de cada ciclo; deve absorver os próprios erros de rede.
  final Future<void> Function(WidgetRef ref) onTick;

  final Widget child;
  final bool enabled;
  final Duration interval;

  @override
  ConsumerState<LiveRefresh> createState() => _LiveRefreshState();
}

class _LiveRefreshState extends ConsumerState<LiveRefresh>
    with WidgetsBindingObserver {
  Timer? _timer;
  bool _ticking = false;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addObserver(this);
    if (widget.enabled) {
      _start();
    }
  }

  @override
  void didUpdateWidget(LiveRefresh oldWidget) {
    super.didUpdateWidget(oldWidget);
    if (widget.enabled == oldWidget.enabled) {
      return;
    }
    widget.enabled ? _start() : _stop();
  }

  @override
  void dispose() {
    _stop();
    WidgetsBinding.instance.removeObserver(this);
    super.dispose();
  }

  @override
  void didChangeAppLifecycleState(AppLifecycleState state) {
    if (!widget.enabled) {
      return;
    }
    if (state == AppLifecycleState.resumed) {
      _start();
      _tick();
    } else {
      _stop();
    }
  }

  void _start() {
    _timer ??= Timer.periodic(widget.interval, (_) => _tick());
  }

  void _stop() {
    _timer?.cancel();
    _timer = null;
  }

  Future<void> _tick() async {
    if (!widget.enabled || _ticking) {
      return;
    }
    _ticking = true;
    try {
      await widget.onTick(ref);
    } catch (_) {
      // Falha de um ciclo não interrompe os próximos.
    } finally {
      _ticking = false;
    }
  }

  @override
  Widget build(BuildContext context) => widget.child;
}
