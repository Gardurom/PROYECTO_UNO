<?php
// ajax/verificar_geocerca.php
session_start();
header('Content-Type: application/json');

require_once '../includes/database.php';

$db = getDB();

$lat = floatval($_POST['lat'] ?? 0);
$lng = floatval($_POST['lng'] ?? 0);
$dentro = intval($_POST['dentro'] ?? 0);
$precision = $_POST['precision'] ?? 'N/A';
$sistema = $_POST['sistema'] ?? 'EPSG:4326';
$usuario_id = $_SESSION['user_id'] ?? null;
$ip = $_SERVER['REMOTE_ADDR'];

try {
    $stmt = $db->prepare("
        INSERT INTO geocerca_verificaciones 
        (usuario_id, latitud, longitud, dentro, precision, sistema, ip, fecha_verificacion)
        VALUES (?, ?, ?, ?, ?, ?, ?, datetime('now'))
    ");
    $stmt->execute([$usuario_id, $lat, $lng, $dentro, $precision, $sistema, $ip]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Verificación registrada (EPSG:4326)',
        'data' => [
            'lat' => $lat,
            'lng' => $lng,
            'dentro' => $dentro,
            'sistema' => $sistema
        ]
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>