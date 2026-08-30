<?php

declare(strict_types=1);

function exibir_login(): void
{
    if (usuario_autenticado()) {
        redirecionar('/');
    }

    renderizar('autenticacao/login', ['email' => ''], 'Entrar');
}

function processar_login(): void
{
    somente_post();
    validar_csrf();

    $email = trim((string) ($_POST['email'] ?? ''));
    $senha = (string) ($_POST['senha'] ?? '');

    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $senha === '') {
        flash('erro', 'Informe um e-mail válido e a senha.');
        renderizar('autenticacao/login', ['email' => $email], 'Entrar');
        return;
    }

    if (!autenticar($email, $senha)) {
        flash('erro', 'E-mail ou senha inválidos.');
        renderizar('autenticacao/login', ['email' => $email], 'Entrar');
        return;
    }

    flash('sucesso', 'Bem-vindo ao seu Diário Fitness!');
    redirecionar('/');
}

function processar_saida(): void
{
    somente_post();
    validar_csrf();
    sair();
    redirecionar('/login');
}

