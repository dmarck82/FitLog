<?php $editando = !empty($aplicacao['apl_id']); ?>
<div class="d-flex justify-content-between align-items-center mb-4"><div><h1 class="h3 mb-1"><?= $editando ? 'Editar aplicação' : 'Registrar aplicação' ?></h1><p class="text-secondary mb-0">Registre somente a dose efetivamente administrada.</p></div><a class="btn btn-outline-secondary" href="<?= escapar(url('/medicamentos/aplicacoes')) ?>">Voltar</a></div>
<?php if (!$medicamentos): ?><div class="alert alert-warning">Cadastre um medicamento antes de registrar uma aplicação. <a href="<?= escapar(url('/medicamentos/formulario')) ?>">Cadastrar agora</a>.</div><?php else: ?>
<form action="<?= escapar(url('/medicamentos/aplicacoes/salvar')) ?>" method="post"><?= campo_csrf() ?><?php if ($editando): ?><input type="hidden" name="apl_id" value="<?= (int) $aplicacao['apl_id'] ?>"><?php endif; ?>
<?php if (!$editando): ?>
<div id="feedback-aplicacao-anterior" class="alert alert-info d-none" data-url="<?= escapar(url("/medicamentos/aplicacoes/feedback")) ?>" aria-live="polite">
    <div class="d-flex gap-3">
        <i class="bi bi-chat-left-text fs-4" aria-hidden="true"></i>
        <div class="flex-grow-1">
            <h2 class="h6 mb-1">Feedback da aplicação anterior</h2>
            <p class="small mb-3">Aplicação de <strong id="feedback-aplicacao-resumo"></strong>. Como você se sentiu depois dela?</p>
            <label class="form-label" for="feedback_aplicacao_anterior">Feedback <span class="text-secondary">(opcional)</span></label>
            <textarea class="form-control" id="feedback_aplicacao_anterior" name="feedback_aplicacao_anterior" rows="3" maxlength="250" placeholder="Ex.: senti muita náusea ou não percebi mudança na vontade de comer"><?= escapar($aplicacao["feedback_aplicacao_anterior"] ?? "") ?></textarea>
            <div class="text-end small text-secondary mt-1"><span id="feedback-contador">0</span>/250 caracteres</div>
            <input type="hidden" id="feedback_aplicacao_anterior_carregado" name="feedback_aplicacao_anterior_carregado" value="0">
        </div>
    </div>
</div>
<?php endif; ?>
<div class="card"><div class="card-body p-4"><div class="row g-3">
    <div class="col-md-7"><label class="form-label campo-obrigatorio" for="med_id">Medicamento</label><select class="form-select" id="med_id" name="med_id" required><option value="">Selecione</option><?php foreach ($medicamentos as $medicamento): ?><option value="<?= (int) $medicamento['med_id'] ?>" <?= (string) ($aplicacao['med_id'] ?? '') === (string) $medicamento['med_id'] ? 'selected' : '' ?>><?= escapar($medicamento['med_nome'] . ($medicamento['med_apresentacao'] ? ' — ' . $medicamento['med_apresentacao'] : '') . (!$medicamento['med_ativo'] ? ' (inativo)' : '')) ?></option><?php endforeach; ?></select></div>
    <div class="col-md-5"><label class="form-label campo-obrigatorio" for="apl_data_aplicacao">Data e hora</label><input class="form-control" type="datetime-local" id="apl_data_aplicacao" name="apl_data_aplicacao" value="<?= escapar($aplicacao['apl_data_aplicacao'] ?? '') ?>" required></div>
    <div class="col-md-4"><label class="form-label campo-obrigatorio" for="apl_dose">Dose</label><input class="form-control" type="number" step="0.01" min="0.01" id="apl_dose" name="apl_dose" value="<?= escapar(formatar_decimal_input($aplicacao['apl_dose'] ?? '')) ?>" required></div>
    <div class="col-md-4"><label class="form-label campo-obrigatorio" for="apl_unidade">Unidade</label><select class="form-select" id="apl_unidade" name="apl_unidade" required><option value="">Selecione</option><?php foreach (UNIDADES_DOSE as $valor => $rotulo): ?><option value="<?= escapar($valor) ?>" <?= ($aplicacao['apl_unidade'] ?? '') === $valor ? 'selected' : '' ?>><?= escapar($rotulo) ?></option><?php endforeach; ?></select></div>
    <div class="col-md-4"><label class="form-label" for="apl_local_aplicacao">Local da aplicação</label><input class="form-control" id="apl_local_aplicacao" name="apl_local_aplicacao" maxlength="100" value="<?= escapar($aplicacao['apl_local_aplicacao'] ?? '') ?>" placeholder="Ex.: abdômen esquerdo"></div>
    <div class="col-md-6"><label class="form-label" for="apl_reacoes">Reações observadas</label><textarea class="form-control" id="apl_reacoes" name="apl_reacoes" rows="3" maxlength="2000"><?= escapar($aplicacao['apl_reacoes'] ?? '') ?></textarea></div>
    <div class="col-md-6"><label class="form-label" for="apl_observacoes">Observações desta aplicação</label><textarea class="form-control" id="apl_observacoes" name="apl_observacoes" rows="3" maxlength="250"><?= escapar($aplicacao['apl_observacoes'] ?? '') ?></textarea></div>
</div><div class="d-flex justify-content-end gap-2 mt-4"><a class="btn btn-outline-secondary" href="<?= escapar(url('/medicamentos/aplicacoes')) ?>">Cancelar</a><button class="btn btn-success" type="submit">Salvar aplicação</button></div></div></div></form><?php endif; ?>

