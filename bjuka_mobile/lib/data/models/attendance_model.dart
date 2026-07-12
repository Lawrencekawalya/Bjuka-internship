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
  final String? batchStatus;
  final String? attendanceUnavailableMessage;
  final int batchProgressPercentage;
  final AttendanceSummary attendanceSummary;
  final String? certificateDownloadUrl;

  AttendanceStateResponse({
    required this.attendance,
    required this.canCheckIn,
    required this.canCheckOut,
    required this.batchProgressPercentage,
    required this.attendanceSummary,
    this.batchStatus,
    this.attendanceUnavailableMessage,
    this.certificateDownloadUrl,
  });

  factory AttendanceStateResponse.fromJson(Map<String, dynamic> json) {
    final attendanceJson = json['attendance'];

    return AttendanceStateResponse(
      attendance: attendanceJson == null
          ? null
          : Attendance.fromJson(attendanceJson as Map<String, dynamic>),
      canCheckIn: json['can_check_in'] == true,
      canCheckOut: json['can_check_out'] == true,
      batchStatus: json['batch_status'] as String?,
      attendanceUnavailableMessage:
          json['attendance_unavailable_message'] as String?,
      batchProgressPercentage: _parseInt(json['batch_progress_percentage']),
      attendanceSummary: AttendanceSummary.fromJson(
        json['attendance_summary'] is Map<String, dynamic>
            ? json['attendance_summary'] as Map<String, dynamic>
            : const <String, dynamic>{},
      ),
      certificateDownloadUrl: json['certificate_download_url'] as String?,
    );
  }

  static int _parseInt(dynamic value) {
    if (value is int) {
      return value;
    }

    if (value is num) {
      return value.round();
    }

    return int.tryParse(value?.toString() ?? '') ?? 0;
  }
}

class AttendanceSummary {
  final int daysAttended;
  final int expectedDays;
  final int attendanceRate;
  final int remainingDays;

  const AttendanceSummary({
    required this.daysAttended,
    required this.expectedDays,
    required this.attendanceRate,
    required this.remainingDays,
  });

  const AttendanceSummary.empty()
    : daysAttended = 0,
      expectedDays = 0,
      attendanceRate = 0,
      remainingDays = 0;

  factory AttendanceSummary.fromJson(Map<String, dynamic> json) {
    return AttendanceSummary(
      daysAttended: _parseInt(json['days_attended']),
      expectedDays: _parseInt(json['expected_days']),
      attendanceRate: _parseInt(json['attendance_rate']).clamp(0, 100).toInt(),
      remainingDays: _parseInt(json['remaining_days']),
    );
  }

  AttendanceSummary recordAttendance() {
    final nextDaysAttended = daysAttended + 1;
    final nextRate = expectedDays > 0
        ? ((nextDaysAttended / expectedDays) * 100)
              .round()
              .clamp(0, 100)
              .toInt()
        : 0;

    return AttendanceSummary(
      daysAttended: nextDaysAttended,
      expectedDays: expectedDays,
      attendanceRate: nextRate,
      remainingDays: (expectedDays - nextDaysAttended)
          .clamp(0, expectedDays)
          .toInt(),
    );
  }

  static int _parseInt(dynamic value) {
    if (value is int) {
      return value;
    }

    if (value is num) {
      return value.round();
    }

    return int.tryParse(value?.toString() ?? '') ?? 0;
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
