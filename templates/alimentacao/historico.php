<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div><h1 class="h3 mb-1">Histórico alimentar</h1><p class="text-secondary mb-0">Aderência calculada somente sobre as refeições registradas em cada dia.</p></div>
    <a class="btn btn-outline-secondary" href="<?= escapar(url('/alimentacao')) ?>">Alimentação de hoje</a>
</div>
<div class="card">
<?php if (!$dias): ?><div class="card-body p-5 text-center"><i class="bi bi-calendar2-check display-5 text-success"></i><h2 class="h5 mt-3">Nenhuma refeição realizada registrada</h2><p class="text-secondary">Os dias aparecerão aqui após o primeiro registro.</p></div>
<?php else: ?><div class="table-responsive"><table class="table table-hover align-middle mb-0"><thead class="table-light"><tr><th>Data</th><th>Refeições registradas</th><th>Conformes</th><th>Aderência</th><th class="tabela-acoes"><span class="visually-hidden">Ações</span></th></tr></thead><tbody>
<?php foreach ($dias as $dia): ?><tr><td class="fw-semibold"><?= escapar(formatar_data($dia['ral_data'])) ?></td><td><?= (int) $dia['total_registros'] ?></td><td><?= (int) $dia['total_conformes'] ?></td><td><span class="fw-semibold"><?= (int) $dia['aderencia'] ?>%</span></td><td class="tabela-acoes"><a class="btn btn-sm btn-outline-success btn-icone" href="<?= escapar(url('/alimentacao?data=' . $dia['ral_data'])) ?>" title="Ver comparativo do dia" aria-label="Ver comparativo do dia"><i class="bi bi-eye"></i></a></td></tr><?php endforeach; ?>
</tbody></table></div><?php endif; ?>
</div>
