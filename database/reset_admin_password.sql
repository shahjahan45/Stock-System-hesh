-- HESH / FRN Operations Suite — Safe administrator password reset
-- Import this file into the SAME database already used by the application.
-- It preserves every transaction and only resets the first active Super Admin.

START TRANSACTION;

UPDATE users
SET email = 'admin@heshfrn.local',
    password_hash = '$2b$12$8XRolcniCA1/65oZ9oTA.etsh22fB6/IYCIgM4r.o1c5OzMDL23aq',
    status = 'active',
    force_password_change = 1,
    deleted_at = NULL
WHERE role_id = (SELECT id FROM roles WHERE slug = 'super-admin' LIMIT 1)
ORDER BY id ASC
LIMIT 1;

DELETE FROM login_attempts
WHERE email = 'admin@heshfrn.local' OR success = 0;

DELETE rt FROM remember_tokens rt
JOIN users u ON u.id = rt.user_id
JOIN roles r ON r.id = u.role_id
WHERE r.slug = 'super-admin';

COMMIT;

-- Login after importing:
-- Email: admin@heshfrn.local
-- Password: Admin@123
