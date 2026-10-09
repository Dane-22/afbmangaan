import 'package:flutter/material.dart';
import 'package:local_auth/local_auth.dart';
import 'package:mobile_scanner/mobile_scanner.dart';
import 'package:workmanager/workmanager.dart';

import 'app_controller.dart';
import 'background_sync.dart';
import 'models.dart';
import 'landing_page.dart';
import 'portal_shell.dart';

Future<void> main() async {
  WidgetsFlutterBinding.ensureInitialized();
  await Workmanager().initialize(mobileCallbackDispatcher);
  runApp(const AttendanceApp());
}

class AttendanceApp extends StatefulWidget {
  const AttendanceApp({super.key});
  @override
  State<AttendanceApp> createState() => _AttendanceAppState();
}

class _AttendanceAppState extends State<AttendanceApp> {
  final controller = AppController();
  bool dark = true;
  @override
  void initState() {
    super.initState();
    controller.initialize();
  }

  @override
  void dispose() {
    controller.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) => MaterialApp(
        title: 'AFB Santol',
        themeMode: dark ? ThemeMode.dark : ThemeMode.light,
        theme: ThemeData(colorScheme: ColorScheme.fromSeed(seedColor: const Color(0xFFC9A227), surface: const Color(0xFFFAF9F6)), useMaterial3: true),
        darkTheme: ThemeData(colorScheme: ColorScheme.fromSeed(seedColor: afbGold, brightness: Brightness.dark, surface: afbDark), useMaterial3: true),
        home: AnimatedBuilder(
          animation: controller,
          builder: (context, _) {
            if (!controller.ready) return const Scaffold(body: Center(child: CircularProgressIndicator()));
            return controller.user == null
                ? LandingPage(signedIn: false, onTheme: () => setState(() => dark = !dark), onEnter: () => Navigator.of(context).push(MaterialPageRoute(builder: (_) => LoginPage(controller: controller))))
                : HomeGate(controller: controller, onTheme: () => setState(() => dark = !dark));
          },
        ),
      );
}

class HomeGate extends StatefulWidget {
  final AppController controller;
  final VoidCallback onTheme;
  const HomeGate({super.key, required this.controller, required this.onTheme});
  @override
  State<HomeGate> createState() => _HomeGateState();
}

class _HomeGateState extends State<HomeGate> with WidgetsBindingObserver {
  final LocalAuthentication auth = LocalAuthentication();
  bool unlocked = false;
  bool authenticating = false;
  String? error;
  DateTime? leftAt;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addObserver(this);
    WidgetsBinding.instance.addPostFrameCallback((_) => unlock());
  }

  @override
  void dispose() {
    WidgetsBinding.instance.removeObserver(this);
    super.dispose();
  }

  @override
  void didChangeAppLifecycleState(AppLifecycleState state) {
    if (authenticating) return;
    if (state == AppLifecycleState.paused) leftAt = DateTime.now();
    if (state == AppLifecycleState.resumed && leftAt != null && DateTime.now().difference(leftAt!) > const Duration(seconds: 30)) {
      setState(() => unlocked = false);
      unlock();
    }
  }

  Future<void> unlock() async {
    if (!mounted || authenticating) return;
    setState(() { authenticating = true; error = null; });
    try {
      if (!await auth.isDeviceSupported()) {
        throw StateError('Set a screen lock (PIN, pattern, or biometrics) in Android settings to protect offline attendance.');
      }
      final ok = await auth.authenticate(localizedReason: 'Unlock AFB attendance', persistAcrossBackgrounding: true);
      if (mounted) setState(() => unlocked = ok);
    } catch (e) {
      if (mounted) setState(() => error = e.toString().replaceFirst('Bad state: ', ''));
    } finally {
      if (mounted) setState(() => authenticating = false);
    }
  }

  @override
  Widget build(BuildContext context) => unlocked
      ? PortalShell(controller: widget.controller, onTheme: widget.onTheme)
      : Scaffold(
          appBar: AppBar(title: const Text('Unlock attendance')),
          body: Center(child: Padding(padding: const EdgeInsets.all(24), child: Column(mainAxisSize: MainAxisSize.min, children: [
            const Icon(Icons.lock_outline, size: 64),
            const SizedBox(height: 16),
            Text(error ?? 'Use your device screen lock to open saved attendance.', textAlign: TextAlign.center),
            const SizedBox(height: 16),
            FilledButton(onPressed: authenticating ? null : unlock, child: Text(authenticating ? 'Unlocking…' : 'Unlock')),
          ]))),
        );
}

class LoginPage extends StatefulWidget {
  final AppController controller;
  final bool reauthentication;
  const LoginPage({super.key, required this.controller, this.reauthentication = false});
  @override
  State<LoginPage> createState() => _LoginPageState();
}

class _LoginPageState extends State<LoginPage> {
  final formKey = GlobalKey<FormState>();
  final url = TextEditingController();
  final username = TextEditingController();
  final password = TextEditingController();
  String church = 'AFB Mangaan';
  String? error;
  bool submitting = false;

  @override
  void initState() {
    super.initState();
    url.text = widget.controller.baseUrl ?? 'https://constra.xandree.com';
    church = widget.controller.user?.church ?? 'AFB Mangaan';
  }

  @override
  void dispose() {
    url.dispose();
    username.dispose();
    password.dispose();
    super.dispose();
  }

  Future<void> submit() async {
    if (!formKey.currentState!.validate()) return;
    setState(() { submitting = true; error = null; });
    try {
      await widget.controller.login(url.text, username.text, password.text, church);
      if (mounted) Navigator.of(context).pop();
    } catch (e) {
      if (mounted) setState(() => error = e.toString().replaceFirst('Bad state: ', ''));
    } finally {
      if (mounted) setState(() => submitting = false);
    }
  }

  @override
  Widget build(BuildContext context) => Scaffold(
        appBar: AppBar(title: Text(widget.reauthentication ? 'Sign in to sync' : 'AFB Santol')),
        body: Center(
          child: SingleChildScrollView(
            padding: const EdgeInsets.all(24),
            child: ConstrainedBox(
              constraints: const BoxConstraints(maxWidth: 480),
              child: Form(
                key: formKey,
                child: Column(crossAxisAlignment: CrossAxisAlignment.stretch, children: [
                  const Icon(Icons.church, size: 72, color: afbGold),
                  const SizedBox(height: 16),
                  Text(widget.reauthentication ? 'Reconnect your account' : 'Welcome Back', textAlign: TextAlign.center, style: Theme.of(context).textTheme.titleLarge),
                  const SizedBox(height: 24),
                  TextFormField(controller: url, keyboardType: TextInputType.url, decoration: const InputDecoration(labelText: 'Server URL', hintText: 'https://your-site.example', border: OutlineInputBorder()), validator: (value) => (value == null || value.trim().isEmpty) ? 'Enter the server URL' : null),
                  const SizedBox(height: 12),
                  DropdownButtonFormField<String>(initialValue: church, decoration: const InputDecoration(labelText: 'Church', border: OutlineInputBorder()), items: const [DropdownMenuItem(value: 'AFB Mangaan', child: Text('AFB Mangaan')), DropdownMenuItem(value: 'AFB Lettac Sur', child: Text('AFB Lettac Sur'))], onChanged: (value) => setState(() => church = value!)),
                  const SizedBox(height: 12),
                  TextFormField(controller: username, decoration: const InputDecoration(labelText: 'Username', border: OutlineInputBorder()), validator: (value) => (value == null || value.trim().isEmpty) ? 'Enter your username' : null),
                  const SizedBox(height: 12),
                  TextFormField(controller: password, obscureText: true, decoration: const InputDecoration(labelText: 'Password', border: OutlineInputBorder()), validator: (value) => (value == null || value.isEmpty) ? 'Enter your password' : null),
                  if (error != null) Padding(padding: const EdgeInsets.only(top: 12), child: Text(error!, style: TextStyle(color: Theme.of(context).colorScheme.error))),
                  const SizedBox(height: 20),
                  FilledButton(onPressed: submitting ? null : submit, child: Text(submitting ? 'Signing in…' : 'Sign in')),
                  const SizedBox(height: 12),
                  const Text('After the first download, attendance remains available offline. Sync requires a valid online sign-in.', textAlign: TextAlign.center),
                ]),
              ),
            ),
          ),
        ),
      );
}

class HomePage extends StatefulWidget {
  final AppController controller;
  final bool embedded;
  const HomePage({super.key, required this.controller, this.embedded = false});
  @override
  State<HomePage> createState() => _HomePageState();
}

class _HomePageState extends State<HomePage> with WidgetsBindingObserver {
  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addObserver(this);
  }

  @override
  void dispose() {
    WidgetsBinding.instance.removeObserver(this);
    super.dispose();
  }

  @override
  void didChangeAppLifecycleState(AppLifecycleState state) {
    if (state == AppLifecycleState.resumed) widget.controller.sync(silent: true);
  }

  @override
  Widget build(BuildContext context) {
    final c = widget.controller;
    return AnimatedBuilder(animation: c, builder: (context, _) => Scaffold(
          appBar: widget.embedded ? null : AppBar(title: const Text('Attendance'), actions: [
            IconButton(tooltip: 'Sync now', onPressed: c.busy ? null : () => c.sync(), icon: const Icon(Icons.sync)),
            PopupMenuButton<String>(onSelected: (choice) async {
              if (choice == 'issues') Navigator.push(context, MaterialPageRoute(builder: (_) => IssuesPage(controller: c)));
              if (choice == 'conflicts') Navigator.push(context, MaterialPageRoute(builder: (_) => ConflictsPage(controller: c)));
              if (choice == 'logout') {
                try { await c.signOut(); } catch (e) { if (context.mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(e.toString()))); }
              }
            }, itemBuilder: (_) => [
              const PopupMenuItem(value: 'issues', child: Text('Sync history and issues')),
              if (c.user?.role == 'admin') const PopupMenuItem(value: 'conflicts', child: Text('Review conflicts')),
              const PopupMenuItem(value: 'logout', child: Text('Sign out')),
            ]),
          ]),
          body: RefreshIndicator(
            onRefresh: () => c.sync(),
            child: ListView(padding: const EdgeInsets.all(16), children: [
              Card(child: Padding(padding: const EdgeInsets.all(16), child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                Text('${c.user!.name} · ${c.user!.church}', style: Theme.of(context).textTheme.titleMedium),
                const SizedBox(height: 8),
                Text('Pending: ${c.pendingCount}    Needs review: ${c.issueCount}'),
                Text('Last download: ${formatTime(c.lastRefresh)}'),
                if (c.busy) const LinearProgressIndicator(),
                if (c.message != null) Padding(padding: const EdgeInsets.only(top: 8), child: Text(c.message!)),
                if (c.needsSignIn) Padding(padding: const EdgeInsets.only(top: 12), child: FilledButton.icon(
                  onPressed: () => Navigator.push(context, MaterialPageRoute(builder: (_) => LoginPage(controller: c, reauthentication: true))),
                  icon: const Icon(Icons.login), label: const Text('Sign in to sync'),
                )),
              ]))),
              const SizedBox(height: 12),
              Text('Events', style: Theme.of(context).textTheme.titleLarge),
              if (c.events.isEmpty) const Padding(padding: EdgeInsets.all(20), child: Text('No events downloaded. Connect and sync before going offline.')),
              ...c.events.map((event) => Card(child: ListTile(
                leading: const Icon(Icons.event),
                title: Text(event.name),
                subtitle: Text('${event.startDate} · ${event.status}${event.location == null ? '' : ' · ${event.location}'}'),
                trailing: const Icon(Icons.chevron_right),
                onTap: () => Navigator.push(context, MaterialPageRoute(builder: (_) => EventPage(controller: c, event: event))),
              ))),
            ]),
          ),
        ));
  }
}

String formatTime(String? utc) {
  if (utc == null) return 'Never';
  final time = DateTime.tryParse(utc)?.toLocal();
  if (time == null) return utc;
  final hour = time.hour.toString().padLeft(2, '0');
  final minute = time.minute.toString().padLeft(2, '0');
  return '${time.year}-${time.month.toString().padLeft(2, '0')}-${time.day.toString().padLeft(2, '0')} $hour:$minute';
}

class EventPage extends StatefulWidget {
  final AppController controller;
  final MobileEvent event;
  const EventPage({super.key, required this.controller, required this.event});
  @override
  State<EventPage> createState() => _EventPageState();
}

class _EventPageState extends State<EventPage> {
  String query = '';

  Future<void> mark(MobileMember member, String status, String method) async {
    try {
      await widget.controller.record(widget.event, member, status, method);
      if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('$status queued for ${member.name}')));
    } catch (e) {
      if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(e.toString().replaceFirst('Bad state: ', ''))));
    }
  }

  @override
  Widget build(BuildContext context) => AnimatedBuilder(animation: widget.controller, builder: (context, _) {
        final c = widget.controller;
        final filtered = c.members.where((m) => m.name.toLowerCase().contains(query.toLowerCase()) || (m.qrToken ?? '').toLowerCase().contains(query.toLowerCase())).toList();
        final today = DateTime.now().toUtc().add(const Duration(hours: 8)).toIso8601String().substring(0, 10);
        final canRecord = widget.event.status != 'Cancelled' && today.compareTo(widget.event.startDate) >= 0 && today.compareTo(widget.event.endDate ?? widget.event.startDate) <= 0;
        return Scaffold(
          appBar: AppBar(title: Text(widget.event.name), actions: [IconButton(tooltip: 'Scan QR code', onPressed: canRecord ? () => Navigator.push(context, MaterialPageRoute(builder: (_) => ScanPage(controller: c, event: widget.event))) : null, icon: const Icon(Icons.qr_code_scanner))]),
          body: Column(children: [
            Padding(padding: const EdgeInsets.all(16), child: Column(crossAxisAlignment: CrossAxisAlignment.stretch, children: [
              Text('${widget.event.startDate} · ${widget.event.status}'),
              if (!canRecord) const Padding(padding: EdgeInsets.only(top: 8), child: Text('Recording is available on the event date. Sync to check for changes.')),
              const SizedBox(height: 12),
              TextField(decoration: const InputDecoration(prefixIcon: Icon(Icons.search), hintText: 'Search member or QR code', border: OutlineInputBorder()), onChanged: (value) => setState(() => query = value)),
            ])),
            Expanded(child: ListView.builder(itemCount: filtered.length, itemBuilder: (context, index) {
              final member = filtered[index];
              final current = c.attendance.where((a) => a.eventId == widget.event.id && a.memberId == member.id).firstOrNull;
              final pending = c.actions.where((a) => a.eventId == widget.event.id && a.memberId == member.id && a.syncState == 'pending').firstOrNull;
              return Card(margin: const EdgeInsets.symmetric(horizontal: 16, vertical: 4), child: Padding(padding: const EdgeInsets.all(12), child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                Text(member.name, style: Theme.of(context).textTheme.titleMedium),
                Text('${member.category ?? ''} · ${member.qrToken ?? 'No QR code'}'),
                Text(pending != null ? '${pending.status} · Pending sync' : (current?.status ?? 'Not recorded')),
                const SizedBox(height: 8),
                Wrap(spacing: 8, children: [
                  OutlinedButton(onPressed: canRecord ? () => mark(member, 'Absent', 'Manual') : null, child: const Text('Absent')),
                  FilledButton(onPressed: canRecord ? () => mark(member, 'Present', 'Manual') : null, child: const Text('Present')),
                ]),
              ])));
            })),
          ]),
        );
      });
}

class ScanPage extends StatefulWidget {
  final AppController controller;
  final MobileEvent event;
  const ScanPage({super.key, required this.controller, required this.event});
  @override
  State<ScanPage> createState() => _ScanPageState();
}

class _ScanPageState extends State<ScanPage> {
  bool handling = false;
  String? error;

  Future<void> detected(BarcodeCapture capture) async {
    if (handling || capture.barcodes.isEmpty) return;
    final token = capture.barcodes.first.rawValue?.trim();
    if (token == null || token.isEmpty) return;
    setState(() => handling = true);
    final member = widget.controller.members.where((m) => m.qrToken == token).firstOrNull;
    if (member == null) {
      setState(() { error = 'QR code is not in the downloaded member list'; handling = false; });
      return;
    }
    try {
      await widget.controller.record(widget.event, member, 'Present', 'QR Scan');
      if (mounted) Navigator.pop(context, member.name);
    } catch (e) {
      if (mounted) setState(() { error = e.toString(); handling = false; });
    }
  }

  @override
  Widget build(BuildContext context) => Scaffold(
        appBar: AppBar(title: const Text('Scan member QR')),
        body: Column(children: [
          Expanded(child: MobileScanner(onDetect: detected)),
          Padding(padding: const EdgeInsets.all(16), child: Text(error ?? 'Point the camera at a member QR code. A successful scan is queued immediately.')),
        ]),
      );
}

class IssuesPage extends StatelessWidget {
  final AppController controller;
  const IssuesPage({super.key, required this.controller});
  @override
  Widget build(BuildContext context) => AnimatedBuilder(animation: controller, builder: (context, _) => Scaffold(
        appBar: AppBar(title: const Text('Sync history')),
        body: ListView(children: controller.actions.map((action) {
          final member = controller.members.where((m) => m.id == action.memberId).firstOrNull;
          return ListTile(
            leading: Icon(action.syncState == 'pending' ? Icons.cloud_upload_outlined : action.syncState == 'conflict' || action.syncState == 'rejected' ? Icons.warning_amber : Icons.check_circle_outline),
            title: Text('${member?.name ?? 'Member #${action.memberId}'} · ${action.status}'),
            subtitle: Text('${action.syncState.toUpperCase()} · ${formatTime(action.occurredAtUtc)}${action.reason == null ? '' : '\n${action.reason}'}'),
            isThreeLine: action.reason != null,
          );
        }).toList()),
      ));
}

class ConflictsPage extends StatefulWidget {
  final AppController controller;
  const ConflictsPage({super.key, required this.controller});
  @override
  State<ConflictsPage> createState() => _ConflictsPageState();
}

class _ConflictsPageState extends State<ConflictsPage> {
  late Future<List<Map<String, dynamic>>> future;
  @override
  void initState() {
    super.initState();
    future = widget.controller.conflicts();
  }

  void refresh() => setState(() => future = widget.controller.conflicts());

  Future<void> decide(Map<String, dynamic> item, String decision) async {
    try {
      await widget.controller.resolve(item['client_id'] as String, decision, item['current_status'] as String?, item['current_log_time_utc'] as String?);
      if (mounted) refresh();
    } catch (e) {
      if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(e.toString())));
    }
  }

  @override
  Widget build(BuildContext context) => Scaffold(
        appBar: AppBar(title: const Text('Review conflicts'), actions: [IconButton(onPressed: refresh, icon: const Icon(Icons.refresh))]),
        body: FutureBuilder<List<Map<String, dynamic>>>(future: future, builder: (context, snapshot) {
          if (!snapshot.hasData) return Center(child: Text(snapshot.hasError ? snapshot.error.toString() : 'Loading…'));
          final conflicts = snapshot.data!;
          if (conflicts.isEmpty) return const Center(child: Text('No conflicts to review'));
          return ListView(children: conflicts.map((item) => Card(margin: const EdgeInsets.all(12), child: Padding(padding: const EdgeInsets.all(16), child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
            Text('${item['fullname'] ?? 'Member #${item['attendee_id']}'} · ${item['event_name'] ?? 'Event #${item['event_id']}'}', style: Theme.of(context).textTheme.titleMedium),
            Text('Server: ${item['current_status'] ?? 'No record'}   Mobile: ${item['requested_status']}'),
            Text('Captured: ${formatTime(item['occurred_at_utc'] as String?)} · ${item['method']}'),
            Text(item['reason']?.toString() ?? ''),
            const SizedBox(height: 12),
            Wrap(spacing: 8, children: [
              OutlinedButton(onPressed: () => decide(item, 'keep'), child: const Text('Keep server result')),
              FilledButton(onPressed: () => decide(item, 'apply'), child: const Text('Apply mobile result')),
            ]),
          ])))).toList());
        }),
      );
}
