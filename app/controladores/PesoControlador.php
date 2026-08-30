<?php

declare(strict_types=1);

function listar_pesos(): void
{
    exigir_autenticacao();

    $consulta = banco()->prepare(
        'SELECT * FROM pes_peso_corporal
         WHERE usu_id = :usuario
         ORDER BY pes_data_pesagem DESC, pes_id DESC'
    );
    $consulta->execute(['usuario' => usuario_atual()['id']]);

    renderizar('pesos/lista', ['pesos' => $consulta->fetchAll()], 'Histórico de peso');
}

function formulario_peso(): void
{
    exigir_autenticacao();
    $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
    $peso = ['pes_data_pesagem' => date('Y-m-d')];

    if ($id) {
        $pesoEncontrado = buscar_peso((int) $id);

        if (!$pesoEncontrado) {
            flash('erro', 'Registro de peso não encontrado.');
            redirecionar('/pesos');
        }

        $peso = $pesoEncontrado;
    }

    renderizar('pesos/formulario', compact('peso'), $id ? 'Editar peso' : 'Registrar peso');
}

function salvar_peso(): void
{
    exigir_autenticacao();
    somente_post();
    validar_csrf();

    $id = filter_var($_POST['pes_id'] ?? null, FILTER_VALIDATE_INT) ?: null;
    $data = trim((string) ($_POST['pes_data_pesagem'] ?? ''));
    $valorInformado = $_POST['pes_peso_kg'] ?? null;
    $valor = normalizar_decimal($valorInformado);
    $gorduraInformada = $_POST["pes_percentual_gordura"] ?? null;
    $percentualGordura = normalizar_decimal($gorduraInformada);
    $massaMagraInformada = $_POST["pes_massa_magra_kg"] ?? null;
    $massaMagra = normalizar_decimal($massaMagraInformada);
    $observacoes = trim((string) ($_POST['pes_observacoes'] ?? ''));
    $erros = [];

    $dataValida = DateTimeImmutable::createFromFormat('!Y-m-d', $data);
    if (!$dataValida || $dataValida->format('Y-m-d') !== $data) {
        $erros[] = 'Informe uma data de pesagem válida.';
    }

    if ($valor === null) {
        $erros[] = 'Informe o peso.';
    } elseif ($valor <= 0 || $valor > 500) {
        $erros[] = 'O peso deve ser maior que zero e menor ou igual a 500 kg.';
    }

    if (trim((string) $gorduraInformada) !== "" && $percentualGordura === null) {
        $erros[] = "O percentual de gordura deve ser numérico.";
    } elseif ($percentualGordura !== null && ($percentualGordura < 0 || $percentualGordura > 100)) {
        $erros[] = "O percentual de gordura deve estar entre 0 e 100%.";
    }

    if (trim((string) $massaMagraInformada) !== "" && $massaMagra === null) {
        $erros[] = "A massa magra deve ser numérica.";
    } elseif ($massaMagra !== null && ($massaMagra < 0 || $massaMagra > 500)) {
        $erros[] = "A massa magra deve estar entre 0 e 500 kg.";
    }

    if ($erros) {
        foreach ($erros as $erro) {
            flash('erro', $erro);
        }

        $peso = $_POST;
        renderizar('pesos/formulario', compact('peso'), $id ? 'Editar peso' : 'Registrar peso');
        return;
    }

    $parametros = [
        'usuario' => usuario_atual()['id'],
        'data_pesagem' => $data,
        'peso_kg' => $valor,
        "percentual_gordura" => $percentualGordura,
        "massa_magra_kg" => $massaMagra,
        'observacoes' => $observacoes ?: null,
    ];

    if ($id) {
        if (!buscar_peso((int) $id)) {
            flash('erro', 'Registro de peso não encontrado.');
            redirecionar('/pesos');
        }

        $parametros['id'] = $id;
        $sql = 'UPDATE pes_peso_corporal SET
                    pes_data_pesagem = :data_pesagem,
                    pes_peso_kg = :peso_kg,
                    pes_percentual_gordura = :percentual_gordura,
                    pes_massa_magra_kg = :massa_magra_kg,
                    pes_observacoes = :observacoes
                WHERE pes_id = :id AND usu_id = :usuario';
        $mensagem = 'Peso atualizado com sucesso.';
    } else {
        $sql = 'INSERT INTO pes_peso_corporal (
                    usu_id, pes_data_pesagem, pes_peso_kg, pes_percentual_gordura,
                    pes_massa_magra_kg, pes_observacoes
                ) VALUES (
                    :usuario, :data_pesagem, :peso_kg, :percentual_gordura,
                    :massa_magra_kg, :observacoes
                )';
        $mensagem = 'Peso registrado com sucesso.';
    }

    banco()->prepare($sql)->execute($parametros);
    flash('sucesso', $mensagem);
    redirecionar('/pesos');
}

function excluir_peso(): void
{
    exigir_autenticacao();
    somente_post();
    validar_csrf();

    $id = filter_var($_POST['pes_id'] ?? null, FILTER_VALIDATE_INT);

    if (!$id) {
        flash('erro', 'Registro de peso inválido.');
        redirecionar('/pesos');
    }

    $excluir = banco()->prepare(
        'DELETE FROM pes_peso_corporal WHERE pes_id = :id AND usu_id = :usuario'
    );
    $excluir->execute(['id' => $id, 'usuario' => usuario_atual()['id']]);

    $excluido = $excluir->rowCount() > 0;
    flash($excluido ? 'sucesso' : 'erro', $excluido ? 'Registro de peso excluído.' : 'Registro de peso não encontrado.');
    redirecionar('/pesos');
}

function buscar_peso(int $id): array|false
{
    $consulta = banco()->prepare(
        'SELECT * FROM pes_peso_corporal WHERE pes_id = :id AND usu_id = :usuario LIMIT 1'
    );
    $consulta->execute(['id' => $id, 'usuario' => usuario_atual()['id']]);

    return $consulta->fetch();
}

