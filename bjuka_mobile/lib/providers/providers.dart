import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../core/network/dio_client.dart';
import '../core/network/wifi_info_service.dart';
import '../core/storage/secure_storage_service.dart';
import '../data/repositories/attendance_repository.dart';
import '../data/repositories/auth_repository.dart';
import 'attendance_provider.dart';
import 'auth_provider.dart';

final secureStorageProvider = Provider((ref) => SecureStorageService());

final wifiInfoProvider = Provider((ref) => WifiInfoService());

final dioProvider = Provider((ref) {
  final storage = ref.watch(secureStorageProvider);
  return DioClient(storage).dio;
});

final authRepositoryProvider = Provider((ref) {
  final dio = ref.watch(dioProvider);
  return AuthRepository(dio);
});

final attendanceRepositoryProvider = Provider((ref) {
  final dio = ref.watch(dioProvider);
  return AttendanceRepository(dio);
});

final authStateProvider = NotifierProvider<AuthNotifier, AuthState>(() {
  return AuthNotifier();
});

final attendanceStateProvider =
    NotifierProvider<AttendanceNotifier, AttendanceState>(() {
      return AttendanceNotifier();
    });
