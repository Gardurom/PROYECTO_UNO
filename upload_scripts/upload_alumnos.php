<?php
// upload_scripts/upload_alumnos.php
session_start();
require_once '../config/database.php';
require_once '../config/functions.php';
require_once '../vendor/autoload.php'; // Para PhpSpreadsheet

use PhpOffice\PhpSpreadsheet\IOFactory;

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../login.php');
    exit;
}

$db = Database::getInstance()->getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['excel_file'])) {
    try {
        if (!validateExcelFile($_FILES['excel_file'])) {
            throw new Exception('Archivo no válido');
        }
        
        $inputFileName = $_FILES['excel_file']['tmp_name'];
        $spreadsheet = IOFactory::load($inputFileName);
        $worksheet = $spreadsheet->getActiveSheet();
        $rows = $worksheet->toArray();
        
        // Eliminar encabezados
        array_shift($rows);
        
        $successCount = 0;
        $errors = [];
        
        $db->beginTransaction();
        
        foreach ($rows as $rowIndex => $row) {
            if (empty($row[0]) || empty($row[1])) continue;
            
            $matricula = sanitizeInput($row[0]);
            $nombre = sanitizeInput($row[1]);
            $apellidos = sanitizeInput($row[2] ?? '');
            $email = sanitizeInput($row[3] ?? '');
            $generacion_nombre = sanitizeInput($row[4] ?? '');
            $grupo_nombre = sanitizeInput($row[5] ?? '');
            
            // Obtener o crear generación
            $stmt = $db->prepare("SELECT id FROM generaciones WHERE nombre = ?");
            $stmt->execute([$generacion_nombre]);
            $generacion = $stmt->fetch();
            
            if (!$generacion) {
                $stmt = $db->prepare("INSERT INTO generaciones (nombre, anio) VALUES (?, ?)");
                $anio = date('Y');
                $stmt->execute([$generacion_nombre, $anio]);
                $generacion_id = $db->lastInsertId();
            } else {
                $generacion_id = $generacion['id'];
            }
            
            // Obtener o crear grupo
            $stmt = $db->prepare("SELECT id FROM grupos WHERE nombre = ? AND generacion_id = ?");
            $stmt->execute([$grupo_nombre, $generacion_id]);
            $grupo = $stmt->fetch();
            
            if (!$grupo) {
                $stmt = $db->prepare("INSERT INTO grupos (nombre, generacion_id) VALUES (?, ?)");
                $stmt->execute([$grupo_nombre, $generacion_id]);
                $grupo_id = $db->lastInsertId();
            } else {
                $grupo_id = $grupo['id'];
            }
            
            // Insertar alumno
            try {
                $stmt = $db->prepare("
                    INSERT INTO alumnos (matricula, nombre, apellidos, email, generacion_id, grupo_id) 
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([$matricula, $nombre, $apellidos, $email, $generacion_id, $grupo_id]);
                $successCount++;
            } catch (PDOException $e) {
                if ($e->getCode() == 23000) { // Unique constraint violation
                    $errors[] = "Fila " . ($rowIndex + 2) . ": La matrícula $matricula ya existe";
                } else {
                    throw $e;
                }
            }
        }
        
        $db->commit();
        
        $_SESSION['upload_result'] = [
            'success' => true,
            'message' => "Se cargaron $successCount alumnos correctamente",
            'errors' => $errors
        ];
        
    } catch (Exception $e) {
        $db->rollBack();
        $_SESSION['upload_result'] = [
            'success' => false,
            'message' => 'Error: ' . $e->getMessage()
        ];
    }
    
    header('Location: ../index.php?page=alumnos');
    exit;
}
?>