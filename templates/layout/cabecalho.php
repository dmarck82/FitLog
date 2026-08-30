<?php
$nomeAplicacao = (string) config('APP_NOME', 'Diário Fitness');
$tituloCompleto = $titulo ? $titulo . ' · ' . $nomeAplicacao : $nomeAplicacao;
$flashes = obter_flashes();
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light">
    <title><?= escapar($tituloCompleto) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0/dist/css/select2.min.css" rel="stylesheet">
    <link href="<?= escapar(url('/assets/css/app.css')) ?>" rel="stylesheet">
</head>
<body>
<?php if (usuario_autenticado()): ?>
    <nav class="navbar navbar-expand-lg bg-white border-bottom mb-4">
        <div class="container">
            <a class="navbar-brand text-success" href="<?= escapar(url('/')) ?>"><?= escapar($nomeAplicacao) ?></a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#menuPrincipal" aria-controls="menuPrincipal" aria-expanded="false" aria-label="Abrir menu">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="menuPrincipal">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item"><a class="nav-link" href="<?= escapar(url('/')) ?>">Painel</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= escapar(url('/pesos')) ?>">Peso</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= escapar(url('/medidas')) ?>">Medidas</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= escapar(url('/alimentacao')) ?>">Alimentação</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= escapar(url('/treinos')) ?>">Treinos</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= escapar(url('/medicamentos')) ?>">Medicamentos</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= escapar(url('/suplementos')) ?>">Suplementos</a></li>
                </ul>
                <a class="nav-link me-3" href="<?= escapar(url("/perfil")) ?>" title="Meu perfil">
                    <i class="bi bi-person-circle me-1" aria-hidden="true"></i><?= escapar(usuario_atual()["nome"]) ?>
                </a>
                <form action="<?= escapar(url('/sair')) ?>" method="post">
                    <?= campo_csrf() ?>
                    <button class="btn btn-outline-secondary btn-sm" type="submit">Sair</button>
                </form>
            </div>
        </div>
    </nav>
<?php endif; ?>

<main class="container pb-5 <?= usuario_autenticado() ? '' : 'py-5' ?>">
    <?php foreach ($flashes as $flash): ?>
        <?php
        $classe = match ($flash['tipo']) {
            'sucesso' => 'success',
            'erro' => 'danger',
            'aviso' => 'warning',
            default => 'info',
        };
        ?>
        <div class="alert alert-<?= escapar($classe) ?> alert-dismissible fade show" role="alert">
            <?= escapar($flash['mensagem']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
        </div>
    <?php endforeach; ?>
