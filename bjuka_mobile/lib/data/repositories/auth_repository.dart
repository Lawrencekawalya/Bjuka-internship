import 'package:dio/dio.dart';
import '../models/user_model.dart';

class AuthRepository {
  final Dio _dio;

  AuthRepository(this._dio);

  Future<AuthResponse> login({
    required String email,
    required String password,
    required String deviceName,
  }) async {
    final response = await _dio.post('/login', data: {
      'email': email,
      'password': password,
      'device_name': deviceName,
    });

    return AuthResponse.fromJson(response.data);
  }

  Future<User> getMe() async {
    final response = await _dio.get('/me');
    return User.fromJson(response.data['user']);
  }

  Future<void> logout() async {
    await _dio.post('/logout');
  }
}
