<?php

declare(strict_types=1);

function exibir_treinos(): void
{
    exigir_autenticacao();
    $data = trim((string) ($_GET['data'] ?? date('Y-m-d')));
    if (!data_iso_valida($data)) { flash('erro', 'Informe uma data válida para consultar os treinos.'); redirecionar('/treinos'); }
    $plano = buscar_plano_treino_por_data($data);
    $treinos = [];
    $alternativas = [];
    $resumo = ['planejados' => 0, 'registrados' => 0, 'series' => 0, 'volume' => 0.0];
    if ($plano) {
        $todos = treinos_planejados_do_plano((int) $plano['ptr_id']);
        $recomendado = treino_recomendado_para_data($plano, $todos, $data);
        $registros = registros_treino_por_data((int) $plano['ptr_id'], $data);
        if ($recomendado) {
            $recomendado['registro'] = $registros[(int) $recomendado['trp_id']] ?? null;
            $recomendado['eh_do_dia'] = $data === date('Y-m-d');
            $recomendado['eh_previsao'] = $data > date('Y-m-d');
            $treinos = [$recomendado];
            $resumo['planejados'] = 1;
        }
        foreach ($todos as $treino) {
            if (!$recomendado || (int) $treino['trp_id'] !== (int) $recomendado['trp_id']) { $alternativas[] = $treino; }
        }
        foreach ($registros as $registro) {
            $resumo['registrados']++;
            if ($registro['trr_situacao'] !== 'nao_realizado') { $dados = resumo_treino_realizado($registro, $registro['exercicios']); $resumo['series'] += $dados['series_concluidas']; $resumo['volume'] += $dados['volume']; }
        }
    }
    $podeRegistrar = $data === date('Y-m-d');
    renderizar('treinos/hoje', compact('plano', 'treinos', 'alternativas', 'data', 'resumo', 'podeRegistrar'), 'Treinos');
}

function listar_exercicios(): void
{
    exigir_autenticacao();
    renderizar('treinos/exercicios_lista', ['exercicios' => exercicios_do_usuario()], 'Exercícios');
}

function formulario_exercicio(): void
{
    exigir_autenticacao();
    $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
    $exercicio = ['exe_tipo' => 'forca', 'exe_ativo' => 1];

    if ($id) {
        $exercicio = buscar_exercicio((int) $id);

        if (!$exercicio) {
            flash('erro', 'Exercício não encontrado.');
            redirecionar('/treinos/exercicios');
        }
    }

    renderizar('treinos/exercicio_formulario', compact('exercicio'), $id ? 'Editar exercício' : 'Novo exercício');
}

function salvar_exercicio(): void
{
    exigir_autenticacao();
    somente_post();
    validar_csrf();

    $id = filter_var($_POST['exe_id'] ?? null, FILTER_VALIDATE_INT) ?: null;
    $nome = trim((string) ($_POST['exe_nome'] ?? ''));
    $grupo = trim((string) ($_POST['exe_grupo_muscular'] ?? ''));
    $tipo = trim((string) ($_POST['exe_tipo'] ?? ''));
    $observacoes = trim((string) ($_POST['exe_observacoes'] ?? ''));
    $ativo = isset($_POST['exe_ativo']) ? 1 : 0;
    $erros = [];

    if ($nome === '' || mb_strlen($nome) > 120) {
        $erros[] = 'Informe um nome de exercício com até 120 caracteres.';
    }

    if (mb_strlen($grupo) > 80) {
        $erros[] = 'O grupo muscular deve ter no máximo 80 caracteres.';
    }

    if (!array_key_exists($tipo, TIPOS_EXERCICIO)) {
        $erros[] = 'Selecione um tipo de exercício válido.';
    }

    if ($erros) {
        foreach ($erros as $erro) {
            flash('erro', $erro);
        }

        $exercicio = $_POST;
        $exercicio['exe_ativo'] = $ativo;
        renderizar('treinos/exercicio_formulario', compact('exercicio'), $id ? 'Editar exercício' : 'Novo exercício');
        return;
    }

    $parametros = [
        'usuario' => usuario_atual()['id'],
        'nome' => $nome,
        'grupo' => $grupo ?: null,
        'tipo' => $tipo,
        'observacoes' => $observacoes ?: null,
        'ativo' => $ativo,
    ];

    if ($id) {
        if (!buscar_exercicio((int) $id)) {
            flash('erro', 'Exercício não encontrado.');
            redirecionar('/treinos/exercicios');
        }

        $parametros['id'] = $id;
        $sql = 'UPDATE exe_exercicio
                   SET exe_nome = :nome, exe_grupo_muscular = :grupo, exe_tipo = :tipo,
                       exe_observacoes = :observacoes, exe_ativo = :ativo
                 WHERE exe_id = :id AND usu_id = :usuario';
        $mensagem = 'Exercício atualizado com sucesso.';
    } else {
        $sql = 'INSERT INTO exe_exercicio
                    (usu_id, exe_nome, exe_grupo_muscular, exe_tipo, exe_observacoes, exe_ativo)
                VALUES (:usuario, :nome, :grupo, :tipo, :observacoes, :ativo)';
        $mensagem = 'Exercício criado com sucesso.';
    }

    banco()->prepare($sql)->execute($parametros);
    flash('sucesso', $mensagem);
    redirecionar('/treinos/exercicios');
}

function listar_planos_treino(): void
{
    exigir_autenticacao();
    $consulta = banco()->prepare(
        'SELECT ptr.*,
                (SELECT COUNT(*) FROM trp_treino_planejado trp WHERE trp.ptr_id = ptr.ptr_id) AS total_treinos,
                (SELECT COUNT(*)
                   FROM exp_exercicio_planejado exp
                   INNER JOIN trp_treino_planejado trp ON trp.trp_id = exp.trp_id
                  WHERE trp.ptr_id = ptr.ptr_id) AS total_exercicios
           FROM ptr_plano_treino ptr
          WHERE ptr.usu_id = :usuario
          ORDER BY ptr.ptr_ativo DESC, ptr.ptr_data_inicio DESC, ptr.ptr_id DESC'
    );
    $consulta->execute(['usuario' => usuario_atual()['id']]);
    renderizar('treinos/planos_lista', ['planos' => $consulta->fetchAll()], 'Planos de treino');
}

function formulario_plano_treino(): void
{
    exigir_autenticacao();
    $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
    $plano = ['ptr_data_inicio' => date('Y-m-d'), 'ptr_ativo' => 1];

    if ($id) {
        $plano = buscar_plano_treino((int) $id);

        if (!$plano) {
            flash('erro', 'Plano de treino não encontrado.');
            redirecionar('/treinos/planos');
        }
    }

    renderizar('treinos/plano_formulario', compact('plano'), $id ? 'Editar plano de treino' : 'Novo plano de treino');
}

function salvar_plano_treino(): void
{
    exigir_autenticacao();
    somente_post();
    validar_csrf();

    $id = filter_var($_POST['ptr_id'] ?? null, FILTER_VALIDATE_INT) ?: null;
    $nome = trim((string) ($_POST['ptr_nome'] ?? ''));
    $objetivo = trim((string) ($_POST['ptr_objetivo'] ?? ''));
    $inicio = trim((string) ($_POST['ptr_data_inicio'] ?? ''));
    $fim = trim((string) ($_POST['ptr_data_fim'] ?? ''));
    $orientacoes = trim((string) ($_POST['ptr_orientacoes'] ?? ''));
    $ativo = isset($_POST['ptr_ativo']) ? 1 : 0;
    $erros = [];

    if ($nome === '' || mb_strlen($nome) > 120) {
        $erros[] = 'Informe um nome de plano com até 120 caracteres.';
    }

    if (mb_strlen($objetivo) > 160) {
        $erros[] = 'O objetivo deve ter no máximo 160 caracteres.';
    }

    if (!data_iso_valida($inicio)) {
        $erros[] = 'Informe uma data inicial válida.';
    }

    if ($fim !== '' && !data_iso_valida($fim)) {
        $erros[] = 'Informe uma data final válida.';
    } elseif ($fim !== '' && data_iso_valida($inicio) && $fim < $inicio) {
        $erros[] = 'A data final não pode ser anterior à inicial.';
    }

    if ($erros) {
        foreach ($erros as $erro) {
            flash('erro', $erro);
        }

        $plano = $_POST;
        $plano['ptr_ativo'] = $ativo;
        renderizar('treinos/plano_formulario', compact('plano'), $id ? 'Editar plano de treino' : 'Novo plano de treino');
        return;
    }

    $parametros = [
        'usuario' => usuario_atual()['id'],
        'nome' => $nome,
        'objetivo' => $objetivo ?: null,
        'inicio' => $inicio,
        'fim' => $fim ?: null,
        'orientacoes' => $orientacoes ?: null,
        'ativo' => $ativo,
    ];

    if ($id) {
        if (!buscar_plano_treino((int) $id)) {
            flash('erro', 'Plano de treino não encontrado.');
            redirecionar('/treinos/planos');
        }

        $parametros['id'] = $id;
        $sql = 'UPDATE ptr_plano_treino
                   SET ptr_nome = :nome, ptr_objetivo = :objetivo, ptr_data_inicio = :inicio,
                       ptr_data_fim = :fim, ptr_orientacoes = :orientacoes, ptr_ativo = :ativo
                 WHERE ptr_id = :id AND usu_id = :usuario';
        $mensagem = 'Plano de treino atualizado com sucesso.';
    } else {
        $sql = 'INSERT INTO ptr_plano_treino
                    (usu_id, ptr_nome, ptr_objetivo, ptr_data_inicio, ptr_data_fim, ptr_orientacoes, ptr_ativo)
                VALUES (:usuario, :nome, :objetivo, :inicio, :fim, :orientacoes, :ativo)';
        $mensagem = 'Plano criado. Agora adicione seus treinos.';
    }

    $conexao = banco();
    $conexao->prepare($sql)->execute($parametros);
    $id = $id ?: (int) $conexao->lastInsertId();
    flash('sucesso', $mensagem);
    redirecionar('/treinos/planos/detalhes?id=' . $id);
}

function detalhar_plano_treino(): void
{
    exigir_autenticacao();
    $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
    $plano = $id ? buscar_plano_treino((int) $id) : false;

    if (!$plano) {
        flash('erro', 'Plano de treino não encontrado.');
        redirecionar('/treinos/planos');
    }

    $treinos = treinos_planejados_do_plano((int) $id);
    renderizar('treinos/plano_detalhes', compact('plano', 'treinos'), $plano['ptr_nome']);
}

function formulario_treino_planejado(): void
{
    exigir_autenticacao();
    $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
    $planoId = filter_input(INPUT_GET, 'plano', FILTER_VALIDATE_INT);
    $treino = ['ptr_id' => $planoId ?: '', 'trp_ordem' => proxima_ordem_treino((int) ($planoId ?: 0))];
    $itens = [];

    if ($id) {
        $treino = buscar_treino_planejado((int) $id);

        if (!$treino) {
            flash('erro', 'Treino planejado não encontrado.');
            redirecionar('/treinos/planos');
        }

        $planoId = (int) $treino['ptr_id'];
        $itens = exercicios_planejados_do_treino((int) $id);
    }

    $plano = $planoId ? buscar_plano_treino((int) $planoId) : false;

    if (!$plano) {
        flash('erro', 'Selecione um plano de treino válido.');
        redirecionar('/treinos/planos');
    }

    $exercicios = exercicios_do_usuario();
    renderizar('treinos/treino_formulario', compact('plano', 'treino', 'itens', 'exercicios'), $id ? 'Editar treino' : 'Novo treino');
}

function salvar_treino_planejado(): void
{
    exigir_autenticacao();
    somente_post();
    validar_csrf();

    $id = filter_var($_POST['trp_id'] ?? null, FILTER_VALIDATE_INT) ?: null;
    $planoId = filter_var($_POST['ptr_id'] ?? null, FILTER_VALIDATE_INT);
    $nome = trim((string) ($_POST['trp_nome'] ?? ''));
    $diaInformado = trim((string) ($_POST['trp_dia_semana'] ?? ''));
    $dia = $diaInformado === '' ? null : filter_var($diaInformado, FILTER_VALIDATE_INT);
    $ordem = filter_var($_POST['trp_ordem'] ?? null, FILTER_VALIDATE_INT);
    $orientacoes = trim((string) ($_POST['trp_orientacoes'] ?? ''));
    $itens = exercicios_planejados_do_post();
    $plano = $planoId ? buscar_plano_treino((int) $planoId) : false;
    $erros = [];

    if (!$plano) {
        $erros[] = 'Selecione um plano de treino válido.';
    }

    if ($nome === '' || mb_strlen($nome) > 100) {
        $erros[] = 'Informe um nome de treino com até 100 caracteres.';
    }

    if ($diaInformado !== '' && ($dia === false || !array_key_exists((int) $dia, DIAS_SEMANA))) {
        $erros[] = 'Selecione um dia da semana válido.';
    }

    if ($ordem === false || $ordem < 0 || $ordem > 999) {
        $erros[] = 'Informe uma ordem entre 0 e 999.';
    }

    if (!$itens) {
        $erros[] = 'Adicione pelo menos um exercício ao treino.';
    }

    foreach ($itens as $indice => $item) {
        $numero = $indice + 1;
        $exercicio = $item['exe_id'] ? buscar_exercicio((int) $item['exe_id']) : false;

        if (!$exercicio) {
            $erros[] = "Selecione um exercício válido no item {$numero}.";
        }

        if ($item['series'] < 1 || $item['series'] > 99) {
            $erros[] = "As séries do item {$numero} devem estar entre 1 e 99.";
        }

        if ($item['repeticoes_min'] !== null && $item['repeticoes_min'] < 1) {
            $erros[] = "As repetições mínimas do item {$numero} devem ser positivas.";
        }

        if ($item['repeticoes_max'] !== null && $item['repeticoes_max'] < ($item['repeticoes_min'] ?? 1)) {
            $erros[] = "As repetições máximas do item {$numero} devem ser maiores ou iguais às mínimas.";
        }

        foreach (['carga_alvo', 'distancia_km'] as $campo) {
            if ($item[$campo . '_informada'] !== '' && ($item[$campo] === null || $item[$campo] < 0)) {
                $erros[] = "Informe um valor válido no item {$numero}.";
            }
        }

        if ($item['descanso_segundos'] !== null && ($item['descanso_segundos'] < 0 || $item['descanso_segundos'] > 65535)) {
            $erros[] = "Informe um descanso válido no item {$numero}.";
        }

        if ($item['duracao_segundos'] !== null && ($item['duracao_segundos'] < 0 || $item['duracao_segundos'] > 604800)) {
            $erros[] = "Informe uma duração válida no item {$numero}.";
        }

        if (mb_strlen($item['observacoes']) > 500) {
            $erros[] = "As observações do item {$numero} devem ter no máximo 500 caracteres.";
        }
    }

    if ($erros) {
        foreach ($erros as $erro) {
            flash('erro', $erro);
        }

        $treino = $_POST;
        $exercicios = exercicios_do_usuario();
        renderizar('treinos/treino_formulario', compact('plano', 'treino', 'itens', 'exercicios'), $id ? 'Editar treino' : 'Novo treino');
        return;
    }

    $conexao = banco();
    $conexao->beginTransaction();

    try {
        $parametros = [
            'plano' => $planoId,
            'nome' => $nome,
            'dia' => $dia === false ? null : $dia,
            'ordem' => $ordem,
            'orientacoes' => $orientacoes ?: null,
        ];

        if ($id) {
            $existente = buscar_treino_planejado((int) $id);

            if (!$existente || (int) $existente['ptr_id'] !== (int) $planoId) {
                throw new RuntimeException('Treino não encontrado neste plano.');
            }

            $parametros['id'] = $id;
            $conexao->prepare(
                'UPDATE trp_treino_planejado
                    SET ptr_id = :plano, trp_nome = :nome, trp_dia_semana = :dia,
                        trp_ordem = :ordem, trp_orientacoes = :orientacoes
                  WHERE trp_id = :id'
            )->execute($parametros);
            $conexao->prepare('DELETE FROM exp_exercicio_planejado WHERE trp_id = :treino')->execute(['treino' => $id]);
            $mensagem = 'Treino atualizado com sucesso.';
        } else {
            $conexao->prepare(
                'INSERT INTO trp_treino_planejado
                    (ptr_id, trp_nome, trp_dia_semana, trp_ordem, trp_orientacoes)
                 VALUES (:plano, :nome, :dia, :ordem, :orientacoes)'
            )->execute($parametros);
            $id = (int) $conexao->lastInsertId();
            $mensagem = 'Treino adicionado com sucesso.';
        }

        $inserir = $conexao->prepare(
            'INSERT INTO exp_exercicio_planejado
                (trp_id, exe_id, exp_series, exp_repeticoes_min, exp_repeticoes_max,
                 exp_carga_alvo, exp_descanso_segundos, exp_duracao_segundos,
                 exp_distancia_km, exp_observacoes, exp_ordem)
             VALUES (:treino, :exercicio, :series, :rep_min, :rep_max, :carga,
                     :descanso, :duracao, :distancia, :observacoes, :ordem)'
        );

        foreach ($itens as $indice => $item) {
            $inserir->execute([
                'treino' => $id,
                'exercicio' => $item['exe_id'],
                'series' => $item['series'],
                'rep_min' => $item['repeticoes_min'],
                'rep_max' => $item['repeticoes_max'],
                'carga' => $item['carga_alvo'],
                'descanso' => $item['descanso_segundos'],
                'duracao' => $item['duracao_segundos'],
                'distancia' => $item['distancia_km'],
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
    redirecionar('/treinos/planos/detalhes?id=' . $planoId);
}

function excluir_treino_planejado(): void
{
    exigir_autenticacao();
    somente_post();
    validar_csrf();

    $id = filter_var($_POST['trp_id'] ?? null, FILTER_VALIDATE_INT);
    $treino = $id ? buscar_treino_planejado((int) $id) : false;

    if (!$treino) {
        flash('erro', 'Treino planejado não encontrado.');
        redirecionar('/treinos/planos');
    }

    banco()->prepare('DELETE FROM trp_treino_planejado WHERE trp_id = :id')->execute(['id' => $id]);
    flash('sucesso', 'Treino planejado excluído.');
    redirecionar('/treinos/planos/detalhes?id=' . $treino['ptr_id']);
}

function formulario_treino_realizado(): void
{
    exigir_autenticacao();
    $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
    $treinoId = filter_input(INPUT_GET, 'treino', FILTER_VALIDATE_INT);
    $data = trim((string) ($_GET['data'] ?? date('Y-m-d')));
    $registro = [];
    $planejado = [];
    $exerciciosRealizados = [];

    if ($id) {
        $registro = buscar_treino_realizado((int) $id);

        if (!$registro) {
            flash('erro', 'Treino realizado não encontrado.');
            redirecionar('/treinos');
        }

        $planejado = decodificar_snapshot_treino($registro['trr_planejado_snapshot']);
        $registro['planejado'] = $planejado;
        $registro['trr_hora_inicio'] = $registro['trr_hora_inicio'] ? substr($registro['trr_hora_inicio'], 0, 5) : '';
        $registro['trr_hora_fim'] = $registro['trr_hora_fim'] ? substr($registro['trr_hora_fim'], 0, 5) : '';
        $exerciciosRealizados = exercicios_realizados_do_treino((int) $id);

        if (!$exerciciosRealizados) {
            $exerciciosRealizados = preparar_exercicios_realizados($planejado);
        }
    } else {
        if (!data_iso_valida($data) || $data !== date('Y-m-d')) {
            flash('erro', 'O treino só pode ser iniciado na data de hoje.');
            redirecionar('/treinos');
        }

        $treino = $treinoId ? buscar_treino_planejado((int) $treinoId) : false;

        if (!$treino) {
            flash('erro', 'Selecione um treino planejado válido.');
            redirecionar('/treinos');
        }

        $existente = buscar_treino_realizado_por_data((int) $treinoId, $data);

        if ($existente) {
            redirecionar('/treinos/realizados/formulario?id=' . $existente['trr_id']);
        }

        $itens = exercicios_planejados_do_treino((int) $treinoId);
        $planejado = montar_snapshot_treino($treino, $itens);
        $registro = [
            'ptr_id' => $treino['ptr_id'],
            'trp_id' => $treino['trp_id'],
            'trr_data' => $data,
            'trr_situacao' => 'em_andamento',
            'trr_treino_nome' => $treino['trp_nome'],
        ];

        $exerciciosRealizados = preparar_exercicios_realizados($planejado);
    }

    $exercicios = exercicios_do_usuario();
    renderizar('treinos/realizado_formulario', compact('registro', 'planejado', 'exerciciosRealizados', 'exercicios'), $id ? 'Editar treino realizado' : 'Registrar treino');
}

function salvar_treino_realizado(): void
{
    exigir_autenticacao();
    somente_post();
    validar_csrf();

    $id = filter_var($_POST['trr_id'] ?? null, FILTER_VALIDATE_INT) ?: null;
    $treinoId = filter_var($_POST['trp_id'] ?? null, FILTER_VALIDATE_INT);
    $data = trim((string) ($_POST['trr_data'] ?? ''));
    $inicio = normalizar_horario_treino(trim((string) ($_POST['trr_hora_inicio'] ?? '')));
    $fim = normalizar_horario_treino(trim((string) ($_POST['trr_hora_fim'] ?? '')));
    $situacao = trim((string) ($_POST['trr_situacao'] ?? ''));
    $esforco = normalizar_nivel_treino($_POST['trr_esforco_percebido'] ?? '');
    $energia = normalizar_nivel_treino($_POST['trr_energia'] ?? '');
    $observacoes = trim((string) ($_POST['trr_observacoes'] ?? ''));
    $exerciciosRealizados = exercicios_realizados_do_post();
    $existente = $id ? buscar_treino_realizado((int) $id) : false;
    $treino = $treinoId ? buscar_treino_planejado((int) $treinoId) : false;
    $erros = [];

    if ($id && !$existente) {
        flash('erro', 'Treino realizado não encontrado.');
        redirecionar('/treinos');
    }

    if (!$id && !$treino) {
        $erros[] = 'Selecione um treino planejado válido.';
    }

    if (!data_iso_valida($data) || $data !== date('Y-m-d')) {
        $erros[] = 'A data do treino deve ser válida e não pode estar no futuro.';
    }

    if ($existente && $existente['trp_id'] !== null && data_iso_valida($data)) {
        $duplicado = buscar_treino_realizado_por_data((int) $existente['trp_id'], $data);

        if ($duplicado && (int) $duplicado['trr_id'] !== (int) $id) {
            $erros[] = 'Este treino já possui outro registro na data informada.';
        }
    }

    if (!array_key_exists($situacao, SITUACOES_TREINO)) {
        $erros[] = 'Selecione uma situação válida.';
    }

    if ($inicio === false || $fim === false) {
        $erros[] = 'Informe horários válidos.';
    } elseif ($inicio && $fim && $fim < $inicio) {
        $erros[] = 'O horário final não pode ser anterior ao inicial.';
    }

    if ($esforco === false || $energia === false) {
        $erros[] = 'Esforço e energia devem estar entre 0 e 10.';
    }

    if ($situacao !== 'nao_realizado' && !$exerciciosRealizados) {
        $erros[] = 'Mantenha pelo menos um exercício no registro.';
    }

    foreach ($exerciciosRealizados as $indice => $exercicio) {
        $numero = $indice + 1;

        if ($exercicio['exr_nome'] === '' || mb_strlen($exercicio['exr_nome']) > 120) {
            $erros[] = "Informe um nome válido para o exercício {$numero}.";
        }

        if (!array_key_exists($exercicio['exr_tipo'], TIPOS_EXERCICIO)) {
            $erros[] = "Selecione um tipo válido para o exercício {$numero}.";
        }

        if ($exercicio['exe_id'] && !buscar_exercicio((int) $exercicio['exe_id'])) {
            $erros[] = "O exercício {$numero} não pertence ao usuário.";
        }

        foreach ($exercicio['series'] as $serie) {
            if ($serie['repeticoes_informadas'] !== '' && ($serie['srr_repeticoes'] === false || $serie['srr_repeticoes'] === null || $serie['srr_repeticoes'] < 0)) {
                $erros[] = "Informe repetições válidas no exercício {$numero}.";
            }

            foreach (['carga_kg', 'distancia_km'] as $campo) {
                if ($serie[$campo . '_informada'] !== '' && ($serie['srr_' . $campo] === null || $serie['srr_' . $campo] < 0)) {
                    $erros[] = "Informe valores positivos no exercício {$numero}.";
                }
            }

            if ($serie['srr_duracao_segundos'] === false || ($serie['srr_duracao_segundos'] !== null && $serie['srr_duracao_segundos'] < 0)) {
                $erros[] = "Informe uma duração válida no exercício {$numero}.";
            }
        }
    }

    $planejado = $existente
        ? decodificar_snapshot_treino($existente['trr_planejado_snapshot'])
        : ($treino ? montar_snapshot_treino($treino, exercicios_planejados_do_treino((int) $treinoId)) : []);

    if ($erros) {
        foreach (array_unique($erros) as $erro) {
            flash('erro', $erro);
        }

        $registro = $_POST;
        $registro['trr_id'] = $id;
        $registro['planejado'] = $planejado;

        if (!$exerciciosRealizados && $planejado) {
            $exerciciosRealizados = preparar_exercicios_realizados($planejado);
        }

        $exercicios = exercicios_do_usuario();
        renderizar('treinos/realizado_formulario', compact('registro', 'planejado', 'exerciciosRealizados', 'exercicios'), $id ? 'Editar treino realizado' : 'Registrar treino');
        return;
    }

    $conexao = banco();
    $conexao->beginTransaction();

    try {
        $parametros = [
            'usuario' => usuario_atual()['id'],
            'plano' => $existente ? $existente['ptr_id'] : $treino['ptr_id'],
            'treino' => $existente ? $existente['trp_id'] : $treino['trp_id'],
            'data' => $data,
            'inicio' => $inicio ?: null,
            'fim' => $fim ?: null,
            'situacao' => $situacao,
            'nome' => $existente ? $existente['trr_treino_nome'] : $treino['trp_nome'],
            'snapshot' => json_encode($planejado, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
            'esforco' => $esforco === null ? null : $esforco,
            'energia' => $energia === null ? null : $energia,
            'observacoes' => $observacoes ?: null,
        ];

        if ($id) {
            $parametros['id'] = $id;
            $conexao->prepare(
                'UPDATE trr_treino_realizado
                    SET trr_data = :data, trr_hora_inicio = :inicio, trr_hora_fim = :fim,
                        trr_situacao = :situacao, trr_esforco_percebido = :esforco,
                        trr_energia = :energia, trr_observacoes = :observacoes
                  WHERE trr_id = :id AND usu_id = :usuario'
            )->execute([
                'data' => $parametros['data'],
                'inicio' => $parametros['inicio'],
                'fim' => $parametros['fim'],
                'situacao' => $parametros['situacao'],
                'esforco' => $parametros['esforco'],
                'energia' => $parametros['energia'],
                'observacoes' => $parametros['observacoes'],
                'id' => $id,
                'usuario' => $parametros['usuario'],
            ]);
            $conexao->prepare('DELETE FROM exr_exercicio_realizado WHERE trr_id = :treino')->execute(['treino' => $id]);
            $mensagem = 'Treino realizado atualizado.';
        } else {
            if (buscar_treino_realizado_por_data((int) $treinoId, $data)) {
                throw new RuntimeException('Este treino já foi registrado nesta data.');
            }

            $conexao->prepare(
                'INSERT INTO trr_treino_realizado
                    (usu_id, ptr_id, trp_id, trr_data, trr_hora_inicio, trr_hora_fim,
                     trr_situacao, trr_treino_nome, trr_planejado_snapshot,
                     trr_esforco_percebido, trr_energia, trr_observacoes)
                 VALUES (:usuario, :plano, :treino, :data, :inicio, :fim, :situacao,
                         :nome, :snapshot, :esforco, :energia, :observacoes)'
            )->execute($parametros);
            $id = (int) $conexao->lastInsertId();
            $mensagem = 'Treino registrado com sucesso.';
        }

        if ($situacao !== 'nao_realizado') {
            salvar_exercicios_realizados($conexao, (int) $id, $exerciciosRealizados);
        }

        $conexao->commit();
    } catch (Throwable $erro) {
        if ($conexao->inTransaction()) {
            $conexao->rollBack();
        }

        throw $erro;
    }

    flash('sucesso', $mensagem);
    redirecionar('/treinos/realizados/detalhes?id=' . $id);
}

function detalhar_treino_realizado(): void
{
    exigir_autenticacao();
    $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
    $registro = $id ? buscar_treino_realizado((int) $id) : false;

    if (!$registro) {
        flash('erro', 'Treino realizado não encontrado.');
        redirecionar('/treinos/historico');
    }

    $registro['planejado'] = decodificar_snapshot_treino($registro['trr_planejado_snapshot']);
    $exercicios = exercicios_realizados_do_treino((int) $id);
    $resumo = resumo_treino_realizado($registro, $exercicios);
    renderizar('treinos/realizado_detalhes', compact('registro', 'exercicios', 'resumo'), $registro['trr_treino_nome']);
}

function listar_historico_treinos(): void
{
    exigir_autenticacao();
    $consulta = banco()->prepare(
        'SELECT trr.*,
                (SELECT COUNT(*) FROM exr_exercicio_realizado exr WHERE exr.trr_id = trr.trr_id) AS total_exercicios,
                (SELECT COUNT(*)
                   FROM srr_serie_realizada srr
                   INNER JOIN exr_exercicio_realizado exr ON exr.exr_id = srr.exr_id
                  WHERE exr.trr_id = trr.trr_id AND srr.srr_concluida = 1) AS total_series
           FROM trr_treino_realizado trr
          WHERE trr.usu_id = :usuario
          ORDER BY trr.trr_data DESC, trr.trr_id DESC
          LIMIT 120'
    );
    $consulta->execute(['usuario' => usuario_atual()['id']]);
    $registros = $consulta->fetchAll();

    foreach ($registros as &$registro) {
        $registro['planejado'] = decodificar_snapshot_treino($registro['trr_planejado_snapshot']);
        $registro['resumo'] = resumo_treino_realizado($registro, exercicios_realizados_do_treino((int) $registro['trr_id']));
    }
    unset($registro);

    renderizar('treinos/historico', compact('registros'), 'Histórico de treinos');
}

function excluir_treino_realizado(): void
{
    exigir_autenticacao();
    somente_post();
    validar_csrf();

    $id = filter_var($_POST['trr_id'] ?? null, FILTER_VALIDATE_INT);
    $registro = $id ? buscar_treino_realizado((int) $id) : false;

    if (!$registro) {
        flash('erro', 'Treino realizado não encontrado.');
        redirecionar('/treinos/historico');
    }

    banco()->prepare('DELETE FROM trr_treino_realizado WHERE trr_id = :id AND usu_id = :usuario')
        ->execute(['id' => $id, 'usuario' => usuario_atual()['id']]);
    flash('sucesso', 'Registro de treino excluído.');
    redirecionar('/treinos?data=' . $registro['trr_data']);
}

function buscar_exercicio(int $id): array|false
{
    $consulta = banco()->prepare('SELECT * FROM exe_exercicio WHERE exe_id = :id AND usu_id = :usuario LIMIT 1');
    $consulta->execute(['id' => $id, 'usuario' => usuario_atual()['id']]);
    return $consulta->fetch();
}

function exercicios_do_usuario(): array
{
    $consulta = banco()->prepare(
        'SELECT * FROM exe_exercicio
          WHERE usu_id = :usuario
          ORDER BY exe_ativo DESC, exe_nome, exe_id'
    );
    $consulta->execute(['usuario' => usuario_atual()['id']]);
    return $consulta->fetchAll();
}

function buscar_plano_treino(int $id): array|false
{
    $consulta = banco()->prepare('SELECT * FROM ptr_plano_treino WHERE ptr_id = :id AND usu_id = :usuario LIMIT 1');
    $consulta->execute(['id' => $id, 'usuario' => usuario_atual()['id']]);
    return $consulta->fetch();
}

function buscar_plano_treino_por_data(string $data): array|false
{
    $consulta = banco()->prepare(
        'SELECT * FROM ptr_plano_treino
          WHERE usu_id = :usuario AND ptr_ativo = 1
            AND ptr_data_inicio <= :data
            AND (ptr_data_fim IS NULL OR ptr_data_fim >= :data_fim)
          ORDER BY ptr_data_inicio DESC, ptr_id DESC LIMIT 1'
    );
    $consulta->execute(['usuario' => usuario_atual()['id'], 'data' => $data, 'data_fim' => $data]);
    return $consulta->fetch();
}

function buscar_treino_planejado(int $id): array|false
{
    $consulta = banco()->prepare(
        'SELECT trp.* FROM trp_treino_planejado trp
         INNER JOIN ptr_plano_treino ptr ON ptr.ptr_id = trp.ptr_id
         WHERE trp.trp_id = :id AND ptr.usu_id = :usuario LIMIT 1'
    );
    $consulta->execute(['id' => $id, 'usuario' => usuario_atual()['id']]);
    return $consulta->fetch();
}

function exercicios_planejados_do_treino(int $treinoId): array
{
    $consulta = banco()->prepare(
        'SELECT exp.*, exe.exe_nome, exe.exe_tipo, exe.exe_grupo_muscular
           FROM exp_exercicio_planejado exp
           INNER JOIN exe_exercicio exe ON exe.exe_id = exp.exe_id
           INNER JOIN trp_treino_planejado trp ON trp.trp_id = exp.trp_id
           INNER JOIN ptr_plano_treino ptr ON ptr.ptr_id = trp.ptr_id
          WHERE exp.trp_id = :treino AND ptr.usu_id = :usuario
          ORDER BY exp.exp_ordem, exp.exp_id'
    );
    $consulta->execute(['treino' => $treinoId, 'usuario' => usuario_atual()['id']]);
    return $consulta->fetchAll();
}

function treinos_planejados_do_plano(int $planoId): array
{
    $consulta = banco()->prepare(
        'SELECT trp.* FROM trp_treino_planejado trp
         INNER JOIN ptr_plano_treino ptr ON ptr.ptr_id = trp.ptr_id
         WHERE trp.ptr_id = :plano AND ptr.usu_id = :usuario
         ORDER BY trp.trp_ordem, trp.trp_dia_semana, trp.trp_id'
    );
    $consulta->execute(['plano' => $planoId, 'usuario' => usuario_atual()['id']]);
    $treinos = $consulta->fetchAll();

    foreach ($treinos as &$treino) {
        $treino['exercicios'] = exercicios_planejados_do_treino((int) $treino['trp_id']);
    }
    unset($treino);

    return $treinos;
}

function proxima_ordem_treino(int $planoId): int
{
    if ($planoId <= 0 || !buscar_plano_treino($planoId)) {
        return 0;
    }

    $consulta = banco()->prepare('SELECT COALESCE(MAX(trp_ordem), -1) + 1 FROM trp_treino_planejado WHERE ptr_id = :plano');
    $consulta->execute(['plano' => $planoId]);
    return (int) $consulta->fetchColumn();
}

function buscar_treino_realizado(int $id): array|false
{
    $consulta = banco()->prepare('SELECT * FROM trr_treino_realizado WHERE trr_id = :id AND usu_id = :usuario LIMIT 1');
    $consulta->execute(['id' => $id, 'usuario' => usuario_atual()['id']]);
    return $consulta->fetch();
}

function buscar_treino_realizado_por_data(int $treinoId, string $data): array|false
{
    $consulta = banco()->prepare(
        'SELECT * FROM trr_treino_realizado
          WHERE trp_id = :treino AND trr_data = :data AND usu_id = :usuario LIMIT 1'
    );
    $consulta->execute(['treino' => $treinoId, 'data' => $data, 'usuario' => usuario_atual()['id']]);
    return $consulta->fetch();
}

function registros_treino_por_data(int $planoId, string $data): array
{
    $consulta = banco()->prepare(
        'SELECT * FROM trr_treino_realizado
          WHERE ptr_id = :plano AND trr_data = :data AND usu_id = :usuario'
    );
    $consulta->execute(['plano' => $planoId, 'data' => $data, 'usuario' => usuario_atual()['id']]);
    $registros = [];

    foreach ($consulta->fetchAll() as $registro) {
        $registro['planejado'] = decodificar_snapshot_treino($registro['trr_planejado_snapshot']);
        $registro['exercicios'] = exercicios_realizados_do_treino((int) $registro['trr_id']);

        if ($registro['trp_id'] !== null) {
            $registros[(int) $registro['trp_id']] = $registro;
        }
    }

    return $registros;
}

function exercicios_realizados_do_treino(int $treinoId): array
{
    $consulta = banco()->prepare(
        'SELECT exr.* FROM exr_exercicio_realizado exr
         INNER JOIN trr_treino_realizado trr ON trr.trr_id = exr.trr_id
         WHERE exr.trr_id = :treino AND trr.usu_id = :usuario
         ORDER BY exr.exr_ordem, exr.exr_id'
    );
    $consulta->execute(['treino' => $treinoId, 'usuario' => usuario_atual()['id']]);
    $exercicios = $consulta->fetchAll();
    $series = banco()->prepare('SELECT * FROM srr_serie_realizada WHERE exr_id = :exercicio ORDER BY srr_numero, srr_id');

    foreach ($exercicios as &$exercicio) {
        $series->execute(['exercicio' => $exercicio['exr_id']]);
        $exercicio['series'] = $series->fetchAll();
    }
    unset($exercicio);

    return $exercicios;
}

function exercicios_planejados_do_post(): array
{
    $exercicios = is_array($_POST['exe_id'] ?? null) ? $_POST['exe_id'] : [];
    $series = is_array($_POST['exp_series'] ?? null) ? $_POST['exp_series'] : [];
    $repMin = is_array($_POST['exp_repeticoes_min'] ?? null) ? $_POST['exp_repeticoes_min'] : [];
    $repMax = is_array($_POST['exp_repeticoes_max'] ?? null) ? $_POST['exp_repeticoes_max'] : [];
    $cargas = is_array($_POST['exp_carga_alvo'] ?? null) ? $_POST['exp_carga_alvo'] : [];
    $descansos = is_array($_POST['exp_descanso_segundos'] ?? null) ? $_POST['exp_descanso_segundos'] : [];
    $duracoes = is_array($_POST['exp_duracao_segundos'] ?? null) ? $_POST['exp_duracao_segundos'] : [];
    $distancias = is_array($_POST['exp_distancia_km'] ?? null) ? $_POST['exp_distancia_km'] : [];
    $observacoes = is_array($_POST['exp_observacoes'] ?? null) ? $_POST['exp_observacoes'] : [];
    $itens = [];

    foreach ($exercicios as $indice => $exercicio) {
        $exercicioId = filter_var($exercicio, FILTER_VALIDATE_INT) ?: null;

        if (!$exercicioId) {
            continue;
        }

        $cargaInformada = trim((string) ($cargas[$indice] ?? ''));
        $distanciaInformada = trim((string) ($distancias[$indice] ?? ''));

        $itens[] = [
            'exe_id' => $exercicioId,
            'series' => max(0, (int) ($series[$indice] ?? 0)),
            'repeticoes_min' => ($repMin[$indice] ?? '') === '' ? null : (int) $repMin[$indice],
            'repeticoes_max' => ($repMax[$indice] ?? '') === '' ? null : (int) $repMax[$indice],
            'carga_alvo_informada' => $cargaInformada,
            'carga_alvo' => normalizar_decimal($cargaInformada),
            'descanso_segundos' => ($descansos[$indice] ?? '') === '' ? null : (int) $descansos[$indice],
            'duracao_segundos' => ($duracoes[$indice] ?? '') === '' ? null : (int) $duracoes[$indice],
            'distancia_km_informada' => $distanciaInformada,
            'distancia_km' => normalizar_decimal($distanciaInformada),
            'observacoes' => trim((string) ($observacoes[$indice] ?? '')),
        ];
    }

    return $itens;
}

function exercicios_realizados_do_post(): array
{
    $nomes = is_array($_POST['exr_nome'] ?? null) ? $_POST['exr_nome'] : [];
    $itens = [];

    foreach ($nomes as $indice => $nomeInformado) {
        $nome = trim((string) $nomeInformado);

        if ($nome === '') {
            continue;
        }

        $series = [];
        $repeticoes = $_POST['srr_repeticoes'][$indice] ?? [];
        $cargas = $_POST['srr_carga_kg'][$indice] ?? [];
        $duracoes = $_POST['srr_duracao_segundos'][$indice] ?? [];
        $distancias = $_POST['srr_distancia_km'][$indice] ?? [];
        $concluidas = $_POST['srr_concluida'][$indice] ?? [];
        $observacoesSeries = $_POST['srr_observacoes'][$indice] ?? [];
        $total = max(count((array) $repeticoes), count((array) $cargas), count((array) $duracoes), count((array) $distancias), 1);

        for ($serieIndice = 0; $serieIndice < $total; $serieIndice++) {
            $repInformadas = trim((string) ($repeticoes[$serieIndice] ?? ''));
            $cargaInformada = trim((string) ($cargas[$serieIndice] ?? ''));
            $distanciaInformada = trim((string) ($distancias[$serieIndice] ?? ''));
            $duracaoInformada = trim((string) ($duracoes[$serieIndice] ?? ''));
            $observacao = trim((string) ($observacoesSeries[$serieIndice] ?? ''));

            if ($repInformadas === '' && $cargaInformada === '' && $distanciaInformada === ''
                && $duracaoInformada === '' && $observacao === ''
                && !isset($concluidas[$serieIndice])) {
                continue;
            }

            $series[] = [
                'srr_numero' => count($series) + 1,
                'repeticoes_informadas' => $repInformadas,
                'srr_repeticoes' => $repInformadas === '' ? null : filter_var($repInformadas, FILTER_VALIDATE_INT),
                'carga_kg_informada' => $cargaInformada,
                'srr_carga_kg' => normalizar_decimal($cargaInformada),
                'srr_duracao_segundos' => $duracaoInformada === '' ? null : filter_var($duracaoInformada, FILTER_VALIDATE_INT),
                'distancia_km_informada' => $distanciaInformada,
                'srr_distancia_km' => normalizar_decimal($distanciaInformada),
                'srr_concluida' => isset($concluidas[$serieIndice]) ? 1 : 0,
                'srr_observacoes' => $observacao,
            ];
        }

        $itens[] = [
            'exe_id' => filter_var($_POST['exr_exe_id'][$indice] ?? null, FILTER_VALIDATE_INT) ?: null,
            'exp_id' => filter_var($_POST['exr_exp_id'][$indice] ?? null, FILTER_VALIDATE_INT) ?: null,
            'exr_exp_id_snapshot' => filter_var($_POST['exr_exp_id_snapshot'][$indice] ?? null, FILTER_VALIDATE_INT) ?: null,
            'exr_nome' => $nome,
            'exr_tipo' => trim((string) ($_POST['exr_tipo'][$indice] ?? 'outro')),
            'exr_observacoes' => trim((string) ($_POST['exr_observacoes'][$indice] ?? '')),
            'series' => $series,
        ];
    }

    return $itens;
}

function salvar_exercicios_realizados(PDO $conexao, int $treinoId, array $exercicios): void
{
    $inserirExercicio = $conexao->prepare(
        'INSERT INTO exr_exercicio_realizado
            (trr_id, exe_id, exp_id, exr_exp_id_snapshot, exr_nome, exr_tipo, exr_observacoes, exr_ordem)
         VALUES (:treino, :exercicio, :planejado, :planejado_snapshot, :nome, :tipo, :observacoes, :ordem)'
    );
    $inserirSerie = $conexao->prepare(
        'INSERT INTO srr_serie_realizada
            (exr_id, srr_numero, srr_repeticoes, srr_carga_kg, srr_duracao_segundos,
             srr_distancia_km, srr_concluida, srr_observacoes)
         VALUES (:exercicio, :numero, :repeticoes, :carga, :duracao, :distancia, :concluida, :observacoes)'
    );

    foreach ($exercicios as $ordem => $item) {
        $inserirExercicio->execute([
            'treino' => $treinoId,
            'exercicio' => $item['exe_id'],
            'planejado' => $item['exp_id'],
            'planejado_snapshot' => $item['exr_exp_id_snapshot'] ?? $item['exp_id'],
            'nome' => $item['exr_nome'],
            'tipo' => $item['exr_tipo'],
            'observacoes' => $item['exr_observacoes'] ?: null,
            'ordem' => $ordem,
        ]);
        $exercicioId = (int) $conexao->lastInsertId();

        foreach ($item['series'] as $serie) {
            $inserirSerie->execute([
                'exercicio' => $exercicioId,
                'numero' => $serie['srr_numero'],
                'repeticoes' => $serie['srr_repeticoes'] === false ? null : $serie['srr_repeticoes'],
                'carga' => $serie['srr_carga_kg'],
                'duracao' => $serie['srr_duracao_segundos'] === false ? null : $serie['srr_duracao_segundos'],
                'distancia' => $serie['srr_distancia_km'],
                'concluida' => $serie['srr_concluida'],
                'observacoes' => $serie['srr_observacoes'] ?: null,
            ]);
        }
    }
}

function normalizar_horario_treino(string $horario): string|false|null
{
    if ($horario === '') {
        return null;
    }

    $objeto = DateTimeImmutable::createFromFormat('!H:i', $horario);
    return $objeto !== false && $objeto->format('H:i') === $horario
        ? $objeto->format('H:i:s')
        : false;
}

function normalizar_nivel_treino(mixed $valor): int|false|null
{
    if ($valor === null || $valor === '') {
        return null;
    }

    $nivel = filter_var($valor, FILTER_VALIDATE_INT);
    return $nivel !== false && $nivel >= 0 && $nivel <= 10 ? $nivel : false;
}

function preparar_exercicios_realizados(array $planejado): array
{
    $exercicios = [];

    foreach ($planejado['exercicios'] ?? [] as $item) {
        $series = [];

        for ($numero = 1; $numero <= max(1, (int) $item['series']); $numero++) {
            $series[] = [
                'srr_numero' => $numero,
                'srr_repeticoes' => '',
                'srr_carga_kg' => $item['carga_alvo'] ?? '',
                'srr_duracao_segundos' => $item['duracao_segundos'] ?? '',
                'srr_distancia_km' => $item['distancia_km'] ?? '',
                'srr_concluida' => 1,
                'srr_observacoes' => '',
            ];
        }

        $exercicios[] = [
            'exe_id' => $item['exe_id'],
            'exp_id' => $item['exp_id'],
            'exr_exp_id_snapshot' => $item['exp_id'],
            'exr_nome' => $item['nome'],
            'exr_tipo' => $item['tipo'],
            'exr_observacoes' => '',
            'series' => $series,
        ];
    }

    return $exercicios;
}



function treino_recomendado_para_data(array $plano, array $treinos, string $data): ?array
{
    if (!$treinos) { return null; }
    $hoje = date('Y-m-d');
    if ($data > $hoje) {
        $consulta = banco()->prepare('SELECT trp_id, trr_data FROM trr_treino_realizado WHERE ptr_id = :plano AND usu_id = :usuario AND trr_data <= :hoje AND trr_situacao <> "nao_realizado" ORDER BY trr_data DESC, trr_id DESC LIMIT 1');
        $consulta->execute(['plano' => $plano['ptr_id'], 'usuario' => usuario_atual()['id'], 'hoje' => $hoje]);
        $ultimo = $consulta->fetch();
        $indice = 0;
        if ($ultimo) {
            foreach ($treinos as $posicao => $treino) { if ((int) $treino['trp_id'] === (int) $ultimo['trp_id']) { $indice = ($posicao + 1) % count($treinos); break; } }
            $dias = (int) ((new DateTimeImmutable($hoje))->diff(new DateTimeImmutable($data))->days);
            $indice = ($indice + $dias - ($ultimo['trr_data'] === $hoje ? 1 : 0)) % count($treinos);
        } else {
            $indice = (int) ((new DateTimeImmutable($hoje))->diff(new DateTimeImmutable($data))->days) % count($treinos);
        }
        return $treinos[$indice];
    }
    $consulta = banco()->prepare('SELECT trp_id FROM trr_treino_realizado WHERE ptr_id = :plano AND usu_id = :usuario AND trr_data < :data AND trr_situacao <> "nao_realizado" ORDER BY trr_data DESC, trr_id DESC LIMIT 1');
    $consulta->execute(['plano' => $plano['ptr_id'], 'usuario' => usuario_atual()['id'], 'data' => $data]);
    $ultimo = $consulta->fetchColumn();
    if ($ultimo === false) { return $treinos[0]; }
    foreach ($treinos as $indice => $treino) { if ((int) $treino['trp_id'] === (int) $ultimo) { return $treinos[($indice + 1) % count($treinos)]; } }
    return $treinos[0];
}
