<?php
// includes/VisitasManager.php
class VisitasManager {
    private $db;
    private $table = 'visitas';
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Registrar una nueva visita
     * @param array $data Datos de la visita (opcional)
     */
    public function registrarVisita($data = []) {
        try {
            // Si no se proporcionan datos, usar valores por defecto
            if (empty($data)) {
                $data = [
                    'pagina' => $_SERVER['REQUEST_URI'] ?? '/',
                    'ip' => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
                    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
                    'session_id' => session_id(),
                    'usuario_id' => $_SESSION['user_id'] ?? null
                ];
            }
            
            $sql = "INSERT INTO {$this->table} (
                        pagina, 
                        ip, 
                        user_agent, 
                        session_id,
                        usuario_id,
                        fecha_visita
                    ) VALUES (?, ?, ?, ?, ?, datetime('now'))";
            
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([
                $data['pagina'] ?? '',
                $data['ip'] ?? $_SERVER['REMOTE_ADDR'] ?? '',
                $data['user_agent'] ?? $_SERVER['HTTP_USER_AGENT'] ?? '',
                $data['session_id'] ?? session_id(),
                $data['usuario_id'] ?? null
            ]);
            
            return $result;
        } catch (PDOException $e) {
            error_log("Error al registrar visita: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtener estadísticas de visitas
     */
    public function getEstadisticas($periodo = 'hoy') {
        try {
            $sql = "SELECT 
                        COUNT(*) as total,
                        COUNT(DISTINCT ip) as visitantes_unicos,
                        COUNT(DISTINCT session_id) as sesiones
                    FROM {$this->table}";
            
            if ($periodo == 'hoy') {
                $sql .= " WHERE date(fecha_visita) = date('now')";
            } elseif ($periodo == 'semana') {
                $sql .= " WHERE fecha_visita >= datetime('now', '-7 days')";
            } elseif ($periodo == 'mes') {
                $sql .= " WHERE fecha_visita >= datetime('now', '-30 days')";
            }
            
            $stmt = $this->db->query($sql);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error al obtener estadísticas: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Obtener visitas por página
     */
    public function getVisitasPorPagina($limite = 10) {
        try {
            $sql = "SELECT 
                        pagina,
                        COUNT(*) as total,
                        COUNT(DISTINCT ip) as visitantes
                    FROM {$this->table}
                    GROUP BY pagina
                    ORDER BY total DESC
                    LIMIT ?";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$limite]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error al obtener visitas por página: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtener últimas visitas
     */
    public function getUltimasVisitas($limite = 10) {
        try {
            $sql = "SELECT 
                        v.*,
                        u.username as usuario_nombre
                    FROM {$this->table} v
                    LEFT JOIN usuarios u ON v.usuario_id = u.id
                    ORDER BY v.fecha_visita DESC
                    LIMIT ?";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$limite]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error al obtener últimas visitas: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Limpiar visitas antiguas
     */
    public function limpiarVisitasAntiguas($dias = 30) {
        try {
            $sql = "DELETE FROM {$this->table} 
                    WHERE fecha_visita < datetime('now', '-' || ? || ' days')";
            
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$dias]);
        } catch (PDOException $e) {
            error_log("Error al limpiar visitas: " . $e->getMessage());
            return false;
        }
    }
}