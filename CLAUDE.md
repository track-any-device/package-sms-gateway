# package-sms-gateway — AI Instructions

This is the **SMS HTTP gateway client** for the Track Any Device platform.
Packagist: `track-any-device/sms-gateway` | Namespace: `TrackAnyDevice\SmsGateway\`

This is the lowest-level TAD package — it has no dependencies on other TAD packages.
It is the foundation for `package-core` (which depends on it for OTP delivery).

Read this file before making any change.

---

## Platform-Wide Rules

These three rules apply in every repository under the `track-any-device` organisation.

**Cross-repo changes: file a GitHub issue first.**
If a task in this repository requires a change in another package or server app — stop. Open a
GitHub issue in the target repository describing exactly what is needed and why. Reference that
issue number in your commit message (`ref track-any-device/{repo}#{n}`). Do not directly edit
files in another repository. When picking up a cross-repo issue, run Claude locally inside that
repository's working directory and work only within its scope.

**Release order: packages before server apps.**
This is the first package in the release chain. Tag here before any downstream package bumps
their constraint. Order: `package-sms-gateway → package-core → all others → server apps`.

**Database layer lives in `package-core` only.**
This package has no models or migrations — it is a pure HTTP client library.
Do not add Eloquent models or migrations here.

---

## Rule 1 — Plan before implementing

Before writing any code, ask clarifying questions. Present a plan and get explicit agreement.
Only begin once the approach is confirmed.

---

## What lives in this package

| Class | Purpose |
|---|---|
| `SmsGatewayContract` | Interface — send SMS, read inbox, delete message, get status |
| `SmsGatewayService` | HTTP client implementation (wraps the on-premise gateway REST API) |
| `SmsGatewayServiceProvider` | Binds the contract in the container |
| `FakeSmsGateway` | In-memory fake for testing |

---

## Rule 2 — Code to the contract, not the implementation

All callers (in `package-core` and server apps) must type-hint `SmsGatewayContract`, not
`SmsGatewayService`. This allows swapping to Twilio or another driver via the container binding.

If a new SMS provider is needed, add a new implementation of `SmsGatewayContract` — do not
modify `SmsGatewayService` to add conditional provider logic.

---

## Rule 3 — No TAD package dependencies

This package must remain a standalone HTTP client library. Its only allowed PHP dependency is
`laravel/framework`. Never add `track-any-device/core` or any other TAD package as a dependency.

---

## Versioning

Tags are created automatically on merge to `main`. Default bump is `patch`.
Include `#minor` or `#major` in the commit message to trigger a larger bump.
