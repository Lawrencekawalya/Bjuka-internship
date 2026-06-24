import 'package:dio/dio.dart';
import '../models/attendance_model.dart';

class AttendanceRepository {
  final Dio _dio;

  AttendanceRepository(this._dio);

  Future<AttendanceStateResponse> getToday() async {
    final response = await _dio.get('/attendance/today');
    return AttendanceStateResponse.fromJson(response.data);
  }

  Future<Attendance> checkIn({
    required String wifiSsid,
    required String wifiBssid,
  }) async {
    final response = await _dio.post(
      '/attendance/check-in',
      data: {
        'device_time': DateTime.now().toUtc().toIso8601String(),
        'wifi_ssid': wifiSsid,
        'wifi_bssid': wifiBssid,
      },
    );

    return Attendance.fromJson(response.data['attendance']);
  }

  Future<Attendance> checkOut({
    required String wifiSsid,
    required String wifiBssid,
  }) async {
    final response = await _dio.post(
      '/attendance/check-out',
      data: {
        'device_time': DateTime.now().toUtc().toIso8601String(),
        'wifi_ssid': wifiSsid,
        'wifi_bssid': wifiBssid,
      },
    );

    return Attendance.fromJson(response.data['attendance']);
  }
}
