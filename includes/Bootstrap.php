<?php
/**
 * Bootstrap - Inicialización del sistema
 * Carga configuraciones, clases y prepara el entorno
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/SessionManager.php';

class Bootstrap {
    private static $instance = null;
    private $logger;
    
    private function __construct() {
        $this->setupErrorHandling();
        $this->loadClasses();
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Configurar manejo de errores
     */
    private function setupErrorHandling() {
        if (ENVIRONMENT === 'development') {
            error_reporting(E_ALL);
            ini_set('display_errors', 1);
        } else {
            error_reporting(E_ALL);
            ini_set('display_errors', 0);
            ini_set('log_errors', 1);
            ini_set('error_log', LOG_ERRORS);
        }
    }
    
    /**
     * Cargar clases necesarias con autoloading
     */
    private function loadClasses() {
        // Registrar autoloader
        spl_autoload_register(function($class) {
            $file = INCLUDES_PATH . '/' . $class . '.php';
            if (file_exists($file)) {
                require_once $file;
                return true;
            }
            return false;
        });
        
        // Cargar clases base
        $baseClasses = ['Database', 'Logger', 'VisitasManager'];
        foreach ($baseClasses as $class) {
            $file = INCLUDES_PATH . '/' . $class . '.php';
            if (file_exists($file)) {
                require_once $file;
            } else {
                $this->loadMinimalClass($class);
            }
        }
    }
    
    /**
     * Cargar clases mínimas si no existen
     */
    private function loadMinimalClass($class) {
        switch($class) {
            case 'Database':
                if (!class_exists('Database')) {
                    eval('class Database {
                        private $connection;
                        public function __construct($dbPath) {
                            $this->connection = new SQLite3($dbPath);
                        }
                        public function execute($sql, $params = []) {
                            $stmt = $this->connection->prepare($sql);
                            return $stmt->execute();
                        }
                    }');
                }
                break;
                
            case 'Logger':
                if (!class_exists('Logger')) {
                    eval('class Logger {
                        public function error($msg, $ctx) {
                            error_log("ERROR: " . $msg);
                        }
                        public function info($msg, $ctx) {
                            error_log("INFO: " . $msg);
                        }
                    }');
                }
                break;
                
            case 'VisitasManager':
                if (!class_exists('VisitasManager')) {
                    eval('class VisitasManager {
                        private $db;
                        public function __construct() {
                            $dbPath = DATA_PATH . "/visitas.db";
                            if (!file_exists(dirname($dbPath))) mkdir(dirname($dbPath), 0755, true);
                            $this->db = new SQLite3($dbPath);
                            $this->db->exec("CREATE TABLE IF NOT EXISTS visitas (id INTEGER PRIMARY KEY, fecha TEXT)");
                        }
                        public function registrarVisita() { return true; }
                    }');
                }
                break;
        }
    }
    
    /**
     * Inicializar sesión
     */
    public function initSession() {
        SessionManager::init();
    }
    
    /**
     * Obtener instancia de Logger
     */
    public function getLogger() {
        if ($this->logger === null && class_exists('Logger')) {
            $this->logger = new Logger();
        }
        return $this->logger;
    }
    
    /**
     * Ejecutar el sistema
     */
    public function run() {
        try {
            // Registrar visita
            $visitasManager = new VisitasManager();
            $visitasManager->registrarVisita();
            
            // Limpiar intentos de login
            SessionManager::set('login_attempts', 0);
            SessionManager::remove('last_attempt');
            
            // Redirigir al login
            header('Location: ' . BASE_URL . '/login.php');
            exit;
            
        } catch (Exception $e) {
            $logger = $this->getLogger();
            if ($logger) {
                $logger->error('Error en Bootstrap', [
                    'message' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
            }
            
            $this->showErrorPage($e);
        }
    }
    
    /**
     * Mostrar página de error
     */
    private function showErrorPage($e) {
        $errorFile = ERRORS_PATH . '/500.html';
        if (file_exists($errorFile)) {
            require_once $errorFile;
        } else {
            echo "<h1>Error 500 - Error interno del servidor</h1>";
            if (ENVIRONMENT === 'development') {
                echo "<p>" . $e->getMessage() . "</p>";
            }
        }
        exit;
    }
}
?>