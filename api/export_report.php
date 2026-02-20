<?php
/**
 * API: Export Report (PDF, CSV, Excel)
 * AFB Mangaan Attendance System
 */

session_start();
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../functions/report_engine.php';

$format = $_GET['format'] ?? 'csv';
$fromDate = $_GET['from_date'] ?? null;
$toDate = $_GET['to_date'] ?? null;
$eventId = $_GET['event_id'] ?? null;
$category = $_GET['category'] ?? null;

// Get report data
$reportData = getAttendanceReport($eventId, $fromDate, $toDate, $category);

switch ($format) {
    case 'pdf':
        generatePDF($reportData, $fromDate, $toDate);
        break;
        
    case 'xlsx':
        generateExcel($reportData, $fromDate, $toDate);
        break;
        
    case 'csv':
    default:
        generateCSV($reportData, $fromDate, $toDate);
        break;
}

function generateCSV($data, $from, $to) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="attendance_report_' . date('Y-m-d') . '.csv"');
    
    $output = fopen('php://output', 'w');
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // Headers
    fputcsv($output, ['Report Period:', ($from ?: 'All') . ' to ' . ($to ?: 'All')]);
    fputcsv($output, ['Generated:', date('Y-m-d H:i:s')]);
    fputcsv($output, []);
    
    fputcsv($output, ['Member Name', 'Category', 'Contact', 'Email', 'QR Token', 'Event', 'Event Date', 'Type', 'Status', 'Method', 'Log Time']);
    
    foreach ($data as $row) {
        fputcsv($output, [
            $row['fullname'],
            $row['category'],
            $row['contact'],
            $row['email'],
            $row['qr_token'],
            $row['event_name'],
            $row['event_date'],
            $row['event_type'],
            $row['attendance_status'] ?? 'N/A',
            $row['method'] ?? '-',
            $row['log_time'] ?? '-'
        ]);
    }
    
    fclose($output);
    exit;
}

function generatePDF($data, $from, $to) {
    // Check if Dompdf is available
    $autoloadPath = __DIR__ . '/../vendor/autoload.php';
    
    if (!file_exists($autoloadPath)) {
        // Fallback to simple HTML output
        header('Content-Type: text/html');
        echo '<h1>PDF Export Requires Dompdf</h1>';
        echo '<p>Please install dependencies: <code>composer install</code></p>';
        echo '<p>Report data available in CSV format instead.</p>';
        exit;
    }
    
    require_once $autoloadPath;
    
    use Dompdf\Dompdf;
    use Dompdf\Options;
    
    $options = new Options();
    $options->set('isHtml5ParserEnabled', true);
    $options->set('isPhpEnabled', false);
    $options->set('defaultFont', 'DejaVu Sans');
    
    $dompdf = new Dompdf($options);
    
    // Build HTML
    $html = '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <style>
            body { font-family: DejaVu Sans, sans-serif; font-size: 10pt; }
            h1 { color: #6366f1; font-size: 18pt; margin-bottom: 5pt; }
            .subtitle { color: #64748b; font-size: 10pt; margin-bottom: 15pt; }
            table { width: 100%; border-collapse: collapse; margin-top: 10pt; }
            th { background-color: #6366f1; color: white; padding: 8pt; text-align: left; font-size: 9pt; }
            td { padding: 6pt 8pt; border-bottom: 1px solid #e2e8f0; font-size: 9pt; }
            tr:nth-child(even) { background-color: #f8fafc; }
            .status-present { color: #22c55e; font-weight: bold; }
            .status-absent { color: #ef4444; font-weight: bold; }
            .footer { margin-top: 20pt; font-size: 8pt; color: #64748b; text-align: center; }
        </style>
    </head>
    <body>
        <h1>AFB Mangaan Attendance Report</h1>
        <div class="subtitle">
            Period: ' . ($from ?: 'All time') . ' to ' . ($to ?: 'Present') . '<br>
            Generated: ' . date('F d, Y h:i A') . '<br>
            Total Records: ' . count($data) . '
        </div>
        
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Member</th>
                    <th>Category</th>
                    <th>Event</th>
                    <th>Date</th>
                    <th>Status</th>
                    <th>Method</th>
                </tr>
            </thead>
            <tbody>';
    
    foreach ($data as $i => $row) {
        $statusClass = ($row['attendance_status'] === 'Present') ? 'status-present' : 'status-absent';
        $html .= '
                <tr>
                    <td>' . ($i + 1) . '</td>
                    <td>' . htmlspecialchars($row['fullname']) . '</td>
                    <td>' . $row['category'] . '</td>
                    <td>' . htmlspecialchars($row['event_name']) . '</td>
                    <td>' . date('M d, Y', strtotime($row['event_date'])) . '</td>
                    <td class="' . $statusClass . '">' . ($row['attendance_status'] ?? 'N/A') . '</td>
                    <td>' . ($row['method'] ?? '-') . '</td>
                </tr>';
    }
    
    $html .= '
            </tbody>
        </table>
        
        <div class="footer">
            AFB Mangaan Attendance & Analytics System • Confidential Report
        </div>
    </body>
    </html>';
    
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'landscape');
    $dompdf->render();
    
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="attendance_report_' . date('Y-m-d') . '.pdf"');
    
    echo $dompdf->output();
    exit;
}

function generateExcel($data, $from, $to) {
    $autoloadPath = __DIR__ . '/../vendor/autoload.php';
    
    if (!file_exists($autoloadPath)) {
        // Fallback to CSV with .xlsx extension
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="attendance_report_' . date('Y-m-d') . '.xlsx"');
        
        // Actually send CSV data - Excel can open it
        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        fputcsv($output, ['Report Period:', ($from ?: 'All') . ' to ' . ($to ?: 'All')]);
        fputcsv($output, ['Generated:', date('Y-m-d H:i:s')]);
        fputcsv($output, []);
        fputcsv($output, ['Member Name', 'Category', 'Contact', 'Email', 'QR Token', 'Event', 'Event Date', 'Type', 'Status', 'Method', 'Log Time']);
        
        foreach ($data as $row) {
            fputcsv($output, [
                $row['fullname'],
                $row['category'],
                $row['contact'],
                $row['email'],
                $row['qr_token'],
                $row['event_name'],
                $row['event_date'],
                $row['event_type'],
                $row['attendance_status'] ?? 'N/A',
                $row['method'] ?? '-',
                $row['log_time'] ?? '-'
            ]);
        }
        
        fclose($output);
        exit;
    }
    
    require_once $autoloadPath;
    
    use PhpOffice\PhpSpreadsheet\Spreadsheet;
    use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
    use PhpOffice\PhpSpreadsheet\Style\Fill;
    use PhpOffice\PhpSpreadsheet\Style\Font;
    use PhpOffice\PhpSpreadsheet\Style\Alignment;
    
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    
    // Title
    $sheet->setCellValue('A1', 'AFB Mangaan Attendance Report');
    $sheet->mergeCells('A1:K1');
    $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
    $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    
    // Subtitle
    $sheet->setCellValue('A2', 'Period: ' . ($from ?: 'All time') . ' to ' . ($to ?: 'Present'));
    $sheet->setCellValue('A3', 'Generated: ' . date('F d, Y h:i A'));
    $sheet->setCellValue('A4', 'Total Records: ' . count($data));
    
    // Headers
    $headers = ['#', 'Member Name', 'Category', 'Contact', 'Email', 'QR Token', 'Event', 'Event Date', 'Type', 'Status', 'Method'];
    $col = 1;
    foreach ($headers as $header) {
        $sheet->setCellValueByColumnAndRow($col, 6, $header);
        $sheet->getStyleByColumnAndRow($col, 6)->getFont()->setBold(true);
        $sheet->getStyleByColumnAndRow($col, 6)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('6366f1');
        $sheet->getStyleByColumnAndRow($col, 6)->getFont()->getColor()->setRGB('FFFFFF');
        $col++;
    }
    
    // Data
    $row = 7;
    foreach ($data as $i => $record) {
        $sheet->setCellValueByColumnAndRow(1, $row, $i + 1);
        $sheet->setCellValueByColumnAndRow(2, $row, $record['fullname']);
        $sheet->setCellValueByColumnAndRow(3, $row, $record['category']);
        $sheet->setCellValueByColumnAndRow(4, $row, $record['contact']);
        $sheet->setCellValueByColumnAndRow(5, $row, $record['email']);
        $sheet->setCellValueByColumnAndRow(6, $row, $record['qr_token']);
        $sheet->setCellValueByColumnAndRow(7, $row, $record['event_name']);
        $sheet->setCellValueByColumnAndRow(8, $row, $record['event_date']);
        $sheet->setCellValueByColumnAndRow(9, $row, $record['event_type']);
        $sheet->setCellValueByColumnAndRow(10, $row, $record['attendance_status'] ?? 'N/A');
        $sheet->setCellValueByColumnAndRow(11, $row, $record['method'] ?? '-');
        
        // Color code status
        if ($record['attendance_status'] === 'Present') {
            $sheet->getStyleByColumnAndRow(10, $row)->getFont()->getColor()->setRGB('22c55e');
        } elseif ($record['attendance_status'] === 'Absent') {
            $sheet->getStyleByColumnAndRow(10, $row)->getFont()->getColor()->setRGB('ef4444');
        }
        
        $row++;
    }
    
    // Auto-size columns
    foreach (range('A', 'K') as $col) {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }
    
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="attendance_report_' . date('Y-m-d') . '.xlsx"');
    
    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;
}
