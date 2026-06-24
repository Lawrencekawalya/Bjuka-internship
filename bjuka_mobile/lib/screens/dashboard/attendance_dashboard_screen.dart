import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../core/network/wifi_info_service.dart';
import '../../data/models/attendance_model.dart';
import '../../data/models/user_model.dart';
import '../../providers/providers.dart';
import '../../theme/bjuka_brand.dart';
import 'attendance_history_screen.dart';

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
    final wifiState = ref.watch(currentWifiProvider);
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
        title: const Row(
          children: [
            BjukaLogo(width: 32, showText: false),
            SizedBox(width: 10),
            Text('Attendance'),
          ],
        ),
        actions: [
          IconButton(
            tooltip: 'Refresh',
            icon: const Icon(Icons.refresh),
            onPressed: attendanceState.isSubmitting
                ? null
                : _refreshAttendanceAndWifi,
          ),
          PopupMenuButton<_MoreMenuAction>(
            tooltip: 'More',
            icon: const Icon(Icons.more_vert),
            onSelected: (action) {
              switch (action) {
                case _MoreMenuAction.history:
                  Navigator.of(context).push(
                    MaterialPageRoute(
                      builder: (_) => const AttendanceHistoryScreen(),
                    ),
                  );
                case _MoreMenuAction.logout:
                  ref.read(authStateProvider.notifier).logout();
              }
            },
            itemBuilder: (context) => const [
              PopupMenuItem(
                value: _MoreMenuAction.history,
                child: ListTile(
                  leading: Icon(Icons.history),
                  title: Text('History'),
                  contentPadding: EdgeInsets.zero,
                ),
              ),
              PopupMenuItem(
                value: _MoreMenuAction.logout,
                child: ListTile(
                  leading: Icon(Icons.logout),
                  title: Text('Logout'),
                  contentPadding: EdgeInsets.zero,
                ),
              ),
            ],
          ),
        ],
      ),
      body: SafeArea(
        child: attendanceState.isLoading
            ? const Center(child: CircularProgressIndicator())
            : RefreshIndicator(
                onRefresh: _refreshAttendanceAndWifi,
                child: ListView(
                  padding: const EdgeInsets.all(16),
                  children: [
                    _WelcomeHeader(user: authState.user, wifiState: wifiState),
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

  Future<void> _refreshAttendanceAndWifi() async {
    ref.invalidate(currentWifiProvider);
    await ref.read(attendanceStateProvider.notifier).loadToday();
  }
}

enum _MoreMenuAction { history, logout }

class _WelcomeHeader extends StatelessWidget {
  final User? user;
  final AsyncValue<WifiInfo> wifiState;

  const _WelcomeHeader({required this.user, required this.wifiState});

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
          Row(
            children: [
              _ProfilePhoto(user: user),
              const SizedBox(width: 14),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      'Welcome, ${user?.name ?? 'Intern'}',
                      maxLines: 2,
                      overflow: TextOverflow.ellipsis,
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
              ),
            ],
          ),
          const SizedBox(height: 16),
          _WifiStatusPill(wifiState: wifiState),
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

class _ProfilePhoto extends StatelessWidget {
  final User? user;

  const _ProfilePhoto({required this.user});

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final avatarUrl = user?.avatarUrl;

    return CircleAvatar(
      radius: 28,
      backgroundColor: theme.colorScheme.surface.withValues(alpha: 0.65),
      foregroundImage: avatarUrl == null || avatarUrl.isEmpty
          ? null
          : NetworkImage(avatarUrl),
      child: Text(
        _initials(user?.name ?? 'Intern'),
        style: theme.textTheme.titleMedium?.copyWith(
          color: theme.colorScheme.onPrimaryContainer,
          fontWeight: FontWeight.w800,
        ),
      ),
    );
  }

  String _initials(String value) {
    final parts = value
        .trim()
        .split(RegExp(r'\s+'))
        .where((part) => part.isNotEmpty)
        .take(2)
        .toList();

    if (parts.isEmpty) {
      return 'I';
    }

    return parts.map((part) => part[0].toUpperCase()).join();
  }
}

class _WifiStatusPill extends StatelessWidget {
  final AsyncValue<WifiInfo> wifiState;

  const _WifiStatusPill({required this.wifiState});

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final foreground = theme.colorScheme.onPrimaryContainer;

    final Widget leading;
    final String title;
    final String subtitle;
    final Color indicatorColor;

    switch (wifiState) {
      case AsyncData(:final value):
        leading = const Icon(Icons.wifi, size: 20);
        title = 'Wi-Fi connected';
        subtitle = value.ssid;
        indicatorColor = Colors.green;
      case AsyncError():
        leading = const Icon(Icons.wifi_off, size: 20);
        title = 'Wi-Fi unavailable';
        subtitle = 'Not connected or permission needed';
        indicatorColor = theme.colorScheme.error;
      default:
        leading = SizedBox(
          width: 18,
          height: 18,
          child: CircularProgressIndicator(strokeWidth: 2, color: foreground),
        );
        title = 'Checking Wi-Fi';
        subtitle = 'Detecting connected network';
        indicatorColor = theme.colorScheme.outline;
    }

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
      decoration: BoxDecoration(
        color: theme.colorScheme.surface.withValues(alpha: 0.55),
        borderRadius: BorderRadius.circular(12),
      ),
      child: Row(
        children: [
          IconTheme(
            data: IconThemeData(color: foreground),
            child: leading,
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  title,
                  style: theme.textTheme.bodyMedium?.copyWith(
                    color: foreground,
                    fontWeight: FontWeight.w700,
                  ),
                ),
                const SizedBox(height: 2),
                Text(
                  subtitle,
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                  style: theme.textTheme.bodySmall?.copyWith(
                    color: foreground.withValues(alpha: 0.82),
                  ),
                ),
              ],
            ),
          ),
          const SizedBox(width: 12),
          Container(
            width: 10,
            height: 10,
            decoration: BoxDecoration(
              color: indicatorColor,
              shape: BoxShape.circle,
            ),
          ),
        ],
      ),
    );
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
                ? () async {
                    final activities = await _showActivitiesDialog(context);
                    if (activities == null) {
                      return;
                    }

                    ref
                        .read(attendanceStateProvider.notifier)
                        .checkOut(activities: activities);
                  }
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

  Future<String?> _showActivitiesDialog(BuildContext context) {
    return showDialog<String>(
      context: context,
      builder: (context) => const _ActivitiesDialog(),
    );
  }
}

class _ActivitiesDialog extends StatefulWidget {
  const _ActivitiesDialog();

  @override
  State<_ActivitiesDialog> createState() => _ActivitiesDialogState();
}

class _ActivitiesDialogState extends State<_ActivitiesDialog> {
  final TextEditingController _controller = TextEditingController();

  @override
  void dispose() {
    _controller.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final activities = _controller.text.trim();
    final wordCount = _wordCount(activities);
    final canSubmit = activities.isNotEmpty && wordCount <= 70;

    return AlertDialog(
      title: const Text('Today\'s activities'),
      content: Column(
        mainAxisSize: MainAxisSize.min,
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          TextField(
            controller: _controller,
            autofocus: true,
            minLines: 4,
            maxLines: 6,
            maxLength: 500,
            textInputAction: TextInputAction.newline,
            decoration: const InputDecoration(
              hintText: 'Summarize what you worked on today.',
              border: OutlineInputBorder(),
            ),
            onChanged: (_) => setState(() {}),
          ),
          Text(
            '$wordCount / 70 words',
            style: Theme.of(context).textTheme.bodySmall?.copyWith(
              color: wordCount > 70
                  ? Theme.of(context).colorScheme.error
                  : Theme.of(context).colorScheme.onSurfaceVariant,
            ),
          ),
        ],
      ),
      actions: [
        TextButton(
          onPressed: () => Navigator.of(context).pop(),
          child: const Text('Cancel'),
        ),
        FilledButton(
          onPressed: canSubmit
              ? () => Navigator.of(context).pop(activities)
              : null,
          child: const Text('Check Out'),
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
            if (attendance?.dailyActivities != null) ...[
              const SizedBox(height: 12),
              Text(
                'Activities',
                style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                  color: Theme.of(context).colorScheme.onSurfaceVariant,
                ),
              ),
              const SizedBox(height: 6),
              Text(
                attendance!.dailyActivities!,
                style: Theme.of(
                  context,
                ).textTheme.bodyMedium?.copyWith(fontWeight: FontWeight.w600),
              ),
            ],
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

int _wordCount(String value) {
  return RegExp(r'\S+').allMatches(value.trim()).length;
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
