CREATE TABLE usu_usuario (
    usu_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    usu_nome VARCHAR(120) NOT NULL,
    usu_email VARCHAR(190) NOT NULL,
    usu_senha_hash VARCHAR(255) NOT NULL,
    usu_ativo TINYINT(1) NOT NULL DEFAULT 1,
    usu_ultimo_acesso_em DATETIME NULL,
    usu_criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    usu_atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (usu_id),
    UNIQUE KEY uk_usu_email (usu_email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE mec_medicao_corporal (
    mec_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    usu_id BIGINT UNSIGNED NOT NULL,
    mec_data_medicao DATE NOT NULL,
    mec_peso_kg DECIMAL(6,2) NULL,
    mec_percentual_gordura DECIMAL(5,2) NULL,
    mec_massa_magra_kg DECIMAL(6,2) NULL,
    mec_cintura_cm DECIMAL(6,2) NULL,
    mec_abdomen_cm DECIMAL(6,2) NULL,
    mec_quadril_cm DECIMAL(6,2) NULL,
    mec_torax_cm DECIMAL(6,2) NULL,
    mec_braco_direito_cm DECIMAL(6,2) NULL,
    mec_braco_esquerdo_cm DECIMAL(6,2) NULL,
    mec_coxa_direita_cm DECIMAL(6,2) NULL,
    mec_coxa_esquerda_cm DECIMAL(6,2) NULL,
    mec_observacoes TEXT NULL,
    mec_criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    mec_atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (mec_id),
    KEY idx_mec_usuario_data (usu_id, mec_data_medicao),
    CONSTRAINT fk_mec_usuario FOREIGN KEY (usu_id) REFERENCES usu_usuario (usu_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

