import 'package:dio/dio.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../data/models/user_model.dart';
import '../data/repositories/auth_repository.dart';
import '../core/storage/secure_storage_service.dart';
import 'providers.dart';

enum AuthStatus {
  initial,
  authenticating,
  authenticated,
  unauthenticated,
  error,
}

class AuthState {
  final AuthStatus status;
  final User? user;
  final String? errorMessage;

  AuthState({required this.status, this.user, this.errorMessage});

  factory AuthState.initial() => AuthState(status: AuthStatus.initial);
  factory AuthState.authenticating() =>
      AuthState(status: AuthStatus.authenticating);
  factory AuthState.authenticated(User user) =>
      AuthState(status: AuthStatus.authenticated, user: user);
  factory AuthState.unauthenticated() =>
      AuthState(status: AuthStatus.unauthenticated);
  factory AuthState.error(String message) =>
      AuthState(status: AuthStatus.error, errorMessage: message);
}

class AuthNotifier extends Notifier<AuthState> {
  @override
  AuthState build() {
    return AuthState.initial();
  }

  AuthRepository get _repository => ref.read(authRepositoryProvider);
  SecureStorageService get _storage => ref.read(secureStorageProvider);

  Future<void> checkAuth() async {
    final hasToken = await _storage.hasToken();
    if (!hasToken) {
      state = AuthState.unauthenticated();
      return;
    }

    try {
      final user = await _repository.getMe();
      state = AuthState.authenticated(user);
    } catch (e) {
      await _storage.deleteToken();
      state = AuthState.unauthenticated();
    }
  }

  Future<void> login(String email, String password, String deviceName) async {
    state = AuthState.authenticating();
    try {
      final response = await _repository.login(
        email: email.trim(),
        password: password,
        deviceName: deviceName,
      );
      await _storage.saveToken(response.token);
      state = AuthState.authenticated(response.user);
    } catch (e) {
      state = AuthState.error(_readAuthError(e));
    }
  }

  Future<void> logout() async {
    try {
      await _repository.logout();
    } finally {
      await _storage.deleteToken();
      state = AuthState.unauthenticated();
    }
  }

  String _readAuthError(Object error) {
    if (error is! DioException) {
      return 'Unable to log in. Please try again.';
    }

    final data = error.response?.data;
    if (data is Map<String, dynamic>) {
      final errors = data['errors'];
      if (errors is Map<String, dynamic>) {
        for (final value in errors.values) {
          if (value is List && value.isNotEmpty) {
            return value.first.toString();
          }
        }
      }

      final message = data['message'];
      if (message is String && message.isNotEmpty) {
        return message;
      }
    }

    if (error.type == DioExceptionType.connectionTimeout ||
        error.type == DioExceptionType.receiveTimeout ||
        error.type == DioExceptionType.connectionError) {
      return 'Unable to connect to server. Please check your internet connection.';
    }

    return 'Unable to log in. Please try again.';
  }
}
