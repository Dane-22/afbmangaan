import 'package:flutter/material.dart';
import 'package:flutter/services.dart';

import 'app_controller.dart';
import 'models.dart';

List<Map<String, dynamic>> _rows(Map<String, dynamic>? data, String key) =>
    ((data?[key] as List?) ?? []).map((e) => Map<String, dynamic>.from(e as Map)).toList();
String _value(Object? value) => value == null ? '' : value.toString();
int _id(Object? value) => int.tryParse(_value(value)) ?? 0;

class PortalSection extends StatefulWidget {
  final AppController controller;
  final String section;
  final ValueChanged<String> onNavigate;
  const PortalSection({super.key, required this.controller, required this.section, required this.onNavigate});
  @override
  State<PortalSection> createState() => _PortalSectionState();
}

class _PortalSectionState extends State<PortalSection> {
  String query = '';
  String filter = '';
  int? eventId;
  String fromDate = '';
  String toDate = '';

  Future<void> _act(Map<String, dynamic> action, {String? confirmation}) async {
    if (confirmation != null) {
      final yes = await showDialog<bool>(context: context, builder: (context) => AlertDialog(title: const Text('Confirm action'), content: Text(confirmation), actions: [TextButton(onPressed: () => Navigator.pop(context, false), child: const Text('Cancel')), FilledButton(onPressed: () => Navigator.pop(context, true), child: const Text('Continue'))]));
      if (yes != true) return;
    }
    try {
      await widget.controller.portalAction(action);
      if (mounted) ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Saved successfully')));
    } catch (e) {
      if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(e.toString().replaceFirst('Bad state: ', ''))));
    }
  }

  Future<void> _edit(String resource, Map<String, dynamic>? row, List<EditField> fields, {Map<String, dynamic> extra = const {}}) async {
    final value = await showModalBottomSheet<Map<String, dynamic>>(context: context, isScrollControlled: true, showDragHandle: true, builder: (_) => EditForm(title: row == null ? 'Add ${resource.substring(0, resource.length - 1)}' : 'Edit ${resource.substring(0, resource.length - 1)}', row: row, fields: fields));
    if (value == null) return;
    await _act({'resource': resource, 'action': 'save', if (row != null) 'id': row['id'], ...extra, ...value});
  }

  @override
  Widget build(BuildContext context) {
    final c = widget.controller;
    final data = c.portalData;
    if (data == null) {
      return Center(child: Padding(padding: const EdgeInsets.all(24), child: Column(mainAxisSize: MainAxisSize.min, children: [
        if (c.portalBusy) const CircularProgressIndicator(),
        const SizedBox(height: 16),
        Text(c.portalError ?? 'Connect to load your church dashboard.', textAlign: TextAlign.center),
        const SizedBox(height: 12),
        FilledButton.icon(onPressed: c.portalBusy ? null : c.loadPortal, icon: const Icon(Icons.refresh), label: const Text('Try again')),
      ])));
    }
    final body = switch (widget.section) {
      'dashboard' => _dashboard(data),
      'audit' => _audit(data),
      'members' => _members(data),
      'events' => _events(data),
      'songs' => _songs(data),
      'stations' => _stations(data),
      'logs' => _logs(data),
      'settings' => _settings(data),
      'reports' => _reports(data),
      _ => const Center(child: Text('Select a page from the menu.')),
    };
    return RefreshIndicator(onRefresh: c.loadPortal, child: ListView(padding: const EdgeInsets.all(16), children: [
      if (c.portalBusy) const LinearProgressIndicator(),
      if (c.portalError != null) Card(child: ListTile(leading: const Icon(Icons.cloud_off), title: Text(c.portalError!), subtitle: const Text('Showing last loaded online data. Pull down to retry.'))),
      body,
    ]));
  }

  Widget _heading(String title, {String? subtitle, Widget? action}) => Padding(padding: const EdgeInsets.fromLTRB(2, 8, 2, 15), child: Row(children: [
    Expanded(child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [Text(title, style: const TextStyle(fontFamily: 'serif', fontSize: 25)), if (subtitle != null) Text(subtitle)])),
    if (action != null) action,
  ]));

  Widget _search(String hint) => Padding(padding: const EdgeInsets.only(bottom: 12), child: TextField(decoration: InputDecoration(prefixIcon: const Icon(Icons.search), hintText: hint, border: const OutlineInputBorder()), onChanged: (v) => setState(() => query = v.trim().toLowerCase())));

  Widget _empty(String text) => Padding(padding: const EdgeInsets.symmetric(vertical: 40), child: Center(child: Text(text, textAlign: TextAlign.center)));

  Widget _dashboard(Map<String, dynamic> data) {
    final stats = Map<String, dynamic>.from((data['stats'] as Map?) ?? {});
    final trends = _rows(data, 'trends');
    final categories = <String, int>{};
    for (final row in _rows(data, 'category_counts')) {
      categories[_value(row['category'])] = _id(row['member_count']);
    }
    final todayEvent = stats['today_event'] is Map ? Map<String, dynamic>.from(stats['today_event'] as Map) : null;
    return Column(crossAxisAlignment: CrossAxisAlignment.stretch, children: [
      _heading(widget.controller.user?.church ?? 'Dashboard', subtitle: 'Current Branch'),
      Wrap(spacing: 9, runSpacing: 9, children: [
        _metric('Total Members', _value(stats['total_members']), Icons.people_outline),
        _metric("Today's Event", todayEvent == null ? 'No Event' : _value(todayEvent['event_name']), Icons.event_available_outlined),
        _metric('Consistent Members', _value(stats['consistent_count']), Icons.insights_outlined),
        _metric('At Risk Members', _value(stats['at_risk_count']), Icons.warning_amber_outlined),
      ]),
      const SizedBox(height: 16),
      _card('Attendance Trends', Column(children: [if (trends.isEmpty) const Text('No attendance data yet.'), ...trends.map((row) => ListTile(dense: true, title: Text(_value(row['month'])), trailing: Text(_value(row['present_count']))))]), action: TextButton(onPressed: () => widget.onNavigate('reports'), child: const Text('View Reports'))),
      _card('Categories', categories.isEmpty ? const Text('No category data yet.') : Column(children: categories.entries.map((e) => ListTile(dense: true, title: Text(e.key), trailing: Text('${e.value}'))).toList())),
      _card('Recent Activity', Column(children: _rows(data, 'audit').take(5).map((row) => ListTile(dense: true, title: Text('${row['fullname']} · ${row['status']}'), subtitle: Text('${row['event_name']} · ${row['log_time']}'))).toList())),
      _card('Quick Actions', Wrap(spacing: 8, runSpacing: 8, children: [
        FilledButton.icon(onPressed: () => widget.onNavigate('attendance'), icon: const Icon(Icons.check_circle_outline), label: const Text('Take Attendance')),
        if (widget.controller.user?.role != 'viewer') ...[
          OutlinedButton.icon(onPressed: () => widget.onNavigate('members'), icon: const Icon(Icons.person_add_alt), label: const Text('Members')),
          OutlinedButton.icon(onPressed: () => widget.onNavigate('events'), icon: const Icon(Icons.event), label: const Text('Events')),
        ],
      ])),
    ]);
  }

  Widget _metric(String label, String value, IconData icon) => SizedBox(width: 155, child: Card(child: Padding(padding: const EdgeInsets.all(14), child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [Icon(icon, color: Theme.of(context).colorScheme.primary), const SizedBox(height: 12), Text(value, maxLines: 2, overflow: TextOverflow.ellipsis, style: const TextStyle(fontSize: 22, fontWeight: FontWeight.bold)), Text(label, style: Theme.of(context).textTheme.bodySmall)]))));

  Widget _card(String title, Widget child, {Widget? action}) => Card(margin: const EdgeInsets.only(bottom: 12), child: Padding(padding: const EdgeInsets.all(14), child: Column(crossAxisAlignment: CrossAxisAlignment.stretch, children: [Row(children: [Expanded(child: Text(title, style: Theme.of(context).textTheme.titleMedium)), if (action != null) action]), const Divider(), child])));

  Widget _audit(Map<String, dynamic> data) {
    final rows = _rows(data, 'audit').where((r) => (query.isEmpty || '${r['fullname']} ${r['event_name']} ${r['status']}'.toLowerCase().contains(query)) && (filter.isEmpty || r['status'] == filter)).toList();
    return Column(children: [
      _heading('Attendance Audit', subtitle: 'Latest 500 attendance changes'),
      _search('Search member or event'),
      _choice(['', 'Present', 'Absent'], {'': 'All', 'Present': 'Present', 'Absent': 'Absent'}),
      if (rows.isEmpty) _empty('No matching attendance records.'),
      ...rows.map((r) => Card(child: ListTile(leading: Icon(r['status'] == 'Present' ? Icons.check_circle : Icons.cancel_outlined), title: Text(_value(r['fullname'])), subtitle: Text('${r['event_name']} · ${r['start_date']}\n${r['method']} · ${r['log_time']}'), isThreeLine: true, trailing: Text(_value(r['status']))))),
    ]);
  }

  Widget _choice(List<String> values, Map<String, String> labels) => Padding(padding: const EdgeInsets.only(bottom: 12), child: SingleChildScrollView(scrollDirection: Axis.horizontal, child: Row(children: values.map((v) => Padding(padding: const EdgeInsets.only(right: 6), child: ChoiceChip(label: Text(labels[v] ?? v), selected: filter == v, onSelected: (_) => setState(() => filter = v)))).toList())));

  static const memberFields = [
    EditField('Full name', 'fullname', required: true), EditField('Category', 'category', required: true),
    EditField('Ministry', 'ministry'), EditField('Contact', 'contact'), EditField('Email', 'email'),
    EditField('Status (Active, Inactive, Archived)', 'status', required: true),
  ];

  Widget _members(Map<String, dynamic> data) {
    final rows = _rows(data, 'members').where((m) => (query.isEmpty || '${m['fullname']} ${m['category']} ${m['qr_token']}'.toLowerCase().contains(query)) && (filter.isEmpty || m['status'] == filter)).toList();
    return Column(children: [
      _heading('Members', subtitle: '${rows.length} members', action: IconButton(tooltip: 'Add member', onPressed: () => _edit('members', null, memberFields), icon: const Icon(Icons.person_add_alt))),
      _search('Search name, category, or QR code'),
      _choice(['', 'Active', 'Inactive', 'Archived'], {'': 'All', 'Active': 'Active', 'Inactive': 'Inactive', 'Archived': 'Archived'}),
      if (rows.isEmpty) _empty('No matching members.'),
      ...rows.map((m) => Card(child: ExpansionTile(leading: const Icon(Icons.person_outline), title: Text(_value(m['fullname'])), subtitle: Text('${m['category']} · ${m['status']}'), childrenPadding: const EdgeInsets.fromLTRB(16, 0, 16, 12), children: [
        _detail('Ministry', m['ministry']), _detail('Contact', m['contact']), _detail('Email', m['email']), _detail('QR token', m['qr_token']),
        Row(children: [TextButton.icon(onPressed: () => _edit('members', m, memberFields), icon: const Icon(Icons.edit), label: const Text('Edit')), TextButton.icon(onPressed: () => _act({'resource': 'members', 'action': 'archive', 'id': m['id']}, confirmation: 'Archive ${m['fullname']}?'), icon: const Icon(Icons.archive_outlined), label: const Text('Archive'))]),
      ]))),
      OutlinedButton.icon(onPressed: () async { final value = await showModalBottomSheet<Map<String, dynamic>>(context: context, isScrollControlled: true, builder: (_) => const EditForm(title: 'Add category', fields: [EditField('Category name', 'name', required: true)])); if (value != null) _act({'resource': 'members', 'action': 'category', ...value}); }, icon: const Icon(Icons.add), label: const Text('Add Category')),
    ]);
  }

  Widget _detail(String label, Object? value) => Padding(padding: const EdgeInsets.only(bottom: 6), child: Row(children: [SizedBox(width: 85, child: Text(label, style: Theme.of(context).textTheme.bodySmall)), Expanded(child: Text(_value(value).isEmpty ? '—' : _value(value)))]));

  static const eventFields = [
    EditField('Event name', 'event_name', required: true), EditField('Start date (YYYY-MM-DD)', 'start_date', required: true),
    EditField('End date (YYYY-MM-DD)', 'end_date'), EditField('Time (HH:MM)', 'event_time'),
    EditField('Location', 'location'), EditField('Type', 'type', required: true),
    EditField('Description', 'description', lines: 3),
  ];

  Widget _events(Map<String, dynamic> data) {
    final rows = _rows(data, 'events').where((e) => (query.isEmpty || '${e['event_name']} ${e['type']} ${e['location']}'.toLowerCase().contains(query)) && (filter.isEmpty || e['status'] == filter)).toList();
    return Column(children: [
      _heading('Events', subtitle: '${rows.length} events', action: IconButton(tooltip: 'Create event', onPressed: () => _edit('events', null, eventFields), icon: const Icon(Icons.add_circle_outline))),
      _search('Search events'),
      _choice(['', 'Upcoming', 'Ongoing', 'Completed', 'Cancelled', 'Archived'], {'': 'All'}),
      if (rows.isEmpty) _empty('No matching events.'),
      ...rows.map((e) => Card(child: ExpansionTile(leading: const Icon(Icons.event_outlined), title: Text(_value(e['event_name'])), subtitle: Text('${e['start_date']} · ${e['status']}'), childrenPadding: const EdgeInsets.fromLTRB(16, 0, 16, 12), children: [
        _detail('End date', e['end_date']), _detail('Time', e['event_time']), _detail('Location', e['location']), _detail('Type', e['type']), _detail('Description', e['description']),
        Wrap(spacing: 6, children: [
          TextButton(onPressed: () => _edit('events', e, eventFields), child: const Text('Edit')),
          PopupMenuButton<String>(tooltip: 'Change status', onSelected: (status) => _act({'resource': 'events', 'action': 'status', 'id': e['id'], 'status': status}), itemBuilder: (_) => ['Upcoming', 'Ongoing', 'Completed', 'Cancelled', 'Archived'].map((s) => PopupMenuItem(value: s, child: Text(s))).toList(), child: const Padding(padding: EdgeInsets.all(8), child: Text('Status'))),
          TextButton(onPressed: () => widget.onNavigate('attendance'), child: const Text('Attendance')),
        ]),
      ]))),
    ]);
  }

  Widget _eventPicker(Map<String, dynamic> data) {
    final events = _rows(data, 'events').where((e) => e['status'] == 'Upcoming' || e['status'] == 'Ongoing').toList();
    if (events.isEmpty) return _empty('Create an upcoming event first.');
    final selected = events.any((e) => _id(e['id']) == eventId) ? eventId : _id(events.first['id']);
    eventId = selected;
    return Padding(padding: const EdgeInsets.only(bottom: 14), child: DropdownButtonFormField<int>(key: ValueKey(selected), initialValue: selected, decoration: const InputDecoration(labelText: 'Select event', border: OutlineInputBorder()), items: events.map((e) => DropdownMenuItem(value: _id(e['id']), child: Text(_value(e['event_name']), overflow: TextOverflow.ellipsis))).toList(), onChanged: (v) => setState(() => eventId = v)));
  }

  static const songFields = [
    EditField('Title', 'title', required: true), EditField('Artist', 'artist'),
    EditField('Lyrics', 'lyrics', lines: 6), EditField('Chords', 'chords', lines: 6), EditField('Sort order', 'sort_order'),
  ];

  Widget _songs(Map<String, dynamic> data) {
    final firstEvent = _rows(data, 'events').where((e) => e['status'] == 'Upcoming' || e['status'] == 'Ongoing').firstOrNull;
    final selected = eventId ?? (firstEvent == null ? null : _id(firstEvent['id']));
    final rows = _rows(data, 'songs').where((s) => _id(s['event_id']) == selected).toList();
    return Column(children: [
      _heading('Event Lineups', action: IconButton(tooltip: 'Add song', onPressed: selected == null ? null : () => _edit('songs', null, songFields, extra: {'event_id': selected}), icon: const Icon(Icons.add))),
      _eventPicker(data),
      if (rows.isEmpty) _empty('No songs in this event lineup.'),
      ...rows.map((s) => Card(child: ExpansionTile(leading: const Icon(Icons.music_note), title: Text(_value(s['title'])), subtitle: Text(_value(s['artist'])), childrenPadding: const EdgeInsets.fromLTRB(16, 0, 16, 12), children: [
        if (_value(s['lyrics']).isNotEmpty) _card('Lyrics', SelectableText(_value(s['lyrics']))),
        if (_value(s['chords']).isNotEmpty) _card('Chords', SelectableText(_value(s['chords']))),
        Row(children: [TextButton(onPressed: () => _edit('songs', s, songFields, extra: {'event_id': selected}), child: const Text('Edit')), TextButton(onPressed: () => _act({'resource': 'songs', 'action': 'delete', 'id': s['id']}, confirmation: 'Remove this song?'), child: const Text('Remove'))]),
      ]))),
    ]);
  }

  Widget _stations(Map<String, dynamic> data) {
    final firstEvent = _rows(data, 'events').where((e) => e['status'] == 'Upcoming' || e['status'] == 'Ongoing').firstOrNull;
    final selected = eventId ?? (firstEvent == null ? null : _id(firstEvent['id']));
    final rows = _rows(data, 'stations').where((s) => _id(s['event_id']) == selected).toList();
    final assignments = _rows(data, 'assignments');
    final members = _rows(data, 'members').where((m) => m['status'] == 'Active').toList();
    return Column(children: [
      _heading('Event Stations', action: IconButton(tooltip: 'Add station', onPressed: selected == null ? null : () => _edit('stations', null, const [EditField('Station name', 'station_name', required: true)], extra: {'event_id': selected}), icon: const Icon(Icons.add))),
      _eventPicker(data),
      if (rows.isEmpty) _empty('No stations for this event.'),
      ...rows.map((s) {
        final assigned = assignments.where((a) => _id(a['station_id']) == _id(s['id'])).toList();
        return Card(child: ExpansionTile(leading: const Icon(Icons.groups_outlined), title: Text(_value(s['station_name'])), subtitle: Text('${assigned.length} assigned'), children: [
          ...assigned.map((a) => ListTile(title: Text(_value(a['fullname'])), subtitle: Text(_value(a['category'])), trailing: IconButton(tooltip: 'Unassign', onPressed: () => _act({'resource': 'stations', 'action': 'unassign', 'id': a['id']}), icon: const Icon(Icons.remove_circle_outline)))),
          if (members.isNotEmpty) Padding(padding: const EdgeInsets.symmetric(horizontal: 16), child: DropdownButtonFormField<int>(key: ValueKey('assign-${s['id']}-${assigned.length}'), decoration: const InputDecoration(labelText: 'Assign member'), items: members.map((m) => DropdownMenuItem(value: _id(m['id']), child: Text(_value(m['fullname'])))).toList(), onChanged: (id) { if (id != null) _act({'resource': 'stations', 'action': 'assign', 'station_id': s['id'], 'member_id': id}); })),
          TextButton.icon(onPressed: () => _act({'resource': 'stations', 'action': 'delete', 'id': s['id']}, confirmation: 'Remove this station and its assignments?'), icon: const Icon(Icons.delete_outline), label: const Text('Remove station')),
        ]));
      }),
    ]);
  }

  Widget _logs(Map<String, dynamic> data) {
    final rows = _rows(data, 'logs').where((l) => query.isEmpty || '${l['action']} ${l['details']} ${l['user_name']}'.toLowerCase().contains(query)).toList();
    return Column(children: [_heading('System Logs', subtitle: 'Most recent 200 entries'), _search('Search logs'), if (rows.isEmpty) _empty('No matching logs.'), ...rows.map((l) => Card(child: ListTile(leading: const Icon(Icons.list_alt), title: Text(_value(l['action'])), subtitle: Text('${l['user_name']} · ${l['timestamp']}\n${l['details']}'), isThreeLine: true)))]);
  }

  Widget _settings(Map<String, dynamic> data) {
    final profile = Map<String, dynamic>.from((data['profile'] as Map?) ?? {});
    return Column(children: [
      _heading('Settings'),
      _card('Profile Information', Column(children: [_detail('Name', profile['fullname']), _detail('Username', profile['username']), _detail('Role', profile['role']), _detail('Church', profile['church']), _detail('User ID', profile['id'])])),
      _card('Change Password', Column(children: [const Text('Your password is used for web and mobile sign in.'), const SizedBox(height: 12), FilledButton.icon(onPressed: () async {
        final value = await showModalBottomSheet<Map<String, dynamic>>(context: context, isScrollControlled: true, builder: (_) => const EditForm(title: 'Change password', fields: [EditField('Current password', 'current_password', required: true, secret: true), EditField('New password', 'new_password', required: true, secret: true), EditField('Confirm new password', 'confirm_password', required: true, secret: true)]));
        if (value == null) return;
        if (value['new_password'] != value['confirm_password']) { if (mounted) ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('New passwords do not match'))); return; }
        await _act({'resource': 'settings', 'action': 'password', ...value});
      }, icon: const Icon(Icons.lock_outline), label: const Text('Update Password'))])),
      _card('System Information', Column(children: [_detail('Server', widget.controller.baseUrl), _detail('Last download', widget.controller.lastRefresh), _detail('Pending sync', widget.controller.pendingCount), _detail('Needs review', widget.controller.issueCount)])),
    ]);
  }

  Widget _reports(Map<String, dynamic> data) {
    final audit = _rows(data, 'audit').where((r) => (fromDate.isEmpty || _value(r['start_date']).compareTo(fromDate) >= 0) && (toDate.isEmpty || _value(r['start_date']).compareTo(toDate) <= 0) && (eventId == null || _id(r['event_id']) == eventId) && (filter.isEmpty || r['category'] == filter)).toList();
    final present = audit.where((r) => r['status'] == 'Present').length;
    final events = audit.map((r) => _id(r['event_id'])).toSet().length;
    final categories = _rows(data, 'categories').map((c) => _value(c['name'])).toList();
    return Column(children: [
      _heading('Reports', subtitle: 'Attendance summary and records'),
      _card('Report Filters', Column(children: [
        TextFormField(initialValue: fromDate, decoration: const InputDecoration(labelText: 'From date (YYYY-MM-DD)'), onChanged: (v) => setState(() => fromDate = v.trim())),
        TextFormField(initialValue: toDate, decoration: const InputDecoration(labelText: 'To date (YYYY-MM-DD)'), onChanged: (v) => setState(() => toDate = v.trim())),
        DropdownButtonFormField<int?>(initialValue: eventId, decoration: const InputDecoration(labelText: 'Event'), items: [const DropdownMenuItem<int?>(value: null, child: Text('All events')), ..._rows(data, 'events').map((e) => DropdownMenuItem<int?>(value: _id(e['id']), child: Text(_value(e['event_name']), overflow: TextOverflow.ellipsis)))], onChanged: (v) => setState(() => eventId = v)),
        DropdownButtonFormField<String>(initialValue: filter, decoration: const InputDecoration(labelText: 'Category'), items: [const DropdownMenuItem(value: '', child: Text('All categories')), ...categories.map((c) => DropdownMenuItem(value: c, child: Text(c)))], onChanged: (v) => setState(() => filter = v ?? '')),
      ])),
      Wrap(spacing: 8, runSpacing: 8, children: [_metric('Total Events', '$events', Icons.event), _metric('Total Present', '$present', Icons.check_circle), _metric('Records', '${audit.length}', Icons.bar_chart)]),
      const SizedBox(height: 14),
      _card('Attendance Report', Column(children: [
        Align(alignment: Alignment.centerRight, child: TextButton.icon(onPressed: audit.isEmpty ? null : () async {
          final csv = <String>['Event,Date,Member,Category,Status,Method', ...audit.map((r) => [r['event_name'], r['start_date'], r['fullname'], r['category'], r['status'], r['method']].map((v) => '"${_value(v).replaceAll('"', '""')}"').join(','))].join('\n');
          await Clipboard.setData(ClipboardData(text: csv));
          if (mounted) ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('CSV copied to clipboard')));
        }, icon: const Icon(Icons.copy), label: const Text('Copy CSV'))),
        if (audit.isEmpty) const Text('No records in this selection.'),
        ...audit.take(100).map((r) => ListTile(dense: true, title: Text(_value(r['fullname'])), subtitle: Text('${r['event_name']} · ${r['start_date']}'), trailing: Text(_value(r['status'])))),
        if (audit.length > 100) Text('Showing 100 of ${audit.length} records'),
      ])),
    ]);
  }
}

class EditField {
  final String label, key;
  final bool required, secret;
  final int lines;
  const EditField(this.label, this.key, {this.required = false, this.secret = false, this.lines = 1});
}

class EditForm extends StatefulWidget {
  final String title;
  final Map<String, dynamic>? row;
  final List<EditField> fields;
  const EditForm({super.key, required this.title, this.row, required this.fields});
  @override
  State<EditForm> createState() => _EditFormState();
}

class _EditFormState extends State<EditForm> {
  final form = GlobalKey<FormState>();
  final values = <String, TextEditingController>{};
  bool recurring = false;
  @override
  void initState() {
    super.initState();
    for (final field in widget.fields) values[field.key] = TextEditingController(text: _value(widget.row?[field.key] ?? (field.key == 'status' ? 'Active' : field.key == 'type' ? 'Sunday Service' : '')));
  }
  @override
  void dispose() {
    for (final controller in values.values) { controller.dispose(); }
    super.dispose();
  }
  @override
  Widget build(BuildContext context) => Padding(padding: EdgeInsets.fromLTRB(20, 12, 20, MediaQuery.viewInsetsOf(context).bottom + 20), child: ConstrainedBox(constraints: BoxConstraints(maxHeight: MediaQuery.sizeOf(context).height * 0.85), child: Form(key: form, child: ListView(shrinkWrap: true, children: [
    Text(widget.title, style: Theme.of(context).textTheme.headlineSmall),
    const SizedBox(height: 18),
    ...widget.fields.map((field) => Padding(padding: const EdgeInsets.only(bottom: 12), child: TextFormField(controller: values[field.key], obscureText: field.secret, maxLines: field.secret ? 1 : field.lines, decoration: InputDecoration(labelText: field.label, border: const OutlineInputBorder()), validator: field.required ? (v) => v == null || v.trim().isEmpty ? 'Required' : null : null))),
    if (widget.title == 'Add event') SwitchListTile(title: const Text('Repeat weekly for 52 weeks'), value: recurring, onChanged: (v) => setState(() => recurring = v)),
    FilledButton(onPressed: () {
      if (!form.currentState!.validate()) return;
      Navigator.pop(context, {for (final field in widget.fields) field.key: values[field.key]!.text.trim(), if (recurring) 'is_recurring': true});
    }, child: const Text('Save')),
  ]))));
}
