<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <h1 class="h3 mb-1">Medicamentos</h1>
        <p class="text-secondary mb-0">Cadastre medicamentos e acompanhe compras e aplicações.</p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <a class="btn btn-outline-secondary" href="<?= escapar(url('/medicamentos/compras')) ?>"><i class="bi bi-bag me-1"></i>Compras</a>
        <a class="btn btn-outline-secondary" href="<?= escapar(url('/medicamentos/aplicacoes')) ?>"><i class="bi bi-clock-history me-1"></i>Aplicações</a>
        <a class="btn btn-success" href="<?= escapar(url('/medicamentos/formulario')) ?>"><i class="bi bi-plus-lg me-1"></i>Novo medicamento</a>
    </div>
</div>

<div class="card">
    <?php if (!$medicamentos): ?>
        <div class="card-body p-5 text-center">
            <i class="bi bi-capsule display-5 text-success"></i>
            <h2 class="h5 mt-3">Nenhum medicamento cadastrado</h2>
            <p class="text-secondary">Cadastre o primeiro medicamento para registrar compras e aplicações.</p>
            <a class="btn btn-success" href="<?= escapar(url('/medicamentos/formulario')) ?>">Cadastrar medicamento</a>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light"><tr>
                    <th>Medicamento</th><th>Via</th><th>Última aplicação</th><th>Registros</th><th>Status</th>
                    <th class="tabela-acoes"><span class="visually-hidden">Ações</span></th>
                </tr></thead>
                <tbody>
                <?php foreach ($medicamentos as $medicamento): ?>
                    <tr>
                        <td><span class="fw-semibold"><?= escapar($medicamento['med_nome']) ?></span><br><span class="small text-secondary"><?= escapar($medicamento['med_apresentacao'] ?: 'Sem apresentação') ?></span></td>
                        <td><?= escapar(VIAS_MEDICAMENTO[$medicamento['med_via_administracao']] ?? '—') ?></td>
                        <td><?= $medicamento['ultima_aplicacao'] ? escapar(formatar_data_hora($medicamento['ultima_aplicacao'])) : '—' ?></td>
                        <td><span class="small"><?= (int) $medicamento['total_compras'] ?> compra(s) · <?= (int) $medicamento['total_aplicacoes'] ?> aplicação(ões)</span></td>
                        <td><span class="badge <?= $medicamento['med_ativo'] ? 'text-bg-success' : 'text-bg-secondary' ?>"><?= $medicamento['med_ativo'] ? 'Ativo' : 'Inativo' ?></span></td>
                        <td class="tabela-acoes">
                            <a class="btn btn-sm btn-outline-primary btn-icone" href="<?= escapar(url('/medicamentos/formulario?id=' . $medicamento['med_id'])) ?>" title="Editar medicamento" aria-label="Editar medicamento"><i class="bi bi-pencil-square"></i></a>
                            <a class="btn btn-sm btn-outline-success btn-icone" href="<?= escapar(url('/medicamentos/compras/formulario?medicamento=' . $medicamento['med_id'])) ?>" title="Registrar compra" aria-label="Registrar compra"><i class="bi bi-bag-plus"></i></a>
                            <a class="btn btn-sm btn-success btn-icone" href="<?= escapar(url('/medicamentos/aplicacoes/formulario?medicamento=' . $medicamento['med_id'])) ?>" title="Registrar aplicação" aria-label="Registrar aplicação"><i class="bi bi-eyedropper"></i></a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

