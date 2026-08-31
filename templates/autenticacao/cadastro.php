<div class="row justify-content-center">
    <div class="col-sm-10 col-md-7 col-lg-5 col-xl-4">
        <div class="text-center mb-4">
            <div class="display-6 fw-bold text-success">Diário Fitness</div>
            <p class="text-secondary mt-2">Crie sua conta para começar.</p>
        </div>
        <div class="card">
            <div class="card-body p-4">
                <h1 class="h4 mb-4">Criar conta</h1>
                <form action="<?= escapar(url('/cadastro')) ?>" method="post"><?= campo_csrf() ?><div class="mb-3"><label class="form-label campo-obrigatorio" for="nome">Nome</label><input class="form-control" id="nome" name="nome" value="<?= escapar($nome) ?>" autocomplete="name" required autofocus></div>
                    <div class="mb-3"><label class="form-label campo-obrigatorio" for="email">E-mail</label><input class="form-control" id="email" name="email" type="email" value="<?= escapar($email) ?>" autocomplete="email" required></div>
                    <div class="mb-3"><label class="form-label campo-obrigatorio" for="senha">Senha</label><input class="form-control" id="senha" name="senha" type="password" autocomplete="new-password" minlength="8" required>
                        <div class="form-text">Use ao menos 8 caracteres.</div>
                    </div>
                    <div class="mb-4"><label class="form-label campo-obrigatorio" for="confirmacao_senha">Confirmar senha</label><input class="form-control" id="confirmacao_senha" name="confirmacao_senha" type="password" autocomplete="new-password" minlength="8" required></div><button class="btn btn-success w-100" type="submit">Criar conta</button>
                </form>
                <p class="text-center mt-3 mb-0">Já tem conta? <a href="<?= escapar(url('/login')) ?>">Entrar</a></p>
            </div>
        </div>
    </div>
</div>