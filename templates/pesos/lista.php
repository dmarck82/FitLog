<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <h1 class="h3 mb-1">Histórico de peso</h1>
        <p class="text-secondary mb-1">Acompanhe suas pesagens ao longo do tempo.</p>
        <div class="small">
            <span class="badge text-bg-light border"><i class="bi <?= escapar(icone_objetivo()) ?> me-1"></i><?= escapar(rotulo_objetivo()) ?></span>
            <span class="text-secondary ms-1"><?= escapar(descricao_objetivo()) ?></span>
        </div>
    </div>
    <a class="btn btn-success" href="<?= escapar(url('/pesos/formulario')) ?>">Registrar peso</a>
</div>

<div class="card">
    <?php if (!$pesos): ?>
        <div class="card-body p-5 text-center">
            <h2 class="h5">Nenhum peso registrado</h2>
            <p class="text-secondary">Adicione sua primeira pesagem para iniciar o histórico.</p>
            <a class="btn btn-success" href="<?= escapar(url('/pesos/formulario')) ?>">Registrar peso</a>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                <tr>
                    <th>Data</th>
                    <th>Peso</th>
                    <th>Gordura</th>
                    <th>Massa magra</th>
                    <th>Variação</th>
                    <th>Observações</th>
                    <th class="tabela-acoes"><span class="visually-hidden">Ações</span></th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($pesos as $indice => $peso): ?>
                    <?php
                    $pesoAnterior = $pesos[$indice + 1] ?? null;
                    $variacao = $pesoAnterior ? (float) $peso['pes_peso_kg'] - (float) $pesoAnterior['pes_peso_kg'] : null;
                    ?>
                    <tr>
                        <td><?= escapar(formatar_data($peso['pes_data_pesagem'])) ?></td>
                        <td class="fw-semibold"><?= formatar_decimal($peso['pes_peso_kg']) ?> kg</td>
                        <td><?= $peso["pes_percentual_gordura"] === null ? "—" : formatar_decimal($peso["pes_percentual_gordura"]) . "%" ?></td>
                        <td><?= $peso["pes_massa_magra_kg"] === null ? "—" : formatar_decimal($peso["pes_massa_magra_kg"]) . " kg" ?></td>
                        <td class="<?= escapar(classe_variacao_peso($variacao)) ?>" title="<?= escapar(situacao_variacao_peso($variacao)) ?>">
                            <?php if ($variacao !== null): ?><i class="bi <?= escapar(icone_variacao_peso($variacao)) ?>" aria-hidden="true"></i><?php endif; ?>
                            <?= $variacao === null ? "—" : ($variacao > 0 ? "+" : "") . formatar_decimal($variacao) . " kg" ?>
                        </td>
                        <td><?= escapar($peso['pes_observacoes'] ?: '—') ?></td>
                        <td class="tabela-acoes">
                            <a class="btn btn-sm btn-outline-primary btn-icone" href="<?= escapar(url('/pesos/formulario?id=' . $peso['pes_id'])) ?>" title="Editar peso" aria-label="Editar peso"><i class="bi bi-pencil-square" aria-hidden="true"></i><span class="visually-hidden">Editar</span></a>
                            <form class="d-inline" action="<?= escapar(url('/pesos/excluir')) ?>" method="post">
                                <?= campo_csrf() ?>
                                <input type="hidden" name="pes_id" value="<?= (int) $peso['pes_id'] ?>">
                                <button class="btn btn-sm btn-outline-danger btn-icone" type="submit" data-confirmar="Excluir este registro de peso?" title="Excluir peso" aria-label="Excluir peso">
                                    <i class="bi bi-trash" aria-hidden="true"></i>
                                    <span class="visually-hidden">Excluir</span>
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

