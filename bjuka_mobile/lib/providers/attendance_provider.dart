import 'package:dio/dio.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../core/network/wifi_info_service.dart';
import '../data/models/attendance_model.dart';
import '../data/repositories/attendance_repository.dart';
import 'providers.dart';

class AttendanceState {
  final bool isLoading;
  final bool isSubmitting;
  final Attendance? attendance;
  final bool canCheckIn;
  final bool canCheckOut;
  final int batchProgressPercentage;
  final AttendanceSummary attendanceSummary;
  final String? certificateDownloadUrl;
  final String? errorMessage;
  final String? successMessage;

  AttendanceState({
    required this.isLoading,
    required this.isSubmitting,
    required this.canCheckIn,
    required this.canCheckOut,
    required this.batchProgressPercentage,
    required this.attendanceSummary,
    this.certificateDownloadUrl,
    this.attendance,
    this.errorMessage,
    this.successMessage,
  });

  factory AttendanceState.initial() {
    return AttendanceState(
      isLoading: true,
      isSubmitting: false,
      canCheckIn: false,
      canCheckOut: false,
      batchProgressPercentage: 0,
      attendanceSummary: const AttendanceSummary.empty(),
    );
  }

  AttendanceState copyWith({
    bool? isLoading,
    bool? isSubmitting,
    Attendance? attendance,
    bool? canCheckIn,
    bool? canCheckOut,
    int? batchProgressPercentage,
    AttendanceSummary? attendanceSummary,
    String? certificateDownloadUrl,
    String? errorMessage,
    String? successMessage,
    bool clearAttendance = false,
    bool clearMessages = false,
  }) {
    return AttendanceState(
      isLoading: isLoading ?? this.isLoading,
      isSubmitting: isSubmitting ?? this.isSubmitting,
      attendance: clearAttendance ? null : attendance ?? this.attendance,
      canCheckIn: canCheckIn ?? this.canCheckIn,
      canCheckOut: canCheckOut ?? this.canCheckOut,
      batchProgressPercentage:
          batchProgressPercentage ?? this.batchProgressPercentage,
      attendanceSummary: attendanceSummary ?? this.attendanceSummary,
      certificateDownloadUrl:
          certificateDownloadUrl ?? this.certificateDownloadUrl,
      errorMessage: clearMessages ? null : errorMessage,
      successMessage: clearMessages ? null : successMessage,
    );
  }
}

class AttendanceNotifier extends Notifier<AttendanceState> {
  @override
  AttendanceState build() {
    return AttendanceState.initial();
  }

  AttendanceRepository get _repository =>
      ref.read(attendanceRepositoryProvider);
  WifiInfoService get _wifiInfo => ref.read(wifiInfoProvider);

  Future<void> loadToday() async {
    state = state.copyWith(isLoading: true, clearMessages: true);

    try {
      final response = await _repository.getToday();
      state = state.copyWith(
        isLoading: false,
        attendance: response.attendance,
        clearAttendance: response.attendance == null,
        canCheckIn: response.canCheckIn,
        canCheckOut: response.canCheckOut,
        batchProgressPercentage: response.batchProgressPercentage,
        attendanceSummary: response.attendanceSummary,
        certificateDownloadUrl: response.certificateDownloadUrl,
      );
    } catch (e) {
      state = state.copyWith(
        isLoading: false,
        errorMessage: _messageForError(e),
      );
    }
  }

  Future<void> checkIn() async {
    state = state.copyWith(isSubmitting: true, clearMessages: true);

    try {
      final wifi = await _wifiInfo.currentWifi(
        action: AttendanceAction.checkIn,
      );
      final attendance = await _repository.checkIn(
        wifiSsid: wifi.ssid,
        wifiBssid: wifi.bssid,
      );
      state = state.copyWith(
        isSubmitting: false,
        attendance: attendance,
        canCheckIn: false,
        canCheckOut: attendance.checkOutServerTime == null,
        attendanceSummary: state.attendanceSummary.recordAttendance(),
        successMessage: 'Checked in successfully.',
      );
    } catch (e) {
      state = state.copyWith(
        isSubmitting: false,
        errorMessage: _messageForError(e),
      );
    }
  }

  Future<void> checkOut({required String activities}) async {
    state = state.copyWith(isSubmitting: true, clearMessages: true);

    try {
      final wifi = await _wifiInfo.currentWifi(
        action: AttendanceAction.checkOut,
      );
      final attendance = await _repository.checkOut(
        wifiSsid: wifi.ssid,
        wifiBssid: wifi.bssid,
        activities: activities,
      );
      state = state.copyWith(
        isSubmitting: false,
        attendance: attendance,
        canCheckIn: false,
        canCheckOut: false,
        successMessage: 'Checked out successfully.',
      );
    } catch (e) {
      state = state.copyWith(
        isSubmitting: false,
        errorMessage: _messageForError(e),
      );
    }
  }

  String _messageForError(Object error) {
    if (error is WifiInfoException) {
      return error.message;
    }

    if (error is DioException) {
      final data = error.response?.data;

      if (data is Map<String, dynamic> && data['message'] is String) {
        return data['message'];
      }

      if (error.type == DioExceptionType.connectionTimeout ||
          error.type == DioExceptionType.receiveTimeout) {
        return 'Connection timed out. Please try again.';
      }
    }

    return 'Something went wrong. Please try again.';
  }
}
