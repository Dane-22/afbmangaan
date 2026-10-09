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
  int _portalGeneration = 0;

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
  Map<String, dynamic>? portalData;
  bool portalBusy = false;
  String? portalError;
  Map<int, String> detailDownloads = {};
  final Set<int> downloadingEvents = {};
  bool catalogNeedsUpgrade = false;

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
      if (user != null && token != null) {
        if (user!.role == 'viewer') { unawaited(loadPortal()); }
        else { unawaited(sync(silent: true)); unawaited(loadPortal()); }
      }
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
    detailDownloads = await store.detailDownloads();
    catalogNeedsUpgrade = !await store.hasVersion2Catalog();
    notifyListeners();
  }

  Future<void> login(String url, String username, String password, String church) async {
    if (downloadingEvents.isNotEmpty) throw StateError('Wait for attendance downloads to finish before signing in.');
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
      if (user == null || nextUser.id != user!.id || nextUser.church != user!.church || baseUrl != url.trim()) {
        portalData = null;
        portalError = null;
      }
      _api?.close();
      _portalGeneration++;
      portalBusy = false;
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
    if (user?.role != 'viewer') await sync(silent: true);
    await loadPortal();
  }

  Future<void> record(MobileEvent event, MobileMember member, String status, String method) async {
    if (user == null) throw StateError('Sign in online once before recording attendance.');
    if (user!.role == 'viewer') throw StateError('Viewers cannot record attendance.');
    event = events.where((item) => item.id == event.id).firstOrNull ?? event;
    if (!detailDownloads.containsKey(event.id)) throw StateError('Download attendance details before recording for this event.');
    final now = DateTime.now().toUtc();
    if (!event.canRecordAt(now)) throw StateError('Recording is available on the event date and is disabled for cancelled or archived events.');
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

  Future<void> markAllPresent(MobileEvent event) async {
    if (user == null || user!.role == 'viewer') throw StateError('Only admins and operators can record attendance.');
    event = events.where((item) => item.id == event.id).firstOrNull ?? event;
    if (!detailDownloads.containsKey(event.id)) throw StateError('Download attendance details before recording for this event.');
    final now = DateTime.now().toUtc();
    if (!event.canRecordAt(now)) throw StateError('Recording is available on the active event date.');
    final queued = <PendingAction>[];
    for (final member in members) {
      final base = attendance.where((a) => a.eventId == event.id && a.memberId == member.id).firstOrNull;
      final pending = actions.where((a) => a.eventId == event.id && a.memberId == member.id && a.syncState == 'pending').firstOrNull;
      if ((pending?.status ?? base?.status) == 'Present') continue;
      queued.add(PendingAction(clientId: _uuid.v4(), eventId: event.id, memberId: member.id, status: 'Present', method: 'Manual', occurredAtUtc: now.toIso8601String(), baseStatus: base?.status, baseLogTimeUtc: base?.logTimeUtc));
    }
    await store.addActions(queued);
    await reload();
    message = '${queued.length} attendance entries queued';
    notifyListeners();
    unawaited(scheduleMobileSync().catchError((_) {}));
    unawaited(sync(silent: true));
  }

  Future<void> sync({bool silent = false}) async {
    if (user?.role == 'viewer') return;
    if (busy || user == null || token == null || _api == null) return;
    final refreshed = lastRefresh == null ? null : DateTime.tryParse(lastRefresh!);
    if (silent && !catalogNeedsUpgrade && pendingCount == 0 && refreshed != null && DateTime.now().toUtc().difference(refreshed.toUtc()) < const Duration(minutes: 15)) return;
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
      final catalog = await _api!.catalogV2(token!);
      await store.replaceCatalog(catalog);
      await reload();
      needsSignIn = false;
      catalogNeedsUpgrade = false;
      final available = events.where((event) => detailDownloads.containsKey(event.id)).length;
      message = 'Synced: ${events.length} events; $available available offline${issueCount > 0 ? "; $issueCount entries need review" : ""}';
      unawaited(loadPortal());
    } on ApiFailure catch (e) {
      if (e.status == 401 || e.status == 403) needsSignIn = true;
      message = 'Refresh failed: ${e.message}. Saved data is still available.';
    } catch (e) {
      message = 'Refresh failed: $e. Saved data is still available.';
    } finally {
      busy = false;
      notifyListeners();
    }
  }

  Future<void> downloadEvent(int eventId) async {
    if (_api == null || token == null) throw StateError('Sign in online to download attendance details.');
    if (downloadingEvents.contains(eventId)) return;
    downloadingEvents.add(eventId);
    final api = _api!;
    final accessToken = token!;
    notifyListeners();
    try {
      final catalog = await api.catalogV2(accessToken, eventId: eventId);
      if (api != _api || accessToken != token) throw StateError('Account changed during download. Retry.');
      await store.replaceCatalog(catalog, eventDetail: true);
      await reload();
    } on ApiFailure catch (error) {
      if (error.status == 401 || error.status == 403) needsSignIn = true;
      rethrow;
    } finally {
      downloadingEvents.remove(eventId);
      notifyListeners();
    }
  }

  Future<void> loadPortal() async {
    if (portalBusy || token == null || _api == null) return;
    final generation = ++_portalGeneration;
    final api = _api!;
    final accessToken = token!;
    portalBusy = true;
    portalError = null;
    notifyListeners();
    try {
      final response = await api.portal(accessToken);
      if (generation == _portalGeneration) portalData = response;
    } on ApiFailure catch (e) {
      if (generation != _portalGeneration) return;
      portalError = e.message;
      if (e.status == 401 || e.status == 403) needsSignIn = true;
    } catch (e) {
      if (generation != _portalGeneration) return;
      portalError = e.toString();
    } finally {
      if (generation == _portalGeneration) {
        portalBusy = false;
        notifyListeners();
      }
    }
  }

  Future<void> portalAction(Map<String, dynamic> action) async {
    if (token == null || _api == null) throw StateError('Sign in online to make changes.');
    await _api!.portalAction(token!, action);
    await loadPortal();
    if (action['resource'] == 'members' || action['resource'] == 'events') await sync();
  }

  Future<Map<String, dynamic>> report(Map<String, dynamic> filters) async {
    if (_api == null || token == null) throw StateError('Sign in online to load reports.');
    try {
      return await _api!.report(token!, filters);
    } on ApiFailure catch (e) {
      if (e.status == 401 || e.status == 403) { needsSignIn = true; notifyListeners(); }
      rethrow;
    }
  }

  Future<Map<String, dynamic>> assistant(String query) async {
    if (_api == null || token == null) throw StateError('Sign in online to use the assistant.');
    try { return await _api!.assistant(token!, query); }
    on ApiFailure catch (e) { if (e.status == 401) { needsSignIn = true; notifyListeners(); } rethrow; }
  }

  Future<Map<String, dynamic>> chat(Map<String, dynamic> action) async {
    if (_api == null || token == null) throw StateError('Sign in online to use group chat.');
    try { return await _api!.chat(token!, action); }
    on ApiFailure catch (e) { if (e.status == 401) { needsSignIn = true; notifyListeners(); } rethrow; }
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
    if (downloadingEvents.isNotEmpty) throw StateError('Wait for attendance downloads to finish before signing out.');
    if (busy) throw StateError('Wait for sync to finish before signing out.');
    if (pendingCount > 0) throw StateError('Sync pending attendance before signing out.');
    await store.clear();
    await _secure.deleteAll();
    _api?.close();
    _portalGeneration++;
    portalBusy = false;
    _api = null;
    user = null;
    token = null;
    baseUrl = null;
    needsSignIn = false;
    message = null;
    portalData = null;
    portalError = null;
    await reload();
  }

  @override
  void dispose() {
    _portalGeneration++;
    _retryTimer?.cancel();
    _api?.close();
    super.dispose();
  }
}
