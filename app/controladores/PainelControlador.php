<?php

declare(strict_types=1);

function exibir_painel(): void
{
    exigir_autenticacao();
    $usuario = usuario_atual();
    $hoje = date('Y-m-d');

    $consultaPesos = banco()->prepare(
        'SELECT * FROM pes_peso_corporal
         WHERE usu_id = :usuario
         ORDER BY pes_data_pesagem DESC, pes_id DESC
         LIMIT 2'
    );
    $consultaPesos->execute(['usuario' => $usuario['id']]);
    $pesos = $consultaPesos->fetchAll();

    $ultimoPeso = $pesos[0] ?? null;
    $pesoAnterior = $pesos[1] ?? null;
    $variacaoPeso = null;

    if ($ultimoPeso && $pesoAnterior && $ultimoPeso['pes_peso_kg'] !== null && $pesoAnterior['pes_peso_kg'] !== null) {
        $variacaoPeso = (float) $ultimoPeso['pes_peso_kg'] - (float) $pesoAnterior['pes_peso_kg'];
    }

    $consultaMedicoes = banco()->prepare(
        'SELECT mec_id, mec_data_medicao, mec_cintura_cm, mec_abdomen_cm
           FROM mec_medicao_corporal
          WHERE usu_id = :usuario
          ORDER BY mec_data_medicao DESC, mec_id DESC
          LIMIT 2'
    );
    $consultaMedicoes->execute(['usuario' => $usuario['id']]);
    $medicoes = $consultaMedicoes->fetchAll();
    $ultimaMedicao = $medicoes[0] ?? null;
    $medicaoAnterior = $medicoes[1] ?? null;
    $variacaoCintura = null;

    if ($ultimaMedicao && $medicaoAnterior && $ultimaMedicao['mec_cintura_cm'] !== null && $medicaoAnterior['mec_cintura_cm'] !== null) {
        $variacaoCintura = (float) $ultimaMedicao['mec_cintura_cm'] - (float) $medicaoAnterior['mec_cintura_cm'];
    }

    $consultaPlanoAlimentar = banco()->prepare(
        'SELECT pal.pal_id, pal.pal_nome, COUNT(ref.ref_id) AS total_refeicoes
           FROM pal_plano_alimentar pal
           LEFT JOIN ref_refeicao_plano ref ON ref.pal_id = pal.pal_id
          WHERE pal.usu_id = :usuario AND pal.pal_ativo = 1
            AND pal.pal_data_inicio <= :inicio
            AND (pal.pal_data_fim IS NULL OR pal.pal_data_fim >= :fim)
          GROUP BY pal.pal_id, pal.pal_nome
          ORDER BY pal.pal_data_inicio DESC, pal.pal_id DESC
          LIMIT 1'
    );
    $consultaPlanoAlimentar->execute(['usuario' => $usuario['id'], 'inicio' => $hoje, 'fim' => $hoje]);
    $planoAlimentarHoje = $consultaPlanoAlimentar->fetch() ?: null;
    $resumoAlimentacaoHoje = ['registradas' => 0, 'aderencia' => null];

    if ($planoAlimentarHoje) {
        $consultaResumoAlimentacao = banco()->prepare(
            "SELECT COUNT(*) AS registradas,
                    ROUND(AVG(CASE ral_situacao
                        WHEN 'conforme' THEN 100
                        WHEN 'substituida' THEN 75
                        WHEN 'parcial' THEN 50
                        WHEN 'nao_realizada' THEN 0
                        ELSE NULL END)) AS aderencia
               FROM ral_registro_alimentar
              WHERE usu_id = :usuario AND pal_id = :plano AND ral_data = :hoje"
        );
        $consultaResumoAlimentacao->execute([
            'usuario' => $usuario['id'],
            'plano' => $planoAlimentarHoje['pal_id'],
            'hoje' => $hoje,
        ]);
        $resumoAlimentacaoHoje = $consultaResumoAlimentacao->fetch() ?: $resumoAlimentacaoHoje;
    }

    $consultaMedicamentos = banco()->prepare(
        'SELECT COUNT(*) FROM med_medicamento WHERE usu_id = :usuario AND med_ativo = 1'
    );
    $consultaMedicamentos->execute(['usuario' => $usuario['id']]);
    $totalMedicamentosAtivos = (int) $consultaMedicamentos->fetchColumn();

    $consultaAplicacao = banco()->prepare(
        'SELECT apl.apl_data_aplicacao, apl.apl_dose, apl.apl_unidade, med.med_nome
           FROM apl_aplicacao_medicamento apl
           INNER JOIN med_medicamento med ON med.med_id = apl.med_id
          WHERE med.usu_id = :usuario
          ORDER BY apl.apl_data_aplicacao DESC, apl.apl_id DESC
          LIMIT 1'
    );
    $consultaAplicacao->execute(['usuario' => $usuario['id']]);
    $ultimaAplicacao = $consultaAplicacao->fetch() ?: null;

    $consultaCompra = banco()->prepare(
        'SELECT com.com_data_compra, med.med_nome
           FROM com_compra_medicamento com
           INNER JOIN med_medicamento med ON med.med_id = com.med_id
          WHERE med.usu_id = :usuario
          ORDER BY com.com_data_compra DESC, com.com_id DESC
          LIMIT 1'
    );
    $consultaCompra->execute(['usuario' => $usuario['id']]);
    $ultimaCompra = $consultaCompra->fetch() ?: null;

    $consultaSuplementos = banco()->prepare(
        'SELECT COUNT(*) FROM sup_suplemento WHERE usu_id = :usuario AND sup_ativo = 1'
    );
    $consultaSuplementos->execute(['usuario' => $usuario['id']]);
    $totalSuplementosAtivos = (int) $consultaSuplementos->fetchColumn();

    $consultaUltimoConsumoSuplemento = banco()->prepare(
        'SELECT cos.cos_data_consumo, cos.cos_dose, cos.cos_unidade, sup.sup_nome
           FROM cos_consumo_suplemento cos
           INNER JOIN sup_suplemento sup ON sup.sup_id = cos.sup_id
          WHERE sup.usu_id = :usuario
          ORDER BY cos.cos_data_consumo DESC, cos.cos_id DESC
          LIMIT 1'
    );
    $consultaUltimoConsumoSuplemento->execute(['usuario' => $usuario['id']]);
    $ultimoConsumoSuplemento = $consultaUltimoConsumoSuplemento->fetch() ?: null;

    $consultaTreinosSemana = banco()->prepare(
        "SELECT COUNT(*) FROM trr_treino_realizado
         WHERE usu_id = :usuario AND trr_situacao IN ('concluido', 'parcial')
           AND YEARWEEK(trr_data, 1) = YEARWEEK(CURDATE(), 1)"
    );
    $consultaTreinosSemana->execute(['usuario' => $usuario['id']]);
    $totalTreinosSemana = (int) $consultaTreinosSemana->fetchColumn();

    $consultaUltimoTreino = banco()->prepare(
        'SELECT trr_id, trr_data, trr_treino_nome, trr_situacao
           FROM trr_treino_realizado
          WHERE usu_id = :usuario
          ORDER BY trr_data DESC, trr_id DESC
          LIMIT 1'
    );
    $consultaUltimoTreino->execute(['usuario' => $usuario['id']]);
    $ultimoTreino = $consultaUltimoTreino->fetch() ?: null;

    $consultaTreinoHoje = banco()->prepare(
        'SELECT trp.trp_id, trp.trp_nome
           FROM trp_treino_planejado trp
           INNER JOIN ptr_plano_treino ptr ON ptr.ptr_id = trp.ptr_id
          WHERE ptr.usu_id = :usuario AND ptr.ptr_ativo = 1
            AND ptr.ptr_data_inicio <= CURDATE()
            AND (ptr.ptr_data_fim IS NULL OR ptr.ptr_data_fim >= CURDATE())
            AND trp.trp_dia_semana = DAYOFWEEK(CURDATE()) - 1
          ORDER BY ptr.ptr_data_inicio DESC, trp.trp_ordem, trp.trp_id
          LIMIT 1'
    );
    $consultaTreinoHoje->execute(['usuario' => $usuario['id']]);
    $treinoHoje = $consultaTreinoHoje->fetch() ?: null;

    renderizar('painel/index', compact(
        'hoje', 'ultimoPeso', 'pesoAnterior', 'variacaoPeso',
        'ultimaMedicao', 'medicaoAnterior', 'variacaoCintura',
        'planoAlimentarHoje', 'resumoAlimentacaoHoje',
        'totalMedicamentosAtivos', 'ultimaAplicacao', 'ultimaCompra',
        'totalSuplementosAtivos', 'ultimoConsumoSuplemento',
        'totalTreinosSemana', 'ultimoTreino', 'treinoHoje'
    ), 'Painel');
}
