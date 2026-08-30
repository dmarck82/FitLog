CREATE TABLE ral_registro_alimentar (
    ral_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    usu_id BIGINT UNSIGNED NOT NULL,
    pal_id BIGINT UNSIGNED NULL,
    ref_id BIGINT UNSIGNED NULL,
    ral_data DATE NOT NULL,
    ral_horario TIME NULL,
    ral_situacao VARCHAR(30) NOT NULL,
    ral_refeicao_nome VARCHAR(80) NOT NULL,
    ral_planejado_snapshot MEDIUMTEXT NOT NULL,
    ral_fome_antes TINYINT UNSIGNED NULL,
    ral_saciedade_depois TINYINT UNSIGNED NULL,
    ral_observacoes TEXT NULL,
    ral_criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    ral_atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (ral_id),
    UNIQUE KEY uk_ral_refeicao_data (ref_id, ral_data),
    KEY idx_ral_usuario_data (usu_id, ral_data),
    KEY idx_ral_plano_data (pal_id, ral_data),
    CONSTRAINT fk_ral_usuario FOREIGN KEY (usu_id) REFERENCES usu_usuario (usu_id),
    CONSTRAINT fk_ral_plano FOREIGN KEY (pal_id) REFERENCES pal_plano_alimentar (pal_id) ON DELETE SET NULL,
    CONSTRAINT fk_ral_refeicao FOREIGN KEY (ref_id) REFERENCES ref_refeicao_plano (ref_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE ira_item_realizado (
    ira_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    ral_id BIGINT UNSIGNED NOT NULL,
    ira_alimento VARCHAR(160) NOT NULL,
    ira_quantidade DECIMAL(10,3) NULL,
    ira_unidade VARCHAR(30) NULL,
    ira_observacoes VARCHAR(500) NULL,
    ira_ordem SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    ira_criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    ira_atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (ira_id),
    KEY idx_ira_registro_ordem (ral_id, ira_ordem),
    CONSTRAINT fk_ira_registro FOREIGN KEY (ral_id) REFERENCES ral_registro_alimentar (ral_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
