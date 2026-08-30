<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    exit("Este script deve ser executado no terminal.\n");
}

require dirname(__DIR__) . '/app/inicializar.php';

try {
    $pdo = banco();
    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS mig_migracao (
            mig_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            mig_arquivo VARCHAR(190) NOT NULL,
            mig_executada_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (mig_id),
            UNIQUE KEY uk_mig_arquivo (mig_arquivo)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );

    $executadas = $pdo->query('SELECT mig_arquivo FROM mig_migracao')->fetchAll(PDO::FETCH_COLUMN);
    $arquivos = glob(RAIZ_PROJETO . '/database/migrations/*.sql') ?: [];
    sort($arquivos, SORT_NATURAL);
    $quantidade = 0;

    foreach ($arquivos as $arquivo) {
        $nome = basename($arquivo);

        if (in_array($nome, $executadas, true)) {
            echo "Ignorada: {$nome}\n";
            continue;
        }

        $sql = file_get_contents($arquivo);
        if ($sql === false) {
            throw new RuntimeException("Não foi possível ler {$nome}.");
        }

        echo "Executando: {$nome}\n";
        $pdo->exec($sql);
        $registro = $pdo->prepare('INSERT INTO mig_migracao (mig_arquivo) VALUES (:arquivo)');
        $registro->execute(['arquivo' => $nome]);
        $quantidade++;
    }

    echo $quantidade === 0 ? "Banco já está atualizado.\n" : "Migrations executadas: {$quantidade}.\n";
} catch (Throwable $erro) {
    fwrite(STDERR, "Erro: {$erro->getMessage()}\n");
    exit(1);
}

