<?php
// ajax/generar_qr.php
session_start();
header('Content-Type: application/json');

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../includes/database.php';

function responderError($mensaje) {
    echo json_encode(['success' => false, 'error' => $mensaje]);
    exit;
}

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    responderError('No autorizado');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    responderError('Método no permitido');
}

$tipo = $_POST['tipo'] ?? '';
$referencia_id = intval($_POST['referencia_id'] ?? 0);
$datos_extra = $_POST['datos_extra'] ?? '';

if (empty($tipo)) {
    responderError('El tipo de QR es requerido');
}

try {
    $db = getDB();
    
    $codigo = 'QR-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -8));
    
    $stmt = $db->prepare("INSERT INTO qr_codes (codigo, tipo, referencia_id, datos_extra, generado_por, fecha_generacion) VALUES (?, ?, ?, ?, ?, datetime('now'))");
    $stmt->execute([$codigo, $tipo, $referencia_id > 0 ? $referencia_id : null, $datos_extra ?: null, $_SESSION['user_id'] ?? 1]);
    
    $qr_id = $db->lastInsertId();
    $qr_image = 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=' . urlencode($codigo);
    
    echo json_encode([
        'success' => true,
        'id' => $qr_id,
        'codigo' => $codigo,
        'qr_image' => $qr_image,
        'tipo' => $tipo
    ]);
    
} catch (PDOException $e) {
    responderError('Error de base de datos: ' . $e->getMessage());
} catch (Exception $e) {
    responderError('Error: ' . $e->getMessage());
}
?>