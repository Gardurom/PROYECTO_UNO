<?php
// pages/evaluaciones_content.php
$db = getDB();
$mensaje = '';
$error = '';

// Si se envía una evaluación
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_evaluacion'])) {
    $alumno_id = intval($_POST['alumno_id']);
    $materia_docente_id = intval($_POST['materia_docente_id']);
    $puntualidad = intval($_POST['puntualidad']);
    $resolucion = intval($_POST['resolucion']);
    $comentario = $_POST['comentario'] ?? '';
    
    try {
        $stmt = $db->prepare("
            INSERT INTO evaluaciones (alumno_id, materia_docente_id, puntualidad_asistencia, resolvio_dudas, comentario, ip_address, user_agent)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $alumno_id, 
            $materia_docente_id, 
            $puntualidad, 
            $resolucion, 
            $comentario,
            $_SERVER['REMOTE_ADDR'],
            $_SERVER['HTTP_USER_AGENT']
        ]);
        $mensaje = "¡Evaluación guardada exitosamente! Gracias por tu participación.";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'UNIQUE constraint failed') !== false) {
            $error = "Ya has evaluado esta materia anteriormente.";
        } else {
            $error = "Error al guardar la evaluación: " . $e->getMessage();
        }
    }
}

// Obtener lista de alumnos para seleccionar (en producción, esto vendría de la sesión)
$alumnos = $db->query("SELECT id, matricula, nombre, apellidos FROM alumnos WHERE activo = 1 ORDER BY nombre")->fetchAll();
$alumno_seleccionado = isset($_GET['alumno_id']) ? intval($_GET['alumno_id']) : ($alumnos[0]['id'] ?? 0);

// Obtener materias pendientes de evaluar para el alumno seleccionado
$materias_pendientes = [];
if ($alumno_seleccionado) {
    $stmt = $db->prepare("
        SELECT 
            md.id as materia_docente_id,
            m.nombre as materia_nombre,
            m.clave,
            d.nombre as docente_nombre,
            d.apellidos as docente_apellidos,
            g.nombre as generacion_nombre,
            gr.nombre as grupo_nombre,
            md.periodo
        FROM materia_docente md
        JOIN materias m ON md.materia_id = m.id
        JOIN docentes d ON md.docente_id = d.id
        JOIN alumnos a ON a.id = ?
        JOIN grupos gr ON a.grupo_id = gr.id
        JOIN generaciones g ON gr.generacion_id = g.id
        WHERE md.activo = 1
        AND a.activo = 1
        AND NOT EXISTS (
            SELECT 1 FROM evaluaciones e 
            WHERE e.materia_docente_id = md.id 
            AND e.alumno_id = a.id
        )
    ");
    $stmt->execute([$alumno_seleccionado]);
    $materias_pendientes = $stmt->fetchAll();
}
?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col">
            <h2><i class="fas fa-star"></i> Evaluación Docente</h2>
            <p class="text-muted">Evalúa a tus docentes de manera honesta y constructiva</p>
        </div>
    </div>
    
    <?php if ($mensaje): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle"></i> <?php echo $mensaje; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle"></i> <?php echo $error; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <div class="row">
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5><i class="fas fa-user-graduate"></i> Seleccionar Alumno</h5>
                </div>
                <div class="card-body">
                    <select class="form-control" id="selectorAlumno" onchange="location.href='?page=evaluaciones&alumno_id='+this.value">
                        <option value="">Seleccione un alumno</option>
                        <?php foreach($alumnos as $alumno): ?>
                        <option value="<?php echo $alumno['id']; ?>" <?php echo $alumno_seleccionado == $alumno['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($alumno['matricula'] . ' - ' . $alumno['nombre'] . ' ' . $alumno['apellidos']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <div class="card mt-3">
                <div class="card-header">
                    <h5><i class="fas fa-info-circle"></i> Instrucciones</h5>
                </div>
                <div class="card-body">
                    <p>Por favor, evalúa a cada docente considerando:</p>
                    <ul>
                        <li><strong>Asistencia y puntualidad (0-9):</strong> ¿El docente asistió regularmente a clases y fue puntual?</li>
                        <li><strong>Resolución de dudas (0-9):</strong> ¿El docente resolvió tus dudas de manera clara y oportuna?</li>
                    </ul>
                    <div class="alert alert-warning mt-3">
                        <i class="fas fa-lock"></i> <strong>Confidencialidad:</strong> Esta evaluación es anónima y confidencial.
                    </div>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> <strong>Escala de calificación:</strong><br>
                        0-3: Necesita mejorar<br>
                        4-6: Regular<br>
                        7-9: Excelente
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-8">
            <?php if (count($materias_pendientes) > 0): ?>
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-book"></i> Materias por Evaluar</h5>
                    </div>
                    <div class="card-body">
                        <?php foreach ($materias_pendientes as $materia): ?>
                            <div class="card mb-3 border-primary">
                                <div class="card-body">
                                    <form method="POST" class="evaluacion-form" onsubmit="return confirm('¿Estás seguro de enviar esta evaluación?');">
                                        <input type="hidden" name="alumno_id" value="<?php echo $alumno_seleccionado; ?>">
                                        <input type="hidden" name="materia_docente_id" value="<?php echo $materia['materia_docente_id']; ?>">
                                        
                                        <div class="row">
                                            <div class="col-md-8">
                                                <h5><?php echo htmlspecialchars($materia['materia_nombre']); ?></h5>
                                                <p class="text-muted mb-1">
                                                    <i class="fas fa-chalkboard-user"></i> Docente: <?php echo htmlspecialchars($materia['docente_nombre'] . ' ' . $materia['docente_apellidos']); ?>
                                                </p>
                                                <p class="text-muted mb-1">
                                                    <i class="fas fa-users"></i> Generación: <?php echo htmlspecialchars($materia['generacion_nombre']); ?> - Grupo: <?php echo htmlspecialchars($materia['grupo_nombre']); ?>
                                                </p>
                                                <p class="text-muted">
                                                    <i class="fas fa-calendar"></i> Periodo: <?php echo htmlspecialchars($materia['periodo']); ?>
                                                </p>
                                            </div>
                                            <div class="col-md-4 text-end">
                                                <span class="badge bg-primary">Pendiente</span>
                                            </div>
                                        </div>
                                        
                                        <hr>
                                        
                                        <div class="mb-4">
                                            <label class="form-label fw-bold">
                                                <i class="fas fa-clock"></i> 1. Asistencia y puntualidad del docente
                                            </label>
                                            <div class="rating" data-rating-name="puntualidad_<?php echo $materia['materia_docente_id']; ?>">
                                                <?php for($i = 9; $i >= 0; $i--): ?>
                                                    <input type="radio" name="puntualidad" value="<?php echo $i; ?>" 
                                                           id="puntualidad_<?php echo $materia['materia_docente_id']; ?>_<?php echo $i; ?>" required>
                                                    <label for="puntualidad_<?php echo $materia['materia_docente_id']; ?>_<?php echo $i; ?>" 
                                                           title="<?php echo $i; ?> puntos"><?php echo $i; ?></label>
                                                <?php endfor; ?>
                                            </div>
                                            <small class="text-muted">0 = Muy malo, 9 = Excelente</small>
                                        </div>
                                        
                                        <div class="mb-4">
                                            <label class="form-label fw-bold">
                                                <i class="fas fa-question-circle"></i> 2. Resolución de dudas y claridad en explicaciones
                                            </label>
                                            <div class="rating" data-rating-name="resolucion_<?php echo $materia['materia_docente_id']; ?>">
                                                <?php for($i = 9; $i >= 0; $i--): ?>
                                                    <input type="radio" name="resolucion" value="<?php echo $i; ?>" 
                                                           id="resolucion_<?php echo $materia['materia_docente_id']; ?>_<?php echo $i; ?>" required>
                                                    <label for="resolucion_<?php echo $materia['materia_docente_id']; ?>_<?php echo $i; ?>" 
                                                           title="<?php echo $i; ?> puntos"><?php echo $i; ?></label>
                                                <?php endfor; ?>
                                            </div>
                                            <small class="text-muted">0 = Muy malo, 9 = Excelente</small>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label class="form-label">Comentarios adicionales (opcional)</label>
                                            <textarea class="form-control" name="comentario" rows="3" 
                                                      placeholder="Escribe aquí tus comentarios sobre el docente..."></textarea>
                                        </div>
                                        
                                        <button type="submit" name="submit_evaluacion" class="btn btn-custom">
                                            <i class="fas fa-paper-plane"></i> Enviar Evaluación
                                        </button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php else: ?>
                <div class="card">
                    <div class="card-body text-center">
                        <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                        <h4>No hay materias pendientes</h4>
                        <p class="text-muted">¡Has completado todas tus evaluaciones! Gracias por tu participación.</p>
                        <a href="?page=dashboard" class="btn btn-custom">
                            <i class="fas fa-home"></i> Volver al Dashboard
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.rating {
    display: flex;
    flex-direction: row-reverse;
    justify-content: flex-end;
    gap: 8px;
}
.rating input {
    display: none;
}
.rating label {
    font-size: 2rem;
    padding: 0 5px;
    color: #ddd;
    cursor: pointer;
    transition: all 0.2s;
    background: #f8f9fa;
    border-radius: 5px;
    width: 50px;
    text-align: center;
}
.rating input:checked ~ label {
    color: #ffc107;
    background: #fff3cd;
}
.rating label:hover,
.rating label:hover ~ label {
    color: #ffc107;
    transform: scale(1.1);
}
</style>

<script>
// Agregar animación a las calificaciones
document.querySelectorAll('.rating label').forEach(label => {
    label.addEventListener('mouseenter', function() {
        this.style.transform = 'scale(1.1)';
    });
    label.addEventListener('mouseleave', function() {
        this.style.transform = 'scale(1)';
    });
});
</script>