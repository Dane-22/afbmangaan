# Android attendance implementation

The first Android version is in [`mobile_app`](mobile_app/README.md). It uses Flutter and Dart with the existing PHP/MySQL website as its server. The app is for admins and operators. Member and event editing remains on the web dashboard.

## Implemented flow

1. Sign in online with an existing account and select a church.
2. Download church-scoped events, active members, and current attendance.
3. Record attendance by member search or QR scan. Save each action in SQLite before attempting network sync.
4. Retry pending actions in the foreground and with an Android network-constrained WorkManager job. There is no offline recording time limit.
5. Return one result per action: applied, duplicate, conflict, or rejected. The UUID makes retries idempotent. Conflicting statuses remain visible for an admin to review online.

The server migration is [`mobile_schema.sql`](mobile_schema.sql). Mobile API endpoints are `mobile_login`, `mobile_catalog`, `mobile_sync`, `mobile_conflicts`, and `mobile_resolve` under `api/`. The original capture time is stored with accepted attendance. Authentication expires after one hour for syncing, while offline recording remains available after expiry.

## Verification and deployment

Flutter static analysis, unit tests, and a debug APK build have completed. PHP syntax checks and sync-rule tests have completed. The production database migration, HTTPS API catalog, and empty sync request have been validated. An actual attendance write, camera, background scheduling, and release signing still require validation; see the app README for setup and test instructions.
