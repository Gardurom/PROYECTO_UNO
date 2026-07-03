<?php
// ajax/registrar_qr.php
session_start();
header('Content-Type: application/json');

require_once '../includes/database.php';

$db = getDB();
$input = json_decode(file_get_contents('php://input'), true);
$codigo = $input['codigo'] ?? '';
$ubicacion = $input['ubicacion'] ?? null;

if (empty($codigo)) {
    echo json_encode(['success' => false, 'error' => 'Código QR no proporcionado']);
    exit;
}

try {
    $stmt = $db->prepare("SELECT id FROM qr_codes WHERE codigo = ?");
    $stmt->execute([$codigo]);
    $qr = $stmt->fetch();
    
    if (!$qr) {
        echo json_encode(['success' => false, 'error' => 'Código QR no válido']);
        exit;
    }
    
    $stmt = $db->prepare("INSERT INTO qr_scans (qr_code_id, usuario_id, ip, user_agent, latitud, longitud) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $qr['id'],
        $_SESSION['user_id'] ?? null,
        $_SERVER['REMOTE_ADDR'],
        $_SERVER['HTTP_USER_AGENT'],
        $ubicacion['lat'] ?? null,
        $ubicacion['lng'] ?? null
    ]);
    
    echo json_encode(['success' => true, 'message' => 'Escaneo registrado correctamente']);
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>