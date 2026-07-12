class ReportStatusResponse {
  final InternshipReportAvailability report;

  ReportStatusResponse({required this.report});

  factory ReportStatusResponse.fromJson(Map<String, dynamic> json) {
    return ReportStatusResponse(
      report: InternshipReportAvailability.fromJson(
        json['report'] is Map<String, dynamic>
            ? json['report'] as Map<String, dynamic>
            : const <String, dynamic>{},
      ),
    );
  }
}

class InternshipReportAvailability {
  final bool available;
  final bool completionRequired;
  final ReportQuota quota;
  final GeneratedReport? latestReport;

  InternshipReportAvailability({
    required this.available,
    required this.completionRequired,
    required this.quota,
    this.latestReport,
  });

  factory InternshipReportAvailability.fromJson(Map<String, dynamic> json) {
    final latestReportJson = json['latest_report'];

    return InternshipReportAvailability(
      available: json['available'] == true,
      completionRequired: json['completion_required'] == true,
      quota: ReportQuota.fromJson(
        json['quota'] is Map<String, dynamic>
            ? json['quota'] as Map<String, dynamic>
            : const <String, dynamic>{},
      ),
      latestReport: latestReportJson is Map<String, dynamic>
          ? GeneratedReport.fromJson(latestReportJson)
          : null,
    );
  }
}

class ReportQuota {
  final int generationCount;
  final int generationLimit;
  final int remainingGenerations;
  final bool resetRequested;
  final bool resetUsed;
  final bool canGenerate;
  final bool canRequestReset;
  final bool permanentlyLocked;

  ReportQuota({
    required this.generationCount,
    required this.generationLimit,
    required this.remainingGenerations,
    required this.resetRequested,
    required this.resetUsed,
    required this.canGenerate,
    required this.canRequestReset,
    required this.permanentlyLocked,
  });

  factory ReportQuota.fromJson(Map<String, dynamic> json) {
    return ReportQuota(
      generationCount: _parseInt(json['generation_count']),
      generationLimit: _parseInt(json['generation_limit']),
      remainingGenerations: _parseInt(json['remaining_generations']),
      resetRequested: json['reset_requested'] == true,
      resetUsed: json['reset_used'] == true,
      canGenerate: json['can_generate'] == true,
      canRequestReset: json['can_request_reset'] == true,
      permanentlyLocked: json['permanently_locked'] == true,
    );
  }
}

class GeneratedReport {
  final String id;
  final String status;
  final ReportContent? content;
  final String? downloadUrl;
  final DateTime? generatedAt;

  GeneratedReport({
    required this.id,
    required this.status,
    this.content,
    this.downloadUrl,
    this.generatedAt,
  });

  factory GeneratedReport.fromJson(Map<String, dynamic> json) {
    return GeneratedReport(
      id: json['id']?.toString() ?? '',
      status: json['status']?.toString() ?? '',
      content: json['content'] is Map<String, dynamic>
          ? ReportContent.fromJson(json['content'] as Map<String, dynamic>)
          : null,
      downloadUrl: json['download_url'] as String?,
      generatedAt: _parseDateTime(json['generated_at']),
    );
  }
}

class ReportContent {
  final String title;
  final List<ReportSection> sections;

  ReportContent({required this.title, required this.sections});

  factory ReportContent.fromJson(Map<String, dynamic> json) {
    final sectionsJson = json['sections'];

    return ReportContent(
      title: json['title']?.toString() ?? 'Internship Report Draft',
      sections: sectionsJson is List
          ? sectionsJson
                .whereType<Map<String, dynamic>>()
                .map(ReportSection.fromJson)
                .toList()
          : [],
    );
  }
}

class ReportSection {
  final String heading;
  final String body;
  final List<String> imagePlaceholders;

  ReportSection({
    required this.heading,
    required this.body,
    required this.imagePlaceholders,
  });

  factory ReportSection.fromJson(Map<String, dynamic> json) {
    final placeholdersJson = json['image_placeholders'];

    return ReportSection(
      heading: json['heading']?.toString() ?? 'Section',
      body: json['body']?.toString() ?? '',
      imagePlaceholders: placeholdersJson is List
          ? placeholdersJson.map((value) => value.toString()).toList()
          : [],
    );
  }
}

int _parseInt(dynamic value) {
  if (value is int) {
    return value;
  }

  if (value is num) {
    return value.round();
  }

  return int.tryParse(value?.toString() ?? '') ?? 0;
}

DateTime? _parseDateTime(dynamic value) {
  if (value == null) {
    return null;
  }

  return DateTime.tryParse(value.toString())?.toLocal();
}
