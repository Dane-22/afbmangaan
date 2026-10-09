import 'dart:convert';

import 'package:path/path.dart' as path;
import 'package:sqflite/sqflite.dart';

import 'models.dart';

class LocalStore {
  final String? databasePath;
  LocalStore({this.databasePath});
  Database? _db;

  Future<Database> get db async {
    if (_db != null) return _db!;
    final file = databasePath ?? path.join(await getDatabasesPath(), 'afb_mobile.db');
    _db = await openDatabase(file, version: 1, onCreate: (database, _) async {
      await database.execute('CREATE TABLE metadata (key TEXT PRIMARY KEY, value TEXT NOT NULL)');
      await database.execute('CREATE TABLE events (id INTEGER PRIMARY KEY, data TEXT NOT NULL)');
      await database.execute('CREATE TABLE members (id INTEGER PRIMARY KEY, data TEXT NOT NULL)');
      await database.execute('CREATE TABLE attendance (event_id INTEGER NOT NULL, member_id INTEGER NOT NULL, status TEXT NOT NULL, log_time_utc TEXT, PRIMARY KEY(event_id, member_id))');
      await database.execute('CREATE TABLE actions (client_id TEXT PRIMARY KEY, event_id INTEGER NOT NULL, member_id INTEGER NOT NULL, status TEXT NOT NULL, method TEXT NOT NULL, occurred_at_utc TEXT NOT NULL, base_status TEXT, base_log_time_utc TEXT, sync_state TEXT NOT NULL, reason TEXT)');
      await database.execute('CREATE INDEX idx_actions_state ON actions(sync_state)');
    });
    return _db!;
  }

  Future<void> replaceCatalog(Map<String, dynamic> catalog) async {
    final database = await db;
    await database.transaction((tx) async {
      await tx.delete('events');
      await tx.delete('members');
      await tx.delete('attendance');
      for (final item in (catalog['events'] as List)) {
        final event = MobileEvent.fromJson(Map<String, dynamic>.from(item as Map));
        await tx.insert('events', {'id': event.id, 'data': jsonEncode(event.toJson())});
      }
      for (final item in (catalog['members'] as List)) {
        final member = MobileMember.fromJson(Map<String, dynamic>.from(item as Map));
        await tx.insert('members', {'id': member.id, 'data': jsonEncode(member.toJson())});
      }
      for (final item in (catalog['attendance'] as List)) {
        final state = AttendanceState.fromJson(Map<String, dynamic>.from(item as Map));
        await tx.insert('attendance', {'event_id': state.eventId, 'member_id': state.memberId, 'status': state.status, 'log_time_utc': state.logTimeUtc});
      }
      for (final item in (catalog['resolved_actions'] as List? ?? [])) {
        final resolved = Map<String, dynamic>.from(item as Map);
        await tx.update('actions', {'sync_state': resolved['result'], 'reason': resolved['reason']}, where: 'client_id = ?', whereArgs: [resolved['client_id']]);
      }
      await tx.insert('metadata', {'key': 'last_refresh', 'value': catalog['fetched_at_utc'] as String}, conflictAlgorithm: ConflictAlgorithm.replace);
    });
  }

  Future<List<MobileEvent>> events() async => (await (await db).query('events', orderBy: 'id DESC'))
      .map((row) => MobileEvent.fromJson(jsonDecode(row['data'] as String) as Map<String, dynamic>)).toList();

  Future<List<MobileMember>> members() async => (await (await db).query('members', orderBy: 'id'))
      .map((row) => MobileMember.fromJson(jsonDecode(row['data'] as String) as Map<String, dynamic>)).toList();

  Future<List<AttendanceState>> attendance() async => (await (await db).query('attendance'))
      .map((row) => AttendanceState(eventId: row['event_id'] as int, memberId: row['member_id'] as int, status: row['status'] as String, logTimeUtc: row['log_time_utc'] as String?)).toList();

  Future<List<PendingAction>> actions() async => (await (await db).query('actions', orderBy: 'occurred_at_utc DESC'))
      .map(PendingAction.fromDb).toList();

  Future<void> addAction(PendingAction action) async => (await db).insert('actions', action.toDb());

  Future<void> addActions(List<PendingAction> actions) async {
    final database = await db;
    await database.transaction((tx) async {
      for (final action in actions) { await tx.insert('actions', action.toDb()); }
    });
  }

  Future<void> setResult(String clientId, String result, String? reason) async {
    final database = await db;
    await database.transaction((tx) async {
      final rows = await tx.query('actions', where: 'client_id = ?', whereArgs: [clientId], limit: 1);
      if (rows.isEmpty) return;
      final action = PendingAction.fromDb(rows.first);
      await tx.update('actions', {'sync_state': result, 'reason': reason}, where: 'client_id = ?', whereArgs: [clientId]);
      if (result == 'applied' || result == 'duplicate') {
        await tx.insert('attendance', {
          'event_id': action.eventId, 'member_id': action.memberId,
          'status': action.status, 'log_time_utc': action.occurredAtUtc,
        }, conflictAlgorithm: ConflictAlgorithm.replace);
      }
    });
  }

  Future<String?> lastRefresh() async {
    final rows = await (await db).query('metadata', columns: ['value'], where: 'key = ?', whereArgs: ['last_refresh']);
    return rows.isEmpty ? null : rows.first['value'] as String;
  }

  Future<void> clear() async {
    final database = await db;
    await database.transaction((tx) async {
      for (final table in ['metadata', 'events', 'members', 'attendance', 'actions']) {
        await tx.delete(table);
      }
    });
  }

  Future<void> close() async {
    await _db?.close();
    _db = null;
  }
}
