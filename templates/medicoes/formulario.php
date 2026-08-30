<?php $editando = !empty($medicao['mec_id']); ?>
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h1 class="h3 mb-1"><?= $editando ? 'Editar medição' : 'Nova medição' ?></h1>
        <p class="text-secondary mb-0">Preencha somente as medidas disponíveis.</p>
    </div>
    <a class="btn btn-outline-secondary" href="<?= escapar(url('/medidas')) ?>">Voltar</a>
</div>

<form action="<?= escapar(url('/medidas/salvar')) ?>" method="post">
    <?= campo_csrf() ?>
    <?php if ($editando): ?>
        <input type="hidden" name="mec_id" value="<?= (int) $medicao['mec_id'] ?>">
    <?php endif; ?>

    <div class="card mb-4">
        <div class="card-body p-4">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label campo-obrigatorio" for="mec_data_medicao">Data da medição</label>
                    <input class="form-control" type="date" id="mec_data_medicao" name="mec_data_medicao" value="<?= escapar($medicao['mec_data_medicao'] ?? '') ?>" required>
                </div>
            </div>

            <hr class="my-4">
            <h2 class="h5 mb-3">Circunferências</h2>
            <div class="row g-3">
                <?php foreach (CAMPOS_MEDICAO as $campo => [$rotulo, $unidade]): ?>
                    <div class="col-sm-6 col-lg-4">
                        <label class="form-label" for="<?= escapar($campo) ?>"><?= escapar($rotulo) ?></label>
                        <div class="input-group">
                            <input class="form-control" type="number" step="0.01" min="0" id="<?= escapar($campo) ?>" name="<?= escapar($campo) ?>" value="<?= escapar(formatar_decimal_input($medicao[$campo] ?? '')) ?>">
                            <span class="input-group-text"><?= escapar($unidade) ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <hr class="my-4">
            <div>
                <label class="form-label" for="mec_observacoes">Observações</label>
                <textarea class="form-control" id="mec_observacoes" name="mec_observacoes" rows="3" maxlength="2000"><?= escapar($medicao['mec_observacoes'] ?? '') ?></textarea>
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-end gap-2">
        <a class="btn btn-outline-secondary" href="<?= escapar(url('/medidas')) ?>">Cancelar</a>
        <button class="btn btn-success" type="submit">Salvar medição</button>
    </div>
</form>

