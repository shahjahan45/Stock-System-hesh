# HESH & FRN Daily Operations App v1.8

Production-oriented, admin-only, mobile-first operations system for HESH and FRN across Al Hilal, Duhail, and future branches.

## Technology

- Pure PHP 8.3+ (no framework)
- MySQL 8+
- Responsive custom CSS design system inspired by Tailwind utility principles
- Vanilla JavaScript, Fetch-ready JSON APIs, PWA manifest/service worker
- PDO prepared statements and database transactions

## Installation (XAMPP or Apache hosting)

1. Extract the project into your web root, for example `htdocs/hesh-frn-operations`.
2. Create a MySQL database named `hesh_frn_operations` with `utf8mb4` collation.
3. Import `database/install.sql` in phpMyAdmin. This is a clean production database with no sample products, stock or transactions.
4. Copy `config.local.example.php` to `config.local.php`.
5. Update database credentials. The application URL is detected automatically, including folders such as `http://localhost/heshapp`.
6. Allow `.htaccess` overrides. `mod_rewrite` is not required.
7. Ensure `assets/uploads/` is writable by PHP (`755` normally; use the minimum supported by the host).
8. Open `/auth/manage-admin/login/` and complete the one-time recovery login.
9. The app opens **Profile → Fingerprint / Face ID** automatically. Add the current device.
10. Keep **Keep me signed in** ticked to avoid username/password prompts on the same device for 30 days. Fingerprint, Face ID and Windows Hello remain available.

## Initial administrator

- Email: `admin@heshfrn.local`
- Password: `Admin@123`

Change the temporary recovery password after biometric setup. It is retained only for new-device setup and emergency recovery.

Passkeys require HTTPS on a live domain. Browsers also allow them on `http://localhost` for XAMPP testing. The fingerprint or face template never reaches this application; the phone/computer verifies it securely and returns a signed passkey response.

If the database was imported from an earlier package and login fails, import `database/reset_admin_password.sql`. It preserves application data, resets the Super Admin password, and clears failed-login lockout attempts.

To remove all content from an existing installation, first export a backup and then import `database/reset_to_fresh_database.sql`. It deletes all products, categories, suppliers, employees, passkeys, transactions, stock, notifications and logs, then recreates only the required administrator, branches, brands, units and settings.

When upgrading an existing v1.6 database without deleting its content, import `database/migrations/2026_10_08_passkey_and_simple_wastage.sql` once.

When upgrading v1.7, also import `database/migrations/2026_10_08_daily_sales_and_remember_login.sql` once. New/fresh installations only import `database/install.sql`.

## Security and routing

- Only `/auth/manage-admin/login/` exposes the sign-in screen.
- `/admin`, `/admin/login`, and `/login` return the protected 404 page.
- Sessions use HttpOnly/SameSite cookies and regenerate after login.
- CSRF is required for state-changing forms.
- Authentication is rate-limited to five failed attempts per IP within 15 minutes.
- Passwordless access uses WebAuthn with required user verification, per-request challenges, origin/RP validation, ES256 signature verification and passkey counter checks.
- Financial and stock writes run inside MySQL transactions.
- Critical history is recorded in `audit_logs`.
- Configure HTTPS in production; secure cookies activate automatically under HTTPS.

## Inventory logic

Stock is never trusted from a manually editable total. Every movement creates an `inventory_transactions` entry:

- opening
- receiving
- sale
- usage
- wastage
- transfer out / transfer in
- adjustment increase / decrease
- reversal / return (schema-ready)

Available stock is calculated from total quantity in minus total quantity out for a product, branch, and brand. Negative stock is blocked for outbound movements.

The raw-item master requires only the item name and counting unit. There is no barcode, category, selling price or payment method. Receive stock by choosing date, branch, brand, raw item, quantity and unit cost. Available stock updates automatically.

End-of-day sales are saved as one branch/brand summary with Total Sale, Cash, Card, Talabat, Keeta and Snoonu. Channel totals must reconcile with the entered total. The sales screen includes Today, This Month, Last Month and custom From/To date filters.

The wastage screen remains quantity-only: choose a raw item, see its available balance and unit, enter the wasted quantity, choose a reason and save. There is no price or cost field.

## Included modules

The primary workspace contains Dashboard, End-of-Day Sales, Received Stock, Wastage, Available Stock, Raw Items, Branches, Brands, Users/Roles, Reports, Audit Logs, Settings and Profile. Supporting legacy operation pages remain available for compatibility but are removed from the simplified navigation.

## PWA/mobile

The mobile interface is not a scaled desktop layout. Version 1.4 includes an always-visible native-style app header, working slide-out navigation drawer, compact swipeable filters, fixed safe-area-aware bottom navigation visible before scrolling, elevated quick-entry action button, slide-up action sheet, card-based mobile tables, touch feedback, dark mode and reduced-motion support.

Install support requires HTTPS (localhost is allowed by browsers). Icons and offline shell assets are included.

Version 1.8 keeps the automatic professional installation invitation and adds an app-style Daily Sales/Receive/Raw Items navigation, remembered login, biometric access, channel-based sales entry, custom date reporting and simplified raw-item receiving. Android Chrome opens its native install window after the user taps **Install App**. iPhone/iPad users receive the required **Share → Add to Home Screen** instructions. Browsers intentionally require a user tap before installation; a website cannot silently install itself.

## Backups

Configure the host to run `scripts/backup.sh` daily outside the public web root. Never store unrestricted database backups in a publicly accessible folder.

## Production checklist

- Set `APP_URL`, `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS` and `APP_ENV=production`.
- Change the initial administrator password and email.
- Update the placeholder company phone/address records.
- Enable HTTPS and verify the Secure session cookie.
- Configure mail delivery before sending password-reset links externally.
- Schedule daily database backups and test restore procedures.
- Confirm `display_errors=Off` and inspect server logs during QA.

## XAMPP Apache 500 troubleshooting

Version 1.2 removes the previous `mod_rewrite` dependency and uses XAMPP-compatible multiline `<FilesMatch>` blocks. Replace the entire old project folder so both corrected `.htaccess` files are used. If Apache still displays its generic 500 page before PHP output, open `C:\xampp\apache\logs\error.log` and inspect the final entry; it will identify the exact unsupported directive or filesystem problem.
