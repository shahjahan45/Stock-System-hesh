# QA Report — v1.9 Monthly Sales & Raw-Item Operations

Date: 9 October 2026  
Target: PHP 8.3+, MySQL 8+, Apache, desktop/tablet/mobile browsers

## Automated package checks

- JavaScript syntax: passed with Node syntax validation.
- Monthly boundary/status regression suite: passed (empty month, accumulation, month isolation, preserved history, leap year and excluded statuses).
- PWA manifest JSON: parsed successfully.
- Backup shell script: Bash syntax passed.
- Required configuration, schema, route, asset, documentation and module files: present.
- No TODO, “coming soon”, demo-button or placeholder-module markers remain in application code.
- Archive integrity: validated after packaging.
- Root routing no longer depends on Apache `mod_rewrite`.
- Application base URL auto-detects custom XAMPP folders such as `/heshapp`.
- Apache `<FilesMatch>` security directives use validated multiline block syntax.
- Default administrator uses a verified bcrypt hash for `Admin@123`.
- A non-destructive `database/reset_admin_password.sql` is included for existing installations.
- Mobile app header and navigation are visible from initial render without scrolling.
- Mobile sidebar drawer now opens, closes from its backdrop, and locks background scrolling.
- Bottom navigation reserves safe-area space and cannot cover page content.
- Mobile filters scroll horizontally instead of creating excessive vertical height.
- Global product search now returns functional results through the authenticated search API.
- Install invitation automatically appears on eligible mobile devices.
- Android native installation prompt is triggered from the required user gesture.
- iOS receives platform-specific Add to Home Screen instructions.
- Manifest includes 192px, 512px and maskable application icons plus app shortcuts.
- Installation invitation remains hidden when the application is already running in standalone mode.
- Main installer contains no sample products, employees, stock or transactions.
- Fresh reset script clears existing business content and restores only required system records.
- Passwordless WebAuthn endpoints cover passkey registration, challenge creation, assertion verification and device revocation.
- Passkey verification requires fingerprint/Face ID/device-lock user verification and validates RP ID, origin, challenge, ES256 signature and sign counter.
- The login page attempts biometric unlock automatically and keeps password entry inside a recovery/setup panel.
- The simplified product form requires only product name and counting unit; SKU and zero-value price fields are handled internally.
- The wastage screen has a searchable touch-friendly product picker, live available stock, automatic unit display and no price input.
- The stock screen includes active products with zero activity instead of hiding them.
- Dashboard totals do not add incompatible units such as kg and pieces together.
- JavaScript passkey and product-picker code passed Node syntax validation.
- Manifest JSON parsed successfully after the Daily Sales and Receive Raw Item shortcuts were added.
- Raw-item creation now contains only item name, unit, status and an optional note.
- Received Stock contains only date, branch, brand, raw item, quantity, unit cost and an optional note.
- Receiving totals are calculated in the UI and validated/recalculated by the server.
- Daily sales stores total, Cash, Card, Talabat, Keeta and Snoonu amounts without product-level selling prices or payment-method dropdowns.
- Daily sales rejects an unbalanced channel split and prevents duplicate branch/brand summaries for the same date.
- Sales history includes Today, This Month, Last Month and custom From/To filters.
- Dashboard sales totals use the current Qatar-calendar month from its first day through today.
- Month-to-date dashboard totals include only completed, non-deleted sales and return zero when no rows match.
- Monthly Sales accepts a validated month/year and derives the final calendar day automatically, including leap years and year boundaries.
- Monthly Sales exposes the complete Cash, Card, Talabat, Keeta and Snoonu breakdown and matching transactions.
- Month changes require no scheduled or manual reset; historical sales rows remain untouched and reports are always calculated from transaction dates.
- Monthly Sales print/PDF and formula-safe UTF-8 CSV export use the same completed-sales filters shown on screen.
- Remembered login uses a random selector/validator token, stores only its SHA-256 hash, rotates the validator after use and uses an HttpOnly SameSite cookie.
- Logout revokes the current remembered-login token.

## Implementation verification

- Authentication uses password hashing/verification, secure session regeneration, lockout tracking and CSRF.
- Protected pages require an authenticated session; privileged mutations require RBAC permissions.
- `/admin`, `/admin/login`, and `/login` do not expose the login form.
- Receiving, sale, usage, wastage, transfer and adjustment operations write to a single auditable inventory ledger.
- Stock-affecting writes use database transactions and roll back on failure.
- Outbound movements prevent negative stock.
- Transfers create paired source and destination ledger movements.
- Dashboard and reports query database transactions rather than hard-coded values.
- All list views include responsive mobile card behavior; desktop uses tables and sidebar navigation.
- Mobile bottom navigation accounts for iPhone safe-area inset and reserves content space.
- Light, dark and reduced-motion modes are included.
- CSV/Excel-compatible export, print/PDF layout and pagination are included.
- User branch scope is enforced in dashboard filters and stock-writing workflows.

## Deployment acceptance tests

Run these after importing the database on the target PHP/MySQL server:

1. Sign in once with the temporary recovery account, add the device passkey, sign out and verify fingerprint/Face ID unlock.
2. Add Beef with unit kg and Cauliflower with unit piece.
3. Receive raw stock with branch, brand, quantity and unit cost; verify available stock.
4. Add an end-of-day sales summary and confirm the channel split must equal Total Sale.
5. Verify the dashboard total equals all completed sales from the first of the current month through today.
6. Select a month with no sales and confirm all totals are zero; then select a previous month and confirm its transactions and totals are unchanged.
7. Test a February leap year, a December-to-January transition, and excluded draft/cancelled rows.
8. Test **Keep me signed in**, close the browser, reopen it and confirm the session restores without credentials.
9. Test each role with an assigned branch and confirm mutation permissions.
10. Export monthly sales, print to PDF and test phone layouts at 360, 390 and 430 px widths.
11. Install the PWA over HTTPS, confirm standalone launch and re-test biometric unlock.
12. Configure outbound email before enabling emailed password-reset links.

PHP CLI was not available in the packaging workspace, so final PHP runtime lint and browser/database integration should be run on the target PHP 8.3 environment using the acceptance steps above.
