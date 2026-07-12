class WorkingHoursResponse {
  final WorkingHours workingHours;

  const WorkingHoursResponse({required this.workingHours});

  factory WorkingHoursResponse.fromJson(Map<String, dynamic> json) {
    return WorkingHoursResponse(
      workingHours: WorkingHours.fromJson(
        json['working_hours'] as Map<String, dynamic>,
      ),
    );
  }
}

class WorkingHours {
  final WorkingHoursBatch batch;
  final String timezone;
  final List<WorkingHourDay> days;

  const WorkingHours({
    required this.batch,
    required this.timezone,
    required this.days,
  });

  factory WorkingHours.fromJson(Map<String, dynamic> json) {
    return WorkingHours(
      batch: WorkingHoursBatch.fromJson(json['batch'] as Map<String, dynamic>),
      timezone: json['timezone']?.toString() ?? '',
      days: (json['days'] as List<dynamic>? ?? [])
          .map((day) => WorkingHourDay.fromJson(day as Map<String, dynamic>))
          .toList(),
    );
  }
}

class WorkingHoursBatch {
  final String id;
  final String name;
  final String batchCode;

  const WorkingHoursBatch({
    required this.id,
    required this.name,
    required this.batchCode,
  });

  factory WorkingHoursBatch.fromJson(Map<String, dynamic> json) {
    return WorkingHoursBatch(
      id: json['id']?.toString() ?? '',
      name: json['name']?.toString() ?? '',
      batchCode: json['batch_code']?.toString() ?? '',
    );
  }
}

class WorkingHourDay {
  final String id;
  final int dayOfWeek;
  final String dayName;
  final bool isWorkingDay;
  final String? startTime;
  final String? endTime;
  final String? breakStartTime;
  final String? breakEndTime;
  final String? notes;

  const WorkingHourDay({
    required this.id,
    required this.dayOfWeek,
    required this.dayName,
    required this.isWorkingDay,
    required this.startTime,
    required this.endTime,
    required this.breakStartTime,
    required this.breakEndTime,
    required this.notes,
  });

  factory WorkingHourDay.fromJson(Map<String, dynamic> json) {
    return WorkingHourDay(
      id: json['id']?.toString() ?? '',
      dayOfWeek: int.tryParse(json['day_of_week']?.toString() ?? '') ?? 0,
      dayName: json['day_name']?.toString() ?? '',
      isWorkingDay: json['is_working_day'] == true,
      startTime: json['start_time']?.toString(),
      endTime: json['end_time']?.toString(),
      breakStartTime: json['break_start_time']?.toString(),
      breakEndTime: json['break_end_time']?.toString(),
      notes: json['notes']?.toString(),
    );
  }

  bool isToday(DateTime date) {
    return dayOfWeek == date.weekday;
  }
}
