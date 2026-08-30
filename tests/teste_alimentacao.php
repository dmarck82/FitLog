<?php

declare(strict_types=1);

require dirname(__DIR__) . '/app/inicializar.php';
require RAIZ_PROJETO . '/app/controladores/AlimentacaoControlador.php';

$conexao = banco();
$usuario = $conexao->query(
    'SELECT usu_id, usu_nome, usu_email, usu_objetivo FROM usu_usuario ORDER BY usu_id LIMIT 1'
)->fetch();

if (!$usuario) {
    fwrite(STDERR, "Nenhum usuário disponível para o teste de alimentação.\n");
    exit(1);
}

$_SESSION['usuario'] = [
    'id' => (int) $usuario['usu_id'],
    'nome' => $usuario['usu_nome'],
    'email' => $usuario['usu_email'],
    'objetivo' => $usuario['usu_objetivo'],
];

$nomePlano = '__TESTE_ALIMENTACAO_' . bin2hex(random_bytes(4));
$planoId = 0;
$refeicaoId = 0;
$registroId = 0;

try {
    $inserirPlano = $conexao->prepare(
        'INSERT INTO pal_plano_alimentar
            (usu_id, pal_nome, pal_profissional, pal_data_inicio, pal_orientacoes, pal_ativo)
         VALUES (:usuario, :nome, "Nutricionista teste", CURDATE(), "Beber água", 1)'
    );
    $inserirPlano->execute(['usuario' => $usuario['usu_id'], 'nome' => $nomePlano]);
    $planoId = (int) $conexao->lastInsertId();

    $conexao->prepare(
        'INSERT INTO ref_refeicao_plano (pal_id, ref_nome, ref_horario, ref_observacoes, ref_ordem)
         VALUES (:plano, "Café da manhã", "08:00:00", "Consumir com calma", 0)'
    )->execute(['plano' => $planoId]);
    $refeicaoId = (int) $conexao->lastInsertId();

    $inserirItem = $conexao->prepare(
        'INSERT INTO ita_item_alimentar
            (ref_id, ita_alimento, ita_quantidade, ita_unidade, ita_substituicoes, ita_ordem)
         VALUES (:refeicao, :alimento, :quantidade, :unidade, :substituicoes, :ordem)'
    );
    $inserirItem->execute([
        'refeicao' => $refeicaoId,
        'alimento' => 'Ovos',
        'quantidade' => 2,
        'unidade' => 'unidade',
        'substituicoes' => 'Queijo branco',
        'ordem' => 0,
    ]);
    $inserirItem->execute([
        'refeicao' => $refeicaoId,
        'alimento' => 'Café sem açúcar',
        'quantidade' => 200,
        'unidade' => 'ml',
        'substituicoes' => null,
        'ordem' => 1,
    ]);

    $vigente = buscar_plano_alimentar_vigente();
    if (!$vigente || (int) $vigente['pal_id'] !== $planoId) {
        throw new RuntimeException('O plano vigente não foi localizado.');
    }

    $refeicoes = refeicoes_com_itens($planoId);
    if (count($refeicoes) !== 1 || count($refeicoes[0]['itens']) !== 2) {
        throw new RuntimeException('A estrutura de refeições e alimentos está incorreta.');
    }

    $_POST = [
        'ita_alimento' => ['Iogurte'],
        'ita_quantidade' => ['1,5'],
        'ita_unidade' => ['porcao'],
        'ita_substituicoes' => ['Leite fermentado'],
    ];
    $itensNormalizados = itens_alimentares_do_post();
    if (count($itensNormalizados) !== 1 || $itensNormalizados[0]['quantidade'] !== 1.5) {
        throw new RuntimeException('A normalização dos alimentos falhou.');
    }

    ob_start();
    exibir_alimentacao_hoje();
    $html = (string) ob_get_clean();
    if (!str_contains($html, $nomePlano) || !str_contains($html, 'Ovos') || !str_contains($html, 'Queijo branco')) {
        throw new RuntimeException('A tela de alimentação de hoje não exibiu o plano completo.');
    }

    $snapshot = montar_snapshot_alimentar($refeicoes[0], $refeicoes[0]['itens']);
    $inserirRegistro = $conexao->prepare(
        'INSERT INTO ral_registro_alimentar
            (usu_id, pal_id, ref_id, ral_data, ral_horario, ral_situacao,
             ral_refeicao_nome, ral_planejado_snapshot, ral_fome_antes,
             ral_saciedade_depois, ral_observacoes)
         VALUES (:usuario, :plano, :refeicao, CURDATE(), "08:15:00", "parcial",
                 "Café da manhã", :snapshot, 7, 8, "Não tomei o café")'
    );
    $inserirRegistro->execute([
        'usuario' => $usuario['usu_id'],
        'plano' => $planoId,
        'refeicao' => $refeicaoId,
        'snapshot' => json_encode($snapshot, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
    ]);
    $registroId = (int) $conexao->lastInsertId();

    $conexao->prepare(
        'INSERT INTO ira_item_realizado
            (ral_id, ira_alimento, ira_quantidade, ira_unidade, ira_observacoes, ira_ordem)
         VALUES (:registro, "Omelete", 2, "unidade", "Preparada sem óleo", 0)'
    )->execute(['registro' => $registroId]);

    $_POST = [
        'ira_alimento' => ['Fruta'],
        'ira_quantidade' => ['1,5'],
        'ira_unidade' => ['porcao'],
        'ira_observacoes' => ['Quantidade aproximada'],
    ];
    $realizadosNormalizados = itens_realizados_do_post();
    if (count($realizadosNormalizados) !== 1 || $realizadosNormalizados[0]['quantidade'] !== 1.5) {
        throw new RuntimeException('A normalização dos alimentos realizados falhou.');
    }

    $conexao->prepare(
        'UPDATE ita_item_alimentar SET ita_alimento = "Ovos alterados" WHERE ref_id = :refeicao AND ita_ordem = 0'
    )->execute(['refeicao' => $refeicaoId]);

    $_GET['data'] = date('Y-m-d');
    ob_start();
    exibir_alimentacao_hoje();
    $comparativo = (string) ob_get_clean();
    if (!str_contains($comparativo, 'Parcialmente conforme')
        || !str_contains($comparativo, 'Omelete')
        || !str_contains($comparativo, 'Ovos')
        || str_contains($comparativo, 'Ovos alterados')) {
        throw new RuntimeException('O comparativo não preservou a fotografia original do planejado.');
    }

    $registros = registros_alimentares_por_data($planoId, date('Y-m-d'));
    $refeicoesComRegistro = $refeicoes;
    $refeicoesComRegistro[0]['registro'] = $registros[$refeicaoId] ?? null;
    $resumo = resumo_aderencia_alimentar($refeicoesComRegistro);
    if ($resumo['registradas'] !== 1 || $resumo['aderencia'] !== 50) {
        throw new RuntimeException('O cálculo de aderência alimentar está incorreto.');
    }

    ob_start();
    listar_historico_alimentar();
    $historico = (string) ob_get_clean();
    if (!str_contains($historico, '50%')) {
        throw new RuntimeException('O histórico alimentar não exibiu a aderência esperada.');
    }

    $conexao->prepare('DELETE FROM ref_refeicao_plano WHERE ref_id = :refeicao')->execute(['refeicao' => $refeicaoId]);
    ob_start();
    exibir_alimentacao_hoje();
    $comparativoPreservado = (string) ob_get_clean();
    if (!str_contains($comparativoPreservado, 'Omelete') || !str_contains($comparativoPreservado, 'Ovos')) {
        throw new RuntimeException('O registro real desapareceu após a exclusão da refeição planejada.');
    }

    $conexao->prepare('DELETE FROM pal_plano_alimentar WHERE pal_id = :plano')->execute(['plano' => $planoId]);
    $itensRestantes = (int) $conexao->query("SELECT COUNT(*) FROM ita_item_alimentar WHERE ref_id = {$refeicaoId}")->fetchColumn();
    $refeicoesRestantes = (int) $conexao->query("SELECT COUNT(*) FROM ref_refeicao_plano WHERE ref_id = {$refeicaoId}")->fetchColumn();
    if ($itensRestantes !== 0 || $refeicoesRestantes !== 0) {
        throw new RuntimeException('A exclusão em cascata do plano falhou.');
    }
    $registroPreservado = buscar_registro_alimentar($registroId);
    $itensRealizadosRestantes = (int) $conexao->query("SELECT COUNT(*) FROM ira_item_realizado WHERE ral_id = {$registroId}")->fetchColumn();
    if (!$registroPreservado || $registroPreservado['pal_id'] !== null || $registroPreservado['ref_id'] !== null || $itensRealizadosRestantes !== 1) {
        throw new RuntimeException('O histórico realizado não foi preservado após a remoção do plano.');
    }
    $planoId = 0;

    $conexao->prepare('DELETE FROM ral_registro_alimentar WHERE ral_id = :registro')->execute(['registro' => $registroId]);
    $itensRealizadosRestantes = (int) $conexao->query("SELECT COUNT(*) FROM ira_item_realizado WHERE ral_id = {$registroId}")->fetchColumn();
    if ($itensRealizadosRestantes !== 0) {
        throw new RuntimeException('A exclusão em cascata dos itens realizados falhou.');
    }
    $registroId = 0;

    echo "Fluxo funcional de alimentação passou.\n";
} finally {
    if ($registroId > 0) {
        $conexao->prepare('DELETE FROM ral_registro_alimentar WHERE ral_id = :registro')->execute(['registro' => $registroId]);
    }
    if ($planoId > 0) {
        $conexao->prepare('DELETE FROM pal_plano_alimentar WHERE pal_id = :plano')->execute(['plano' => $planoId]);
    }
}
