import 'package:flutter/material.dart';

import 'app_tone.dart';
import 'icon_tile.dart';

/// Visual de uma modalidade (ícone + tom) a partir do nome vindo da API.
///
/// Assim a lista de treinos fica reconhecível de relance, sem depender de
/// imagem nenhuma no servidor.
({IconData icon, AppTone tone}) sportVisual(String? nome) {
  final chave = _normalize(nome);

  if (chave.isEmpty) return (icon: Icons.fitness_center_rounded, tone: AppTone.brand);

  if (chave.contains('natac') || chave.contains('swim') || chave.contains('nado')) {
    return (icon: Icons.pool_rounded, tone: AppTone.info);
  }
  if (chave.contains('cicl') || chave.contains('bike') || chave.contains('pedal')) {
    return (icon: Icons.directions_bike_rounded, tone: AppTone.success);
  }
  if (chave.contains('triat')) {
    return (icon: Icons.sports_score_rounded, tone: AppTone.brand);
  }
  if (chave.contains('corrid') || chave.contains('run') || chave.contains('correr')) {
    return (icon: Icons.directions_run_rounded, tone: AppTone.brand);
  }
  if (chave.contains('muscul') || chave.contains('academia') || chave.contains('forca')) {
    return (icon: Icons.fitness_center_rounded, tone: AppTone.warning);
  }
  if (chave.contains('emagrec') || chave.contains('peso')) {
    return (icon: Icons.monitor_weight_rounded, tone: AppTone.warning);
  }
  if (chave.contains('yoga') || chave.contains('pilates') || chave.contains('mobilidade')) {
    return (icon: Icons.self_improvement_rounded, tone: AppTone.success);
  }
  if (chave.contains('funcional') ||
      chave.contains('condicionamento') ||
      chave.contains('cross')) {
    return (icon: Icons.monitor_heart_rounded, tone: AppTone.danger);
  }
  if (chave.contains('caminh') || chave.contains('walk')) {
    return (icon: Icons.directions_walk_rounded, tone: AppTone.info);
  }

  return (icon: Icons.fitness_center_rounded, tone: AppTone.brand);
}

/// Quadradinho com o ícone da modalidade.
class SportIcon extends StatelessWidget {
  const SportIcon({super.key, required this.name, this.size = 44});

  final String? name;
  final double size;

  @override
  Widget build(BuildContext context) {
    final visual = sportVisual(name);
    return IconTile(icon: visual.icon, tone: visual.tone, size: size);
  }
}

String _normalize(String? value) {
  if (value == null) return '';
  const acentos = {
    'á': 'a', 'à': 'a', 'â': 'a', 'ã': 'a', 'ä': 'a',
    'é': 'e', 'è': 'e', 'ê': 'e', 'ë': 'e',
    'í': 'i', 'ì': 'i', 'î': 'i', 'ï': 'i',
    'ó': 'o', 'ò': 'o', 'ô': 'o', 'õ': 'o', 'ö': 'o',
    'ú': 'u', 'ù': 'u', 'û': 'u', 'ü': 'u',
    'ç': 'c', 'ñ': 'n',
  };
  var out = value.toLowerCase().trim();
  acentos.forEach((de, para) => out = out.replaceAll(de, para));
  return out;
}
