<?php
// ajax/generar_qr.php
header('Content-Type: application/json');
require_once '../includes/database.php';

// Verificar que la asignación existe
$asignacion_id = intval($_GET['asignacion_id'] ?? 0);

if ($asignacion_id <= 0) {
    echo json_encode(['success' => false, 'error' => 'ID de asignación no válido']);
    exit;
}

$db = getDB();

$stmt = $db->prepare("
    SELECT md.*, m.nombre as materia_nombre, d.nombre as docente_nombre, d.apellidos as docente_apellidos
    FROM materia_docente md
    JOIN materias m ON md.materia_id = m.id
    JOIN docentes d ON md.docente_id = d.id
    WHERE md.id = ? AND md.activo = 1
");
$stmt->execute([$asignacion_id]);
$asignacion = $stmt->fetch();

if (!$asignacion) {
    echo json_encode(['success' => false, 'error' => 'Asignación no encontrada']);
    exit;
}

// Generar URL para la evaluación
$base_url = (isset($_SERVER['HTTPS']) ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'];
$url = $base_url . '/evaluar.php?asignacion=' . $asignacion_id;

try {
    // Verificar si existe la librería QR
    if (!class_exists('QRcode')) {
        // Intentar cargar la librería
        require_once '../vendor/autoload.php';
        
        if (!class_exists('QRcode')) {
            throw new Exception('Librería QR no encontrada. Instala: composer require endroid/qr-code');
        }
    }
    
    // Usar la librería para generar QR
    $qrCode = new \Endroid\QrCode\Builder\Builder();
    $result = $qrCode
        ->data($url)
        ->size(300)
        ->margin(10)
        ->build();
    
    // Guardar imagen en base64
    $imageData = $result->getDataUri();
    
    echo json_encode([
        'success' => true,
        'qr_image' => $imageData,
        'url' => $url,
        'asignacion' => [
            'id' => $asignacion['id'],
            'materia' => $asignacion['materia_nombre'],
            'docente' => $asignacion['docente_nombre'] . ' ' . $asignacion['docente_apellidos'],
            'periodo' => $asignacion['periodo']
        ]
    ]);
    
} catch (Exception $e) {
    // Si no hay librería QR, generar manualmente con Google Charts API
    $qr_url = 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=' . urlencode($url);
    
    echo json_encode([
        'success' => true,
        'qr_image' => $qr_url,
        'url' => $url,
        'asignacion' => [
            'id' => $asignacion['id'],
            'materia' => $asignacion['materia_nombre'],
            'docente' => $asignacion['docente_nombre'] . ' ' . $asignacion['docente_apellidos'],
            'periodo' => $asignacion['periodo']
        ]
    ]);
}
?>