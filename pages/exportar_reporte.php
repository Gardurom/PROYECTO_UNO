<?php
// exportar_reporte.php
require_once 'includes/database.php';
require_once 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

$db = getDB();
$tipo = $_GET['tipo'] ?? 'general';

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Estilos
$headerStyle = [
    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4472C4']],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
];

if ($tipo == 'general') {
    // Reporte General
    $sheet->setTitle('Reporte General');
    $sheet->setCellValue('A1', 'REPORTE GENERAL DE EVALUACIONES');
    $sheet->setCellValue('A2', 'Fecha: ' . date('d/m/Y H:i:s'));
    $sheet->mergeCells('A1:F1');
    $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
    
    // Estadísticas
    $row = 4;
    $stats = [
        'Total Alumnos' => $db->query("SELECT COUNT(*) FROM alumnos WHERE activo = 1")->fetchColumn(),
        'Total Docentes' => $db->query("SELECT COUNT(*) FROM docentes WHERE activo = 1")->fetchColumn(),
        'Total Materias' => $db->query("SELECT COUNT(*) FROM materias WHERE activo = 1")->fetchColumn(),
        'Total Evaluaciones' => $db->query("SELECT COUNT(*) FROM evaluaciones")->fetchColumn(),
        'Promedio Puntualidad' => round($db->query("SELECT AVG(puntualidad_asistencia) FROM evaluaciones")->fetchColumn(), 2),
        'Promedio Resolución' => round($db->query("SELECT AVG(resolvio_dudas) FROM evaluaciones")->fetchColumn(), 2)
    ];
    
    foreach ($stats as $label => $value) {
        $sheet->setCellValue('A' . $row, $label . ':');
        $sheet->setCellValue('B' . $row, $value);
        $row++;
    }
    
} elseif ($tipo == 'docentes') {
    // Reporte por Docente
    $sheet->setTitle('Reporte por Docente');
    $sheet->setCellValue('A1', 'REPORTE POR DOCENTE');
    $sheet->setCellValue('A2', 'Fecha: ' . date('d/m/Y H:i:s'));
    $sheet->mergeCells('A1:H1');
    $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
    
    // Encabezados
    $headers = ['Docente', 'Email', 'Materias', 'Evaluaciones', 'Puntualidad', 'Resolución', 'Promedio', 'Estado'];
    $col = 'A';
    foreach ($headers as $header) {
        $sheet->setCellValue($col . '4', $header);
        $sheet->getColumnDimension($col)->setAutoSize(true);
        $col++;
    }
    $sheet->getStyle('A4:H4')->applyFromArray($headerStyle);
    
    // Datos
    $docentes = $db->query("
        SELECT 
            d.nombre || ' ' || d.apellidos as docente,
            d.email,
            GROUP_CONCAT(DISTINCT m.nombre) as materias,
            COUNT(DISTINCT e.id) as evaluaciones,
            AVG(e.puntualidad_asistencia) as puntualidad,
            AVG(e.resolvio_dudas) as resolucion
        FROM docentes d
        LEFT JOIN materia_docente md ON d.id = md.docente_id AND md.activo = 1
        LEFT JOIN materias m ON md.materia_id = m.id
        LEFT JOIN evaluaciones e ON md.id = e.materia_docente_id
        WHERE d.activo = 1
        GROUP BY d.id
        ORDER BY d.nombre
    ")->fetchAll();
    
    $row = 5;
    foreach ($docentes as $d) {
        $promedio = $d['evaluaciones'] > 0 ? round(($d['puntualidad'] + $d['resolucion']) / 2, 2) : 0;
        $estado = $promedio >= 7 ? 'Excelente' : ($promedio >= 4 ? 'Regular' : 'Mejorar');
        
        $sheet->setCellValue('A' . $row, $d['docente']);
        $sheet->setCellValue('B' . $row, $d['email']);
        $sheet->setCellValue('C' . $row, $d['materias'] ?: 'Sin asignar');
        $sheet->setCellValue('D' . $row, $d['evaluaciones']);
        $sheet->setCellValue('E' . $row, $d['evaluaciones'] > 0 ? round($d['puntualidad'], 2) : 'N/A');
        $sheet->setCellValue('F' . $row, $d['evaluaciones'] > 0 ? round($d['resolucion'], 2) : 'N/A');
        $sheet->setCellValue('G' . $row, $promedio);
        $sheet->setCellValue('H' . $row, $estado);
        $row++;
    }
    
} elseif ($tipo == 'materias') {
    // Reporte por Materia
    $sheet->setTitle('Reporte por Materia');
    $sheet->setCellValue('A1', 'REPORTE POR MATERIA');
    $sheet->setCellValue('A2', 'Fecha: ' . date('d/m/Y H:i:s'));
    $sheet->mergeCells('A1:H1');
    $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
    
    $headers = ['Clave', 'Materia', 'Docentes', 'Evaluaciones', 'Puntualidad', 'Resolución', 'Promedio', 'Estado'];
    $col = 'A';
    foreach ($headers as $header) {
        $sheet->setCellValue($col . '4', $header);
        $sheet->getColumnDimension($col)->setAutoSize(true);
        $col++;
    }
    $sheet->getStyle('A4:H4')->applyFromArray($headerStyle);
    
    $materias = $db->query("
        SELECT 
            m.clave,
            m.nombre,
            GROUP_CONCAT(DISTINCT d.nombre || ' ' || d.apellidos) as docentes,
            COUNT(DISTINCT e.id) as evaluaciones,
            AVG(e.puntualidad_asistencia) as puntualidad,
            AVG(e.resolvio_dudas) as resolucion
        FROM materias m
        LEFT JOIN materia_docente md ON m.id = md.materia_id AND md.activo = 1
        LEFT JOIN docentes d ON md.docente_id = d.id
        LEFT JOIN evaluaciones e ON md.id = e.materia_docente_id
        WHERE m.activo = 1
        GROUP BY m.id
        ORDER BY m.nombre
    ")->fetchAll();
    
    $row = 5;
    foreach ($materias as $m) {
        $promedio = $m['evaluaciones'] > 0 ? round(($m['puntualidad'] + $m['resolucion']) / 2, 2) : 0;
        $estado = $promedio >= 7 ? 'Excelente' : ($promedio >= 4 ? 'Regular' : 'Mejorar');
        
        $sheet->setCellValue('A' . $row, $m['clave']);
        $sheet->setCellValue('B' . $row, $m['nombre']);
        $sheet->setCellValue('C' . $row, $m['docentes'] ?: 'Sin asignar');
        $sheet->setCellValue('D' . $row, $m['evaluaciones']);
        $sheet->setCellValue('E' . $row, $m['evaluaciones'] > 0 ? round($m['puntualidad'], 2) : 'N/A');
        $sheet->setCellValue('F' . $row, $m['evaluaciones'] > 0 ? round($m['resolucion'], 2) : 'N/A');
        $sheet->setCellValue('G' . $row, $promedio);
        $sheet->setCellValue('H' . $row, $estado);
        $row++;
    }
    
} elseif ($tipo == 'generaciones') {
    // Reporte por Generación
    $sheet->setTitle('Reporte por Generación');
    $sheet->setCellValue('A1', 'REPORTE POR GENERACIÓN');
    $sheet->setCellValue('A2', 'Fecha: ' . date('d/m/Y H:i:s'));
    $sheet->mergeCells('A1:H1');
    $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
    
    $headers = ['Generación', 'Año', 'Alumnos', 'Evaluaciones', 'Puntualidad', 'Resolución', 'Promedio', 'Estado'];
    $col = 'A';
    foreach ($headers as $header) {
        $sheet->setCellValue($col . '4', $header);
        $sheet->getColumnDimension($col)->setAutoSize(true);
        $col++;
    }
    $sheet->getStyle('A4:H4')->applyFromArray($headerStyle);
    
    $generaciones = $db->query("
        SELECT 
            g.nombre,
            g.anio,
            COUNT(DISTINCT a.id) as alumnos,
            COUNT(DISTINCT e.id) as evaluaciones,
            AVG(e.puntualidad_asistencia) as puntualidad,
            AVG(e.resolvio_dudas) as resolucion
        FROM generaciones g
        LEFT JOIN alumnos a ON g.id = a.generacion_id AND a.activo = 1
        LEFT JOIN evaluaciones e ON a.id = e.alumno_id
        WHERE g.activo = 1
        GROUP BY g.id
        ORDER BY g.anio DESC
    ")->fetchAll();
    
    $row = 5;
    foreach ($generaciones as $g) {
        $promedio = $g['evaluaciones'] > 0 ? round(($g['puntualidad'] + $g['resolucion']) / 2, 2) : 0;
        $estado = $promedio >= 7 ? 'Excelente' : ($promedio >= 4 ? 'Regular' : 'Mejorar');
        
        $sheet->setCellValue('A' . $row, $g['nombre']);
        $sheet->setCellValue('B' . $row, $g['anio']);
        $sheet->setCellValue('C' . $row, $g['alumnos']);
        $sheet->setCellValue('D' . $row, $g['evaluaciones']);
        $sheet->setCellValue('E' . $row, $g['evaluaciones'] > 0 ? round($g['puntualidad'], 2) : 'N/A');
        $sheet->setCellValue('F' . $row, $g['evaluaciones'] > 0 ? round($g['resolucion'], 2) : 'N/A');
        $sheet->setCellValue('G' . $row, $promedio);
        $sheet->setCellValue('H' . $row, $estado);
        $row++;
    }
    
} elseif ($tipo == 'periodos') {
    // Reporte por Periodo
    $sheet->setTitle('Reporte por Periodo');
    $sheet->setCellValue('A1', 'REPORTE POR PERIODO');
    $sheet->setCellValue('A2', 'Fecha: ' . date('d/m/Y H:i:s'));
    $sheet->mergeCells('A1:H1');
    $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
    
    $headers = ['Periodo', 'Alumnos', 'Docentes', 'Materias', 'Evaluaciones', 'Puntualidad', 'Resolución', 'Promedio'];
    $col = 'A';
    foreach ($headers as $header) {
        $sheet->setCellValue($col . '4', $header);
        $sheet->getColumnDimension($col)->setAutoSize(true);
        $col++;
    }
    $sheet->getStyle('A4:H4')->applyFromArray($headerStyle);
    
    $periodos = $db->query("
        SELECT DISTINCT periodo 
        FROM materia_docente 
        WHERE activo = 1 
        ORDER BY periodo DESC
    ")->fetchAll(PDO::FETCH_COLUMN);
    
    $row = 5;
    foreach ($periodos as $periodo) {
        $stmt = $db->prepare("
            SELECT 
                COUNT(DISTINCT a.id) as alumnos,
                COUNT(DISTINCT d.id) as docentes,
                COUNT(DISTINCT m.id) as materias,
                COUNT(DISTINCT e.id) as evaluaciones,
                AVG(e.puntualidad_asistencia) as puntualidad,
                AVG(e.resolvio_dudas) as resolucion
            FROM materia_docente md
            JOIN materias m ON md.materia_id = m.id
            JOIN docentes d ON md.docente_id = d.id
            LEFT JOIN evaluaciones e ON md.id = e.materia_docente_id
            LEFT JOIN alumnos a ON e.alumno_id = a.id
            WHERE md.periodo = ? AND md.activo = 1
        ");
        $stmt->execute([$periodo]);
        $data = $stmt->fetch();
        
        $promedio = $data['evaluaciones'] > 0 ? round(($data['puntualidad'] + $data['resolucion']) / 2, 2) : 0;
        
        $sheet->setCellValue('A' . $row, $periodo);
        $sheet->setCellValue('B' . $row, $data['alumnos'] ?: 0);
        $sheet->setCellValue('C' . $row, $data['docentes'] ?: 0);
        $sheet->setCellValue('D' . $row, $data['materias'] ?: 0);
        $sheet->setCellValue('E' . $row, $data['evaluaciones']);
        $sheet->setCellValue('F' . $row, $data['evaluaciones'] > 0 ? round($data['puntualidad'], 2) : 'N/A');
        $sheet->setCellValue('G' . $row, $data['evaluaciones'] > 0 ? round($data['resolucion'], 2) : 'N/A');
        $sheet->setCellValue('H' . $row, $promedio);
        $row++;
    }
}

// Aplicar bordes
$styleArray = [
    'borders' => [
        'allBorders' => [
            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
            'color' => ['rgb' => '000000']
        ]
    ]
];

$lastRow = $row - 1;
$lastCol = $sheet->getHighestColumn();
$sheet->getStyle('A4:' . $lastCol . $lastRow)->applyFromArray($styleArray);

// Configurar headers para descarga
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="reporte_' . $tipo . '_' . date('Ymd_His') . '.xlsx"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;