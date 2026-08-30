<?php

declare(strict_types=1);

const TIPOS_EXERCICIO = [
    'forca' => 'Força / musculação',
    'cardio' => 'Cardio',
    'mobilidade' => 'Mobilidade',
    'outro' => 'Outro',
];

const DIAS_SEMANA = [
    0 => 'Domingo',
    1 => 'Segunda-feira',
    2 => 'Terça-feira',
    3 => 'Quarta-feira',
    4 => 'Quinta-feira',
    5 => 'Sexta-feira',
    6 => 'Sábado',
];

const SITUACOES_TREINO = [
    'em_andamento' => ['rotulo' => 'Em andamento', 'classe' => 'text-bg-info', 'icone' => 'bi-play-circle'],
    'concluido' => ['rotulo' => 'Concluído', 'classe' => 'text-bg-success', 'icone' => 'bi-check-circle'],
    'parcial' => ['rotulo' => 'Parcial', 'classe' => 'text-bg-warning', 'icone' => 'bi-circle-half'],
    'nao_realizado' => ['rotulo' => 'Não realizado', 'classe' => 'text-bg-danger', 'icone' => 'bi-x-circle'],
];

function dia_semana_treino(mixed $dia): string
{
    if ($dia === null || $dia === '') {
        return 'Sem dia fixo';
    }

    return DIAS_SEMANA[(int) $dia] ?? 'Sem dia fixo';
}

function situacao_plano_treino(array $plano): array
{
    if (!(bool) $plano['ptr_ativo']) {
        return ['rotulo' => 'Inativo', 'classe' => 'text-bg-secondary'];
    }

    $hoje = date('Y-m-d');

    if ($plano['ptr_data_inicio'] > $hoje) {
        return ['rotulo' => 'Agendado', 'classe' => 'text-bg-info'];
    }

    if ($plano['ptr_data_fim'] && $plano['ptr_data_fim'] < $hoje) {
        return ['rotulo' => 'Encerrado', 'classe' => 'text-bg-light border'];
    }

    return ['rotulo' => 'Vigente', 'classe' => 'text-bg-success'];
}

function montar_snapshot_treino(array $treino, array $exercicios): array
{
    return [
        'nome' => $treino['trp_nome'],
        'dia_semana' => $treino['trp_dia_semana'],
        'orientacoes' => $treino['trp_orientacoes'],
        'exercicios' => array_map(static fn (array $exercicio): array => [
            'exp_id' => (int) $exercicio['exp_id'],
            'exe_id' => (int) $exercicio['exe_id'],
            'nome' => $exercicio['exe_nome'],
            'tipo' => $exercicio['exe_tipo'],
            'series' => (int) $exercicio['exp_series'],
            'repeticoes_min' => $exercicio['exp_repeticoes_min'] === null ? null : (int) $exercicio['exp_repeticoes_min'],
            'repeticoes_max' => $exercicio['exp_repeticoes_max'] === null ? null : (int) $exercicio['exp_repeticoes_max'],
            'carga_alvo' => $exercicio['exp_carga_alvo'],
            'descanso_segundos' => $exercicio['exp_descanso_segundos'] === null ? null : (int) $exercicio['exp_descanso_segundos'],
            'duracao_segundos' => $exercicio['exp_duracao_segundos'] === null ? null : (int) $exercicio['exp_duracao_segundos'],
            'distancia_km' => $exercicio['exp_distancia_km'],
            'observacoes' => $exercicio['exp_observacoes'],
        ], $exercicios),
    ];
}

function decodificar_snapshot_treino(?string $snapshot): array
{
    if (!$snapshot) {
        return [];
    }

    try {
        $dados = json_decode($snapshot, true, 512, JSON_THROW_ON_ERROR);
        return is_array($dados) ? $dados : [];
    } catch (JsonException) {
        return [];
    }
}

function resumo_treino_realizado(array $registro, array $exerciciosRealizados): array
{
    $snapshot = is_array($registro['planejado'] ?? null)
        ? $registro['planejado']
        : decodificar_snapshot_treino($registro['trr_planejado_snapshot'] ?? null);
    $planejados = $snapshot['exercicios'] ?? [];
    $seriesPlanejadas = array_sum(array_map(
        static fn (array $item): int => max(0, (int) ($item['series'] ?? 0)),
        $planejados
    ));
    $limitesPorExercicio = [];

    foreach ($planejados as $item) {
        $limitesPorExercicio[(int) ($item['exp_id'] ?? 0)] = max(0, (int) ($item['series'] ?? 0));
    }

    $seriesConcluidas = 0;
    $seriesAderentes = 0;
    $volume = 0.0;
    $extras = 0;

    foreach ($exerciciosRealizados as $exercicio) {
        $concluidasDoExercicio = 0;

        foreach ($exercicio['series'] ?? [] as $serie) {
            if (!(bool) ($serie['srr_concluida'] ?? false)) {
                continue;
            }

            $seriesConcluidas++;
            $concluidasDoExercicio++;
            $volume += (float) ($serie['srr_carga_kg'] ?? 0) * (int) ($serie['srr_repeticoes'] ?? 0);
        }

        $planejadoId = (int) ($exercicio['exr_exp_id_snapshot'] ?? $exercicio['exp_id'] ?? 0);
        $limite = $limitesPorExercicio[$planejadoId] ?? 0;

        if ($limite > 0) {
            $seriesAderentes += min($concluidasDoExercicio, $limite);
            $extras += max(0, $concluidasDoExercicio - $limite);
        } else {
            $extras += $concluidasDoExercicio;
        }
    }

    $aderencia = $seriesPlanejadas > 0
        ? (int) round(min(100, ($seriesAderentes / $seriesPlanejadas) * 100))
        : null;

    return [
        'series_planejadas' => $seriesPlanejadas,
        'series_concluidas' => $seriesConcluidas,
        'series_extras' => $extras,
        'aderencia' => $aderencia,
        'volume' => $volume,
    ];
}

function formatar_duracao_segundos(mixed $segundos): string
{
    if ($segundos === null || $segundos === '') {
        return '—';
    }

    $total = max(0, (int) $segundos);
    $minutos = intdiv($total, 60);
    $restante = $total % 60;

    if ($minutos === 0) {
        return $restante . ' s';
    }

    return $restante > 0 ? $minutos . ' min ' . $restante . ' s' : $minutos . ' min';
}
