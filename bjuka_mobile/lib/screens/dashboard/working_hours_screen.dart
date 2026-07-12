import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../data/models/working_hours_model.dart';
import '../../providers/providers.dart';

class WorkingHoursScreen extends ConsumerWidget {
  const WorkingHoursScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final theme = Theme.of(context);
    final workingHoursState = ref.watch(workingHoursProvider);

    return Scaffold(
      backgroundColor: theme.colorScheme.surfaceContainerLowest,
      appBar: AppBar(
        title: const Text('Working Hours'),
        backgroundColor: theme.colorScheme.surfaceContainerLowest,
        scrolledUnderElevation: 0,
        centerTitle: false,
      ),
      body: SafeArea(
        child: workingHoursState.when(
          data: (response) {
            final today = DateTime.now();
            final days = response.workingHours.days;
            final todaySchedule = _todaySchedule(days, today);

            return RefreshIndicator(
              onRefresh: () => ref.refresh(workingHoursProvider.future),
              child: ListView.builder(
                padding: const EdgeInsets.symmetric(
                  horizontal: 16,
                  vertical: 12,
                ),
                itemCount: days.length + 2,
                itemBuilder: (context, index) {
                  if (index == 0) {
                    return _WorkingHoursHeader(
                      batchName: response.workingHours.batch.name,
                      batchCode: response.workingHours.batch.batchCode,
                      timezone: response.workingHours.timezone,
                    );
                  }

                  if (index == 1) {
                    return _TodayScheduleCard(day: todaySchedule);
                  }

                  final day = days[index - 2];
                  return _WorkingHourDayCard(
                    day: day,
                    isToday: day.isToday(today),
                  );
                },
              ),
            );
          },
          loading: () => const Center(child: CircularProgressIndicator()),
          error: (error, _) => ListView(
            padding: const EdgeInsets.all(24),
            children: [
              const SizedBox(height: 120),
              Icon(
                Icons.schedule_outlined,
                size: 44,
                color: theme.colorScheme.onSurfaceVariant,
              ),
              const SizedBox(height: 16),
              Text(
                'Could not load working hours.',
                textAlign: TextAlign.center,
                style: theme.textTheme.titleMedium?.copyWith(
                  fontWeight: FontWeight.w700,
                ),
              ),
              const SizedBox(height: 8),
              Text(
                error.toString(),
                textAlign: TextAlign.center,
                style: theme.textTheme.bodySmall?.copyWith(
                  color: theme.colorScheme.onSurfaceVariant,
                ),
              ),
              const SizedBox(height: 16),
              FilledButton(
                onPressed: () => ref.invalidate(workingHoursProvider),
                child: const Text('Try again'),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

WorkingHourDay? _todaySchedule(List<WorkingHourDay> days, DateTime today) {
  for (final day in days) {
    if (day.isToday(today)) {
      return day;
    }
  }

  return null;
}

class _WorkingHoursHeader extends StatelessWidget {
  final String batchName;
  final String batchCode;
  final String timezone;

  const _WorkingHoursHeader({
    required this.batchName,
    required this.batchCode,
    required this.timezone,
  });

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    return Padding(
      padding: const EdgeInsets.only(left: 4, bottom: 18, top: 8),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            'B. JUKA Technologies',
            style: theme.textTheme.labelLarge?.copyWith(
              color: theme.colorScheme.primary,
              fontWeight: FontWeight.w700,
              letterSpacing: 1.1,
            ),
          ),
          const SizedBox(height: 4),
          Text(
            'Company Working Hours',
            style: theme.textTheme.headlineSmall?.copyWith(
              fontWeight: FontWeight.w800,
              color: theme.colorScheme.onSurface,
            ),
          ),
          const SizedBox(height: 6),
          Text(
            '$batchName • $batchCode',
            style: theme.textTheme.bodyMedium?.copyWith(
              color: theme.colorScheme.onSurfaceVariant,
            ),
          ),
          if (timezone.isNotEmpty) ...[
            const SizedBox(height: 4),
            Text(
              'Timezone: $timezone',
              style: theme.textTheme.bodySmall?.copyWith(
                color: theme.colorScheme.onSurfaceVariant,
              ),
            ),
          ],
        ],
      ),
    );
  }
}

class _TodayScheduleCard extends StatelessWidget {
  final WorkingHourDay? day;

  const _TodayScheduleCard({required this.day});

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final isWorkingDay = day?.isWorkingDay ?? false;

    return Container(
      margin: const EdgeInsets.only(bottom: 16),
      padding: const EdgeInsets.all(18),
      decoration: BoxDecoration(
        color: isWorkingDay
            ? theme.colorScheme.primaryContainer
            : theme.colorScheme.secondaryContainer,
        borderRadius: BorderRadius.circular(16),
      ),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Icon(
            isWorkingDay ? Icons.access_time_filled : Icons.event_busy,
            color: isWorkingDay
                ? theme.colorScheme.onPrimaryContainer
                : theme.colorScheme.onSecondaryContainer,
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  'Today',
                  style: theme.textTheme.labelLarge?.copyWith(
                    color: isWorkingDay
                        ? theme.colorScheme.onPrimaryContainer
                        : theme.colorScheme.onSecondaryContainer,
                    fontWeight: FontWeight.w800,
                  ),
                ),
                const SizedBox(height: 4),
                Text(
                  day == null
                      ? 'No schedule has been configured for today.'
                      : day!.isWorkingDay
                      ? '${_formatTime(day!.startTime)} - ${_formatTime(day!.endTime)}'
                      : 'No attendance is expected today.',
                  style: theme.textTheme.titleMedium?.copyWith(
                    color: isWorkingDay
                        ? theme.colorScheme.onPrimaryContainer
                        : theme.colorScheme.onSecondaryContainer,
                    fontWeight: FontWeight.w700,
                  ),
                ),
                if (day?.isWorkingDay == true &&
                    day?.breakStartTime != null &&
                    day?.breakEndTime != null) ...[
                  const SizedBox(height: 4),
                  Text(
                    'Break: ${_formatTime(day!.breakStartTime)} - ${_formatTime(day!.breakEndTime)}',
                    style: theme.textTheme.bodySmall?.copyWith(
                      color: theme.colorScheme.onPrimaryContainer.withValues(
                        alpha: 0.82,
                      ),
                    ),
                  ),
                ],
              ],
            ),
          ),
        ],
      ),
    );
  }
}

class _WorkingHourDayCard extends StatelessWidget {
  final WorkingHourDay day;
  final bool isToday;

  const _WorkingHourDayCard({required this.day, required this.isToday});

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    return Container(
      margin: const EdgeInsets.only(bottom: 12),
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: theme.colorScheme.surface,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(
          color: isToday
              ? theme.colorScheme.primary.withValues(alpha: 0.55)
              : theme.colorScheme.outlineVariant,
          width: isToday ? 2 : 1,
        ),
      ),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Container(
            width: 44,
            height: 44,
            decoration: BoxDecoration(
              color: day.isWorkingDay
                  ? theme.colorScheme.primary.withValues(alpha: 0.1)
                  : theme.colorScheme.surfaceContainerHighest,
              borderRadius: BorderRadius.circular(12),
            ),
            child: Icon(
              day.isWorkingDay
                  ? Icons.calendar_today_rounded
                  : Icons.weekend_rounded,
              color: day.isWorkingDay
                  ? theme.colorScheme.primary
                  : theme.colorScheme.onSurfaceVariant,
            ),
          ),
          const SizedBox(width: 14),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  children: [
                    Expanded(
                      child: Text(
                        day.dayName,
                        style: theme.textTheme.titleMedium?.copyWith(
                          fontWeight: FontWeight.w800,
                        ),
                      ),
                    ),
                    if (isToday)
                      Container(
                        padding: const EdgeInsets.symmetric(
                          horizontal: 8,
                          vertical: 3,
                        ),
                        decoration: BoxDecoration(
                          color: theme.colorScheme.primaryContainer,
                          borderRadius: BorderRadius.circular(8),
                        ),
                        child: Text(
                          'TODAY',
                          style: theme.textTheme.labelSmall?.copyWith(
                            color: theme.colorScheme.onPrimaryContainer,
                            fontWeight: FontWeight.w800,
                          ),
                        ),
                      ),
                  ],
                ),
                const SizedBox(height: 6),
                Text(
                  day.isWorkingDay
                      ? '${_formatTime(day.startTime)} - ${_formatTime(day.endTime)}'
                      : 'Non-working day',
                  style: theme.textTheme.bodyMedium?.copyWith(
                    color: theme.colorScheme.onSurface,
                    fontWeight: FontWeight.w600,
                  ),
                ),
                if (day.isWorkingDay &&
                    day.breakStartTime != null &&
                    day.breakEndTime != null) ...[
                  const SizedBox(height: 4),
                  Text(
                    'Break: ${_formatTime(day.breakStartTime)} - ${_formatTime(day.breakEndTime)}',
                    style: theme.textTheme.bodySmall?.copyWith(
                      color: theme.colorScheme.onSurfaceVariant,
                    ),
                  ),
                ],
                if ((day.notes ?? '').trim().isNotEmpty) ...[
                  const SizedBox(height: 8),
                  Text(
                    day.notes!.trim(),
                    style: theme.textTheme.bodySmall?.copyWith(
                      color: theme.colorScheme.onSurfaceVariant,
                      height: 1.4,
                    ),
                  ),
                ],
              ],
            ),
          ),
        ],
      ),
    );
  }
}

String _formatTime(String? value) {
  if (value == null || value.isEmpty) {
    return '--';
  }

  final parts = value.split(':');
  final hour = int.tryParse(parts.first) ?? 0;
  final minute = parts.length > 1 ? int.tryParse(parts[1]) ?? 0 : 0;
  final period = hour >= 12 ? 'PM' : 'AM';
  final hour12 = hour % 12 == 0 ? 12 : hour % 12;
  final minuteText = minute.toString().padLeft(2, '0');

  return '$hour12:$minuteText $period';
}
