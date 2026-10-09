import 'package:flutter/material.dart';

const afbGold = Color(0xFFE8D068);
const afbDark = Color(0xFF0D0D0D);

class LandingPage extends StatefulWidget {
  final VoidCallback onEnter;
  final bool signedIn;
  final VoidCallback onTheme;
  const LandingPage({super.key, required this.onEnter, required this.signedIn, required this.onTheme});

  @override
  State<LandingPage> createState() => _LandingPageState();
}

class _LandingPageState extends State<LandingPage> {
  final scroll = ScrollController();
  final mission = GlobalKey();

  @override
  void dispose() {
    scroll.dispose();
    super.dispose();
  }

  void explore() {
    final target = mission.currentContext;
    if (target != null) Scrollable.ensureVisible(target, duration: const Duration(milliseconds: 550), curve: Curves.easeInOut);
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    return Scaffold(
      body: SafeArea(child: CustomScrollView(controller: scroll, slivers: [
        SliverAppBar(
          pinned: true,
          backgroundColor: theme.colorScheme.surface,
          title: Text('AFB SANTOL', style: TextStyle(fontFamily: 'serif', letterSpacing: 2, color: theme.colorScheme.primary)),
          actions: [
            IconButton(tooltip: 'Toggle theme', onPressed: widget.onTheme, icon: const Icon(Icons.brightness_6_outlined)),
            TextButton(onPressed: widget.onEnter, child: Text(widget.signedIn ? 'Dashboard' : 'Sign In')),
            const SizedBox(width: 8),
          ],
        ),
        SliverToBoxAdapter(child: Container(
          constraints: const BoxConstraints(minHeight: 620),
          decoration: const BoxDecoration(gradient: LinearGradient(begin: Alignment.topLeft, end: Alignment.bottomRight, colors: [Color(0xFF1B1811), afbDark, Color(0xFF302817)])),
          child: Stack(children: [
            Positioned(top: 85, right: -95, child: Container(width: 285, height: 285, decoration: BoxDecoration(shape: BoxShape.circle, border: Border.all(color: afbGold.withValues(alpha: 0.18), width: 32)))),
            Padding(padding: const EdgeInsets.symmetric(horizontal: 28, vertical: 70), child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
              const Text('EST. 1975', style: TextStyle(color: afbGold, letterSpacing: 4, fontWeight: FontWeight.w600)),
              const SizedBox(height: 24),
              const Text('Where Faith\nMeets Purpose', style: TextStyle(color: Colors.white, fontFamily: 'serif', fontSize: 47, height: 1.18)),
              const SizedBox(height: 22),
              const Text('A sacred community walking in divine light, serving two congregations with unwavering devotion.', style: TextStyle(color: Color(0xFFD1CCC0), fontSize: 17, height: 1.6)),
              const SizedBox(height: 30),
              FilledButton.icon(onPressed: explore, icon: const Icon(Icons.arrow_downward), label: const Text('Begin Your Journey')),
              const SizedBox(height: 70),
              const Text('SCROLL TO EXPLORE', style: TextStyle(color: Color(0xFFADA48F), fontSize: 11, letterSpacing: 3)),
            ])),
          ]),
        )),
        SliverToBoxAdapter(child: _section(context, key: mission, eyebrow: 'OUR MISSION', title: 'Gathered in His Name', child: const Text('“For where two or three gather in my name, there am I with them.”\n\nMatthew 18:20', textAlign: TextAlign.center, style: TextStyle(fontFamily: 'serif', fontSize: 20, height: 1.6)))),
        SliverToBoxAdapter(child: _section(context, eyebrow: 'OUR CONGREGATIONS', title: 'Two Hearts, One Spirit', child: Column(children: [
          _church(context, 'Main Sanctuary', 'AFB Santol', 'Our founding congregation, where generations have gathered to worship, learn, and serve.', 'Mangaan, Barangay Proper'),
          _church(context, 'Branch Fellowship', 'AFB Lettac Sur', 'A growing fellowship extending our mission of love and service.', 'Lettac Sur, Barangay Extension'),
        ]))),
        SliverToBoxAdapter(child: _quote('“Let your light shine before others, that they may see your good deeds and glorify your Father in heaven.”', 'Matthew 5:16')),
        SliverToBoxAdapter(child: _section(context, eyebrow: 'OUR JOURNEY', title: 'A Legacy of Faith', child: Column(children: const [
          _History('1975', 'Humble Beginnings', 'A small group of faithful believers gathered under a humble roof.'),
          _History('1985', 'Sanctuary Built', 'The first permanent sanctuary became a home for worship.'),
          _History('2005', 'Community Expansion', 'Outreach programs extended the mission beyond the church walls.'),
          _History('2018', 'Lettac Sur Branch', 'A new fellowship brought the message of hope to another community.'),
          _History('2024', 'Digital Transformation', 'Modern stewardship joined a longstanding tradition of service.'),
        ]))),
        SliverToBoxAdapter(child: _quote('“Now you are the body of Christ, and each one of you is a part of it.”', '1 Corinthians 12:27')),
        SliverToBoxAdapter(child: _section(context, eyebrow: 'SERVE WITH US', title: 'Our Ministries', child: Wrap(spacing: 10, runSpacing: 10, children: const [
          _Ministry(Icons.music_note, 'Music Ministry'), _Ministry(Icons.favorite, 'Pastoral Care'),
          _Ministry(Icons.child_care, "Youth and Children’s Ministry"), _Ministry(Icons.door_front_door_outlined, 'Usher Ministry'),
          _Ministry(Icons.volunteer_activism, 'Deacon Ministry'), _Ministry(Icons.music_video, 'Dance Ministry'),
          _Ministry(Icons.videocam_outlined, 'Multimedia Ministry'),
        ]))),
        SliverToBoxAdapter(child: _section(context, eyebrow: 'CONNECT & GROW', title: 'Our Activities', child: const Column(children: [
          ListTile(leading: Icon(Icons.people), title: Text('Youth Collide'), subtitle: Text('Monthly gathering for youth and young adults')),
          ListTile(leading: Icon(Icons.church), title: Text('Weekly Sunday Service'), subtitle: Text('Worship, the Word, and fellowship every Sunday')),
          ListTile(leading: Icon(Icons.volunteer_activism), title: Text('Prayer Meeting Every Friday'), subtitle: Text('Rotating prayer gathering per sitio')),
          ListTile(leading: Icon(Icons.groups), title: Text('Senior Crew Meeting'), subtitle: Text('Connection and faith for working professionals')),
          ListTile(leading: Icon(Icons.diversity_3), title: Text('Life Group'), subtitle: Text('Small-group prayer, Scripture, and sharing')),
        ]))),
        SliverToBoxAdapter(child: _quote('“The light shines in the darkness, and the darkness has not overcome it.”', 'John 1:5')),
        SliverToBoxAdapter(child: Padding(padding: const EdgeInsets.fromLTRB(24, 24, 24, 48), child: Column(children: [
          const Text('YOUR JOURNEY BEGINS HERE', style: TextStyle(color: afbGold, letterSpacing: 2, fontSize: 11)),
          const SizedBox(height: 10),
          const Text('Walk With Us', style: TextStyle(fontFamily: 'serif', fontSize: 28)),
          const SizedBox(height: 10),
          const Text("Whether you're seeking community, searching for meaning, or looking to serve, there's a place for you in our family.", textAlign: TextAlign.center),
          const SizedBox(height: 18),
          FilledButton(onPressed: widget.onEnter, child: Text(widget.signedIn ? 'Open Dashboard' : 'Sign In')),
          const SizedBox(height: 32),
          const Text('AFB Santol · A Divine Light in Our Community Since 1975', textAlign: TextAlign.center),
        ]))),
      ])),
    );
  }

  Widget _section(BuildContext context, {Key? key, required String eyebrow, required String title, required Widget child}) => Container(
    key: key,
    padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 58),
    width: double.infinity,
    child: Column(children: [
      Text(eyebrow, style: TextStyle(color: Theme.of(context).colorScheme.primary, letterSpacing: 3, fontWeight: FontWeight.bold, fontSize: 11)),
      const SizedBox(height: 12),
      Text(title, textAlign: TextAlign.center, style: const TextStyle(fontFamily: 'serif', fontSize: 29)),
      const SizedBox(height: 28),
      child,
    ]),
  );

  Widget _church(BuildContext context, String label, String name, String description, String location) => Card(
    margin: const EdgeInsets.only(bottom: 16),
    child: Padding(padding: const EdgeInsets.all(24), child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
      Icon(Icons.church, size: 40, color: Theme.of(context).colorScheme.primary),
      const SizedBox(height: 16),
      Text(label.toUpperCase(), style: const TextStyle(letterSpacing: 2, fontSize: 11)),
      const SizedBox(height: 8),
      Text(name, style: const TextStyle(fontFamily: 'serif', fontSize: 25)),
      const SizedBox(height: 10),
      Text(description),
      const SizedBox(height: 16),
      Text('$location  •  Sundays at 9:00 AM'),
    ])),
  );

  Widget _quote(String text, String source) => Container(
    width: double.infinity,
    padding: const EdgeInsets.symmetric(horizontal: 30, vertical: 75),
    color: afbDark,
    child: Column(children: [Text(text, textAlign: TextAlign.center, style: const TextStyle(color: Colors.white, fontFamily: 'serif', fontSize: 23, height: 1.5)), const SizedBox(height: 22), Text(source, style: const TextStyle(color: afbGold, letterSpacing: 2))]),
  );
}

class _History extends StatelessWidget {
  final String year, title, description;
  const _History(this.year, this.title, this.description);
  @override
  Widget build(BuildContext context) => Card(child: ListTile(leading: CircleAvatar(child: Text(year.substring(2))), title: Text('$year • $title'), subtitle: Text(description)));
}

class _Ministry extends StatelessWidget {
  final IconData icon;
  final String name;
  const _Ministry(this.icon, this.name);
  @override
  Widget build(BuildContext context) => Chip(avatar: Icon(icon, size: 19), label: Text(name), padding: const EdgeInsets.all(8));
}
