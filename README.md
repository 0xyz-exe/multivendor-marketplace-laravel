# Multi-Vendor Marketplace Laravel

Independent Laravel 13 app for moderated seller onboarding, a multi-seller catalog, session cart, and transactional split-order checkout.

## Stack / setup

- PHP 8.3+, Composer, Node.js/npm, SQLite (PostgreSQL supported with matching PDO driver)

```powershell
Copy-Item .env.example .env
php C:/Users/wondr/.config/herd-lite/bin/composer.phar install
npm install
php artisan key:generate
php artisan migrate --seed
npm run build
php artisan serve
```

Register customer accounts at `/register`. No test identities, credentials, sellers, or product records are seeded; only categories and 10% default commission configuration are seeded. Use Tinker locally to promote a trusted first admin:

```powershell
php artisan tinker
```

```php
App\Models\User::where('email', 'your-admin@example.com')->firstOrFail()->update(['role' => 'admin']);
```

Admins review `/admin` seller applications and products. Users apply at `/`; approved sellers can submit products for moderation in `/seller`. Public catalog only exposes approved products. Signed-in customers can place mixed-seller orders from `/cart`.

## Commission/payment behavior

Commission is an explicit 10% default of each seller's item subtotal, rounded to nearest minor unit (`intdiv(subtotal * percent + 50, 100)`). `marketplace_settings.commission_percent` stores the global percentage (0–100); change only through trusted admin/database tooling. Prices and snapshots use integer currency minor units; no currency conversion, tax, or shipping calculation exists. Checkout locks and revalidates approved product stock, reads prices from the database, decrements stock, then creates one parent order and seller-specific suborders/items in a single transaction. Failed stock validation rolls back all changes. Payment is always marked `unpaid`; there is no payment, refund, dispute, settlement, or payout integration. A placed order is not a paid transaction.

Seller dashboards and order lists are scoped to the authenticated seller. Customers see only their own orders. Seller/admin permissions are server-checked. Buyer checkout accepts no seller ID.

## Validation

```powershell
php artisan migrate:fresh --seed
php artisan test --compact
npm run build
php C:/Users/wondr/.config/herd-lite/bin/composer.phar validate --no-check-publish
php C:/Users/wondr/.config/herd-lite/bin/composer.phar audit
npm audit --omit=dev
```

## Limits before production

No payment provider, settlement, shipping, tax, inventory adjustment UI, image upload, refunds/disputes workflow, or notifications are implemented. Database row locking requires a transactional production DB; SQLite tests do not establish concurrent oversell safety. Configure HTTPS, mail delivery, backups, session/security headers, and approved tax/payment policies before deployment. No `.env`, personal data, `vendor`, `node_modules`, or build output belongs in Git.

MIT license.


## Screenshots

### Dashboard Overview
![Dashboard](docs/screenshots/01_dashboard.png)

### Login Overview
![Login](docs/screenshots/02_login.png)
