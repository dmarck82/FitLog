<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    exit("Este script deve ser executado no terminal.\n");
}

require dirname(__DIR__) . '/app/inicializar.php';

$nome = trim((string) ($argv[1] ?? ''));
$email = mb_strtolower(trim((string) ($argv[2] ?? '')));

if ($nome === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    fwrite(STDERR, "Uso: php scripts/criar_usuario.php \"Nome\" \"email@exemplo.com\"\n");
    exit(1);
}

echo 'Senha: ';
if (DIRECTORY_SEPARATOR === '/' && function_exists('shell_exec')) {
    shell_exec('stty -echo');
}
$senha = trim((string) fgets(STDIN));
if (DIRECTORY_SEPARATOR === '/' && function_exists('shell_exec')) {
    shell_exec('stty echo');
}
echo "\n";

if (strlen($senha) < 8) {
    fwrite(STDERR, "A senha deve ter pelo menos 8 caracteres.\n");
    exit(1);
}

try {
    $consulta = banco()->prepare(
        'INSERT INTO usu_usuario (usu_nome, usu_email, usu_senha_hash)
         VALUES (:nome, :email, :senha)'
    );
    $consulta->execute([
        'nome' => $nome,
        'email' => $email,
        'senha' => password_hash($senha, PASSWORD_DEFAULT),
    ]);
    echo "Usuário criado com sucesso.\n";
} catch (PDOException $erro) {
    if ((int) $erro->getCode() === 23000) {
        fwrite(STDERR, "Já existe um usuário com esse e-mail.\n");
    } else {
        fwrite(STDERR, "Erro ao criar usuário: {$erro->getMessage()}\n");
    }
    exit(1);
}

