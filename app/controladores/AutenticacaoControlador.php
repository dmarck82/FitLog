<?php

declare(strict_types=1);

function exibir_login(): void
{
    if (usuario_autenticado()) { redirecionar('/'); }
    renderizar('autenticacao/login', ['email' => ''], 'Entrar');
}

function processar_login(): void
{
    somente_post(); validar_csrf();
    $email = trim((string) ($_POST['email'] ?? ''));
    $senha = (string) ($_POST['senha'] ?? '');
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $senha === '' || !autenticar($email, $senha)) {
        flash('erro', 'E-mail ou senha inválidos.');
        renderizar('autenticacao/login', ['email' => $email], 'Entrar'); return;
    }
    flash('sucesso', 'Bem-vindo ao seu Diário Fitness!'); redirecionar('/');
}

function exibir_cadastro(): void
{
    if (usuario_autenticado()) { redirecionar('/'); }
    renderizar('autenticacao/cadastro', ['nome' => '', 'email' => ''], 'Criar conta');
}

function processar_cadastro(): void
{
    somente_post(); validar_csrf();
    $nome = trim((string) ($_POST['nome'] ?? ''));
    $email = mb_strtolower(trim((string) ($_POST['email'] ?? '')));
    $senha = (string) ($_POST['senha'] ?? '');
    $confirmacao = (string) ($_POST['confirmacao_senha'] ?? '');
    if (mb_strlen($nome) < 2 || mb_strlen($nome) > 120 || !filter_var($email, FILTER_VALIDATE_EMAIL) || !senha_valida($senha) || $senha !== $confirmacao) {
        flash('erro', 'Preencha os dados corretamente. A senha deve ter ao menos 8 caracteres e ser confirmada.');
        renderizar('autenticacao/cadastro', compact('nome', 'email'), 'Criar conta'); return;
    }
    try {
        banco()->prepare('INSERT INTO usu_usuario (usu_nome, usu_email, usu_senha_hash) VALUES (:nome, :email, :senha)')
            ->execute(['nome' => $nome, 'email' => $email, 'senha' => password_hash($senha, PASSWORD_DEFAULT)]);
    } catch (PDOException $erro) {
        if ($erro->getCode() === '23000') {
            flash('erro', 'Este e-mail já possui uma conta.');
            renderizar('autenticacao/cadastro', compact('nome', 'email'), 'Criar conta'); return;
        }
        throw $erro;
    }
    autenticar($email, $senha);
    flash('sucesso', 'Conta criada com sucesso.'); redirecionar('/');
}

function exibir_esqueci_senha(): void
{
    if (usuario_autenticado()) { redirecionar('/'); }
    renderizar('autenticacao/esqueci_senha', ['email' => ''], 'Recuperar senha');
}

function processar_esqueci_senha(): void
{
    somente_post(); validar_csrf();
    $email = mb_strtolower(trim((string) ($_POST['email'] ?? '')));
    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $consulta = banco()->prepare('SELECT usu_id, usu_nome, usu_email FROM usu_usuario WHERE usu_email = :email AND usu_ativo = 1 LIMIT 1');
        $consulta->execute(['email' => $email]);
        $usuario = $consulta->fetch();
        if ($usuario) {
            $token = rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
            $hash = hash('sha256', $token);
            banco()->prepare('DELETE FROM rec_recuperacao_senha WHERE usu_id = :usuario AND rec_utilizado_em IS NULL')->execute(['usuario' => $usuario['usu_id']]);
            banco()->prepare('INSERT INTO rec_recuperacao_senha (usu_id, rec_token_hash, rec_expira_em) VALUES (:usuario, :hash, DATE_ADD(NOW(), INTERVAL 1 HOUR))')
                ->execute(['usuario' => $usuario['usu_id'], 'hash' => $hash]);
            $link = url('/redefinir-senha?token=' . rawurlencode($token));
            try {
                enviar_email((string) $usuario['usu_email'], (string) $usuario['usu_nome'], 'Redefinição de senha', "Olá, {$usuario['usu_nome']},\n\nRecebemos uma solicitação para redefinir a senha da sua conta no " . config('APP_NOME', 'Diário Fitness') . ".\n\nUse este link em até 1 hora:\n{$link}\n\nSe você não solicitou a alteração, ignore este e-mail.");
            } catch (Throwable $erro) {
                error_log('Falha ao enviar recuperação de senha: ' . $erro->getMessage());
            }
        }
    }
    flash('sucesso', 'Se o e-mail estiver cadastrado, você receberá as instruções para redefinir a senha.');
    redirecionar('/login');
}

function exibir_redefinir_senha(): void
{
    if (usuario_autenticado()) { redirecionar('/'); }
    renderizar('autenticacao/redefinir_senha', ['token' => (string) ($_GET['token'] ?? '')], 'Redefinir senha');
}

function processar_redefinir_senha(): void
{
    somente_post(); validar_csrf();
    $token = (string) ($_POST['token'] ?? '');
    $senha = (string) ($_POST['senha'] ?? '');
    if ($token === '' || !senha_valida($senha) || $senha !== (string) ($_POST['confirmacao_senha'] ?? '')) {
        flash('erro', 'Informe uma nova senha de ao menos 8 caracteres e confirme-a.');
        renderizar('autenticacao/redefinir_senha', compact('token'), 'Redefinir senha'); return;
    }
    $pdo = banco(); $pdo->beginTransaction();
    try {
        $consulta = $pdo->prepare('SELECT rec_id, usu_id FROM rec_recuperacao_senha WHERE rec_token_hash = :hash AND rec_utilizado_em IS NULL AND rec_expira_em >= NOW() LIMIT 1 FOR UPDATE');
        $consulta->execute(['hash' => hash('sha256', $token)]); $recuperacao = $consulta->fetch();
        if (!$recuperacao) { $pdo->rollBack(); flash('erro', 'Este link é inválido ou expirou. Solicite uma nova recuperação.'); redirecionar('/esqueci-senha'); }
        $pdo->prepare('UPDATE usu_usuario SET usu_senha_hash = :senha WHERE usu_id = :usuario')->execute(['senha' => password_hash($senha, PASSWORD_DEFAULT), 'usuario' => $recuperacao['usu_id']]);
        $pdo->prepare('UPDATE rec_recuperacao_senha SET rec_utilizado_em = NOW() WHERE rec_id = :id')->execute(['id' => $recuperacao['rec_id']]);
        $pdo->commit();
    } catch (Throwable $erro) { if ($pdo->inTransaction()) { $pdo->rollBack(); } throw $erro; }
    flash('sucesso', 'Senha alterada. Entre com a nova senha.'); redirecionar('/login');
}

function processar_saida(): void { somente_post(); validar_csrf(); sair(); redirecionar('/login'); }
function senha_valida(string $senha): bool { return mb_strlen($senha) >= 8 && mb_strlen($senha) <= 255; }
