CREATE TABLE med_medicamento (
    med_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    usu_id BIGINT UNSIGNED NOT NULL,
    med_nome VARCHAR(120) NOT NULL,
    med_apresentacao VARCHAR(120) NULL,
    med_via_administracao VARCHAR(30) NULL,
    med_orientacoes TEXT NULL,
    med_observacoes TEXT NULL,
    med_ativo TINYINT(1) NOT NULL DEFAULT 1,
    med_criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    med_atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (med_id),
    KEY idx_med_usuario_ativo (usu_id, med_ativo),
    CONSTRAINT fk_med_usuario FOREIGN KEY (usu_id) REFERENCES usu_usuario (usu_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE com_compra_medicamento (
    com_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    med_id BIGINT UNSIGNED NOT NULL,
    com_data_compra DATE NOT NULL,
    com_quantidade DECIMAL(10,2) NULL,
    com_valor DECIMAL(10,2) NULL,
    com_lote VARCHAR(80) NULL,
    com_data_validade DATE NULL,
    com_observacoes TEXT NULL,
    com_criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    com_atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (com_id),
    KEY idx_com_medicamento_data (med_id, com_data_compra),
    CONSTRAINT fk_com_medicamento FOREIGN KEY (med_id) REFERENCES med_medicamento (med_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE apl_aplicacao_medicamento (
    apl_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    med_id BIGINT UNSIGNED NOT NULL,
    apl_data_aplicacao DATETIME NOT NULL,
    apl_dose DECIMAL(10,3) NOT NULL,
    apl_unidade VARCHAR(30) NOT NULL,
    apl_local_aplicacao VARCHAR(100) NULL,
    apl_reacoes TEXT NULL,
    apl_observacoes TEXT NULL,
    apl_criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    apl_atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (apl_id),
    KEY idx_apl_medicamento_data (med_id, apl_data_aplicacao),
    CONSTRAINT fk_apl_medicamento FOREIGN KEY (med_id) REFERENCES med_medicamento (med_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

