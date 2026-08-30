CREATE TABLE pal_plano_alimentar (
    pal_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    usu_id BIGINT UNSIGNED NOT NULL,
    pal_nome VARCHAR(120) NOT NULL,
    pal_profissional VARCHAR(120) NULL,
    pal_data_inicio DATE NOT NULL,
    pal_data_fim DATE NULL,
    pal_orientacoes TEXT NULL,
    pal_ativo TINYINT(1) NOT NULL DEFAULT 1,
    pal_criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    pal_atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (pal_id),
    KEY idx_pal_usuario_vigencia (usu_id, pal_ativo, pal_data_inicio, pal_data_fim),
    CONSTRAINT fk_pal_usuario FOREIGN KEY (usu_id) REFERENCES usu_usuario (usu_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE ref_refeicao_plano (
    ref_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    pal_id BIGINT UNSIGNED NOT NULL,
    ref_nome VARCHAR(80) NOT NULL,
    ref_horario TIME NULL,
    ref_observacoes TEXT NULL,
    ref_ordem SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    ref_criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    ref_atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (ref_id),
    KEY idx_ref_plano_ordem (pal_id, ref_ordem, ref_horario),
    CONSTRAINT fk_ref_plano FOREIGN KEY (pal_id) REFERENCES pal_plano_alimentar (pal_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE ita_item_alimentar (
    ita_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    ref_id BIGINT UNSIGNED NOT NULL,
    ita_alimento VARCHAR(160) NOT NULL,
    ita_quantidade DECIMAL(10,3) NULL,
    ita_unidade VARCHAR(30) NULL,
    ita_substituicoes TEXT NULL,
    ita_ordem SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    ita_criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    ita_atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (ita_id),
    KEY idx_ita_refeicao_ordem (ref_id, ita_ordem),
    CONSTRAINT fk_ita_refeicao FOREIGN KEY (ref_id) REFERENCES ref_refeicao_plano (ref_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
