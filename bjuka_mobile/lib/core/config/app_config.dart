import 'package:flutter/foundation.dart';

class AppConfig {
  /// The environment the app is running in (local, staging, production)
  static const String environment = String.fromEnvironment(
    'APP_ENV',
    defaultValue: 'local',
  );

  /// The base URL for the API
  static const String apiBaseUrl = String.fromEnvironment(
    'API_BASE_URL',
    defaultValue: 'http://10.0.2.2:8000/api',
  );

  static bool get isLocal => environment == 'local';
  static bool get isStaging => environment == 'staging';
  static bool get isProduction => environment == 'production';

  /// Log configuration for debugging
  static void logConfig() {
    debugPrint('--- App Configuration ---');
    debugPrint('Environment: $environment');
    debugPrint('API Base URL: $apiBaseUrl');
    debugPrint('-------------------------');
  }
}
