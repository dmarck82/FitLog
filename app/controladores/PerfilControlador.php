<?php

declare(strict_types=1);

function exibir_perfil(): void
{
    exigir_autenticacao();

    renderizar('perfil/index', [
        'usuario' => usuario_atual(),
        'objetivos' => OBJETIVOS_USUARIO,
    ], 'Meu perfil');
}

function salvar_perfil(): void
{
    exigir_autenticacao();
    somente_post();
    validar_csrf();

    $nome = trim((string) ($_POST['usu_nome'] ?? ''));
    $email = mb_strtolower(trim((string) ($_POST['usu_email'] ?? '')));
    $objetivo = trim((string) ($_POST['usu_objetivo'] ?? ''));

    if (mb_strlen($nome) < 2 || mb_strlen($nome) > 120 || !filter_var($email, FILTER_VALIDATE_EMAIL) || !objetivo_valido($objetivo)) {
        flash('erro', 'Preencha nome, e-mail e objetivo corretamente.');
        redirecionar('/perfil');
    }

    if ($email !== usuario_atual()['email']) {
        $senhaAtual = (string) ($_POST['senha_atual_email'] ?? '');
        $consulta = banco()->prepare('SELECT usu_senha_hash FROM usu_usuario WHERE usu_id = :id LIMIT 1');
        $consulta->execute(['id' => usuario_atual()['id']]);
        $conta = $consulta->fetch();
        if (!$conta || !password_verify($senhaAtual, $conta['usu_senha_hash'])) {
            flash('erro', 'Informe sua senha atual para alterar o e-mail.');
            redirecionar('/perfil');
        }
    }

    try {
        banco()->prepare('UPDATE usu_usuario SET usu_nome = :nome, usu_email = :email, usu_objetivo = :objetivo WHERE usu_id = :usuario')
            ->execute([
                'nome' => $nome,
                'email' => $email,
                'objetivo' => $objetivo,
                'usuario' => usuario_atual()['id'],
            ]);
    } catch (PDOException $erro) {
        if ($erro->getCode() === '23000') {
            flash('erro', 'Este e-mail já está vinculado a outra conta.');
            redirecionar('/perfil');
        }
        throw $erro;
    }

    $_SESSION['usuario']['nome'] = $nome;
    $_SESSION['usuario']['email'] = $email;
    $_SESSION['usuario']['objetivo'] = $objetivo;
    flash('sucesso', 'Perfil atualizado com sucesso.');
    redirecionar('/perfil');
}

function alterar_senha_perfil(): void
{
    exigir_autenticacao();
    somente_post();
    validar_csrf();

    $senhaAtual = (string) ($_POST['senha_atual'] ?? '');
    $novaSenha = (string) ($_POST['nova_senha'] ?? '');
    $confirmacao = (string) ($_POST['confirmacao_senha'] ?? '');
    $consulta = banco()->prepare('SELECT usu_senha_hash FROM usu_usuario WHERE usu_id = :id LIMIT 1');
    $consulta->execute(['id' => usuario_atual()['id']]);
    $usuario = $consulta->fetch();

    if (!$usuario || !password_verify($senhaAtual, $usuario['usu_senha_hash']) || !senha_valida($novaSenha) || $novaSenha !== $confirmacao) {
        flash('erro', 'Confira a senha atual e informe uma nova senha de ao menos 8 caracteres, confirmada corretamente.');
        redirecionar('/perfil');
    }

    banco()->prepare('UPDATE usu_usuario SET usu_senha_hash = :senha WHERE usu_id = :id')
        ->execute(['senha' => password_hash($novaSenha, PASSWORD_DEFAULT), 'id' => usuario_atual()['id']]);
    flash('sucesso', 'Senha alterada com sucesso.');
    redirecionar('/perfil');
}
