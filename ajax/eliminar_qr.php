<?php
// ajax/eliminar_qr.php
session_start();
header('Content-Type: application/json');

require_once '../includes/database.php';

$db = getDB();
$input = json_decode(file_get_contents('php://input'), true);
$id = intval($input['id'] ?? 0);

if ($id <= 0) {
    echo json_encode(['success' => false, 'error' => 'ID no válido']);
    exit;
}

try {
    $stmt = $db->prepare("UPDATE qr_codes SET estado = 0 WHERE id = ?");
    $stmt->execute([$id]);
    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>