import 'package:flutter/material.dart';

import 'app_controller.dart';
import 'main.dart';
import 'models.dart';

class AttendanceEventsPage extends StatefulWidget {
  final AppController controller;
  final int? initialEventId;
  final ValueChanged<String> onNavigate;
  const AttendanceEventsPage({super.key, required this.controller, this.initialEventId, required this.onNavigate});
  @override
  State<AttendanceEventsPage> createState() => _AttendanceEventsPageState();
}

class _AttendanceEventsPageState extends State<AttendanceEventsPage> {
  String search = '', status = '';
  bool offlineOnly = false;
  DateTimeRange? range;
  bool opening = false;
  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) async {
      await widget.controller.sync(silent: true);
      if (!mounted || widget.initialEventId == null) return;
      final event = widget.controller.events.where((event) => event.id == widget.initialEventId).firstOrNull;
      if (event != null) { await open(event); }
      else if (mounted) { ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('This event is not downloaded. Refresh or sign in to download it.'))); }
    });
  }

  Future<void> open(MobileEvent event) async {
    if (opening) return;
    setState(() => opening = true);
    try {
      if (!widget.controller.detailDownloads.containsKey(event.id)) {
        try { await widget.controller.downloadEvent(event.id); }
        catch (error) { if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(error.toString()))); }
      }
      if (!mounted) return;
      final current = widget.controller.events.where((item) => item.id == event.id).firstOrNull ?? event;
      await Navigator.push(context, MaterialPageRoute(builder: (_) => EventPage(controller: widget.controller, event: current)));
    } finally {
      if (mounted) setState(() => opening = false);
    }
  }

  @override
  Widget build(BuildContext context) => AnimatedBuilder(animation: widget.controller, builder: (context, _) {
    final c = widget.controller;
    final rows = c.events.where((event) => (status.isEmpty || event.status == status) && (search.isEmpty || '${event.name} ${event.location ?? ''}'.toLowerCase().contains(search)) && (!offlineOnly || c.detailDownloads.containsKey(event.id)) && (range == null || (event.startDate.compareTo(range!.end.toIso8601String().substring(0, 10)) <= 0 && (event.endDate ?? event.startDate).compareTo(range!.start.toIso8601String().substring(0, 10)) >= 0))).toList();
    return RefreshIndicator(onRefresh: () => c.sync(), child: ListView(physics: const AlwaysScrollableScrollPhysics(), padding: const EdgeInsets.fromLTRB(16, 16, 16, 90), children: [
      Card(child: Padding(padding: const EdgeInsets.all(16), child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
        Text('${c.user?.name ?? ''} · ${c.user?.church ?? ''}', style: Theme.of(context).textTheme.titleMedium),
        const SizedBox(height: 8),
        Text('Pending: ${c.pendingCount}    Needs review: ${c.issueCount}'),
        Text('Last event download: ${formatTime(c.lastRefresh)}'),
        if (c.busy || opening) const LinearProgressIndicator(),
        if (c.busy && c.lastRefresh == null) const Text('Downloading events…'),
        if (c.message != null) Padding(padding: const EdgeInsets.only(top: 8), child: Text(c.message!)),
        if (c.needsSignIn) TextButton.icon(onPressed: () => Navigator.push(context, MaterialPageRoute(builder: (_) => LoginPage(controller: c, reauthentication: true))), icon: const Icon(Icons.login), label: const Text('Sign in to download and sync')),
      ]))),
      const SizedBox(height: 12),
      Text('Events', style: Theme.of(context).textTheme.titleLarge),
      const SizedBox(height: 12),
      TextField(decoration: const InputDecoration(prefixIcon: Icon(Icons.search), hintText: 'Search events', border: OutlineInputBorder()), onChanged: (value) => setState(() => search = value.trim().toLowerCase())),
      const SizedBox(height: 8),
      SingleChildScrollView(scrollDirection: Axis.horizontal, child: Row(children: ['', 'Upcoming', 'Ongoing', 'Completed', 'Cancelled', 'Archived'].map((value) => Padding(padding: const EdgeInsets.only(right: 6), child: ChoiceChip(label: Text(value.isEmpty ? 'All' : value), selected: status == value, onSelected: (_) => setState(() => status = value)))).toList())),
      Wrap(spacing: 8, crossAxisAlignment: WrapCrossAlignment.center, children: [
        FilterChip(label: const Text('Available offline'), selected: offlineOnly, onSelected: (value) => setState(() => offlineOnly = value)),
        TextButton.icon(icon: const Icon(Icons.date_range), label: Text(range == null ? 'Date range' : '${range!.start.toIso8601String().substring(0, 10)} – ${range!.end.toIso8601String().substring(0, 10)}'), onPressed: () async {
          final picked = await showDateRangePicker(context: context, firstDate: DateTime(2000), lastDate: DateTime(2100), initialDateRange: range);
          if (picked != null && mounted) setState(() => range = picked);
        }),
        if (status.isNotEmpty || offlineOnly || range != null) TextButton(onPressed: () => setState(() { status = ''; offlineOnly = false; range = null; }), child: const Text('Clear filters')),
      ]),
      if (c.events.isEmpty && !c.busy) Card(child: Padding(padding: const EdgeInsets.all(20), child: Column(children: [
        Text(c.lastRefresh == null ? 'Download events for offline attendance.' : 'No events exist for this church in the last successful download.'),
        TextButton.icon(onPressed: () => c.sync(), icon: const Icon(Icons.download), label: const Text('Download events')),
        if (c.lastRefresh != null) TextButton(onPressed: () => widget.onNavigate('events:add'), child: const Text('Create event')),
      ]))),
      if (c.events.isNotEmpty && rows.isEmpty) const Padding(padding: EdgeInsets.all(20), child: Text('No events match these filters. Clear filters or change your search.')),
      ...rows.map((event) {
        final saved = c.detailDownloads[event.id];
        return Card(child: ListTile(leading: const Icon(Icons.event), title: Text(event.name), subtitle: Text('${event.startDate} · ${event.status}\n${saved == null ? 'Attendance details not downloaded' : 'Available offline · saved ${formatTime(saved)}'}'), isThreeLine: true, trailing: Icon(saved == null ? Icons.cloud_download_outlined : Icons.offline_pin_outlined), onTap: opening ? null : () => open(event)));
      }),
    ]));
  });
}
