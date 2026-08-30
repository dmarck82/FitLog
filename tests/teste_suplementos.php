<?php

declare(strict_types=1);

require dirname(__DIR__) . '/app/inicializar.php';
require RAIZ_PROJETO . '/app/controladores/SuplementoControlador.php';

$conexao = banco();
$usuario = $conexao->query(
    'SELECT usu_id, usu_nome, usu_email, usu_objetivo FROM usu_usuario ORDER BY usu_id LIMIT 1'
)->fetch();

if (!$usuario) {
    fwrite(STDERR, "Nenhum usuário disponível para o teste de suplementos.\n");
    exit(1);
}

$_SESSION['usuario'] = [
    'id' => (int) $usuario['usu_id'],
    'nome' => $usuario['usu_nome'],
    'email' => $usuario['usu_email'],
    'objetivo' => $usuario['usu_objetivo'],
];

$nome = '__TESTE_SUPLEMENTO_' . bin2hex(random_bytes(4));
$suplementoId = 0;

try {
    $inserirSuplemento = $conexao->prepare(
        'INSERT INTO sup_suplemento
            (usu_id, sup_nome, sup_marca, sup_apresentacao, sup_ativo, sup_solicitar_feedback)
         VALUES (:usuario, :nome, "Marca teste", "300 g", 1, 1)'
    );
    $inserirSuplemento->execute(['usuario' => $usuario['usu_id'], 'nome' => $nome]);
    $suplementoId = (int) $conexao->lastInsertId();

    $conexao->prepare(
        'INSERT INTO cps_compra_suplemento
            (sup_id, cps_data_compra, cps_quantidade, cps_valor)
         VALUES (:suplemento, CURDATE(), 1, 99.90)'
    )->execute(['suplemento' => $suplementoId]);

    $conexao->prepare(
        'INSERT INTO cos_consumo_suplemento
            (sup_id, cos_data_consumo, cos_dose, cos_unidade, cos_reacoes, cos_observacoes)
         VALUES (:suplemento, NOW(), 5, "g", "Boa disposição", "Sem desconforto")'
    )->execute(['suplemento' => $suplementoId]);

    if (!buscar_suplemento($suplementoId) || !buscar_ultimo_consumo_suplemento($suplementoId)) {
        throw new RuntimeException('Falha ao consultar o suplemento ou seu consumo.');
    }

    $_GET['suplemento'] = $suplementoId;
    ob_start();
    obter_feedback_ultimo_consumo();
    $resposta = json_decode((string) ob_get_clean(), true, 512, JSON_THROW_ON_ERROR);

    if (empty($resposta['solicitar']) || ($resposta['consumo']['feedback'] ?? '') !== 'Sem desconforto') {
        throw new RuntimeException('Falha ao recuperar o feedback do consumo anterior.');
    }

    ob_start();
    listar_suplementos();
    $html = (string) ob_get_clean();

    if (!str_contains($html, $nome)) {
        throw new RuntimeException('O suplemento não foi encontrado na listagem renderizada.');
    }

    echo "Fluxo funcional de suplementos passou.\n";
} finally {
    if ($suplementoId > 0) {
        $conexao->prepare('DELETE FROM cos_consumo_suplemento WHERE sup_id = :suplemento')->execute(['suplemento' => $suplementoId]);
        $conexao->prepare('DELETE FROM cps_compra_suplemento WHERE sup_id = :suplemento')->execute(['suplemento' => $suplementoId]);
        $conexao->prepare('DELETE FROM sup_suplemento WHERE sup_id = :suplemento')->execute(['suplemento' => $suplementoId]);
    }
}
