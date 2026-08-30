<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div><h1 class="h3 mb-1">Consumos de suplementos</h1><p class="text-secondary mb-0">Histórico das doses efetivamente consumidas.</p></div>
    <div class="d-flex gap-2"><a class="btn btn-outline-secondary" href="<?= escapar(url('/suplementos')) ?>">Suplementos</a><a class="btn btn-success" href="<?= escapar(url('/suplementos/consumos/formulario')) ?>"><i class="bi bi-plus-circle me-1"></i>Registrar consumo</a></div>
</div>
<div class="card">
<?php if (!$consumos): ?><div class="card-body p-5 text-center"><h2 class="h5">Nenhum consumo registrado</h2><p class="text-secondary">Registre um consumo para iniciar o histórico.</p></div>
<?php else: ?><div class="table-responsive"><table class="table table-hover align-middle mb-0"><thead class="table-light"><tr><th>Data e hora</th><th>Suplemento</th><th>Dose</th><th>Percepções ou reações</th><th>Feedback</th><th class="tabela-acoes"><span class="visually-hidden">Ações</span></th></tr></thead><tbody>
<?php foreach ($consumos as $consumo): ?><tr>
    <td><?= escapar(formatar_data_hora($consumo['cos_data_consumo'])) ?></td><td><span class="fw-semibold"><?= escapar($consumo['sup_nome']) ?></span><br><span class="small text-secondary"><?= escapar(implode(' · ', array_filter([$consumo['sup_marca'], $consumo['sup_apresentacao']]))) ?></span></td><td><?= formatar_decimal($consumo['cos_dose']) ?> <?= escapar(UNIDADES_SUPLEMENTO[$consumo['cos_unidade']] ?? $consumo['cos_unidade']) ?></td><td><?= escapar($consumo['cos_reacoes'] ?: '—') ?></td><td><?= escapar($consumo['cos_observacoes'] ?: '—') ?></td>
    <td class="tabela-acoes"><a class="btn btn-sm btn-outline-primary btn-icone" href="<?= escapar(url('/suplementos/consumos/formulario?id=' . $consumo['cos_id'])) ?>" title="Editar consumo" aria-label="Editar consumo"><i class="bi bi-pencil-square"></i></a>
    <form class="d-inline" action="<?= escapar(url('/suplementos/consumos/excluir')) ?>" method="post"><?= campo_csrf() ?><input type="hidden" name="cos_id" value="<?= (int) $consumo['cos_id'] ?>"><button class="btn btn-sm btn-outline-danger btn-icone" type="submit" data-confirmar="Excluir este consumo?" title="Excluir consumo" aria-label="Excluir consumo"><i class="bi bi-trash"></i></button></form></td>
</tr><?php endforeach; ?></tbody></table></div><?php endif; ?>
</div>
