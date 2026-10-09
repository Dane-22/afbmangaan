import 'package:afb_mangaan_mobile/models.dart';
import 'package:flutter_test/flutter_test.dart';

void main() {
  test('queued attendance retains its original ID and timestamp for retries', () {
    final action = PendingAction(
      clientId: '1fd108b9-038e-4bb2-8ba1-569d28741884',
      eventId: 12,
      memberId: 34,
      status: 'Present',
      method: 'QR Scan',
      occurredAtUtc: '2026-10-09T01:23:45.000Z',
    );
    final saved = PendingAction.fromDb(action.toDb());
    expect(saved.toApi(), action.toApi());
    expect(saved.toApi()['client_id'], action.clientId);
    expect(saved.toApi()['occurred_at_utc'], '2026-10-09T01:23:45.000Z');
  });
}
