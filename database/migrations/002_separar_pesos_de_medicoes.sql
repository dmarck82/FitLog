CREATE TABLE pes_peso_corporal (
    pes_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    usu_id BIGINT UNSIGNED NOT NULL,
    pes_data_pesagem DATE NOT NULL,
    pes_peso_kg DECIMAL(6,2) NOT NULL,
    pes_observacoes TEXT NULL,
    pes_criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    pes_atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (pes_id),
    KEY idx_pes_usuario_data (usu_id, pes_data_pesagem),
    CONSTRAINT fk_pes_usuario FOREIGN KEY (usu_id) REFERENCES usu_usuario (usu_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO pes_peso_corporal (
    usu_id,
    pes_data_pesagem,
    pes_peso_kg,
    pes_observacoes,
    pes_criado_em,
    pes_atualizado_em
)
SELECT
    usu_id,
    mec_data_medicao,
    mec_peso_kg,
    mec_observacoes,
    mec_criado_em,
    mec_atualizado_em
FROM mec_medicao_corporal
WHERE mec_peso_kg IS NOT NULL;

DELETE FROM mec_medicao_corporal
WHERE mec_percentual_gordura IS NULL
  AND mec_massa_magra_kg IS NULL
  AND mec_cintura_cm IS NULL
  AND mec_abdomen_cm IS NULL
  AND mec_quadril_cm IS NULL
  AND mec_torax_cm IS NULL
  AND mec_braco_direito_cm IS NULL
  AND mec_braco_esquerdo_cm IS NULL
  AND mec_coxa_direita_cm IS NULL
  AND mec_coxa_esquerda_cm IS NULL;

ALTER TABLE mec_medicao_corporal DROP COLUMN mec_peso_kg;

