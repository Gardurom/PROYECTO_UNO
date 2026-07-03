<?php
// ajax/historial_escaneos.php
session_start();
header('Content-Type: application/json');

require_once '../includes/database.php';

$db = getDB();

try {
    $stmt = $db->query("
        SELECT 
            s.*,
            q.codigo,
            u.username as usuario
        FROM qr_scans s
        JOIN qr_codes q ON s.qr_code_id = q.id
        LEFT JOIN usuarios u ON s.usuario_id = u.id
        ORDER BY s.fecha_escaneo DESC
        LIMIT 100
    ");
    echo json_encode($stmt->fetchAll());
} catch (PDOException $e) {
    echo json_encode([]);
}
?>