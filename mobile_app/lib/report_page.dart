import 'package:flutter/material.dart';

import 'app_controller.dart';
import 'native_export.dart';
import 'portal_charts.dart';

String _text(Object? value) => value?.toString() ?? '';
int _number(Object? value) => int.tryParse(_text(value)) ?? 0;
String _date(DateTime value) => '${value.year}-${value.month.toString().padLeft(2, '0')}-${value.day.toString().padLeft(2, '0')}';
List<Map<String, dynamic>> _list(Map<String, dynamic>? data, String key) => ((data?[key] as List?) ?? []).map((row) => Map<String, dynamic>.from(row as Map)).toList();

class ReportPage extends StatefulWidget {
  final AppController controller;
  final bool audit;
  const ReportPage({super.key, required this.controller, this.audit = false});
  @override
  State<ReportPage> createState() => _ReportPageState();
}

class _ReportPageState extends State<ReportPage> {
  DateTime from = DateTime.now().subtract(const Duration(days: 30));
  DateTime to = DateTime.now();
  DateTime selected = DateTime.now();
  String view = 'day';
  int? eventId;
  String category = '', status = '', search = '';
  Map<String, dynamic>? data;
  bool loading = false, exporting = false;
  String? error;
  int requestId = 0;

  @override
  void initState() {
    super.initState();
    if (widget.audit) _auditRange();
    WidgetsBinding.instance.addPostFrameCallback((_) => load());
  }

  void _auditRange() {
    if (view == 'month') {
      from = DateTime(selected.year, selected.month, 1);
      to = DateTime(selected.year, selected.month + 1, 0);
    } else if (view == 'week') {
      from = DateTime(selected.year, selected.month, selected.day - selected.weekday + 1);
      to = from.add(const Duration(days: 6));
    } else {
      from = selected;
      to = selected;
    }
  }

  Map<String, dynamic> filters({int page = 1, bool export = false}) => {
    'mode': widget.audit ? 'audit' : 'report', 'from_date': _date(from), 'to_date': _date(to),
    if (eventId != null) 'event_id': eventId, 'category': category, 'status': status, 'search': search,
    'page': page, 'export': export,
  };

  Future<void> load({int page = 1}) async {
    final id = ++requestId;
    setState(() { loading = true; error = null; });
    try {
      final response = await widget.controller.report(filters(page: page));
      if (mounted && id == requestId) setState(() => data = response);
    } catch (e) {
      if (mounted && id == requestId) setState(() => error = e.toString().replaceFirst('Bad state: ', ''));
    } finally {
      if (mounted && id == requestId) setState(() => loading = false);
    }
  }

  Future<void> _pick(bool isFrom) async {
    final value = await showDatePicker(context: context, initialDate: isFrom ? from : to, firstDate: DateTime(2000), lastDate: DateTime(2100));
    if (value != null && mounted) setState(() { if (isFrom) { from = value; } else { to = value; } });
  }

  Future<void> export(String format) async {
    setState(() => exporting = true);
    try {
      final response = await widget.controller.report(filters(export: true));
      final rows = _list(response, 'rows');
      final saved = await NativeExport.table(format, 'attendance_${_date(from)}_${_date(to)}', '${widget.controller.user?.church} Attendance Report', const ['Member', 'Category', 'Contact', 'Email', 'QR Token', 'Event', 'Date', 'Type', 'Status', 'Method', 'Log Time'], rows.map((row) => ['fullname', 'category', 'contact', 'email', 'qr_token', 'event_name', 'start_date', 'type', 'status', 'method', 'log_time'].map((key) => _text(row[key])).toList()).toList());
      if (mounted && saved) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(format == 'pdf' ? 'Choose Save as PDF in the print dialog.' : 'Report saved.')));
    } catch (e) {
      if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(e.toString().replaceFirst('Bad state: ', ''))));
    } finally {
      if (mounted) setState(() => exporting = false);
    }
  }

  @override
  void dispose() { requestId++; super.dispose(); }

  @override
  Widget build(BuildContext context) {
    final events = _list(widget.controller.portalData, 'events');
    final categories = _list(widget.controller.portalData, 'categories').map((c) => _text(c['name'])).toSet().toList();
    final rows = _list(data, 'rows');
    final summary = Map<String, dynamic>.from((data?['summary'] as Map?) ?? {});
    final page = _number(data?['page']);
    final totalPages = _number(data?['total_pages']);
    return RefreshIndicator(onRefresh: load, child: ListView(padding: const EdgeInsets.all(16), children: [
      Text(widget.audit ? 'Attendance Audit' : 'Reports', style: const TextStyle(fontFamily: 'Cinzel', fontSize: 26)),
      const SizedBox(height: 14),
      Card(child: Padding(padding: const EdgeInsets.all(16), child: Column(crossAxisAlignment: CrossAxisAlignment.stretch, children: [
        Text(widget.audit ? 'Audit Filters' : 'Report Filters', style: Theme.of(context).textTheme.titleMedium),
        const SizedBox(height: 12),
        if (widget.audit) ...[
          SegmentedButton<String>(segments: const [ButtonSegment(value: 'day', label: Text('Day')), ButtonSegment(value: 'week', label: Text('Week')), ButtonSegment(value: 'month', label: Text('Month'))], selected: {view}, onSelectionChanged: (v) { setState(() { view = v.first; _auditRange(); }); load(); }),
          ExpansionTile(title: Text('Calendar · ${_date(selected)}'), children: [CalendarDatePicker(key: ValueKey(_date(selected)), initialDate: selected, firstDate: DateTime(2000), lastDate: DateTime(2100), onDateChanged: (value) { setState(() { selected = value; _auditRange(); }); load(); })]),
        ] else Row(children: [Expanded(child: OutlinedButton(onPressed: () => _pick(true), child: Text('From ${_date(from)}'))), const SizedBox(width: 8), Expanded(child: OutlinedButton(onPressed: () => _pick(false), child: Text('To ${_date(to)}')))]),
        DropdownButtonFormField<int>(key: ValueKey('event-$eventId-${events.length}'), initialValue: events.any((e) => _number(e['id']) == eventId) ? eventId : null, decoration: const InputDecoration(labelText: 'Event (optional)'), items: events.map((e) => DropdownMenuItem(value: _number(e['id']), child: Text('${e['event_name']} · ${e['start_date']}', overflow: TextOverflow.ellipsis))).toList(), onChanged: (v) => setState(() => eventId = v)),
        if (eventId != null) Align(alignment: Alignment.centerLeft, child: TextButton(onPressed: () => setState(() => eventId = null), child: const Text('All events'))),
        DropdownButtonFormField<String>(initialValue: category, decoration: const InputDecoration(labelText: 'Category'), items: [const DropdownMenuItem(value: '', child: Text('All categories')), ...categories.map((c) => DropdownMenuItem(value: c, child: Text(c)))], onChanged: (v) => setState(() => category = v ?? '')),
        if (widget.audit) DropdownButtonFormField<String>(initialValue: status, decoration: const InputDecoration(labelText: 'Attendance status'), items: ['', 'Present', 'Absent'].map((s) => DropdownMenuItem(value: s, child: Text(s.isEmpty ? 'All statuses' : s))).toList(), onChanged: (v) => setState(() => status = v ?? '')),
        TextField(decoration: const InputDecoration(labelText: 'Search name, QR token, or event', prefixIcon: Icon(Icons.search)), onChanged: (v) => search = v.trim(), onSubmitted: (_) => load()),
        const SizedBox(height: 16),
        FilledButton.icon(onPressed: loading ? null : () => load(), icon: const Icon(Icons.search), label: Text(widget.audit ? 'Review Attendance' : 'Generate Report')),
      ]))),
      if (loading || exporting) const LinearProgressIndicator(),
      if (error != null) Card(child: ListTile(leading: const Icon(Icons.error_outline), title: Text(error!), trailing: IconButton(tooltip: 'Retry', onPressed: () => load(), icon: const Icon(Icons.refresh)))),
      if (data != null) ...[
        LayoutBuilder(builder: (context, constraints) => Wrap(spacing: 8, runSpacing: 8, children: [
          _metric('Total Events', summary['total_events'], constraints.maxWidth),
          _metric('Present', summary['present'], constraints.maxWidth),
          _metric('Absent', summary['absent'], constraints.maxWidth),
          _metric(widget.audit ? 'Total Records' : 'Not Recorded', summary[widget.audit ? 'total_records' : 'not_recorded'], constraints.maxWidth),
        ])),
        const SizedBox(height: 14),
        Row(children: [Expanded(child: Text('Attendance Records · ${summary['total_records']}', style: Theme.of(context).textTheme.titleMedium)), PopupMenuButton<String>(enabled: !exporting && !loading, tooltip: 'Export report', onSelected: export, itemBuilder: (_) => const [PopupMenuItem(value: 'csv', child: Text('Export CSV')), PopupMenuItem(value: 'xlsx', child: Text('Export Excel')), PopupMenuItem(value: 'pdf', child: Text('Export PDF'))], icon: const Icon(Icons.download_outlined))]),
        if (rows.isEmpty) const Padding(padding: EdgeInsets.all(28), child: Text('No records match the selected filters.', textAlign: TextAlign.center)),
        ...rows.map((row) => Card(child: ExpansionTile(leading: Icon(row['status'] == 'Present' ? Icons.check_circle_outline : row['status'] == 'Absent' ? Icons.cancel_outlined : Icons.remove_circle_outline), title: Text(_text(row['fullname'])), subtitle: Text('${row['event_name']} · ${row['start_date']}\n${row['status']}'), childrenPadding: const EdgeInsets.fromLTRB(16, 0, 16, 14), children: [Text('${row['category']} · ${row['method'] ?? '—'}\n${row['log_time'] ?? '—'}'), if (_text(row['notes']).isNotEmpty) Text(_text(row['notes'])), SelectableText(_text(row['qr_token']))]))),
        Row(mainAxisAlignment: MainAxisAlignment.center, children: [IconButton(tooltip: 'Previous page', onPressed: !loading && page > 1 ? () => load(page: page - 1) : null, icon: const Icon(Icons.chevron_left)), Text('Page $page of $totalPages'), IconButton(tooltip: 'Next page', onPressed: !loading && page < totalPages ? () => load(page: page + 1) : null, icon: const Icon(Icons.chevron_right))]),
        if (!widget.audit) ...[
          Card(child: Padding(padding: const EdgeInsets.all(16), child: Column(crossAxisAlignment: CrossAxisAlignment.stretch, children: [Text('Top Attendees', style: Theme.of(context).textTheme.titleMedium), ..._list(data, 'top_attendees').map((r) => ListTile(dense: true, title: Text(_text(r['fullname'])), subtitle: Text(_text(r['category'])), trailing: Text('${r['attended']} events')))]))),
          Card(child: Padding(padding: const EdgeInsets.all(16), child: Column(crossAxisAlignment: CrossAxisAlignment.stretch, children: [Text('Monthly Comparison', style: Theme.of(context).textTheme.titleMedium), const SizedBox(height: 12), TrendChart(labels: _list(data, 'monthly').map((r) => _text(r['month'])).toList(), values: _list(data, 'monthly').map((r) => _number(r['present_count'])).toList())]))),
        ],
      ],
    ]));
  }

  Widget _metric(String label, Object? value, double width) => SizedBox(width: (width - 8) / 2, child: Card(child: Padding(padding: const EdgeInsets.all(14), child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [Text(_text(value), style: const TextStyle(fontSize: 26, fontWeight: FontWeight.bold)), Text(label)]))));
}
