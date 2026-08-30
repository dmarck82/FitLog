<?php

declare(strict_types=1);

require dirname(__DIR__) . '/app/objetivos.php';

$testes = [
    [classe_variacao_peso(-1.0, 'perder_peso'), 'text-success fw-bold'],
    [classe_variacao_peso(1.0, 'perder_peso'), 'text-danger'],
    [classe_variacao_peso(0.0, 'perder_peso'), 'text-body'],
    [classe_variacao_peso(1.0, 'ganhar_peso'), 'text-success fw-bold'],
    [classe_variacao_peso(-1.0, 'ganhar_peso'), 'text-danger'],
    [classe_variacao_peso(0.0, 'ganhar_peso'), 'text-body'],
    [classe_variacao_peso(0.0, 'manter_peso'), 'text-success fw-bold'],
    [classe_variacao_peso(1.0, 'manter_peso'), 'text-warning-emphasis fw-semibold'],
];

foreach ($testes as $indice => [$obtido, $esperado]) {
    if ($obtido !== $esperado) {
        fwrite(STDERR, 'Falha na regra de objetivo ' . ($indice + 1) . "\n");
        exit(1);
    }
}

echo "Todas as regras de objetivo passaram.\n";

