<?php
$quantidadeExibida = count($medicoes);
$primeiraMedicao = $medicoes[0] ?? null;
$ultimaMedicao = $medicoes[$quantidadeExibida - 1] ?? null;
$temComparacao = $quantidadeExibida > 1;
$parametrosPaginacao = array_filter([
    'data_inicio' => $dataInicio,
    'data_fim' => $dataFim,
], static fn (mixed $valor): bool => $valor !== null && $valor !== '');
$linkPaginacao = static function (int $destino) use ($parametrosPaginacao): string {
    $parametrosPaginacao['pagina'] = $destino;

    return url('/medidas?' . http_build_query($parametrosPaginacao));
};
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <h1 class="h3 mb-1">Medidas corporais</h1>
        <p class="text-secondary mb-0">Compare sua evolução entre diferentes medições.</p>
    </div>
    <a class="btn btn-success" href="<?= escapar(url('/medidas/formulario')) ?>"><i class="bi bi-plus-lg me-1"></i>Nova medição</a>
</div>

<?php foreach ($errosFiltro as $erro): ?>
    <div class="alert alert-warning" role="alert"><?= escapar($erro) ?></div>
<?php endforeach; ?>

<div class="card mb-4">
    <div class="card-body p-4">
        <form action="<?= escapar(url('/medidas')) ?>" method="get">
            <div class="row g-3 align-items-end">
                <div class="col-sm-6 col-lg-4">
                    <label class="form-label" for="data_inicio">Data inicial</label>
                    <input class="form-control" type="date" id="data_inicio" name="data_inicio" value="<?= escapar($dataInicio) ?>">
                </div>
                <div class="col-sm-6 col-lg-4">
                    <label class="form-label" for="data_fim">Data final</label>
                    <input class="form-control" type="date" id="data_fim" name="data_fim" value="<?= escapar($dataFim) ?>">
                </div>
                <div class="col-lg-4">
                    <div class="d-flex flex-wrap gap-2">
                        <button class="btn btn-success" type="submit"><i class="bi bi-funnel me-1"></i>Filtrar</button>
                        <?php if ($filtroAtivo): ?>
                            <a class="btn btn-outline-secondary" href="<?= escapar(url('/medidas')) ?>">Limpar filtro</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <?php if (!$medicoes): ?>
        <div class="card-body p-5 text-center">
            <i class="bi bi-rulers display-5 text-success"></i>
            <h2 class="h5 mt-3"><?= $filtroAtivo ? 'Nenhuma medição encontrada no período' : 'Nenhuma medição registrada' ?></h2>
            <p class="text-secondary"><?= $filtroAtivo ? 'Ajuste as datas ou limpe o filtro para visualizar outras medições.' : 'Adicione seu primeiro registro para iniciar o histórico.' ?></p>
            <?php if ($filtroAtivo): ?>
                <a class="btn btn-outline-secondary" href="<?= escapar(url('/medidas')) ?>">Limpar filtro</a>
            <?php else: ?>
                <a class="btn btn-success" href="<?= escapar(url('/medidas/formulario')) ?>">Adicionar medição</a>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="card-header bg-white d-flex flex-wrap justify-content-between align-items-center gap-2 px-4 py-3">
            <div>
                <h2 class="h5 mb-1">Evolução no período exibido</h2>
                <p class="small text-secondary mb-0">A comparação usa a primeira e a última medição deste grupo.</p>
            </div>
            <span class="badge text-bg-light border"><?= $quantidadeExibida ?> de <?= $totalMedicoes ?> <?= $totalMedicoes === 1 ? 'medição' : 'medições' ?></span>
        </div>
        <div class="table-responsive tabela-medicoes-container">
            <table class="table table-bordered align-middle mb-0 tabela-medicoes-transposta">
                <thead class="table-light">
                <tr>
                    <th class="medicao-coluna-rotulo" scope="col">Indicador</th>
                    <?php foreach ($medicoes as $indice => $medicao): ?>
                        <th class="text-center" scope="col">Medição <?= $indice + 1 ?></th>
                    <?php endforeach; ?>
                    <th class="text-center medicao-coluna-comparacao" scope="col">
                        Comparação
                        <span class="d-block small fw-normal text-secondary">primeira × última</span>
                    </th>
                </tr>
                </thead>
                <tbody>
                <tr>
                    <th class="medicao-coluna-rotulo" scope="row"><i class="bi bi-calendar3 me-2 text-success"></i>Data</th>
                    <?php foreach ($medicoes as $medicao): ?>
                        <td class="text-center">
                            <span class="d-block fw-semibold text-nowrap"><?= escapar(formatar_data($medicao['mec_data_medicao'])) ?></span>
                            <div class="d-flex justify-content-center gap-1 mt-2 medicao-acoes-data">
                                <a class="btn btn-sm btn-outline-primary btn-icone" href="<?= escapar(url('/medidas/formulario?id=' . $medicao['mec_id'])) ?>" title="Editar medição de <?= escapar(formatar_data($medicao['mec_data_medicao'])) ?>" aria-label="Editar medição"><i class="bi bi-pencil-square" aria-hidden="true"></i></a>
                                <form action="<?= escapar(url('/medidas/excluir')) ?>" method="post">
                                    <?= campo_csrf() ?>
                                    <input type="hidden" name="mec_id" value="<?= (int) $medicao['mec_id'] ?>">
                                    <button class="btn btn-sm btn-outline-danger btn-icone" type="submit" data-confirmar="Excluir esta medição?" title="Excluir medição de <?= escapar(formatar_data($medicao['mec_data_medicao'])) ?>" aria-label="Excluir medição"><i class="bi bi-trash" aria-hidden="true"></i></button>
                                </form>
                            </div>
                        </td>
                    <?php endforeach; ?>
                    <td class="text-center medicao-coluna-comparacao">
                        <?php if ($temComparacao): ?>
                            <span class="small text-nowrap"><?= escapar(formatar_data($primeiraMedicao['mec_data_medicao'])) ?></span>
                            <i class="bi bi-arrow-right mx-1" aria-hidden="true"></i>
                            <span class="small text-nowrap"><?= escapar(formatar_data($ultimaMedicao['mec_data_medicao'])) ?></span>
                        <?php else: ?>
                            <span class="text-secondary">—</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php foreach (CAMPOS_MEDICAO as $campo => [$rotulo, $unidade]): ?>
                    <?php
                    $valorInicial = $temComparacao ? ($primeiraMedicao[$campo] ?? null) : null;
                    $valorFinal = $temComparacao ? ($ultimaMedicao[$campo] ?? null) : null;
                    $variacao = null;

                    if ($valorInicial !== null && $valorFinal !== null) {
                        $variacao = round((float) $valorFinal - (float) $valorInicial, 2);

                        if (abs($variacao) < 0.005) {
                            $variacao = 0.0;
                        }
                    }
                    ?>
                    <tr>
                        <th class="medicao-coluna-rotulo" scope="row"><?= escapar($rotulo) ?></th>
                        <?php foreach ($medicoes as $medicao): ?>
                            <td class="text-center text-nowrap">
                                <?= $medicao[$campo] === null ? '<span class="text-secondary">—</span>' : escapar(formatar_decimal($medicao[$campo]) . ' ' . $unidade) ?>
                            </td>
                        <?php endforeach; ?>
                        <td class="text-center medicao-coluna-comparacao">
                            <?php if ($variacao === null): ?>
                                <span class="text-secondary">—</span>
                            <?php else: ?>
                                <span class="badge text-bg-light border variacao-medicao" title="Última medida menos a primeira">
                                    <?php if ($variacao > 0): ?><i class="bi bi-arrow-up me-1" aria-hidden="true"></i><?php elseif ($variacao < 0): ?><i class="bi bi-arrow-down me-1" aria-hidden="true"></i><?php else: ?><i class="bi bi-dash me-1" aria-hidden="true"></i><?php endif; ?><?= $variacao > 0 ? '+' : '' ?><?= escapar(formatar_decimal($variacao)) ?> <?= escapar($unidade) ?>
                                </span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="card-footer bg-white d-flex flex-wrap justify-content-between align-items-center gap-3 px-4 py-3">
            <span class="small text-secondary">Grupo <?= $paginaAtual ?> de <?= $totalPaginas ?> · máximo de <?= MEDICOES_POR_PAGINA ?> medições por vez</span>
            <?php if ($totalPaginas > 1): ?>
                <nav class="d-flex gap-2" aria-label="Navegação entre grupos de medições">
                    <?php if ($paginaAtual > 1): ?>
                        <a class="btn btn-sm btn-outline-secondary" href="<?= escapar($linkPaginacao($paginaAtual - 1)) ?>"><i class="bi bi-chevron-left me-1"></i>Mais recentes</a>
                    <?php endif; ?>
                    <?php if ($paginaAtual < $totalPaginas): ?>
                        <a class="btn btn-sm btn-outline-secondary" href="<?= escapar($linkPaginacao($paginaAtual + 1)) ?>">Medições anteriores<i class="bi bi-chevron-right ms-1"></i></a>
                    <?php endif; ?>
                </nav>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>
