<?php

declare(strict_types=1);

require dirname(__DIR__) . '/app/inicializar.php';
require RAIZ_PROJETO . '/app/controladores/AutenticacaoControlador.php';
require RAIZ_PROJETO . '/app/controladores/PainelControlador.php';
require RAIZ_PROJETO . '/app/controladores/MedicaoControlador.php';
require RAIZ_PROJETO . '/app/controladores/PesoControlador.php';
require RAIZ_PROJETO . '/app/controladores/PerfilControlador.php';
require RAIZ_PROJETO . '/app/controladores/MedicamentoControlador.php';
require RAIZ_PROJETO . '/app/controladores/SuplementoControlador.php';
require RAIZ_PROJETO . '/app/controladores/AlimentacaoControlador.php';
require RAIZ_PROJETO . '/app/controladores/TreinoControlador.php';

$metodo = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$rota = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$rota = '/' . trim($rota, '/');
$rota = $rota === '/' ? '/' : rtrim($rota, '/');

$rotas = [
    'GET' => [
        '/' => 'exibir_painel',
        '/login' => 'exibir_login',
        '/pesos' => 'listar_pesos',
        '/pesos/formulario' => 'formulario_peso',
        '/medidas' => 'listar_medicoes',
        '/medidas/formulario' => 'formulario_medicao',
        '/perfil' => 'exibir_perfil',
        '/medicamentos' => 'listar_medicamentos',
        '/medicamentos/formulario' => 'formulario_medicamento',
        '/medicamentos/compras' => 'listar_compras_medicamento',
        '/medicamentos/compras/formulario' => 'formulario_compra_medicamento',
        '/medicamentos/aplicacoes' => 'listar_aplicacoes_medicamento',
        '/medicamentos/aplicacoes/feedback' => 'obter_feedback_ultima_aplicacao',
        '/medicamentos/aplicacoes/formulario' => 'formulario_aplicacao_medicamento',
        '/suplementos' => 'listar_suplementos',
        '/suplementos/formulario' => 'formulario_suplemento',
        '/suplementos/compras' => 'listar_compras_suplemento',
        '/suplementos/compras/formulario' => 'formulario_compra_suplemento',
        '/suplementos/consumos' => 'listar_consumos_suplemento',
        '/suplementos/consumos/feedback' => 'obter_feedback_ultimo_consumo',
        '/suplementos/consumos/formulario' => 'formulario_consumo_suplemento',
        '/alimentacao' => 'exibir_alimentacao_hoje',
        '/alimentacao/planos' => 'listar_planos_alimentares',
        '/alimentacao/planos/formulario' => 'formulario_plano_alimentar',
        '/alimentacao/planos/detalhes' => 'detalhar_plano_alimentar',
        '/alimentacao/refeicoes/formulario' => 'formulario_refeicao_plano',
        '/alimentacao/historico' => 'listar_historico_alimentar',
        '/alimentacao/registros/formulario' => 'formulario_registro_alimentar',
        '/treinos' => 'exibir_treinos',
        '/treinos/exercicios' => 'listar_exercicios',
        '/treinos/exercicios/formulario' => 'formulario_exercicio',
        '/treinos/planos' => 'listar_planos_treino',
        '/treinos/planos/formulario' => 'formulario_plano_treino',
        '/treinos/planos/detalhes' => 'detalhar_plano_treino',
        '/treinos/planejados/formulario' => 'formulario_treino_planejado',
        '/treinos/realizados/formulario' => 'formulario_treino_realizado',
        '/treinos/realizados/detalhes' => 'detalhar_treino_realizado',
        '/treinos/historico' => 'listar_historico_treinos',
    ],
    'POST' => [
        '/login' => 'processar_login',
        '/sair' => 'processar_saida',
        '/pesos/salvar' => 'salvar_peso',
        '/pesos/excluir' => 'excluir_peso',
        '/medidas/salvar' => 'salvar_medicao',
        '/medidas/excluir' => 'excluir_medicao',
        '/perfil/salvar' => 'salvar_perfil',
        '/medicamentos/salvar' => 'salvar_medicamento',
        '/medicamentos/compras/salvar' => 'salvar_compra_medicamento',
        '/medicamentos/compras/excluir' => 'excluir_compra_medicamento',
        '/medicamentos/aplicacoes/salvar' => 'salvar_aplicacao_medicamento',
        '/medicamentos/aplicacoes/excluir' => 'excluir_aplicacao_medicamento',
        '/suplementos/salvar' => 'salvar_suplemento',
        '/suplementos/compras/salvar' => 'salvar_compra_suplemento',
        '/suplementos/compras/excluir' => 'excluir_compra_suplemento',
        '/suplementos/consumos/salvar' => 'salvar_consumo_suplemento',
        '/suplementos/consumos/excluir' => 'excluir_consumo_suplemento',
        '/alimentacao/planos/salvar' => 'salvar_plano_alimentar',
        '/alimentacao/refeicoes/salvar' => 'salvar_refeicao_plano',
        '/alimentacao/refeicoes/excluir' => 'excluir_refeicao_plano',
        '/alimentacao/registros/salvar' => 'salvar_registro_alimentar',
        '/alimentacao/registros/excluir' => 'excluir_registro_alimentar',
        '/treinos/exercicios/salvar' => 'salvar_exercicio',
        '/treinos/planos/salvar' => 'salvar_plano_treino',
        '/treinos/planejados/salvar' => 'salvar_treino_planejado',
        '/treinos/planejados/excluir' => 'excluir_treino_planejado',
        '/treinos/realizados/salvar' => 'salvar_treino_realizado',
        '/treinos/realizados/excluir' => 'excluir_treino_realizado',
    ],
];

$acao = $rotas[$metodo][$rota] ?? null;

if (!$acao || !is_callable($acao)) {
    http_response_code(404);
    renderizar('erros/404', [], 'Página não encontrada');
    exit;
}

$acao();
