<?php

declare(strict_types=1);

function usuario_autenticado(): bool
{
    return isset($_SESSION['usuario']) && is_array($_SESSION['usuario']);
}

function usuario_atual(): ?array
{
    return usuario_autenticado() ? $_SESSION['usuario'] : null;
}

function exigir_autenticacao(): void
{
    if (!usuario_autenticado()) {
        flash('aviso', 'Entre para acessar o Diário Fitness.');
        redirecionar('/login');
    }
}

function autenticar(string $email, string $senha): bool
{
    $consulta = banco()->prepare(
        'SELECT usu_id, usu_nome, usu_email, usu_objetivo, usu_senha_hash
         FROM usu_usuario
         WHERE usu_email = :email AND usu_ativo = 1
         LIMIT 1'
    );
    $consulta->execute(['email' => mb_strtolower(trim($email))]);
    $usuario = $consulta->fetch();

    if (!$usuario || !password_verify($senha, $usuario['usu_senha_hash'])) {
        return false;
    }

    session_regenerate_id(true);
    $_SESSION['usuario'] = [
        'id' => (int) $usuario['usu_id'],
        'nome' => $usuario['usu_nome'],
        'email' => $usuario['usu_email'],
        'objetivo' => $usuario['usu_objetivo'],
    ];

    $atualizar = banco()->prepare(
        'UPDATE usu_usuario SET usu_ultimo_acesso_em = NOW() WHERE usu_id = :id'
    );
    $atualizar->execute(['id' => $usuario['usu_id']]);

    return true;
}

function sair(): void
{
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $parametros = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $parametros['path'], $parametros['domain'], $parametros['secure'], $parametros['httponly']);
    }

    session_destroy();
}

