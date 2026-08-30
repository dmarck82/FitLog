<?php

declare(strict_types=1);

function escapar(mixed $valor): string
{
    return htmlspecialchars((string) $valor, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function url(string $caminho = ''): string
{
    $base = rtrim((string) config('APP_URL', ''), '/');
    $caminho = '/' . ltrim($caminho, '/');

    return $base . ($caminho === '/' ? '/' : $caminho);
}

function redirecionar(string $caminho): never
{
    header('Location: ' . url($caminho));
    exit;
}

function renderizar(string $pagina, array $dados = [], string $titulo = ''): void
{
    $arquivo = RAIZ_PROJETO . '/templates/' . $pagina . '.php';

    if (!is_file($arquivo)) {
        throw new RuntimeException("Template não encontrado: {$pagina}");
    }

    extract($dados, EXTR_SKIP);
    ob_start();
    require $arquivo;
    $conteudo = (string) ob_get_clean();

    require RAIZ_PROJETO . '/templates/layout/cabecalho.php';
    echo $conteudo;
    require RAIZ_PROJETO . '/templates/layout/rodape.php';
}

function flash(string $tipo, string $mensagem): void
{
    $_SESSION['flash'][] = ['tipo' => $tipo, 'mensagem' => $mensagem];
}

function obter_flashes(): array
{
    $mensagens = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);

    return is_array($mensagens) ? $mensagens : [];
}

function token_csrf(): string
{
    if (empty($_SESSION['token_csrf'])) {
        $_SESSION['token_csrf'] = bin2hex(random_bytes(32));
    }

    return (string) $_SESSION['token_csrf'];
}

function campo_csrf(): string
{
    return '<input type="hidden" name="token_csrf" value="' . escapar(token_csrf()) . '">';
}

function validar_csrf(): void
{
    $recebido = $_POST['token_csrf'] ?? '';

    if (!is_string($recebido) || !hash_equals(token_csrf(), $recebido)) {
        http_response_code(419);
        renderizar('erros/419', [], 'Sessão expirada');
        exit;
    }
}

function somente_post(): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
        http_response_code(405);
        header('Allow: POST');
        exit('Método não permitido.');
    }
}

function normalizar_decimal(mixed $valor): ?float
{
    if ($valor === null || trim((string) $valor) === '') {
        return null;
    }

    $texto = str_replace([" ", " "], "", trim((string) $valor));

    if (str_contains($texto, ',') && str_contains($texto, '.')) {
        $texto = str_replace('.', '', $texto);
    }

    $texto = str_replace(',', '.', $texto);

    return is_numeric($texto) ? (float) $texto : null;
}

function formatar_decimal(mixed $valor): string
{
    if ($valor === null || $valor === '') {
        return '—';
    }

    return number_format((float) $valor, 2, ',', '.');
}

function formatar_decimal_input(mixed $valor): string
{
    if ($valor === null || trim((string) $valor) === "") {
        return "";
    }

    $normalizado = normalizar_decimal($valor);

    if ($normalizado === null) {
        return (string) $valor;
    }

    return number_format($normalizado, 2, ".", "");
}

function formatar_data(?string $data): string
{
    if (!$data) {
        return '—';
    }

    return (new DateTimeImmutable($data))->format('d/m/Y');
}


function formatar_data_hora(?string $data): string
{
    if (!$data) {
        return "—";
    }

    return (new DateTimeImmutable($data))->format("d/m/Y H:i");
}

function data_iso_valida(string $data): bool
{
    $objeto = DateTimeImmutable::createFromFormat('!Y-m-d', $data);

    return $objeto !== false && $objeto->format('Y-m-d') === $data;
}

function normalizar_data_hora(string $data): ?string
{
    $objeto = DateTimeImmutable::createFromFormat('!Y-m-d\TH:i', $data);

    return $objeto !== false && $objeto->format('Y-m-d\TH:i') === $data
        ? $objeto->format('Y-m-d H:i:s')
        : null;
}
