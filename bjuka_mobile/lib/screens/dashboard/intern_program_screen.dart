import 'package:flutter/material.dart';

class InternProgramScreen extends StatelessWidget {
  const InternProgramScreen({super.key});

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final today = DateTime.now();

    return Scaffold(
      backgroundColor: theme.colorScheme.surfaceContainerLowest,
      appBar: AppBar(
        title: const Text('Internship Syllabus'),
        backgroundColor: theme.colorScheme.surfaceContainerLowest,
        scrolledUnderElevation: 0,
        centerTitle: false,
      ),
      body: SafeArea(
        child: ListView.builder(
          padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
          itemCount: _programRows.length + 1,
          itemBuilder: (context, index) {
            if (index == 0) {
              return Padding(
                padding: const EdgeInsets.only(left: 4, bottom: 24, top: 8),
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
                      'Internship Program Roadmap',
                      style: theme.textTheme.headlineSmall?.copyWith(
                        fontWeight: FontWeight.w800,
                        color: theme.colorScheme.onSurface,
                        letterSpacing: -0.5,
                      ),
                    ),
                    const SizedBox(height: 6),
                    Text(
                      'A comprehensive 6-week technical curriculum',
                      style: theme.textTheme.bodyMedium?.copyWith(
                        color: theme.colorScheme.onSurfaceVariant,
                      ),
                    ),
                  ],
                ),
              );
            }

            final row = _programRows[index - 1];
            final isCurrent = row.isCurrentWeek(today);

            return _ProgramSyllabusCard(row: row, isCurrent: isCurrent);
          },
        ),
      ),
    );
  }
}

class _ProgramSyllabusCard extends StatelessWidget {
  final _ProgramRow row;
  final bool isCurrent;

  const _ProgramSyllabusCard({required this.row, required this.isCurrent});

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    return Container(
      margin: const EdgeInsets.only(bottom: 16),
      decoration: BoxDecoration(
        color: theme.colorScheme.surface,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(
          color: isCurrent
              ? theme.colorScheme.primary.withValues(alpha: 0.5)
              : theme.colorScheme.outlineVariant,
          width: isCurrent ? 2 : 1,
        ),
        boxShadow: [
          BoxShadow(
            color: theme.colorScheme.shadow.withValues(alpha: 0.03),
            blurRadius: 10,
            offset: const Offset(0, 4),
          ),
        ],
      ),
      child: ClipRRect(
        borderRadius: BorderRadius.circular(16),
        child: IntrinsicHeight(
          child: Row(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              // Left Indicator Accent Bar
              Container(
                width: 6,
                color: isCurrent
                    ? theme.colorScheme.primary
                    : theme.colorScheme.outlineVariant,
              ),
              Expanded(
                child: Padding(
                  padding: const EdgeInsets.all(20),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      // Header Row
                      Row(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Expanded(
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Text(
                                  row.week,
                                  style: theme.textTheme.titleMedium?.copyWith(
                                    fontWeight: FontWeight.w800,
                                    color: theme.colorScheme.onSurface,
                                    height: 1.3,
                                  ),
                                ),
                                const SizedBox(height: 4),
                                Text(
                                  row.dateRange,
                                  style: theme.textTheme.bodySmall?.copyWith(
                                    color: theme.colorScheme.onSurfaceVariant,
                                    fontWeight: FontWeight.w600,
                                  ),
                                ),
                              ],
                            ),
                          ),
                          if (isCurrent) ...[
                            const SizedBox(width: 12),
                            Container(
                              padding: const EdgeInsets.symmetric(
                                horizontal: 10,
                                vertical: 4,
                              ),
                              decoration: BoxDecoration(
                                color: theme.colorScheme.primaryContainer,
                                borderRadius: BorderRadius.circular(8),
                              ),
                              child: Text(
                                'ACTIVE',
                                style: theme.textTheme.labelSmall?.copyWith(
                                  color: theme.colorScheme.onPrimaryContainer,
                                  fontWeight: FontWeight.w800,
                                  letterSpacing: 0.5,
                                ),
                              ),
                            ),
                          ],
                        ],
                      ),
                      const SizedBox(height: 16),
                      const Divider(height: 1),
                      const SizedBox(height: 16),

                      // Detailed Information Blocks
                      _SyllabusSection(
                        title: 'Objectives',
                        content: row.objectives,
                        icon: Icons.track_changes_rounded,
                      ),
                      const SizedBox(height: 16),
                      _SyllabusSection(
                        title: 'Topics Covered',
                        content: row.topics,
                        icon: Icons.menu_book_rounded,
                      ),
                      const SizedBox(height: 16),
                      _SyllabusSection(
                        title: 'Practical Activities & Projects',
                        content: row.activities,
                        icon: Icons.assignment_turned_in_rounded,
                        isLast: true,
                      ),
                    ],
                  ),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _SyllabusSection extends StatelessWidget {
  final String title;
  final String content;
  final IconData icon;
  final bool isLast;

  const _SyllabusSection({
    required this.title,
    required this.content,
    required this.icon,
    this.isLast = false,
  });

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Row(
          children: [
            Icon(
              icon,
              size: 16,
              color: theme.colorScheme.primary.withValues(alpha: 0.8),
            ),
            const SizedBox(width: 8),
            Text(
              title.toUpperCase(),
              style: theme.textTheme.labelMedium?.copyWith(
                color: theme.colorScheme.onSurfaceVariant,
                fontWeight: FontWeight.w700,
                letterSpacing: 0.5,
              ),
            ),
          ],
        ),
        const SizedBox(height: 6),
        Padding(
          padding: const EdgeInsets.only(left: 24), // Fixed constructor here
          child: Text(
            content.trim(),
            style: theme.textTheme.bodyMedium?.copyWith(
              color: theme.colorScheme.onSurface,
              height: 1.5,
            ),
          ),
        ),
      ],
    );
  }
}

class _ProgramRow {
  final String week;
  final String dateRange;
  final DateTime startDate;
  final DateTime endDate;
  final String objectives;
  final String topics;
  final String activities;

  _ProgramRow({
    required this.week,
    required this.dateRange,
    required this.startDate,
    required this.endDate,
    required this.objectives,
    required this.topics,
    required this.activities,
  });

  bool isCurrentWeek(DateTime date) {
    final day = DateTime(date.year, date.month, date.day);
    final start = DateTime(startDate.year, startDate.month, startDate.day);
    final end = DateTime(endDate.year, endDate.month, endDate.day);

    return !day.isBefore(start) && !day.isAfter(end);
  }
}

final _programRows = [
  _ProgramRow(
    week: 'Week 1 - Hardware Repair & Maintenance Fundamentals',
    dateRange: '15th to 19th June 2026',
    startDate: DateTime(2026, 6, 15),
    endDate: DateTime(2026, 6, 19),
    objectives:
        '- Understand computer hardware components\n'
        '- Learn computer safety procedures\n'
        '- Perform basic maintenance and troubleshooting',
    topics:
        'Computer architecture\n'
        'Motherboard components\n'
        'CPU and RAM installation\n'
        'HDD vs SSD\n'
        'Power Supply Unit (PSU)\n'
        'Laptop disassembly\n'
        'Desktop assembly\n'
        'BIOS settings\n'
        'Preventive maintenance\n'
        'Diagnosing hardware faults',
    activities:
        'Assemble/disassemble desktop\n'
        'Replace RAM, HDD/SSD & PSU\n'
        'Professional cleaning\n'
        'Troubleshoot boot failures',
  ),
  _ProgramRow(
    week: 'Week 2 - Cabling & Network Installation',
    dateRange: '22nd to 26th June 2026',
    startDate: DateTime(2026, 6, 22),
    endDate: DateTime(2026, 6, 26),
    objectives:
        '- Learn networking basics\n'
        '- Install/configure small office networks',
    topics:
        'Networking fundamentals\n'
        'LAN vs WAN\n'
        'IP Addressing\n'
        'Routers\n'
        'Switches\n'
        'Ethernet standards\n'
        'T568A & T568B\n'
        'Cable testing\n'
        'Printer sharing\n'
        'File sharing\n'
        'Network troubleshooting',
    activities:
        'Terminate Ethernet cables\n'
        'Test cables\n'
        'Configure two PCs\n'
        'Share folders\n'
        'Share printers\n'
        'Configure static IPs',
  ),
  _ProgramRow(
    week: 'Week 3 - Desktop Management',
    dateRange: '29th June 2026 to 03rd July 2026',
    startDate: DateTime(2026, 6, 29),
    endDate: DateTime(2026, 7, 3),
    objectives: 'Learn Windows administration and maintenance',
    topics:
        'Windows installation\n'
        'Driver installation\n'
        'Windows Updates\n'
        'User Accounts\n'
        'Disk Management\n'
        'Device Manager\n'
        'Backup & Restore\n'
        'Antivirus\n'
        'Software installation\n'
        'Performance optimization',
    activities:
        'Install Windows 11\n'
        'Install drivers\n'
        'Partition drive\n'
        'Install Office\n'
        'Create users\n'
        'Optimize startup\n'
        'Configure Local Users and Groups',
  ),
  _ProgramRow(
    week: 'Week 4 - Frontend Development',
    dateRange: '06th to 10th July 2026',
    startDate: DateTime(2026, 7, 6),
    endDate: DateTime(2026, 7, 10),
    objectives: 'Learn how websites are built',
    topics:
        'HTML5\n'
        'CSS3\n'
        'Responsive Design\n'
        'Flexbox\n'
        'CSS Grid\n'
        'JavaScript Basics\n'
        'DOM Manipulation\n'
        'Forms\n'
        'Git Basics',
    activities:
        'Build: Personal Portfolio, Business Website, Contact Form, Product Landing Page\n'
        'Mini Project: B. JUKA Technologies Homepage',
  ),
  _ProgramRow(
    week: 'Week 5 - Backend Development & Introduction to Flutter',
    dateRange: '13th to 17th July 2026',
    startDate: DateTime(2026, 7, 13),
    endDate: DateTime(2026, 7, 17),
    objectives:
        'Consolidate backend web development concepts.\n'
        'Understand the fundamentals of Flutter and cross-platform mobile application development.\n'
        'Learn Flutter project structure, widgets, and navigation.\n'
        'Build a simple mobile application using Flutter.',
    topics:
        'Backend Development Review\n'
        '- JavaScript Functions\n'
        '- CRUD Operations\n'
        '- Browser Local Storage\n'
        '- Database Fundamentals\n'
        '- Web Project Structure\n\n'
        'Introduction to Flutter\n'
        '- What is Flutter?\n'
        '- Installing Flutter & Android Studio\n'
        '- Flutter Project Structure\n'
        '- Dart Basics\n'
        '- Widgets (Stateless & Stateful)\n'
        '- Layouts (Row, Column, Container)\n'
        '- Navigation Between Screens\n'
        '- Forms & User Input',
    activities:
        'Build a Login Screen.\n'
        'Build a Dashboard Screen.\n'
        'Create a Customer Registration Form.\n'
        'Implement Navigation Between Pages.\n'
        'Build a Simple Repair Management Mobile App (UI Only).\n'
        'Run the application on an Android Emulator or Physical Device.',
  ),
  _ProgramRow(
    week: 'Week 6 - CCTV Camera Installation & Final Project',
    dateRange: '20th to 24th July 2026',
    startDate: DateTime(2026, 7, 20),
    endDate: DateTime(2026, 7, 24),
    objectives:
        'Install/configure surveillance systems\n'
        'Integrate previous learning',
    topics:
        'Types of CCTV\n'
        'DVR vs NVR\n'
        'Camera Positioning\n'
        'Cable Routing\n'
        'Power Supply\n'
        'IP Cameras\n'
        'Network Configuration\n'
        'Remote Viewing\n'
        'Maintenance\n'
        'Troubleshooting',
    activities:
        'Install 4 cameras\n'
        'Configure DVR/NVR\n'
        'Remote viewing\n'
        'Test recording\n'
        'Fault diagnosis\n'
        'Final Project: PC repair, Windows install, LAN setup, frontend website, backend repair system, CCTV installation, presentation',
  ),
];
