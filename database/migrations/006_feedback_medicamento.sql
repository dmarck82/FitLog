ALTER TABLE med_medicamento
    ADD COLUMN med_solicitar_feedback TINYINT(1) NOT NULL DEFAULT 0 AFTER med_ativo;

