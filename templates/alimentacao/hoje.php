<?php
$dataObjeto = new DateTimeImmutable($data);
$ehHoje = $data === date('Y-m-d');
$dataAnterior = $dataObjeto->modify('-1 day')->format('Y-m-d');
$dataSeguinte = $dataObjeto->modify('+1 day')->format('Y-m-d');
$classeAderencia = $resumo['aderencia'] === null ? 'text-secondary' : ($resumo['aderencia'] >= 75 ? 'text-success' : ($resumo['aderencia'] >= 50 ? 'text-warning-emphasis' : 'text-danger'));
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div><h1 class="h3 mb-1"><?= $ehHoje ? 'Alimentação de hoje' : 'Alimentação diária' ?></h1><p class="text-secondary mb-0"><?= escapar(formatar_data($data)) ?> · Objetivo: <?= escapar(rotulo_objetivo()) ?></p></div>
    <div class="d-flex flex-wrap gap-2"><a class="btn btn-outline-secondary" href="<?= escapar(url('/alimentacao/historico')) ?>"><i class="bi bi-clock-history me-1"></i>Histórico</a><a class="btn btn-outline-secondary" href="<?= escapar(url('/alimentacao/planos')) ?>"><i class="bi bi-journal-text me-1"></i>Planos</a><a class="btn btn-success" href="<?= escapar(url('/alimentacao/planos/formulario')) ?>"><i class="bi bi-plus-lg me-1"></i>Novo plano</a></div>
</div>

<div class="card mb-4"><div class="card-body d-flex flex-wrap justify-content-center align-items-end gap-2">
    <a class="btn btn-outline-secondary btn-icone mb-1" href="<?= escapar(url('/alimentacao?data=' . $dataAnterior)) ?>" title="Dia anterior" aria-label="Dia anterior"><i class="bi bi-chevron-left"></i></a>
    <form class="d-flex align-items-end gap-2" action="<?= escapar(url('/alimentacao')) ?>" method="get"><div><label class="form-label small mb-1" for="data-alimentacao">Consultar data</label><input class="form-control" type="date" id="data-alimentacao" name="data" value="<?= escapar($data) ?>"></div><button class="btn btn-outline-success mb-0" type="submit">Consultar</button></form>
    <a class="btn btn-outline-secondary btn-icone mb-1" href="<?= escapar(url('/alimentacao?data=' . $dataSeguinte)) ?>" title="Dia seguinte" aria-label="Dia seguinte"><i class="bi bi-chevron-right"></i></a>
    <?php if (!$ehHoje): ?><a class="btn btn-link mb-1" href="<?= escapar(url('/alimentacao')) ?>">Ir para hoje</a><?php endif; ?>
</div></div>

<?php if (!$plano): ?>
    <div class="card"><div class="card-body p-5 text-center"><i class="bi bi-egg-fried display-5 text-success"></i><h2 class="h5 mt-3">Nenhum plano alimentar encontrado nesta data</h2><p class="text-secondary">Crie um plano ou ajuste sua vigência para visualizar as refeições.</p><a class="btn btn-success" href="<?= escapar(url('/alimentacao/planos/formulario')) ?>">Criar plano alimentar</a></div></div>
<?php else: ?>
    <div class="row g-3 mb-4">
        <div class="col-md-6"><div class="card h-100"><div class="card-body"><div class="text-secondary small">Refeições registradas</div><div class="h4 mb-0"><?= (int) $resumo['registradas'] ?> de <?= (int) $resumo['total'] ?></div><div class="small text-secondary">As não registradas não entram no cálculo.</div></div></div></div>
        <div class="col-md-6"><div class="card h-100"><div class="card-body"><div class="text-secondary small">Aderência das registradas</div><div class="h4 mb-0 <?= escapar($classeAderencia) ?>"><?= $resumo['aderencia'] === null ? '—' : (int) $resumo['aderencia'] . '%' ?></div><div class="small text-secondary">Conforme 100% · substituída 75% · parcial 50%</div></div></div></div>
    </div>

    <div class="card mb-4"><div class="card-body d-flex flex-wrap justify-content-between align-items-start gap-3"><div><div class="d-flex flex-wrap align-items-center gap-2 mb-1"><h2 class="h5 mb-0"><?= escapar($plano['pal_nome']) ?></h2><span class="badge text-bg-success">Plano da data</span></div><div class="small text-secondary"><?= escapar(formatar_data($plano['pal_data_inicio'])) ?><?= $plano['pal_data_fim'] ? ' a ' . escapar(formatar_data($plano['pal_data_fim'])) : ' em diante' ?><?= $plano['pal_profissional'] ? ' · ' . escapar($plano['pal_profissional']) : '' ?></div></div><a class="btn btn-sm btn-outline-primary btn-icone" href="<?= escapar(url('/alimentacao/planos/detalhes?id=' . $plano['pal_id'])) ?>" title="Gerenciar plano" aria-label="Gerenciar plano"><i class="bi bi-gear"></i></a></div></div>
    <?php if ($plano['pal_orientacoes']): ?><div class="alert alert-light border"><i class="bi bi-info-circle me-2"></i><?= nl2br(escapar($plano['pal_orientacoes'])) ?></div><?php endif; ?>

    <?php if (!$plano['refeicoes']): ?><div class="alert alert-warning">Este plano ainda não possui refeições. <a href="<?= escapar(url('/alimentacao/refeicoes/formulario?plano=' . $plano['pal_id'])) ?>">Adicionar a primeira refeição</a>.</div>
    <?php else: ?><div class="vstack gap-4">
    <?php foreach ($plano['refeicoes'] as $refeicao): ?>
        <?php
        $registro = $refeicao['registro'];
        $dadosSituacao = $registro ? (SITUACOES_REGISTRO_ALIMENTAR[$registro['ral_situacao']] ?? null) : null;
        $itensPlanejados = $registro
            ? ($registro['planejado']['itens'] ?? [])
            : array_map(static fn (array $item): array => [
                'alimento' => $item['ita_alimento'], 'quantidade' => $item['ita_quantidade'],
                'unidade' => $item['ita_unidade'], 'substituicoes' => $item['ita_substituicoes'],
            ], $refeicao['itens']);
        ?>
        <section class="card">
            <div class="card-header bg-white d-flex flex-wrap justify-content-between align-items-center gap-2 py-3">
                <div><h2 class="h5 mb-0"><?= escapar($refeicao['ref_nome']) ?></h2><?php if ($refeicao['ref_horario']): ?><span class="small text-secondary"><i class="bi bi-clock me-1"></i>Planejado para <?= escapar(substr($refeicao['ref_horario'], 0, 5)) ?></span><?php endif; ?></div>
                <div class="d-flex align-items-center gap-2">
                    <?php if ($dadosSituacao): ?><span class="badge <?= escapar($dadosSituacao['classe']) ?>"><i class="bi <?= escapar($dadosSituacao['icone']) ?> me-1"></i><?= escapar($dadosSituacao['rotulo']) ?></span><?php else: ?><span class="badge text-bg-secondary">Não registrada</span><?php endif; ?>
                    <?php if ($podeRegistrar): ?><a class="btn btn-sm <?= $registro ? 'btn-outline-primary' : 'btn-success' ?> btn-icone" href="<?= escapar(url($registro ? '/alimentacao/registros/formulario?id=' . $registro['ral_id'] : '/alimentacao/registros/formulario?refeicao=' . $refeicao['ref_id'] . '&data=' . $data)) ?>" title="<?= $registro ? 'Editar refeição realizada' : 'Registrar refeição realizada' ?>" aria-label="<?= $registro ? 'Editar refeição realizada' : 'Registrar refeição realizada' ?>"><i class="bi <?= $registro ? 'bi-pencil-square' : 'bi-check2-square' ?>"></i></a><?php endif; ?>
                </div>
            </div>
            <div class="card-body"><div class="row g-4">
                <div class="col-lg-6"><h3 class="h6 text-secondary text-uppercase mb-3"><i class="bi bi-journal-check me-1"></i>Planejado</h3><ul class="list-group list-group-flush border rounded">
                    <?php foreach ($itensPlanejados as $item): ?><li class="list-group-item"><div class="d-flex justify-content-between gap-2"><span class="fw-semibold"><?= escapar($item['alimento']) ?></span><?php if ($item['quantidade'] !== null): ?><span class="text-nowrap"><?= formatar_decimal($item['quantidade']) ?> <?= escapar(UNIDADES_ALIMENTO[$item['unidade']] ?? ($item['unidade'] ?: '')) ?></span><?php endif; ?></div><?php if (!empty($item['substituicoes'])): ?><div class="small text-secondary"><i class="bi bi-arrow-left-right me-1"></i><?= escapar($item['substituicoes']) ?></div><?php endif; ?></li><?php endforeach; ?>
                </ul></div>
                <div class="col-lg-6"><h3 class="h6 text-secondary text-uppercase mb-3"><i class="bi bi-check2-circle me-1"></i>Realizado</h3>
                    <?php if (!$registro): ?><div class="border rounded p-4 text-center text-secondary">Ainda não registrado.</div>
                    <?php elseif ($registro['ral_situacao'] === 'nao_realizada'): ?><div class="alert alert-danger mb-0">Refeição não realizada.</div>
                    <?php else: ?><ul class="list-group list-group-flush border rounded"><?php foreach ($registro['itens'] as $item): ?><li class="list-group-item"><div class="d-flex justify-content-between gap-2"><span class="fw-semibold"><?= escapar($item['ira_alimento']) ?></span><?php if ($item['ira_quantidade'] !== null): ?><span class="text-nowrap"><?= formatar_decimal($item['ira_quantidade']) ?> <?= escapar(UNIDADES_ALIMENTO[$item['ira_unidade']] ?? ($item['ira_unidade'] ?: '')) ?></span><?php endif; ?></div><?php if ($item['ira_observacoes']): ?><div class="small text-secondary"><?= escapar($item['ira_observacoes']) ?></div><?php endif; ?></li><?php endforeach; ?></ul><?php endif; ?>
                    <?php if ($registro): ?><div class="small text-secondary mt-2"><?php if ($registro['ral_horario']): ?><span class="me-3"><i class="bi bi-clock me-1"></i><?= escapar(substr($registro['ral_horario'], 0, 5)) ?></span><?php endif; ?><?php if ($registro['ral_fome_antes'] !== null): ?><span class="me-3">Fome: <?= (int) $registro['ral_fome_antes'] ?>/10</span><?php endif; ?><?php if ($registro['ral_saciedade_depois'] !== null): ?><span>Saciedade: <?= (int) $registro['ral_saciedade_depois'] ?>/10</span><?php endif; ?></div><?php if ($registro['ral_observacoes']): ?><div class="small mt-2"><i class="bi bi-chat-left-text me-1"></i><?= nl2br(escapar($registro['ral_observacoes'])) ?></div><?php endif; ?><?php endif; ?>
                </div>
            </div></div>
        </section>
    <?php endforeach; ?>
    </div><?php endif; ?>
<?php endif; ?>
