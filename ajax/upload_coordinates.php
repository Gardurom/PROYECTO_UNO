<?php
session_start();

// Verificar autenticación
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    echo json_encode(['success' => false, 'error' => 'No autorizado']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['excel_data'])) {
    $data = json_decode($_POST['excel_data'], true);
    
    if (!$data) {
        echo json_encode(['success' => false, 'error' => 'Datos inválidos']);
        exit;
    }
    
    $coordinates = [];
    foreach ($data as $row) {
        $lat = $row['lat'] ?? $row['latitude'] ?? $row['Lat'] ?? null;
        $lng = $row['lng'] ?? $row['longitude'] ?? $row['Lng'] ?? null;
        $title = $row['title'] ?? $row['Titulo'] ?? 'Punto';
        $description = $row['description'] ?? $row['Descripcion'] ?? '';
        
        if ($lat && $lng) {
            $coordinates[] = [
                'lat' => floatval($lat),
                'lng' => floatval($lng),
                'title' => $title,
                'description' => $description
            ];
        }
    }
    
    // Guardar en sesión o base de datos temporal
    $_SESSION['temp_coordinates'] = $coordinates;
    
    echo json_encode([
        'success' => true,
        'coordinates' => $coordinates,
        'count' => count($coordinates)
    ]);
} else {
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
}
?>