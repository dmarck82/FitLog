<?php

declare(strict_types=1);

require dirname(__DIR__) . '/app/inicializar.php';
require RAIZ_PROJETO . '/app/controladores/MedicaoControlador.php';

$conexao = banco();
$usuario = $conexao->query(
    'SELECT usu_id, usu_nome, usu_email, usu_objetivo FROM usu_usuario ORDER BY usu_id LIMIT 1'
)->fetch();

if (!$usuario) {
    fwrite(STDERR, "Nenhum usuário disponível para o teste de medições.\n");
    exit(1);
}

$_SESSION['usuario'] = [
    'id' => (int) $usuario['usu_id'],
    'nome' => $usuario['usu_nome'],
    'email' => $usuario['usu_email'],
    'objetivo' => $usuario['usu_objetivo'],
];

$ids = [];

try {
    $inserir = $conexao->prepare(
        'INSERT INTO mec_medicao_corporal (
            usu_id, mec_data_medicao, mec_cintura_cm, mec_abdomen_cm,
            mec_quadril_cm, mec_torax_cm, mec_braco_direito_cm,
            mec_braco_esquerdo_cm, mec_coxa_direita_cm, mec_coxa_esquerda_cm,
            mec_observacoes
        ) VALUES (
            :usuario, :data, :cintura, :abdomen,
            :quadril, :torax, :braco_direito,
            :braco_esquerdo, :coxa_direita, :coxa_esquerda,
            :observacoes
        )'
    );

    for ($dia = 1; $dia <= 8; $dia++) {
        $inserir->execute([
            'usuario' => $usuario['usu_id'],
            'data' => sprintf('2098-01-%02d', $dia),
            'cintura' => 100 - $dia,
            'abdomen' => 110 - $dia,
            'quadril' => 105 - ($dia / 2),
            'torax' => 95 + ($dia / 2),
            'braco_direito' => 35 + ($dia / 10),
            'braco_esquerdo' => 34 + ($dia / 10),
            'coxa_direita' => 60 + ($dia / 10),
            'coxa_esquerda' => 59 + ($dia / 10),
            'observacoes' => '__TESTE_MEDICAO_TRANSPOSTA__',
        ]);
        $ids[] = (int) $conexao->lastInsertId();
    }

    $_GET = ['data_inicio' => '2098-01-01', 'data_fim' => '2098-01-08'];
    ob_start();
    listar_medicoes();
    $primeiraPagina = (string) ob_get_clean();

    if (!str_contains($primeiraPagina, 'Medição 7')
        || str_contains($primeiraPagina, 'Medição 8')
        || str_contains($primeiraPagina, '01/01/2098')
        || !str_contains($primeiraPagina, '02/01/2098')
        || !str_contains($primeiraPagina, '08/01/2098')
        || !str_contains($primeiraPagina, '-6,00')
        || !str_contains($primeiraPagina, 'Coxa esquerda')
        || !str_contains($primeiraPagina, 'Medições anteriores')) {
        throw new RuntimeException('A primeira página transposta não respeitou o limite ou a comparação.');
    }

    $_GET['pagina'] = 2;
    ob_start();
    listar_medicoes();
    $segundaPagina = (string) ob_get_clean();

    if (!str_contains($segundaPagina, '01/01/2098')
        || str_contains($segundaPagina, '02/01/2098')
        || !str_contains($segundaPagina, 'Mais recentes')) {
        throw new RuntimeException('A navegação para o grupo anterior de medições falhou.');
    }

    $_GET = ['data_inicio' => '2098-01-03', 'data_fim' => '2098-01-05'];
    ob_start();
    listar_medicoes();
    $paginaFiltrada = (string) ob_get_clean();

    if (!str_contains($paginaFiltrada, '03/01/2098')
        || !str_contains($paginaFiltrada, '05/01/2098')
        || str_contains($paginaFiltrada, '02/01/2098')
        || !str_contains($paginaFiltrada, '-2,00')) {
        throw new RuntimeException('O filtro por período ou a comparação filtrada falhou.');
    }

    echo "Fluxo funcional de medições passou.\n";
} finally {
    $excluir = $conexao->prepare('DELETE FROM mec_medicao_corporal WHERE mec_id = :id AND usu_id = :usuario');

    foreach ($ids as $id) {
        $excluir->execute(['id' => $id, 'usuario' => $usuario['usu_id']]);
    }

    $_GET = [];
}
