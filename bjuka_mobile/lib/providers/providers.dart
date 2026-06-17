import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../core/network/dio_client.dart';
import '../core/storage/secure_storage_service.dart';
import '../data/repositories/auth_repository.dart';
import 'auth_provider.dart';

final secureStorageProvider = Provider((ref) => SecureStorageService());

final dioProvider = Provider((ref) {
  final storage = ref.watch(secureStorageProvider);
  return DioClient(storage).dio;
});

final authRepositoryProvider = Provider((ref) {
  final dio = ref.watch(dioProvider);
  return AuthRepository(dio);
});

final authStateProvider = NotifierProvider<AuthNotifier, AuthState>(() {
  return AuthNotifier();
});
