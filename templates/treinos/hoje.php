<?php
$dataAnterior = (new DateTimeImmutable($data))->modify('-1 day')->format('Y-m-d');
$dataSeguinte = (new DateTimeImmutable($data))->modify('+1 day')->format('Y-m-d');
$ehHoje = $data === date('Y-m-d');
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <h1 class="h3 mb-1"><?= $ehHoje ? 'Treinos de hoje' : 'Treinos do dia' ?></h1>
        <p class="text-secondary mb-0"><?= escapar(formatar_data($data)) ?></p>
    </div>
    <div class="d-flex flex-wrap gap-2"><a class="btn btn-outline-secondary" href="<?= escapar(url('/treinos/historico')) ?>"><i class="bi bi-clock-history me-1"></i>Histórico</a><a class="btn btn-outline-secondary" href="<?= escapar(url('/treinos/exercicios')) ?>">Exercícios</a><a class="btn btn-outline-secondary" href="<?= escapar(url('/treinos/planos')) ?>">Planos</a></div>
</div>
<div class="card mb-4">
    <div class="card-body d-flex flex-wrap justify-content-center align-items-end gap-2">
        <a class="btn btn-outline-secondary btn-icone mb-1" href="<?= escapar(url('/treinos?data=' . $dataAnterior)) ?>"><i class="bi bi-chevron-left"></i></a>
        <form class="d-flex align-items-end gap-2" action="<?= escapar(url('/treinos')) ?>" method="get">
            <div><label class="form-label small mb-1" for="data-treino">Consultar data</label><input class="form-control" type="date" id="data-treino" name="data" value="<?= escapar($data) ?>"></div><button class="btn btn-outline-success" type="submit">Consultar</button>
        </form>
        <a class="btn btn-outline-secondary btn-icone mb-1" href="<?= escapar(url('/treinos?data=' . $dataSeguinte)) ?>"><i class="bi bi-chevron-right"></i></a>
        <?php if (!$ehHoje): ?><a class="btn btn-link mb-1" href="<?= escapar(url('/treinos')) ?>">Ir para hoje</a><?php endif; ?>
    </div>
</div>
<?php if (!$plano): ?>
    <div class="card">
        <div class="card-body p-5 text-center"><i class="bi bi-activity display-5 text-success"></i>
            <h2 class="h5 mt-3">Nenhum plano de treino vigente nesta data</h2>
            <p class="text-secondary">Crie um plano e distribua os treinos pelos dias da semana.</p><a class="btn btn-success" href="<?= escapar(url('/treinos/planos/formulario')) ?>">Criar plano de treino</a>
        </div>
    </div>
<?php else: ?>
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-body">
                    <div class="text-secondary small"><?= $data > date('Y-m-d') ? 'Treino previsto na sequência' : 'Treino recomendado no dia' ?></div>
                    <div class="h4 mb-0"><?= (int) $resumo['planejados'] ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-body">
                    <div class="text-secondary small">Treinos registrados</div>
                    <div class="h4 mb-0"><?= (int) $resumo['registrados'] ?></div>
                    <div class="small text-secondary"><?= (int) $resumo['series'] ?> séries concluídas</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-body">
                    <div class="text-secondary small">Volume registrado</div>
                    <div class="h4 mb-0"><?= formatar_decimal($resumo['volume']) ?> kg</div>
                </div>
            </div>
        </div>
    </div>
    <div class="card mb-4">
        <div class="card-body d-flex flex-wrap justify-content-between align-items-start gap-3">
            <div>
                <h2 class="h5 mb-1"><?= escapar($plano['ptr_nome']) ?></h2>
                <div class="small text-secondary"><?= escapar($plano['ptr_objetivo'] ?: 'Plano vigente') ?></div>
            </div><a class="btn btn-sm btn-outline-primary btn-icone" href="<?= escapar(url('/treinos/planos/detalhes?id=' . $plano['ptr_id'])) ?>"><i class="bi bi-gear"></i></a>
        </div>
    </div>
    <?php if ($podeRegistrar && $alternativas): ?><div class="alert alert-info d-flex flex-wrap justify-content-between align-items-center gap-2"><span>O recomendado para hoje é <strong><?= escapar($treinos[0]['trp_nome']) ?></strong>. Quer fazer outro?</span>
            <form class="d-flex gap-2" method="get" action="<?= escapar(url('/treinos/realizados/formulario')) ?>"><input type="hidden" name="data" value="<?= escapar($data) ?>"><select class="form-select form-select-sm" name="treino"><?php foreach ($alternativas as $alternativa): ?><option value="<?= (int) $alternativa['trp_id'] ?>"><?= escapar($alternativa['trp_nome']) ?></option><?php endforeach; ?></select><button class="btn btn-sm btn-outline-primary" type="submit">Trocar treino</button></form>
        </div><?php endif; ?>
    <?php if (!$treinos): ?><div class="alert alert-warning">Este plano ainda não possui treinos planejados.</div>
    <?php else: ?><div class="vstack gap-4"><?php foreach ($treinos as $treino): ?>
                <?php $registro = $treino['registro'];
                                                $situacao = $registro ? (SITUACOES_TREINO[$registro['trr_situacao']] ?? null) : null; ?>
                <section class="card <?= $treino['eh_do_dia'] ? 'border border-success' : '' ?>">
                    <div class="card-header bg-white d-flex flex-wrap justify-content-between align-items-center gap-2 py-3">
                        <div>
                            <div class="d-flex align-items-center gap-2">
                                <h2 class="h5 mb-0"><?= escapar($treino['trp_nome']) ?></h2><?php if ($treino['eh_do_dia'] || !empty($treino['eh_previsao'])): ?><span class="badge text-bg-success"><?= $ehHoje ? 'Recomendado hoje' : 'Previsão da sequência' ?></span><?php endif; ?>
                            </div><span class="small text-secondary"><?= escapar(dia_semana_treino($treino['trp_dia_semana'])) ?> · <?= count($treino['exercicios']) ?> exercício(s)</span>
                        </div>
                        <div class="d-flex gap-2 align-items-center"><?php if ($situacao): ?><span class="badge <?= escapar($situacao['classe']) ?>"><?= escapar($situacao['rotulo']) ?></span><?php endif; ?><?php if ($podeRegistrar): ?><a class="btn btn-sm <?= $registro ? 'btn-outline-primary' : 'btn-success' ?>" href="<?= escapar(url($registro ? '/treinos/realizados/formulario?id=' . $registro['trr_id'] : '/treinos/realizados/formulario?treino=' . $treino['trp_id'] . '&data=' . $data)) ?>"><i class="bi <?= $registro ? 'bi-pencil-square' : 'bi-play-fill' ?> me-1"></i><?= $registro ? 'Editar' : 'Iniciar' ?></a><?php endif; ?></div>
                    </div>
                    <div class="card-body">
                        <div class="row g-4">
                            <div class="col-lg-7">
                                <h3 class="h6 text-secondary text-uppercase">Planejado</h3>
                                <div class="list-group list-group-flush border rounded"><?php foreach ($treino['exercicios'] as $item): ?><div class="list-group-item d-flex justify-content-between gap-3">
                                            <div><span class="fw-semibold"><?= escapar($item['exe_nome']) ?></span><?php if ($item['exp_observacoes']): ?><div class="small text-secondary"><?= escapar($item['exp_observacoes']) ?></div><?php endif; ?></div><span class="text-nowrap"><?= (int) $item['exp_series'] ?> série(s)<?= $item['exp_repeticoes_min'] ? ' · ' . (int) $item['exp_repeticoes_min'] . ($item['exp_repeticoes_max'] && $item['exp_repeticoes_max'] !== $item['exp_repeticoes_min'] ? '–' . (int) $item['exp_repeticoes_max'] : '') . ' rep.' : '' ?></span>
                                        </div><?php endforeach; ?></div>
                            </div>
                            <div class="col-lg-5">
                                <h3 class="h6 text-secondary text-uppercase">Realizado</h3><?php if (!$registro): ?><div class="border rounded p-4 text-center text-secondary">Ainda não registrado.</div><?php elseif ($registro['trr_situacao'] === 'nao_realizado'): ?><div class="alert alert-danger mb-0">Treino não realizado.</div><?php else: ?><?php $dados = resumo_treino_realizado($registro, $registro['exercicios']); ?><div class="border rounded p-3">
                                        <div class="d-flex justify-content-between"><span>Aderência</span><strong><?= $dados['aderencia'] === null ? '—' : $dados['aderencia'] . '%' ?></strong></div>
                                        <div class="d-flex justify-content-between"><span>Séries concluídas</span><strong><?= $dados['series_concluidas'] ?></strong></div>
                                        <div class="d-flex justify-content-between"><span>Volume</span><strong><?= formatar_decimal($dados['volume']) ?> kg</strong></div><a class="btn btn-sm btn-link px-0 mt-2" href="<?= escapar(url('/treinos/realizados/detalhes?id=' . $registro['trr_id'])) ?>">Ver comparativo</a>
                                    </div><?php endif; ?>
                            </div>
                        </div>
                    </div>
                </section>
            <?php endforeach; ?>
        </div><?php endif; ?>
<?php endif; ?>