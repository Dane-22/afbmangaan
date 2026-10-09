# Android attendance event visibility: investigation and implementation plan

Date: 2026-10-09

Status: Proposed. Investigation and documentation only; no application changes, builds, pushes, or production deployment made for this issue.

## Confirmed cause

The two Android pages use different event sources and selection rules:

| Page | Source | Event selection |
| --- | --- | --- |
| Events | `api/mobile_portal.php` via `portalData` | Church events across all dates and statuses; currently capped at 1,000 |
| Attendance | `api/mobile_catalog.php` saved to SQLite | Upcoming, Ongoing, or Completed events whose **start date** is within the last 30 days or in the future |
| Web Attendance | `attendance.php` calling `getEvents(['status' => null])` | Church events across dates and statuses |

The Events screenshot shows February 5–26, 2026. On October 9, 2026, those start dates fall outside the attendance catalog's 30-day window. Even the February event marked Ongoing is excluded by its date. An event spanning into the current date can also be excluded if it started more than 30 days ago.

The relevant catalog predicate is:

```sql
status IN ('Upcoming', 'Ongoing', 'Completed')
AND start_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
```

`LocalStore.replaceCatalog()` successfully saves an empty event list and the download timestamp. `AppController.sync()` then reports “Up to date.” The Attendance page displays “No events downloaded. Connect and sync before going offline” whenever the local list is empty, including a successful empty response. This is misleading.

This is separate from offline sign-in duration. Removing the previously discussed eight-hour limit does not affect this SQL date filter. Repeatedly pressing Sync will not bring these February events into the current catalog.

Evidence comes from the screenshots and the deployed commit's source. No fresh production database inspection was performed for this investigation; the temporary deployment SSH key was already revoked.

## Intended behavior

1. Admins and operators can find the same church event IDs from Events and Attendance with matching filters, including historical events.
2. An event's visibility is separate from whether attendance can be recorded for it.
3. Historical events open for review; existing capture-date restrictions remain. Merely displaying a February event does not enable recording attendance for February today.
4. Cancelled and Archived events can appear when selected through the corresponding filters, with recording disabled and the reason shown.
5. Successfully downloaded data remains available offline, and pending attendance survives refreshes and app updates.
6. Viewers retain their existing dashboard/report permissions.

## Recommended implementation

### 1. Unify event discovery

- Extract a shared church-scoped event query used by portal event discovery and the attendance catalog.
- Remove the hard-coded 30-day **event visibility** restriction. Use the same status/date filters and ordering on both pages.
- Include all statuses for discovery, and label recording eligibility separately. Use stable ordering by date, time, and ID.
- Add pagination for event metadata so history is reachable without an arbitrary hidden 1,000-event ceiling.
- Define a versioned catalog contract for the updated client; preserve the existing contract for older installed APKs during rollout.
- Use explicit Asia/Manila calendar dates for event eligibility. UTC remains the storage/transport convention for attendance capture timestamps.

### 2. Keep attendance records complete for the selected event

- Do not remove only the event date filter while leaving historical attendance records excluded. That would display old events with existing records falsely shown as “Not recorded.”
- Download church-scoped attendance state for a selected historical event, using a versioned event-detail endpoint or explicit event ID parameter.
- Keep automatic attendance downloads focused on recent/active events; allow deliberate historical downloads. Show which events have downloaded member and attendance details.
- Display “Attendance details not downloaded” when metadata exists without details. Offer “Download for offline use” online; keep that distinction after restart.
- Only mark a download complete after all pages for that event are saved successfully. Failed or partial downloads retain the last complete snapshot.
- Preserve the `actions` queue and conflict records. A historical download must not overwrite pending local actions or discard their original server baseline.

### 3. Improve Attendance navigation

- Add event search and status/date filters consistent with Events, plus an “Available offline” filter.
- Pass the event ID from an Events card's Attendance action. Currently that action opens the generic Attendance page and loses the selected event.
- Resolve the selected event from the shared cache, downloading required details online when needed.
- Use the refreshed event status/date when rendering an open attendance screen rather than retaining an obsolete event object.
- Keep cancelled/archived events read-only, and explain why an event cannot accept attendance.

### 4. Correct empty and sync states

Use separate states and actionable messages:

| State | Proposed message/action |
| --- | --- |
| No catalog has ever completed | “Download events for offline attendance.” — Download |
| First download is running | “Downloading events…” |
| Successful catalog with no church events | “No events exist for this church.” — Create event, when authorized |
| Filters match no events | “No events match these filters.” — Clear filters |
| Event metadata exists, details absent | “Download attendance details to use this event offline.” — Download |
| Offline with a saved snapshot | Show saved events and last successful download time |
| Refresh failed with saved data | Keep saved events visible; show failure and Retry |
| Token expired | Keep offline recording available under the existing rules; request online sign-in for downloads/sync |

Report counts explicitly, for example “Synced: 5 events; 2 available offline.” A successful zero-event response should not claim that connecting again will solve it.

### 5. Keep recording rules consistent without breaking delayed sync

The investigation also found a related inconsistency: web controls require Ongoing status, while native recording mostly checks the event date and excludes Cancelled; bulk marking additionally excludes Archived. The mobile sync endpoint currently excludes Cancelled but not Archived.

- Centralize current recording eligibility and explain it consistently in manual, QR, and bulk flows.
- Do not silently alter event dates or statuses to make the screenshots' February events recordable.
- Decide the Ongoing-only rule explicitly during discussion; do not broaden historical recording as part of this visibility fix.
- Keep delayed offline uploads distinct from new captures. An event becoming Completed after a valid offline capture must not automatically invalidate that capture. Preserve capture-time validation, duplicate handling, and admin conflict review.
- Define archived-event sync behavior explicitly and test it before changing server rejection rules.
- Keep offline attendance entry available without an eight-hour timeout as previously agreed.

## Verification and acceptance criteria

### API integration checks

- Seed February events and use a fixed October reference date: both event discovery paths return the same IDs for the same filters.
- Cover Upcoming, Ongoing, Completed, Cancelled, and Archived events.
- Include an event starting over 30 days ago but ending today.
- Verify historical Present/Absent records are returned with historical event detail downloads.
- Verify church isolation, viewer restrictions, pagination, stable ordering, and Manila midnight boundaries.
- Verify fresh invalid-date recording is blocked and valid delayed captures still follow the existing sync/conflict policy.

### Flutter and SQLite checks

- Successful zero-event download displays the correct empty state.
- Offline/no-download, failed refresh, expired token, and filters with no results display distinct messages.
- Events → Attendance opens the intended event ID.
- Partial historical downloads roll back; completed downloads persist after restart.
- Cached historical records never become false “Not recorded” entries merely because data has not been downloaded.
- Refreshes preserve pending UUIDs, capture timestamps, baseline values, and conflict outcomes.
- Cancelled/archived recording controls are disabled with a clear explanation; open screens reflect refreshed metadata.

### Device check

Reproduce the screenshots on an Android device: the same five February events are discoverable in Attendance, historical detail availability is clear, and recording remains disabled outside valid event dates. Verify downloaded details offline and verify the APK updates the existing installation without uninstalling.

## Rollout after approval

1. Implement the versioned API contract and shared query with integration tests.
2. Implement shared discovery/cache metadata, per-event detail downloads, navigation, and UI states.
3. Run PHP checks, isolated API integration tests, Flutter analysis/tests, and an Android build.
4. Review the reproduced screens and offline behavior on a device.
5. Push the approved changes, back up production, deploy the compatible backend, and run read-only production checks.
6. Build the next APK with a higher version code and the existing signing certificate; publish its verified checksum and download link.

No production records or event schedules should be edited to conceal this mismatch. No database migration is expected unless the approved implementation introduces persistent server-side download metadata; local cache metadata changes may require a SQLite schema upgrade.
