<?php
// ajax/procesar_csv.php
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Log para depuración
$logDir = __DIR__ . '/../logs';
if (!file_exists($logDir)) mkdir($logDir, 0777, true);

function logCSV($msg) {
    global $logDir;
    file_put_contents($logDir . '/csv_import.log', date('Y-m-d H:i:s') . " - $msg\n", FILE_APPEND);
}

try {
    logCSV("=== INICIO IMPORTACIÓN CSV ===");
    
    require_once '../includes/database.php';
    $db = getDB();
    
    // Verificar archivo
    if (!isset($_FILES['archivo']) || $_FILES['archivo']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('No se recibió el archivo correctamente');
    }
    
    if (!isset($_POST['tipo'])) {
        throw new Exception('No se especificó el tipo de datos');
    }
    
    $tipo = $_POST['tipo'];
    $archivo = $_FILES['archivo'];
    
    // Verificar extensión
    $ext = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));
    if ($ext !== 'csv') {
        throw new Exception('Solo se permiten archivos CSV. Extensión recibida: ' . $ext);
    }
    
    logCSV("Archivo: " . $archivo['name']);
    logCSV("Tipo: $tipo");
    
    // Abrir y leer CSV
    $handle = fopen($archivo['tmp_name'], 'r');
    if (!$handle) {
        throw new Exception('No se pudo abrir el archivo');
    }
    
    // Leer encabezados
    $headers = fgetcsv($handle);
    if (!$headers) {
        throw new Exception('El archivo está vacío o no tiene encabezados');
    }
    
    // Normalizar encabezados
    $headers = array_map('strtolower', array_map('trim', $headers));
    logCSV("Encabezados: " . implode(', ', $headers));
    
    // Procesar según tipo
    $db->beginTransaction();
    $resultado = [];
    
    try {
        if ($tipo === 'alumnos') {
            $resultado = procesarAlumnosCSV($db, $handle, $headers);
        } elseif ($tipo === 'docentes') {
            $resultado = procesarDocentesCSV($db, $handle, $headers);
        } elseif ($tipo === 'materias') {
            $resultado = procesarMateriasCSV($db, $handle, $headers);
        } else {
            throw new Exception('Tipo no válido: ' . $tipo);
        }
        
        $db->commit();
        fclose($handle);
        
        logCSV("Éxito: " . $resultado['success'] . " registros importados");
        
        echo json_encode([
            'success' => true,
            'total' => $resultado['success'],
            'errores' => count($resultado['errors']),
            'duplicados' => count($resultado['duplicados'] ?? []),
            'detalle_errores' => $resultado['errors'],
            'detalle_duplicados' => $resultado['duplicados'] ?? []
        ]);
        exit;
        
    } catch (Exception $e) {
        $db->rollBack();
        fclose($handle);
        throw $e;
    }
    
} catch (Exception $e) {
    logCSV("ERROR: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
    exit;
}

// ============================================
// FUNCIONES DE PROCESAMIENTO
// ============================================

function procesarAlumnosCSV($db, $handle, $headers) {
    $success = 0;
    $errors = [];
    $duplicados = [];
    $rowNum = 2;
    
    while (($data = fgetcsv($handle)) !== false) {
        // Si la fila está vacía, saltar
        if (empty(array_filter($data))) {
            $rowNum++;
            continue;
        }
        
        // Mapear datos
        $row = array_combine($headers, $data);
        if ($row === false) {
            $errors[] = "Fila $rowNum: Error en el formato de la fila";
            $rowNum++;
            continue;
        }
        
        $matricula = trim($row['matricula'] ?? '');
        $nombre = trim($row['nombre'] ?? '');
        $apellidos = trim($row['apellidos'] ?? '');
        $email = trim($row['email'] ?? '');
        $generacion = trim($row['generacion'] ?? '');
        $grupo = trim($row['grupo'] ?? '');
        
        // Validaciones
        if (empty($matricula)) {
            $errors[] = "Fila $rowNum: Matrícula es requerida";
            $rowNum++;
            continue;
        }
        
        if (empty($nombre)) {
            $errors[] = "Fila $rowNum: Nombre es requerido";
            $rowNum++;
            continue;
        }
        
        try {
            // Procesar generación
            if (empty($generacion)) {
                $generacion = 'Generación ' . date('Y');
            }
            
            $stmt = $db->prepare("SELECT id FROM generaciones WHERE nombre = ?");
            $stmt->execute([$generacion]);
            $gen = $stmt->fetch();
            
            if (!$gen) {
                $stmt = $db->prepare("INSERT INTO generaciones (nombre, anio) VALUES (?, ?)");
                $stmt->execute([$generacion, date('Y')]);
                $gen_id = $db->lastInsertId();
            } else {
                $gen_id = $gen['id'];
            }
            
            // Procesar grupo
            if (empty($grupo)) {
                $grupo = 'Grupo A';
            }
            
            $stmt = $db->prepare("SELECT id FROM grupos WHERE nombre = ? AND generacion_id = ?");
            $stmt->execute([$grupo, $gen_id]);
            $grp = $stmt->fetch();
            
            if (!$grp) {
                $stmt = $db->prepare("INSERT INTO grupos (nombre, generacion_id) VALUES (?, ?)");
                $stmt->execute([$grupo, $gen_id]);
                $grupo_id = $db->lastInsertId();
            } else {
                $grupo_id = $grp['id'];
            }
            
            // Insertar alumno
            $stmt = $db->prepare("
                INSERT INTO alumnos (matricula, nombre, apellidos, email, generacion_id, grupo_id) 
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$matricula, $nombre, $apellidos, $email, $gen_id, $grupo_id]);
            $success++;
            
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'UNIQUE constraint failed') !== false) {
                $duplicados[] = "Fila $rowNum: Matrícula '$matricula' ya existe";
            } else {
                $errors[] = "Fila $rowNum: " . $e->getMessage();
            }
        }
        
        $rowNum++;
    }
    
    return ['success' => $success, 'errors' => $errors, 'duplicados' => $duplicados];
}

function procesarDocentesCSV($db, $handle, $headers) {
    $success = 0;
    $errors = [];
    $duplicados = [];
    $rowNum = 2;
    
    while (($data = fgetcsv($handle)) !== false) {
        if (empty(array_filter($data))) {
            $rowNum++;
            continue;
        }
        
        $row = array_combine($headers, $data);
        if ($row === false) {
            $errors[] = "Fila $rowNum: Error en el formato";
            $rowNum++;
            continue;
        }
        
        $nombre = trim($row['nombre'] ?? '');
        $apellidos = trim($row['apellidos'] ?? '');
        $email = trim($row['email'] ?? '');
        $telefono = trim($row['telefono'] ?? '');
        
        if (empty($nombre) || empty($apellidos)) {
            $errors[] = "Fila $rowNum: Nombre y apellidos son requeridos";
            $rowNum++;
            continue;
        }
        
        try {
            $stmt = $db->prepare("INSERT INTO docentes (nombre, apellidos, email, telefono) VALUES (?, ?, ?, ?)");
            $stmt->execute([$nombre, $apellidos, $email, $telefono]);
            $success++;
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'UNIQUE constraint failed') !== false) {
                $duplicados[] = "Fila $rowNum: Email '$email' ya existe";
            } else {
                $errors[] = "Fila $rowNum: " . $e->getMessage();
            }
        }
        
        $rowNum++;
    }
    
    return ['success' => $success, 'errors' => $errors, 'duplicados' => $duplicados];
}

function procesarMateriasCSV($db, $handle, $headers) {
    $success = 0;
    $errors = [];
    $duplicados = [];
    $rowNum = 2;
    
    while (($data = fgetcsv($handle)) !== false) {
        if (empty(array_filter($data))) {
            $rowNum++;
            continue;
        }
        
        $row = array_combine($headers, $data);
        if ($row === false) {
            $errors[] = "Fila $rowNum: Error en el formato";
            $rowNum++;
            continue;
        }
        
        $nombre = trim($row['nombre'] ?? '');
        $clave = trim($row['clave'] ?? '');
        
        if (empty($nombre)) {
            $errors[] = "Fila $rowNum: Nombre de materia es requerido";
            $rowNum++;
            continue;
        }
        
        if (empty($clave)) {
            $clave = strtoupper(substr(preg_replace('/[^a-zA-Z0-9]/', '', $nombre), 0, 5)) . rand(10, 99);
        }
        
        try {
            $stmt = $db->prepare("INSERT INTO materias (nombre, clave) VALUES (?, ?)");
            $stmt->execute([$nombre, $clave]);
            $success++;
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'UNIQUE constraint failed') !== false) {
                $duplicados[] = "Fila $rowNum: Materia '$nombre' ya existe";
            } else {
                $errors[] = "Fila $rowNum: " . $e->getMessage();
            }
        }
        
        $rowNum++;
    }
    
    return ['success' => $success, 'errors' => $errors, 'duplicados' => $duplicados];
}
?>