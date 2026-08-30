ALTER TABLE pes_peso_corporal
    ADD COLUMN pes_percentual_gordura DECIMAL(5,2) NULL AFTER pes_peso_kg,
    ADD COLUMN pes_massa_magra_kg DECIMAL(6,2) NULL AFTER pes_percentual_gordura;

UPDATE pes_peso_corporal AS pes
SET
    pes.pes_percentual_gordura = (
        SELECT mec.mec_percentual_gordura
        FROM mec_medicao_corporal AS mec
        WHERE mec.usu_id = pes.usu_id
          AND mec.mec_data_medicao = pes.pes_data_pesagem
        ORDER BY mec.mec_id DESC
        LIMIT 1
    ),
    pes.pes_massa_magra_kg = (
        SELECT mec.mec_massa_magra_kg
        FROM mec_medicao_corporal AS mec
        WHERE mec.usu_id = pes.usu_id
          AND mec.mec_data_medicao = pes.pes_data_pesagem
        ORDER BY mec.mec_id DESC
        LIMIT 1
    )
WHERE EXISTS (
    SELECT 1
    FROM mec_medicao_corporal AS mec
    WHERE mec.usu_id = pes.usu_id
      AND mec.mec_data_medicao = pes.pes_data_pesagem
);

DELETE FROM mec_medicao_corporal
WHERE mec_cintura_cm IS NULL
  AND mec_abdomen_cm IS NULL
  AND mec_quadril_cm IS NULL
  AND mec_torax_cm IS NULL
  AND mec_braco_direito_cm IS NULL
  AND mec_braco_esquerdo_cm IS NULL
  AND mec_coxa_direita_cm IS NULL
  AND mec_coxa_esquerda_cm IS NULL;

ALTER TABLE mec_medicao_corporal
    DROP COLUMN mec_percentual_gordura,
    DROP COLUMN mec_massa_magra_kg;

