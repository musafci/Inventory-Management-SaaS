# Mobile app — test plan

**Product:** Oneapp Inventory (Expo tenant mobile app)  
**Version under test:** 1.0.0  
**Last updated:** 2026-07-24  
**Related:** [MOBILE-PLANNING.md](./MOBILE-PLANNING.md), [MOBILE-REQUIREMENTS.md](./MOBILE-REQUIREMENTS.md), [MOBILE-RELEASE.md](./MOBILE-RELEASE.md)

This document is the **master QA checklist** for end-to-end validation before store release. Use it for manual regression, staging sign-off, and to map automated coverage.

---

## 1. Test environment

| Item | Requirement |
|------|-------------|
| API | Laravel backend running (`php artisan serve` or staging URL) |
| Demo data | `php artisan app:setup` — owner `owner@demo.test` / `password123` |
| Mobile API URL | Set `EXPO_PUBLIC_API_URL` in `mobile/.env` |
| Platforms | iOS Simulator, Android Emulator, and at least one physical device each |
| Network modes | Online, airplane mode, flaky (toggle mid-action) |

### Automated smoke (Maestro)

```bash
cd mobile
maestro test .maestro/smoke/all.yaml
```

Individual flows: `login.yaml`, `inventory.yaml`, `purchasing.yaml`, `sales.yaml`, `settings.yaml`.

### Unit tests

```bash
cd mobile
npm test
npm run typecheck
```

---

## 2. Test roles & permissions

Run permission-sensitive cases with at least:

| Role | Purpose |
|------|---------|
| **Org Owner** | Full access — primary regression |
| **Read-only custom role** | Verify gated tabs, hidden create buttons |
| **Sales-only / Purchasing-only** | Tab visibility |

Verify `/auth/me` returns correct `permissions` after org switch.

---

## 3. Authentication

| ID | Case | Steps | Expected |
|----|------|-------|----------|
| AUTH-01 | Login success | Valid email/password | Lands on Dashboard; tabs visible per permissions |
| AUTH-02 | Login failure | Wrong password | Error message; stays on login |
| AUTH-03 | Register | New org + owner on register screen | Account created; logged in; default org selected |
| AUTH-04 | Forgot password | Submit email on forgot screen | Success message (no email enumeration leak) |
| AUTH-05 | Reset password | Token + email + new password | Redirect to login; can sign in with new password |
| AUTH-06 | Token refresh | Wait for access token expiry or force 401 | Silent refresh; session continues |
| AUTH-07 | Logout | More → Sign out | Tokens cleared; login screen |
| AUTH-08 | Sessions list | Settings → Active sessions | Lists devices; current session marked |
| AUTH-09 | Revoke other session | Revoke non-current session | Session removed from list |
| AUTH-10 | Revoke current session | Revoke this device | Signed out |
| AUTH-11 | Persist session | Kill app, reopen | Still authenticated |

---

## 4. Organization & impersonation

| ID | Case | Steps | Expected |
|----|------|-------|----------|
| ORG-01 | Org switcher | Switch org from header | Data refreshes; permissions update |
| ORG-02 | Org data isolation | Create product in org A; switch to org B | Product not visible in org B |
| ORG-03 | Impersonation banner | Login while platform admin impersonates | Banner visible with admin name |
| ORG-04 | Exit impersonation | Tap Exit on banner | Calls API; user logged out / session ends |
| ORG-05 | Local cache wipe on switch | Switch org after sync | Previous org SQLite rows not shown |

---

## 5. Inventory — catalog

| ID | Case | Steps | Expected |
|----|------|-------|----------|
| INV-01 | Products list | Inventory → Products | Paginated list; search works |
| INV-02 | Create product | New product with category + unit | Saved; appears in list |
| INV-03 | Edit product | Edit name, prices, active flag | Changes persisted |
| INV-04 | Empty catalog prompt | No categories/units → new product | Links to add category/unit in-app |
| INV-05 | Categories CRUD | List, create, edit, delete | Matches API |
| INV-06 | Units CRUD | List, create, edit, delete | Matches API |
| INV-07 | Warehouses CRUD | List, create, edit, delete, default flag | Matches API; used in orders |
| INV-08 | CSV import products | Document picker + upload | Import result shown; products sync |

---

## 6. Inventory — stock

| ID | Case | Steps | Expected |
|----|------|-------|----------|
| STK-01 | Stock levels list | Inventory → Stock levels | Quantities by warehouse/product |
| STK-02 | Stock movements list | Inventory → Stock movements | Ledger entries visible |
| STK-03 | Record adjustment (online) | New adjustment in/out | Movement created; stock updates |
| STK-04 | Record adjustment (offline) | Airplane mode → adjustment | Queued; syncs when online |
| STK-05 | No warehouse prompt | Delete all warehouses → new movement | Link to add warehouse |

---

## 7. Purchasing

| ID | Case | Steps | Expected |
|----|------|-------|----------|
| PUR-01 | Suppliers CRUD | Create, edit, delete | API parity |
| PUR-02 | Supplier CSV import | Import screen | Success/errors reported |
| PUR-03 | Create PO (draft) | New PO with supplier, warehouse, line | Draft created |
| PUR-04 | Edit draft PO | Detail → Edit draft | Fields update |
| PUR-05 | Send PO | Draft → Send | Status `sent` |
| PUR-06 | Receive PO | Partial/full receive (multi-line modal + notes) | Stock increases; status updates |
| PUR-07 | Pay PO | Pay with amount/method/reference/note | Payment recorded; amount due decreases |
| PUR-08 | Cancel PO | Cancel draft or sent | Status `cancelled` |
| PUR-09 | Delete draft PO | List → Delete (permitted) | Order removed |
| PUR-10 | PO print/share | Share/print on detail | HTML shared via system sheet |
| PUR-11 | PO offline create | Offline → create PO | Queued in outbox; created on sync |
| PUR-12 | PO offline send/receive | Queue lifecycle offline | Processes in dependency order |

---

## 8. Sales

| ID | Case | Steps | Expected |
|----|------|-------|----------|
| SAL-01 | Customers CRUD | Create, edit, delete | API parity |
| SAL-02 | Customer CSV import | Import screen | Success/errors reported |
| SAL-03 | Create SO (draft) | New SO | Draft created |
| SAL-04 | Edit draft SO | Detail → Edit draft | Fields update |
| SAL-05 | Confirm SO | Draft → Confirm | Status `confirmed` |
| SAL-06 | Fulfill SO | Fulfill (multi-line modal + notes) | Stock decreases |
| SAL-07 | Deliver SO | Deliver after fulfill | Status `delivered` |
| SAL-08 | Pay SO | Pay with amount/method/reference/note | Payment recorded |
| SAL-09 | Refund SO | Refund with return items when eligible | Refund recorded |
| SAL-10 | Cancel SO | Cancel draft/confirmed | Status `cancelled` |
| SAL-11 | Delete draft SO | List → Delete (permitted) | Order removed |
| SAL-12 | SO print/share | Share/print on detail | HTML shared |
| SAL-13 | SO offline pipeline | Offline confirm → fulfill → deliver | Outbox dependency order respected |

---

## 9. Payments

| ID | Case | Steps | Expected |
|----|------|-------|----------|
| PAY-01 | Payments list | Sales → Payments | Paginated history |
| PAY-02 | Payment detail | Tap payment | Amount, method, related order |

---

## 10. Reports & dashboard

| ID | Case | Steps | Expected |
|----|------|-------|----------|
| RPT-01 | Dashboard | Home tab | KPI cards, recent orders, low-stock widget, quick actions |
| RPT-02 | Stock valuation | Reports → Stock valuation | Totals + by warehouse; warehouse filter chips |
| RPT-03 | Low stock | Reports → Low stock | Products at/below reorder point; warehouse filter |
| RPT-04 | Sales summary | Reports → Sales summary | Status breakdown |
| RPT-05 | Purchase summary | Reports → Purchase summary | Status breakdown |
| RPT-06 | Report export | Queue export → poll → share CSV | File shareable |
| RPT-07 | Permission gate | Read-only role without report perms | Reports tab hidden or empty |

---

## 11. Settings

| ID | Case | Steps | Expected |
|----|------|-------|----------|
| SET-01 | Organization | View/edit name, contact fields | PATCH persisted |
| SET-02 | Billing | Monthly + yearly checkout; open portal | Stripe opens in in-app browser |
| SET-03 | Team | List members, invite, edit role, deactivate | Matches API |
| SET-04 | Roles | Create role, assign permissions, edit | Matches API |
| SET-05 | Privacy — export | Request data export | Export queued/downloadable |
| SET-06 | Privacy — deletion | Request deletion / cancel | API messages shown |
| SET-07 | Notifications | Toggle push preferences | Saved; push respects prefs |
| SET-08 | Sync status | View pending count, sync now | Pull + push run |
| SET-09 | Failed mutations | Force conflict → retry/dismiss | UI on sync screen works |
| SET-10 | Active sessions | List + revoke | See AUTH-08–10 |

---

## 12. Offline & sync

| ID | Case | Steps | Expected |
|----|------|-------|----------|
| SYNC-01 | Offline banner | Disable network | Banner visible |
| SYNC-02 | Read cache offline | Open products/stocks offline | Cached data shown |
| SYNC-03 | Outbox count | Queue mutations | Pending count increases |
| SYNC-04 | Manual sync | Sync now when online | Outbox drains; data fresh |
| SYNC-05 | Background sync | Background app (native) | Periodic sync runs |
| SYNC-06 | Conflict handling | Invalid mutation (e.g. delete used entity) | Failed entry with error; retry/dismiss |
| SYNC-07 | Idempotency | Duplicate PO create on flaky network | Single order on server |

---

## 13. Push notifications

| ID | Case | Steps | Expected |
|----|------|-------|----------|
| PUSH-01 | Register token | Login on physical device | Token POST to API |
| PUSH-02 | Low stock alert | Trigger low stock on server | Notification received (if enabled) |
| PUSH-03 | Tap notification | Tap push | Navigates to relevant screen |
| PUSH-04 | Unregister on logout | Logout | Token removed |

---

## 14. UX, performance, accessibility

| ID | Case | Steps | Expected |
|----|------|-------|----------|
| UX-01 | Tab navigation | All main tabs | Correct screens; permission-gated |
| UX-02 | Hub cards | Inventory, Sales, Purchasing, Reports, Settings | Consistent HubCard layout |
| UX-03 | List performance | Scroll 500+ products/orders | Smooth scroll (OptimizedFlatList) |
| UX-04 | Pull to refresh | List screens | Refetch without crash |
| UX-05 | Accessibility | VoiceOver/TalkBack on login + tabs | Labels on inputs and hub cards |
| UX-06 | Deep link reset | Open `oneapp://reset-password?email=&token=` | Reset screen pre-filled |

---

## 15. Security

| ID | Case | Expected |
|----|------|----------|
| SEC-01 | Tokens in SecureStore (native) | Not in plain AsyncStorage |
| SEC-02 | No platform admin routes | App cannot reach `/api/platform/v1` |
| SEC-03 | Org header on tenant routes | All tenant API calls send `X-Organization-Id` |
| SEC-04 | 403 on wrong org | Cannot access other org data by ID guessing |
| SEC-05 | Push payload | No sensitive PII in notification body |

---

## 16. Release & store (pre-submit)

| ID | Case | Expected |
|----|------|----------|
| REL-01 | EAS production build | iOS + Android build succeeds |
| REL-02 | Privacy policy URL | Valid URL in store listing + `app.json` |
| REL-03 | App icons & splash | Assets present per MOBILE-RELEASE.md |
| REL-04 | Maestro full suite | `smoke/all.yaml` passes on RC build |
| REL-05 | TestFlight / Play Internal | Beta install on real devices |

---

## 17. Sign-off matrix

| Module | Tester | Date | Pass/Fail | Notes |
|--------|--------|------|-----------|-------|
| Auth | | | | |
| Inventory | | | | |
| Purchasing | | | | |
| Sales | | | | |
| Reports | | | | |
| Settings | | | | |
| Offline/Sync | | | | |
| Push | | | | |
| Security | | | | |
| Release | | | | |

**Release approval:** Product owner + QA lead sign [MOBILE-REQUIREMENTS.md](./MOBILE-REQUIREMENTS.md) when all **P0** cases pass.

---

## 18. Priority legend

- **P0** — Must pass for v1.0 store release (all AUTH, ORG, core CRUD, order lifecycles, sync basics)
- **P1** — Should pass; minor UX issues acceptable with documented workaround
- **P2** — Nice to have (edge cases, performance on very large datasets)

Default: all cases in sections 3–13 are **P0** unless marked otherwise in test execution notes.
