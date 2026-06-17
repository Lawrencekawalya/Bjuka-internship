import 'package:dio/dio.dart';
import '../storage/secure_storage_service.dart';
import '../config/app_config.dart';

class DioClient {
  final SecureStorageService _storage;

  static const String baseUrl = AppConfig.apiBaseUrl;

  DioClient(this._storage);
  Dio get dio {
    final dio = Dio(BaseOptions(
      baseUrl: baseUrl,
      connectTimeout: const Duration(seconds: 5),
      receiveTimeout: const Duration(seconds: 3),
      headers: {
        'Accept': 'application/json',
      },
    ));

    dio.interceptors.add(InterceptorsWrapper(
      onRequest: (options, handler) async {
        final token = await _storage.getToken();
        if (token != null) {
          options.headers['Authorization'] = 'Bearer $token';
        }
        return handler.next(options);
      },
      onError: (DioException e, handler) async {
        if (e.response?.statusCode == 401) {
          await _storage.deleteToken();
          // Logic for navigating to login would be handled by state management
        }
        return handler.next(e);
      },
    ));

    return dio;
  }
}
