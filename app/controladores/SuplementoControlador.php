<?php

declare(strict_types=1);

const UNIDADES_SUPLEMENTO = [
    'mg' => 'mg',
    'mcg' => 'mcg',
    'g' => 'g',
    'ml' => 'mL',
    'ui' => 'UI',
    'capsula' => 'cápsula(s)',
    'comprimido' => 'comprimido(s)',
    'scoop' => 'scoop(s)',
    'sache' => 'sachê(s)',
    'dose' => 'dose(s)',
    'gota' => 'gota(s)',
];

function listar_suplementos(): void
{
    exigir_autenticacao();
    $consulta = banco()->prepare(
        'SELECT sup.*,
                (SELECT MAX(cos.cos_data_consumo) FROM cos_consumo_suplemento cos WHERE cos.sup_id = sup.sup_id) AS ultimo_consumo,
                (SELECT COUNT(*) FROM cps_compra_suplemento cps WHERE cps.sup_id = sup.sup_id) AS total_compras,
                (SELECT COUNT(*) FROM cos_consumo_suplemento cos WHERE cos.sup_id = sup.sup_id) AS total_consumos
         FROM sup_suplemento sup
         WHERE sup.usu_id = :usuario
         ORDER BY sup.sup_ativo DESC, sup.sup_nome'
    );
    $consulta->execute(['usuario' => usuario_atual()['id']]);

    renderizar('suplementos/lista', ['suplementos' => $consulta->fetchAll()], 'Suplementos');
}

function formulario_suplemento(): void
{
    exigir_autenticacao();
    $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
    $suplemento = ['sup_ativo' => 1, 'sup_solicitar_feedback' => 0];

    if ($id) {
        $suplemento = buscar_suplemento((int) $id);
        if (!$suplemento) {
            flash('erro', 'Suplemento não encontrado.');
            redirecionar('/suplementos');
        }
    }

    renderizar('suplementos/formulario', compact('suplemento'), $id ? 'Editar suplemento' : 'Novo suplemento');
}

function salvar_suplemento(): void
{
    exigir_autenticacao();
    somente_post();
    validar_csrf();

    $id = filter_var($_POST['sup_id'] ?? null, FILTER_VALIDATE_INT) ?: null;
    $nome = trim((string) ($_POST['sup_nome'] ?? ''));
    $marca = trim((string) ($_POST['sup_marca'] ?? ''));
    $apresentacao = trim((string) ($_POST['sup_apresentacao'] ?? ''));
    $orientacoes = trim((string) ($_POST['sup_orientacoes'] ?? ''));
    $observacoes = trim((string) ($_POST['sup_observacoes'] ?? ''));
    $ativo = isset($_POST['sup_ativo']) ? 1 : 0;
    $solicitarFeedback = isset($_POST['sup_solicitar_feedback']) ? 1 : 0;
    $erros = [];

    if ($nome === '') {
        $erros[] = 'Informe o nome do suplemento.';
    } elseif (mb_strlen($nome) > 120) {
        $erros[] = 'O nome deve ter no máximo 120 caracteres.';
    }
    if (mb_strlen($marca) > 120) {
        $erros[] = 'A marca deve ter no máximo 120 caracteres.';
    }
    if (mb_strlen($apresentacao) > 120) {
        $erros[] = 'A apresentação deve ter no máximo 120 caracteres.';
    }

    if ($erros) {
        foreach ($erros as $erro) {
            flash('erro', $erro);
        }
        $suplemento = $_POST;
        $suplemento['sup_ativo'] = $ativo;
        $suplemento['sup_solicitar_feedback'] = $solicitarFeedback;
        renderizar('suplementos/formulario', compact('suplemento'), $id ? 'Editar suplemento' : 'Novo suplemento');
        return;
    }

    $parametros = [
        'usuario' => usuario_atual()['id'],
        'nome' => $nome,
        'marca' => $marca ?: null,
        'apresentacao' => $apresentacao ?: null,
        'orientacoes' => $orientacoes ?: null,
        'observacoes' => $observacoes ?: null,
        'ativo' => $ativo,
        'solicitar_feedback' => $solicitarFeedback,
    ];

    if ($id) {
        if (!buscar_suplemento((int) $id)) {
            flash('erro', 'Suplemento não encontrado.');
            redirecionar('/suplementos');
        }
        $parametros['id'] = $id;
        $sql = 'UPDATE sup_suplemento SET sup_nome = :nome, sup_marca = :marca,
                    sup_apresentacao = :apresentacao, sup_orientacoes = :orientacoes,
                    sup_observacoes = :observacoes, sup_ativo = :ativo,
                    sup_solicitar_feedback = :solicitar_feedback
                WHERE sup_id = :id AND usu_id = :usuario';
        $mensagem = 'Suplemento atualizado com sucesso.';
    } else {
        $sql = 'INSERT INTO sup_suplemento
                    (usu_id, sup_nome, sup_marca, sup_apresentacao, sup_orientacoes,
                     sup_observacoes, sup_ativo, sup_solicitar_feedback)
                VALUES (:usuario, :nome, :marca, :apresentacao, :orientacoes,
                        :observacoes, :ativo, :solicitar_feedback)';
        $mensagem = 'Suplemento cadastrado com sucesso.';
    }

    banco()->prepare($sql)->execute($parametros);
    flash('sucesso', $mensagem);
    redirecionar('/suplementos');
}

function listar_compras_suplemento(): void
{
    exigir_autenticacao();
    $consulta = banco()->prepare(
        'SELECT cps.*, sup.sup_nome, sup.sup_marca, sup.sup_apresentacao
         FROM cps_compra_suplemento cps
         INNER JOIN sup_suplemento sup ON sup.sup_id = cps.sup_id
         WHERE sup.usu_id = :usuario
         ORDER BY cps.cps_data_compra DESC, cps.cps_id DESC'
    );
    $consulta->execute(['usuario' => usuario_atual()['id']]);

    renderizar('suplementos/compras_lista', ['compras' => $consulta->fetchAll()], 'Compras de suplementos');
}

function formulario_compra_suplemento(): void
{
    exigir_autenticacao();
    $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
    $suplementoId = filter_input(INPUT_GET, 'suplemento', FILTER_VALIDATE_INT);
    $compra = ['cps_data_compra' => date('Y-m-d'), 'sup_id' => $suplementoId ?: ''];

    if ($id) {
        $compra = buscar_compra_suplemento((int) $id);
        if (!$compra) {
            flash('erro', 'Compra não encontrada.');
            redirecionar('/suplementos/compras');
        }
    }

    renderizar('suplementos/compra_formulario', [
        'compra' => $compra,
        'suplementos' => suplementos_do_usuario(),
    ], $id ? 'Editar compra' : 'Registrar compra');
}

function salvar_compra_suplemento(): void
{
    exigir_autenticacao();
    somente_post();
    validar_csrf();

    $id = filter_var($_POST['cps_id'] ?? null, FILTER_VALIDATE_INT) ?: null;
    $suplementoId = filter_var($_POST['sup_id'] ?? null, FILTER_VALIDATE_INT);
    $data = trim((string) ($_POST['cps_data_compra'] ?? ''));
    $quantidadeInformada = $_POST['cps_quantidade'] ?? null;
    $quantidade = normalizar_decimal($quantidadeInformada);
    $valorInformado = $_POST['cps_valor'] ?? null;
    $valor = normalizar_decimal($valorInformado);
    $lote = trim((string) ($_POST['cps_lote'] ?? ''));
    $validade = trim((string) ($_POST['cps_data_validade'] ?? ''));
    $observacoes = trim((string) ($_POST['cps_observacoes'] ?? ''));
    $erros = [];

    if (!$suplementoId || !buscar_suplemento((int) $suplementoId)) {
        $erros[] = 'Selecione um suplemento válido.';
    }
    if (!data_iso_valida($data)) {
        $erros[] = 'Informe uma data de compra válida.';
    }
    if (trim((string) $quantidadeInformada) !== '' && ($quantidade === null || $quantidade <= 0)) {
        $erros[] = 'A quantidade deve ser maior que zero.';
    }
    if (trim((string) $valorInformado) !== '' && ($valor === null || $valor < 0)) {
        $erros[] = 'O valor da compra deve ser numérico e não negativo.';
    }
    if ($validade !== '' && !data_iso_valida($validade)) {
        $erros[] = 'Informe uma data de validade válida.';
    }

    if ($erros) {
        foreach ($erros as $erro) {
            flash('erro', $erro);
        }
        $compra = $_POST;
        renderizar('suplementos/compra_formulario', ['compra' => $compra, 'suplementos' => suplementos_do_usuario()], $id ? 'Editar compra' : 'Registrar compra');
        return;
    }

    $parametros = [
        'suplemento' => $suplementoId,
        'data' => $data,
        'quantidade' => $quantidade,
        'valor' => $valor,
        'lote' => $lote ?: null,
        'validade' => $validade ?: null,
        'observacoes' => $observacoes ?: null,
    ];

    if ($id) {
        if (!buscar_compra_suplemento((int) $id)) {
            flash('erro', 'Compra não encontrada.');
            redirecionar('/suplementos/compras');
        }
        $parametros['id'] = $id;
        $sql = 'UPDATE cps_compra_suplemento SET sup_id = :suplemento, cps_data_compra = :data,
                    cps_quantidade = :quantidade, cps_valor = :valor, cps_lote = :lote,
                    cps_data_validade = :validade, cps_observacoes = :observacoes
                WHERE cps_id = :id';
        $mensagem = 'Compra atualizada com sucesso.';
    } else {
        $sql = 'INSERT INTO cps_compra_suplemento
                    (sup_id, cps_data_compra, cps_quantidade, cps_valor, cps_lote, cps_data_validade, cps_observacoes)
                VALUES (:suplemento, :data, :quantidade, :valor, :lote, :validade, :observacoes)';
        $mensagem = 'Compra registrada com sucesso.';
    }

    banco()->prepare($sql)->execute($parametros);
    flash('sucesso', $mensagem);
    redirecionar('/suplementos/compras');
}

function excluir_compra_suplemento(): void
{
    exigir_autenticacao();
    somente_post();
    validar_csrf();
    $id = filter_var($_POST['cps_id'] ?? null, FILTER_VALIDATE_INT);
    $consulta = banco()->prepare(
        'DELETE cps FROM cps_compra_suplemento cps
         INNER JOIN sup_suplemento sup ON sup.sup_id = cps.sup_id
         WHERE cps.cps_id = :id AND sup.usu_id = :usuario'
    );
    $consulta->execute(['id' => $id ?: 0, 'usuario' => usuario_atual()['id']]);
    flash($consulta->rowCount() ? 'sucesso' : 'erro', $consulta->rowCount() ? 'Compra excluída.' : 'Compra não encontrada.');
    redirecionar('/suplementos/compras');
}

function listar_consumos_suplemento(): void
{
    exigir_autenticacao();
    $consulta = banco()->prepare(
        'SELECT cos.*, sup.sup_nome, sup.sup_marca, sup.sup_apresentacao
         FROM cos_consumo_suplemento cos
         INNER JOIN sup_suplemento sup ON sup.sup_id = cos.sup_id
         WHERE sup.usu_id = :usuario
         ORDER BY cos.cos_data_consumo DESC, cos.cos_id DESC'
    );
    $consulta->execute(['usuario' => usuario_atual()['id']]);

    renderizar('suplementos/consumos_lista', ['consumos' => $consulta->fetchAll()], 'Consumos de suplementos');
}

function obter_feedback_ultimo_consumo(): void
{
    exigir_autenticacao();
    header('Content-Type: application/json; charset=UTF-8');

    $suplementoId = filter_var($_GET['suplemento'] ?? null, FILTER_VALIDATE_INT);
    $suplemento = $suplementoId ? buscar_suplemento((int) $suplementoId) : false;

    if (!$suplemento || !(bool) $suplemento['sup_solicitar_feedback']) {
        echo json_encode(['solicitar' => false], JSON_UNESCAPED_UNICODE);
        return;
    }

    $ultimoConsumo = buscar_ultimo_consumo_suplemento((int) $suplementoId);
    echo json_encode([
        'solicitar' => $ultimoConsumo !== false,
        'consumo' => $ultimoConsumo ? [
            'data' => formatar_data_hora($ultimoConsumo['cos_data_consumo']),
            'dose' => formatar_decimal($ultimoConsumo['cos_dose']),
            'unidade' => UNIDADES_SUPLEMENTO[$ultimoConsumo['cos_unidade']] ?? $ultimoConsumo['cos_unidade'],
            'feedback' => $ultimoConsumo['cos_observacoes'] ?? '',
        ] : null,
    ], JSON_UNESCAPED_UNICODE);
}

function formulario_consumo_suplemento(): void
{
    exigir_autenticacao();
    $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
    $suplementoId = filter_input(INPUT_GET, 'suplemento', FILTER_VALIDATE_INT);
    $consumo = ['cos_data_consumo' => date('Y-m-d\TH:i'), 'sup_id' => $suplementoId ?: ''];

    if ($id) {
        $consumo = buscar_consumo_suplemento((int) $id);
        if (!$consumo) {
            flash('erro', 'Consumo não encontrado.');
            redirecionar('/suplementos/consumos');
        }
        $consumo['cos_data_consumo'] = (new DateTimeImmutable($consumo['cos_data_consumo']))->format('Y-m-d\TH:i');
    }

    renderizar('suplementos/consumo_formulario', [
        'consumo' => $consumo,
        'suplementos' => suplementos_do_usuario(),
    ], $id ? 'Editar consumo' : 'Registrar consumo');
}

function salvar_consumo_suplemento(): void
{
    exigir_autenticacao();
    somente_post();
    validar_csrf();

    $id = filter_var($_POST['cos_id'] ?? null, FILTER_VALIDATE_INT) ?: null;
    $suplementoId = filter_var($_POST['sup_id'] ?? null, FILTER_VALIDATE_INT);
    $dataInformada = trim((string) ($_POST['cos_data_consumo'] ?? ''));
    $data = normalizar_data_hora($dataInformada);
    $doseInformada = $_POST['cos_dose'] ?? null;
    $dose = normalizar_decimal($doseInformada);
    $unidade = trim((string) ($_POST['cos_unidade'] ?? ''));
    $reacoes = trim((string) ($_POST['cos_reacoes'] ?? ''));
    $observacoes = trim((string) ($_POST['cos_observacoes'] ?? ''));
    $feedbackAnterior = trim((string) ($_POST['feedback_consumo_anterior'] ?? ''));
    $feedbackCarregado = ($_POST['feedback_consumo_anterior_carregado'] ?? '0') === '1';
    $erros = [];

    $suplementoSelecionado = $suplementoId ? buscar_suplemento((int) $suplementoId) : false;
    if (!$suplementoSelecionado) {
        $erros[] = 'Selecione um suplemento válido.';
    }
    if ($data === null) {
        $erros[] = 'Informe uma data e hora válidas.';
    }
    if ($dose === null || $dose <= 0) {
        $erros[] = 'Informe uma dose maior que zero.';
    }
    if (!array_key_exists($unidade, UNIDADES_SUPLEMENTO)) {
        $erros[] = 'Selecione uma unidade de dose válida.';
    }
    if (!$id && mb_strlen($feedbackAnterior) > 250) {
        $erros[] = 'O feedback do consumo anterior deve ter no máximo 250 caracteres.';
    }
    if (mb_strlen($observacoes) > 250) {
        $erros[] = 'As observações do consumo devem ter no máximo 250 caracteres.';
    }

    if ($erros) {
        foreach ($erros as $erro) {
            flash('erro', $erro);
        }
        $consumo = $_POST;
        renderizar('suplementos/consumo_formulario', ['consumo' => $consumo, 'suplementos' => suplementos_do_usuario()], $id ? 'Editar consumo' : 'Registrar consumo');
        return;
    }

    $parametros = [
        'suplemento' => $suplementoId,
        'data' => $data,
        'dose' => $dose,
        'unidade' => $unidade,
        'reacoes' => $reacoes ?: null,
        'observacoes' => $observacoes ?: null,
    ];

    if ($id) {
        if (!buscar_consumo_suplemento((int) $id)) {
            flash('erro', 'Consumo não encontrado.');
            redirecionar('/suplementos/consumos');
        }
        $parametros['id'] = $id;
        $sql = 'UPDATE cos_consumo_suplemento SET sup_id = :suplemento, cos_data_consumo = :data,
                    cos_dose = :dose, cos_unidade = :unidade, cos_reacoes = :reacoes,
                    cos_observacoes = :observacoes
                WHERE cos_id = :id';
        $mensagem = 'Consumo atualizado com sucesso.';
    } else {
        $sql = 'INSERT INTO cos_consumo_suplemento
                    (sup_id, cos_data_consumo, cos_dose, cos_unidade, cos_reacoes, cos_observacoes)
                VALUES (:suplemento, :data, :dose, :unidade, :reacoes, :observacoes)';
        $mensagem = 'Consumo registrado com sucesso.';
    }

    $conexao = banco();
    $conexao->beginTransaction();

    try {
        if (!$id && $feedbackCarregado && !empty($suplementoSelecionado['sup_solicitar_feedback'])) {
            $consumoAnterior = buscar_ultimo_consumo_suplemento((int) $suplementoId);

            if ($consumoAnterior) {
                $atualizarFeedback = $conexao->prepare(
                    'UPDATE cos_consumo_suplemento SET cos_observacoes = :feedback WHERE cos_id = :consumo'
                );
                $atualizarFeedback->execute([
                    'feedback' => $feedbackAnterior ?: null,
                    'consumo' => $consumoAnterior['cos_id'],
                ]);
            }
        }

        $conexao->prepare($sql)->execute($parametros);
        $conexao->commit();
    } catch (Throwable $erro) {
        if ($conexao->inTransaction()) {
            $conexao->rollBack();
        }
        throw $erro;
    }

    flash('sucesso', $mensagem);
    redirecionar('/suplementos/consumos');
}

function excluir_consumo_suplemento(): void
{
    exigir_autenticacao();
    somente_post();
    validar_csrf();
    $id = filter_var($_POST['cos_id'] ?? null, FILTER_VALIDATE_INT);
    $consulta = banco()->prepare(
        'DELETE cos FROM cos_consumo_suplemento cos
         INNER JOIN sup_suplemento sup ON sup.sup_id = cos.sup_id
         WHERE cos.cos_id = :id AND sup.usu_id = :usuario'
    );
    $consulta->execute(['id' => $id ?: 0, 'usuario' => usuario_atual()['id']]);
    flash($consulta->rowCount() ? 'sucesso' : 'erro', $consulta->rowCount() ? 'Consumo excluído.' : 'Consumo não encontrado.');
    redirecionar('/suplementos/consumos');
}

function buscar_suplemento(int $id): array|false
{
    $consulta = banco()->prepare('SELECT * FROM sup_suplemento WHERE sup_id = :id AND usu_id = :usuario LIMIT 1');
    $consulta->execute(['id' => $id, 'usuario' => usuario_atual()['id']]);
    return $consulta->fetch();
}

function suplementos_do_usuario(): array
{
    $consulta = banco()->prepare(
        'SELECT sup_id, sup_nome, sup_marca, sup_apresentacao, sup_ativo FROM sup_suplemento
         WHERE usu_id = :usuario ORDER BY sup_ativo DESC, sup_nome'
    );
    $consulta->execute(['usuario' => usuario_atual()['id']]);
    return $consulta->fetchAll();
}

function buscar_compra_suplemento(int $id): array|false
{
    $consulta = banco()->prepare(
        'SELECT cps.* FROM cps_compra_suplemento cps
         INNER JOIN sup_suplemento sup ON sup.sup_id = cps.sup_id
         WHERE cps.cps_id = :id AND sup.usu_id = :usuario LIMIT 1'
    );
    $consulta->execute(['id' => $id, 'usuario' => usuario_atual()['id']]);
    return $consulta->fetch();
}

function buscar_consumo_suplemento(int $id): array|false
{
    $consulta = banco()->prepare(
        'SELECT cos.* FROM cos_consumo_suplemento cos
         INNER JOIN sup_suplemento sup ON sup.sup_id = cos.sup_id
         WHERE cos.cos_id = :id AND sup.usu_id = :usuario LIMIT 1'
    );
    $consulta->execute(['id' => $id, 'usuario' => usuario_atual()['id']]);
    return $consulta->fetch();
}

function buscar_ultimo_consumo_suplemento(int $suplementoId): array|false
{
    $consulta = banco()->prepare(
        'SELECT cos.* FROM cos_consumo_suplemento cos
         INNER JOIN sup_suplemento sup ON sup.sup_id = cos.sup_id
         WHERE cos.sup_id = :suplemento AND sup.usu_id = :usuario
         ORDER BY cos.cos_data_consumo DESC, cos.cos_id DESC
         LIMIT 1'
    );
    $consulta->execute([
        'suplemento' => $suplementoId,
        'usuario' => usuario_atual()['id'],
    ]);
    return $consulta->fetch();
}
