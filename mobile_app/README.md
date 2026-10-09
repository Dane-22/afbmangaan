# AFB Attendance for Android

Flutter app for admins and operators. It downloads church events, members, and current attendance while online. Attendance recorded by QR scan or manual selection is saved to SQLite first, so recording continues without a time limit while offline. The app retries pending records in the foreground and through an Android WorkManager job when a network becomes available.

## Server setup

1. Deploy the PHP/MySQL website at an HTTPS address that the Android device can reach.
2. Run `COMPOSER=composer.mobile.json composer install --no-dev --prefer-dist --no-interaction` in the website root. This installs the pinned JWT runtime needed by the mobile API. The website's older full Composer manifest has separate report dependencies and may require its own PHP compatibility work.
3. Apply [`../mobile_schema.sql`](../mobile_schema.sql) to the existing database.
4. Set `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASSWORD`, and a unique `JWT_SECRET` of at least 32 characters in the website's `.env` file. The secret is required for every mobile API request.
5. Ensure the PHP deployment exposes `api/mobile_login.php`, `api/mobile_catalog.php`, `api/mobile_sync.php`, `api/mobile_conflicts.php`, and `api/mobile_resolve.php`.

The app requires an existing active `admin` or `operator` account. Accounts marked for a password change must complete that step on the web dashboard first.

## Android development

1. Install Flutter and the Android SDK.
2. From `mobile_app`, run `flutter pub get` and `flutter run`.
3. Enter the root URL of the deployed PHP website, not its `/api` URL.

For a local WAMP server in the Android emulator, a debug build accepts `http://10.0.2.2/afb/afbmangaan`. Release builds require HTTPS. The device must have a screen lock configured; Android PIN, pattern, or biometrics unlock cached attendance. Camera permission is requested for QR scanning.

`flutter build apk --debug` creates a development APK. Configure a private Android release keystore before distributing a release APK or AAB; the generated project does not sign releases with the debug key.

## Sync behavior

- A local action has a UUID and original UTC capture time. It remains pending until the server acknowledges that UUID.
- Retries of an acknowledged UUID return the saved result without writing attendance again.
- The same status recorded elsewhere is treated as a duplicate. Different statuses are flagged for admin review; the current server result remains in place until an admin chooses a result.
- An admin can review conflicts in the app while online. The review records the admin's choice.
- A one-hour API token may expire while offline. Recording continues; the operator signs in again to sync. Pending records remain on the device across app restarts.
- A cancelled event, archived member, changed event date, or invalid device time is shown as an issue instead of being silently overwritten.
- Catalog data is a snapshot. The app shows its last download time, and the server validates every submission again during sync.

The existing website remains the authority for members, events, and attendance. The mobile database is a cache and durable outgoing queue. Signing out clears downloaded data and is blocked while unsynced actions remain.

## Verification

Run `flutter analyze`, `flutter test`, and `flutter build apk --debug` from this directory. Run `php tests/mobile_sync_rules_test.php` from the website root. A live end-to-end test also needs the MySQL migration, an HTTPS PHP deployment, and an Android device or emulator with screen lock configured.
