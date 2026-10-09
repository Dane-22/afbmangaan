import 'dart:convert';

import 'package:afb_mangaan_mobile/mobile_api.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:http/http.dart' as http;
import 'package:http/testing.dart';

Map<String, dynamic> page(String snapshot, int number, int event) => {'success': true, 'version': 2, 'snapshot': snapshot, 'page': number, 'total_pages': 2, 'events': [{'id': event}], 'members': [], 'attendance': [], 'downloaded_event_ids': [], 'fetched_at_utc': '2026-10-09T08:00:00Z'};

void main() {
  test('a changed snapshot restarts the download without mixing event pages', () async {
    var calls = 0;
    final api = MobileApi('https://example.test', client: MockClient((request) async {
      calls++;
      final body = jsonDecode(request.body) as Map;
      expect(request.headers['Authorization'], 'Bearer test');
      switch (calls) {
        case 1:
          expect(body['page'], 1);
          return http.Response(jsonEncode(page('old', 1, 1)), 200);
        case 2:
          expect(body['snapshot'], 'old');
          return http.Response(jsonEncode({'success': false, 'error': 'Changed'}), 409);
        case 3:
          expect(body['page'], 1);
          expect(body.containsKey('snapshot'), isFalse);
          return http.Response(jsonEncode(page('new', 1, 2)), 200);
        default:
          expect(body['snapshot'], 'new');
          return http.Response(jsonEncode(page('new', 2, 3)), 200);
      }
    }));
    try {
      final result = await api.catalogV2('test');
      expect((result['events'] as List).map((item) => item['id']), [2, 3]);
      expect(calls, 4);
    } finally { api.close(); }
  });
  test('a failed page never returns a partially downloaded catalog', () async {
    var calls = 0;
    final api = MobileApi('https://example.test', client: MockClient((request) async {
      calls++;
      return calls == 1 ? http.Response(jsonEncode(page('snapshot', 1, 1)), 200) : http.Response('unavailable', 503);
    }));
    try { await expectLater(api.catalogV2('test'), throwsA(isA<ApiFailure>())); }
    finally { api.close(); }
  });
}
