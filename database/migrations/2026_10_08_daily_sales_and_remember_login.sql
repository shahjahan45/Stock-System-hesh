-- HESH / FRN Operations Suite v1.8 upgrade
-- Import once when upgrading v1.7. New installations only need database/install.sql.

ALTER TABLE sales
    ADD COLUMN cash_amount DECIMAL(14,2) NOT NULL DEFAULT 0 AFTER total_amount,
    ADD COLUMN card_amount DECIMAL(14,2) NOT NULL DEFAULT 0 AFTER cash_amount,
    ADD COLUMN talabat_amount DECIMAL(14,2) NOT NULL DEFAULT 0 AFTER card_amount,
    ADD COLUMN keeta_amount DECIMAL(14,2) NOT NULL DEFAULT 0 AFTER talabat_amount,
    ADD COLUMN snoonu_amount DECIMAL(14,2) NOT NULL DEFAULT 0 AFTER keeta_amount;

CREATE TABLE remember_tokens (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    selector CHAR(32) NOT NULL UNIQUE,
    token_hash CHAR(64) NOT NULL,
    expires_at DATETIME NOT NULL,
    last_used_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX(user_id,expires_at),
    CONSTRAINT fk_remember_user FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
