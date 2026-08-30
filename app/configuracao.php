<?php

declare(strict_types=1);

function carregar_ambiente(string $arquivo): void
{
    if (!is_file($arquivo)) {
        return;
    }

    $linhas = file($arquivo, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    if ($linhas === false) {
        throw new RuntimeException('Não foi possível ler o arquivo de ambiente.');
    }

    foreach ($linhas as $linha) {
        $linha = trim($linha);

        if ($linha === '' || str_starts_with($linha, '#') || !str_contains($linha, '=')) {
            continue;
        }

        [$chave, $valor] = array_map('trim', explode('=', $linha, 2));

        if ($chave === '' || getenv($chave) !== false) {
            continue;
        }

        if (strlen($valor) >= 2) {
            $primeiro = $valor[0];
            $ultimo = $valor[strlen($valor) - 1];

            if (($primeiro === '"' && $ultimo === '"') || ($primeiro === "'" && $ultimo === "'")) {
                $valor = substr($valor, 1, -1);
            }
        }

        putenv($chave . '=' . $valor);
        $_ENV[$chave] = $valor;
    }
}

function config(string $chave, mixed $padrao = null): mixed
{
    $valor = getenv($chave);

    return $valor === false ? $padrao : $valor;
}

