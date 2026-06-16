<?php
/**
 * Gestor de sesiones seguras
 */

class SessionManager {
    
    /**
     * Inicializar configuración de sesión segura
     */
    public static function init() {
        // Configurar cookies de sesión seguras
        ini_set('session.cookie_httponly', 1);
        ini_set('session.use_strict_mode', 1);
        ini_set('session.cookie_samesite', 'Strict');
        
        if (self::isHttps()) {
            ini_set('session.cookie_secure', 1);
        }
        
        // Iniciar sesión
        session_start();
        
        // Regenerar ID para prevenir fijación
        if (!isset($_SESSION['initialized'])) {
            session_regenerate_id(true);
            $_SESSION['initialized'] = true;
        }
    }
    
    /**
     * Verificar si la conexión es HTTPS
     */
    private static function isHttps() {
        return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
    }
    
    /**
     * Regenerar ID de sesión
     */
    public static function regenerate() {
        session_regenerate_id(true);
    }
    
    /**
     * Destruir sesión completamente
     */
    public static function destroy() {
        $_SESSION = array();
        
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        
        session_destroy();
    }
    
    /**
     * Establecer valor en sesión
     */
    public static function set($key, $value) {
        $_SESSION[$key] = $value;
    }
    
    /**
     * Obtener valor de sesión
     */
    public static function get($key, $default = null) {
        return $_SESSION[$key] ?? $default;
    }
    
    /**
     * Eliminar valor de sesión
     */
    public static function remove($key) {
        unset($_SESSION[$key]);
    }
    
    /**
     * Verificar si existe valor en sesión
     */
    public static function has($key) {
        return isset($_SESSION[$key]);
    }
}
?>