<?php
$editando = !empty($registro['ral_id']);
$situacaoAtual = $registro['ral_situacao'] ?? 'conforme';
if (!$itensRealizados) {
    $itensRealizados = [['alimento' => '', 'quantidade_informada' => '', 'unidade' => '', 'observacoes' => '']];
}
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div><h1 class="h3 mb-1"><?= $editando ? 'Editar refeição realizada' : 'Registrar refeição realizada' ?></h1><p class="text-secondary mb-0"><?= escapar($planejado['nome'] ?? $registro['ral_refeicao_nome'] ?? 'Refeição') ?></p></div>
    <a class="btn btn-outline-secondary" href="<?= escapar(url('/alimentacao?data=' . ($registro['ral_data'] ?? date('Y-m-d')))) ?>">Voltar</a>
</div>

<div class="row g-4">
    <div class="col-lg-5"><div class="card h-100">
        <div class="card-header bg-white py-3"><h2 class="h5 mb-0"><i class="bi bi-journal-check me-2 text-success"></i>Planejado</h2></div>
        <ul class="list-group list-group-flush">
            <?php foreach (($planejado['itens'] ?? []) as $item): ?><li class="list-group-item py-3">
                <div class="d-flex justify-content-between gap-2"><span class="fw-semibold"><?= escapar($item['alimento']) ?></span><?php if ($item['quantidade'] !== null): ?><span class="text-nowrap"><?= formatar_decimal($item['quantidade']) ?> <?= escapar(UNIDADES_ALIMENTO[$item['unidade']] ?? ($item['unidade'] ?: '')) ?></span><?php endif; ?></div>
                <?php if (!empty($item['substituicoes'])): ?><div class="small text-secondary mt-1"><i class="bi bi-arrow-left-right me-1"></i><?= escapar($item['substituicoes']) ?></div><?php endif; ?>
            </li><?php endforeach; ?>
        </ul>
        <?php if (!empty($planejado['observacoes'])): ?><div class="card-footer bg-light small"><?= nl2br(escapar($planejado['observacoes'])) ?></div><?php endif; ?>
    </div></div>

    <div class="col-lg-7"><form action="<?= escapar(url('/alimentacao/registros/salvar')) ?>" method="post" id="form-registro-alimentar">
        <?= campo_csrf() ?>
        <?php if ($editando): ?><input type="hidden" name="ral_id" value="<?= (int) $registro['ral_id'] ?>"><?php else: ?><input type="hidden" name="ref_id" value="<?= (int) ($registro['ref_id'] ?? 0) ?>"><?php endif; ?>
        <div class="card mb-4"><div class="card-body p-4"><div class="row g-3">
            <div class="col-md-6"><label class="form-label campo-obrigatorio" for="ral_data">Data</label><input class="form-control" type="date" id="ral_data" name="ral_data" max="<?= escapar(date('Y-m-d')) ?>" value="<?= escapar($registro['ral_data'] ?? '') ?>" required></div>
            <div class="col-md-6"><label class="form-label" for="ral_horario">Horário realizado</label><input class="form-control" type="time" id="ral_horario" name="ral_horario" value="<?= escapar($registro['ral_horario'] ?? '') ?>"></div>
            <div class="col-12"><label class="form-label campo-obrigatorio" for="ral_situacao">Como foi a refeição?</label><select class="form-select" id="ral_situacao" name="ral_situacao" required><?php foreach (SITUACOES_REGISTRO_ALIMENTAR as $valor => $dados): ?><option value="<?= escapar($valor) ?>" <?= $situacaoAtual === $valor ? 'selected' : '' ?>><?= escapar($dados['rotulo']) ?></option><?php endforeach; ?></select></div>
            <div class="col-md-6"><label class="form-label" for="ral_fome_antes">Fome antes <span class="text-secondary">(0 a 10)</span></label><select class="form-select" id="ral_fome_antes" name="ral_fome_antes"><option value="">Não informar</option><?php for ($nivel = 0; $nivel <= 10; $nivel++): ?><option value="<?= $nivel ?>" <?= (string) ($registro['ral_fome_antes'] ?? '') === (string) $nivel ? 'selected' : '' ?>><?= $nivel ?><?= $nivel === 0 ? ' — nenhuma fome' : ($nivel === 10 ? ' — fome extrema' : '') ?></option><?php endfor; ?></select></div>
            <div class="col-md-6"><label class="form-label" for="ral_saciedade_depois">Saciedade depois <span class="text-secondary">(0 a 10)</span></label><select class="form-select" id="ral_saciedade_depois" name="ral_saciedade_depois"><option value="">Não informar</option><?php for ($nivel = 0; $nivel <= 10; $nivel++): ?><option value="<?= $nivel ?>" <?= (string) ($registro['ral_saciedade_depois'] ?? '') === (string) $nivel ? 'selected' : '' ?>><?= $nivel ?><?= $nivel === 0 ? ' — nenhuma saciedade' : ($nivel === 10 ? ' — muito satisfeito' : '') ?></option><?php endfor; ?></select></div>
            <div class="col-12"><label class="form-label" for="ral_observacoes">Observações</label><textarea class="form-control" id="ral_observacoes" name="ral_observacoes" rows="3" maxlength="2000" placeholder="Dificuldades, sintomas, contexto ou motivo da alteração"><?= escapar($registro['ral_observacoes'] ?? '') ?></textarea></div>
        </div></div></div>

        <div id="bloco-itens-realizados">
            <div class="d-flex justify-content-between align-items-center mb-2"><div><h2 class="h5 mb-0">Consumido</h2><span class="small text-secondary">Ajuste os itens que ficaram diferentes do plano.</span></div><button class="btn btn-outline-success" type="button" id="adicionar-item-realizado"><i class="bi bi-plus-lg me-1"></i>Adicionar alimento</button></div>
            <div id="itens-realizados" class="vstack gap-3">
                <?php foreach ($itensRealizados as $item): ?><div class="card item-realizado"><div class="card-body"><div class="row g-3 align-items-start">
                    <div class="col-md-5"><label class="form-label campo-obrigatorio">Alimento</label><input class="form-control" name="ira_alimento[]" maxlength="160" value="<?= escapar($item['ira_alimento'] ?? $item['alimento'] ?? '') ?>" required></div>
                    <div class="col-md-2"><label class="form-label">Quantidade</label><input class="form-control" type="number" step="0.01" min="0.01" name="ira_quantidade[]" value="<?= escapar(formatar_decimal_input($item['ira_quantidade'] ?? $item['quantidade_informada'] ?? '')) ?>"></div>
                    <div class="col-md-3"><label class="form-label">Unidade</label><select class="form-select" name="ira_unidade[]"><option value="">Selecione</option><?php foreach (UNIDADES_ALIMENTO as $valor => $rotulo): ?><option value="<?= escapar($valor) ?>" <?= ($item['ira_unidade'] ?? $item['unidade'] ?? '') === $valor ? 'selected' : '' ?>><?= escapar($rotulo) ?></option><?php endforeach; ?></select></div>
                    <div class="col-md-2 text-end"><label class="form-label d-block">&nbsp;</label><button class="btn btn-outline-danger btn-icone remover-item-realizado" type="button" title="Remover alimento" aria-label="Remover alimento"><i class="bi bi-trash"></i></button></div>
                    <div class="col-12"><label class="form-label">Observação do item</label><input class="form-control" name="ira_observacoes[]" maxlength="500" value="<?= escapar($item['ira_observacoes'] ?? $item['observacoes'] ?? '') ?>" placeholder="Ex.: quantidade aproximada"></div>
                </div></div></div><?php endforeach; ?>
            </div>
        </div>
        <div class="d-flex justify-content-end gap-2 mt-4"><a class="btn btn-outline-secondary" href="<?= escapar(url('/alimentacao?data=' . ($registro['ral_data'] ?? date('Y-m-d')))) ?>">Cancelar</a><button class="btn btn-success" type="submit">Salvar registro</button></div>
    </form>
    <?php if ($editando): ?><form class="text-end mt-2" action="<?= escapar(url('/alimentacao/registros/excluir')) ?>" method="post"><?= campo_csrf() ?><input type="hidden" name="ral_id" value="<?= (int) $registro['ral_id'] ?>"><button class="btn btn-sm btn-outline-danger" type="submit" data-confirmar="Excluir o registro realizado desta refeição?"><i class="bi bi-trash me-1"></i>Excluir registro</button></form><?php endif; ?>
    </div>
</div>

<template id="modelo-item-realizado"><div class="card item-realizado"><div class="card-body"><div class="row g-3 align-items-start">
    <div class="col-md-5"><label class="form-label campo-obrigatorio">Alimento</label><input class="form-control" name="ira_alimento[]" maxlength="160" required></div>
    <div class="col-md-2"><label class="form-label">Quantidade</label><input class="form-control" type="number" step="0.01" min="0.01" name="ira_quantidade[]"></div>
    <div class="col-md-3"><label class="form-label">Unidade</label><select class="form-select" name="ira_unidade[]"><option value="">Selecione</option><?php foreach (UNIDADES_ALIMENTO as $valor => $rotulo): ?><option value="<?= escapar($valor) ?>"><?= escapar($rotulo) ?></option><?php endforeach; ?></select></div>
    <div class="col-md-2 text-end"><label class="form-label d-block">&nbsp;</label><button class="btn btn-outline-danger btn-icone remover-item-realizado" type="button" title="Remover alimento" aria-label="Remover alimento"><i class="bi bi-trash"></i></button></div>
    <div class="col-12"><label class="form-label">Observação do item</label><input class="form-control" name="ira_observacoes[]" maxlength="500" placeholder="Ex.: quantidade aproximada"></div>
</div></div></div></template>
