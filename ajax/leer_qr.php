<?php
// ajax/leer_qr.php
session_start();
header('Content-Type: application/json');

require_once '../includes/database.php';

$db = getDB();
$codigo = $_GET['codigo'] ?? '';

if (empty($codigo)) {
    echo json_encode(['success' => false, 'error' => 'Código QR no proporcionado']);
    exit;
}

$codigo = trim($codigo);
$codigo = preg_replace('/[^A-Za-z0-9-]/', '', $codigo);

try {
    $stmt = $db->prepare("SELECT * FROM qr_codes WHERE codigo = ? AND estado = 1");
    $stmt->execute([$codigo]);
    $qr = $stmt->fetch();
    
    if (!$qr) {
        echo json_encode(['success' => false, 'error' => 'Código QR no válido o inactivo']);
        exit;
    }
    
    $datos = null;
    if ($qr['tipo'] === 'cadete' && $qr['referencia_id']) {
        $stmt2 = $db->prepare("SELECT * FROM alumnos WHERE id = ? AND activo = 1");
        $stmt2->execute([$qr['referencia_id']]);
        $datos = $stmt2->fetch();
    } elseif ($qr['tipo'] === 'docente' && $qr['referencia_id']) {
        $stmt2 = $db->prepare("SELECT * FROM docentes WHERE id = ? AND activo = 1");
        $stmt2->execute([$qr['referencia_id']]);
        $datos = $stmt2->fetch();
    }
    
    echo json_encode([
        'success' => true,
        'id' => $qr['id'],
        'codigo' => $qr['codigo'],
        'tipo' => $qr['tipo'],
        'referencia_id' => $qr['referencia_id'],
        'datos' => $datos,
        'estado' => $qr['estado']
    ]);
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Error al buscar el código QR']);
}
?>