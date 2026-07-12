import 'dart:math' as math;

import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter/services.dart';
import '../../core/network/wifi_info_service.dart';
import '../../data/models/attendance_model.dart';
import '../../data/models/report_model.dart';
import '../../data/models/user_model.dart';
import '../../providers/providers.dart';
import '../../providers/report_provider.dart';
import '../../theme/bjuka_brand.dart';
import 'attendance_history_screen.dart';
import 'intern_program_screen.dart';
import 'working_hours_screen.dart';

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
    Future.microtask(() async {
      await ref.read(attendanceStateProvider.notifier).loadToday();
      await ref.read(reportStateProvider.notifier).loadStatus();
    });
  }

  @override
  Widget build(BuildContext context) {
    final authState = ref.watch(authStateProvider);
    final attendanceState = ref.watch(attendanceStateProvider);
    final reportState = ref.watch(reportStateProvider);
    final wifiState = ref.watch(currentWifiProvider);
    final theme = Theme.of(context);
    final hasCompletedInternship =
        attendanceState.batchProgressPercentage >= 100;

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

    ref.listen(reportStateProvider, (previous, next) {
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
                case _MoreMenuAction.program:
                  Navigator.of(context).push(
                    MaterialPageRoute(
                      builder: (_) => const InternProgramScreen(),
                    ),
                  );
                case _MoreMenuAction.workingHours:
                  Navigator.of(context).push(
                    MaterialPageRoute(
                      builder: (_) => const WorkingHoursScreen(),
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
                value: _MoreMenuAction.program,
                child: ListTile(
                  leading: Icon(Icons.school),
                  title: Text('Intern Program'),
                  contentPadding: EdgeInsets.zero,
                ),
              ),
              PopupMenuItem(
                value: _MoreMenuAction.workingHours,
                child: ListTile(
                  leading: Icon(Icons.schedule),
                  title: Text('Working Hours'),
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
        child: Stack(
          children: [
            attendanceState.isLoading
                ? const Center(child: CircularProgressIndicator())
                : RefreshIndicator(
                    onRefresh: _refreshAttendanceAndWifi,
                    child: ListView(
                      padding: const EdgeInsets.all(16),
                      children: [
                        _WelcomeHeader(
                          user: authState.user,
                          wifiState: wifiState,
                        ),
                        const SizedBox(height: 16),
                        _BatchProgressCard(
                          percentage: attendanceState.batchProgressPercentage,
                        ),
                        const SizedBox(height: 16),
                        _AttendanceRateCard(
                          summary: attendanceState.attendanceSummary,
                          hasCompletedInternship: hasCompletedInternship,
                        ),
                        const SizedBox(height: 16),
                        if (attendanceState.attendanceUnavailableMessage !=
                            null) ...[
                          _AttendanceUnavailableCard(
                            message:
                                attendanceState.attendanceUnavailableMessage!,
                          ),
                          const SizedBox(height: 16),
                        ],
                        if (hasCompletedInternship)
                          Column(
                            children: [
                              _InternshipCompletionCard(
                                user: authState.user,
                                certificateDownloadUrl:
                                    attendanceState.certificateDownloadUrl,
                              ),
                              const SizedBox(height: 16),
                              _ReportDraftCard(reportState: reportState),
                            ],
                          )
                        else ...[
                          _StatusCard(attendance: attendanceState.attendance),
                          const SizedBox(height: 16),
                          _ActionPanel(
                            canCheckIn: attendanceState.canCheckIn,
                            canCheckOut: attendanceState.canCheckOut,
                            isSubmitting: attendanceState.isSubmitting,
                          ),
                          const SizedBox(height: 16),
                          _AttendanceDetails(
                            attendance: attendanceState.attendance,
                          ),
                        ],
                      ],
                    ),
                  ),
            if (hasCompletedInternship) const _FullScreenParticles(),
          ],
        ),
      ),
    );
  }

  Future<void> _refreshAttendanceAndWifi() async {
    ref.invalidate(currentWifiProvider);
    await ref.read(attendanceStateProvider.notifier).loadToday();
    await ref.read(reportStateProvider.notifier).loadStatus();
  }
}

class _FullScreenParticles extends StatefulWidget {
  const _FullScreenParticles();

  @override
  State<_FullScreenParticles> createState() => _FullScreenParticlesState();
}

class _FullScreenParticlesState extends State<_FullScreenParticles>
    with SingleTickerProviderStateMixin {
  static const double _height = 380;

  late final AnimationController _controller;

  @override
  void initState() {
    super.initState();
    _controller = AnimationController(
      vsync: this,
      duration: const Duration(milliseconds: 2800),
    )..repeat();
  }

  @override
  void dispose() {
    _controller.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final colorScheme = Theme.of(context).colorScheme;

    return Positioned(
      top: 0,
      left: 0,
      right: 0,
      height: _height,
      child: IgnorePointer(
        child: AnimatedBuilder(
          animation: _controller,
          builder: (context, _) {
            return CustomPaint(
              painter: _FallingParticlesPainter(
                progress: _controller.value,
                colorScheme: colorScheme,
              ),
            );
          },
        ),
      ),
    );
  }
}

enum _MoreMenuAction { history, program, workingHours, logout }

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

class _BatchProgressCard extends StatelessWidget {
  final int percentage;

  const _BatchProgressCard({required this.percentage});

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final clampedPercentage = percentage.clamp(0, 100);
    final progressColor = _progressColor(clampedPercentage, theme);

    return Card(
      child: Padding(
        padding: const EdgeInsets.all(20),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                CircleAvatar(
                  radius: 24,
                  backgroundColor: progressColor.withValues(alpha: 0.12),
                  child: Icon(Icons.timeline, color: progressColor),
                ),
                const SizedBox(width: 14),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        'Internship progress',
                        style: theme.textTheme.titleMedium?.copyWith(
                          fontWeight: FontWeight.w700,
                        ),
                      ),
                      const SizedBox(height: 4),
                      Text(
                        _progressLabel(clampedPercentage),
                        style: theme.textTheme.bodySmall?.copyWith(
                          color: theme.colorScheme.onSurfaceVariant,
                        ),
                      ),
                    ],
                  ),
                ),
                Text(
                  '$clampedPercentage%',
                  style: theme.textTheme.headlineSmall?.copyWith(
                    color: progressColor,
                    fontWeight: FontWeight.w800,
                  ),
                ),
              ],
            ),
            const SizedBox(height: 16),
            ClipRRect(
              borderRadius: BorderRadius.circular(999),
              child: LinearProgressIndicator(
                value: clampedPercentage / 100,
                minHeight: 8,
                color: progressColor,
                backgroundColor: theme.colorScheme.surfaceContainerHighest,
              ),
            ),
          ],
        ),
      ),
    );
  }

  Color _progressColor(int percentage, ThemeData theme) {
    if (percentage < 35) {
      return Colors.orange;
    }

    if (percentage < 70) {
      return theme.colorScheme.primary;
    }

    return Colors.green;
  }

  String _progressLabel(int percentage) {
    if (percentage < 35) {
      return 'The batch is in its early stage.';
    }

    if (percentage < 70) {
      return 'The batch is around the middle of its period.';
    }

    if (percentage < 100) {
      return 'The batch is approaching completion.';
    }

    return 'The batch period is complete.';
  }
}

class _AttendanceRateCard extends StatelessWidget {
  final AttendanceSummary summary;
  final bool hasCompletedInternship;

  const _AttendanceRateCard({
    required this.summary,
    required this.hasCompletedInternship,
  });

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final rate = summary.attendanceRate.clamp(0, 100).toInt();
    final rateColor = _rateColor(rate, theme);
    final notAttendedDays = (summary.expectedDays - summary.daysAttended)
        .clamp(0, summary.expectedDays)
        .toInt();

    return Card(
      child: Padding(
        padding: const EdgeInsets.all(20),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                CircleAvatar(
                  radius: 24,
                  backgroundColor: rateColor.withValues(alpha: 0.12),
                  child: Icon(Icons.fact_check, color: rateColor),
                ),
                const SizedBox(width: 14),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        'Attendance rate',
                        style: theme.textTheme.titleMedium?.copyWith(
                          fontWeight: FontWeight.w700,
                        ),
                      ),
                      const SizedBox(height: 4),
                      Text(
                        '${summary.daysAttended} of ${summary.expectedDays} expected days',
                        style: theme.textTheme.bodySmall?.copyWith(
                          color: theme.colorScheme.onSurfaceVariant,
                        ),
                      ),
                    ],
                  ),
                ),
                Text(
                  '$rate%',
                  style: theme.textTheme.headlineSmall?.copyWith(
                    color: rateColor,
                    fontWeight: FontWeight.w800,
                  ),
                ),
              ],
            ),
            const SizedBox(height: 16),
            ClipRRect(
              borderRadius: BorderRadius.circular(999),
              child: LinearProgressIndicator(
                value: rate / 100,
                minHeight: 8,
                color: rateColor,
                backgroundColor: theme.colorScheme.surfaceContainerHighest,
              ),
            ),
            const SizedBox(height: 14),
            Wrap(
              spacing: 10,
              runSpacing: 10,
              children: [
                _SummaryPill(
                  icon: Icons.event_available,
                  label: '${summary.daysAttended} attended',
                ),
                _SummaryPill(
                  icon: hasCompletedInternship
                      ? Icons.event_busy
                      : Icons.calendar_month,
                  label: hasCompletedInternship
                      ? '$notAttendedDays days not attended'
                      : '${summary.remainingDays} remaining',
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }

  Color _rateColor(int rate, ThemeData theme) {
    if (rate < 60) {
      return theme.colorScheme.error;
    }

    if (rate < 80) {
      return Colors.orange;
    }

    return Colors.green;
  }
}

class _SummaryPill extends StatelessWidget {
  final IconData icon;
  final String label;

  const _SummaryPill({required this.icon, required this.label});

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
      decoration: BoxDecoration(
        color: theme.colorScheme.surfaceContainerHighest,
        borderRadius: BorderRadius.circular(999),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(icon, size: 16, color: theme.colorScheme.onSurfaceVariant),
          const SizedBox(width: 6),
          Text(
            label,
            style: theme.textTheme.bodySmall?.copyWith(
              color: theme.colorScheme.onSurfaceVariant,
              fontWeight: FontWeight.w600,
            ),
          ),
        ],
      ),
    );
  }
}

class _InternshipCompletionCard extends StatefulWidget {
  final User? user;
  final String? certificateDownloadUrl;

  const _InternshipCompletionCard({
    required this.user,
    required this.certificateDownloadUrl,
  });

  @override
  State<_InternshipCompletionCard> createState() =>
      _InternshipCompletionCardState();
}

class _InternshipCompletionCardState extends State<_InternshipCompletionCard>
    with SingleTickerProviderStateMixin {
  late final AnimationController _controller;

  @override
  void initState() {
    super.initState();
    _controller = AnimationController(
      vsync: this,
      duration: const Duration(milliseconds: 2800),
    )..repeat();
  }

  @override
  void dispose() {
    _controller.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final colorScheme = theme.colorScheme;

    return Card(
      clipBehavior: Clip.antiAlias,
      child: SizedBox(
        height: 310,
        child: Stack(
          children: [
            Positioned.fill(
              child: DecoratedBox(
                decoration: BoxDecoration(
                  color: colorScheme.primaryContainer.withValues(alpha: 0.45),
                ),
              ),
            ),
            Padding(
              padding: const EdgeInsets.all(22),
              child: Column(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  Center(
                    child: AnimatedBuilder(
                      animation: _controller,
                      builder: (context, child) {
                        final pulse =
                            1 +
                            (math.sin(_controller.value * math.pi * 2) * 0.04);

                        return Transform.scale(scale: pulse, child: child);
                      },
                      child: Container(
                        width: 92,
                        height: 92,
                        decoration: BoxDecoration(
                          shape: BoxShape.circle,
                          color: Colors.green.withValues(alpha: 0.16),
                          border: Border.all(color: Colors.green, width: 2),
                          boxShadow: [
                            BoxShadow(
                              color: Colors.green.withValues(alpha: 0.22),
                              blurRadius: 28,
                              spreadRadius: 6,
                            ),
                          ],
                        ),
                        child: Stack(
                          alignment: Alignment.center,
                          children: [
                            const Icon(
                              Icons.workspace_premium,
                              color: Colors.green,
                              size: 48,
                            ),
                            Positioned(
                              top: 15,
                              right: 17,
                              child: _SparkleDot(
                                animation: _controller,
                                delay: 0.0,
                              ),
                            ),
                            Positioned(
                              left: 17,
                              bottom: 19,
                              child: _SparkleDot(
                                animation: _controller,
                                delay: 0.45,
                              ),
                            ),
                          ],
                        ),
                      ),
                    ),
                  ),
                  const SizedBox(height: 18),
                  Text(
                    'Congratulations${_firstName(widget.user)}',
                    textAlign: TextAlign.center,
                    style: theme.textTheme.headlineSmall?.copyWith(
                      fontWeight: FontWeight.w800,
                      color: colorScheme.onSurface,
                    ),
                  ),
                  const SizedBox(height: 8),
                  Text(
                    'You have completed your internship with us.',
                    textAlign: TextAlign.center,
                    style: theme.textTheme.bodyMedium?.copyWith(
                      color: colorScheme.onSurfaceVariant,
                    ),
                  ),
                  const SizedBox(height: 18),
                  FilledButton.icon(
                    onPressed: () => _showCertificateActions(context),
                    icon: const Icon(Icons.download),
                    label: const Text('Get your certificate'),
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }

  String _firstName(User? user) {
    final name = user?.name.trim() ?? '';

    if (name.isEmpty) {
      return '';
    }

    return ', ${name.split(RegExp(r'\s+')).first}';
  }

  Future<void> _showCertificateActions(BuildContext context) async {
    final url = widget.certificateDownloadUrl;
    final messenger = ScaffoldMessenger.of(context);

    if (url == null || url.isEmpty) {
      messenger.showSnackBar(
        const SnackBar(content: Text('Your certificate is not available yet.')),
      );

      return;
    }

    final action = await showModalBottomSheet<_CertificateAction>(
      context: context,
      showDragHandle: true,
      builder: (context) {
        return SafeArea(
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              ListTile(
                leading: const Icon(Icons.visibility),
                title: const Text('View Certificate'),
                onTap: () => Navigator.of(context).pop(_CertificateAction.view),
              ),
              ListTile(
                leading: const Icon(Icons.download),
                title: const Text('Download Certificate'),
                subtitle: Text(_certificateFileName()),
                onTap: () =>
                    Navigator.of(context).pop(_CertificateAction.download),
              ),
              ListTile(
                leading: const Icon(Icons.share),
                title: const Text('Share Certificate'),
                onTap: () =>
                    Navigator.of(context).pop(_CertificateAction.share),
              ),
            ],
          ),
        );
      },
    );

    if (action == null) {
      return;
    }

    try {
      switch (action) {
        case _CertificateAction.view:
          await _certificateChannel.invokeMethod<void>('openCertificate', {
            'url': url,
          });
        case _CertificateAction.download:
          await _certificateChannel.invokeMethod<void>('downloadCertificate', {
            'url': url,
            'fileName': _certificateFileName(),
          });
          messenger.showSnackBar(
            SnackBar(content: Text('Downloading ${_certificateFileName()}')),
          );
        case _CertificateAction.share:
          await _certificateChannel.invokeMethod<void>('shareCertificate', {
            'url': url,
          });
      }
    } on PlatformException {
      messenger.showSnackBar(
        const SnackBar(
          content: Text('Could not handle the certificate. Please try again.'),
        ),
      );
    }
  }

  String _certificateFileName() {
    final rawName = widget.user?.name.trim() ?? 'Intern';
    final safeName = rawName
        .replaceAll(RegExp(r'[^A-Za-z0-9]+'), '_')
        .replaceAll(RegExp(r'_+'), '_')
        .replaceAll(RegExp(r'^_|_$'), '');
    final extension = _certificateExtension();

    return 'BJUKA_Certificate_${safeName.isEmpty ? 'Intern' : safeName}.$extension';
  }

  String _certificateExtension() {
    final path = Uri.tryParse(widget.certificateDownloadUrl ?? '')?.path ?? '';
    final extensionMatch = RegExp(r'\.([A-Za-z0-9]+)$').firstMatch(path);
    final extension = extensionMatch?.group(1)?.toLowerCase();

    if (extension == 'jpg' || extension == 'jpeg' || extension == 'png') {
      return extension!;
    }

    return 'pdf';
  }
}

const MethodChannel _certificateChannel = MethodChannel(
  'com.bjuka/certificate',
);

enum _CertificateAction { view, download, share }

class _ReportDraftCard extends ConsumerWidget {
  final ReportState reportState;

  const _ReportDraftCard({required this.reportState});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final theme = Theme.of(context);
    final availability = reportState.availability;
    final quota = availability?.quota;
    final latestReport = availability?.latestReport;
    final canGenerate =
        availability?.available == true &&
        quota?.canGenerate == true &&
        !reportState.isGenerating;
    final canRequestReset =
        availability?.available == true &&
        quota?.canRequestReset == true &&
        !reportState.isRequestingReset;

    if (reportState.isLoading && availability == null) {
      return const Card(
        child: Padding(
          padding: EdgeInsets.all(20),
          child: Center(child: CircularProgressIndicator()),
        ),
      );
    }

    return Card(
      child: Padding(
        padding: const EdgeInsets.all(20),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                CircleAvatar(
                  radius: 24,
                  backgroundColor: theme.colorScheme.primaryContainer,
                  child: Icon(
                    Icons.description,
                    color: theme.colorScheme.onPrimaryContainer,
                  ),
                ),
                const SizedBox(width: 14),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        'Internship report draft',
                        style: theme.textTheme.titleMedium?.copyWith(
                          fontWeight: FontWeight.w800,
                        ),
                      ),
                      const SizedBox(height: 4),
                      Text(
                        _quotaLabel(quota),
                        style: theme.textTheme.bodySmall?.copyWith(
                          color: theme.colorScheme.onSurfaceVariant,
                        ),
                      ),
                    ],
                  ),
                ),
              ],
            ),
            const SizedBox(height: 16),
            if (latestReport != null) ...[
              _ReportActionButton(
                icon: Icons.visibility,
                label: 'Preview draft',
                onPressed: () => _showReportPreview(context, latestReport),
              ),
              const SizedBox(height: 10),
              _ReportActionButton(
                icon: Icons.download,
                label: 'Download Word document',
                onPressed: latestReport.downloadUrl == null
                    ? null
                    : () => _downloadReport(context, latestReport),
              ),
              const SizedBox(height: 12),
            ],
            FilledButton.icon(
              onPressed: canGenerate
                  ? () => ref.read(reportStateProvider.notifier).generate()
                  : null,
              icon: reportState.isGenerating
                  ? const SizedBox(
                      width: 18,
                      height: 18,
                      child: CircularProgressIndicator(strokeWidth: 2),
                    )
                  : const Icon(Icons.auto_awesome),
              label: Text(
                reportState.isGenerating
                    ? 'Generating...'
                    : 'Generate Internship Report',
              ),
            ),
            if (canRequestReset) ...[
              const SizedBox(height: 10),
              OutlinedButton.icon(
                onPressed: () =>
                    ref.read(reportStateProvider.notifier).requestReset(),
                icon: const Icon(Icons.lock_reset),
                label: const Text('Request reset from admin'),
              ),
            ] else if (quota?.resetRequested == true) ...[
              const SizedBox(height: 10),
              _ReportNotice(
                icon: Icons.hourglass_top,
                text: 'Reset request pending admin approval.',
              ),
            ] else if (quota?.permanentlyLocked == true) ...[
              const SizedBox(height: 10),
              _ReportNotice(
                icon: Icons.lock,
                text: 'Report generation is permanently locked.',
              ),
            ] else if (quota != null && !quota.canGenerate) ...[
              const SizedBox(height: 10),
              _ReportNotice(
                icon: Icons.block,
                text: quota.resetUsed
                    ? 'Final report generation attempts have been used.'
                    : 'Generation attempts used. Request a reset from admin.',
              ),
            ],
          ],
        ),
      ),
    );
  }

  String _quotaLabel(ReportQuota? quota) {
    if (quota == null) {
      return 'Report generation status unavailable.';
    }

    return '${quota.remainingGenerations} of ${quota.generationLimit} generations remaining';
  }

  Future<void> _downloadReport(
    BuildContext context,
    GeneratedReport report,
  ) async {
    final messenger = ScaffoldMessenger.of(context);

    try {
      await _certificateChannel.invokeMethod<void>('downloadCertificate', {
        'url': report.downloadUrl,
        'fileName': 'BJUKA_Internship_Report_${report.id}.docx',
      });
      messenger.showSnackBar(
        const SnackBar(content: Text('Downloading report document')),
      );
    } on PlatformException {
      messenger.showSnackBar(
        const SnackBar(content: Text('Could not download the report.')),
      );
    }
  }

  Future<void> _showReportPreview(
    BuildContext context,
    GeneratedReport report,
  ) async {
    final content = report.content;

    if (content == null) {
      return;
    }

    await showModalBottomSheet<void>(
      context: context,
      showDragHandle: true,
      isScrollControlled: true,
      builder: (context) {
        return DraggableScrollableSheet(
          expand: false,
          initialChildSize: 0.82,
          minChildSize: 0.45,
          maxChildSize: 0.95,
          builder: (context, scrollController) {
            return ListView(
              controller: scrollController,
              padding: const EdgeInsets.fromLTRB(20, 4, 20, 24),
              children: [
                Text(
                  content.title,
                  style: Theme.of(
                    context,
                  ).textTheme.titleLarge?.copyWith(fontWeight: FontWeight.w800),
                ),
                const SizedBox(height: 16),
                for (final section in content.sections) ...[
                  Text(
                    section.heading,
                    style: Theme.of(context).textTheme.titleMedium?.copyWith(
                      fontWeight: FontWeight.w800,
                    ),
                  ),
                  const SizedBox(height: 6),
                  for (final paragraph in section.paragraphs) ...[
                    Text(paragraph),
                    const SizedBox(height: 8),
                  ],
                  for (final bulletPoint in section.bulletPoints)
                    Padding(
                      padding: const EdgeInsets.only(bottom: 6),
                      child: Row(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          const Text('- '),
                          Expanded(child: Text(bulletPoint)),
                        ],
                      ),
                    ),
                  for (final placeholder in section.imagePlaceholders)
                    Padding(
                      padding: const EdgeInsets.only(top: 8),
                      child: Text(
                        '[Insert image: $placeholder]',
                        style: Theme.of(context).textTheme.bodySmall?.copyWith(
                          fontStyle: FontStyle.italic,
                          color: Theme.of(context).colorScheme.primary,
                        ),
                      ),
                    ),
                  const SizedBox(height: 16),
                ],
              ],
            );
          },
        );
      },
    );
  }
}

class _ReportActionButton extends StatelessWidget {
  final IconData icon;
  final String label;
  final VoidCallback? onPressed;

  const _ReportActionButton({
    required this.icon,
    required this.label,
    required this.onPressed,
  });

  @override
  Widget build(BuildContext context) {
    return SizedBox(
      width: double.infinity,
      child: OutlinedButton.icon(
        onPressed: onPressed,
        icon: Icon(icon),
        label: Text(label),
      ),
    );
  }
}

class _ReportNotice extends StatelessWidget {
  final IconData icon;
  final String text;

  const _ReportNotice({required this.icon, required this.text});

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    return Row(
      children: [
        Icon(icon, size: 18, color: theme.colorScheme.onSurfaceVariant),
        const SizedBox(width: 8),
        Expanded(
          child: Text(
            text,
            style: theme.textTheme.bodySmall?.copyWith(
              color: theme.colorScheme.onSurfaceVariant,
              fontWeight: FontWeight.w600,
            ),
          ),
        ),
      ],
    );
  }
}

class _SparkleDot extends StatelessWidget {
  final Animation<double> animation;
  final double delay;

  const _SparkleDot({required this.animation, required this.delay});

  @override
  Widget build(BuildContext context) {
    return AnimatedBuilder(
      animation: animation,
      builder: (context, _) {
        final value = (animation.value + delay) % 1;
        final opacity = 0.35 + (math.sin(value * math.pi * 2).abs() * 0.65);
        final size = 5 + (math.sin(value * math.pi * 2).abs() * 5);

        return Container(
          width: size,
          height: size,
          decoration: BoxDecoration(
            shape: BoxShape.circle,
            color: Colors.amber.withValues(alpha: opacity),
          ),
        );
      },
    );
  }
}

class _FallingParticlesPainter extends CustomPainter {
  final double progress;
  final ColorScheme colorScheme;

  _FallingParticlesPainter({required this.progress, required this.colorScheme});

  static const List<_ParticleSeed> _particles = [
    _ParticleSeed(0.08, 0.02, 0.90, 3.5),
    _ParticleSeed(0.18, 0.34, 0.74, 4.0),
    _ParticleSeed(0.28, 0.18, 1.00, 3.0),
    _ParticleSeed(0.39, 0.52, 0.82, 5.0),
    _ParticleSeed(0.50, 0.09, 0.92, 3.5),
    _ParticleSeed(0.62, 0.43, 0.76, 4.5),
    _ParticleSeed(0.74, 0.25, 0.88, 3.0),
    _ParticleSeed(0.86, 0.57, 0.96, 4.0),
    _ParticleSeed(0.94, 0.12, 0.80, 3.5),
  ];

  @override
  void paint(Canvas canvas, Size size) {
    final colors = [
      Colors.green,
      Colors.amber,
      colorScheme.primary,
      Colors.lightBlue,
    ];

    for (var index = 0; index < _particles.length; index += 1) {
      final seed = _particles[index];
      final fall = (progress * seed.speed + seed.phase) % 1;
      final x =
          (seed.x * size.width) +
          (math.sin((fall + seed.phase) * math.pi * 2) * 14);
      final y = (fall * (size.height + 34)) - 24;
      final paint = Paint()
        ..color = colors[index % colors.length].withValues(alpha: 0.72);

      canvas.save();
      canvas.translate(x, y);
      canvas.rotate((fall + seed.phase) * math.pi * 2);
      canvas.drawRRect(
        RRect.fromRectAndRadius(
          Rect.fromCenter(
            center: Offset.zero,
            width: seed.size * 1.8,
            height: seed.size,
          ),
          const Radius.circular(2),
        ),
        paint,
      );
      canvas.restore();
    }
  }

  @override
  bool shouldRepaint(covariant _FallingParticlesPainter oldDelegate) {
    return oldDelegate.progress != progress ||
        oldDelegate.colorScheme != colorScheme;
  }
}

class _ParticleSeed {
  final double x;
  final double phase;
  final double speed;
  final double size;

  const _ParticleSeed(this.x, this.phase, this.speed, this.size);
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

class _AttendanceUnavailableCard extends StatelessWidget {
  final String message;

  const _AttendanceUnavailableCard({required this.message});

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    return Card(
      color: theme.colorScheme.secondaryContainer.withValues(alpha: 0.45),
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Row(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Icon(
              Icons.lock_clock_outlined,
              color: theme.colorScheme.onSecondaryContainer,
            ),
            const SizedBox(width: 12),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    'Attendance unavailable',
                    style: theme.textTheme.titleSmall?.copyWith(
                      color: theme.colorScheme.onSecondaryContainer,
                      fontWeight: FontWeight.w800,
                    ),
                  ),
                  const SizedBox(height: 4),
                  Text(
                    message,
                    style: theme.textTheme.bodySmall?.copyWith(
                      color: theme.colorScheme.onSecondaryContainer,
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
