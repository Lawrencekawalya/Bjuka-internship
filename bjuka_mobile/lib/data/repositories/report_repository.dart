import 'package:dio/dio.dart';
import '../models/report_model.dart';

class ReportRepository {
  final Dio _dio;

  ReportRepository(this._dio);

  Future<ReportStatusResponse> getStatus() async {
    final response = await _dio.get('/intern/report/status');
    return ReportStatusResponse.fromJson(response.data);
  }

  Future<GeneratedReportResponse> generate() async {
    final response = await _dio.post('/intern/report/generate');
    return GeneratedReportResponse.fromJson(response.data);
  }

  Future<ReportStatusResponse> requestReset() async {
    final response = await _dio.post('/intern/report/request-reset');
    return ReportStatusResponse(
      report: InternshipReportAvailability(
        available: true,
        completionRequired: false,
        quota: ReportQuota.fromJson(response.data['quota']),
      ),
    );
  }
}

class GeneratedReportResponse {
  final GeneratedReport report;
  final ReportQuota quota;

  GeneratedReportResponse({required this.report, required this.quota});

  factory GeneratedReportResponse.fromJson(Map<String, dynamic> json) {
    return GeneratedReportResponse(
      report: GeneratedReport.fromJson(json['report'] as Map<String, dynamic>),
      quota: ReportQuota.fromJson(json['quota'] as Map<String, dynamic>),
    );
  }
}
