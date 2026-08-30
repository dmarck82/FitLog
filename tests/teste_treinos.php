<?php

declare(strict_types=1);

require dirname(__DIR__) . '/app/inicializar.php';
require RAIZ_PROJETO . '/app/controladores/TreinoControlador.php';
require RAIZ_PROJETO . '/app/controladores/MedicamentoControlador.php';
require RAIZ_PROJETO . '/app/controladores/PainelControlador.php';

$conexao = banco();
$usuario = $conexao->query(
    'SELECT usu_id, usu_nome, usu_email, usu_objetivo FROM usu_usuario ORDER BY usu_id LIMIT 1'
)->fetch();

if (!$usuario) {
    fwrite(STDERR, "Nenhum usuário disponível para o teste de treinos.\n");
    exit(1);
}

$_SESSION['usuario'] = [
    'id' => (int) $usuario['usu_id'],
    'nome' => $usuario['usu_nome'],
    'email' => $usuario['usu_email'],
    'objetivo' => $usuario['usu_objetivo'],
];

$sufixo = bin2hex(random_bytes(4));
$exercicioId = 0;
$planoId = 0;
$treinoId = 0;
$registroId = 0;

try {
    $conexao->prepare(
        'INSERT INTO exe_exercicio (usu_id, exe_nome, exe_grupo_muscular, exe_tipo)
         VALUES (:usuario, :nome, "Peitoral", "forca")'
    )->execute(['usuario' => $usuario['usu_id'], 'nome' => '__TESTE_SUPINO_' . $sufixo]);
    $exercicioId = (int) $conexao->lastInsertId();

    $conexao->prepare(
        'INSERT INTO ptr_plano_treino
            (usu_id, ptr_nome, ptr_objetivo, ptr_data_inicio, ptr_ativo)
         VALUES (:usuario, :nome, "Teste funcional", CURDATE(), 1)'
    )->execute(['usuario' => $usuario['usu_id'], 'nome' => '__TESTE_PLANO_' . $sufixo]);
    $planoId = (int) $conexao->lastInsertId();

    $conexao->prepare(
        'INSERT INTO trp_treino_planejado
            (ptr_id, trp_nome, trp_dia_semana, trp_ordem, trp_orientacoes)
         VALUES (:plano, "Treino A", DAYOFWEEK(CURDATE()) - 1, 0, "Manter a técnica")'
    )->execute(['plano' => $planoId]);
    $treinoId = (int) $conexao->lastInsertId();

    $conexao->prepare(
        'INSERT INTO exp_exercicio_planejado
            (trp_id, exe_id, exp_series, exp_repeticoes_min, exp_repeticoes_max,
             exp_carga_alvo, exp_descanso_segundos, exp_ordem)
         VALUES (:treino, :exercicio, 3, 8, 12, 10, 60, 0)'
    )->execute(['treino' => $treinoId, 'exercicio' => $exercicioId]);

    $treino = buscar_treino_planejado($treinoId);
    $planejados = exercicios_planejados_do_treino($treinoId);
    $snapshot = montar_snapshot_treino($treino, $planejados);

    if (count($snapshot['exercicios']) !== 1 || $snapshot['exercicios'][0]['series'] !== 3) {
        throw new RuntimeException('O snapshot do treino planejado está incorreto.');
    }

    $conexao->prepare(
        'INSERT INTO trr_treino_realizado
            (usu_id, ptr_id, trp_id, trr_data, trr_situacao, trr_treino_nome,
             trr_planejado_snapshot, trr_esforco_percebido, trr_energia)
         VALUES (:usuario, :plano, :treino, CURDATE(), "parcial", "Treino A",
                 :snapshot, 8, 7)'
    )->execute([
        'usuario' => $usuario['usu_id'],
        'plano' => $planoId,
        'treino' => $treinoId,
        'snapshot' => json_encode($snapshot, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
    ]);
    $registroId = (int) $conexao->lastInsertId();

    salvar_exercicios_realizados($conexao, $registroId, [[
        'exe_id' => $exercicioId,
        'exp_id' => $planejados[0]['exp_id'],
        'exr_nome' => $planejados[0]['exe_nome'],
        'exr_tipo' => 'forca',
        'exr_observacoes' => '',
        'series' => [
            ['srr_numero' => 1, 'srr_repeticoes' => 10, 'srr_carga_kg' => 10, 'srr_duracao_segundos' => null, 'srr_distancia_km' => null, 'srr_concluida' => 1, 'srr_observacoes' => ''],
            ['srr_numero' => 2, 'srr_repeticoes' => 8, 'srr_carga_kg' => 10, 'srr_duracao_segundos' => null, 'srr_distancia_km' => null, 'srr_concluida' => 1, 'srr_observacoes' => ''],
            ['srr_numero' => 3, 'srr_repeticoes' => 6, 'srr_carga_kg' => 10, 'srr_duracao_segundos' => null, 'srr_distancia_km' => null, 'srr_concluida' => 0, 'srr_observacoes' => ''],
        ],
    ]]);

    $registro = buscar_treino_realizado($registroId);
    $registro['planejado'] = decodificar_snapshot_treino($registro['trr_planejado_snapshot']);
    $realizados = exercicios_realizados_do_treino($registroId);
    $resumo = resumo_treino_realizado($registro, $realizados);

    if ($resumo['aderencia'] !== 67 || $resumo['series_concluidas'] !== 2 || abs($resumo['volume'] - 180.0) > 0.001) {
        throw new RuntimeException('O cálculo de aderência ou volume está incorreto.');
    }

    ob_start();
    renderizar('treinos/realizado_detalhes', ['registro' => $registro, 'exercicios' => $realizados, 'resumo' => $resumo], 'Treino A');
    $html = (string) ob_get_clean();

    if (!str_contains($html, '67%') || !str_contains($html, '180,00 kg') || !str_contains($html, 'Treino A')) {
        throw new RuntimeException('O comparativo do treino não foi renderizado corretamente.');
    }

    $_GET = ['data' => date('Y-m-d')];
    ob_start();
    exibir_treinos();
    $paginaTreinos = (string) ob_get_clean();

    if (!str_contains($paginaTreinos, 'Treino A') || !str_contains($paginaTreinos, '67%')) {
        throw new RuntimeException('A página diária de treinos não foi renderizada corretamente.');
    }

    $conexao->prepare('DELETE FROM ptr_plano_treino WHERE ptr_id = :plano')->execute(['plano' => $planoId]);
    $planoId = 0;
    $preservado = buscar_treino_realizado($registroId);

    if (!$preservado || $preservado['ptr_id'] !== null || $preservado['trp_id'] !== null
        || !str_contains($preservado['trr_planejado_snapshot'], 'Manter a técnica')) {
        throw new RuntimeException('O histórico não foi preservado após excluir o planejamento.');
    }

    $preservado['planejado'] = decodificar_snapshot_treino($preservado['trr_planejado_snapshot']);
    $resumoPreservado = resumo_treino_realizado($preservado, exercicios_realizados_do_treino($registroId));

    if ($resumoPreservado['aderencia'] !== 67) {
        throw new RuntimeException('O comparativo perdeu a associação após excluir o planejamento.');
    }

    ob_start();
    exibir_painel();
    $painel = (string) ob_get_clean();

    if (!str_contains($painel, 'Treinos') || !str_contains($painel, 'Último treino')) {
        throw new RuntimeException('O resumo de treinos não foi integrado ao painel.');
    }

    echo "Fluxo funcional de treinos passou.\n";
} finally {
    if ($registroId > 0) {
        $conexao->prepare('DELETE FROM trr_treino_realizado WHERE trr_id = :registro')->execute(['registro' => $registroId]);
    }

    if ($planoId > 0) {
        $conexao->prepare('DELETE FROM ptr_plano_treino WHERE ptr_id = :plano')->execute(['plano' => $planoId]);
    }

    if ($exercicioId > 0) {
        $conexao->prepare('DELETE FROM exe_exercicio WHERE exe_id = :exercicio')->execute(['exercicio' => $exercicioId]);
    }
}

