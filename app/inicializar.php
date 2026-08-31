<?php

declare(strict_types=1);

define('RAIZ_PROJETO', dirname(__DIR__));

require_once RAIZ_PROJETO . '/app/configuracao.php';
carregar_ambiente(RAIZ_PROJETO . '/.env');

date_default_timezone_set((string) config('APP_FUSO_HORARIO', 'America/Sao_Paulo'));

if ((string) config('APP_AMBIENTE', 'producao') === 'desenvolvimento') {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
}

if (PHP_SAPI !== 'cli' && session_status() !== PHP_SESSION_ACTIVE) {
    $seguro = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    session_name('diario_fitness');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => $seguro,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

require_once RAIZ_PROJETO . '/app/banco.php';
require_once RAIZ_PROJETO . '/app/funcoes.php';
require_once RAIZ_PROJETO . '/app/email.php';
require_once RAIZ_PROJETO . '/app/autenticacao.php';
require_once RAIZ_PROJETO . '/app/objetivos.php';
require_once RAIZ_PROJETO . '/app/treinos.php';

