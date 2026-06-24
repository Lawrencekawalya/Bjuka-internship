import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../data/models/attendance_model.dart';
import '../../providers/providers.dart';

class AttendanceHistoryScreen extends ConsumerWidget {
  const AttendanceHistoryScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final historyState = ref.watch(attendanceHistoryProvider);

    return Scaffold(
      appBar: AppBar(title: const Text('Attendance History')),
      body: SafeArea(
        child: RefreshIndicator(
          onRefresh: () async {
            ref.invalidate(attendanceHistoryProvider);
            await ref.read(attendanceHistoryProvider.future);
          },
          child: historyState.when(
            data: (history) => _HistoryList(attendances: history.attendances),
            loading: () => const Center(child: CircularProgressIndicator()),
            error: (error, stackTrace) => ListView(
              padding: const EdgeInsets.all(16),
              children: const [
                _EmptyState(
                  icon: Icons.error_outline,
                  title: 'Unable to load history',
                  subtitle: 'Pull down to try again.',
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}

class _HistoryList extends StatelessWidget {
  final List<Attendance> attendances;

  const _HistoryList({required this.attendances});

  @override
  Widget build(BuildContext context) {
    if (attendances.isEmpty) {
      return ListView(
        padding: const EdgeInsets.all(16),
        children: const [
          _EmptyState(
            icon: Icons.history,
            title: 'No attendance history yet',
            subtitle: 'Your past check-ins will appear here.',
          ),
        ],
      );
    }

    return ListView.separated(
      padding: const EdgeInsets.all(16),
      itemBuilder: (context, index) {
        return _AttendanceHistoryCard(attendance: attendances[index]);
      },
      separatorBuilder: (context, index) => const SizedBox(height: 12),
      itemCount: attendances.length,
    );
  }
}

class _AttendanceHistoryCard extends StatelessWidget {
  final Attendance attendance;

  const _AttendanceHistoryCard({required this.attendance});

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final statusColor = _statusColor(context, attendance.status);

    return Card(
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                CircleAvatar(
                  radius: 22,
                  backgroundColor: statusColor.withValues(alpha: 0.12),
                  child: Icon(Icons.event_available, color: statusColor),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        _formatDate(attendance.date),
                        style: theme.textTheme.titleMedium?.copyWith(
                          fontWeight: FontWeight.w700,
                        ),
                      ),
                      const SizedBox(height: 2),
                      Text(
                        attendance.status.toUpperCase(),
                        style: theme.textTheme.bodySmall?.copyWith(
                          color: statusColor,
                          fontWeight: FontWeight.w700,
                        ),
                      ),
                    ],
                  ),
                ),
              ],
            ),
            const SizedBox(height: 14),
            _HistoryDetailRow(
              label: 'Check in',
              value: _formatTime(attendance.checkInServerTime),
            ),
            _HistoryDetailRow(
              label: 'Check out',
              value: _formatTime(attendance.checkOutServerTime),
            ),
            _HistoryDetailRow(
              label: 'Duration',
              value: _formatDuration(attendance.workDurationMinutes),
            ),
            if (attendance.dailyActivities != null) ...[
              const SizedBox(height: 12),
              Text(
                'Activities',
                style: theme.textTheme.bodySmall?.copyWith(
                  color: theme.colorScheme.onSurfaceVariant,
                  fontWeight: FontWeight.w600,
                ),
              ),
              const SizedBox(height: 4),
              Text(attendance.dailyActivities!),
            ],
          ],
        ),
      ),
    );
  }

  Color _statusColor(BuildContext context, String status) {
    return switch (status.toLowerCase()) {
      'present' => Colors.green,
      'late' => Colors.orange,
      _ => Theme.of(context).colorScheme.primary,
    };
  }
}

class _HistoryDetailRow extends StatelessWidget {
  final String label;
  final String value;

  const _HistoryDetailRow({required this.label, required this.value});

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 4),
      child: Row(
        children: [
          Expanded(
            child: Text(
              label,
              style: theme.textTheme.bodyMedium?.copyWith(
                color: theme.colorScheme.onSurfaceVariant,
              ),
            ),
          ),
          Flexible(
            child: Text(
              value,
              textAlign: TextAlign.right,
              style: theme.textTheme.bodyMedium?.copyWith(
                fontWeight: FontWeight.w600,
              ),
            ),
          ),
        ],
      ),
    );
  }
}

class _EmptyState extends StatelessWidget {
  final IconData icon;
  final String title;
  final String subtitle;

  const _EmptyState({
    required this.icon,
    required this.title,
    required this.subtitle,
  });

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    return SizedBox(
      height: MediaQuery.sizeOf(context).height * 0.65,
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          Icon(icon, size: 42, color: theme.colorScheme.outline),
          const SizedBox(height: 12),
          Text(
            title,
            textAlign: TextAlign.center,
            style: theme.textTheme.titleMedium?.copyWith(
              fontWeight: FontWeight.w700,
            ),
          ),
          const SizedBox(height: 4),
          Text(
            subtitle,
            textAlign: TextAlign.center,
            style: theme.textTheme.bodyMedium?.copyWith(
              color: theme.colorScheme.onSurfaceVariant,
            ),
          ),
        ],
      ),
    );
  }
}

String _formatDate(String value) {
  final date = DateTime.tryParse(value);
  if (date == null) {
    return value;
  }

  const months = [
    'Jan',
    'Feb',
    'Mar',
    'Apr',
    'May',
    'Jun',
    'Jul',
    'Aug',
    'Sep',
    'Oct',
    'Nov',
    'Dec',
  ];

  return '${months[date.month - 1]} ${date.day}, ${date.year}';
}

String _formatTime(DateTime? value) {
  if (value == null) {
    return 'Not recorded';
  }

  final hour = value.hour % 12 == 0 ? 12 : value.hour % 12;
  final minute = value.minute.toString().padLeft(2, '0');
  final period = value.hour >= 12 ? 'PM' : 'AM';

  return '$hour:$minute $period';
}

String _formatDuration(int? minutes) {
  if (minutes == null) {
    return 'Not recorded';
  }

  final hours = minutes ~/ 60;
  final remainingMinutes = minutes % 60;

  if (hours == 0) {
    return '${remainingMinutes}m';
  }

  return '${hours}h ${remainingMinutes}m';
}
