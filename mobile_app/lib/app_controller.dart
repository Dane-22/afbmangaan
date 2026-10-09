import 'dart:async';
import 'dart:convert';

import 'package:flutter/foundation.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import 'package:uuid/uuid.dart';

import 'background_sync.dart';
import 'local_store.dart';
import 'mobile_api.dart';
import 'models.dart';

class AppController extends ChangeNotifier {
  final LocalStore store = LocalStore();
  final FlutterSecureStorage _secure = const FlutterSecureStorage();
  final Uuid _uuid = const Uuid();
  MobileApi? _api;
  Timer? _retryTimer;

  MobileUser? user;
  String? token;
  String? baseUrl;
  String? lastRefresh;
  String? message;
  bool busy = false;
  bool ready = false;
  bool needsSignIn = false;
  List<MobileEvent> events = [];
  List<MobileMember> members = [];
  List<AttendanceState> attendance = [];
  List<PendingAction> actions = [];

  int get pendingCount => actions.where((a) => a.syncState == 'pending').length;
  int get issueCount => actions.where((a) => a.syncState == 'conflict' || a.syncState == 'rejected').length;

  Future<void> initialize() async {
    try {
      baseUrl = await _secure.read(key: 'base_url');
      token = await _secure.read(key: 'token');
      final savedUser = await _secure.read(key: 'user');
      if (savedUser != null) user = MobileUser.fromJson(jsonDecode(savedUser) as Map<String, dynamic>);
      if (baseUrl != null) _api = MobileApi(baseUrl!);
      await reload();
      _retryTimer = Timer.periodic(const Duration(minutes: 1), (_) => sync(silent: true));
      if (pendingCount > 0) unawaited(scheduleMobileSync().catchError((_) {}));
      if (user != null && token != null) unawaited(sync(silent: true));
    } catch (e) {
      message = 'Could not open saved mobile data: $e';
    } finally {
      ready = true;
      notifyListeners();
    }
  }

  Future<void> reload() async {
    events = await store.events();
    members = await store.members();
    attendance = await store.attendance();
    actions = await store.actions();
    lastRefresh = await store.lastRefresh();
    notifyListeners();
  }

  Future<void> login(String url, String username, String password, String church) async {
    final parsed = Uri.tryParse(url.trim());
    if (parsed == null || !parsed.hasAuthority || (parsed.scheme != 'https' && !(kDebugMode && parsed.scheme == 'http' && ['localhost', '127.0.0.1', '10.0.2.2'].contains(parsed.host)))) {
      throw StateError('Use an HTTPS server URL (Android emulator may use http://10.0.2.2 in debug).');
    }
    busy = true;
    notifyListeners();
    try {
      final candidate = MobileApi(url.trim());
      final response = await candidate.login(username.trim(), password, church);
      final nextUser = MobileUser.fromJson(Map<String, dynamic>.from(response['user'] as Map));
      if (user != null && (nextUser.id != user!.id || nextUser.church != user!.church) && pendingCount > 0) {
        candidate.close();
        throw StateError('Sync pending entries under the current account before switching users.');
      }
      if (user != null && (nextUser.id != user!.id || nextUser.church != user!.church)) await store.clear();
      _api?.close();
      _api = candidate;
      baseUrl = url.trim();
      user = nextUser;
      token = response['token'] as String;
      needsSignIn = false;
      await _secure.write(key: 'base_url', value: baseUrl);
      await _secure.write(key: 'user', value: jsonEncode(user!.toJson()));
      await _secure.write(key: 'token', value: token);
      await reload();
      message = 'Signed in. Downloading latest data…';
      notifyListeners();
    } finally {
      busy = false;
      notifyListeners();
    }
    await sync(silent: true);
  }

  Future<void> record(MobileEvent event, MobileMember member, String status, String method) async {
    if (user == null) throw StateError('Sign in online once before recording attendance.');
    if (event.status == 'Cancelled') throw StateError('This event was cancelled in the last downloaded catalog.');
    final now = DateTime.now().toUtc();
    final manilaDate = now.add(const Duration(hours: 8)).toIso8601String().substring(0, 10);
    if (manilaDate.compareTo(event.startDate) < 0 || manilaDate.compareTo(event.endDate ?? event.startDate) > 0) {
      throw StateError('Attendance can only be recorded on the event date. Refresh data if the date changed.');
    }
    final base = attendance.where((a) => a.eventId == event.id && a.memberId == member.id).firstOrNull;
    final action = PendingAction(
      clientId: _uuid.v4(), eventId: event.id, memberId: member.id,
      status: status, method: method, occurredAtUtc: now.toIso8601String(),
      baseStatus: base?.status, baseLogTimeUtc: base?.logTimeUtc,
    );
    await store.addAction(action);
    await reload();
    unawaited(scheduleMobileSync().catchError((_) {}));
    message = '$status queued for ${member.name}';
    notifyListeners();
    unawaited(sync(silent: true));
  }

  Future<void> sync({bool silent = false}) async {
    if (busy || user == null || token == null || _api == null) return;
    final refreshed = lastRefresh == null ? null : DateTime.tryParse(lastRefresh!);
    if (silent && pendingCount == 0 && refreshed != null && DateTime.now().toUtc().difference(refreshed.toUtc()) < const Duration(minutes: 15)) return;
    busy = true;
    if (!silent) message = 'Syncing…';
    notifyListeners();
    try {
      final pending = actions.where((a) => a.syncState == 'pending').toList().reversed.toList();
      for (var i = 0; i < pending.length; i += 50) {
        final batch = pending.skip(i).take(50).toList();
        final response = await _api!.sync(token!, batch.map((a) => a.toApi()).toList());
        final returned = (response['results'] as List).map((r) => Map<String, dynamic>.from(r as Map)).toList();
        if (returned.length != batch.length) throw StateError('Server did not acknowledge every attendance entry');
        for (final result in returned) {
          final id = result['client_id'] as String;
          if (!batch.any((a) => a.clientId == id)) throw StateError('Server returned an unknown attendance entry');
          await store.setResult(id, result['result'] as String, result['reason'] as String?);
        }
      }
      await reload();
      final catalog = await _api!.catalog(token!);
      await store.replaceCatalog(catalog);
      await reload();
      needsSignIn = false;
      message = issueCount > 0 ? '$issueCount entries need review' : 'Up to date';
    } on ApiFailure catch (e) {
      if (e.status == 401 || e.status == 403) needsSignIn = true;
      if (!silent || e.status == 401 || e.status == 403) message = e.message;
    } catch (e) {
      if (!silent) message = e.toString();
    } finally {
      busy = false;
      notifyListeners();
    }
  }

  Future<List<Map<String, dynamic>>> conflicts() async {
    if (_api == null || token == null) throw StateError('Sign in to review conflicts.');
    final response = await _api!.conflicts(token!);
    return (response['conflicts'] as List).map((item) => Map<String, dynamic>.from(item as Map)).toList();
  }

  Future<void> resolve(String clientId, String decision, String? expectedStatus, String? expectedTime) async {
    if (_api == null || token == null) throw StateError('Sign in to review conflicts.');
    await _api!.resolve(token!, clientId, decision, expectedStatus, expectedTime);
    await store.setResult(clientId, 'resolved', decision == 'apply' ? 'Admin applied mobile result' : 'Admin kept server result');
    await reload();
    await sync();
  }

  Future<void> signOut() async {
    if (busy) throw StateError('Wait for sync to finish before signing out.');
    if (pendingCount > 0) throw StateError('Sync pending attendance before signing out.');
    await store.clear();
    await _secure.deleteAll();
    _api?.close();
    _api = null;
    user = null;
    token = null;
    baseUrl = null;
    needsSignIn = false;
    message = null;
    await reload();
  }

  @override
  void dispose() {
    _retryTimer?.cancel();
    _api?.close();
    super.dispose();
  }
}
