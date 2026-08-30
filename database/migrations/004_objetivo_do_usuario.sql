ALTER TABLE usu_usuario
    ADD COLUMN usu_objetivo VARCHAR(20) NOT NULL DEFAULT 'perder_peso' AFTER usu_email;

