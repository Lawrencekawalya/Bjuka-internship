import 'package:network_info_plus/network_info_plus.dart';
import 'package:permission_handler/permission_handler.dart';

class WifiInfo {
  final String ssid;
  final String bssid;

  WifiInfo({required this.ssid, required this.bssid});
}

class WifiInfoService {
  final NetworkInfo _networkInfo;

  WifiInfoService({NetworkInfo? networkInfo})
    : _networkInfo = networkInfo ?? NetworkInfo();

  Future<WifiInfo> currentWifi() async {
    final permission = await Permission.locationWhenInUse.request();
    if (!permission.isGranted) {
      throw WifiInfoException(
        'Location permission is required to verify office Wi-Fi.',
      );
    }

    final ssid = _cleanWifiValue(await _networkInfo.getWifiName());
    final bssid = _cleanWifiValue(await _networkInfo.getWifiBSSID());

    if (ssid == null || bssid == null) {
      throw WifiInfoException(
        'Connect to the office Wi-Fi before checking in.',
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
}

class WifiInfoException implements Exception {
  final String message;

  WifiInfoException(this.message);

  @override
  String toString() => message;
}
