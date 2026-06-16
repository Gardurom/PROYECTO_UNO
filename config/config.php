<?php
/**
 * Configuración central del sistema
 */

// Configuración de la aplicación
define('APP_NAME', 'Sistema de Gestión');
define('APP_VERSION', '2.0.0');
define('ENVIRONMENT', 'development'); // development, production
define('TIMEZONE', 'America/Mexico_City');
define('BASE_URL', '');

// Configuración de rutas
define('BASE_PATH', dirname(__DIR__));
define('CONFIG_PATH', BASE_PATH . '/config');
define('INCLUDES_PATH', BASE_PATH . '/includes');
define('DATA_PATH', BASE_PATH . '/data');
define('LOGS_PATH', BASE_PATH . '/logs');
define('ERRORS_PATH', BASE_PATH . '/errors');

// Configuración de base de datos
define('DB_VISITAS', DATA_PATH . '/visitas.db');
define('DB_USUARIOS', DATA_PATH . '/usuarios.db');

// Configuración de seguridad
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOCKOUT_TIME', 900); // 15 minutos
define('SESSION_LIFETIME', 7200); // 2 horas

// Configuración de logs
define('LOG_LEVEL', ENVIRONMENT === 'development' ? 'debug' : 'error');
define('LOG_ERRORS', LOGS_PATH . '/php_errors.log');

// Crear directorios necesarios
$directories = [DATA_PATH, LOGS_PATH, ERRORS_PATH];
foreach ($directories as $dir) {
    if (!file_exists($dir)) {
        mkdir($dir, 0755, true);
    }
}

// Configurar zona horaria
date_default_timezone_set(TIMEZONE);
?>