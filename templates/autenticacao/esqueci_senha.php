<div class="row justify-content-center">
    <div class="col-sm-10 col-md-7 col-lg-5 col-xl-4">
        <div class="card">
            <div class="card-body p-4">
                <h1 class="h4 mb-2">Recuperar senha</h1>
                <p class="text-secondary mb-4">Informe seu e-mail para receber um link de redefinição.</p>
                <form action="<?= escapar(url('/esqueci-senha')) ?>" method="post"><?= campo_csrf() ?><div class="mb-4"><label class="form-label campo-obrigatorio" for="email">E-mail</label><input class="form-control" id="email" name="email" type="email" value="<?= escapar($email) ?>" autocomplete="email" required autofocus></div><button class="btn btn-success w-100" type="submit">Enviar instruções</button></form>
                <p class="text-center mt-3 mb-0"><a href="<?= escapar(url('/login')) ?>">Voltar para entrar</a></p>
            </div>
        </div>
    </div>
</div>