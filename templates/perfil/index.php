<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <h1 class="h3 mb-1">Meu perfil</h1>
        <p class="text-secondary mb-0">Mantenha os dados da sua conta atualizados.</p>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-4">
        <div class="card h-100"><div class="card-body p-4"><div class="icone-resumo mb-3"><i class="bi bi-person"></i></div><h2 class="h5 mb-1"><?= escapar($usuario['nome']) ?></h2><p class="text-secondary mb-0"><?= escapar($usuario['email']) ?></p></div></div>
    </div>
    <div class="col-lg-8">
        <form class="card" action="<?= escapar(url('/perfil/salvar')) ?>" method="post" id="formulario-perfil" data-email-original="<?= escapar($usuario['email']) ?>">
            <?= campo_csrf() ?>
            <div class="card-body p-4">
                <h2 class="h5 mb-1">Dados pessoais</h2><p class="text-secondary mb-4">Altere seu nome, e-mail ou objetivo quando precisar.</p>
                <div class="row g-3 mb-4">
                    <div class="col-md-6"><label class="form-label campo-obrigatorio" for="usu_nome">Nome</label><input class="form-control" id="usu_nome" name="usu_nome" value="<?= escapar($usuario['nome']) ?>" autocomplete="name" maxlength="120" required></div>
                    <div class="col-md-6"><label class="form-label campo-obrigatorio" for="usu_email">E-mail</label><input class="form-control" id="usu_email" name="usu_email" type="email" value="<?= escapar($usuario['email']) ?>" autocomplete="email" maxlength="190" required></div>
                </div>
                <h3 class="h6 mb-3">Objetivo</h3>
                <div class="row g-3">
                    <?php foreach ($objetivos as $valor => $dados): ?>
                        <div class="col-md-4"><input class="btn-check" type="radio" name="usu_objetivo" id="objetivo_<?= escapar($valor) ?>" value="<?= escapar($valor) ?>" <?= $usuario['objetivo'] === $valor ? 'checked' : '' ?> required><label class="card h-100 p-3 objetivo-opcao" for="objetivo_<?= escapar($valor) ?>"><i class="bi <?= escapar($dados['icone']) ?> fs-3 text-success mb-2"></i><span class="fw-semibold"><?= escapar($dados['rotulo']) ?></span></label></div>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="card-footer bg-white border-0 px-4 pb-4 text-end"><button class="btn btn-success" type="submit">Salvar perfil</button></div>
            <div class="modal fade" id="confirmar-email-modal" tabindex="-1" aria-labelledby="confirmar-email-titulo" aria-hidden="true"><div class="modal-dialog modal-dialog-centered"><div class="modal-content"><div class="modal-header"><h2 class="modal-title fs-5" id="confirmar-email-titulo">Confirmar alteração de e-mail</h2><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button></div><div class="modal-body"><p>Para alterar o e-mail da sua conta, informe sua senha atual.</p><label class="form-label" for="senha_atual_email">Senha atual</label><input class="form-control" id="senha_atual_email" name="senha_atual_email" type="password" autocomplete="current-password"></div><div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button><button type="button" class="btn btn-success" id="confirmar-alteracao-email">Confirmar e salvar</button></div></div></div></div>
        </form>
    </div>
</div>

<div class="row g-4 mt-1"><div class="col-lg-8 offset-lg-4"><form class="card" action="<?= escapar(url('/perfil/senha')) ?>" method="post"><?= campo_csrf() ?><div class="card-body p-4"><h2 class="h5 mb-1">Alterar senha</h2><p class="text-secondary mb-4">Para proteger sua conta, confirme sua senha atual.</p><div class="mb-3"><label class="form-label campo-obrigatorio" for="senha_atual">Senha atual</label><input class="form-control" id="senha_atual" name="senha_atual" type="password" autocomplete="current-password" required></div><div class="mb-3"><label class="form-label campo-obrigatorio" for="nova_senha">Nova senha</label><input class="form-control" id="nova_senha" name="nova_senha" type="password" autocomplete="new-password" minlength="8" required></div><div><label class="form-label campo-obrigatorio" for="confirmacao_senha">Confirmar nova senha</label><input class="form-control" id="confirmacao_senha" name="confirmacao_senha" type="password" autocomplete="new-password" minlength="8" required></div></div><div class="card-footer bg-white border-0 px-4 pb-4 text-end"><button class="btn btn-success" type="submit">Alterar senha</button></div></form></div></div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const formulario = document.getElementById('formulario-perfil');
    const email = document.getElementById('usu_email');
    const senha = document.getElementById('senha_atual_email');
    const modal = new bootstrap.Modal(document.getElementById('confirmar-email-modal'));
    let confirmando = false;

    formulario.addEventListener('submit', (evento) => {
        const emailAlterado = email.value.trim().toLowerCase() !== formulario.dataset.emailOriginal.toLowerCase();
        if (emailAlterado && !confirmando) {
            evento.preventDefault();
            senha.value = '';
            modal.show();
            setTimeout(() => senha.focus(), 200);
        }
    });

    document.getElementById('confirmar-alteracao-email').addEventListener('click', () => {
        if (senha.value === '') {
            senha.focus();
            return;
        }
        confirmando = true;
        modal.hide();
        formulario.requestSubmit();
    });
});
</script>
