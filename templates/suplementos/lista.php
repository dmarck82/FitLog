<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <h1 class="h3 mb-1">Suplementos</h1>
        <p class="text-secondary mb-0">Cadastre suplementos e acompanhe compras e consumos.</p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <a class="btn btn-outline-secondary" href="<?= escapar(url('/suplementos/compras')) ?>"><i class="bi bi-bag me-1"></i>Compras</a>
        <a class="btn btn-outline-secondary" href="<?= escapar(url('/suplementos/consumos')) ?>"><i class="bi bi-clock-history me-1"></i>Consumos</a>
        <a class="btn btn-success" href="<?= escapar(url('/suplementos/formulario')) ?>"><i class="bi bi-plus-lg me-1"></i>Novo suplemento</a>
    </div>
</div>

<div class="card">
    <?php if (!$suplementos): ?>
        <div class="card-body p-5 text-center">
            <i class="bi bi-jar display-5 text-success"></i>
            <h2 class="h5 mt-3">Nenhum suplemento cadastrado</h2>
            <p class="text-secondary">Cadastre o primeiro suplemento para registrar compras e consumos.</p>
            <a class="btn btn-success" href="<?= escapar(url('/suplementos/formulario')) ?>">Cadastrar suplemento</a>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light"><tr>
                    <th>Suplemento</th><th>Marca</th><th>Último consumo</th><th>Registros</th><th>Status</th>
                    <th class="tabela-acoes"><span class="visually-hidden">Ações</span></th>
                </tr></thead>
                <tbody>
                <?php foreach ($suplementos as $suplemento): ?>
                    <tr>
                        <td><span class="fw-semibold"><?= escapar($suplemento['sup_nome']) ?></span><br><span class="small text-secondary"><?= escapar($suplemento['sup_apresentacao'] ?: 'Sem apresentação') ?></span></td>
                        <td><?= escapar($suplemento['sup_marca'] ?: '—') ?></td>
                        <td><?= $suplemento['ultimo_consumo'] ? escapar(formatar_data_hora($suplemento['ultimo_consumo'])) : '—' ?></td>
                        <td><span class="small"><?= (int) $suplemento['total_compras'] ?> compra(s) · <?= (int) $suplemento['total_consumos'] ?> consumo(s)</span></td>
                        <td><span class="badge <?= $suplemento['sup_ativo'] ? 'text-bg-success' : 'text-bg-secondary' ?>"><?= $suplemento['sup_ativo'] ? 'Ativo' : 'Inativo' ?></span></td>
                        <td class="tabela-acoes">
                            <a class="btn btn-sm btn-outline-primary btn-icone" href="<?= escapar(url('/suplementos/formulario?id=' . $suplemento['sup_id'])) ?>" title="Editar suplemento" aria-label="Editar suplemento"><i class="bi bi-pencil-square"></i></a>
                            <a class="btn btn-sm btn-outline-success btn-icone" href="<?= escapar(url('/suplementos/compras/formulario?suplemento=' . $suplemento['sup_id'])) ?>" title="Registrar compra" aria-label="Registrar compra"><i class="bi bi-bag-plus"></i></a>
                            <a class="btn btn-sm btn-success btn-icone" href="<?= escapar(url('/suplementos/consumos/formulario?suplemento=' . $suplemento['sup_id'])) ?>" title="Registrar consumo" aria-label="Registrar consumo"><i class="bi bi-plus-circle"></i></a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
