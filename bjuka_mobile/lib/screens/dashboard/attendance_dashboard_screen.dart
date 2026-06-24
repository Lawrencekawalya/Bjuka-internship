import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../data/models/attendance_model.dart';
import '../../providers/providers.dart';

class AttendanceDashboardScreen extends ConsumerStatefulWidget {
  const AttendanceDashboardScreen({super.key});

  @override
  ConsumerState<AttendanceDashboardScreen> createState() =>
      _AttendanceDashboardScreenState();
}

class _AttendanceDashboardScreenState
    extends ConsumerState<AttendanceDashboardScreen> {
  @override
  void initState() {
    super.initState();
    Future.microtask(
      () => ref.read(attendanceStateProvider.notifier).loadToday(),
    );
  }

  @override
  Widget build(BuildContext context) {
    final authState = ref.watch(authStateProvider);
    final attendanceState = ref.watch(attendanceStateProvider);
    final theme = Theme.of(context);

    ref.listen(attendanceStateProvider, (previous, next) {
      final message = next.errorMessage ?? next.successMessage;
      final previousMessage =
          previous?.errorMessage ?? previous?.successMessage;

      if (message != null && message != previousMessage) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(message),
            backgroundColor: next.errorMessage == null
                ? Colors.green
                : theme.colorScheme.error,
          ),
        );
      }
    });

    return Scaffold(
      appBar: AppBar(
        title: const Text('Attendance'),
        actions: [
          IconButton(
            tooltip: 'Refresh',
            icon: const Icon(Icons.refresh),
            onPressed: attendanceState.isSubmitting
                ? null
                : () => ref.read(attendanceStateProvider.notifier).loadToday(),
          ),
          IconButton(
            tooltip: 'Logout',
            icon: const Icon(Icons.logout),
            onPressed: () => ref.read(authStateProvider.notifier).logout(),
          ),
        ],
      ),
      body: SafeArea(
        child: attendanceState.isLoading
            ? const Center(child: CircularProgressIndicator())
            : RefreshIndicator(
                onRefresh: () =>
                    ref.read(attendanceStateProvider.notifier).loadToday(),
                child: ListView(
                  padding: const EdgeInsets.all(16),
                  children: [
                    _WelcomeHeader(name: authState.user?.name ?? 'Intern'),
                    const SizedBox(height: 16),
                    _StatusCard(attendance: attendanceState.attendance),
                    const SizedBox(height: 16),
                    _ActionPanel(
                      canCheckIn: attendanceState.canCheckIn,
                      canCheckOut: attendanceState.canCheckOut,
                      isSubmitting: attendanceState.isSubmitting,
                    ),
                    const SizedBox(height: 16),
                    _AttendanceDetails(attendance: attendanceState.attendance),
                  ],
                ),
              ),
      ),
    );
  }
}

class _WelcomeHeader extends StatelessWidget {
  final String name;

  const _WelcomeHeader({required this.name});

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    return Container(
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(
        color: theme.colorScheme.primaryContainer,
        borderRadius: BorderRadius.circular(16),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            'Welcome, $name',
            style: theme.textTheme.titleLarge?.copyWith(
              color: theme.colorScheme.onPrimaryContainer,
              fontWeight: FontWeight.w700,
            ),
          ),
          const SizedBox(height: 8),
          Text(
            _formatToday(),
            style: theme.textTheme.bodyMedium?.copyWith(
              color: theme.colorScheme.onPrimaryContainer,
            ),
          ),
        ],
      ),
    );
  }

  static String _formatToday() {
    final now = DateTime.now();
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

    return '${months[now.month - 1]} ${now.day}, ${now.year}';
  }
}

class _StatusCard extends StatelessWidget {
  final Attendance? attendance;

  const _StatusCard({required this.attendance});

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final hasCheckedIn = attendance?.checkInServerTime != null;
    final hasCheckedOut = attendance?.checkOutServerTime != null;
    final statusText = !hasCheckedIn
        ? 'Not checked in'
        : hasCheckedOut
        ? 'Checked out'
        : 'Checked in';
    final statusColor = !hasCheckedIn
        ? theme.colorScheme.outline
        : hasCheckedOut
        ? Colors.green
        : theme.colorScheme.primary;

    return Card(
      child: Padding(
        padding: const EdgeInsets.all(20),
        child: Row(
          children: [
            CircleAvatar(
              radius: 28,
              backgroundColor: statusColor.withValues(alpha: 0.12),
              child: Icon(
                hasCheckedOut
                    ? Icons.task_alt
                    : hasCheckedIn
                    ? Icons.login
                    : Icons.schedule,
                color: statusColor,
              ),
            ),
            const SizedBox(width: 16),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    statusText,
                    style: theme.textTheme.titleMedium?.copyWith(
                      fontWeight: FontWeight.w700,
                    ),
                  ),
                  const SizedBox(height: 4),
                  Text(
                    _subtitle(),
                    style: theme.textTheme.bodySmall?.copyWith(
                      color: theme.colorScheme.onSurfaceVariant,
                    ),
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }

  String _subtitle() {
    if (attendance == null) {
      return 'Start your day by checking in.';
    }

    if (attendance!.checkOutServerTime == null) {
      return 'Remember to check out before leaving.';
    }

    return 'Your attendance for today is complete.';
  }
}

class _ActionPanel extends ConsumerWidget {
  final bool canCheckIn;
  final bool canCheckOut;
  final bool isSubmitting;

  const _ActionPanel({
    required this.canCheckIn,
    required this.canCheckOut,
    required this.isSubmitting,
  });

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    return Row(
      children: [
        Expanded(
          child: FilledButton.icon(
            onPressed: canCheckIn && !isSubmitting
                ? () => ref.read(attendanceStateProvider.notifier).checkIn()
                : null,
            icon: const Icon(Icons.login),
            label: Text(
              isSubmitting && canCheckIn ? 'Please wait...' : 'Check In',
            ),
          ),
        ),
        const SizedBox(width: 12),
        Expanded(
          child: FilledButton.tonalIcon(
            onPressed: canCheckOut && !isSubmitting
                ? () => ref.read(attendanceStateProvider.notifier).checkOut()
                : null,
            icon: const Icon(Icons.logout),
            label: Text(
              isSubmitting && canCheckOut ? 'Please wait...' : 'Check Out',
            ),
          ),
        ),
      ],
    );
  }
}

class _AttendanceDetails extends StatelessWidget {
  final Attendance? attendance;

  const _AttendanceDetails({required this.attendance});

  @override
  Widget build(BuildContext context) {
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(20),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              'Today',
              style: Theme.of(
                context,
              ).textTheme.titleMedium?.copyWith(fontWeight: FontWeight.w700),
            ),
            const SizedBox(height: 16),
            _DetailRow(
              label: 'Check in',
              value: _formatTime(attendance?.checkInServerTime),
            ),
            _DetailRow(
              label: 'Check out',
              value: _formatTime(attendance?.checkOutServerTime),
            ),
            _DetailRow(
              label: 'Duration',
              value: _formatDuration(attendance?.workDurationMinutes),
            ),
            _DetailRow(
              label: 'Status',
              value: attendance?.status.toUpperCase() ?? 'NOT RECORDED',
            ),
          ],
        ),
      ),
    );
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
      return attendance?.checkInServerTime == null
          ? 'Not recorded'
          : 'In progress';
    }

    final hours = minutes ~/ 60;
    final remainingMinutes = minutes % 60;

    if (hours == 0) {
      return '${remainingMinutes}m';
    }

    return '${hours}h ${remainingMinutes}m';
  }
}

class _DetailRow extends StatelessWidget {
  final String label;
  final String value;

  const _DetailRow({required this.label, required this.value});

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 8),
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
