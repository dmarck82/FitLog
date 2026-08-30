<?php

declare(strict_types=1);

const CAMPOS_MEDICAO = [
    'mec_cintura_cm' => ['Cintura', 'cm', 0, 400],
    'mec_abdomen_cm' => ['Abdômen', 'cm', 0, 400],
    'mec_quadril_cm' => ['Quadril', 'cm', 0, 400],
    'mec_torax_cm' => ['Tórax', 'cm', 0, 400],
    'mec_braco_direito_cm' => ['Braço direito', 'cm', 0, 200],
    'mec_braco_esquerdo_cm' => ['Braço esquerdo', 'cm', 0, 200],
    'mec_coxa_direita_cm' => ['Coxa direita', 'cm', 0, 250],
    'mec_coxa_esquerda_cm' => ['Coxa esquerda', 'cm', 0, 250],
];

const MEDICOES_POR_PAGINA = 7;

function listar_medicoes(): void
{
    exigir_autenticacao();
    $usuario = usuario_atual();
    $dataInicio = trim((string) ($_GET['data_inicio'] ?? ''));
    $dataFim = trim((string) ($_GET['data_fim'] ?? ''));
    $paginaAtual = max(1, filter_var($_GET['pagina'] ?? 1, FILTER_VALIDATE_INT) ?: 1);
    $errosFiltro = [];

    if ($dataInicio !== '' && !data_iso_valida($dataInicio)) {
        $errosFiltro[] = 'A data inicial informada é inválida.';
        $dataInicio = '';
    }

    if ($dataFim !== '' && !data_iso_valida($dataFim)) {
        $errosFiltro[] = 'A data final informada é inválida.';
        $dataFim = '';
    }

    if ($dataInicio !== '' && $dataFim !== '' && $dataInicio > $dataFim) {
        $errosFiltro[] = 'A data inicial não pode ser posterior à data final.';
    }

    $condicoes = ['usu_id = :usuario'];
    $parametros = ['usuario' => $usuario['id']];

    if ($dataInicio !== '') {
        $condicoes[] = 'mec_data_medicao >= :data_inicio';
        $parametros['data_inicio'] = $dataInicio;
    }

    if ($dataFim !== '') {
        $condicoes[] = 'mec_data_medicao <= :data_fim';
        $parametros['data_fim'] = $dataFim;
    }

    $onde = implode(' AND ', $condicoes);
    $contagem = banco()->prepare("SELECT COUNT(*) FROM mec_medicao_corporal WHERE {$onde}");
    $contagem->execute($parametros);
    $totalMedicoes = (int) $contagem->fetchColumn();
    $totalPaginas = max(1, (int) ceil($totalMedicoes / MEDICOES_POR_PAGINA));
    $paginaAtual = min($paginaAtual, $totalPaginas);
    $deslocamento = ($paginaAtual - 1) * MEDICOES_POR_PAGINA;

    $consulta = banco()->prepare(
        "SELECT * FROM mec_medicao_corporal
         WHERE {$onde}
         ORDER BY mec_data_medicao DESC, mec_id DESC
         LIMIT " . MEDICOES_POR_PAGINA . " OFFSET {$deslocamento}"
    );
    $consulta->execute($parametros);
    $medicoes = array_reverse($consulta->fetchAll());
    $filtroAtivo = $dataInicio !== '' || $dataFim !== '';

    renderizar('medicoes/lista', compact(
        'medicoes', 'dataInicio', 'dataFim', 'paginaAtual', 'totalPaginas',
        'totalMedicoes', 'filtroAtivo', 'errosFiltro'
    ), 'Medidas corporais');
}

function formulario_medicao(): void
{
    exigir_autenticacao();
    $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
    $medicao = ['mec_data_medicao' => date('Y-m-d')];

    if ($id) {
        $medicaoEncontrada = buscar_medicao((int) $id);

        if (!$medicaoEncontrada) {
            flash('erro', 'Medição não encontrada.');
            redirecionar('/medidas');
        }

        $medicao = $medicaoEncontrada;
    }

    renderizar('medicoes/formulario', compact('medicao'), $id ? 'Editar medição' : 'Nova medição');
}

function salvar_medicao(): void
{
    exigir_autenticacao();
    somente_post();
    validar_csrf();

    $id = filter_var($_POST['mec_id'] ?? null, FILTER_VALIDATE_INT) ?: null;
    $data = trim((string) ($_POST['mec_data_medicao'] ?? ''));
    $observacoes = trim((string) ($_POST['mec_observacoes'] ?? ''));
    $erros = [];

    $dataValida = DateTimeImmutable::createFromFormat('!Y-m-d', $data);
    if (!$dataValida || $dataValida->format('Y-m-d') !== $data) {
        $erros[] = 'Informe uma data de medição válida.';
    }

    $valores = [];
    $algumValor = false;

    foreach (CAMPOS_MEDICAO as $campo => [$rotulo, , $minimo, $maximo]) {
        $bruto = $_POST[$campo] ?? null;
        $valor = normalizar_decimal($bruto);

        if (trim((string) $bruto) !== '' && $valor === null) {
            $erros[] = "O campo {$rotulo} deve ser numérico.";
        } elseif ($valor !== null && ($valor < $minimo || $valor > $maximo)) {
            $erros[] = "O campo {$rotulo} está fora do intervalo esperado.";
        }

        $valores[$campo] = $valor;
        $algumValor = $algumValor || $valor !== null;
    }

    if (!$algumValor) {
        $erros[] = 'Informe ao menos uma medida corporal.';
    }

    if ($erros) {
        foreach ($erros as $erro) {
            flash('erro', $erro);
        }

        $medicao = array_merge($_POST, $valores);
        renderizar('medicoes/formulario', compact('medicao'), $id ? 'Editar medição' : 'Nova medição');
        return;
    }

    $parametros = array_merge($valores, [
        'usuario' => usuario_atual()['id'],
        'data_medicao' => $data,
        'observacoes' => $observacoes ?: null,
    ]);

    if ($id) {
        if (!buscar_medicao((int) $id)) {
            flash('erro', 'Medição não encontrada.');
            redirecionar('/medidas');
        }

        $parametros['id'] = $id;
        $sql = 'UPDATE mec_medicao_corporal SET
                    mec_data_medicao = :data_medicao,
                    mec_cintura_cm = :mec_cintura_cm,
                    mec_abdomen_cm = :mec_abdomen_cm,
                    mec_quadril_cm = :mec_quadril_cm,
                    mec_torax_cm = :mec_torax_cm,
                    mec_braco_direito_cm = :mec_braco_direito_cm,
                    mec_braco_esquerdo_cm = :mec_braco_esquerdo_cm,
                    mec_coxa_direita_cm = :mec_coxa_direita_cm,
                    mec_coxa_esquerda_cm = :mec_coxa_esquerda_cm,
                    mec_observacoes = :observacoes
                WHERE mec_id = :id AND usu_id = :usuario';
        $mensagem = 'Medição atualizada com sucesso.';
    } else {
        $sql = 'INSERT INTO mec_medicao_corporal (
                    usu_id, mec_data_medicao, mec_cintura_cm, mec_abdomen_cm, mec_quadril_cm,
                    mec_torax_cm, mec_braco_direito_cm, mec_braco_esquerdo_cm,
                    mec_coxa_direita_cm, mec_coxa_esquerda_cm, mec_observacoes
                ) VALUES (
                    :usuario, :data_medicao, :mec_cintura_cm, :mec_abdomen_cm, :mec_quadril_cm,
                    :mec_torax_cm, :mec_braco_direito_cm, :mec_braco_esquerdo_cm,
                    :mec_coxa_direita_cm, :mec_coxa_esquerda_cm, :observacoes
                )';
        $mensagem = 'Medição registrada com sucesso.';
    }

    banco()->prepare($sql)->execute($parametros);
    flash('sucesso', $mensagem);
    redirecionar('/medidas');
}

function excluir_medicao(): void
{
    exigir_autenticacao();
    somente_post();
    validar_csrf();

    $id = filter_var($_POST['mec_id'] ?? null, FILTER_VALIDATE_INT);

    if (!$id) {
        flash('erro', 'Medição inválida.');
        redirecionar('/medidas');
    }

    $excluir = banco()->prepare(
        'DELETE FROM mec_medicao_corporal WHERE mec_id = :id AND usu_id = :usuario'
    );
    $excluir->execute(['id' => $id, 'usuario' => usuario_atual()['id']]);

    flash($excluir->rowCount() ? 'sucesso' : 'erro', $excluir->rowCount() ? 'Medição excluída.' : 'Medição não encontrada.');
    redirecionar('/medidas');
}

function buscar_medicao(int $id): array|false
{
    $consulta = banco()->prepare(
        'SELECT * FROM mec_medicao_corporal WHERE mec_id = :id AND usu_id = :usuario LIMIT 1'
    );
    $consulta->execute(['id' => $id, 'usuario' => usuario_atual()['id']]);

    return $consulta->fetch();
}
