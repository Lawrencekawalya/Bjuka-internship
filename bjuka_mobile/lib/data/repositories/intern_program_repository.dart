import 'package:dio/dio.dart';

import '../models/intern_program_model.dart';

class InternProgramRepository {
  final Dio _dio;

  InternProgramRepository(this._dio);

  Future<InternProgramResponse> getProgram() async {
    final response = await _dio.get('/intern/program');
    return InternProgramResponse.fromJson(response.data);
  }
}
