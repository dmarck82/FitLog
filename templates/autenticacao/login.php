<div class="row justify-content-center">
    <div class="col-sm-10 col-md-7 col-lg-5 col-xl-4">
        <div class="text-center mb-4">
            <div class="display-6 fw-bold text-success">Diário Fitness</div>
            <p class="text-secondary mt-2">Seu acompanhamento pessoal em um só lugar.</p>
        </div>

        <div class="card">
            <div class="card-body p-4">
                <h1 class="h4 mb-4">Entrar</h1>
                <form action="<?= escapar(url('/login')) ?>" method="post">
                    <?= campo_csrf() ?>
                    <div class="mb-3">
                        <label class="form-label campo-obrigatorio" for="email">E-mail</label>
                        <input class="form-control" id="email" name="email" type="email" value="<?= escapar($email) ?>" autocomplete="username" required autofocus>
                    </div>
                    <div class="mb-4">
                        <label class="form-label campo-obrigatorio" for="senha">Senha</label>
                        <input class="form-control" id="senha" name="senha" type="password" autocomplete="current-password" required>
                    </div>
                    <button class="btn btn-success w-100" type="submit">Entrar</button>
                    <div class="d-flex justify-content-between mt-3 small"><a href="<?= escapar(url('/cadastro')) ?>">Criar conta</a><a href="<?= escapar(url('/esqueci-senha')) ?>">Esqueci minha senha</a></div>
                </form>
            </div>
        </div>
    </div>
</div>