<?php $editando = !empty($suplemento['sup_id']); ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div><h1 class="h3 mb-1"><?= $editando ? 'Editar suplemento' : 'Novo suplemento' ?></h1><p class="text-secondary mb-0">Registre os dados do suplemento e as orientações de uso recebidas.</p></div>
    <a class="btn btn-outline-secondary" href="<?= escapar(url('/suplementos')) ?>">Voltar</a>
</div>

<form action="<?= escapar(url('/suplementos/salvar')) ?>" method="post">
    <?= campo_csrf() ?>
    <?php if ($editando): ?><input type="hidden" name="sup_id" value="<?= (int) $suplemento['sup_id'] ?>"><?php endif; ?>
    <div class="card"><div class="card-body p-4">
        <div class="row g-3">
            <div class="col-md-6"><label class="form-label campo-obrigatorio" for="sup_nome">Nome</label><input class="form-control" id="sup_nome" name="sup_nome" maxlength="120" value="<?= escapar($suplemento['sup_nome'] ?? '') ?>" required autofocus placeholder="Ex.: Creatina monohidratada"></div>
            <div class="col-md-6"><label class="form-label" for="sup_marca">Marca</label><input class="form-control" id="sup_marca" name="sup_marca" maxlength="120" value="<?= escapar($suplemento['sup_marca'] ?? '') ?>"></div>
            <div class="col-md-6"><label class="form-label" for="sup_apresentacao">Apresentação ou concentração</label><input class="form-control" id="sup_apresentacao" name="sup_apresentacao" maxlength="120" value="<?= escapar($suplemento['sup_apresentacao'] ?? '') ?>" placeholder="Ex.: pote de 300 g"></div>
            <div class="col-md-6 d-flex flex-column justify-content-end gap-2">
                <div class="form-check form-switch"><input class="form-check-input" type="checkbox" role="switch" id="sup_ativo" name="sup_ativo" value="1" <?= !empty($suplemento['sup_ativo']) ? 'checked' : '' ?>><label class="form-check-label" for="sup_ativo">Suplemento ativo</label></div>
                <div class="form-check form-switch"><input class="form-check-input" type="checkbox" role="switch" id="sup_solicitar_feedback" name="sup_solicitar_feedback" value="1" <?= !empty($suplemento['sup_solicitar_feedback']) ? 'checked' : '' ?>><label class="form-check-label" for="sup_solicitar_feedback">Pedir feedback antes do próximo consumo</label></div>
            </div>
            <div class="col-12"><label class="form-label" for="sup_orientacoes">Orientações de uso</label><textarea class="form-control" id="sup_orientacoes" name="sup_orientacoes" rows="3" maxlength="3000" placeholder="Ex.: consumir após o treino"><?= escapar($suplemento['sup_orientacoes'] ?? '') ?></textarea></div>
            <div class="col-12"><label class="form-label" for="sup_observacoes">Observações pessoais</label><textarea class="form-control" id="sup_observacoes" name="sup_observacoes" rows="3" maxlength="3000"><?= escapar($suplemento['sup_observacoes'] ?? '') ?></textarea></div>
        </div>
        <div class="d-flex justify-content-end gap-2 mt-4"><a class="btn btn-outline-secondary" href="<?= escapar(url('/suplementos')) ?>">Cancelar</a><button class="btn btn-success" type="submit">Salvar suplemento</button></div>
    </div></div>
</form>
