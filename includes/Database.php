<?php
// includes/database.php
class Database {
    private static $instance = null;
    private $pdo;
    
    private function __construct() {
        $dbDir = __DIR__ . '/../database';
        if (!is_dir($dbDir)) {
            mkdir($dbDir, 0777, true);
        }
        
        $dbPath = $dbDir . '/evaluaciones.db';
        
        try {
            $this->pdo = new PDO("sqlite:$dbPath");
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            
            $this->initializeDatabase();
        } catch (PDOException $e) {
            error_log("Error de conexión: " . $e->getMessage());
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        return $this->pdo;
    }
    
    private function initializeDatabase() {
        $tables = $this->pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='generaciones'")->fetch();
        
        if (!$tables) {
            $sql = "
                -- Tabla de generaciones (años/grupos de alumnos)
                CREATE TABLE generaciones (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    nombre TEXT NOT NULL UNIQUE,
                    anio INTEGER NOT NULL,
                    activo INTEGER DEFAULT 1,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
                );

                -- Tabla de grupos
                CREATE TABLE grupos (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    nombre TEXT NOT NULL,
                    generacion_id INTEGER NOT NULL,
                    activo INTEGER DEFAULT 1,
                    FOREIGN KEY (generacion_id) REFERENCES generaciones(id),
                    UNIQUE(nombre, generacion_id)
                );

                -- Tabla de materias
                CREATE TABLE materias (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    nombre TEXT NOT NULL UNIQUE,
                    clave TEXT UNIQUE,
                    activo INTEGER DEFAULT 1,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
                );

                -- Tabla de docentes
                CREATE TABLE docentes (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    nombre TEXT NOT NULL,
                    apellidos TEXT NOT NULL,
                    email TEXT UNIQUE,
                    telefono TEXT,
                    activo INTEGER DEFAULT 1,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
                );

                -- Tabla de alumnos
                CREATE TABLE alumnos (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    matricula TEXT NOT NULL UNIQUE,
                    nombre TEXT NOT NULL,
                    apellidos TEXT NOT NULL,
                    email TEXT,
                    generacion_id INTEGER NOT NULL,
                    grupo_id INTEGER NOT NULL,
                    activo INTEGER DEFAULT 1,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (generacion_id) REFERENCES generaciones(id),
                    FOREIGN KEY (grupo_id) REFERENCES grupos(id)
                );

                -- Tabla de asignación materia-docente
                CREATE TABLE materia_docente (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    materia_id INTEGER NOT NULL,
                    docente_id INTEGER NOT NULL,
                    periodo TEXT NOT NULL,
                    activo INTEGER DEFAULT 1,
                    FOREIGN KEY (materia_id) REFERENCES materias(id),
                    FOREIGN KEY (docente_id) REFERENCES docentes(id),
                    UNIQUE(materia_id, docente_id, periodo)
                );

                -- Tabla de evaluaciones
                CREATE TABLE evaluaciones (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    alumno_id INTEGER NOT NULL,
                    materia_docente_id INTEGER NOT NULL,
                    puntualidad_asistencia INTEGER CHECK (puntualidad_asistencia >= 0 AND puntualidad_asistencia <= 9),
                    resolvio_dudas INTEGER CHECK (resolvio_dudas >= 0 AND resolvio_dudas <= 9),
                    comentario TEXT,
                    fecha_evaluacion DATETIME DEFAULT CURRENT_TIMESTAMP,
                    ip_address TEXT,
                    user_agent TEXT,
                    FOREIGN KEY (alumno_id) REFERENCES alumnos(id),
                    FOREIGN KEY (materia_docente_id) REFERENCES materia_docente(id),
                    UNIQUE(alumno_id, materia_docente_id)
                );

                -- Índices para mejor rendimiento
                CREATE INDEX idx_evaluaciones_alumno ON evaluaciones(alumno_id);
                CREATE INDEX idx_evaluaciones_materia_docente ON evaluaciones(materia_docente_id);
                CREATE INDEX idx_alumnos_matricula ON alumnos(matricula);
                CREATE INDEX idx_alumnos_generacion ON alumnos(generacion_id);
                CREATE INDEX idx_alumnos_grupo ON alumnos(grupo_id);
            ";
            
            $this->pdo->exec($sql);
            
            // Insertar datos de ejemplo
            $this->insertSampleData();
        }
    }
    
    private function insertSampleData() {
        try {
            // Insertar generación de ejemplo
            $this->pdo->exec("INSERT INTO generaciones (nombre, anio) VALUES ('Generación 2024', 2024)");
            $this->pdo->exec("INSERT INTO generaciones (nombre, anio) VALUES ('Generación 2025', 2025)");
            
            // Insertar grupos
            $this->pdo->exec("INSERT INTO grupos (nombre, generacion_id) VALUES ('Grupo A', 1)");
            $this->pdo->exec("INSERT INTO grupos (nombre, generacion_id) VALUES ('Grupo B', 1)");
            
            // Insertar materias
            $this->pdo->exec("INSERT INTO materias (nombre, clave) VALUES ('Matemáticas', 'MAT101')");
            $this->pdo->exec("INSERT INTO materias (nombre, clave) VALUES ('Programación Web', 'PROG101')");
            $this->pdo->exec("INSERT INTO materias (nombre, clave) VALUES ('Base de Datos', 'BD101')");
            
            // Insertar docentes
            $this->pdo->exec("INSERT INTO docentes (nombre, apellidos, email) VALUES ('Juan', 'Pérez García', 'juan.perez@ejemplo.com')");
            $this->pdo->exec("INSERT INTO docentes (nombre, apellidos, email) VALUES ('María', 'López Martínez', 'maria.lopez@ejemplo.com')");
            $this->pdo->exec("INSERT INTO docentes (nombre, apellidos, email) VALUES ('Carlos', 'Rodríguez Sánchez', 'carlos.rodriguez@ejemplo.com')");
            
            // Insertar alumnos de ejemplo
            $this->pdo->exec("INSERT INTO alumnos (matricula, nombre, apellidos, email, generacion_id, grupo_id) 
                             VALUES ('A001', 'Ana', 'González Flores', 'ana@ejemplo.com', 1, 1)");
            $this->pdo->exec("INSERT INTO alumnos (matricula, nombre, apellidos, email, generacion_id, grupo_id) 
                             VALUES ('A002', 'Luis', 'Martínez Ruiz', 'luis@ejemplo.com', 1, 1)");
            $this->pdo->exec("INSERT INTO alumnos (matricula, nombre, apellidos, email, generacion_id, grupo_id) 
                             VALUES ('A003', 'Laura', 'Fernández López', 'laura@ejemplo.com', 1, 2)");
            
            // Insertar asignaciones materia-docente
            $this->pdo->exec("INSERT INTO materia_docente (materia_id, docente_id, periodo) VALUES (1, 1, '2024-1')");
            $this->pdo->exec("INSERT INTO materia_docente (materia_id, docente_id, periodo) VALUES (2, 2, '2024-1')");
            $this->pdo->exec("INSERT INTO materia_docente (materia_id, docente_id, periodo) VALUES (3, 3, '2024-1')");
            
        } catch (PDOException $e) {
            // Las tablas ya tienen datos, ignorar error
            error_log("Nota: " . $e->getMessage());
        }
    }
}

// Función helper para obtener la conexión
function getDB() {
    return Database::getInstance()->getConnection();
}
?>