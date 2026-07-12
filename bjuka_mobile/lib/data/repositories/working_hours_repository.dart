import 'package:dio/dio.dart';

import '../models/working_hours_model.dart';

class WorkingHoursRepository {
  final Dio _dio;

  WorkingHoursRepository(this._dio);

  Future<WorkingHoursResponse> getWorkingHours() async {
    final response = await _dio.get('/intern/working-hours');
    return WorkingHoursResponse.fromJson(response.data);
  }
}
