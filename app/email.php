<?php

declare(strict_types=1);

function enviar_email(string $destinatario, string $nomeDestinatario, string $assunto, string $texto): void
{
    $host = (string) config('MAIL_HOST', '');
    $porta = (int) config('MAIL_PORT', '587');
    $usuario = (string) config('MAIL_USERNAME', '');
    $senha = (string) config('MAIL_PASSWORD', '');
    $remetente = (string) config('MAIL_FROM_ADDRESS', '');
    $nomeRemetente = (string) config('MAIL_FROM_NAME', config('APP_NOME', 'Diário Fitness'));

    if ($host === '' || $usuario === '' || $senha === '' || $remetente === '') {
        throw new RuntimeException('O envio de e-mail não está configurado.');
    }

    $conexao = @stream_socket_client("tcp://{$host}:{$porta}", $codigo, $mensagem, 15);
    if ($conexao === false) {
        throw new RuntimeException('Não foi possível conectar ao serviço de e-mail.');
    }

    stream_set_timeout($conexao, 15);

    try {
        smtp_esperar($conexao, [220]);
        smtp_enviar($conexao, 'EHLO localhost', [250]);
        smtp_enviar($conexao, 'STARTTLS', [220]);
        if (!stream_socket_enable_crypto($conexao, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
            throw new RuntimeException('Não foi possível estabelecer conexão segura com o serviço de e-mail.');
        }
        smtp_enviar($conexao, 'EHLO localhost', [250]);
        smtp_enviar($conexao, 'AUTH LOGIN', [334]);
        smtp_enviar($conexao, base64_encode($usuario), [334]);
        smtp_enviar($conexao, base64_encode($senha), [235]);
        smtp_enviar($conexao, "MAIL FROM:<{$remetente}>", [250]);
        smtp_enviar($conexao, "RCPT TO:<{$destinatario}>", [250, 251]);
        smtp_enviar($conexao, 'DATA', [354]);

        $cabecalhos = [
            'From: ' . smtp_endereco($nomeRemetente, $remetente),
            'To: ' . smtp_endereco($nomeDestinatario, $destinatario),
            'Subject: =?UTF-8?B?' . base64_encode($assunto) . '?=',
            'MIME-Version: 1.0',
            'Content-Type: text/plain; charset=UTF-8',
            'Content-Transfer-Encoding: base64',
        ];
        $corpo = rtrim(chunk_split(base64_encode($texto), 76, "\r\n"));
        smtp_enviar_dados($conexao, implode("\r\n", $cabecalhos) . "\r\n\r\n" . $corpo);
        fwrite($conexao, "QUIT\r\n");
    } finally {
        fclose($conexao);
    }
}

function smtp_enviar($conexao, string $comando, array $codigosEsperados): void
{
    if (fwrite($conexao, $comando . "\r\n") === false) {
        throw new RuntimeException('Não foi possível enviar o e-mail.');
    }
    smtp_esperar($conexao, $codigosEsperados);
}

function smtp_esperar($conexao, array $codigosEsperados): void
{
    do {
        $linha = fgets($conexao, 512);
        if ($linha === false || !preg_match('/^(\d{3})([ -])/', $linha, $partes)) {
            throw new RuntimeException('Resposta inválida do serviço de e-mail.');
        }
        $codigo = (int) $partes[1];
    } while ($partes[2] === '-');

    if (!in_array($codigo, $codigosEsperados, true)) {
        throw new RuntimeException('O serviço de e-mail recusou o envio.');
    }
}

function smtp_endereco(string $nome, string $email): string
{
    return '=?UTF-8?B?' . base64_encode($nome) . '?= <' . $email . '>';
}

function smtp_enviar_dados($conexao, string $dados): void
{
    if (fwrite($conexao, $dados . "\r\n.\r\n") === false) {
        throw new RuntimeException('Não foi possível enviar o conteúdo do e-mail.');
    }
    smtp_esperar($conexao, [250]);
}
