import 'dart:convert';

import 'package:http/http.dart' as http;

class ApiFailure implements Exception {
  final int status;
  final String message;
  ApiFailure(this.status, this.message);
  @override
  String toString() => message;
}

class MobileApi {
  final String baseUrl;
  final http.Client _client = http.Client();

  MobileApi(this.baseUrl);

  Uri _url(String endpoint) => Uri.parse('${baseUrl.replaceAll(RegExp(r'/$'), '')}/api/$endpoint.php');

  Future<Map<String, dynamic>> _request(String method, String endpoint, {String? token, Map<String, dynamic>? body}) async {
    final headers = <String, String>{'Accept': 'application/json'};
    if (body != null) headers['Content-Type'] = 'application/json';
    if (token != null) headers['Authorization'] = 'Bearer $token';
    late http.Response response;
    try {
      response = await (method == 'GET'
          ? _client.get(_url(endpoint), headers: headers)
          : _client.post(_url(endpoint), headers: headers, body: jsonEncode(body ?? {}))).timeout(const Duration(seconds: 25));
    } catch (_) {
      throw ApiFailure(0, 'Cannot reach the server. Entries remain on this device.');
    }
    Map<String, dynamic> data;
    try {
      data = jsonDecode(response.body) as Map<String, dynamic>;
    } catch (_) {
      throw ApiFailure(response.statusCode, 'Server returned an invalid response');
    }
    if (response.statusCode < 200 || response.statusCode >= 300 || data['success'] != true) {
      throw ApiFailure(response.statusCode, (data['error'] ?? data['message'] ?? 'Request failed').toString());
    }
    return data;
  }

  Future<Map<String, dynamic>> login(String username, String password, String church) =>
      _request('POST', 'mobile_login', body: {'username': username, 'password': password, 'church': church});

  Future<Map<String, dynamic>> catalog(String token) => _request('GET', 'mobile_catalog', token: token);

  Future<Map<String, dynamic>> portal(String token) => _request('GET', 'mobile_portal', token: token);

  Future<Map<String, dynamic>> portalAction(String token, Map<String, dynamic> action) =>
      _request('POST', 'mobile_portal_action', token: token, body: action);

  Future<Map<String, dynamic>> report(String token, Map<String, dynamic> filters) =>
      _request('POST', 'mobile_report', token: token, body: filters);

  Future<Map<String, dynamic>> assistant(String token, String query) =>
      _request('POST', 'ai_assistant', token: token, body: {'query': query});

  Future<Map<String, dynamic>> chat(String token, Map<String, dynamic> action) =>
      _request('POST', 'chat', token: token, body: action);

  Future<Map<String, dynamic>> sync(String token, List<Map<String, dynamic>> actions) =>
      _request('POST', 'mobile_sync', token: token, body: {'actions': actions});

  Future<Map<String, dynamic>> conflicts(String token) => _request('GET', 'mobile_conflicts', token: token);

  Future<void> resolve(String token, String clientId, String decision, String? expectedStatus, String? expectedTime) async {
    await _request('POST', 'mobile_resolve', token: token, body: {'client_id': clientId, 'decision': decision, 'expected_status': expectedStatus, 'expected_log_time_utc': expectedTime});
  }

  void close() => _client.close();
}
