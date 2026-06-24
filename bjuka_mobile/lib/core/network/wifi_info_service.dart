import 'package:flutter/foundation.dart';
import 'package:network_info_plus/network_info_plus.dart';
import 'package:permission_handler/permission_handler.dart';

enum AttendanceAction { checkIn, checkOut }

class WifiInfo {
  final String ssid;
  final String? bssid;

  WifiInfo({required this.ssid, this.bssid});
}

class WifiInfoService {
  final NetworkInfo _networkInfo;

  WifiInfoService({NetworkInfo? networkInfo})
    : _networkInfo = networkInfo ?? NetworkInfo();

  Future<WifiInfo> currentWifi({required AttendanceAction action}) async {
    final locationPermission = await Permission.locationWhenInUse.request();
    if (!locationPermission.isGranted) {
      throw WifiInfoException(
        'Location permission is required to verify office Wi-Fi.',
      );
    }

    if (!kIsWeb && defaultTargetPlatform == TargetPlatform.android) {
      await Permission.nearbyWifiDevices.request();
    }

    final String? rawSsid;
    final String? rawBssid;
    try {
      rawSsid = await _networkInfo.getWifiName();
      rawBssid = await _networkInfo.getWifiBSSID();
    } catch (_) {
      throw WifiInfoException(
        'Unable to read Wi-Fi details. Turn on Wi-Fi and location, then try again.',
      );
    }

    final ssid = _cleanWifiValue(rawSsid);
    final bssid = _cleanWifiValue(rawBssid);

    if (ssid == null) {
      throw WifiInfoException(
        'Unable to identify the connected Wi-Fi. Turn on Wi-Fi and location, then try ${_actionLabel(action)} again.',
      );
    }

    return WifiInfo(ssid: ssid, bssid: bssid);
  }

  String? _cleanWifiValue(String? value) {
    final cleaned = value?.replaceAll('"', '').trim();
    if (cleaned == null || cleaned.isEmpty || cleaned == '<unknown ssid>') {
      return null;
    }

    return cleaned;
  }

  String _actionLabel(AttendanceAction action) {
    return switch (action) {
      AttendanceAction.checkIn => 'checking in',
      AttendanceAction.checkOut => 'checking out',
    };
  }
}

class WifiInfoException implements Exception {
  final String message;

  WifiInfoException(this.message);

  @override
  String toString() => message;
}
