<?php $editando = !empty($peso['pes_id']); ?>
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h1 class="h3 mb-1"><?= $editando ? 'Editar peso' : 'Registrar peso' ?></h1>
        <p class="text-secondary mb-0">Um registro rápido para o acompanhamento do dia a dia.</p>
    </div>
    <a class="btn btn-outline-secondary" href="<?= escapar(url('/pesos')) ?>">Voltar</a>
</div>

<form action="<?= escapar(url('/pesos/salvar')) ?>" method="post">
    <?= campo_csrf() ?>
    <?php if ($editando): ?>
        <input type="hidden" name="pes_id" value="<?= (int) $peso['pes_id'] ?>">
    <?php endif; ?>

    <div class="card mx-auto" style="max-width: 42rem">
        <div class="card-body p-4">
            <div class="row g-3">
                <div class="col-sm-6">
                    <label class="form-label campo-obrigatorio" for="pes_data_pesagem">Data da pesagem</label>
                    <input class="form-control" type="date" id="pes_data_pesagem" name="pes_data_pesagem" value="<?= escapar($peso['pes_data_pesagem'] ?? '') ?>" required>
                </div>
                <div class="col-sm-6">
                    <label class="form-label campo-obrigatorio" for="pes_peso_kg">Peso</label>
                    <div class="input-group">
                        <input class="form-control" type="number" step="0.01" min="0.01" max="500" id="pes_peso_kg" name="pes_peso_kg" value="<?= escapar(formatar_decimal_input($peso['pes_peso_kg'] ?? '')) ?>" inputmode="decimal" required autofocus>
                        <span class="input-group-text">kg</span>
                    </div>
                </div>
                <div class="col-sm-6">
                    <label class="form-label" for="pes_percentual_gordura">Gordura corporal</label>
                    <div class="input-group">
                        <input class="form-control" type="number" step="0.01" min="0" max="100" id="pes_percentual_gordura" name="pes_percentual_gordura" value="<?= escapar(formatar_decimal_input($peso["pes_percentual_gordura"] ?? "")) ?>" inputmode="decimal">
                        <span class="input-group-text">%</span>
                    </div>
                </div>
                <div class="col-sm-6">
                    <label class="form-label" for="pes_massa_magra_kg">Massa magra</label>
                    <div class="input-group">
                        <input class="form-control" type="number" step="0.01" min="0" max="500" id="pes_massa_magra_kg" name="pes_massa_magra_kg" value="<?= escapar(formatar_decimal_input($peso["pes_massa_magra_kg"] ?? "")) ?>" inputmode="decimal">
                        <span class="input-group-text">kg</span>
                    </div>
                </div>
                <div class="col-12">
                    <label class="form-label" for="pes_observacoes">Observações</label>
                    <textarea class="form-control" id="pes_observacoes" name="pes_observacoes" rows="3" maxlength="2000"><?= escapar($peso['pes_observacoes'] ?? '') ?></textarea>
                </div>
            </div>

            <div class="d-flex justify-content-end gap-2 mt-4">
                <a class="btn btn-outline-secondary" href="<?= escapar(url('/pesos')) ?>">Cancelar</a>
                <button class="btn btn-success" type="submit">Salvar peso</button>
            </div>
        </div>
    </div>
</form>

