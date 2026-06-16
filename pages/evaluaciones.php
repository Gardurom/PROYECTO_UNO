<?php
// pages/evaluaciones.php
require_once 'config/database.php';
$db = Database::getInstance()->getConnection();

// Verificar si se envió una evaluación
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_evaluacion'])) {
    $alumno_id = $_POST['alumno_id'];
    $materia_docente_id = $_POST['materia_docente_id'];
    $puntualidad = (int)$_POST['puntualidad'];
    $resolucion = (int)$_POST['resolucion'];
    $comentario = $_POST['comentario'] ?? '';
    
    try {
        $stmt = $db->prepare("
            INSERT INTO evaluaciones (alumno_id, materia_docente_id, puntualidad_asistencia, resolvio_dudas, comentario, ip_address, user_agent)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$alumno_id, $materia_docente_id, $puntualidad, $resolucion, $comentario, 
                       $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT']]);
        $success = "¡Evaluación guardada exitosamente!";
    } catch (PDOException $e) {
        $error = "Ya has evaluado esta materia anteriormente.";
    }
}

// Obtener materias pendientes de evaluar para el alumno (ejemplo con alumno específico)
$alumno_id = $_GET['alumno_id'] ?? 1; // En producción, obtener del login
?>

<div class="container">
    <div class="row mb-4">
        <div class="col">
            <h2><i class="fas fa-star"></i> Evaluación Docente</h2>
            <p class="text-muted">Evalúa a tus docentes de manera honesta y constructiva</p>
        </div>
    </div>
    
    <?php if (isset($success)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo $success; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <?php if (isset($error)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php echo $error; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5>Materias por Evaluar</h5>
                </div>
                <div class="card-body">
                    <?php
                    $sql = "
                        SELECT 
                            md.id as materia_docente_id,
                            m.nombre as materia,
                            CONCAT(d.nombre, ' ', d.apellidos) as docente,
                            g.nombre as generacion,
                            gr.nombre as grupo
                        FROM materia_docente md
                        JOIN materias m ON md.materia_id = m.id
                        JOIN docentes d ON md.docente_id = d.id
                        JOIN alumnos a ON a.id = ?
                        JOIN grupos gr ON a.grupo_id = gr.id
                        JOIN generaciones g ON gr.generacion_id = g.id
                        WHERE md.activo = 1
                        AND NOT EXISTS (
                            SELECT 1 FROM evaluaciones e 
                            WHERE e.materia_docente_id = md.id 
                            AND e.alumno_id = a.id
                        )
                    ";
                    $stmt = $db->prepare($sql);
                    $stmt->execute([$alumno_id]);
                    $materias = $stmt->fetchAll();
                    ?>
                    
                    <?php if (count($materias) > 0): ?>
                        <?php foreach ($materias as $materia): ?>
                            <div class="card mb-3">
                                <div class="card-body">
                                    <form method="POST" class="evaluacion-form">
                                        <input type="hidden" name="alumno_id" value="<?php echo $alumno_id; ?>">
                                        <input type="hidden" name="materia_docente_id" value="<?php echo $materia['materia_docente_id']; ?>">
                                        
                                        <h5><?php echo htmlspecialchars($materia['materia']); ?></h5>
                                        <p class="text-muted">
                                            Docente: <?php echo htmlspecialchars($materia['docente']); ?><br>
                                            Generación: <?php echo htmlspecialchars($materia['generacion']); ?> - Grupo: <?php echo htmlspecialchars($materia['grupo']); ?>
                                        </p>
                                        
                                        <div class="mb-4">
                                            <label class="form-label fw-bold">1. Asistencia y puntualidad del docente</label>
                                            <div class="rating">
                                                <?php for($i = 9; $i >= 0; $i--): ?>
                                                    <input type="radio" name="puntualidad" value="<?php echo $i; ?>" id="puntualidad_<?php echo $materia['materia_docente_id']; ?>_<?php echo $i; ?>" required>
                                                    <label for="puntualidad_<?php echo $materia['materia_docente_id']; ?>_<?php echo $i; ?>"><?php echo $i; ?></label>
                                                <?php endfor; ?>
                                            </div>
                                            <small class="text-muted">0 = Muy malo, 9 = Excelente</small>
                                        </div>
                                        
                                        <div class="mb-4">
                                            <label class="form-label fw-bold">2. Resolución de dudas y claridad en explicaciones</label>
                                            <div class="rating">
                                                <?php for($i = 9; $i >= 0; $i--): ?>
                                                    <input type="radio" name="resolucion" value="<?php echo $i; ?>" id="resolucion_<?php echo $materia['materia_docente_id']; ?>_<?php echo $i; ?>" required>
                                                    <label for="resolucion_<?php echo $materia['materia_docente_id']; ?>_<?php echo $i; ?>"><?php echo $i; ?></label>
                                                <?php endfor; ?>
                                            </div>
                                            <small class="text-muted">0 = Muy malo, 9 = Excelente</small>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label class="form-label">Comentarios adicionales (opcional)</label>
                                            <textarea class="form-control" name="comentario" rows="3" placeholder="Escribe aquí tus comentarios sobre el docente..."></textarea>
                                        </div>
                                        
                                        <button type="submit" name="submit_evaluacion" class="btn btn-custom">
                                            <i class="fas fa-paper-plane"></i> Enviar Evaluación
                                        </button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> No tienes materias pendientes por evaluar. ¡Gracias por tu participación!
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5>Instrucciones</h5>
                </div>
                <div class="card-body">
                    <p>Por favor, evalúa a cada docente considerando:</p>
                    <ul>
                        <li><strong>Asistencia y puntualidad:</strong> ¿El docente asistió regularmente a clases y fue puntual?</li>
                        <li><strong>Resolución de dudas:</strong> ¿El docente resolvió tus dudas de manera clara y oportuna?</li>
                    </ul>
                    <p>Tu opinión es muy importante para mejorar la calidad educativa.</p>
                    <div class="alert alert-warning">
                        <i class="fas fa-lock"></i> Esta evaluación es anónima y confidencial.
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.rating {
    display: flex;
    flex-direction: row-reverse;
    justify-content: flex-end;
    gap: 5px;
}
.rating input {
    display: none;
}
.rating label {
    font-size: 2rem;
    padding: 0 5px;
    color: #ddd;
    cursor: pointer;
    transition: color 0.2s;
}
.rating input:checked ~ label {
    color: #ffc107;
}
.rating label:hover,
.rating label:hover ~ label {
    color: #ffc107;
}
</style>