<?php
// ajax/listar_qr.php
session_start();
header('Content-Type: application/json');

require_once '../includes/database.php';

$db = getDB();

try {
    $stmt = $db->query("
        SELECT 
            q.*,
            COUNT(s.id) as total_escaneos
        FROM qr_codes q
        LEFT JOIN qr_scans s ON q.id = s.qr_code_id
        GROUP BY q.id
        ORDER BY q.fecha_generacion DESC
    ");
    echo json_encode($stmt->fetchAll());
} catch (PDOException $e) {
    echo json_encode([]);
}
?>