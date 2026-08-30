<?php

declare(strict_types=1);

function exibir_perfil(): void
{
    exigir_autenticacao();

    renderizar('perfil/index', [
        'usuario' => usuario_atual(),
        'objetivos' => OBJETIVOS_USUARIO,
    ], 'Meu perfil');
}

function salvar_perfil(): void
{
    exigir_autenticacao();
    somente_post();
    validar_csrf();

    $objetivo = trim((string) ($_POST['usu_objetivo'] ?? ''));

    if (!objetivo_valido($objetivo)) {
        flash('erro', 'Selecione um objetivo válido.');
        redirecionar('/perfil');
    }

    $atualizar = banco()->prepare(
        'UPDATE usu_usuario SET usu_objetivo = :objetivo WHERE usu_id = :usuario'
    );
    $atualizar->execute([
        'objetivo' => $objetivo,
        'usuario' => usuario_atual()['id'],
    ]);

    $_SESSION['usuario']['objetivo'] = $objetivo;
    flash('sucesso', 'Objetivo atualizado com sucesso.');
    redirecionar('/perfil');
}

