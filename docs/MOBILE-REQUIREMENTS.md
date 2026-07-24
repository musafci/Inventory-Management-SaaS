# Mobile app — requirements & sign-off

**Product:** Oneapp Inventory — Tenant Mobile App (iOS & Android)  
**Document type:** Requirements summary + acceptance criteria  
**Version:** 1.0.0  
**Last updated:** 2026-07-24  
**Status:** Ready for QA / beta

---

## 1. Purpose

This document defines **what the mobile app must deliver** for v1.0 and provides a **sign-off checklist** for product, QA, and engineering. Detailed test steps live in [MOBILE-TEST-PLAN.md](./MOBILE-TEST-PLAN.md).

---

## 2. Scope

### In scope

- Full **tenant** ERP functionality matching web (excluding platform super-admin)
- **iOS and Android** via Expo EAS
- **Offline-first** reads from SQLite cache + mutation outbox
- **Push notifications** (device registration, preferences, low-stock)
- **Stripe billing** via in-app browser (Checkout / Customer Portal)
- **Permission-gated** navigation from `GET /auth/me`

### Out of scope (v1.0)

- Platform admin portal (`/api/platform/v1/*`)
- Starting impersonation from mobile (display-only when active; **exit** via API when banner is shown)
- Native POS register mode
- Livewire / web UI parity pixel-for-pixel

---

## 3. Functional requirements

### 3.1 Authentication & session

| Req ID | Requirement | Acceptance criteria |
|--------|-------------|---------------------|
| FR-AUTH-1 | Login with email/password | OAuth tokens stored securely; navigates to app shell |
| FR-AUTH-2 | Register organization + owner | Creates org, user, trial; auto-login |
| FR-AUTH-3 | Forgot / reset password | API-integrated flows; no credential storage in logs |
| FR-AUTH-4 | Token refresh | Automatic on 401; logout if refresh fails |
| FR-AUTH-5 | Session management | List and revoke sessions via API |
| FR-AUTH-6 | Logout | Revokes token, clears local auth state |

### 3.2 Organization context

| Req ID | Requirement | Acceptance criteria |
|--------|-------------|---------------------|
| FR-ORG-1 | Multi-org membership | User sees all orgs from login/me |
| FR-ORG-2 | Org switcher | Updates permissions and scoped data |
| FR-ORG-3 | Impersonation banner | Visible when `impersonation.active` in `/auth/me`; **Exit** calls `POST /auth/impersonation/exit` |
| FR-ORG-4 | Tenant header | `X-Organization-Id` on all tenant API calls |

### 3.3 Inventory

| Req ID | Requirement | Acceptance criteria |
|--------|-------------|---------------------|
| FR-INV-1 | Products CRUD | Online + cached reads |
| FR-INV-2 | Categories, units, warehouses CRUD | Full API parity |
| FR-INV-3 | Stock levels & movements | List + create adjustments |
| FR-INV-4 | CSV product import | Document picker upload |
| FR-INV-5 | Empty-state guidance | In-app links to create prerequisites (category, unit, warehouse) |

### 3.4 Purchasing & sales

| Req ID | Requirement | Acceptance criteria |
|--------|-------------|---------------------|
| FR-ORD-1 | Suppliers & customers CRUD | Including CSV import |
| FR-ORD-2 | Purchase order lifecycle | Draft → send → receive → pay → cancel |
| FR-ORD-3 | Sales order lifecycle | Draft → confirm → fulfill → deliver → pay → refund |
| FR-ORD-4 | Draft order editing | Edit screen for draft PO/SO |
| FR-ORD-5 | Payments list/detail | Read-only payment history |
| FR-ORD-6 | Order print/share | Authenticated HTML via API + system share sheet |
| FR-ORD-7 | Offline order actions | Queue create + lifecycle; dependency-aware outbox |
| FR-ORD-8 | Delete draft PO/SO | Delete action on list for permitted users |
| FR-ORD-9 | Receive / fulfill | Multi-line qty selection + notes modal (matches web) |
| FR-ORD-10 | Pay / refund | Amount, method, reference, note; refund return items |

### 3.5 Reports & dashboard

| Req ID | Requirement | Acceptance criteria |
|--------|-------------|---------------------|
| FR-RPT-1 | Dashboard | KPI cards + recent orders + low-stock widget + quick actions |
| FR-RPT-2 | Inventory/sales/purchase reports | Permission-gated; **warehouse filter** on stock valuation & low stock |
| FR-RPT-3 | Report exports | Queue, poll, download/share CSV |

### 3.6 Settings & compliance

| Req ID | Requirement | Acceptance criteria |
|--------|-------------|---------------------|
| FR-SET-1 | Organization settings | View/update org profile |
| FR-SET-2 | Billing | Stripe URLs via `expo-web-browser`; **monthly and yearly** checkout per plan |
| FR-SET-3 | Team & roles | Member invite/remove/**edit role** |
| FR-SET-4 | GDPR | Data export + deletion request flows |
| FR-SET-5 | Notification preferences | Per-org push toggles |
| FR-SET-6 | Sync status | Pending outbox, manual sync, conflict UI |

---

## 4. Non-functional requirements

| Req ID | Category | Requirement |
|--------|----------|-------------|
| NFR-1 | Security | Tokens in SecureStore (native); HTTPS only |
| NFR-2 | Offline | Core catalog and orders readable offline after sync |
| NFR-3 | Performance | List screens use tuned FlatList defaults |
| NFR-4 | Accessibility | Key flows have accessibility labels / testIDs |
| NFR-5 | Testing | Jest unit tests + Maestro smoke suite |
| NFR-6 | Release | EAS profiles for dev, preview, production |
| NFR-7 | Documentation | Planning, release, test plan, and this requirements doc |

---

## 5. Implementation status (v1.0)

| Area | Status | Notes |
|------|--------|-------|
| Auth (login, register, reset, sessions) | ✅ Complete | |
| Dashboard & reports | ✅ Complete | Widgets, warehouse filters, quick actions |
| Inventory (products, categories, units, warehouses, stock) | ✅ Complete | |
| Purchasing & sales lifecycles | ✅ Complete | Multi-line receive/fulfill, pay/refund modals, list delete |
| Payments | ✅ Complete | |
| Settings, billing, team, roles, privacy | ✅ Complete | Team edit, monthly/yearly billing |
| CSV import | ✅ Complete | Products, customers, suppliers |
| Offline outbox + background sync | ✅ Complete | Native background fetch |
| Push notifications | ✅ Complete | Registration + preferences |
| Print/share orders | ✅ Complete | API + share sheet |
| Hub UI (HubCard) | ✅ Complete | All hub screens |
| List performance (OptimizedFlatList) | ✅ Complete | All list screens |
| Maestro E2E | ✅ Complete | login, inventory, purchasing, sales, settings, all |
| Store submit IDs | ⚠️ Manual | Replace `eas.json` / `app.json` placeholders before submit |

---

## 6. Dependencies

| Dependency | Owner | Required for |
|------------|-------|--------------|
| Laravel API v1 | Backend | All features |
| Passport OAuth | Backend | Auth |
| Demo/staging environment | DevOps | QA |
| Apple Developer + Play Console | Product | Store release |
| EAS project ID | Mobile lead | OTA + builds |
| Privacy policy URL (production) | Legal/Product | Store listing |

---

## 7. Acceptance & sign-off

### 7.1 Entry criteria for QA

- [ ] Staging API deployed and reachable from test devices
- [ ] `php artisan app:setup` demo org available
- [ ] Mobile `.env` points to staging API
- [ ] EAS preview build installed on iOS + Android

### 7.2 Exit criteria (v1.0 release)

- [ ] All **P0** cases in [MOBILE-TEST-PLAN.md](./MOBILE-TEST-PLAN.md) pass
- [ ] `npm run typecheck` and `npm test` pass in CI
- [ ] Maestro `smoke/all.yaml` passes on release candidate
- [ ] No open **P0/P1** defects
- [ ] Store assets and privacy URL finalized
- [ ] `eas.json` submit placeholders replaced with real IDs

### 7.3 Sign-off

| Role | Name | Signature | Date |
|------|------|-----------|------|
| Product owner | | | |
| QA lead | | | |
| Mobile engineering | | | |
| Backend engineering | | | |

---

## 8. Post-release

- Tag mobile releases independently from Laravel deploys
- Re-run Maestro smoke on staging after each mobile RC
- Coordinate breaking API changes with optional min-app-version header (future)
- Collect beta feedback via TestFlight / Play Internal before production promotion

---

## 9. References

- [MOBILE-PLANNING.md](./MOBILE-PLANNING.md) — Architecture and phased roadmap
- [MOBILE-RELEASE.md](./MOBILE-RELEASE.md) — EAS build and store submission
- [MOBILE-TEST-PLAN.md](./MOBILE-TEST-PLAN.md) — Detailed test cases
- [GETTING-STARTED.md](./GETTING-STARTED.md) — Backend setup
