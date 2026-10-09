import 'package:afb_mangaan_mobile/app_controller.dart';
import 'package:afb_mangaan_mobile/attendance_events_page.dart';
import 'package:afb_mangaan_mobile/models.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';

class FakeController extends AppController {
  int? downloadedId;
  @override
  Future<void> sync({bool silent = false}) async {}
  @override
  Future<void> downloadEvent(int eventId) async {
    downloadedId = eventId;
    detailDownloads[eventId] = '2026-10-09T08:00:00Z';
    members = [MobileMember(id: 1, name: 'Historical Member')];
    attendance = [AttendanceState(eventId: eventId, memberId: 1, status: 'Present')];
    notifyListeners();
  }
}

void main() {
  testWidgets('successful zero-event sync does not ask the user to connect again', (tester) async {
    final controller = FakeController()..lastRefresh = '2026-10-09T08:00:00Z';
    await tester.pumpWidget(MaterialApp(home: Scaffold(body: AttendanceEventsPage(controller: controller, onNavigate: (_) {}))));
    await tester.pumpAndSettle();
    expect(find.text('No events exist for this church in the last successful download.'), findsOneWidget);
    expect(find.textContaining('Connect and sync before going offline'), findsNothing);
    await tester.pumpWidget(const SizedBox());
    controller.dispose();
  });
  testWidgets('an event link downloads its own historical details and opens that event', (tester) async {
    final controller = FakeController()..lastRefresh = '2026-10-09T08:00:00Z';
    controller.events = [MobileEvent(id: 61, name: 'February Event', startDate: '2026-02-12', status: 'Archived')];
    await tester.pumpWidget(MaterialApp(home: Scaffold(body: AttendanceEventsPage(controller: controller, initialEventId: 61, onNavigate: (_) {}))));
    await tester.pumpAndSettle();
    expect(controller.downloadedId, 61);
    expect(find.text('February Event'), findsWidgets);
    expect(find.text('Historical Member'), findsOneWidget);
    expect(find.text('Present'), findsWidgets);
    expect(find.text('Not recorded'), findsNothing);
    final presentButton = tester.widget<FilledButton>(find.widgetWithText(FilledButton, 'Present'));
    expect(presentButton.onPressed, isNull);
    await tester.pumpWidget(const SizedBox());
    controller.dispose();
  });
}
