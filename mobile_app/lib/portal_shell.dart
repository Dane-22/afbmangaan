import 'package:flutter/material.dart';

import 'app_controller.dart';
import 'landing_page.dart';
import 'main.dart';
import 'portal_sections.dart';
import 'report_page.dart';
import 'assistant_page.dart';
import 'attendance_events_page.dart';

class _Destination {
  final String key, label;
  final IconData icon;
  final bool adminOnly;
  const _Destination(this.key, this.label, this.icon, {this.adminOnly = false});
}

const _destinations = [
  _Destination('dashboard', 'Dashboard', Icons.dashboard_outlined),
  _Destination('attendance', 'Attendance', Icons.check_circle_outline),
  _Destination('audit', 'Attendance Audit', Icons.fact_check_outlined),
  _Destination('members', 'Members', Icons.people_outline),
  _Destination('events', 'Events', Icons.event_outlined),
  _Destination('songs', 'Event Lineups', Icons.music_note_outlined),
  _Destination('stations', 'Event Stations', Icons.groups_outlined),
  _Destination('logs', 'System Logs', Icons.list_alt_outlined, adminOnly: true),
  _Destination('settings', 'Settings', Icons.settings_outlined),
];

class PortalShell extends StatefulWidget {
  final AppController controller;
  final VoidCallback onTheme;
  const PortalShell({super.key, required this.controller, required this.onTheme});
  @override
  State<PortalShell> createState() => _PortalShellState();
}

class _PortalShellState extends State<PortalShell> {
  String selected = 'dashboard';
  bool createOnOpen = false;
  int navigationRevision = 0;
  int? selectedEventId;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) => widget.controller.loadPortal());
  }

  Future<void> _logout() async {
    try {
      await widget.controller.signOut();
    } catch (e) {
      if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(e.toString().replaceFirst('Bad state: ', ''))));
    }
  }

  void _select(String key) {
    Navigator.of(context).pop();
    _navigate(key);
  }

  void _navigate(String destination) {
    final parts = destination.split(':');
    final key = parts.first;
    final role = widget.controller.user?.role;
    if (role == 'viewer' && !['dashboard', 'reports', 'welcome'].contains(key)) return;
    if (key == 'logs' && role != 'admin') return;
    setState(() { selected = key; selectedEventId = key == 'attendance' && parts.length > 1 ? int.tryParse(parts[1]) : null; createOnOpen = parts.length > 1 && parts[1] == 'add'; navigationRevision++; });
  }

  Future<void> _assistant() async {
    final destination = await Navigator.push<String>(context, MaterialPageRoute(builder: (_) => AssistantPage(controller: widget.controller, onTheme: widget.onTheme)));
    if (mounted && destination != null) _navigate(destination);
  }

  @override
  Widget build(BuildContext context) => AnimatedBuilder(animation: widget.controller, builder: (context, _) {
    final c = widget.controller;
    final role = c.user?.role ?? '';
    final destinations = role == 'viewer' ? _destinations.where((d) => d.key == 'dashboard').toList() : _destinations.where((d) => !d.adminOnly || role == 'admin').toList();
    final title = selected == 'reports' ? 'Reports' : _destinations.where((d) => d.key == selected).firstOrNull?.label ?? 'Dashboard';
    if (selected == 'welcome') {
      return LandingPage(signedIn: true, onTheme: widget.onTheme, onEnter: () => setState(() => selected = 'dashboard'), onAttendance: role == 'viewer' ? null : () => setState(() => selected = 'attendance'));
    }
    return Scaffold(
      appBar: AppBar(
        title: Text(title, style: const TextStyle(fontFamily: 'Cinzel')),
        actions: [
          if (c.needsSignIn) IconButton(tooltip: 'Sign in again', onPressed: () => Navigator.push(context, MaterialPageRoute(builder: (_) => LoginPage(controller: c, reauthentication: true))), icon: const Icon(Icons.login)),
          if (selected == 'attendance') IconButton(tooltip: 'Sync now', onPressed: c.busy ? null : () => c.sync(), icon: const Icon(Icons.sync)),
          if (!['attendance', 'audit', 'reports'].contains(selected)) IconButton(tooltip: 'Refresh', onPressed: c.portalBusy ? null : c.loadPortal, icon: const Icon(Icons.refresh)),
          IconButton(tooltip: 'Toggle theme', onPressed: widget.onTheme, icon: const Icon(Icons.brightness_6_outlined)),
        ],
      ),
      drawer: Drawer(child: SafeArea(child: Column(children: [
        Container(width: double.infinity, padding: const EdgeInsets.all(22), decoration: const BoxDecoration(gradient: LinearGradient(colors: [Color(0xFF332B17), afbDark])), child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
          const Icon(Icons.church, color: afbGold, size: 36),
          const SizedBox(height: 12),
          const Text('AFB SANTOL', style: TextStyle(color: afbGold, fontFamily: 'Cinzel', fontSize: 22, letterSpacing: 2)),
          const SizedBox(height: 7),
          Text(c.user?.church ?? '', style: const TextStyle(color: Colors.white70)),
          Text('${c.user?.name ?? ''} • ${role.toUpperCase()}', style: const TextStyle(color: Colors.white70, fontSize: 12)),
        ])),
        Expanded(child: ListView(children: [
          ListTile(leading: const Icon(Icons.home_outlined), title: const Text('Home'), onTap: () => _select('welcome')),
          const Divider(),
          ...destinations.map((d) => ListTile(leading: Icon(d.icon), title: Text(d.label), selected: selected == d.key, onTap: () => _select(d.key))),
          const Divider(),
          ListTile(leading: const Icon(Icons.bar_chart_outlined), title: const Text('Reports'), selected: selected == 'reports', onTap: () => _select('reports')),
          ListTile(leading: const Icon(Icons.forum_outlined), title: const Text('Assistant & Group Chat'), onTap: () { Navigator.pop(context); _assistant(); }),
          if (role != 'viewer') ...[
            ListTile(leading: const Icon(Icons.sync_problem_outlined), title: const Text('Sync history and issues'), onTap: () { Navigator.pop(context); Navigator.push(context, MaterialPageRoute(builder: (_) => IssuesPage(controller: c))); }),
            if (role == 'admin') ListTile(leading: const Icon(Icons.rule_outlined), title: const Text('Review conflicts'), onTap: () { Navigator.pop(context); Navigator.push(context, MaterialPageRoute(builder: (_) => ConflictsPage(controller: c))); }),
          ],
        ])),
        ListTile(leading: const Icon(Icons.logout), title: const Text('Sign out'), onTap: () { Navigator.pop(context); _logout(); }),
      ]))),
      body: selected == 'attendance'
              ? AttendanceEventsPage(key: ValueKey('attendance-$selectedEventId'), controller: c, initialEventId: selectedEventId, onNavigate: _navigate)
              : selected == 'reports' || selected == 'audit'
                  ? ReportPage(key: ValueKey(selected), controller: c, audit: selected == 'audit')
              : PortalSection(key: ValueKey('$selected-$navigationRevision'), controller: c, section: selected, createOnOpen: createOnOpen, onNavigate: _navigate),
      floatingActionButton: FloatingActionButton(tooltip: 'Assistant and group chat', onPressed: _assistant, child: const Icon(Icons.auto_awesome)),
    );
  });
}
