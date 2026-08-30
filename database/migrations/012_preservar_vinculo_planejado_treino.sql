ALTER TABLE exr_exercicio_realizado
    ADD COLUMN exr_exp_id_snapshot BIGINT UNSIGNED NULL AFTER exp_id;

UPDATE exr_exercicio_realizado
SET exr_exp_id_snapshot = exp_id
WHERE exp_id IS NOT NULL;
