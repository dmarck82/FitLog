<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div><h1 class="h3 mb-1">Compras de suplementos</h1><p class="text-secondary mb-0">Histórico de aquisições, lotes e validades.</p></div>
    <div class="d-flex gap-2"><a class="btn btn-outline-secondary" href="<?= escapar(url('/suplementos')) ?>">Suplementos</a><a class="btn btn-success" href="<?= escapar(url('/suplementos/compras/formulario')) ?>"><i class="bi bi-plus-lg me-1"></i>Registrar compra</a></div>
</div>
<div class="card">
<?php if (!$compras): ?><div class="card-body p-5 text-center"><h2 class="h5">Nenhuma compra registrada</h2><p class="text-secondary">Registre uma compra para iniciar o histórico.</p></div>
<?php else: ?><div class="table-responsive"><table class="table table-hover align-middle mb-0"><thead class="table-light"><tr><th>Data</th><th>Suplemento</th><th>Quantidade</th><th>Valor</th><th>Lote</th><th>Validade</th><th class="tabela-acoes"><span class="visually-hidden">Ações</span></th></tr></thead><tbody>
<?php foreach ($compras as $compra): ?><tr>
    <td><?= escapar(formatar_data($compra['cps_data_compra'])) ?></td><td><span class="fw-semibold"><?= escapar($compra['sup_nome']) ?></span><br><span class="small text-secondary"><?= escapar(implode(' · ', array_filter([$compra['sup_marca'], $compra['sup_apresentacao']]))) ?></span></td>
    <td><?= $compra['cps_quantidade'] === null ? '—' : formatar_decimal($compra['cps_quantidade']) ?></td><td><?= $compra['cps_valor'] === null ? '—' : 'R$ ' . formatar_decimal($compra['cps_valor']) ?></td><td><?= escapar($compra['cps_lote'] ?: '—') ?></td><td><?= escapar(formatar_data($compra['cps_data_validade'])) ?></td>
    <td class="tabela-acoes"><a class="btn btn-sm btn-outline-primary btn-icone" href="<?= escapar(url('/suplementos/compras/formulario?id=' . $compra['cps_id'])) ?>" title="Editar compra" aria-label="Editar compra"><i class="bi bi-pencil-square"></i></a>
    <form class="d-inline" action="<?= escapar(url('/suplementos/compras/excluir')) ?>" method="post"><?= campo_csrf() ?><input type="hidden" name="cps_id" value="<?= (int) $compra['cps_id'] ?>"><button class="btn btn-sm btn-outline-danger btn-icone" type="submit" data-confirmar="Excluir esta compra?" title="Excluir compra" aria-label="Excluir compra"><i class="bi bi-trash"></i></button></form></td>
</tr><?php endforeach; ?></tbody></table></div><?php endif; ?>
</div>
