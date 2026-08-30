<?php
$editando = !empty($refeicao['ref_id']);
if (!$itens) {
    $itens = [['alimento' => '', 'quantidade_informada' => '', 'unidade' => '', 'substituicoes' => '']];
}
?>
<div class="d-flex justify-content-between align-items-center mb-4"><div><h1 class="h3 mb-1"><?= $editando ? 'Editar refeição' : 'Nova refeição' ?></h1><p class="text-secondary mb-0"><?= escapar($plano['pal_nome']) ?></p></div><a class="btn btn-outline-secondary" href="<?= escapar(url('/alimentacao/planos/detalhes?id=' . $plano['pal_id'])) ?>">Voltar</a></div>
<form action="<?= escapar(url('/alimentacao/refeicoes/salvar')) ?>" method="post" id="form-refeicao">
    <?= campo_csrf() ?><input type="hidden" name="pal_id" value="<?= (int) $plano['pal_id'] ?>"><?php if ($editando): ?><input type="hidden" name="ref_id" value="<?= (int) $refeicao['ref_id'] ?>"><?php endif; ?>
    <div class="card mb-4"><div class="card-body p-4"><div class="row g-3">
        <div class="col-md-6"><label class="form-label campo-obrigatorio" for="ref_nome">Nome da refeição</label><input class="form-control" id="ref_nome" name="ref_nome" maxlength="80" value="<?= escapar($refeicao['ref_nome'] ?? '') ?>" required autofocus placeholder="Ex.: Café da manhã"></div>
        <div class="col-md-3"><label class="form-label" for="ref_horario">Horário sugerido</label><input class="form-control" type="time" id="ref_horario" name="ref_horario" value="<?= escapar($refeicao['ref_horario'] ?? '') ?>"></div>
        <div class="col-md-3"><label class="form-label" for="ref_ordem">Ordem de exibição</label><input class="form-control" type="number" min="0" max="999" id="ref_ordem" name="ref_ordem" value="<?= escapar($refeicao['ref_ordem'] ?? 0) ?>"></div>
        <div class="col-12"><label class="form-label" for="ref_observacoes">Orientações da refeição</label><textarea class="form-control" id="ref_observacoes" name="ref_observacoes" rows="2" maxlength="3000"><?= escapar($refeicao['ref_observacoes'] ?? '') ?></textarea></div>
    </div></div></div>
    <div class="d-flex justify-content-between align-items-center mb-2"><div><h2 class="h5 mb-0">Alimentos</h2><span class="small text-secondary">A quantidade é opcional para itens como “salada à vontade”.</span></div><button class="btn btn-outline-success" type="button" id="adicionar-item-alimentar"><i class="bi bi-plus-lg me-1"></i>Adicionar alimento</button></div>
    <div id="itens-alimentares" class="vstack gap-3">
        <?php foreach ($itens as $item): ?><div class="card item-alimentar"><div class="card-body"><div class="row g-3 align-items-start">
            <div class="col-md-5"><label class="form-label campo-obrigatorio">Alimento</label><input class="form-control" name="ita_alimento[]" maxlength="160" value="<?= escapar($item['ita_alimento'] ?? $item['alimento'] ?? '') ?>" required placeholder="Ex.: Arroz integral"></div>
            <div class="col-md-2"><label class="form-label">Quantidade</label><input class="form-control" type="number" step="0.01" min="0.01" name="ita_quantidade[]" value="<?= escapar(formatar_decimal_input($item['ita_quantidade'] ?? $item['quantidade_informada'] ?? '')) ?>"></div>
            <div class="col-md-3"><label class="form-label">Unidade</label><select class="form-select" name="ita_unidade[]"><option value="">Selecione</option><?php foreach (UNIDADES_ALIMENTO as $valor => $rotulo): ?><option value="<?= escapar($valor) ?>" <?= ($item['ita_unidade'] ?? $item['unidade'] ?? '') === $valor ? 'selected' : '' ?>><?= escapar($rotulo) ?></option><?php endforeach; ?></select></div>
            <div class="col-md-2 text-end"><label class="form-label d-block">&nbsp;</label><button class="btn btn-outline-danger btn-icone remover-item-alimentar" type="button" title="Remover alimento" aria-label="Remover alimento"><i class="bi bi-trash"></i></button></div>
            <div class="col-12"><label class="form-label">Substituições</label><textarea class="form-control" name="ita_substituicoes[]" rows="2" maxlength="2000" placeholder="Ex.: substituir por batata-doce ou mandioca"><?= escapar($item['ita_substituicoes'] ?? $item['substituicoes'] ?? '') ?></textarea></div>
        </div></div></div><?php endforeach; ?>
    </div>
    <div class="d-flex justify-content-end gap-2 mt-4"><a class="btn btn-outline-secondary" href="<?= escapar(url('/alimentacao/planos/detalhes?id=' . $plano['pal_id'])) ?>">Cancelar</a><button class="btn btn-success" type="submit">Salvar refeição</button></div>
</form>
<template id="modelo-item-alimentar"><div class="card item-alimentar"><div class="card-body"><div class="row g-3 align-items-start">
    <div class="col-md-5"><label class="form-label campo-obrigatorio">Alimento</label><input class="form-control" name="ita_alimento[]" maxlength="160" required placeholder="Ex.: Arroz integral"></div>
    <div class="col-md-2"><label class="form-label">Quantidade</label><input class="form-control" type="number" step="0.01" min="0.01" name="ita_quantidade[]"></div>
    <div class="col-md-3"><label class="form-label">Unidade</label><select class="form-select" name="ita_unidade[]"><option value="">Selecione</option><?php foreach (UNIDADES_ALIMENTO as $valor => $rotulo): ?><option value="<?= escapar($valor) ?>"><?= escapar($rotulo) ?></option><?php endforeach; ?></select></div>
    <div class="col-md-2 text-end"><label class="form-label d-block">&nbsp;</label><button class="btn btn-outline-danger btn-icone remover-item-alimentar" type="button" title="Remover alimento" aria-label="Remover alimento"><i class="bi bi-trash"></i></button></div>
    <div class="col-12"><label class="form-label">Substituições</label><textarea class="form-control" name="ita_substituicoes[]" rows="2" maxlength="2000" placeholder="Ex.: substituir por batata-doce ou mandioca"></textarea></div>
</div></div></div></template>
