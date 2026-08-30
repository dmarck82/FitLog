CREATE TABLE sup_suplemento (
    sup_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    usu_id BIGINT UNSIGNED NOT NULL,
    sup_nome VARCHAR(120) NOT NULL,
    sup_marca VARCHAR(120) NULL,
    sup_apresentacao VARCHAR(120) NULL,
    sup_orientacoes TEXT NULL,
    sup_observacoes TEXT NULL,
    sup_ativo TINYINT(1) NOT NULL DEFAULT 1,
    sup_solicitar_feedback TINYINT(1) NOT NULL DEFAULT 0,
    sup_criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    sup_atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (sup_id),
    KEY idx_sup_usuario_ativo (usu_id, sup_ativo),
    CONSTRAINT fk_sup_usuario FOREIGN KEY (usu_id) REFERENCES usu_usuario (usu_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE cps_compra_suplemento (
    cps_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    sup_id BIGINT UNSIGNED NOT NULL,
    cps_data_compra DATE NOT NULL,
    cps_quantidade DECIMAL(10,2) NULL,
    cps_valor DECIMAL(10,2) NULL,
    cps_lote VARCHAR(80) NULL,
    cps_data_validade DATE NULL,
    cps_observacoes TEXT NULL,
    cps_criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    cps_atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (cps_id),
    KEY idx_cps_suplemento_data (sup_id, cps_data_compra),
    CONSTRAINT fk_cps_suplemento FOREIGN KEY (sup_id) REFERENCES sup_suplemento (sup_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE cos_consumo_suplemento (
    cos_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    sup_id BIGINT UNSIGNED NOT NULL,
    cos_data_consumo DATETIME NOT NULL,
    cos_dose DECIMAL(10,3) NOT NULL,
    cos_unidade VARCHAR(30) NOT NULL,
    cos_reacoes TEXT NULL,
    cos_observacoes VARCHAR(250) NULL,
    cos_criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    cos_atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (cos_id),
    KEY idx_cos_suplemento_data (sup_id, cos_data_consumo),
    CONSTRAINT fk_cos_suplemento FOREIGN KEY (sup_id) REFERENCES sup_suplemento (sup_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
