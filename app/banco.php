<?php

declare(strict_types=1);

function banco(): PDO
{
    static $conexao = null;

    if ($conexao instanceof PDO) {
        return $conexao;
    }

    $host = (string) config('DB_HOST', '127.0.0.1');
    $porta = (string) config('DB_PORTA', '3306');
    $nome = (string) config('DB_BANCO', 'diario_fitness');
    $usuario = (string) config('DB_USUARIO', 'root');
    $senha = (string) config('DB_SENHA', '');
    $dsn = "mysql:host={$host};port={$porta};dbname={$nome};charset=utf8mb4";

    $conexao = new PDO($dsn, $usuario, $senha, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    return $conexao;
}

