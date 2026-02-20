# Sift for WooCommerce — Fraud Detection Plugin

WooCommerce plugin integrating Sift Science API for real-time fraud detection.
Used on WooCommerce.com marketplace.

**This is the standalone repo.** A built copy is deployed to `woocommerce.com/plugins/sift-for-woocommerce/` (no tests/bin). The sidecar plugin that handles WC.com-specific decision responses lives separately at `woocommerce.com/plugins/wccom-plugins/sift-for-woocommerce-sidecar/`.

## Project Knowledge

### Directory Structure

```
sift-for-woocommerce/
├── sift-for-woocommerce.php    # Root plugin file (WP plugin header)
├── src/
│   ├── sift-for-woocommerce.php  # Main class (singleton) — DIFFERENT file, same name
│   └── inc/
│       ├── sift-events/          # Event tracking to Sift (hook handlers, normalizers)
│       ├── sift-decisions/       # Fraud decision handling + webhook receiver
│       ├── payment-gateways/     # Gateway integrations — conditional via class_exists()
│       │   └── load.php          # Loads at woocommerce_init priority 1000
│       ├── rest-api.php          # Custom REST endpoints (HMAC auth)
│       └── wc-admin-settings-tab.php
├── tests/                        # PHPUnit tests
└── bin/test-payment-gateway/     # Test gateway plugin (auto-approves all)
```

### Key Concepts

**Event System**: Hooks into WooCommerce actions and sends normalized events to Sift API. Events queued async via `async_sift_for_woocommerce_send_event` action. Each event type can be enabled/disabled in settings.

**Decision System**: Two inbound paths for fraud decisions:
- **Sift Webhook**: `GET /sift-for-woocommerce/v1/decision/` — signature auth
- **Manual API**: `GET|POST /fraud/users/{id}/decision` — HMAC auth

Decisions trigger `sift_for_woocommerce_{decision_type}_payment_abuse` hooks handled by the sidecar plugin or custom code. Decision types: `looks_good`, `likely_fraud_keep_purchases`, `likely_fraud_refundno_renew`, `block_wo_review`, `fraud`, `trust_list`, `not_likely_fraud`.

**Gateway Integration**: Filter-based — each gateway registers filters following the pattern `sift_for_woocommerce_{gateway}_{property}` (e.g., `sift_for_woocommerce_stripe_card_last4`). Loaded conditionally via `class_exists()` in `load.php`. Supported: Stripe, WooPayments, PayPal Commerce Platform, Transact.

**Sidecar Plugin**: `do_action('sift_for_woocommerce_load_sidecar_plugin')` — separate plugin extends decision handling. Used on WooCommerce.com via `sift-for-woocommerce-sidecar`.

## Commands

```
npm start                          # Start wp-env (may need retries)
npm stop                           # Stop wp-env
npm test                           # PHPUnit via wp-env
npm test -- --filter=TestName      # Specific test
npm lint                           # Lint package.json + README only (NOT PHP)
composer run-script lint:php       # PHPCS check (PHP)
composer run-script format:php     # PHPCS auto-fix (PHP)
```

## Conventions

- **Standards**: Team51 PHPCS (`a8cteam51/team51-configs`)
- **Namespace**: `Sift_For_WooCommerce\` — PSR-4 from `src/`
- **Text domain**: `sift-for-woocommerce`
- **PHP**: >=8.0, WordPress >=6.2, WooCommerce >=8.6

## Common Pitfalls

1. **CRITICAL: Two completely separate auth mechanisms** — Sift webhooks use `X-Sift-Science-Signature` header validated against a WP option. The REST API uses HMAC with `SIFT_FOR_WOOCOMMERCE_API_SECRET` constant. These are unrelated systems — don't conflate them.

2. **Events are async** — Events queue via WP scheduled actions, not sent synchronously. Don't expect immediate Sift API calls.

3. **WC session handlers differ** — Standard `WC_Session_Handler` has `get_customer_unique_id()`, but the Store API's `SessionHandler` (WooCommerce Blocks) does not. Always use the `Events::get_session_id()` helper instead of calling session methods directly.

## System Map

WooCommerce Events → Sift API → Sift Decision → Webhook/API → Sidecar Plugin → Automated Action

| Layer | Role |
|-------|------|
| Event Tracking | Captures WC actions, normalizes, queues to Sift |
| Sift API | External fraud scoring (not in this repo) |
| Decision Handling | Receives fraud decisions via webhook or REST API |
| Sidecar Plugin | WC.com-specific response to decisions |

## Related Systems

| System | Repo | Location |
|--------|------|----------|
| Sift SDK | wpcom | `wp-content/lib/siftscience/` |
| Billingdaddy Fraud | wpcom | `wp-content/lib/billingdaddy/src/fraud/` |
| Sidecar | woocommerce.com | `plugins/wccom-plugins/sift-for-woocommerce-sidecar/` |
| Sidecar Tests | woocommerce.com | `tests/integration/tests/sift-for-woocommerce-sidecar/` |
