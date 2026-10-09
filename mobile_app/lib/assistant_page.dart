import 'dart:async';

import 'package:flutter/material.dart';

import 'app_controller.dart';
import 'native_export.dart';

List<Map<String, dynamic>> _items(Object? value) => ((value as List?) ?? []).map((item) => Map<String, dynamic>.from(item as Map)).toList();
String _text(Object? value) => value?.toString() ?? '';

class AssistantPage extends StatefulWidget {
  final AppController controller;
  final VoidCallback onTheme;
  const AssistantPage({super.key, required this.controller, required this.onTheme});
  @override
  State<AssistantPage> createState() => _AssistantPageState();
}

class _AssistantPageState extends State<AssistantPage> with SingleTickerProviderStateMixin {
  late final TabController tabs;
  @override
  void initState() { super.initState(); tabs = TabController(length: 2, vsync: this)..addListener(_changed); }
  void _changed() { if (mounted) setState(() {}); }
  @override
  void dispose() { tabs.removeListener(_changed); tabs.dispose(); super.dispose(); }
  @override
  Widget build(BuildContext context) => Scaffold(
    appBar: AppBar(title: const Text('AFB Assistant'), bottom: TabBar(controller: tabs, tabs: const [Tab(icon: Icon(Icons.auto_awesome), text: 'AI Assistant'), Tab(icon: Icon(Icons.forum_outlined), text: 'Group Chat')])),
    body: TabBarView(controller: tabs, children: [_AssistantPanel(controller: widget.controller, onTheme: widget.onTheme), _ChatPanel(controller: widget.controller, active: tabs.index == 1)]),
  );
}

class _AssistantPanel extends StatefulWidget {
  final AppController controller;
  final VoidCallback onTheme;
  const _AssistantPanel({required this.controller, required this.onTheme});
  @override
  State<_AssistantPanel> createState() => _AssistantPanelState();
}

class _AssistantPanelState extends State<_AssistantPanel> {
  final input = TextEditingController();
  final messages = <Map<String, dynamic>>[{'reply': 'Welcome! Ask about attendance, members, events, or open a page.'}];
  bool busy = false;

  @override
  void dispose() { input.dispose(); super.dispose(); }

  Future<void> send([String? suggested]) async {
    final query = (suggested ?? input.text).trim();
    if (query.isEmpty || busy) return;
    input.clear();
    setState(() { messages.add({'query': query}); busy = true; });
    try {
      final response = await widget.controller.assistant(query);
      if (!mounted) return;
      setState(() => messages.add(response));
      final command = response['action_command'];
      if (command is Map) {
        if (command['type'] == 'TOGGLE_THEME') widget.onTheme();
        if (command['type'] == 'NAVIGATE' || command['type'] == 'OPEN_MODAL') {
          final path = _text(command['url'] ?? command['fallback_url']).split('?').first;
          const routes = {'dashboard.php': 'dashboard', 'attendance.php': 'attendance', 'attendance_audit.php': 'audit', 'members.php': 'members', 'events.php': 'events', 'event_lineup.php': 'songs', 'event_stations.php': 'stations', 'reports.php': 'reports', 'logs.php': 'logs', 'settings.php': 'settings', 'change_password.php': 'settings'};
          final route = routes[path];
          if (route != null) {
            final role = widget.controller.user?.role;
            if ((role == 'viewer' && route != 'dashboard' && route != 'reports') || (route == 'logs' && role != 'admin')) {
              setState(() => messages.add({'reply': 'This page is not available for your account role.'}));
            } else {
              Navigator.pop(context, command['type'] == 'OPEN_MODAL' ? '$route:add' : route);
            }
          }
        }
        if (command['type'] == 'EXPORT') {
          final members = _items(widget.controller.portalData?['members']);
          if (widget.controller.user?.role != 'viewer') await NativeExport.table('csv', 'afb_members', 'AFB Members', const ['Name', 'Category', 'Contact', 'Email', 'QR Token'], members.map((m) => ['fullname', 'category', 'contact', 'email', 'qr_token'].map((key) => _text(m[key])).toList()).toList());
        }
      }
      if (widget.controller.user?.role != 'viewer') unawaited(widget.controller.sync(silent: true));
    } catch (e) {
      if (mounted) setState(() => messages.add({'reply': e.toString().replaceFirst('Bad state: ', '')}));
    } finally {
      if (mounted) setState(() => busy = false);
    }
  }

  @override
  Widget build(BuildContext context) => Column(children: [
    Expanded(child: ListView.builder(reverse: true, padding: const EdgeInsets.all(16), itemCount: messages.length, itemBuilder: (_, index) {
      final message = messages[messages.length - 1 - index];
      final mine = message['query'] != null;
      return Align(alignment: mine ? Alignment.centerRight : Alignment.centerLeft, child: Card(color: mine ? Theme.of(context).colorScheme.primaryContainer : null, child: Padding(padding: const EdgeInsets.all(14), child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
        _Reply(_text(message['query'] ?? message['reply'])),
        if (!mine) Wrap(spacing: 6, children: _items(message['quick_actions']).map((q) => ActionChip(label: Text(_text(q['label'])), onPressed: busy ? null : () => send(_text(q['query'])))).toList()),
      ]))));
    })),
    if (busy) const LinearProgressIndicator(),
    SafeArea(top: false, child: Padding(padding: const EdgeInsets.all(12), child: Row(children: [Expanded(child: TextField(controller: input, maxLines: null, decoration: const InputDecoration(hintText: 'Ask your assistant…', border: OutlineInputBorder()), onSubmitted: (_) => send())), IconButton(tooltip: 'Send', onPressed: busy ? null : () => send(), icon: const Icon(Icons.send))]))),
  ]);
}

class _Reply extends StatelessWidget {
  final String text;
  const _Reply(this.text);
  @override
  Widget build(BuildContext context) {
    final decoded = text.replaceAll('&amp;', '&').replaceAll('&lt;', '<').replaceAll('&gt;', '>').replaceAll('&#039;', "'").replaceAll('&quot;', '"');
    final spans = <TextSpan>[];
    var offset = 0;
    for (final match in RegExp(r'\*\*(.*?)\*\*', dotAll: true).allMatches(decoded)) {
      spans.add(TextSpan(text: decoded.substring(offset, match.start)));
      spans.add(TextSpan(text: match.group(1), style: const TextStyle(fontWeight: FontWeight.bold)));
      offset = match.end;
    }
    spans.add(TextSpan(text: decoded.substring(offset)));
    return SelectableText.rich(TextSpan(style: Theme.of(context).textTheme.bodyMedium, children: spans));
  }
}

class _ChatPanel extends StatefulWidget {
  final AppController controller;
  final bool active;
  const _ChatPanel({required this.controller, required this.active});
  @override
  State<_ChatPanel> createState() => _ChatPanelState();
}

class _ChatPanelState extends State<_ChatPanel> with WidgetsBindingObserver {
  final input = TextEditingController();
  List<Map<String, dynamic>> rooms = [], messages = [];
  Map<String, dynamic>? room, reply;
  String search = '';
  String? error;
  bool loading = false, sending = false, foreground = true;
  Timer? timer;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addObserver(this);
    WidgetsBinding.instance.addPostFrameCallback((_) => refresh());
    timer = Timer.periodic(const Duration(seconds: 3), (_) { if (widget.active && foreground) refresh(silent: true); });
  }
  @override
  void dispose() { timer?.cancel(); WidgetsBinding.instance.removeObserver(this); input.dispose(); super.dispose(); }
  @override
  void didChangeAppLifecycleState(AppLifecycleState state) { foreground = state == AppLifecycleState.resumed; }

  Future<void> refresh({bool silent = false}) async {
    if (loading || !mounted) return;
    final roomId = room?['id'];
    setState(() => loading = true);
    try {
      final response = await widget.controller.chat({'action': roomId == null ? 'get_rooms' : 'get_messages', if (roomId != null) 'room_id': roomId});
      if (!mounted || room?['id'] != roomId) return;
      setState(() { if (roomId == null) { rooms = _items(response['rooms']); } else { messages = _items(response['messages']); } error = null; });
    } catch (e) {
      if (mounted && !silent) setState(() => error = e.toString().replaceFirst('Bad state: ', ''));
    } finally {
      if (mounted) setState(() => loading = false);
    }
  }

  Future<void> action(Map<String, dynamic> value) async {
    try {
      await widget.controller.chat(value);
      await refresh();
    } catch (e) {
      if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(e.toString().replaceFirst('Bad state: ', ''))));
    }
  }

  Future<void> createRoom() async {
    final name = TextEditingController();
    final value = await showDialog<String>(context: context, builder: (context) => AlertDialog(title: const Text('Create group'), content: TextField(controller: name, maxLength: 150, decoration: const InputDecoration(labelText: 'Group name')), actions: [TextButton(onPressed: () => Navigator.pop(context), child: const Text('Cancel')), FilledButton(onPressed: () => Navigator.pop(context, name.text.trim()), child: const Text('Create'))]));
    name.dispose();
    if (value == null || value.isEmpty) return;
    await action({'action': 'create_room', 'name': value});
  }

  Future<void> send() async {
    if (sending || room == null || input.text.trim().isEmpty) return;
    final text = input.text.trim();
    final replyId = reply?['id'];
    setState(() => sending = true);
    try {
      await widget.controller.chat({'action': 'send_message', 'room_id': room!['id'], 'message': text, if (replyId != null) 'reply_to_id': replyId});
      if (mounted) { input.clear(); setState(() => reply = null); await refresh(); }
    } catch (e) {
      if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(e.toString().replaceFirst('Bad state: ', ''))));
    } finally {
      if (mounted) setState(() => sending = false);
    }
  }

  Future<void> messageMenu(Map<String, dynamic> message) async {
    final selected = await showModalBottomSheet<String>(context: context, builder: (context) => SafeArea(child: Padding(padding: const EdgeInsets.all(20), child: Column(mainAxisSize: MainAxisSize.min, children: [ListTile(leading: const Icon(Icons.reply), title: const Text('Reply'), onTap: () => Navigator.pop(context, 'reply')), Wrap(spacing: 8, children: ['👍', '❤️', '😂', '😮', '🙏', '🎉'].map((emoji) => IconButton(onPressed: () => Navigator.pop(context, emoji), icon: Text(emoji, style: const TextStyle(fontSize: 26)))).toList())]))));
    if (selected == 'reply' && mounted) setState(() => reply = message);
    else if (selected != null) await action({'action': 'add_reaction', 'message_id': message['id'], 'emoji': selected});
  }

  @override
  Widget build(BuildContext context) {
    if (room == null) return Column(children: [
      Padding(padding: const EdgeInsets.all(12), child: Row(children: [Expanded(child: TextField(decoration: const InputDecoration(hintText: 'Search channels', prefixIcon: Icon(Icons.search)), onChanged: (v) => setState(() => search = v.toLowerCase()))), IconButton(tooltip: 'Create group', onPressed: createRoom, icon: const Icon(Icons.group_add_outlined))])),
      if (loading) const LinearProgressIndicator(),
      if (error != null) ListTile(title: Text(error!), trailing: IconButton(onPressed: () => refresh(), icon: const Icon(Icons.refresh))),
      Expanded(child: RefreshIndicator(onRefresh: refresh, child: ListView(children: rooms.where((r) => _text(r['name']).toLowerCase().contains(search)).map((r) => ListTile(leading: const Icon(Icons.forum_outlined), title: Text(_text(r['name'])), subtitle: Text(_text(r['last_message']), maxLines: 2, overflow: TextOverflow.ellipsis), onTap: () { setState(() { room = r; messages = []; error = null; }); refresh(); })).toList()))),
    ]);
    final newest = messages.reversed.toList();
    return Column(children: [
      ListTile(leading: IconButton(tooltip: 'Back to channels', onPressed: () { setState(() { room = null; reply = null; error = null; }); refresh(); }, icon: const Icon(Icons.arrow_back)), title: Text(_text(room!['name'])), trailing: IconButton(tooltip: 'Refresh', onPressed: () => refresh(), icon: const Icon(Icons.refresh))),
      if (error != null) Padding(padding: const EdgeInsets.all(10), child: Text(error!)),
      Expanded(child: ListView.builder(reverse: true, padding: const EdgeInsets.symmetric(horizontal: 12), itemCount: newest.length, itemBuilder: (_, index) {
        final m = newest[index];
        return Align(alignment: m['is_mine'] == true ? Alignment.centerRight : Alignment.centerLeft, child: GestureDetector(onLongPress: () => messageMenu(m), child: Card(color: m['is_mine'] == true ? Theme.of(context).colorScheme.primaryContainer : null, child: Padding(padding: const EdgeInsets.all(12), child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
          Text(_text(m['sender_name']), style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 12)),
          if (m['reply_message'] != null) Container(padding: const EdgeInsets.all(8), margin: const EdgeInsets.symmetric(vertical: 6), decoration: BoxDecoration(border: Border(left: BorderSide(color: Theme.of(context).colorScheme.primary, width: 3))), child: Text('${m['reply_sender']}: ${m['reply_message']}', maxLines: 2, overflow: TextOverflow.ellipsis)),
          Text(_text(m['message'])),
          const SizedBox(height: 5), Text(_text(m['created_at']), style: Theme.of(context).textTheme.labelSmall),
          Wrap(spacing: 4, children: _items(m['reactions']).map((reaction) => ActionChip(label: Text('${reaction['emoji']} ${reaction['count']}'), onPressed: () => action({'action': 'add_reaction', 'message_id': m['id'], 'emoji': reaction['emoji']}))).toList()),
        ])))));
      })),
      if (reply != null) ListTile(dense: true, leading: const Icon(Icons.reply), title: Text('Reply to ${reply!['sender_name']}'), subtitle: Text(_text(reply!['message']), maxLines: 1, overflow: TextOverflow.ellipsis), trailing: IconButton(onPressed: () => setState(() => reply = null), icon: const Icon(Icons.close))),
      if (sending) const LinearProgressIndicator(),
      SafeArea(top: false, child: Padding(padding: const EdgeInsets.all(12), child: Row(children: [Expanded(child: TextField(controller: input, maxLines: null, decoration: const InputDecoration(hintText: 'Type a message…', border: OutlineInputBorder()), onSubmitted: (_) => send())), IconButton(tooltip: 'Send message', onPressed: sending ? null : send, icon: const Icon(Icons.send))]))),
    ]);
  }
}
