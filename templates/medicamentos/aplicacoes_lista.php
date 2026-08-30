<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div><h1 class="h3 mb-1">Aplicações de medicamentos</h1><p class="text-secondary mb-0">Histórico das doses efetivamente administradas.</p></div>
    <div class="d-flex gap-2"><a class="btn btn-outline-secondary" href="<?= escapar(url('/medicamentos')) ?>">Medicamentos</a><a class="btn btn-success" href="<?= escapar(url('/medicamentos/aplicacoes/formulario')) ?>"><i class="bi bi-eyedropper me-1"></i>Registrar aplicação</a></div>
</div>
<div class="card">
<?php if (!$aplicacoes): ?><div class="card-body p-5 text-center"><h2 class="h5">Nenhuma aplicação registrada</h2><p class="text-secondary">Registre uma aplicação para iniciar o histórico.</p></div>
<?php else: ?><div class="table-responsive"><table class="table table-hover align-middle mb-0"><thead class="table-light"><tr><th>Data e hora</th><th>Medicamento</th><th>Dose</th><th>Local</th><th>Reações</th><th>Feedback</th><th class="tabela-acoes"><span class="visually-hidden">Ações</span></th></tr></thead><tbody>
<?php foreach ($aplicacoes as $aplicacao): ?><tr>
    <td><?= escapar(formatar_data_hora($aplicacao['apl_data_aplicacao'])) ?></td><td><span class="fw-semibold"><?= escapar($aplicacao['med_nome']) ?></span><br><span class="small text-secondary"><?= escapar($aplicacao['med_apresentacao'] ?: '') ?></span></td><td><?= formatar_decimal($aplicacao['apl_dose']) ?> <?= escapar(UNIDADES_DOSE[$aplicacao['apl_unidade']] ?? $aplicacao['apl_unidade']) ?></td><td><?= escapar($aplicacao['apl_local_aplicacao'] ?: '—') ?></td><td><?= escapar($aplicacao['apl_reacoes'] ?: '—') ?></td><td><?= escapar($aplicacao["apl_observacoes"] ?: "—") ?></td>
    <td class="tabela-acoes"><a class="btn btn-sm btn-outline-primary btn-icone" href="<?= escapar(url('/medicamentos/aplicacoes/formulario?id=' . $aplicacao['apl_id'])) ?>" title="Editar aplicação" aria-label="Editar aplicação"><i class="bi bi-pencil-square"></i></a>
    <form class="d-inline" action="<?= escapar(url('/medicamentos/aplicacoes/excluir')) ?>" method="post"><?= campo_csrf() ?><input type="hidden" name="apl_id" value="<?= (int) $aplicacao['apl_id'] ?>"><button class="btn btn-sm btn-outline-danger btn-icone" type="submit" data-confirmar="Excluir esta aplicação?" title="Excluir aplicação" aria-label="Excluir aplicação"><i class="bi bi-trash"></i></button></form></td>
</tr><?php endforeach; ?></tbody></table></div><?php endif; ?>
</div>

