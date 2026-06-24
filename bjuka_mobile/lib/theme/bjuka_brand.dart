import 'package:flutter/material.dart';
import 'package:flutter/services.dart';

class BjukaBrand {
  static const Color navy = Color(0xFF004D86);
  static const Color blue = Color(0xFF006DAE);
  static const Color cyan = Color(0xFF00A9D8);
  static const Color ink = Color(0xFF0B2438);
  static const Color surface = Color(0xFFF5FAFD);

  static ThemeData lightTheme() {
    final scheme = ColorScheme.fromSeed(
      seedColor: blue,
      primary: blue,
      secondary: cyan,
      surface: surface,
      brightness: Brightness.light,
    );

    return ThemeData(
      colorScheme: scheme,
      scaffoldBackgroundColor: surface,
      useMaterial3: true,
      appBarTheme: const AppBarTheme(
        backgroundColor: Colors.white,
        foregroundColor: ink,
        centerTitle: false,
        elevation: 0,
        systemOverlayStyle: SystemUiOverlayStyle(
          statusBarColor: Colors.white,
          statusBarIconBrightness: Brightness.dark,
          statusBarBrightness: Brightness.light,
          systemNavigationBarColor: Colors.white,
          systemNavigationBarIconBrightness: Brightness.dark,
        ),
      ),
      cardTheme: CardThemeData(
        color: Colors.white,
        elevation: 1,
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
      ),
      filledButtonTheme: FilledButtonThemeData(
        style: FilledButton.styleFrom(
          backgroundColor: blue,
          foregroundColor: Colors.white,
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(10),
          ),
          minimumSize: const Size.fromHeight(48),
        ),
      ),
      inputDecorationTheme: InputDecorationTheme(
        filled: true,
        fillColor: Colors.white,
        border: OutlineInputBorder(borderRadius: BorderRadius.circular(10)),
        focusedBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(10),
          borderSide: const BorderSide(color: blue, width: 1.6),
        ),
      ),
    );
  }
}

class BjukaLogo extends StatelessWidget {
  final double width;
  final bool showText;

  const BjukaLogo({super.key, this.width = 260, this.showText = true});

  @override
  Widget build(BuildContext context) {
    final height = showText ? width * 0.42 : width;

    return SizedBox(
      width: width,
      height: height,
      child: CustomPaint(painter: _BjukaLogoPainter(showText: showText)),
    );
  }
}

class _BjukaLogoPainter extends CustomPainter {
  final bool showText;

  const _BjukaLogoPainter({required this.showText});

  @override
  void paint(Canvas canvas, Size size) {
    final scale = size.width / (showText ? 420 : 160);
    canvas.scale(scale);

    if (showText) {
      _drawFullLogo(canvas);
    } else {
      _drawIcon(canvas);
    }
  }

  void _drawFullLogo(Canvas canvas) {
    _drawCircuit(canvas, const Offset(8, 46), 1);
    _drawWordmark(canvas, const Offset(72, 28), 1);
    _drawCircuit(canvas, const Offset(374, 66), -1);
  }

  void _drawIcon(Canvas canvas) {
    final bg = Paint()
      ..shader = const LinearGradient(
        begin: Alignment.topLeft,
        end: Alignment.bottomRight,
        colors: [BjukaBrand.blue, BjukaBrand.cyan],
      ).createShader(const Rect.fromLTWH(0, 0, 160, 160));

    final rect = RRect.fromRectAndRadius(
      const Rect.fromLTWH(0, 0, 160, 160),
      const Radius.circular(32),
    );
    canvas.drawRRect(rect, bg);

    final text = TextPainter(
      text: const TextSpan(
        text: 'B',
        style: TextStyle(
          color: Colors.white,
          fontSize: 92,
          fontWeight: FontWeight.w900,
          letterSpacing: 0,
        ),
      ),
      textDirection: TextDirection.ltr,
    )..layout();
    text.paint(canvas, Offset((160 - text.width) / 2, 30));

    final dot = Paint()..color = Colors.white;
    canvas.drawCircle(const Offset(108, 110), 9, dot);

    final circuit = Paint()
      ..color = Colors.white.withValues(alpha: 0.88)
      ..strokeWidth = 4
      ..style = PaintingStyle.stroke
      ..strokeCap = StrokeCap.round;

    canvas.drawLine(const Offset(22, 38), const Offset(42, 18), circuit);
    canvas.drawLine(const Offset(22, 64), const Offset(48, 38), circuit);
    canvas.drawLine(const Offset(20, 94), const Offset(46, 120), circuit);
    canvas.drawCircle(const Offset(50, 16), 5, circuit);
    canvas.drawCircle(const Offset(56, 32), 5, circuit);
    canvas.drawCircle(const Offset(52, 126), 5, circuit);
  }

  void _drawWordmark(Canvas canvas, Offset offset, double direction) {
    final gradient = const LinearGradient(
      begin: Alignment.topCenter,
      end: Alignment.bottomCenter,
      colors: [BjukaBrand.navy, BjukaBrand.cyan, BjukaBrand.navy],
      stops: [0, 0.48, 1],
    );

    final main = TextPainter(
      text: TextSpan(
        text: 'B. JUKA',
        style: TextStyle(
          foreground: Paint()
            ..shader = gradient.createShader(
              Rect.fromLTWH(offset.dx, offset.dy, 230, 64),
            ),
          fontSize: 58,
          fontWeight: FontWeight.w900,
          letterSpacing: 0,
        ),
      ),
      textDirection: TextDirection.ltr,
    )..layout();
    main.paint(canvas, offset);

    final sub = TextPainter(
      text: const TextSpan(
        text: 'Technologies',
        style: TextStyle(
          color: BjukaBrand.navy,
          fontSize: 27,
          fontWeight: FontWeight.w700,
          letterSpacing: 0,
        ),
      ),
      textDirection: TextDirection.ltr,
    )..layout();
    sub.paint(canvas, offset + const Offset(86, 58));
  }

  void _drawCircuit(Canvas canvas, Offset origin, double direction) {
    final paint = Paint()
      ..color = BjukaBrand.navy
      ..strokeWidth = 2.6
      ..style = PaintingStyle.stroke
      ..strokeCap = StrokeCap.round
      ..strokeJoin = StrokeJoin.round;

    final paths = <Path>[
      Path()
        ..moveTo(origin.dx, origin.dy)
        ..lineTo(origin.dx + 28 * direction, origin.dy - 28)
        ..lineTo(origin.dx + 70 * direction, origin.dy - 28),
      Path()
        ..moveTo(origin.dx, origin.dy + 24)
        ..lineTo(origin.dx + 24 * direction, origin.dy)
        ..lineTo(origin.dx + 90 * direction, origin.dy),
      Path()
        ..moveTo(origin.dx + 8 * direction, origin.dy + 54)
        ..lineTo(origin.dx + 34 * direction, origin.dy + 24)
        ..lineTo(origin.dx + 56 * direction, origin.dy + 24),
      Path()
        ..moveTo(origin.dx + 28 * direction, origin.dy + 76)
        ..lineTo(origin.dx + 28 * direction, origin.dy + 42)
        ..lineTo(origin.dx + 46 * direction, origin.dy + 42),
    ];

    for (final path in paths) {
      canvas.drawPath(path, paint);
    }

    final nodes = [
      Offset(origin.dx + 70 * direction, origin.dy - 28),
      Offset(origin.dx + 90 * direction, origin.dy),
      Offset(origin.dx + 56 * direction, origin.dy + 24),
      Offset(origin.dx + 28 * direction, origin.dy + 76),
    ];

    for (final node in nodes) {
      canvas.drawCircle(node, 5, paint);
    }
  }

  @override
  bool shouldRepaint(covariant _BjukaLogoPainter oldDelegate) {
    return oldDelegate.showText != showText;
  }
}
