# Hostinger / Shared Hosting Deployment

1. Create the database and user in the hosting control panel; grant only the required database privileges.
2. Import `database/install.sql` using phpMyAdmin.
3. Upload the extracted project contents into the selected domain/subdomain document root.
4. Copy `config.local.example.php` as `config.local.php` and set credentials.
5. Update the URL in `config.php` or supply `APP_URL` through hosting environment configuration.
6. Use PHP 8.3 or later and enable PDO MySQL, mbstring, fileinfo, JSON and OpenSSL.
7. Confirm `.htaccess` is uploaded and hidden files are visible in the file manager.
8. Use HTTPS before biometric setup. WebAuthn works on a live HTTPS domain and on `localhost` during local testing, but not on an ordinary HTTP LAN IP.
9. Sign in once with the recovery account, open Profile, add fingerprint/Face ID access, sign out and test the biometric unlock screen.
10. When upgrading an existing v1.6 database, import `database/migrations/2026_10_08_passkey_and_simple_wastage.sql` first. When upgrading v1.7, import `database/migrations/2026_10_08_daily_sales_and_remember_login.sql`. A fresh installation only imports `database/install.sql`.

Recommended permissions: folders `755`, PHP/config files `640` or `644`, and upload folders `755`. Never use `777` unless the host requires it temporarily and it is reverted immediately.
