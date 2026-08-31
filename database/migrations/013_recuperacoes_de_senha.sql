CREATE TABLE rec_recuperacao_senha (
    rec_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    usu_id BIGINT UNSIGNED NOT NULL,
    rec_token_hash CHAR(64) NOT NULL,
    rec_expira_em DATETIME NOT NULL,
    rec_utilizado_em DATETIME NULL,
    rec_criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (rec_id),
    UNIQUE KEY uk_rec_token_hash (rec_token_hash),
    KEY idx_rec_usuario (usu_id),
    KEY idx_rec_expiracao (rec_expira_em),
    CONSTRAINT fk_rec_usuario FOREIGN KEY (usu_id) REFERENCES usu_usuario (usu_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
