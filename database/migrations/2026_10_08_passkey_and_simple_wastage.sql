-- HESH / FRN Operations Suite v1.7 upgrade
-- Import once when upgrading an existing database. New installations do not need this file.

CREATE TABLE IF NOT EXISTS webauthn_credentials (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    credential_id VARCHAR(512) NOT NULL UNIQUE,
    public_key_pem TEXT NOT NULL,
    sign_count BIGINT UNSIGNED NOT NULL DEFAULT 0,
    transports JSON NULL,
    device_name VARCHAR(100) NOT NULL DEFAULT 'Fingerprint / Face ID',
    last_used_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    revoked_at DATETIME NULL,
    INDEX(user_id,revoked_at),
    CONSTRAINT fk_webauthn_user FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
