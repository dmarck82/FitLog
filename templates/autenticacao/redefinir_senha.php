<div class="row justify-content-center">
    <div class="col-sm-10 col-md-7 col-lg-5 col-xl-4">
        <div class="card">
            <div class="card-body p-4">
                <h1 class="h4 mb-2">Redefinir senha</h1>
                <p class="text-secondary mb-4">Escolha uma nova senha para sua conta.</p>
                <form action="<?= escapar(url('/redefinir-senha')) ?>" method="post"><?= campo_csrf() ?><input type="hidden" name="token" value="<?= escapar($token) ?>">
                    <div class="mb-3"><label class="form-label campo-obrigatorio" for="senha">Nova senha</label><input class="form-control" id="senha" name="senha" type="password" autocomplete="new-password" minlength="8" required autofocus></div>
                    <div class="mb-4"><label class="form-label campo-obrigatorio" for="confirmacao_senha">Confirmar nova senha</label><input class="form-control" id="confirmacao_senha" name="confirmacao_senha" type="password" autocomplete="new-password" minlength="8" required></div><button class="btn btn-success w-100" type="submit">Redefinir senha</button>
                </form>
            </div>
        </div>
    </div>
</div>