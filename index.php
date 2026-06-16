<?php
/**
 * Punto de entrada principal del sistema
 */

// Cargar bootstrap
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error.log');
require_once __DIR__ . '/includes/Bootstrap.php';

// Inicializar sistema
$app = Bootstrap::getInstance();
$app->initSession();

// Ejecutar aplicación
$app->run();
?>