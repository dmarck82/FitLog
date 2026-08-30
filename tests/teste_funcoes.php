<?php

declare(strict_types=1);

require dirname(__DIR__) . '/app/configuracao.php';
require dirname(__DIR__) . '/app/funcoes.php';

$testes = [
    ['entrada' => '82,50', 'esperado' => 82.5],
    ['entrada' => '1.234,56', 'esperado' => 1234.56],
    ['entrada' => '82.50', 'esperado' => 82.5],
    ['entrada' => '', 'esperado' => null],
    ['entrada' => 'abc', 'esperado' => null],
];

foreach ($testes as $indice => $teste) {
    $obtido = normalizar_decimal($teste['entrada']);

    if ($obtido !== $teste['esperado']) {
        fwrite(STDERR, 'Falha no teste ' . ($indice + 1) . "\n");
        exit(1);
    }
}

$formatacoes = [
    [formatar_decimal(1.2), "1,20"],
    [formatar_decimal(1.235), "1,24"],
    [formatar_decimal_input("1,2"), "1.20"],
    [formatar_decimal_input("1.235"), "1.24"],
];

foreach ($formatacoes as $indice => [$obtido, $esperado]) {
    if ($obtido !== $esperado) {
        fwrite(STDERR, "Falha na formatação decimal " . ($indice + 1) . "\n");
        exit(1);
    }
}

echo "Todos os testes de funções passaram.\n";
