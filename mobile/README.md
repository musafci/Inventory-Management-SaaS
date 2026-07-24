# Oneapp Mobile (Expo)

React Native tenant app for Inventory Management SaaS.

## Prerequisites

- Node.js 20+
- Expo Go or EAS dev client on a device/simulator
- Laravel API running (see [../docs/GETTING-STARTED.md](../docs/GETTING-STARTED.md))

## Setup

```bash
cd mobile
cp .env.example .env
npm install
npm start
```

Configure `EXPO_PUBLIC_API_URL` in `.env`:

| Environment | URL |
|-------------|-----|
| iOS Simulator | `http://localhost:8000/api` |
| Android Emulator | `http://10.0.2.2:8000/api` |
| Physical device | `http://<your-computer-ip>:8000/api` |

## Features (Phases 0–5)

- Auth (login, register, forgot/reset password, sessions), org switcher, permission-gated tabs
- Products, categories, units, warehouses, stock, offline sync + outbox
- Suppliers, customers, purchase/sales orders (full lifecycle + draft edit), payments
- Reports, dashboard, settings, billing, CSV import
- Push notifications, notification preferences, order print/share
- Maestro E2E smoke suite, Jest unit tests, EAS build profiles

## Documentation

| Doc | Purpose |
|-----|---------|
| [MOBILE-PLANNING.md](../docs/MOBILE-PLANNING.md) | Architecture & roadmap |
| [MOBILE-REQUIREMENTS.md](../docs/MOBILE-REQUIREMENTS.md) | Requirements & sign-off |
| [MOBILE-TEST-PLAN.md](../docs/MOBILE-TEST-PLAN.md) | Full QA test matrix |
| [MOBILE-RELEASE.md](../docs/MOBILE-RELEASE.md) | EAS build & store submission |

## Demo login

After `php artisan app:setup`:

```
Email: owner@demo.test
Password: password123
```

## Scripts

```bash
npm start          # Expo dev server
npm run android    # Open on Android
npm run ios        # Open on iOS (macOS)
npm test           # Jest unit tests
npx tsc --noEmit   # Typecheck
```

## E2E (Maestro)

```bash
maestro test .maestro/smoke/all.yaml
```

Individual flows: `login`, `inventory`, `purchasing`, `sales`, `settings`.

## Store release

See [../docs/MOBILE-RELEASE.md](../docs/MOBILE-RELEASE.md) for EAS build, TestFlight, and Play Store submission.

## Architecture

```
app/                 Expo Router screens
src/api/             HTTP client + API modules
src/auth/            AuthContext + secure storage
src/db/              SQLite cache + outbox
src/sync/            Pull sync + background fetch
components/          Shared UI
.maestro/            E2E smoke flows
```

See [../docs/MOBILE-PLANNING.md](../docs/MOBILE-PLANNING.md) for the full roadmap.
