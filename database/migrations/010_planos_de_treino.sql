CREATE TABLE exe_exercicio (
    exe_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    usu_id BIGINT UNSIGNED NOT NULL,
    exe_nome VARCHAR(120) NOT NULL,
    exe_grupo_muscular VARCHAR(80) NULL,
    exe_tipo VARCHAR(30) NOT NULL DEFAULT 'forca',
    exe_observacoes TEXT NULL,
    exe_ativo TINYINT(1) NOT NULL DEFAULT 1,
    exe_criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    exe_atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (exe_id),
    KEY idx_exe_usuario_ativo (usu_id, exe_ativo, exe_nome),
    CONSTRAINT fk_exe_usuario FOREIGN KEY (usu_id) REFERENCES usu_usuario (usu_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE ptr_plano_treino (
    ptr_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    usu_id BIGINT UNSIGNED NOT NULL,
    ptr_nome VARCHAR(120) NOT NULL,
    ptr_objetivo VARCHAR(160) NULL,
    ptr_data_inicio DATE NOT NULL,
    ptr_data_fim DATE NULL,
    ptr_orientacoes TEXT NULL,
    ptr_ativo TINYINT(1) NOT NULL DEFAULT 1,
    ptr_criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    ptr_atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (ptr_id),
    KEY idx_ptr_usuario_vigencia (usu_id, ptr_ativo, ptr_data_inicio, ptr_data_fim),
    CONSTRAINT fk_ptr_usuario FOREIGN KEY (usu_id) REFERENCES usu_usuario (usu_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE trp_treino_planejado (
    trp_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    ptr_id BIGINT UNSIGNED NOT NULL,
    trp_nome VARCHAR(100) NOT NULL,
    trp_dia_semana TINYINT UNSIGNED NULL,
    trp_ordem SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    trp_orientacoes TEXT NULL,
    trp_criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    trp_atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (trp_id),
    KEY idx_trp_plano_dia (ptr_id, trp_dia_semana, trp_ordem),
    CONSTRAINT fk_trp_plano FOREIGN KEY (ptr_id) REFERENCES ptr_plano_treino (ptr_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE exp_exercicio_planejado (
    exp_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    trp_id BIGINT UNSIGNED NOT NULL,
    exe_id BIGINT UNSIGNED NOT NULL,
    exp_series SMALLINT UNSIGNED NOT NULL DEFAULT 1,
    exp_repeticoes_min SMALLINT UNSIGNED NULL,
    exp_repeticoes_max SMALLINT UNSIGNED NULL,
    exp_carga_alvo DECIMAL(10,2) NULL,
    exp_descanso_segundos SMALLINT UNSIGNED NULL,
    exp_duracao_segundos INT UNSIGNED NULL,
    exp_distancia_km DECIMAL(10,2) NULL,
    exp_observacoes VARCHAR(500) NULL,
    exp_ordem SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    exp_criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    exp_atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (exp_id),
    KEY idx_exp_treino_ordem (trp_id, exp_ordem),
    KEY idx_exp_exercicio (exe_id),
    CONSTRAINT fk_exp_treino FOREIGN KEY (trp_id) REFERENCES trp_treino_planejado (trp_id) ON DELETE CASCADE,
    CONSTRAINT fk_exp_exercicio FOREIGN KEY (exe_id) REFERENCES exe_exercicio (exe_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
