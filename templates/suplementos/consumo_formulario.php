<?php $editando = !empty($consumo['cos_id']); ?>
<div class="d-flex justify-content-between align-items-center mb-4"><div><h1 class="h3 mb-1"><?= $editando ? 'Editar consumo' : 'Registrar consumo' ?></h1><p class="text-secondary mb-0">Registre somente a dose efetivamente consumida.</p></div><a class="btn btn-outline-secondary" href="<?= escapar(url('/suplementos/consumos')) ?>">Voltar</a></div>
<?php if (!$suplementos): ?><div class="alert alert-warning">Cadastre um suplemento antes de registrar um consumo. <a href="<?= escapar(url('/suplementos/formulario')) ?>">Cadastrar agora</a>.</div><?php else: ?>
<form action="<?= escapar(url('/suplementos/consumos/salvar')) ?>" method="post"><?= campo_csrf() ?><?php if ($editando): ?><input type="hidden" name="cos_id" value="<?= (int) $consumo['cos_id'] ?>"><?php endif; ?>
<?php if (!$editando): ?>
<div id="feedback-consumo-anterior" class="alert alert-info d-none" data-url="<?= escapar(url('/suplementos/consumos/feedback')) ?>" aria-live="polite">
    <div class="d-flex gap-3">
        <i class="bi bi-chat-left-text fs-4" aria-hidden="true"></i>
        <div class="flex-grow-1">
            <h2 class="h6 mb-1">Feedback do consumo anterior</h2>
            <p class="small mb-3">Consumo de <strong id="feedback-consumo-resumo"></strong>. Como você se sentiu depois dele?</p>
            <label class="form-label" for="feedback_consumo_anterior">Feedback <span class="text-secondary">(opcional)</span></label>
            <textarea class="form-control" id="feedback_consumo_anterior" name="feedback_consumo_anterior" rows="3" maxlength="250" placeholder="Ex.: tive mais disposição ou senti desconforto abdominal"><?= escapar($consumo['feedback_consumo_anterior'] ?? '') ?></textarea>
            <div class="text-end small text-secondary mt-1"><span id="feedback-consumo-contador">0</span>/250 caracteres</div>
            <input type="hidden" id="feedback_consumo_anterior_carregado" name="feedback_consumo_anterior_carregado" value="0">
        </div>
    </div>
</div>
<?php endif; ?>
<div class="card"><div class="card-body p-4"><div class="row g-3">
    <div class="col-md-7"><label class="form-label campo-obrigatorio" for="sup_id">Suplemento</label><select class="form-select" id="sup_id" name="sup_id" required><option value="">Selecione</option><?php foreach ($suplementos as $suplemento): ?><option value="<?= (int) $suplemento['sup_id'] ?>" <?= (string) ($consumo['sup_id'] ?? '') === (string) $suplemento['sup_id'] ? 'selected' : '' ?>><?= escapar($suplemento['sup_nome'] . ($suplemento['sup_apresentacao'] ? ' — ' . $suplemento['sup_apresentacao'] : '') . (!$suplemento['sup_ativo'] ? ' (inativo)' : '')) ?></option><?php endforeach; ?></select></div>
    <div class="col-md-5"><label class="form-label campo-obrigatorio" for="cos_data_consumo">Data e hora</label><input class="form-control" type="datetime-local" id="cos_data_consumo" name="cos_data_consumo" value="<?= escapar($consumo['cos_data_consumo'] ?? '') ?>" required></div>
    <div class="col-md-6"><label class="form-label campo-obrigatorio" for="cos_dose">Dose</label><input class="form-control" type="number" step="0.01" min="0.01" id="cos_dose" name="cos_dose" value="<?= escapar(formatar_decimal_input($consumo['cos_dose'] ?? '')) ?>" required></div>
    <div class="col-md-6"><label class="form-label campo-obrigatorio" for="cos_unidade">Unidade</label><select class="form-select" id="cos_unidade" name="cos_unidade" required><option value="">Selecione</option><?php foreach (UNIDADES_SUPLEMENTO as $valor => $rotulo): ?><option value="<?= escapar($valor) ?>" <?= ($consumo['cos_unidade'] ?? '') === $valor ? 'selected' : '' ?>><?= escapar($rotulo) ?></option><?php endforeach; ?></select></div>
    <div class="col-md-6"><label class="form-label" for="cos_reacoes">Percepções ou reações</label><textarea class="form-control" id="cos_reacoes" name="cos_reacoes" rows="3" maxlength="2000"><?= escapar($consumo['cos_reacoes'] ?? '') ?></textarea></div>
    <div class="col-md-6"><label class="form-label" for="cos_observacoes">Observações deste consumo</label><textarea class="form-control" id="cos_observacoes" name="cos_observacoes" rows="3" maxlength="250"><?= escapar($consumo['cos_observacoes'] ?? '') ?></textarea></div>
</div><div class="d-flex justify-content-end gap-2 mt-4"><a class="btn btn-outline-secondary" href="<?= escapar(url('/suplementos/consumos')) ?>">Cancelar</a><button class="btn btn-success" type="submit">Salvar consumo</button></div></div></div></form><?php endif; ?>
