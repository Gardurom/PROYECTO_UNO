<?php
// config/functions.php
function sanitizeInput($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

function generateJSONResponse($success, $message, $data = null) {
    header('Content-Type: application/json');
    echo json_encode(['success' => $success, 'message' => $message, 'data' => $data]);
    exit;
}

function validateExcelFile($file) {
    $allowed = ['xlsx', 'xls', 'csv'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    return in_array($ext, $allowed) && $file['error'] === UPLOAD_ERR_OK;
}