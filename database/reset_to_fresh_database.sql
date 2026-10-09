-- HESH / FRN Operations Suite — FRESH DATABASE RESET
-- WARNING: This permanently removes ALL business content and transaction history.
-- Export a backup in phpMyAdmin before importing this file.
-- Preserved/recreated: roles, permissions, Super Admin, Al Hilal, Duhail, HESH, FRN, units and settings.

SET FOREIGN_KEY_CHECKS = 0;

TRUNCATE TABLE sale_items;
TRUNCATE TABLE sales;
TRUNCATE TABLE stock_receiving_items;
TRUNCATE TABLE stock_receivings;
TRUNCATE TABLE stock_usage_items;
TRUNCATE TABLE stock_usages;
TRUNCATE TABLE wastage_items;
TRUNCATE TABLE wastages;
TRUNCATE TABLE stock_transfer_items;
TRUNCATE TABLE stock_transfers;
TRUNCATE TABLE stock_adjustments;
TRUNCATE TABLE inventory_transactions;
TRUNCATE TABLE notifications;
TRUNCATE TABLE audit_logs;
TRUNCATE TABLE login_attempts;
TRUNCATE TABLE password_resets;
TRUNCATE TABLE webauthn_credentials;
TRUNCATE TABLE remember_tokens;
TRUNCATE TABLE user_preferences;
TRUNCATE TABLE employees;
TRUNCATE TABLE products;
TRUNCATE TABLE categories;
TRUNCATE TABLE suppliers;
TRUNCATE TABLE users;
TRUNCATE TABLE branches;
TRUNCATE TABLE brands;
TRUNCATE TABLE units;
TRUNCATE TABLE system_settings;

SET FOREIGN_KEY_CHECKS = 1;

INSERT INTO branches(name,code,address,phone,status)
VALUES ('Al Hilal','AHL','Al Hilal, Doha, Qatar','+974 0000 0000','active'),
       ('Duhail','DUH','Duhail, Doha, Qatar','+974 0000 0000','active');

INSERT INTO brands(name,code,color,status)
VALUES ('HESH','HESH','#6d5dfc','active'),
       ('FRN','FRN','#17b783','active');

INSERT INTO units(name,symbol,status)
VALUES ('Piece','pc','active'),('Box','box','active'),('Carton','ctn','active'),
       ('Kilogram','kg','active'),('Gram','g','active'),('Liter','L','active'),
       ('Milliliter','ml','active'),('Tray','tray','active'),('Packet','pkt','active'),
       ('Bottle','btl','active');

INSERT INTO users(role_id,name,email,phone,password_hash,status,force_password_change)
SELECT id,'Super Administrator','admin@heshfrn.local','+974 0000 0000',
       '$2b$12$8XRolcniCA1/65oZ9oTA.etsh22fB6/IYCIgM4r.o1c5OzMDL23aq','active',1
FROM roles WHERE slug='super-admin' LIMIT 1;

INSERT INTO system_settings(setting_key,setting_value)
VALUES ('company_name','HESH & FRN Operations'),('currency','QAR'),
       ('timezone','Asia/Qatar'),('date_format','d M Y'),
       ('low_stock_enabled','1'),('theme','light');

-- One-time setup/recovery login after reset. Activate fingerprint / Face ID from Profile:
-- Email: admin@heshfrn.local
-- Password: Admin@123
