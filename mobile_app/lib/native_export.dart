import 'package:flutter/services.dart';

class NativeExport {
  static const channel = MethodChannel('org.afbmangaan.portal/export');

  static String _csv(String value) {
    final safe = RegExp(r'^\s*[=+@-]').hasMatch(value) ? "'$value" : value;
    return '"${safe.replaceAll('"', '""')}"';
  }

  static String _html(String value) => value.replaceAll('&', '&amp;').replaceAll('<', '&lt;').replaceAll('>', '&gt;').replaceAll('"', '&quot;').replaceAll("'", '&#39;');

  static Future<bool> table(String format, String filename, String title, List<String> headers, List<List<String>> rows) async {
    if (format == 'xlsx') {
      return await channel.invokeMethod<bool>('saveSpreadsheet', {'filename': '$filename.xlsx', 'headers': headers, 'rows': rows}) ?? false;
    }
    if (format == 'pdf') {
      final html = '<!doctype html><html><head><meta charset="utf-8"><style>body{font-family:sans-serif;font-size:10px}h1{color:#8f711f}table{border-collapse:collapse;width:100%}th,td{border:1px solid #ddd;padding:5px;text-align:left}th{background:#f2e7bd}tr{page-break-inside:avoid}</style></head><body><h1>${_html(title)}</h1><p>${rows.length} records</p><table><thead><tr>${headers.map((v) => '<th>${_html(v)}</th>').join()}</tr></thead><tbody>${rows.map((row) => '<tr>${row.map((v) => '<td>${_html(v)}</td>').join()}</tr>').join()}</tbody></table></body></html>';
      return await channel.invokeMethod<bool>('printReport', {'title': title, 'html': html}) ?? false;
    }
    final csv = '\uFEFF${[headers, ...rows].map((row) => row.map(_csv).join(',')).join('\r\n')}';
    return await channel.invokeMethod<bool>('saveCsv', {'filename': '$filename.csv', 'content': csv}) ?? false;
  }
}
