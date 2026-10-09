import 'dart:io';

import 'package:afb_mangaan_mobile/local_store.dart';
import 'package:afb_mangaan_mobile/models.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:path/path.dart' as path;
import 'package:sqflite_common_ffi/sqflite_ffi.dart';

void main() {
  TestWidgetsFlutterBinding.ensureInitialized();
  sqfliteFfiInit();
  databaseFactory = databaseFactoryFfi;

  test('attendance queue survives closing and reopening its database', () async {
    final directory = await Directory.systemTemp.createTemp('afb-attendance-test-');
    final file = path.join(directory.path, 'offline.db');
    final first = LocalStore(databasePath: file);
    final action = PendingAction(
      clientId: '1fd108b9-038e-4bb2-8ba1-569d28741884',
      eventId: 12,
      memberId: 34,
      status: 'Present',
      method: 'QR Scan',
      occurredAtUtc: '2026-10-09T01:23:45.000Z',
    );
    try {
      await first.addAction(action);
      await first.close();
      final reopened = LocalStore(databasePath: file);
      try {
        final saved = await reopened.actions();
        expect(saved, hasLength(1));
        expect(saved.single.syncState, 'pending');
        expect(saved.single.toApi(), action.toApi());
      } finally {
        await reopened.close();
      }
    } finally {
      await first.close();
      final tempRoot = await Directory.systemTemp.resolveSymbolicLinks();
      final target = await directory.resolveSymbolicLinks();
      if (!path.isWithin(tempRoot, target) || !path.basename(target).startsWith('afb-attendance-test-')) {
        throw StateError('Refusing to remove a directory outside the test temp area');
      }
      await directory.delete(recursive: true);
    }
  });
}
