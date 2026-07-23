# Oneapp Mobile (Expo)

React Native tenant app for Inventory Management SaaS — **Phase 0** foundation.

## Prerequisites

- Node.js 20+
- Expo Go app on a device, or Android/iOS simulator
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

## Phase 0 (current)

- [x] Expo + Expo Router (iOS / Android)
- [x] Login with Passport tokens (SecureStore)
- [x] Token refresh on 401
- [x] Organization switcher
- [x] Permission-gated tabs (from extended `/auth/me`)
- [x] Impersonation banner
- [ ] Products / inventory screens (Phase 1)
- [ ] Offline sync (Phase 1+)
- [ ] Push notifications (Phase 1+)

## Demo login

Use a tenant account from your local API, e.g. after `php artisan app:setup`:

```
Email: owner@demo.test (or register via API)
Password: password123
```

## Scripts

```bash
npm start          # Expo dev server
npm run android    # Open on Android
npm run ios        # Open on iOS (macOS)
```

## Architecture

```
app/                 Expo Router screens
src/api/             HTTP client + auth API
src/auth/            AuthContext + SecureStore
components/          Shared UI
```

See [../docs/MOBILE-PLANNING.md](../docs/MOBILE-PLANNING.md) for the full roadmap.
