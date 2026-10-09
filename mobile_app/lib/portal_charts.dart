import 'dart:math' as math;

import 'package:flutter/material.dart';

class TrendChart extends StatelessWidget {
  final List<String> labels;
  final List<int> values;
  const TrendChart({super.key, required this.labels, required this.values});
  @override
  Widget build(BuildContext context) => values.isEmpty
      ? const Padding(padding: EdgeInsets.all(24), child: Text('No attendance data yet.'))
      : Semantics(label: List.generate(labels.length, (i) => '${labels[i]}: ${values[i]} present').join(', '), child: SizedBox(height: 220, width: double.infinity, child: CustomPaint(painter: _TrendPainter(labels, values, Theme.of(context).colorScheme.primary, Theme.of(context).colorScheme.onSurface))));
}

class _TrendPainter extends CustomPainter {
  final List<String> labels;
  final List<int> values;
  final Color color, textColor;
  _TrendPainter(this.labels, this.values, this.color, this.textColor);

  void label(Canvas canvas, String text, Offset offset, {double width = 45}) {
    final painter = TextPainter(text: TextSpan(text: text, style: TextStyle(color: textColor, fontSize: 10)), textDirection: TextDirection.ltr, textAlign: TextAlign.center)..layout(maxWidth: width);
    painter.paint(canvas, Offset(offset.dx - painter.width / 2, offset.dy));
  }

  @override
  void paint(Canvas canvas, Size size) {
    final plot = Rect.fromLTRB(26, 20, size.width - 16, size.height - 35);
    final maxValue = math.max(1, values.reduce(math.max));
    final grid = Paint()..color = textColor.withValues(alpha: 0.15)..strokeWidth = 1;
    for (var i = 0; i <= 4; i++) {
      final y = plot.bottom - plot.height * i / 4;
      canvas.drawLine(Offset(plot.left, y), Offset(plot.right, y), grid);
      label(canvas, '${(maxValue * i / 4).round()}', Offset(10, y - 5), width: 24);
    }
    final points = List.generate(values.length, (i) => Offset(values.length == 1 ? plot.center.dx : plot.left + plot.width * i / (values.length - 1), plot.bottom - plot.height * values[i] / maxValue));
    final path = Path()..moveTo(points.first.dx, points.first.dy);
    for (final point in points.skip(1)) { path.lineTo(point.dx, point.dy); }
    canvas.drawPath(path, Paint()..color = color..strokeWidth = 3..style = PaintingStyle.stroke);
    for (var i = 0; i < points.length; i++) {
      canvas.drawCircle(points[i], 4, Paint()..color = color);
      label(canvas, labels[i].length > 7 ? labels[i].substring(0, 7) : labels[i], Offset(points[i].dx, plot.bottom + 10), width: 44);
    }
  }

  @override
  bool shouldRepaint(covariant _TrendPainter old) => old.values != values || old.labels != labels || old.color != color || old.textColor != textColor;
}

const chartColors = [Color(0xFFC9A227), Color(0xFF5D9772), Color(0xFF729BB8), Color(0xFFB78098), Color(0xFFB88D62), Color(0xFF9382BD)];

class CategoryChart extends StatelessWidget {
  final Map<String, int> categories;
  const CategoryChart({super.key, required this.categories});
  @override
  Widget build(BuildContext context) {
    if (categories.isEmpty) return const Padding(padding: EdgeInsets.all(24), child: Text('No category data yet.'));
    final entries = categories.entries.toList();
    return Column(children: [
      SizedBox(width: 155, height: 155, child: CustomPaint(painter: _DonutPainter(entries.map((e) => e.value).toList()))),
      const SizedBox(height: 15),
      ...List.generate(entries.length, (i) => Padding(padding: const EdgeInsets.symmetric(vertical: 4), child: Row(children: [Container(width: 10, height: 10, color: chartColors[i % chartColors.length]), const SizedBox(width: 9), Expanded(child: Text(entries[i].key)), Text('${entries[i].value}')]))),
    ]);
  }
}

class _DonutPainter extends CustomPainter {
  final List<int> values;
  _DonutPainter(this.values);
  @override
  void paint(Canvas canvas, Size size) {
    final sum = values.fold<int>(0, (a, b) => a + b);
    if (sum == 0) return;
    var start = -math.pi / 2;
    final rect = Rect.fromLTWH(15, 15, size.width - 30, size.height - 30);
    for (var i = 0; i < values.length; i++) {
      final sweep = 2 * math.pi * values[i] / sum;
      canvas.drawArc(rect, start, sweep, false, Paint()..color = chartColors[i % chartColors.length]..style = PaintingStyle.stroke..strokeWidth = 25);
      start += sweep;
    }
  }
  @override
  bool shouldRepaint(covariant _DonutPainter old) => old.values != values;
}
