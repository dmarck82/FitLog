<?php

declare(strict_types=1);

const OBJETIVOS_USUARIO = [
    'ganhar_peso' => [
        'rotulo' => 'Ganhar Peso',
        'icone' => 'bi-arrow-up-circle',
        'descricao' => 'Variações positivas indicam progresso em direção ao objetivo.',
    ],
    'perder_peso' => [
        'rotulo' => 'Perder Peso',
        'icone' => 'bi-arrow-down-circle',
        'descricao' => 'Variações negativas indicam progresso em direção ao objetivo.',
    ],
    'manter_peso' => [
        'rotulo' => 'Manter Peso',
        'icone' => 'bi-dash-circle',
        'descricao' => 'A estabilidade do peso é o resultado esperado.',
    ],
];

function objetivo_valido(string $objetivo): bool
{
    return array_key_exists($objetivo, OBJETIVOS_USUARIO);
}

function objetivo_atual(): string
{
    $objetivo = (string) (usuario_atual()['objetivo'] ?? 'perder_peso');

    return objetivo_valido($objetivo) ? $objetivo : 'perder_peso';
}

function rotulo_objetivo(?string $objetivo = null): string
{
    $objetivo = $objetivo ?? objetivo_atual();

    return OBJETIVOS_USUARIO[$objetivo]['rotulo'] ?? OBJETIVOS_USUARIO['perder_peso']['rotulo'];
}

function descricao_objetivo(?string $objetivo = null): string
{
    $objetivo = $objetivo ?? objetivo_atual();

    return OBJETIVOS_USUARIO[$objetivo]['descricao'] ?? OBJETIVOS_USUARIO['perder_peso']['descricao'];
}

function icone_objetivo(?string $objetivo = null): string
{
    $objetivo = $objetivo ?? objetivo_atual();

    return OBJETIVOS_USUARIO[$objetivo]['icone'] ?? OBJETIVOS_USUARIO['perder_peso']['icone'];
}

function variacao_zero(float $variacao): bool
{
    return abs($variacao) < 0.005;
}

function classe_variacao_peso(?float $variacao, ?string $objetivo = null): string
{
    if ($variacao === null) {
        return '';
    }

    $objetivo = $objetivo ?? objetivo_atual();

    if (variacao_zero($variacao)) {
        return $objetivo === 'manter_peso' ? 'text-success fw-bold' : 'text-body';
    }

    if ($objetivo === 'perder_peso') {
        return $variacao < 0 ? 'text-success fw-bold' : 'text-danger';
    }

    if ($objetivo === 'ganhar_peso') {
        return $variacao > 0 ? 'text-success fw-bold' : 'text-danger';
    }

    return 'text-warning-emphasis fw-semibold';
}

function icone_variacao_peso(?float $variacao): string
{
    if ($variacao === null) {
        return '';
    }

    if (variacao_zero($variacao)) {
        return 'bi-dash';
    }

    return $variacao > 0 ? 'bi-arrow-up' : 'bi-arrow-down';
}

function situacao_variacao_peso(?float $variacao, ?string $objetivo = null): string
{
    if ($variacao === null) {
        return 'sem pesagem anterior';
    }

    $objetivo = $objetivo ?? objetivo_atual();

    if (variacao_zero($variacao)) {
        return $objetivo === 'manter_peso' ? 'alinhado ao objetivo' : 'peso estável';
    }

    $alinhada = ($objetivo === 'perder_peso' && $variacao < 0)
        || ($objetivo === 'ganhar_peso' && $variacao > 0);

    if ($objetivo === 'manter_peso') {
        return 'fora da estabilidade';
    }

    return $alinhada ? 'alinhado ao objetivo' : 'direção contrária ao objetivo';
}

