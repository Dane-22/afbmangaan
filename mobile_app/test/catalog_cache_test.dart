import 'package:afb_mangaan_mobile/local_store.dart';
import 'package:afb_mangaan_mobile/models.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:sqflite_common_ffi/sqflite_ffi.dart';

Map<String, dynamic> event(int id, String date, {String status = 'Completed'}) => {'id': id, 'event_name': 'Event $id', 'start_date': date, 'status': status};
Map<String, dynamic> catalog({required List<Map<String, dynamic>> events, List<int> downloaded = const [], List<Map<String, dynamic>> records = const []}) => {'version': 2, 'events': events, 'members': [{'id': 1, 'fullname': 'Member'}], 'attendance': records, 'downloaded_event_ids': downloaded, 'fetched_at_utc': '2026-10-09T08:00:00Z'};

void main() {
  TestWidgetsFlutterBinding.ensureInitialized();
  sqfliteFfiInit();
  databaseFactory = databaseFactoryFfi;
  test('metadata refresh preserves historical detail and pending capture baseline', () async {
    final store = LocalStore(databasePath: inMemoryDatabasePath);
    final historical = event(1, '2026-02-05');
    final recent = event(2, '2026-10-09', status: 'Ongoing');
    final action = PendingAction(clientId: 'pending', eventId: 1, memberId: 1, status: 'Absent', method: 'Manual', occurredAtUtc: '2026-02-05T01:00:00Z', baseStatus: 'Present', baseLogTimeUtc: '2026-02-05T00:30:00Z');
    try {
      await store.replaceCatalog(catalog(events: [historical, recent], downloaded: [2]));
      expect((await store.events()).map((item) => item.id), [2, 1]);
      expect((await store.detailDownloads()).containsKey(1), isFalse);
      await store.addAction(action);
      await store.replaceCatalog(catalog(events: [historical], downloaded: [1], records: [{'event_id': 1, 'attendee_id': 1, 'status': 'Present', 'log_time_utc': '2026-02-05T00:30:00Z'}]), eventDetail: true);
      await store.replaceCatalog(catalog(events: [historical, recent], downloaded: [2]));
      expect((await store.attendance()).single.status, 'Present');
      expect((await store.detailDownloads()).keys, containsAll([1, 2]));
      expect((await store.actions()).single.toApi(), action.toApi());
      final invalid = catalog(events: [historical, recent], downloaded: [1]);
      invalid['members'] = [{'id': 1, 'fullname': 'Duplicate'}, {'id': 1, 'fullname': 'Duplicate'}];
      await expectLater(store.replaceCatalog(invalid), throwsA(isA<DatabaseException>()));
      expect((await store.attendance()).single.status, 'Present');
      expect((await store.actions()).single.syncState, 'pending');
    } finally { await store.close(); }
  });
  test('legacy snapshots remain usable during the cache upgrade', () async {
    final store = LocalStore(databasePath: inMemoryDatabasePath);
    try {
      final legacy = catalog(events: [event(1, '2026-10-09')])..remove('version');
      await store.replaceCatalog(legacy);
      expect(await store.hasVersion2Catalog(), isFalse);
      expect((await store.detailDownloads()).containsKey(1), isTrue);
    } finally { await store.close(); }
  });
  test('date eligibility uses Manila boundaries and blocks cancelled or archived events', () {
    final ongoing = MobileEvent.fromJson(event(1, '2026-10-09', status: 'Ongoing'));
    expect(ongoing.canRecordAt(DateTime.parse('2026-10-08T15:59:59Z')), isFalse);
    expect(ongoing.canRecordAt(DateTime.parse('2026-10-08T16:00:00Z')), isTrue);
    expect(ongoing.canRecordAt(DateTime.parse('2026-10-09T16:00:00Z')), isFalse);
    for (final status in ['Archived', 'Cancelled']) {
      expect(MobileEvent.fromJson(event(1, '2026-10-09', status: status)).canRecordAt(DateTime.parse('2026-10-09T01:00:00Z')), isFalse);
    }
  });
}
