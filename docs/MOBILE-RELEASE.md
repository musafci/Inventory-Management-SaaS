# Mobile app release guide

This document covers **Phase 5** store distribution for the Expo app in `mobile/`.

## Prerequisites

- [Expo account](https://expo.dev) and EAS CLI: `npm i -g eas-cli`
- Apple Developer Program (iOS) and Google Play Console (Android)
- Staging/production API URL configured in EAS secrets

## Environment

Set production API URL as an EAS secret (do not commit):

```bash
cd mobile
eas secret:create --name EXPO_PUBLIC_API_URL --value https://api.yourdomain.com/api --scope project
```

Local development uses `.env` — see `mobile/.env.example`.

## Build profiles (`eas.json`)

| Profile | Purpose |
|---------|---------|
| `development` | Dev client with internal distribution |
| `preview` | Internal QA (APK on Android) |
| `production` | Store release builds (auto-increment version) |

```bash
eas build --platform ios --profile preview
eas build --platform android --profile preview
eas build --platform all --profile production
```

## Submit to stores

1. Replace placeholder values in `eas.json` (`ascAppId`, Google service account path).
2. Ensure `app.json` has correct `bundleIdentifier` / `package` and privacy policy URL.
3. Run:

```bash
eas submit --platform ios --profile production
eas submit --platform android --profile production
```

## Store assets checklist

- [ ] App icon (1024×1024) — `mobile/assets/images/icon.png`
- [ ] Android adaptive icons — `mobile/assets/images/android-icon-*.png`
- [ ] Splash screen — configured in `app.json` plugins
- [ ] Screenshots (6.7", 6.5", iPad, phone/tablet Android)
- [ ] Privacy policy URL (linked in store listing and `app.json` extra)
- [ ] App description, keywords, support URL

## E2E smoke tests (Maestro)

Install [Maestro](https://maestro.mobile.dev), start the app on a simulator/device, then:

```bash
cd mobile
maestro test .maestro/smoke/all.yaml
```

Individual flows:

| Flow | Coverage |
|------|----------|
| `smoke/login.yaml` | Login |
| `smoke/inventory.yaml` | Inventory hub, products, sync |
| `smoke/purchasing.yaml` | Suppliers, purchase orders |
| `smoke/sales.yaml` | Customers, sales orders |
| `smoke/settings.yaml` | Reports, settings, sessions |

Override credentials:

```bash
maestro test -e LOGIN_EMAIL=user@test.com -e LOGIN_PASSWORD=secret .maestro/smoke/all.yaml
```

Requires demo user from `php artisan app:setup` or your staging org.

See [MOBILE-TEST-PLAN.md](../docs/MOBILE-TEST-PLAN.md) for the full manual QA matrix.

## Unit tests

```bash
cd mobile
npm test
npx tsc --noEmit
```

## Beta rollout

1. **TestFlight (iOS)** — `preview` or `production` build → internal testers → external beta.
2. **Play Internal testing** — upload AAB via `eas submit` with `track: internal`.
3. Monitor crash reports and API errors before promoting to production tracks.

## Post-release

- Tag mobile releases separately from Laravel deploys.
- Coordinate breaking API changes with optional `X-Min-App-Version` header (future).
- Re-run Maestro against staging after each mobile release candidate.
