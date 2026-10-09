import 'dart:ui';

import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import 'package:workmanager/workmanager.dart';

import 'local_store.dart';
import 'mobile_api.dart';

const mobileSyncTask = 'afb-attendance-sync';

@pragma('vm:entry-point')
void mobileCallbackDispatcher() {
  Workmanager().executeTask((task, inputData) async {
    DartPluginRegistrant.ensureInitialized();
    if (task != mobileSyncTask) return true;
    final secure = FlutterSecureStorage();
    final token = await secure.read(key: 'token');
    final url = await secure.read(key: 'base_url');
    if (token == null || url == null) return true;
    final store = LocalStore();
    final api = MobileApi(url);
    try {
      final pending = (await store.actions()).where((a) => a.syncState == 'pending').toList().reversed.toList();
      for (var i = 0; i < pending.length; i += 50) {
        final batch = pending.skip(i).take(50).toList();
        final response = await api.sync(token, batch.map((a) => a.toApi()).toList());
        final results = (response['results'] as List).map((r) => Map<String, dynamic>.from(r as Map)).toList();
        if (results.length != batch.length) return false;
        for (final result in results) {
          if (!batch.any((a) => a.clientId == result['client_id'])) return false;
          await store.setResult(result['client_id'] as String, result['result'] as String, result['reason'] as String?);
        }
      }
      return true;
    } on ApiFailure catch (e) {
      // A user must sign in again before an expired token can sync.
      return e.status == 401 || e.status == 403;
    } catch (_) {
      return false;
    } finally {
      api.close();
    }
  });
}

Future<void> scheduleMobileSync() => Workmanager().registerOneOffTask(
      'afb-pending-attendance',
      mobileSyncTask,
      constraints: Constraints(networkType: NetworkType.connected),
      existingWorkPolicy: ExistingWorkPolicy.keep,
      backoffPolicy: BackoffPolicy.exponential,
      backoffPolicyDelay: const Duration(minutes: 1),
    );
