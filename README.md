# RawHost (Veneko Server)

![RawHost — VPS Bulletproof, Anonymous & Offshore](assets/screenshot-home.png)

Offshore, privacy-focused **VPS hosting platform** with anonymous crypto payments, domain
registration, a Telegram bot, a support-ticket system, and an admin dashboard. The application
provisions and manages virtual servers through an upstream provider API and bills users in crypto.

> ⚠️ **Security:** all secret values in this repository have been replaced with the placeholder
> `CHANGE_ME`. You **must** fill them in (see [Configuration](#configuration)) before the app will run.
> Never commit real credentials. Any value that was previously committed should be treated as
> compromised and rotated.

---

## Tech stack

- **Backend:** PHP 7.4+ (PDO, cURL, OpenSSL extensions)
- **Database:** MySQL 5.7+ / MariaDB 10.3+
- **Frontend:** server-rendered PHP + vanilla JavaScript/CSS
- **Data layer:** PDO repositories with prepared statements
- **Web server:** Apache (with `mod_rewrite`) or Nginx + PHP-FPM

### External integrations
- **external provider API** — VPS provisioning, control, metrics (SolusVM behind it)
- **NiceNIC API** — domain registration
- **OxaPay** — crypto payment gateway (BTC / XMR / USDT / TRX)
- **Telegram Bot API** — login + notifications
- **Gotify** — push notifications / alerts
- **hCaptcha** — bot protection on auth forms

---

## Features

- VPS provisioning, lifecycle control (start/stop/restart/reinstall/rename), VNC & SSH console
- Add-ons: extra IPv4/IPv6, storage upgrades
- Domain registration & nameserver management (NiceNIC)
- Anonymous crypto payments + user balance ledger
- Resource monitoring (CPU/RAM/Disk) with threshold alerts
- Support ticketing with Telegram notifications
- Admin dashboard (users, orders, transactions, news, settings)
- Bilingual UI: Spanish (`es`) / English (`en`)
- One-click VPS scripts (Docker, Minecraft server, …)

---

## Directory structure

| Path | Purpose |
|------|---------|
| `api/` | REST API endpoints, grouped by module; `api/config.php` holds all constants & shared helpers |
| `dashboard/` | Authenticated user/admin UI (server management, orders, domains, profile, support) |
| `services/` | Business-logic services (`ExternalApiService`, `OxaPayService`) |
| `repositories/` | PDO data-access layer (one class per table) |
| `models/` | Legacy model classes (`Database`, `User`, `Plan`, `Domain`) |
| `daemon/` | Long-running background workers (expiry, IP sync, transaction expiry) |
| `agents/` | Cron-driven notification scripts + Gotify helper |
| `migrations/` | Incremental schema migration scripts (SQL/PHP) |
| `data/` | Base schema (`schema.sql`), triggers, table-creation SQL |
| `vps_scripts/` | Remote provisioning scripts + script catalog |
| `assets/` | CSS, JS, fonts, images, logos |
| `includes/` | Shared PHP includes (i18n loader, auth helpers) |
| `languages/` | `es.php` / `en.php` translation strings |
| `logs/`, `uploads/` | Runtime logs and user uploads (writable) |

Public entry pages live at the project root: `index.php`, `login.php`, `register.php`, `logout.php`,
`terms.php`.

---

## Architecture

```
Browser ──► entry pages (index/login/register)
              │
              ▼
        api/<module>/*.php   ── authenticate_user() (session or X-API-KEY)
              │
              ├─► repositories/*  ──► MySQL (dummiesvps)
              └─► services/
                    ├─ ExternalApiService  ──► external provider / NiceNIC
                    └─ OxaPayService       ──► OxaPay

Background:
  daemon/*  (loops)      agents/*  (cron)      api/webhook/oxapay.php (payment callbacks)
```

- **Endpoints** validate input, authenticate, then delegate to repositories/services.
- **Repositories** are the only place that touches SQL (prepared statements throughout).
- **Services** wrap third-party HTTP APIs.
- **Daemons/agents** run out-of-band for expiry, syncing, and alerting.

---

## API endpoints (overview)

| Module | Path | Examples |
|--------|------|----------|
| Auth | `api/auth/` | `login`, `register`, `telegram_callback`, `telegram_link` |
| Servers | `api/servers/` | `list`, `detail`, `action`, `reinstall`, `usage`, `vnc`, `ssh_*`, `docker_*` |
| Orders | `api/orders/` | `create`, `create_addon_order`, `create_domain`, `calculate_price`, `process`, `pay_service_crypto` |
| Plans | `api/plans/` | `list`, `fees` |
| Domains | `api/domains/` | `list`, `check_availability`, `change_nameservers`, `prices` |
| Users | `api/users/` | `me`, `balance`, `movements`, `apikeys`, `preferences`, `upload_avatar`, `gotify_setup` |
| Transactions | `api/transactions/` | `create`, `list`, `currencies`, `payment_info`, `expire` |
| Add-ons | `api/addons/` | IP / storage management |
| Admin | `api/admin/` | users, settings, reports |
| Support | `api/support/` | ticket create/reply/list |
| Agent | `api/agent/` | `last_metrics`, `config` (alert thresholds) |
| Webhook | `api/webhook/` | `oxapay` (payment callback) |

Authentication: session cookie (web) or `X-API-KEY` header (programmatic), resolved by
`authenticate_user()` in `api/config.php`.

---

## Database

- **Name:** `dummiesvps` (MySQL/MariaDB, `localhost` by default)
- **Base schema:** `data/schema.sql` + `data/trigger.sql`
- **Migrations:** `migrations/` (run in order; PHP runners and raw SQL)

Core tables: `users`, `vps`, `plans`, `plan_fees`, `orders`, `addons`, `domains`, `tickets`,
`ticket_messages`, `movements` (balance ledger), `transactions`, `expenses`, `news`, `api_keys`,
`bot_analytics`.

---

## Configuration

All constants live in **`api/config.php`** via `define(...)`. Secret values are placeholders
(`CHANGE_ME`) and must be set before running.

| Constant | Set to |
|----------|--------|
| `DB_HOST`, `DB_USER`, `DB_NAME` | your MySQL connection (defaults: `localhost` / `root` / `dummiesvps`) |
| `DB_PASS` | MySQL password |
| `EXTERNAL_API_KEY`, `EXTERNAL_USER_ID` | external provider credentials |
| `NICENIC_USER`, `NICENIC_PASS`, `NICENIC_EMAIL` | NiceNIC domain-registrar account |
| `OXAPAY_API_KEY` | OxaPay merchant key |
| `TOKEN_TELEGRAM`, `TELEGRAM_CHAT_ID` | main Telegram bot token + admin chat id |
| `SUPPORT_TG_TOKEN`, `SUPPORT_TG_CHAT_ID` | support Telegram bot token + chat id |
| `GOTIFY_ADMIN_PASS` | Gotify admin password (`GOTIFY_URL`, `GOTIFY_ADMIN_USER` already set) |
| `HCAPTCHA_SECRET_KEY` | hCaptcha secret (the public `HCAPTCHA_SITE_KEY` is non-secret) |

Non-secret constants (API base URLs, `EXTERNAL_IPV4_ADDON_ID`, `SITE_NAME`, `BOT_USERNAME`,
pricing/markup) are left in place — adjust as needed.

---

## Install & run

```bash
# 1. Place the project under your web root
cd /var/www && git clone <repo> veneko && cd veneko   # or extract the archive

# 2. Create the database and import the schema
mysql -u root -p -e "CREATE DATABASE dummiesvps CHARACTER SET utf8mb4;"
mysql -u root -p dummiesvps < data/schema.sql
mysql -u root -p dummiesvps < data/trigger.sql
# apply migrations as needed (see migrations/)

# 3. Fill in credentials
#    edit api/config.php — replace every CHANGE_ME value

# 4. Writable runtime dirs
chown -R www-data:www-data .
chmod -R 775 logs uploads

# 5. Serve with Apache (mod_php / php-fpm) or Nginx + PHP-FPM, document root = project root
```

App is then reachable at `http://localhost/` (or your domain).

### Quick checks
```bash
php -l api/config.php                          # syntax OK
curl -s http://localhost/api/plans/list.php    # public endpoint responds
```

---

## Background workers

**Daemons** (long-running loops — run under systemd or `nohup`):

| Script | Interval | Job |
|--------|----------|-----|
| `daemon/vps_expire_daemon.php` | ~60 s | suspend VPS past `expires_at` |
| `daemon/ip_value_daemon.php` | ~30 s | sync IP add-on values from provider |
| `daemon/transaction_expire_daemon.php` | — | expire stale pending transactions |

**Cron agents** (notifications):

| Script | Schedule | Job |
|--------|----------|-----|
| `agents/notify_expiring.php` | hourly (`0 * * * *`) | VPS expiry alerts (7/3/1 day) via Gotify |
| `agents/notify_metrics.php` | every 15 min (`*/15 * * * *`) | CPU/RAM/Disk threshold alerts |

See [`daemon/README.md`](daemon/README.md) and [`agents/README.md`](agents/README.md) for systemd
unit files and setup details.

---

## Telegram integration

- Bot: `@raw_host_bot` (token in `TOKEN_TELEGRAM`); support bot in `SUPPORT_TG_TOKEN`.
- **Login:** Telegram Web App posts auth data to `api/auth/telegram_callback.php`, which verifies the
  HMAC-SHA256 signature and links/creates the user.
- **Notifications:** `sendTelegramNotification()` (admin), `sendUserTelegramNotification()` (user DMs),
  and `notifySupportTelegram()` (support channel) — all defined in `api/config.php`.

---

## License

Proprietary / internal. Update this section with the project's actual license.
