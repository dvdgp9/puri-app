-- Persistencia de "Recordarme" para administradores.
CREATE TABLE IF NOT EXISTS admin_sessions (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    admin_id INT NOT NULL,
    token CHAR(64) NOT NULL,
    expires_at DATETIME NOT NULL,
    user_agent TEXT NULL,
    ip_address VARCHAR(45) NULL,
    last_used_at DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_admin_sessions_token (token),
    KEY idx_admin_sessions_admin (admin_id),
    KEY idx_admin_sessions_expires (expires_at),
    CONSTRAINT fk_admin_sessions_admin FOREIGN KEY (admin_id)
        REFERENCES admins(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
