import 'package:dio/dio.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../data/models/report_model.dart';
import '../data/repositories/report_repository.dart';
import 'providers.dart';

class ReportState {
  final bool isLoading;
  final bool isGenerating;
  final bool isRequestingReset;
  final InternshipReportAvailability? availability;
  final String? errorMessage;
  final String? successMessage;

  ReportState({
    required this.isLoading,
    required this.isGenerating,
    required this.isRequestingReset,
    this.availability,
    this.errorMessage,
    this.successMessage,
  });

  factory ReportState.initial() {
    return ReportState(
      isLoading: true,
      isGenerating: false,
      isRequestingReset: false,
    );
  }

  ReportState copyWith({
    bool? isLoading,
    bool? isGenerating,
    bool? isRequestingReset,
    InternshipReportAvailability? availability,
    String? errorMessage,
    String? successMessage,
    bool clearMessages = false,
  }) {
    return ReportState(
      isLoading: isLoading ?? this.isLoading,
      isGenerating: isGenerating ?? this.isGenerating,
      isRequestingReset: isRequestingReset ?? this.isRequestingReset,
      availability: availability ?? this.availability,
      errorMessage: clearMessages ? null : errorMessage,
      successMessage: clearMessages ? null : successMessage,
    );
  }
}

class ReportNotifier extends Notifier<ReportState> {
  @override
  ReportState build() {
    return ReportState.initial();
  }

  ReportRepository get _repository => ref.read(reportRepositoryProvider);

  Future<void> loadStatus() async {
    state = state.copyWith(isLoading: true, clearMessages: true);

    try {
      final response = await _repository.getStatus();
      state = state.copyWith(isLoading: false, availability: response.report);
    } catch (e) {
      state = state.copyWith(
        isLoading: false,
        errorMessage: _messageForError(e),
      );
    }
  }

  Future<void> generate() async {
    state = state.copyWith(isGenerating: true, clearMessages: true);

    try {
      final response = await _repository.generate();
      final current = state.availability;
      state = state.copyWith(
        isGenerating: false,
        availability: InternshipReportAvailability(
          available: current?.available ?? true,
          completionRequired: current?.completionRequired ?? false,
          quota: response.quota,
          latestReport: response.report,
        ),
        successMessage: 'Report draft generated.',
      );
    } catch (e) {
      state = state.copyWith(
        isGenerating: false,
        errorMessage: _messageForError(e),
      );
    }
  }

  Future<void> requestReset() async {
    state = state.copyWith(isRequestingReset: true, clearMessages: true);

    try {
      await _repository.requestReset();
      await loadStatus();
      state = state.copyWith(
        isRequestingReset: false,
        successMessage: 'Reset request sent to admin.',
      );
    } catch (e) {
      state = state.copyWith(
        isRequestingReset: false,
        errorMessage: _messageForError(e),
      );
    }
  }

  String _messageForError(Object error) {
    if (error is DioException) {
      final data = error.response?.data;

      if (data is Map<String, dynamic> && data['message'] is String) {
        return data['message'];
      }

      if (error.type == DioExceptionType.receiveTimeout ||
          error.type == DioExceptionType.sendTimeout) {
        return 'Report generation is taking longer than expected. Please try refreshing in a moment.';
      }

      if (error.type == DioExceptionType.connectionTimeout ||
          error.type == DioExceptionType.connectionError) {
        return 'Unable to reach the server. Please check your connection.';
      }
    }

    return 'Something went wrong. Please try again.';
  }
}
