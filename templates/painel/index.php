<?php
$aderenciaAlimentacao = $resumoAlimentacaoHoje['aderencia'] === null
    ? null
    : (int) $resumoAlimentacaoHoje['aderencia'];
$classeAderenciaAlimentacao = $aderenciaAlimentacao === null
    ? 'text-secondary'
    : ($aderenciaAlimentacao >= 75 ? 'text-success' : ($aderenciaAlimentacao >= 50 ? 'text-warning-emphasis' : 'text-danger'));
$totalRefeicoesHoje = (int) ($planoAlimentarHoje['total_refeicoes'] ?? 0);
$refeicoesRegistradasHoje = (int) ($resumoAlimentacaoHoje['registradas'] ?? 0);
$progressoRefeicoes = $totalRefeicoesHoje > 0
    ? min(100, (int) round(($refeicoesRegistradasHoje / $totalRefeicoesHoje) * 100))
    : 0;
$situacaoUltimoTreino = $ultimoTreino
    ? (SITUACOES_TREINO[$ultimoTreino['trr_situacao']] ?? ['rotulo' => $ultimoTreino['trr_situacao'], 'classe' => 'text-bg-secondary'])
    : null;
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <h1 class="h3 mb-1">Olá, <?= escapar(usuario_atual()['nome']) ?>!</h1>
        <p class="text-secondary mb-1">Seu acompanhamento de <?= escapar(formatar_data($hoje)) ?>.</p>
        <span class="badge text-bg-light border"><i class="bi <?= escapar(icone_objetivo()) ?> me-1"></i>Objetivo: <?= escapar(rotulo_objetivo()) ?></span>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <a class="btn btn-outline-success" href="<?= escapar(url('/alimentacao')) ?>"><i class="bi bi-egg-fried me-1"></i>Alimentação</a>
        <a class="btn btn-outline-success" href="<?= escapar(url('/treinos')) ?>"><i class="bi bi-activity me-1"></i>Treinos</a>
        <a class="btn btn-success" href="<?= escapar(url('/pesos/formulario')) ?>"><i class="bi bi-plus-lg me-1"></i>Registrar peso</a>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
        <section class="card h-100 painel-resumo-card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start gap-3">
                    <div class="icone-resumo"><i class="bi bi-egg-fried"></i></div>
                    <a class="btn btn-sm btn-outline-success btn-icone" href="<?= escapar(url('/alimentacao')) ?>" title="Abrir alimentação" aria-label="Abrir alimentação"><i class="bi bi-arrow-right"></i></a>
                </div>
                <div class="mt-3">
                    <div class="text-secondary small">Alimentação de hoje</div>
                    <?php if ($planoAlimentarHoje): ?>
                        <div class="h4 mb-1"><?= $refeicoesRegistradasHoje ?> de <?= $totalRefeicoesHoje ?> refeições</div>
                        <div class="progress painel-progresso mb-2" role="progressbar" aria-label="Refeições registradas" aria-valuenow="<?= $progressoRefeicoes ?>" aria-valuemin="0" aria-valuemax="100"><div class="progress-bar bg-success" style="width: <?= $progressoRefeicoes ?>%"></div></div>
                        <div class="small <?= escapar($classeAderenciaAlimentacao) ?>">Aderência: <?= $aderenciaAlimentacao === null ? '—' : $aderenciaAlimentacao . '%' ?></div>
                    <?php else: ?>
                        <div class="h5 mb-1">Sem plano para hoje</div>
                        <a class="small" href="<?= escapar(url('/alimentacao/planos/formulario')) ?>">Criar plano alimentar</a>
                    <?php endif; ?>
                </div>
            </div>
        </section>
    </div>

    <div class="col-sm-6 col-xl-3">
        <section class="card h-100 painel-resumo-card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start gap-3">
                    <div class="icone-resumo"><i class="bi bi-activity"></i></div>
                    <a class="btn btn-sm btn-outline-success btn-icone" href="<?= escapar(url('/treinos')) ?>" title="Abrir treinos" aria-label="Abrir treinos"><i class="bi bi-arrow-right"></i></a>
                </div>
                <div class="mt-3">
                    <div class="text-secondary small">Treinos</div>
                    <?php if ($treinoHoje): ?>
                        <div class="h5 mb-1"><?= escapar($treinoHoje['trp_nome']) ?></div>
                        <a class="small" href="<?= escapar(url('/treinos/realizados/formulario?treino=' . $treinoHoje['trp_id'] . '&data=' . $hoje)) ?>">Registrar treino de hoje</a>
                    <?php else: ?>
                        <div class="h5 mb-1">Sem treino previsto hoje</div>
                        <span class="small text-secondary"><?= $totalTreinosSemana ?> sessão(ões) nesta semana</span>
                    <?php endif; ?>
                </div>
            </div>
        </section>
    </div>

    <div class="col-sm-6 col-xl-3">
        <section class="card h-100 painel-resumo-card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start gap-3">
                    <div class="icone-resumo">kg</div>
                    <a class="btn btn-sm btn-outline-success btn-icone" href="<?= escapar(url('/pesos')) ?>" title="Ver histórico de peso" aria-label="Ver histórico de peso"><i class="bi bi-arrow-right"></i></a>
                </div>
                <div class="mt-3">
                    <div class="text-secondary small">Peso atual</div>
                    <?php if ($ultimoPeso): ?>
                        <div class="h4 mb-1"><?= formatar_decimal($ultimoPeso['pes_peso_kg']) ?> kg</div>
                        <div class="small <?= escapar(classe_variacao_peso($variacaoPeso)) ?>">
                            <?= $variacaoPeso === null ? 'Sem pesagem anterior' : (($variacaoPeso > 0 ? '+' : '') . formatar_decimal($variacaoPeso) . ' kg · ' . situacao_variacao_peso($variacaoPeso)) ?>
                        </div>
                    <?php else: ?>
                        <div class="h5 mb-1">Sem pesagem</div>
                        <a class="small" href="<?= escapar(url('/pesos/formulario')) ?>">Registrar a primeira</a>
                    <?php endif; ?>
                </div>
            </div>
        </section>
    </div>

    <div class="col-sm-6 col-xl-3">
        <section class="card h-100 painel-resumo-card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start gap-3">
                    <div class="icone-resumo"><i class="bi bi-rulers"></i></div>
                    <a class="btn btn-sm btn-outline-success btn-icone" href="<?= escapar(url('/medidas')) ?>" title="Ver medidas corporais" aria-label="Ver medidas corporais"><i class="bi bi-arrow-right"></i></a>
                </div>
                <div class="mt-3">
                    <div class="text-secondary small">Medidas corporais</div>
                    <?php if ($ultimaMedicao): ?>
                        <div class="h5 mb-1"><?= escapar(formatar_data($ultimaMedicao['mec_data_medicao'])) ?></div>
                        <div class="small text-secondary">Cintura: <?= $ultimaMedicao['mec_cintura_cm'] === null ? '—' : formatar_decimal($ultimaMedicao['mec_cintura_cm']) . ' cm' ?></div>
                        <div class="small text-secondary">Abdômen: <?= $ultimaMedicao['mec_abdomen_cm'] === null ? '—' : formatar_decimal($ultimaMedicao['mec_abdomen_cm']) . ' cm' ?></div>
                        <?php if ($variacaoCintura !== null): ?>
                            <div class="small text-secondary mt-1">Variação da cintura: <?= $variacaoCintura > 0 ? '+' : '' ?><?= formatar_decimal($variacaoCintura) ?> cm</div>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="h5 mb-1">Sem medições</div>
                        <a class="small" href="<?= escapar(url('/medidas/formulario')) ?>">Registrar medidas</a>
                    <?php endif; ?>
                </div>
            </div>
        </section>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-lg-7">
        <section class="card h-100">
            <div class="card-header bg-white d-flex flex-wrap justify-content-between align-items-center gap-2 py-3">
                <div>
                    <h2 class="h5 mb-0">Treinos</h2>
                    <span class="small text-secondary"><?= $totalTreinosSemana ?> sessão(ões) concluída(s) ou parcial(is) nesta semana.</span>
                </div>
                <a class="btn btn-sm btn-outline-success" href="<?= escapar(url('/treinos/historico')) ?>">Histórico</a>
            </div>
            <div class="card-body">
                <?php if ($ultimoTreino): ?>
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                        <div>
                            <div class="small text-secondary">Último treino</div>
                            <div class="fw-semibold"><?= escapar($ultimoTreino['trr_treino_nome']) ?></div>
                            <div class="small text-secondary"><?= escapar(formatar_data($ultimoTreino['trr_data'])) ?></div>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge <?= escapar($situacaoUltimoTreino['classe']) ?>"><?= escapar($situacaoUltimoTreino['rotulo']) ?></span>
                            <a class="btn btn-sm btn-outline-primary" href="<?= escapar(url('/treinos/realizados/detalhes?id=' . $ultimoTreino['trr_id'])) ?>">Ver comparativo</a>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="text-secondary">Nenhum treino realizado registrado ainda.</div>
                <?php endif; ?>
            </div>
        </section>
    </div>

    <div class="col-lg-5">
        <section class="card h-100">
            <div class="card-header bg-white d-flex flex-wrap justify-content-between align-items-center gap-2 py-3">
                <div><h2 class="h5 mb-0">Composição corporal</h2><span class="small text-secondary">Última pesagem registrada.</span></div>
                <a class="btn btn-sm btn-outline-success" href="<?= escapar(url('/pesos')) ?>">Ver histórico</a>
            </div>
            <div class="card-body">
                <?php if ($ultimoPeso): ?>
                    <div class="row g-3">
                        <div class="col-6"><div class="small text-secondary">Gordura corporal</div><div class="fw-semibold"><?= $ultimoPeso['pes_percentual_gordura'] === null ? '—' : formatar_decimal($ultimoPeso['pes_percentual_gordura']) . '%' ?></div></div>
                        <div class="col-6"><div class="small text-secondary">Massa magra</div><div class="fw-semibold"><?= $ultimoPeso['pes_massa_magra_kg'] === null ? '—' : formatar_decimal($ultimoPeso['pes_massa_magra_kg']) . ' kg' ?></div></div>
                        <div class="col-12"><div class="small text-secondary">Pesagem em <?= escapar(formatar_data($ultimoPeso['pes_data_pesagem'])) ?></div></div>
                    </div>
                <?php else: ?>
                    <div class="text-secondary">Os dados de composição aparecerão após a primeira pesagem.</div>
                <?php endif; ?>
            </div>
        </section>
    </div>
</div>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
    <div><h2 class="h4 mb-1">Medicamentos e suplementos</h2><p class="text-secondary mb-0">Últimos registros e atalhos para manter o acompanhamento em dia.</p></div>
</div>

<div class="row g-4">
    <div class="col-lg-6">
        <section class="card h-100">
            <div class="card-header bg-white d-flex flex-wrap justify-content-between align-items-center gap-2 py-3">
                <div><h3 class="h5 mb-0"><i class="bi bi-capsule-pill me-2 text-success"></i>Medicamentos</h3><span class="small text-secondary"><?= $totalMedicamentosAtivos ?> ativo(s)</span></div>
                <div class="d-flex gap-2"><a class="btn btn-sm btn-outline-success" href="<?= escapar(url('/medicamentos')) ?>">Ver todos</a><a class="btn btn-sm btn-success" href="<?= escapar(url('/medicamentos/aplicacoes/formulario')) ?>">Registrar aplicação</a></div>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-sm-6">
                        <div class="small text-secondary">Última aplicação</div>
                        <?php if ($ultimaAplicacao): ?>
                            <div class="fw-semibold"><?= escapar($ultimaAplicacao['med_nome']) ?></div>
                            <div><?= formatar_decimal($ultimaAplicacao['apl_dose']) ?> <?= escapar(UNIDADES_DOSE[$ultimaAplicacao['apl_unidade']] ?? $ultimaAplicacao['apl_unidade']) ?></div>
                            <div class="small text-secondary"><?= escapar(formatar_data_hora($ultimaAplicacao['apl_data_aplicacao'])) ?></div>
                        <?php else: ?>
                            <div class="text-secondary">Nenhuma aplicação registrada.</div>
                        <?php endif; ?>
                    </div>
                    <div class="col-sm-6">
                        <div class="small text-secondary">Compra mais recente</div>
                        <?php if ($ultimaCompra): ?>
                            <div class="fw-semibold"><?= escapar($ultimaCompra['med_nome']) ?></div>
                            <div class="small text-secondary"><?= escapar(formatar_data($ultimaCompra['com_data_compra'])) ?></div>
                        <?php else: ?>
                            <div class="text-secondary">Nenhuma compra registrada.</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </section>
    </div>

    <div class="col-lg-6">
        <section class="card h-100">
            <div class="card-header bg-white d-flex flex-wrap justify-content-between align-items-center gap-2 py-3">
                <div><h3 class="h5 mb-0"><i class="bi bi-jar me-2 text-success"></i>Suplementos</h3><span class="small text-secondary"><?= $totalSuplementosAtivos ?> ativo(s)</span></div>
                <div class="d-flex gap-2"><a class="btn btn-sm btn-outline-success" href="<?= escapar(url('/suplementos')) ?>">Ver todos</a><a class="btn btn-sm btn-success" href="<?= escapar(url('/suplementos/consumos/formulario')) ?>">Registrar consumo</a></div>
            </div>
            <div class="card-body">
                <div class="small text-secondary">Último consumo</div>
                <?php if ($ultimoConsumoSuplemento): ?>
                    <div class="fw-semibold"><?= escapar($ultimoConsumoSuplemento['sup_nome']) ?></div>
                    <div><?= formatar_decimal($ultimoConsumoSuplemento['cos_dose']) ?> <?= escapar($ultimoConsumoSuplemento['cos_unidade']) ?></div>
                    <div class="small text-secondary"><?= escapar(formatar_data_hora($ultimoConsumoSuplemento['cos_data_consumo'])) ?></div>
                <?php else: ?>
                    <div class="text-secondary">Nenhum consumo registrado.</div>
                <?php endif; ?>
            </div>
        </section>
    </div>
</div>
