<?php

declare(strict_types=1);

const VIAS_MEDICAMENTO = [
    'oral' => 'Oral',
    'subcutanea' => 'Subcutânea',
    'intramuscular' => 'Intramuscular',
    'intravenosa' => 'Intravenosa',
    'topica' => 'Tópica',
    'inalatoria' => 'Inalatória',
    'outra' => 'Outra',
];

const UNIDADES_DOSE = [
    'mg' => 'mg',
    'mcg' => 'mcg',
    'g' => 'g',
    'ml' => 'mL',
    'ui' => 'UI',
    'comprimido' => 'comprimido(s)',
    'capsula' => 'cápsula(s)',
    'dose' => 'dose(s)',
];

function listar_medicamentos(): void
{
    exigir_autenticacao();
    $consulta = banco()->prepare(
        'SELECT med.*,
                (SELECT MAX(apl.apl_data_aplicacao) FROM apl_aplicacao_medicamento apl WHERE apl.med_id = med.med_id) AS ultima_aplicacao,
                (SELECT COUNT(*) FROM com_compra_medicamento com WHERE com.med_id = med.med_id) AS total_compras,
                (SELECT COUNT(*) FROM apl_aplicacao_medicamento apl WHERE apl.med_id = med.med_id) AS total_aplicacoes
         FROM med_medicamento med
         WHERE med.usu_id = :usuario
         ORDER BY med.med_ativo DESC, med.med_nome'
    );
    $consulta->execute(['usuario' => usuario_atual()['id']]);

    renderizar('medicamentos/lista', ['medicamentos' => $consulta->fetchAll()], 'Medicamentos');
}

function formulario_medicamento(): void
{
    exigir_autenticacao();
    $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
    $medicamento = ['med_ativo' => 1, 'med_solicitar_feedback' => 0];

    if ($id) {
        $medicamento = buscar_medicamento((int) $id);
        if (!$medicamento) {
            flash('erro', 'Medicamento não encontrado.');
            redirecionar('/medicamentos');
        }
    }

    renderizar('medicamentos/formulario', compact('medicamento'), $id ? 'Editar medicamento' : 'Novo medicamento');
}

function salvar_medicamento(): void
{
    exigir_autenticacao();
    somente_post();
    validar_csrf();

    $id = filter_var($_POST['med_id'] ?? null, FILTER_VALIDATE_INT) ?: null;
    $nome = trim((string) ($_POST['med_nome'] ?? ''));
    $apresentacao = trim((string) ($_POST['med_apresentacao'] ?? ''));
    $via = trim((string) ($_POST['med_via_administracao'] ?? ''));
    $orientacoes = trim((string) ($_POST['med_orientacoes'] ?? ''));
    $observacoes = trim((string) ($_POST['med_observacoes'] ?? ''));
    $ativo = isset($_POST['med_ativo']) ? 1 : 0;
    $solicitarFeedback = isset($_POST['med_solicitar_feedback']) ? 1 : 0;
    $erros = [];

    if ($nome === '') {
        $erros[] = 'Informe o nome do medicamento.';
    } elseif (mb_strlen($nome) > 120) {
        $erros[] = 'O nome deve ter no máximo 120 caracteres.';
    }

    if ($via !== '' && !array_key_exists($via, VIAS_MEDICAMENTO)) {
        $erros[] = 'Selecione uma via de administração válida.';
    }

    if ($erros) {
        foreach ($erros as $erro) {
            flash('erro', $erro);
        }
        $medicamento = $_POST;
        $medicamento['med_ativo'] = $ativo;
        $medicamento['med_solicitar_feedback'] = $solicitarFeedback;
        renderizar('medicamentos/formulario', compact('medicamento'), $id ? 'Editar medicamento' : 'Novo medicamento');
        return;
    }

    $parametros = [
        'usuario' => usuario_atual()['id'],
        'nome' => $nome,
        'apresentacao' => $apresentacao ?: null,
        'via' => $via ?: null,
        'orientacoes' => $orientacoes ?: null,
        'observacoes' => $observacoes ?: null,
        'ativo' => $ativo,
        'solicitar_feedback' => $solicitarFeedback,
    ];

    if ($id) {
        if (!buscar_medicamento((int) $id)) {
            flash('erro', 'Medicamento não encontrado.');
            redirecionar('/medicamentos');
        }
        $parametros['id'] = $id;
        $sql = 'UPDATE med_medicamento SET med_nome = :nome, med_apresentacao = :apresentacao,
                    med_via_administracao = :via, med_orientacoes = :orientacoes,
                    med_observacoes = :observacoes, med_ativo = :ativo,
                    med_solicitar_feedback = :solicitar_feedback
                WHERE med_id = :id AND usu_id = :usuario';
        $mensagem = 'Medicamento atualizado com sucesso.';
    } else {
        $sql = 'INSERT INTO med_medicamento
                    (usu_id, med_nome, med_apresentacao, med_via_administracao, med_orientacoes,
                     med_observacoes, med_ativo, med_solicitar_feedback)
                VALUES (:usuario, :nome, :apresentacao, :via, :orientacoes,
                        :observacoes, :ativo, :solicitar_feedback)';
        $mensagem = 'Medicamento cadastrado com sucesso.';
    }

    banco()->prepare($sql)->execute($parametros);
    flash('sucesso', $mensagem);
    redirecionar('/medicamentos');
}

function listar_compras_medicamento(): void
{
    exigir_autenticacao();
    $consulta = banco()->prepare(
        'SELECT com.*, med.med_nome, med.med_apresentacao
         FROM com_compra_medicamento com
         INNER JOIN med_medicamento med ON med.med_id = com.med_id
         WHERE med.usu_id = :usuario
         ORDER BY com.com_data_compra DESC, com.com_id DESC'
    );
    $consulta->execute(['usuario' => usuario_atual()['id']]);

    renderizar('medicamentos/compras_lista', ['compras' => $consulta->fetchAll()], 'Compras de medicamentos');
}

function formulario_compra_medicamento(): void
{
    exigir_autenticacao();
    $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
    $medicamentoId = filter_input(INPUT_GET, 'medicamento', FILTER_VALIDATE_INT);
    $compra = ['com_data_compra' => date('Y-m-d'), 'med_id' => $medicamentoId ?: ''];

    if ($id) {
        $compra = buscar_compra_medicamento((int) $id);
        if (!$compra) {
            flash('erro', 'Compra não encontrada.');
            redirecionar('/medicamentos/compras');
        }
    }

    renderizar('medicamentos/compra_formulario', [
        'compra' => $compra,
        'medicamentos' => medicamentos_do_usuario(),
    ], $id ? 'Editar compra' : 'Registrar compra');
}

function salvar_compra_medicamento(): void
{
    exigir_autenticacao();
    somente_post();
    validar_csrf();

    $id = filter_var($_POST['com_id'] ?? null, FILTER_VALIDATE_INT) ?: null;
    $medicamentoId = filter_var($_POST['med_id'] ?? null, FILTER_VALIDATE_INT);
    $data = trim((string) ($_POST['com_data_compra'] ?? ''));
    $quantidadeInformada = $_POST['com_quantidade'] ?? null;
    $quantidade = normalizar_decimal($quantidadeInformada);
    $valorInformado = $_POST['com_valor'] ?? null;
    $valor = normalizar_decimal($valorInformado);
    $lote = trim((string) ($_POST['com_lote'] ?? ''));
    $validade = trim((string) ($_POST['com_data_validade'] ?? ''));
    $observacoes = trim((string) ($_POST['com_observacoes'] ?? ''));
    $erros = [];

    $medicamentoSelecionado = $medicamentoId ? buscar_medicamento((int) $medicamentoId) : false;
    if (!$medicamentoSelecionado) {
        $erros[] = 'Selecione um medicamento válido.';
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
        renderizar('medicamentos/compra_formulario', ['compra' => $compra, 'medicamentos' => medicamentos_do_usuario()], $id ? 'Editar compra' : 'Registrar compra');
        return;
    }

    $parametros = [
        'medicamento' => $medicamentoId,
        'data' => $data,
        'quantidade' => $quantidade,
        'valor' => $valor,
        'lote' => $lote ?: null,
        'validade' => $validade ?: null,
        'observacoes' => $observacoes ?: null,
    ];

    if ($id) {
        if (!buscar_compra_medicamento((int) $id)) {
            flash('erro', 'Compra não encontrada.');
            redirecionar('/medicamentos/compras');
        }
        $parametros['id'] = $id;
        $sql = 'UPDATE com_compra_medicamento SET med_id = :medicamento, com_data_compra = :data,
                    com_quantidade = :quantidade, com_valor = :valor, com_lote = :lote,
                    com_data_validade = :validade, com_observacoes = :observacoes
                WHERE com_id = :id';
        $mensagem = 'Compra atualizada com sucesso.';
    } else {
        $sql = 'INSERT INTO com_compra_medicamento
                    (med_id, com_data_compra, com_quantidade, com_valor, com_lote, com_data_validade, com_observacoes)
                VALUES (:medicamento, :data, :quantidade, :valor, :lote, :validade, :observacoes)';
        $mensagem = 'Compra registrada com sucesso.';
    }

    banco()->prepare($sql)->execute($parametros);
    flash('sucesso', $mensagem);
    redirecionar('/medicamentos/compras');
}

function excluir_compra_medicamento(): void
{
    exigir_autenticacao();
    somente_post();
    validar_csrf();
    $id = filter_var($_POST['com_id'] ?? null, FILTER_VALIDATE_INT);
    $consulta = banco()->prepare(
        'DELETE com FROM com_compra_medicamento com
         INNER JOIN med_medicamento med ON med.med_id = com.med_id
         WHERE com.com_id = :id AND med.usu_id = :usuario'
    );
    $consulta->execute(['id' => $id ?: 0, 'usuario' => usuario_atual()['id']]);
    flash($consulta->rowCount() ? 'sucesso' : 'erro', $consulta->rowCount() ? 'Compra excluída.' : 'Compra não encontrada.');
    redirecionar('/medicamentos/compras');
}

function listar_aplicacoes_medicamento(): void
{
    exigir_autenticacao();
    $consulta = banco()->prepare(
        'SELECT apl.*, med.med_nome, med.med_apresentacao
         FROM apl_aplicacao_medicamento apl
         INNER JOIN med_medicamento med ON med.med_id = apl.med_id
         WHERE med.usu_id = :usuario
         ORDER BY apl.apl_data_aplicacao DESC, apl.apl_id DESC'
    );
    $consulta->execute(['usuario' => usuario_atual()['id']]);

    renderizar('medicamentos/aplicacoes_lista', ['aplicacoes' => $consulta->fetchAll()], 'Aplicações de medicamentos');
}
function obter_feedback_ultima_aplicacao(): void
{
    exigir_autenticacao();
    header("Content-Type: application/json; charset=UTF-8");

    $medicamentoId = filter_var($_GET["medicamento"] ?? null, FILTER_VALIDATE_INT);
    $medicamento = $medicamentoId ? buscar_medicamento((int) $medicamentoId) : false;

    if (!$medicamento || !(bool) $medicamento["med_solicitar_feedback"]) {
        echo json_encode(["solicitar" => false], JSON_UNESCAPED_UNICODE);
        return;
    }

    $ultimaAplicacao = buscar_ultima_aplicacao_medicamento((int) $medicamentoId);
    echo json_encode([
        "solicitar" => $ultimaAplicacao !== false,
        "aplicacao" => $ultimaAplicacao ? [
            "data" => formatar_data_hora($ultimaAplicacao["apl_data_aplicacao"]),
            "dose" => formatar_decimal($ultimaAplicacao["apl_dose"]),
            "unidade" => UNIDADES_DOSE[$ultimaAplicacao["apl_unidade"]] ?? $ultimaAplicacao["apl_unidade"],
            "feedback" => $ultimaAplicacao["apl_observacoes"] ?? "",
        ] : null,
    ], JSON_UNESCAPED_UNICODE);
}


function formulario_aplicacao_medicamento(): void
{
    exigir_autenticacao();
    $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
    $medicamentoId = filter_input(INPUT_GET, 'medicamento', FILTER_VALIDATE_INT);
    $aplicacao = ['apl_data_aplicacao' => date('Y-m-d\TH:i'), 'med_id' => $medicamentoId ?: ''];

    if ($id) {
        $aplicacao = buscar_aplicacao_medicamento((int) $id);
        if (!$aplicacao) {
            flash('erro', 'Aplicação não encontrada.');
            redirecionar('/medicamentos/aplicacoes');
        }
        $aplicacao['apl_data_aplicacao'] = (new DateTimeImmutable($aplicacao['apl_data_aplicacao']))->format('Y-m-d\TH:i');
    }

    renderizar('medicamentos/aplicacao_formulario', [
        'aplicacao' => $aplicacao,
        'medicamentos' => medicamentos_do_usuario(),
    ], $id ? 'Editar aplicação' : 'Registrar aplicação');
}

function salvar_aplicacao_medicamento(): void
{
    exigir_autenticacao();
    somente_post();
    validar_csrf();

    $id = filter_var($_POST['apl_id'] ?? null, FILTER_VALIDATE_INT) ?: null;
    $medicamentoId = filter_var($_POST['med_id'] ?? null, FILTER_VALIDATE_INT);
    $dataInformada = trim((string) ($_POST['apl_data_aplicacao'] ?? ''));
    $data = normalizar_data_hora($dataInformada);
    $doseInformada = $_POST['apl_dose'] ?? null;
    $dose = normalizar_decimal($doseInformada);
    $unidade = trim((string) ($_POST['apl_unidade'] ?? ''));
    $local = trim((string) ($_POST['apl_local_aplicacao'] ?? ''));
    $reacoes = trim((string) ($_POST['apl_reacoes'] ?? ''));
    $observacoes = trim((string) ($_POST['apl_observacoes'] ?? ''));
    $feedbackAnterior = trim((string) ($_POST["feedback_aplicacao_anterior"] ?? ""));
    $feedbackCarregado = ($_POST["feedback_aplicacao_anterior_carregado"] ?? "0") === "1";
    $erros = [];

    $medicamentoSelecionado = $medicamentoId ? buscar_medicamento((int) $medicamentoId) : false;
    if (!$medicamentoSelecionado) {
        $erros[] = 'Selecione um medicamento válido.';
    }
    if ($data === null) {
        $erros[] = 'Informe uma data e hora válidas.';
    }
    if ($dose === null || $dose <= 0) {
        $erros[] = 'Informe uma dose maior que zero.';
    }
    if (!array_key_exists($unidade, UNIDADES_DOSE)) {
        $erros[] = 'Selecione uma unidade de dose válida.';
    }

    if (!$id && mb_strlen($feedbackAnterior) > 250) {
        $erros[] = "O feedback da aplicação anterior deve ter no máximo 250 caracteres.";
    }

    if (mb_strlen($observacoes) > 250) {
        $erros[] = "As observações da aplicação devem ter no máximo 250 caracteres.";
    }
    if ($erros) {
        foreach ($erros as $erro) {
            flash('erro', $erro);
        }
        $aplicacao = $_POST;
        renderizar('medicamentos/aplicacao_formulario', ['aplicacao' => $aplicacao, 'medicamentos' => medicamentos_do_usuario()], $id ? 'Editar aplicação' : 'Registrar aplicação');
        return;
    }

    $parametros = [
        'medicamento' => $medicamentoId,
        'data' => $data,
        'dose' => $dose,
        'unidade' => $unidade,
        'local' => $local ?: null,
        'reacoes' => $reacoes ?: null,
        'observacoes' => $observacoes ?: null,
    ];

    if ($id) {
        if (!buscar_aplicacao_medicamento((int) $id)) {
            flash('erro', 'Aplicação não encontrada.');
            redirecionar('/medicamentos/aplicacoes');
        }
        $parametros['id'] = $id;
        $sql = 'UPDATE apl_aplicacao_medicamento SET med_id = :medicamento, apl_data_aplicacao = :data,
                    apl_dose = :dose, apl_unidade = :unidade, apl_local_aplicacao = :local,
                    apl_reacoes = :reacoes, apl_observacoes = :observacoes
                WHERE apl_id = :id';
        $mensagem = 'Aplicação atualizada com sucesso.';
    } else {
        $sql = 'INSERT INTO apl_aplicacao_medicamento
                    (med_id, apl_data_aplicacao, apl_dose, apl_unidade, apl_local_aplicacao, apl_reacoes, apl_observacoes)
                VALUES (:medicamento, :data, :dose, :unidade, :local, :reacoes, :observacoes)';
        $mensagem = 'Aplicação registrada com sucesso.';
    }

    $conexao = banco();
    $conexao->beginTransaction();

    try {
        if (!$id && $feedbackCarregado && !empty($medicamentoSelecionado["med_solicitar_feedback"])) {
            $aplicacaoAnterior = buscar_ultima_aplicacao_medicamento((int) $medicamentoId);

            if ($aplicacaoAnterior) {
                $atualizarFeedback = $conexao->prepare(
                    "UPDATE apl_aplicacao_medicamento
                     SET apl_observacoes = :feedback
                     WHERE apl_id = :aplicacao"
                );
                $atualizarFeedback->execute([
                    "feedback" => $feedbackAnterior ?: null,
                    "aplicacao" => $aplicacaoAnterior["apl_id"],
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
    redirecionar('/medicamentos/aplicacoes');
}

function excluir_aplicacao_medicamento(): void
{
    exigir_autenticacao();
    somente_post();
    validar_csrf();
    $id = filter_var($_POST['apl_id'] ?? null, FILTER_VALIDATE_INT);
    $consulta = banco()->prepare(
        'DELETE apl FROM apl_aplicacao_medicamento apl
         INNER JOIN med_medicamento med ON med.med_id = apl.med_id
         WHERE apl.apl_id = :id AND med.usu_id = :usuario'
    );
    $consulta->execute(['id' => $id ?: 0, 'usuario' => usuario_atual()['id']]);
    flash($consulta->rowCount() ? 'sucesso' : 'erro', $consulta->rowCount() ? 'Aplicação excluída.' : 'Aplicação não encontrada.');
    redirecionar('/medicamentos/aplicacoes');
}

function buscar_medicamento(int $id): array|false
{
    $consulta = banco()->prepare('SELECT * FROM med_medicamento WHERE med_id = :id AND usu_id = :usuario LIMIT 1');
    $consulta->execute(['id' => $id, 'usuario' => usuario_atual()['id']]);
    return $consulta->fetch();
}

function medicamentos_do_usuario(): array
{
    $consulta = banco()->prepare(
        'SELECT med_id, med_nome, med_apresentacao, med_ativo FROM med_medicamento
         WHERE usu_id = :usuario ORDER BY med_ativo DESC, med_nome'
    );
    $consulta->execute(['usuario' => usuario_atual()['id']]);
    return $consulta->fetchAll();
}

function buscar_compra_medicamento(int $id): array|false
{
    $consulta = banco()->prepare(
        'SELECT com.* FROM com_compra_medicamento com
         INNER JOIN med_medicamento med ON med.med_id = com.med_id
         WHERE com.com_id = :id AND med.usu_id = :usuario LIMIT 1'
    );
    $consulta->execute(['id' => $id, 'usuario' => usuario_atual()['id']]);
    return $consulta->fetch();
}

function buscar_aplicacao_medicamento(int $id): array|false
{
    $consulta = banco()->prepare(
        'SELECT apl.* FROM apl_aplicacao_medicamento apl
         INNER JOIN med_medicamento med ON med.med_id = apl.med_id
         WHERE apl.apl_id = :id AND med.usu_id = :usuario LIMIT 1'
    );
    $consulta->execute(['id' => $id, 'usuario' => usuario_atual()['id']]);
    return $consulta->fetch();
}


function buscar_ultima_aplicacao_medicamento(int $medicamentoId): array|false
{
    $consulta = banco()->prepare(
        "SELECT apl.* FROM apl_aplicacao_medicamento apl
         INNER JOIN med_medicamento med ON med.med_id = apl.med_id
         WHERE apl.med_id = :medicamento AND med.usu_id = :usuario
         ORDER BY apl.apl_data_aplicacao DESC, apl.apl_id DESC
         LIMIT 1"
    );
    $consulta->execute([
        "medicamento" => $medicamentoId,
        "usuario" => usuario_atual()["id"],
    ]);

    return $consulta->fetch();
}
