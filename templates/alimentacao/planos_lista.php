<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div><h1 class="h3 mb-1">Planos alimentares</h1><p class="text-secondary mb-0">Organize os planos recebidos e suas respectivas vigências.</p></div>
    <div class="d-flex gap-2"><a class="btn btn-outline-secondary" href="<?= escapar(url('/alimentacao')) ?>">Hoje</a><a class="btn btn-success" href="<?= escapar(url('/alimentacao/planos/formulario')) ?>"><i class="bi bi-plus-lg me-1"></i>Novo plano</a></div>
</div>
<div class="card">
<?php if (!$planos): ?><div class="card-body p-5 text-center"><i class="bi bi-journal-text display-5 text-success"></i><h2 class="h5 mt-3">Nenhum plano alimentar cadastrado</h2><p class="text-secondary">Crie seu primeiro plano para organizar refeições e alimentos.</p></div>
<?php else: ?><div class="table-responsive"><table class="table table-hover align-middle mb-0"><thead class="table-light"><tr><th>Plano</th><th>Vigência</th><th>Estrutura</th><th>Status</th><th class="tabela-acoes"><span class="visually-hidden">Ações</span></th></tr></thead><tbody>
<?php foreach ($planos as $plano): ?><?php $situacao = situacao_plano_alimentar($plano); ?><tr>
    <td><span class="fw-semibold"><?= escapar($plano['pal_nome']) ?></span><br><span class="small text-secondary"><?= escapar($plano['pal_profissional'] ?: 'Profissional não informado') ?></span></td>
    <td><?= escapar(formatar_data($plano['pal_data_inicio'])) ?> a <?= $plano['pal_data_fim'] ? escapar(formatar_data($plano['pal_data_fim'])) : 'sem data final' ?></td>
    <td><span class="small"><?= (int) $plano['total_refeicoes'] ?> refeição(ões) · <?= (int) $plano['total_itens'] ?> item(ns)</span></td>
    <td><span class="badge <?= escapar($situacao['classe']) ?>"><?= escapar($situacao['rotulo']) ?></span></td>
    <td class="tabela-acoes"><a class="btn btn-sm btn-outline-success btn-icone" href="<?= escapar(url('/alimentacao/planos/detalhes?id=' . $plano['pal_id'])) ?>" title="Ver plano" aria-label="Ver plano"><i class="bi bi-eye"></i></a> <a class="btn btn-sm btn-outline-primary btn-icone" href="<?= escapar(url('/alimentacao/planos/formulario?id=' . $plano['pal_id'])) ?>" title="Editar plano" aria-label="Editar plano"><i class="bi bi-pencil-square"></i></a> <a class="btn btn-sm btn-success btn-icone" href="<?= escapar(url('/alimentacao/refeicoes/formulario?plano=' . $plano['pal_id'])) ?>" title="Adicionar refeição" aria-label="Adicionar refeição"><i class="bi bi-plus-circle"></i></a></td>
</tr><?php endforeach; ?></tbody></table></div><?php endif; ?>
</div>
