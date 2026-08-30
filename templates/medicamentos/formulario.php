<?php $editando = !empty($medicamento['med_id']); ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div><h1 class="h3 mb-1"><?= $editando ? 'Editar medicamento' : 'Novo medicamento' ?></h1><p class="text-secondary mb-0">Registre somente as informações fornecidas pelo profissional responsável.</p></div>
    <a class="btn btn-outline-secondary" href="<?= escapar(url('/medicamentos')) ?>">Voltar</a>
</div>

<form action="<?= escapar(url('/medicamentos/salvar')) ?>" method="post">
    <?= campo_csrf() ?>
    <?php if ($editando): ?><input type="hidden" name="med_id" value="<?= (int) $medicamento['med_id'] ?>"><?php endif; ?>
    <div class="card"><div class="card-body p-4">
        <div class="row g-3">
            <div class="col-md-6"><label class="form-label campo-obrigatorio" for="med_nome">Nome</label><input class="form-control" id="med_nome" name="med_nome" maxlength="120" value="<?= escapar($medicamento['med_nome'] ?? '') ?>" required autofocus></div>
            <div class="col-md-6"><label class="form-label" for="med_apresentacao">Apresentação ou concentração</label><input class="form-control" id="med_apresentacao" name="med_apresentacao" maxlength="120" value="<?= escapar($medicamento['med_apresentacao'] ?? '') ?>" placeholder="Ex.: caneta 10 mg/mL"></div>
            <div class="col-md-5"><label class="form-label" for="med_via_administracao">Via de administração</label><select class="form-select" id="med_via_administracao" name="med_via_administracao"><option value="">Selecione</option><?php foreach (VIAS_MEDICAMENTO as $valor => $rotulo): ?><option value="<?= escapar($valor) ?>" <?= ($medicamento['med_via_administracao'] ?? '') === $valor ? 'selected' : '' ?>><?= escapar($rotulo) ?></option><?php endforeach; ?></select></div>
            <div class="col-md-7 d-flex flex-column justify-content-end gap-2">
                <div class="form-check form-switch"><input class="form-check-input" type="checkbox" role="switch" id="med_ativo" name="med_ativo" value="1" <?= !empty($medicamento["med_ativo"]) ? "checked" : "" ?>><label class="form-check-label" for="med_ativo">Medicamento ativo</label></div>
                <div class="form-check form-switch"><input class="form-check-input" type="checkbox" role="switch" id="med_solicitar_feedback" name="med_solicitar_feedback" value="1" <?= !empty($medicamento["med_solicitar_feedback"]) ? "checked" : "" ?>><label class="form-check-label" for="med_solicitar_feedback">Pedir feedback antes da próxima aplicação</label></div>
            </div>
            <div class="col-12"><label class="form-label" for="med_orientacoes">Orientações médicas</label><textarea class="form-control" id="med_orientacoes" name="med_orientacoes" rows="3" maxlength="3000"><?= escapar($medicamento['med_orientacoes'] ?? '') ?></textarea></div>
            <div class="col-12"><label class="form-label" for="med_observacoes">Observações pessoais</label><textarea class="form-control" id="med_observacoes" name="med_observacoes" rows="3" maxlength="3000"><?= escapar($medicamento['med_observacoes'] ?? '') ?></textarea></div>
        </div>
        <div class="d-flex justify-content-end gap-2 mt-4"><a class="btn btn-outline-secondary" href="<?= escapar(url('/medicamentos')) ?>">Cancelar</a><button class="btn btn-success" type="submit">Salvar medicamento</button></div>
    </div></div>
</form>

