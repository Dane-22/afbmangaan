import 'package:flutter/material.dart';

const afbGold = Color(0xFFE8D068);
const afbDark = Color(0xFF0D0D0D);

class LandingPage extends StatefulWidget {
  final VoidCallback onEnter;
  final bool signedIn;
  final VoidCallback onTheme;
  final VoidCallback? onAttendance;
  const LandingPage({super.key, required this.onEnter, required this.signedIn, required this.onTheme, this.onAttendance});

  @override
  State<LandingPage> createState() => _LandingPageState();
}

class _LandingPageState extends State<LandingPage> {
  final scroll = ScrollController();
  final mission = GlobalKey();
  bool moreMinistries = false;

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
          title: Text('AFB SANTOL', style: TextStyle(fontFamily: 'Cinzel', letterSpacing: 2, color: theme.colorScheme.primary)),
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
              const Text('Where Faith\nMeets Purpose', style: TextStyle(color: Colors.white, fontFamily: 'Cinzel', fontSize: 43, height: 1.18)),
              const SizedBox(height: 22),
              const Text('A sacred community walking in divine light, serving two congregations with unwavering devotion.', style: TextStyle(color: Color(0xFFD1CCC0), fontSize: 17, height: 1.6)),
              const SizedBox(height: 30),
              FilledButton.icon(onPressed: explore, icon: const Icon(Icons.arrow_downward), label: const Text('Begin Your Journey')),
              const SizedBox(height: 70),
              const Text('SCROLL TO EXPLORE', style: TextStyle(color: Color(0xFFADA48F), fontSize: 11, letterSpacing: 3)),
            ])),
          ]),
        )),
        SliverToBoxAdapter(child: _quote('“For where two or three gather in my name, there am I with them.”', 'Matthew 18:20', key: mission)),
        SliverToBoxAdapter(child: _section(context, eyebrow: 'OUR CONGREGATIONS', title: 'Two Hearts, One Spirit', child: Column(children: [
          _church(context, 'Main Sanctuary', 'AFB Santol', 'Our founding congregation, where generations have gathered to worship, learn, and serve. A beacon of faith in the community for nearly five decades.', 'Mangaan, Barangay Proper', '200+ Active Members'),
          _church(context, 'Branch Fellowship', 'AFB Lettac Sur', 'A growing fellowship extending our mission of love and service. Where new connections blossom and faith communities flourish.', 'Lettac Sur, Barangay Extension', '100+ Active Members'),
        ]))),
        SliverToBoxAdapter(child: _quote('“Let your light shine before others, that they may see your good deeds and glorify your Father in heaven.”', 'Matthew 5:16')),
        SliverToBoxAdapter(child: _section(context, eyebrow: 'OUR JOURNEY', title: 'A Legacy of Faith', child: SizedBox(height: 285, child: ListView(scrollDirection: Axis.horizontal, children: const [
          _History('1975', 'Humble Beginnings', 'A small group of faithful believers gathered under a humble roof, planting the seed of what would become a thriving spiritual community.'),
          _History('1985', 'Sanctuary Built', 'Through collective sacrifice and divine providence, the first permanent sanctuary was erected, establishing a home for worship.'),
          _History('2005', 'Community Expansion', 'Outreach programs flourished as the church extended its mission beyond walls, serving the needy and welcoming seekers.'),
          _History('2018', 'Lettac Sur Branch', 'Responding to growing needs, AFB Lettac Sur was established, bringing the message of hope to a new community.'),
          _History('2024', 'Digital Transformation', 'Embracing modern stewardship with an advanced attendance and analytics system, honoring tradition while innovating for tomorrow.'),
        ])))),
        SliverToBoxAdapter(child: _quote('“Now you are the body of Christ, and each one of you is a part of it.”', '1 Corinthians 12:27')),
        SliverToBoxAdapter(child: _section(context, eyebrow: 'SERVE WITH US', title: 'Our Ministries', child: Column(children: [
          const _Ministry('music-ministry', 'Music Ministry', 'Leading worship through sacred music and songs that uplift the spirit and glorify God.'),
          const _Ministry('pastoral-care', 'Pastoral Care', 'Providing spiritual guidance, counseling, and support to our church family in times of need.'),
          const _Ministry('youth-children', "Youth and Children’s Ministry", 'Nurturing young hearts and minds in faith, creating a foundation for lifelong spiritual growth.'),
          const _Ministry('usher-ministry', 'Usher Ministry', 'Welcoming and assisting congregants with warmth and hospitality, ensuring order during services.'),
          const _Ministry('deacon-ministry', 'Deacon Ministry', 'Serving the church community through practical support, compassion, and dedicated service.'),
          const _Ministry('dance-ministry', 'Dance Ministry', 'Expressing worship through movement and dance, bringing joy and creative praise to our services.'),
          if (moreMinistries) const _Ministry('multimedia-ministry', 'Multimedia Ministry', 'Enhancing worship experiences through technology, visual media, and sound engineering.'),
          OutlinedButton.icon(onPressed: () => setState(() => moreMinistries = !moreMinistries), icon: Icon(moreMinistries ? Icons.expand_less : Icons.expand_more), label: Text(moreMinistries ? 'See Less Ministries' : 'See More Ministries')),
        ]))),
        SliverToBoxAdapter(child: _section(context, eyebrow: 'CONNECT & GROW', title: 'Our Activities', child: SizedBox(height: 455, child: ListView(scrollDirection: Axis.horizontal, children: const [
          _Activity('youth-collide', 'Youth Collide', 'Monthly Gathering · 2026', 'A monthly gathering for youth and young adults (ages 12–32) to build friendships, grow in faith, and be encouraged.'),
          _Activity('sunday-service', 'Weekly Sunday Service', 'Weekly · Every Sunday', 'A weekly time of worship, the Word, and fellowship as one church family.'),
          _Activity('prayer-meeting', 'Prayer Meeting Every Friday', 'Prayer · Every Friday', 'A rotating prayer gathering per sitio, coming together to pray and intercede for families, the community, and the church.'),
          _Activity('senior-crew', 'Senior Crew Meeting', 'Professionals · Community', 'A gathering for working professionals and senior young people to connect, learn, and strengthen one another in faith and purpose.'),
          _Activity('life-group', 'Life Group', 'Small Groups · Ongoing', 'Small-group gatherings that create a safe space to talk about life, share testimonies, and grow through prayer and Scripture.'),
        ])))),
        SliverToBoxAdapter(child: _quote('“The light shines in the darkness, and the darkness has not overcome it.”', 'John 1:5')),
        SliverToBoxAdapter(child: Padding(padding: const EdgeInsets.fromLTRB(24, 24, 24, 48), child: Column(children: [
          const Text('YOUR JOURNEY BEGINS HERE', style: TextStyle(color: afbGold, letterSpacing: 2, fontSize: 11)),
          const SizedBox(height: 10),
          const Text('Walk With Us', style: TextStyle(fontFamily: 'Cinzel', fontSize: 28)),
          const SizedBox(height: 10),
          const Text("Whether you're seeking community, searching for meaning, or looking to serve, there's a place for you in our family.", textAlign: TextAlign.center),
          const SizedBox(height: 18),
          Wrap(spacing: 8, runSpacing: 8, alignment: WrapAlignment.center, children: [
            if (!widget.signedIn || widget.onAttendance != null) FilledButton.icon(onPressed: widget.onAttendance ?? widget.onEnter, icon: const Icon(Icons.event_available), label: const Text('Take Attendance')),
            OutlinedButton.icon(onPressed: widget.onEnter, icon: const Icon(Icons.dashboard_outlined), label: const Text('Dashboard')),
          ]),
          const SizedBox(height: 20),
          const Text('Mangaan & Lettac Sur · Sunday Service', textAlign: TextAlign.center),
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
      Text(title, textAlign: TextAlign.center, style: const TextStyle(fontFamily: 'Cinzel', fontSize: 29)),
      const SizedBox(height: 28),
      child,
    ]),
  );

  Widget _church(BuildContext context, String label, String name, String description, String location, String members) => Card(
    margin: const EdgeInsets.only(bottom: 16),
    child: Padding(padding: const EdgeInsets.all(24), child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
      Icon(Icons.church, size: 40, color: Theme.of(context).colorScheme.primary),
      const SizedBox(height: 16),
      Text(label.toUpperCase(), style: const TextStyle(letterSpacing: 2, fontSize: 11)),
      const SizedBox(height: 8),
      Text(name, style: const TextStyle(fontFamily: 'Cinzel', fontSize: 25)),
      const SizedBox(height: 10),
      Text(description),
      const SizedBox(height: 16),
      Text('$location  •  Sundays at 9:00 AM'),
      const SizedBox(height: 6),
      Text(members),
    ])),
  );

  Widget _quote(String text, String source, {Key? key}) => Container(
    key: key,
    width: double.infinity,
    padding: const EdgeInsets.symmetric(horizontal: 30, vertical: 75),
    color: afbDark,
    child: Column(children: [Text(text, textAlign: TextAlign.center, style: const TextStyle(color: Colors.white, fontFamily: 'Cinzel', fontSize: 23, height: 1.5)), const SizedBox(height: 22), Text(source, style: const TextStyle(color: afbGold, letterSpacing: 2))]),
  );
}

class _History extends StatelessWidget {
  final String year, title, description;
  const _History(this.year, this.title, this.description);
  @override
  Widget build(BuildContext context) => SizedBox(width: 270, child: Card(child: Padding(padding: const EdgeInsets.all(20), child: SingleChildScrollView(child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [Text(year, style: TextStyle(color: Theme.of(context).colorScheme.primary, fontSize: 28)), const SizedBox(height: 10), Text(title, style: const TextStyle(fontFamily: 'Cinzel', fontSize: 20)), const SizedBox(height: 12), Text(description)])))));
}

class _Ministry extends StatelessWidget {
  final String seed, name, description;
  const _Ministry(this.seed, this.name, this.description);
  @override
  Widget build(BuildContext context) => Card(margin: const EdgeInsets.only(bottom: 18), clipBehavior: Clip.antiAlias, child: Column(crossAxisAlignment: CrossAxisAlignment.stretch, children: [
    _LandingImage(seed: seed, width: 300, height: 200, displayHeight: 170),
    Padding(padding: const EdgeInsets.all(20), child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [Text(name, style: const TextStyle(fontFamily: 'Cinzel', fontSize: 21)), const SizedBox(height: 10), Text(description)])),
  ]));
}

class _Activity extends StatelessWidget {
  final String seed, name, tag, description;
  const _Activity(this.seed, this.name, this.tag, this.description);
  @override
  Widget build(BuildContext context) => SizedBox(width: 285, child: Card(clipBehavior: Clip.antiAlias, child: Column(crossAxisAlignment: CrossAxisAlignment.stretch, children: [
    _LandingImage(seed: seed, width: 900, height: 600, displayHeight: 165),
    Expanded(child: SingleChildScrollView(padding: const EdgeInsets.all(20), child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [Text(tag, style: TextStyle(color: Theme.of(context).colorScheme.primary, fontSize: 11)), const SizedBox(height: 10), Text(name, style: const TextStyle(fontFamily: 'Cinzel', fontSize: 22)), const SizedBox(height: 12), Text(description)]))),
  ])));
}

class _LandingImage extends StatelessWidget {
  final String seed;
  final int width, height;
  final double displayHeight;
  const _LandingImage({required this.seed, required this.width, required this.height, required this.displayHeight});
  @override
  Widget build(BuildContext context) => Image.network('https://picsum.photos/seed/$seed/$width/$height.jpg', height: displayHeight, fit: BoxFit.cover, errorBuilder: (_, error, stack) => Container(height: displayHeight, color: Theme.of(context).colorScheme.surfaceContainerHighest, child: const Icon(Icons.church, size: 48)), loadingBuilder: (context, child, progress) => progress == null ? child : Container(height: displayHeight, color: Theme.of(context).colorScheme.surfaceContainerHighest, child: const Center(child: Icon(Icons.photo_outlined))));
}
