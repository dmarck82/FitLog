<?php

declare(strict_types=1);

const UNIDADES_ALIMENTO = [
    'g' => 'g',
    'ml' => 'mL',
    'unidade' => 'unidade(s)',
    'porcao' => 'porção(ões)',
    'colher_sopa' => 'colher(es) de sopa',
    'colher_cha' => 'colher(es) de chá',
    'xicara' => 'xícara(s)',
    'fatia' => 'fatia(s)',
    'concha' => 'concha(s)',
    'copo' => 'copo(s)',
    'scoop' => 'scoop(s)',
];

const SITUACOES_REGISTRO_ALIMENTAR = [
    'conforme' => ['rotulo' => 'Conforme o plano', 'classe' => 'text-bg-success', 'icone' => 'bi-check-circle', 'pontuacao' => 100],
    'parcial' => ['rotulo' => 'Parcialmente conforme', 'classe' => 'text-bg-warning', 'icone' => 'bi-circle-half', 'pontuacao' => 50],
    'substituida' => ['rotulo' => 'Refeição substituída', 'classe' => 'text-bg-info', 'icone' => 'bi-arrow-left-right', 'pontuacao' => 75],
    'nao_realizada' => ['rotulo' => 'Não realizada', 'classe' => 'text-bg-danger', 'icone' => 'bi-x-circle', 'pontuacao' => 0],
];

function exibir_alimentacao_hoje(): void
{
    exigir_autenticacao();
    $data = trim((string) ($_GET['data'] ?? date('Y-m-d')));
    if (!data_iso_valida($data)) {
        flash('erro', 'Informe uma data válida para consultar a alimentação.');
        redirecionar('/alimentacao');
    }

    $plano = buscar_plano_alimentar_por_data($data);
    $resumo = ['total' => 0, 'registradas' => 0, 'aderencia' => null];

    if ($plano) {
        $plano['refeicoes'] = refeicoes_com_itens((int) $plano['pal_id']);
        $registros = registros_alimentares_por_data((int) $plano['pal_id'], $data);

        foreach ($plano['refeicoes'] as &$refeicao) {
            $refeicao['registro'] = $registros[(int) $refeicao['ref_id']] ?? null;
        }
        unset($refeicao);
        foreach (($registros['sem_refeicao'] ?? []) as $registroOrfao) {
            $planejado = $registroOrfao['planejado'];
            $plano['refeicoes'][] = [
                'ref_id' => 0,
                'ref_nome' => $registroOrfao['ral_refeicao_nome'],
                'ref_horario' => $planejado['horario'] ?? null,
                'ref_observacoes' => $planejado['observacoes'] ?? null,
                'itens' => [],
                'registro' => $registroOrfao,
            ];
        }
        $resumo = resumo_aderencia_alimentar($plano['refeicoes']);
    }

    $podeRegistrar = $data <= date('Y-m-d');
    renderizar('alimentacao/hoje', compact('plano', 'data', 'resumo', 'podeRegistrar'), 'Alimentação diária');
}

function listar_planos_alimentares(): void
{
    exigir_autenticacao();
    $consulta = banco()->prepare(
        'SELECT pal.*,
                (SELECT COUNT(*) FROM ref_refeicao_plano ref WHERE ref.pal_id = pal.pal_id) AS total_refeicoes,
                (SELECT COUNT(*)
                   FROM ita_item_alimentar ita
                   INNER JOIN ref_refeicao_plano ref ON ref.ref_id = ita.ref_id
                  WHERE ref.pal_id = pal.pal_id) AS total_itens
         FROM pal_plano_alimentar pal
         WHERE pal.usu_id = :usuario
         ORDER BY pal.pal_ativo DESC, pal.pal_data_inicio DESC, pal.pal_id DESC'
    );
    $consulta->execute(['usuario' => usuario_atual()['id']]);

    renderizar('alimentacao/planos_lista', ['planos' => $consulta->fetchAll()], 'Planos alimentares');
}

function formulario_plano_alimentar(): void
{
    exigir_autenticacao();
    $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
    $plano = ['pal_data_inicio' => date('Y-m-d'), 'pal_ativo' => 1];

    if ($id) {
        $plano = buscar_plano_alimentar((int) $id);
        if (!$plano) {
            flash('erro', 'Plano alimentar não encontrado.');
            redirecionar('/alimentacao/planos');
        }
    }

    renderizar('alimentacao/plano_formulario', compact('plano'), $id ? 'Editar plano alimentar' : 'Novo plano alimentar');
}

function salvar_plano_alimentar(): void
{
    exigir_autenticacao();
    somente_post();
    validar_csrf();

    $id = filter_var($_POST['pal_id'] ?? null, FILTER_VALIDATE_INT) ?: null;
    $nome = trim((string) ($_POST['pal_nome'] ?? ''));
    $profissional = trim((string) ($_POST['pal_profissional'] ?? ''));
    $inicio = trim((string) ($_POST['pal_data_inicio'] ?? ''));
    $fim = trim((string) ($_POST['pal_data_fim'] ?? ''));
    $orientacoes = trim((string) ($_POST['pal_orientacoes'] ?? ''));
    $ativo = isset($_POST['pal_ativo']) ? 1 : 0;
    $erros = [];

    if ($nome === '') {
        $erros[] = 'Informe o nome do plano alimentar.';
    } elseif (mb_strlen($nome) > 120) {
        $erros[] = 'O nome deve ter no máximo 120 caracteres.';
    }
    if (mb_strlen($profissional) > 120) {
        $erros[] = 'O nome do profissional deve ter no máximo 120 caracteres.';
    }
    if (!data_iso_valida($inicio)) {
        $erros[] = 'Informe uma data inicial válida.';
    }
    if ($fim !== '' && !data_iso_valida($fim)) {
        $erros[] = 'Informe uma data final válida.';
    } elseif ($fim !== '' && data_iso_valida($inicio) && $fim < $inicio) {
        $erros[] = 'A data final não pode ser anterior à data inicial.';
    }

    if ($erros) {
        foreach ($erros as $erro) {
            flash('erro', $erro);
        }
        $plano = $_POST;
        $plano['pal_ativo'] = $ativo;
        renderizar('alimentacao/plano_formulario', compact('plano'), $id ? 'Editar plano alimentar' : 'Novo plano alimentar');
        return;
    }

    $parametros = [
        'usuario' => usuario_atual()['id'],
        'nome' => $nome,
        'profissional' => $profissional ?: null,
        'inicio' => $inicio,
        'fim' => $fim ?: null,
        'orientacoes' => $orientacoes ?: null,
        'ativo' => $ativo,
    ];

    if ($id) {
        if (!buscar_plano_alimentar((int) $id)) {
            flash('erro', 'Plano alimentar não encontrado.');
            redirecionar('/alimentacao/planos');
        }
        $parametros['id'] = $id;
        $sql = 'UPDATE pal_plano_alimentar
                   SET pal_nome = :nome, pal_profissional = :profissional,
                       pal_data_inicio = :inicio, pal_data_fim = :fim,
                       pal_orientacoes = :orientacoes, pal_ativo = :ativo
                 WHERE pal_id = :id AND usu_id = :usuario';
        $mensagem = 'Plano alimentar atualizado com sucesso.';
        $destino = '/alimentacao/planos/detalhes?id=' . $id;
    } else {
        $sql = 'INSERT INTO pal_plano_alimentar
                    (usu_id, pal_nome, pal_profissional, pal_data_inicio, pal_data_fim, pal_orientacoes, pal_ativo)
                VALUES (:usuario, :nome, :profissional, :inicio, :fim, :orientacoes, :ativo)';
        $mensagem = 'Plano alimentar criado. Agora adicione as refeições.';
        $destino = '';
    }

    $conexao = banco();
    $conexao->prepare($sql)->execute($parametros);
    $id = $id ?: (int) $conexao->lastInsertId();
    flash('sucesso', $mensagem);
    redirecionar($destino ?: '/alimentacao/planos/detalhes?id=' . $id);
}

function detalhar_plano_alimentar(): void
{
    exigir_autenticacao();
    $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
    $plano = $id ? buscar_plano_alimentar((int) $id) : false;

    if (!$plano) {
        flash('erro', 'Plano alimentar não encontrado.');
        redirecionar('/alimentacao/planos');
    }

    $plano['refeicoes'] = refeicoes_com_itens((int) $id);
    renderizar('alimentacao/plano_detalhes', compact('plano'), $plano['pal_nome']);
}

function formulario_refeicao_plano(): void
{
    exigir_autenticacao();
    $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
    $planoId = filter_input(INPUT_GET, 'plano', FILTER_VALIDATE_INT);
    $refeicao = [
        'pal_id' => $planoId ?: '',
        'ref_ordem' => proxima_ordem_refeicao($planoId ? (int) $planoId : 0),
    ];
    $itens = [];

    if ($id) {
        $refeicao = buscar_refeicao_plano((int) $id);
        if (!$refeicao) {
            flash('erro', 'Refeição não encontrada.');
            redirecionar('/alimentacao/planos');
        }
        $planoId = (int) $refeicao['pal_id'];
        $refeicao['ref_horario'] = $refeicao['ref_horario'] ? substr($refeicao['ref_horario'], 0, 5) : '';
        $itens = itens_da_refeicao((int) $id);
    }

    $plano = $planoId ? buscar_plano_alimentar((int) $planoId) : false;
    if (!$plano) {
        flash('erro', 'Selecione um plano alimentar válido.');
        redirecionar('/alimentacao/planos');
    }

    renderizar('alimentacao/refeicao_formulario', compact('plano', 'refeicao', 'itens'), $id ? 'Editar refeição' : 'Nova refeição');
}

function salvar_refeicao_plano(): void
{
    exigir_autenticacao();
    somente_post();
    validar_csrf();

    $id = filter_var($_POST['ref_id'] ?? null, FILTER_VALIDATE_INT) ?: null;
    $planoId = filter_var($_POST['pal_id'] ?? null, FILTER_VALIDATE_INT);
    $nome = trim((string) ($_POST['ref_nome'] ?? ''));
    $horarioInformado = trim((string) ($_POST['ref_horario'] ?? ''));
    $horario = normalizar_horario_refeicao($horarioInformado);
    $observacoes = trim((string) ($_POST['ref_observacoes'] ?? ''));
    $ordem = filter_var($_POST['ref_ordem'] ?? null, FILTER_VALIDATE_INT);
    $itens = itens_alimentares_do_post();
    $erros = [];

    $plano = $planoId ? buscar_plano_alimentar((int) $planoId) : false;
    if (!$plano) {
        $erros[] = 'Selecione um plano alimentar válido.';
    }
    if ($nome === '') {
        $erros[] = 'Informe o nome da refeição.';
    } elseif (mb_strlen($nome) > 80) {
        $erros[] = 'O nome da refeição deve ter no máximo 80 caracteres.';
    }
    if ($horarioInformado !== '' && $horario === null) {
        $erros[] = 'Informe um horário válido.';
    }
    if ($ordem === false || $ordem < 0 || $ordem > 999) {
        $erros[] = 'Informe uma ordem entre 0 e 999.';
    }
    if (!$itens) {
        $erros[] = 'Adicione ao menos um alimento à refeição.';
    }

    foreach ($itens as $indice => $item) {
        $numero = $indice + 1;
        if (mb_strlen($item['alimento']) > 160) {
            $erros[] = "O alimento {$numero} deve ter no máximo 160 caracteres.";
        }
        if ($item['quantidade_informada'] !== '' && ($item['quantidade'] === null || $item['quantidade'] <= 0)) {
            $erros[] = "A quantidade do alimento {$numero} deve ser maior que zero.";
        }
        if ($item['unidade'] !== '' && !array_key_exists($item['unidade'], UNIDADES_ALIMENTO)) {
            $erros[] = "Selecione uma unidade válida para o alimento {$numero}.";
        }
        if (mb_strlen($item['substituicoes']) > 2000) {
            $erros[] = "As substituições do alimento {$numero} devem ter no máximo 2.000 caracteres.";
        }
    }

    if ($erros) {
        foreach ($erros as $erro) {
            flash('erro', $erro);
        }
        $refeicao = $_POST;
        renderizar('alimentacao/refeicao_formulario', compact('plano', 'refeicao', 'itens'), $id ? 'Editar refeição' : 'Nova refeição');
        return;
    }

    $conexao = banco();
    $conexao->beginTransaction();

    try {
        $parametros = [
            'plano' => $planoId,
            'nome' => $nome,
            'horario' => $horario,
            'observacoes' => $observacoes ?: null,
            'ordem' => $ordem,
        ];

        if ($id) {
            $refeicaoExistente = buscar_refeicao_plano((int) $id);
            if (!$refeicaoExistente || (int) $refeicaoExistente['pal_id'] !== (int) $planoId) {
                throw new RuntimeException('Refeição não encontrada neste plano alimentar.');
            }
            $parametros['id'] = $id;
            $conexao->prepare(
                'UPDATE ref_refeicao_plano
                    SET pal_id = :plano, ref_nome = :nome, ref_horario = :horario,
                        ref_observacoes = :observacoes, ref_ordem = :ordem
                  WHERE ref_id = :id'
            )->execute($parametros);
            $conexao->prepare('DELETE FROM ita_item_alimentar WHERE ref_id = :refeicao')->execute(['refeicao' => $id]);
            $mensagem = 'Refeição atualizada com sucesso.';
        } else {
            $conexao->prepare(
                'INSERT INTO ref_refeicao_plano (pal_id, ref_nome, ref_horario, ref_observacoes, ref_ordem)
                 VALUES (:plano, :nome, :horario, :observacoes, :ordem)'
            )->execute($parametros);
            $id = (int) $conexao->lastInsertId();
            $mensagem = 'Refeição adicionada com sucesso.';
        }

        $inserirItem = $conexao->prepare(
            'INSERT INTO ita_item_alimentar
                (ref_id, ita_alimento, ita_quantidade, ita_unidade, ita_substituicoes, ita_ordem)
             VALUES (:refeicao, :alimento, :quantidade, :unidade, :substituicoes, :ordem)'
        );
        foreach ($itens as $indice => $item) {
            $inserirItem->execute([
                'refeicao' => $id,
                'alimento' => $item['alimento'],
                'quantidade' => $item['quantidade'],
                'unidade' => $item['unidade'] ?: null,
                'substituicoes' => $item['substituicoes'] ?: null,
                'ordem' => $indice,
            ]);
        }

        $conexao->commit();
    } catch (Throwable $erro) {
        if ($conexao->inTransaction()) {
            $conexao->rollBack();
        }
        throw $erro;
    }

    flash('sucesso', $mensagem);
    redirecionar('/alimentacao/planos/detalhes?id=' . $planoId);
}

function excluir_refeicao_plano(): void
{
    exigir_autenticacao();
    somente_post();
    validar_csrf();

    $id = filter_var($_POST['ref_id'] ?? null, FILTER_VALIDATE_INT);
    $refeicao = $id ? buscar_refeicao_plano((int) $id) : false;
    if (!$refeicao) {
        flash('erro', 'Refeição não encontrada.');
        redirecionar('/alimentacao/planos');
    }

    banco()->prepare('DELETE FROM ref_refeicao_plano WHERE ref_id = :id')->execute(['id' => $id]);
    flash('sucesso', 'Refeição excluída.');
    redirecionar('/alimentacao/planos/detalhes?id=' . $refeicao['pal_id']);
}

function buscar_plano_alimentar(int $id): array|false
{
    $consulta = banco()->prepare(
        'SELECT * FROM pal_plano_alimentar WHERE pal_id = :id AND usu_id = :usuario LIMIT 1'
    );
    $consulta->execute(['id' => $id, 'usuario' => usuario_atual()['id']]);
    return $consulta->fetch();
}

function buscar_plano_alimentar_vigente(): array|false
{
    $consulta = banco()->prepare(
        'SELECT * FROM pal_plano_alimentar
          WHERE usu_id = :usuario AND pal_ativo = 1
            AND pal_data_inicio <= CURDATE()
            AND (pal_data_fim IS NULL OR pal_data_fim >= CURDATE())
          ORDER BY pal_data_inicio DESC, pal_id DESC LIMIT 1'
    );
    $consulta->execute(['usuario' => usuario_atual()['id']]);
    return $consulta->fetch();
}

function buscar_refeicao_plano(int $id): array|false
{
    $consulta = banco()->prepare(
        'SELECT ref.* FROM ref_refeicao_plano ref
         INNER JOIN pal_plano_alimentar pal ON pal.pal_id = ref.pal_id
         WHERE ref.ref_id = :id AND pal.usu_id = :usuario LIMIT 1'
    );
    $consulta->execute(['id' => $id, 'usuario' => usuario_atual()['id']]);
    return $consulta->fetch();
}

function itens_da_refeicao(int $refeicaoId): array
{
    $consulta = banco()->prepare(
        'SELECT * FROM ita_item_alimentar WHERE ref_id = :refeicao ORDER BY ita_ordem, ita_id'
    );
    $consulta->execute(['refeicao' => $refeicaoId]);
    return $consulta->fetchAll();
}

function refeicoes_com_itens(int $planoId): array
{
    $consulta = banco()->prepare(
        'SELECT ref.* FROM ref_refeicao_plano ref
         INNER JOIN pal_plano_alimentar pal ON pal.pal_id = ref.pal_id
         WHERE ref.pal_id = :plano AND pal.usu_id = :usuario
         ORDER BY ref.ref_ordem, ref.ref_horario, ref.ref_id'
    );
    $consulta->execute(['plano' => $planoId, 'usuario' => usuario_atual()['id']]);
    $refeicoes = $consulta->fetchAll();

    foreach ($refeicoes as &$refeicao) {
        $refeicao['itens'] = itens_da_refeicao((int) $refeicao['ref_id']);
    }
    unset($refeicao);
    return $refeicoes;
}

function proxima_ordem_refeicao(int $planoId): int
{
    if ($planoId <= 0 || !buscar_plano_alimentar($planoId)) {
        return 0;
    }
    $consulta = banco()->prepare('SELECT COALESCE(MAX(ref_ordem), -1) + 1 FROM ref_refeicao_plano WHERE pal_id = :plano');
    $consulta->execute(['plano' => $planoId]);
    return (int) $consulta->fetchColumn();
}

function normalizar_horario_refeicao(string $horario): ?string
{
    if ($horario === '') {
        return null;
    }
    $objeto = DateTimeImmutable::createFromFormat('!H:i', $horario);
    return $objeto !== false && $objeto->format('H:i') === $horario ? $objeto->format('H:i:s') : null;
}

function itens_alimentares_do_post(): array
{
    $alimentos = is_array($_POST['ita_alimento'] ?? null) ? $_POST['ita_alimento'] : [];
    $quantidades = is_array($_POST['ita_quantidade'] ?? null) ? $_POST['ita_quantidade'] : [];
    $unidades = is_array($_POST['ita_unidade'] ?? null) ? $_POST['ita_unidade'] : [];
    $substituicoes = is_array($_POST['ita_substituicoes'] ?? null) ? $_POST['ita_substituicoes'] : [];
    $itens = [];

    foreach ($alimentos as $indice => $alimentoInformado) {
        $alimento = trim((string) $alimentoInformado);
        $quantidadeInformada = trim((string) ($quantidades[$indice] ?? ''));
        $unidade = trim((string) ($unidades[$indice] ?? ''));
        $substituicao = trim((string) ($substituicoes[$indice] ?? ''));

        if ($alimento === '' && $quantidadeInformada === '' && $unidade === '' && $substituicao === '') {
            continue;
        }

        $itens[] = [
            'alimento' => $alimento,
            'quantidade_informada' => $quantidadeInformada,
            'quantidade' => normalizar_decimal($quantidadeInformada),
            'unidade' => $unidade,
            'substituicoes' => $substituicao,
        ];
    }

    return array_values(array_filter($itens, static fn (array $item): bool => $item['alimento'] !== ''));
}

function situacao_plano_alimentar(array $plano): array
{
    if (!(bool) $plano['pal_ativo']) {
        return ['rotulo' => 'Inativo', 'classe' => 'text-bg-secondary'];
    }
    $hoje = date('Y-m-d');
    if ($plano['pal_data_inicio'] > $hoje) {
        return ['rotulo' => 'Agendado', 'classe' => 'text-bg-info'];
    }
    if ($plano['pal_data_fim'] && $plano['pal_data_fim'] < $hoje) {
        return ['rotulo' => 'Encerrado', 'classe' => 'text-bg-light border'];
    }
    return ['rotulo' => 'Vigente', 'classe' => 'text-bg-success'];
}

function listar_historico_alimentar(): void
{
    exigir_autenticacao();
    $consulta = banco()->prepare(
        "SELECT ral_data,
                COUNT(*) AS total_registros,
                SUM(ral_situacao = 'conforme') AS total_conformes,
                ROUND(AVG(CASE ral_situacao
                    WHEN 'conforme' THEN 100
                    WHEN 'substituida' THEN 75
                    WHEN 'parcial' THEN 50
                    ELSE 0 END)) AS aderencia
         FROM ral_registro_alimentar
         WHERE usu_id = :usuario
         GROUP BY ral_data
         ORDER BY ral_data DESC
         LIMIT 90"
    );
    $consulta->execute(['usuario' => usuario_atual()['id']]);
    renderizar('alimentacao/historico', ['dias' => $consulta->fetchAll()], 'Histórico alimentar');
}

function formulario_registro_alimentar(): void
{
    exigir_autenticacao();
    $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
    $refeicaoId = filter_input(INPUT_GET, 'refeicao', FILTER_VALIDATE_INT);
    $data = trim((string) ($_GET['data'] ?? date('Y-m-d')));
    $registro = [];
    $itensRealizados = [];

    if ($id) {
        $registro = buscar_registro_alimentar((int) $id);
        if (!$registro) {
            flash('erro', 'Registro alimentar não encontrado.');
            redirecionar('/alimentacao');
        }
        $data = $registro['ral_data'];
        $refeicaoId = $registro['ref_id'] ? (int) $registro['ref_id'] : null;
        $registro['ral_horario'] = $registro['ral_horario'] ? substr($registro['ral_horario'], 0, 5) : '';
        $itensRealizados = itens_realizados_do_registro((int) $id);
        $planejado = decodificar_snapshot_alimentar($registro['ral_planejado_snapshot']);
        $refeicao = $refeicaoId ? buscar_refeicao_plano((int) $refeicaoId) : false;
    } else {
        if (!data_iso_valida($data) || $data > date('Y-m-d')) {
            flash('erro', 'A data do registro alimentar é inválida.');
            redirecionar('/alimentacao');
        }
        $refeicao = $refeicaoId ? buscar_refeicao_plano((int) $refeicaoId) : false;
        if (!$refeicao) {
            flash('erro', 'Refeição planejada não encontrada.');
            redirecionar('/alimentacao?data=' . $data);
        }

        $existente = buscar_registro_refeicao_data((int) $refeicaoId, $data);
        if ($existente) {
            redirecionar('/alimentacao/registros/formulario?id=' . $existente['ral_id']);
        }

        $itensPlanejados = itens_da_refeicao((int) $refeicaoId);
        $planejado = montar_snapshot_alimentar($refeicao, $itensPlanejados);
        $registro = [
            'ref_id' => $refeicaoId,
            'ral_data' => $data,
            'ral_horario' => $data === date('Y-m-d') ? date('H:i') : ($refeicao['ref_horario'] ? substr($refeicao['ref_horario'], 0, 5) : ''),
            'ral_situacao' => 'conforme',
        ];
        foreach ($itensPlanejados as $item) {
            $itensRealizados[] = [
                'alimento' => $item['ita_alimento'],
                'quantidade_informada' => $item['ita_quantidade'],
                'unidade' => $item['ita_unidade'] ?? '',
                'observacoes' => '',
            ];
        }
    }

    renderizar('alimentacao/registro_formulario', compact('registro', 'refeicao', 'planejado', 'itensRealizados'), $id ? 'Editar refeição realizada' : 'Registrar refeição realizada');
}

function salvar_registro_alimentar(): void
{
    exigir_autenticacao();
    somente_post();
    validar_csrf();

    $id = filter_var($_POST['ral_id'] ?? null, FILTER_VALIDATE_INT) ?: null;
    $registroExistente = $id ? buscar_registro_alimentar((int) $id) : false;
    if ($id && !$registroExistente) {
        flash('erro', 'Registro alimentar não encontrado.');
        redirecionar('/alimentacao');
    }

    $refeicaoId = $registroExistente
        ? ($registroExistente['ref_id'] ? (int) $registroExistente['ref_id'] : null)
        : (filter_var($_POST['ref_id'] ?? null, FILTER_VALIDATE_INT) ?: null);
    $refeicao = $refeicaoId ? buscar_refeicao_plano((int) $refeicaoId) : false;
    $data = trim((string) ($_POST['ral_data'] ?? ''));
    $horarioInformado = trim((string) ($_POST['ral_horario'] ?? ''));
    $horario = normalizar_horario_refeicao($horarioInformado);
    $situacao = trim((string) ($_POST['ral_situacao'] ?? ''));
    $fomeInformada = trim((string) ($_POST['ral_fome_antes'] ?? ''));
    $saciedadeInformada = trim((string) ($_POST['ral_saciedade_depois'] ?? ''));
    $fome = $fomeInformada === '' ? null : filter_var($fomeInformada, FILTER_VALIDATE_INT);
    $saciedade = $saciedadeInformada === '' ? null : filter_var($saciedadeInformada, FILTER_VALIDATE_INT);
    $observacoes = trim((string) ($_POST['ral_observacoes'] ?? ''));
    $itensRealizados = itens_realizados_do_post();
    $erros = [];

    if (!$registroExistente && !$refeicao) {
        $erros[] = 'Refeição planejada não encontrada.';
    }
    if (!data_iso_valida($data) || $data > date('Y-m-d')) {
        $erros[] = 'Informe uma data válida, que não esteja no futuro.';
    }
    if ($horarioInformado !== '' && $horario === null) {
        $erros[] = 'Informe um horário válido.';
    }
    if (!array_key_exists($situacao, SITUACOES_REGISTRO_ALIMENTAR)) {
        $erros[] = 'Selecione uma situação válida para a refeição.';
    }
    if ($fome !== null && ($fome === false || $fome < 0 || $fome > 10)) {
        $erros[] = 'A fome antes da refeição deve estar entre 0 e 10.';
    }
    if ($saciedade !== null && ($saciedade === false || $saciedade < 0 || $saciedade > 10)) {
        $erros[] = 'A saciedade após a refeição deve estar entre 0 e 10.';
    }
    if (mb_strlen($observacoes) > 2000) {
        $erros[] = 'As observações devem ter no máximo 2.000 caracteres.';
    }

    if ($situacao === 'nao_realizada') {
        $itensRealizados = [];
    } elseif (!$itensRealizados) {
        $erros[] = 'Adicione ao menos um alimento efetivamente consumido.';
    }

    foreach ($itensRealizados as $indice => $item) {
        $numero = $indice + 1;
        if (mb_strlen($item['alimento']) > 160) {
            $erros[] = "O alimento realizado {$numero} deve ter no máximo 160 caracteres.";
        }
        if ($item['quantidade_informada'] !== '' && ($item['quantidade'] === null || $item['quantidade'] <= 0)) {
            $erros[] = "A quantidade do alimento realizado {$numero} deve ser maior que zero.";
        }
        if ($item['unidade'] !== '' && !array_key_exists($item['unidade'], UNIDADES_ALIMENTO)) {
            $erros[] = "Selecione uma unidade válida para o alimento realizado {$numero}.";
        }
        if (mb_strlen($item['observacoes']) > 500) {
            $erros[] = "A observação do alimento realizado {$numero} deve ter no máximo 500 caracteres.";
        }
    }

    if ($refeicaoId && data_iso_valida($data)) {
        $duplicado = buscar_registro_refeicao_data((int) $refeicaoId, $data);
        if ($duplicado && (int) $duplicado['ral_id'] !== (int) $id) {
            $erros[] = 'Esta refeição já possui um registro na data informada.';
        }
    }

    $planejado = $registroExistente
        ? decodificar_snapshot_alimentar($registroExistente['ral_planejado_snapshot'])
        : ($refeicao ? montar_snapshot_alimentar($refeicao, itens_da_refeicao((int) $refeicaoId)) : []);

    if ($erros) {
        foreach ($erros as $erro) {
            flash('erro', $erro);
        }
        $registro = $_POST;
        renderizar('alimentacao/registro_formulario', compact('registro', 'refeicao', 'planejado', 'itensRealizados'), $id ? 'Editar refeição realizada' : 'Registrar refeição realizada');
        return;
    }

    $conexao = banco();
    $conexao->beginTransaction();
    try {
        $parametros = [
            'data' => $data,
            'horario' => $horario,
            'situacao' => $situacao,
            'fome' => $fome,
            'saciedade' => $saciedade,
            'observacoes' => $observacoes ?: null,
        ];

        if ($id) {
            $parametros['id'] = $id;
            $conexao->prepare(
                'UPDATE ral_registro_alimentar
                    SET ral_data = :data, ral_horario = :horario, ral_situacao = :situacao,
                        ral_fome_antes = :fome, ral_saciedade_depois = :saciedade,
                        ral_observacoes = :observacoes
                  WHERE ral_id = :id'
            )->execute($parametros);
            $conexao->prepare('DELETE FROM ira_item_realizado WHERE ral_id = :registro')->execute(['registro' => $id]);
            $mensagem = 'Refeição realizada atualizada com sucesso.';
        } else {
            $parametros += [
                'usuario' => usuario_atual()['id'],
                'plano' => $refeicao['pal_id'],
                'refeicao' => $refeicaoId,
                'nome' => $refeicao['ref_nome'],
                'snapshot' => json_encode($planejado, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR),
            ];
            $conexao->prepare(
                'INSERT INTO ral_registro_alimentar
                    (usu_id, pal_id, ref_id, ral_data, ral_horario, ral_situacao,
                     ral_refeicao_nome, ral_planejado_snapshot, ral_fome_antes,
                     ral_saciedade_depois, ral_observacoes)
                 VALUES (:usuario, :plano, :refeicao, :data, :horario, :situacao,
                         :nome, :snapshot, :fome, :saciedade, :observacoes)'
            )->execute($parametros);
            $id = (int) $conexao->lastInsertId();
            $mensagem = 'Refeição realizada registrada com sucesso.';
        }

        $inserirItem = $conexao->prepare(
            'INSERT INTO ira_item_realizado
                (ral_id, ira_alimento, ira_quantidade, ira_unidade, ira_observacoes, ira_ordem)
             VALUES (:registro, :alimento, :quantidade, :unidade, :observacoes, :ordem)'
        );
        foreach ($itensRealizados as $indice => $item) {
            $inserirItem->execute([
                'registro' => $id,
                'alimento' => $item['alimento'],
                'quantidade' => $item['quantidade'],
                'unidade' => $item['unidade'] ?: null,
                'observacoes' => $item['observacoes'] ?: null,
                'ordem' => $indice,
            ]);
        }
        $conexao->commit();
    } catch (Throwable $erro) {
        if ($conexao->inTransaction()) {
            $conexao->rollBack();
        }
        throw $erro;
    }

    flash('sucesso', $mensagem);
    redirecionar('/alimentacao?data=' . $data);
}

function excluir_registro_alimentar(): void
{
    exigir_autenticacao();
    somente_post();
    validar_csrf();
    $id = filter_var($_POST['ral_id'] ?? null, FILTER_VALIDATE_INT);
    $registro = $id ? buscar_registro_alimentar((int) $id) : false;
    if (!$registro) {
        flash('erro', 'Registro alimentar não encontrado.');
        redirecionar('/alimentacao');
    }
    banco()->prepare('DELETE FROM ral_registro_alimentar WHERE ral_id = :id')->execute(['id' => $id]);
    flash('sucesso', 'Registro da refeição excluído.');
    redirecionar('/alimentacao?data=' . $registro['ral_data']);
}

function buscar_plano_alimentar_por_data(string $data): array|false
{
    $consulta = banco()->prepare(
        'SELECT * FROM pal_plano_alimentar
          WHERE usu_id = :usuario
            AND pal_data_inicio <= :data_inicio
            AND (pal_data_fim IS NULL OR pal_data_fim >= :data_fim)
            AND (pal_ativo = 1 OR :data_historica < CURDATE())
          ORDER BY pal_ativo DESC, pal_data_inicio DESC, pal_id DESC LIMIT 1'
    );
    $consulta->execute([
        'usuario' => usuario_atual()['id'],
        'data_inicio' => $data,
        'data_fim' => $data,
        'data_historica' => $data,
    ]);
    return $consulta->fetch();
}

function buscar_registro_alimentar(int $id): array|false
{
    $consulta = banco()->prepare(
        'SELECT * FROM ral_registro_alimentar WHERE ral_id = :id AND usu_id = :usuario LIMIT 1'
    );
    $consulta->execute(['id' => $id, 'usuario' => usuario_atual()['id']]);
    return $consulta->fetch();
}

function buscar_registro_refeicao_data(int $refeicaoId, string $data): array|false
{
    $consulta = banco()->prepare(
        'SELECT * FROM ral_registro_alimentar
         WHERE ref_id = :refeicao AND ral_data = :data AND usu_id = :usuario LIMIT 1'
    );
    $consulta->execute(['refeicao' => $refeicaoId, 'data' => $data, 'usuario' => usuario_atual()['id']]);
    return $consulta->fetch();
}

function itens_realizados_do_registro(int $registroId): array
{
    $consulta = banco()->prepare(
        'SELECT * FROM ira_item_realizado WHERE ral_id = :registro ORDER BY ira_ordem, ira_id'
    );
    $consulta->execute(['registro' => $registroId]);
    return $consulta->fetchAll();
}

function registros_alimentares_por_data(int $planoId, string $data): array
{
    $consulta = banco()->prepare(
        'SELECT * FROM ral_registro_alimentar
         WHERE pal_id = :plano AND ral_data = :data AND usu_id = :usuario
         ORDER BY ral_id'
    );
    $consulta->execute(['plano' => $planoId, 'data' => $data, 'usuario' => usuario_atual()['id']]);
    $registros = [];
    foreach ($consulta->fetchAll() as $registro) {
        $registro['itens'] = itens_realizados_do_registro((int) $registro['ral_id']);
        $registro['planejado'] = decodificar_snapshot_alimentar($registro['ral_planejado_snapshot']);
        if ($registro['ref_id']) {
            $registros[(int) $registro['ref_id']] = $registro;
        } else {
            $registros['sem_refeicao'][] = $registro;
        }
    }
    return $registros;
}

function montar_snapshot_alimentar(array $refeicao, array $itens): array
{
    return [
        'nome' => $refeicao['ref_nome'],
        'horario' => $refeicao['ref_horario'],
        'observacoes' => $refeicao['ref_observacoes'],
        'itens' => array_map(static fn (array $item): array => [
            'alimento' => $item['ita_alimento'],
            'quantidade' => $item['ita_quantidade'],
            'unidade' => $item['ita_unidade'],
            'substituicoes' => $item['ita_substituicoes'],
        ], $itens),
    ];
}

function decodificar_snapshot_alimentar(?string $snapshot): array
{
    if (!$snapshot) {
        return ['nome' => '', 'horario' => null, 'observacoes' => null, 'itens' => []];
    }
    try {
        $dados = json_decode($snapshot, true, 512, JSON_THROW_ON_ERROR);
        return is_array($dados) ? $dados : [];
    } catch (JsonException) {
        return ['nome' => '', 'horario' => null, 'observacoes' => null, 'itens' => []];
    }
}

function itens_realizados_do_post(): array
{
    $alimentos = is_array($_POST['ira_alimento'] ?? null) ? $_POST['ira_alimento'] : [];
    $quantidades = is_array($_POST['ira_quantidade'] ?? null) ? $_POST['ira_quantidade'] : [];
    $unidades = is_array($_POST['ira_unidade'] ?? null) ? $_POST['ira_unidade'] : [];
    $observacoes = is_array($_POST['ira_observacoes'] ?? null) ? $_POST['ira_observacoes'] : [];
    $itens = [];

    foreach ($alimentos as $indice => $alimentoInformado) {
        $alimento = trim((string) $alimentoInformado);
        $quantidadeInformada = trim((string) ($quantidades[$indice] ?? ''));
        $unidade = trim((string) ($unidades[$indice] ?? ''));
        $observacao = trim((string) ($observacoes[$indice] ?? ''));
        if ($alimento === '' && $quantidadeInformada === '' && $unidade === '' && $observacao === '') {
            continue;
        }
        if ($alimento === '') {
            continue;
        }
        $itens[] = [
            'alimento' => $alimento,
            'quantidade_informada' => $quantidadeInformada,
            'quantidade' => normalizar_decimal($quantidadeInformada),
            'unidade' => $unidade,
            'observacoes' => $observacao,
        ];
    }
    return $itens;
}

function resumo_aderencia_alimentar(array $refeicoes): array
{
    $total = count($refeicoes);
    $registradas = 0;
    $pontos = 0;
    foreach ($refeicoes as $refeicao) {
        if (empty($refeicao['registro'])) {
            continue;
        }
        $registradas++;
        $situacao = $refeicao['registro']['ral_situacao'];
        $pontos += SITUACOES_REGISTRO_ALIMENTAR[$situacao]['pontuacao'] ?? 0;
    }
    return [
        'total' => $total,
        'registradas' => $registradas,
        'aderencia' => $registradas > 0 ? (int) round($pontos / $registradas) : null,
    ];
}
