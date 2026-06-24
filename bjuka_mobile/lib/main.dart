import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter/services.dart';
import 'providers/providers.dart';
import 'providers/auth_provider.dart';
import 'screens/splash/splash_screen.dart';
import 'screens/auth/login_screen.dart';
import 'screens/dashboard/attendance_dashboard_screen.dart';
import 'core/config/app_config.dart';
import 'theme/bjuka_brand.dart';

void main() {
  WidgetsFlutterBinding.ensureInitialized();
  SystemChrome.setSystemUIOverlayStyle(
    const SystemUiOverlayStyle(
      statusBarColor: Colors.white,
      statusBarIconBrightness: Brightness.dark,
      statusBarBrightness: Brightness.light,
      systemNavigationBarColor: Colors.white,
      systemNavigationBarIconBrightness: Brightness.dark,
    ),
  );
  AppConfig.logConfig();
  runApp(const ProviderScope(child: MyApp()));
}

class MyApp extends ConsumerWidget {
  const MyApp({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final authState = ref.watch(authStateProvider);

    return MaterialApp(
      title: 'B. JUKA Internship',
      debugShowCheckedModeBanner: false,
      theme: BjukaBrand.lightTheme(),
      home: _getHome(authState.status),
    );
  }

  Widget _getHome(AuthStatus status) {
    switch (status) {
      case AuthStatus.initial:
        return const SplashScreen();
      case AuthStatus.authenticated:
        return const AttendanceDashboardScreen();
      case AuthStatus.unauthenticated:
      case AuthStatus.error:
        return const LoginScreen();
      case AuthStatus.authenticating:
        return const Scaffold(body: Center(child: CircularProgressIndicator()));
    }
  }
}
