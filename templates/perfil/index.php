<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <h1 class="h3 mb-1">Meu perfil</h1>
        <p class="text-secondary mb-0">Defina como os resultados devem ser interpretados visualmente.</p>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-body p-4">
                <div class="icone-resumo mb-3"><i class="bi bi-person"></i></div>
                <h2 class="h5 mb-1"><?= escapar($usuario['nome']) ?></h2>
                <p class="text-secondary mb-0"><?= escapar($usuario['email']) ?></p>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <form class="card" action="<?= escapar(url('/perfil/salvar')) ?>" method="post">
            <?= campo_csrf() ?>
            <div class="card-body p-4">
                <h2 class="h5 mb-1">Objetivo</h2>
                <p class="text-secondary mb-4">A direção desejada será destacada em verde nos seus resultados.</p>

                <div class="row g-3">
                    <?php foreach ($objetivos as $valor => $dados): ?>
                        <div class="col-md-4">
                            <input class="btn-check" type="radio" name="usu_objetivo" id="objetivo_<?= escapar($valor) ?>" value="<?= escapar($valor) ?>" <?= objetivo_atual() === $valor ? 'checked' : '' ?> required>
                            <label class="card h-100 p-3 objetivo-opcao" for="objetivo_<?= escapar($valor) ?>">
                                <i class="bi <?= escapar($dados['icone']) ?> fs-3 text-success mb-2"></i>
                                <span class="fw-semibold"><?= escapar($dados['rotulo']) ?></span>
                            </label>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="card-footer bg-white border-0 px-4 pb-4 text-end">
                <button class="btn btn-success" type="submit">Salvar objetivo</button>
            </div>
        </form>
    </div>
</div>

