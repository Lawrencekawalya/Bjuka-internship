class InternProgramResponse {
  final InternProgram program;

  const InternProgramResponse({required this.program});

  factory InternProgramResponse.fromJson(Map<String, dynamic> json) {
    return InternProgramResponse(
      program: InternProgram.fromJson(json['program'] as Map<String, dynamic>),
    );
  }
}

class InternProgram {
  final ProgramBatch batch;
  final List<InternProgramWeek> weeks;

  const InternProgram({required this.batch, required this.weeks});

  factory InternProgram.fromJson(Map<String, dynamic> json) {
    return InternProgram(
      batch: ProgramBatch.fromJson(json['batch'] as Map<String, dynamic>),
      weeks: (json['weeks'] as List<dynamic>? ?? [])
          .map(
            (week) => InternProgramWeek.fromJson(week as Map<String, dynamic>),
          )
          .toList(),
    );
  }
}

class ProgramBatch {
  final String id;
  final String name;
  final String batchCode;

  const ProgramBatch({
    required this.id,
    required this.name,
    required this.batchCode,
  });

  factory ProgramBatch.fromJson(Map<String, dynamic> json) {
    return ProgramBatch(
      id: json['id']?.toString() ?? '',
      name: json['name']?.toString() ?? '',
      batchCode: json['batch_code']?.toString() ?? '',
    );
  }
}

class InternProgramWeek {
  final String id;
  final int weekNumber;
  final String title;
  final DateTime startDate;
  final DateTime endDate;
  final String objectives;
  final String topics;
  final String activities;

  const InternProgramWeek({
    required this.id,
    required this.weekNumber,
    required this.title,
    required this.startDate,
    required this.endDate,
    required this.objectives,
    required this.topics,
    required this.activities,
  });

  factory InternProgramWeek.fromJson(Map<String, dynamic> json) {
    return InternProgramWeek(
      id: json['id']?.toString() ?? '',
      weekNumber: int.tryParse(json['week_number']?.toString() ?? '') ?? 0,
      title: json['title']?.toString() ?? '',
      startDate: DateTime.parse(json['start_date'] as String),
      endDate: DateTime.parse(json['end_date'] as String),
      objectives: json['objectives']?.toString() ?? '',
      topics: json['topics']?.toString() ?? '',
      activities: json['activities']?.toString() ?? '',
    );
  }

  String get weekLabel => 'Week $weekNumber - $title';

  bool isCurrentWeek(DateTime date) {
    final day = DateTime(date.year, date.month, date.day);
    final start = DateTime(startDate.year, startDate.month, startDate.day);
    final end = DateTime(endDate.year, endDate.month, endDate.day);

    return !day.isBefore(start) && !day.isAfter(end);
  }
}
