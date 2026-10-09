int mobileId(Object? value) => value is num ? value.toInt() : int.parse(value.toString());

class MobileUser {
  final int id;
  final String name;
  final String role;
  final String church;

  MobileUser({required this.id, required this.name, required this.role, required this.church});

  factory MobileUser.fromJson(Map<String, dynamic> json) => MobileUser(
        id: mobileId(json['id']),
        name: json['fullname'] as String,
        role: json['role'] as String,
        church: json['church'] as String,
      );

  Map<String, dynamic> toJson() => {'id': id, 'fullname': name, 'role': role, 'church': church};
}

class MobileEvent {
  final int id;
  final String name;
  final String startDate;
  final String? endDate;
  final String? time;
  final String? location;
  final String status;

  MobileEvent({required this.id, required this.name, required this.startDate, this.endDate, this.time, this.location, required this.status});

  factory MobileEvent.fromJson(Map<String, dynamic> json) => MobileEvent(
        id: mobileId(json['id']),
        name: json['event_name'] as String,
        startDate: json['start_date'] as String,
        endDate: json['end_date'] as String?,
        time: json['event_time'] as String?,
        location: json['location'] as String?,
        status: json['status'] as String,
      );

  Map<String, dynamic> toJson() => {'id': id, 'event_name': name, 'start_date': startDate, 'end_date': endDate, 'event_time': time, 'location': location, 'status': status};
}

class MobileMember {
  final int id;
  final String name;
  final String? category;
  final String? qrToken;

  MobileMember({required this.id, required this.name, this.category, this.qrToken});

  factory MobileMember.fromJson(Map<String, dynamic> json) => MobileMember(
        id: mobileId(json['id']),
        name: json['fullname'] as String,
        category: json['category'] as String?,
        qrToken: json['qr_token'] as String?,
      );

  Map<String, dynamic> toJson() => {'id': id, 'fullname': name, 'category': category, 'qr_token': qrToken};
}

class AttendanceState {
  final int eventId;
  final int memberId;
  final String status;
  final String? logTimeUtc;

  AttendanceState({required this.eventId, required this.memberId, required this.status, this.logTimeUtc});

  factory AttendanceState.fromJson(Map<String, dynamic> json) => AttendanceState(
        eventId: mobileId(json['event_id']),
        memberId: mobileId(json['attendee_id']),
        status: json['status'] as String,
        logTimeUtc: json['log_time_utc'] as String?,
      );

  Map<String, dynamic> toJson() => {'event_id': eventId, 'attendee_id': memberId, 'status': status, 'log_time_utc': logTimeUtc};
}

class PendingAction {
  final String clientId;
  final int eventId;
  final int memberId;
  final String status;
  final String method;
  final String occurredAtUtc;
  final String? baseStatus;
  final String? baseLogTimeUtc;
  final String syncState;
  final String? reason;

  PendingAction({required this.clientId, required this.eventId, required this.memberId, required this.status, required this.method, required this.occurredAtUtc, this.baseStatus, this.baseLogTimeUtc, this.syncState = 'pending', this.reason});

  factory PendingAction.fromDb(Map<String, Object?> row) => PendingAction(
        clientId: row['client_id'] as String,
        eventId: row['event_id'] as int,
        memberId: row['member_id'] as int,
        status: row['status'] as String,
        method: row['method'] as String,
        occurredAtUtc: row['occurred_at_utc'] as String,
        baseStatus: row['base_status'] as String?,
        baseLogTimeUtc: row['base_log_time_utc'] as String?,
        syncState: row['sync_state'] as String,
        reason: row['reason'] as String?,
      );

  Map<String, Object?> toDb() => {
        'client_id': clientId, 'event_id': eventId, 'member_id': memberId,
        'status': status, 'method': method, 'occurred_at_utc': occurredAtUtc,
        'base_status': baseStatus, 'base_log_time_utc': baseLogTimeUtc,
        'sync_state': syncState, 'reason': reason,
      };

  Map<String, dynamic> toApi() => {
        'client_id': clientId, 'event_id': eventId, 'attendee_id': memberId,
        'status': status, 'method': method, 'occurred_at_utc': occurredAtUtc,
        'base_status': baseStatus, 'base_log_time_utc': baseLogTimeUtc,
      };
}
