class Attendance {
  final String id;
  final String date;
  final DateTime? checkInDeviceTime;
  final DateTime? checkInServerTime;
  final DateTime? checkOutDeviceTime;
  final DateTime? checkOutServerTime;
  final int? workDurationMinutes;
  final String status;
  final String? wifiSsid;
  final String? wifiBssid;
  final String? dailyActivities;

  Attendance({
    required this.id,
    required this.date,
    required this.status,
    this.checkInDeviceTime,
    this.checkInServerTime,
    this.checkOutDeviceTime,
    this.checkOutServerTime,
    this.workDurationMinutes,
    this.wifiSsid,
    this.wifiBssid,
    this.dailyActivities,
  });

  factory Attendance.fromJson(Map<String, dynamic> json) {
    return Attendance(
      id: json['id'],
      date: json['date'],
      checkInDeviceTime: _parseDateTime(json['check_in_device_time']),
      checkInServerTime: _parseDateTime(json['check_in_server_time']),
      checkOutDeviceTime: _parseDateTime(json['check_out_device_time']),
      checkOutServerTime: _parseDateTime(json['check_out_server_time']),
      workDurationMinutes: json['work_duration_minutes'],
      status: json['status'],
      wifiSsid: json['wifi_ssid'],
      wifiBssid: json['wifi_bssid'],
      dailyActivities: json['daily_activities'],
    );
  }

  static DateTime? _parseDateTime(dynamic value) {
    if (value == null) {
      return null;
    }

    return DateTime.parse(value.toString()).toLocal();
  }
}

class AttendanceStateResponse {
  final Attendance? attendance;
  final bool canCheckIn;
  final bool canCheckOut;

  AttendanceStateResponse({
    required this.attendance,
    required this.canCheckIn,
    required this.canCheckOut,
  });

  factory AttendanceStateResponse.fromJson(Map<String, dynamic> json) {
    final attendanceJson = json['attendance'];

    return AttendanceStateResponse(
      attendance: attendanceJson == null
          ? null
          : Attendance.fromJson(attendanceJson as Map<String, dynamic>),
      canCheckIn: json['can_check_in'] == true,
      canCheckOut: json['can_check_out'] == true,
    );
  }
}

class AttendanceHistoryResponse {
  final List<Attendance> attendances;

  AttendanceHistoryResponse({required this.attendances});

  factory AttendanceHistoryResponse.fromJson(Map<String, dynamic> json) {
    final attendanceJson = json['attendances'];

    return AttendanceHistoryResponse(
      attendances: attendanceJson is List
          ? attendanceJson
                .whereType<Map<String, dynamic>>()
                .map(Attendance.fromJson)
                .toList()
          : [],
    );
  }
}
