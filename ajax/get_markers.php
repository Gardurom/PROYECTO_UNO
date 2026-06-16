<?php
session_start();

// Verificar autenticación
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    echo json_encode(['success' => false, 'error' => 'No autorizado']);
    exit;
}

// Obtener coordenadas de la sesión o base de datos
$coordinates = $_SESSION['temp_coordinates'] ?? [];

echo json_encode([
    'success' => true,
    'markers' => $coordinates
]);
?>