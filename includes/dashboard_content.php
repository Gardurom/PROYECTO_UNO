<?php
// pages/dashboard_content.php
$db = getDB();

// Obtener estadísticas
$total_alumnos = $db->query("SELECT COUNT(*) FROM alumnos WHERE activo = 1")->fetchColumn();
$total_docentes = $db->query("SELECT COUNT(*) FROM docentes WHERE activo = 1")->fetchColumn();
$total_materias = $db->query("SELECT COUNT(*) FROM materias WHERE activo = 1")->fetchColumn();
$total_evaluaciones = $db->query("SELECT COUNT(*) FROM evaluaciones")->fetchColumn();
$promedio_puntualidad = round($db->query("SELECT AVG(puntualidad_asistencia) FROM evaluaciones")->fetchColumn(), 2);
$promedio_resolucion = round($db->query("SELECT AVG(resolvio_dudas) FROM evaluaciones")->fetchColumn(), 2);

// Obtener últimas evaluaciones
$ultimas_evaluaciones = $db->query("
    SELECT e.*, 
           a.nombre as alumno_nombre, a.apellidos as alumno_apellidos,
           m.nombre as materia_nombre, 
           d.nombre as docente_nombre, d.apellidos as docente_apellidos,
           g.nombre as generacion_nombre
    FROM evaluaciones e
    JOIN alumnos a ON e.alumno_id = a.id
    JOIN materia_docente md ON e.materia_docente_id = md.id
    JOIN materias m ON md.materia_id = m.id
    JOIN docentes d ON md.docente_id = d.id
    JOIN generaciones g ON a.generacion_id = g.id
    ORDER BY e.fecha_evaluacion DESC
    LIMIT 10
")->fetchAll();
?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col">
            <h2><i class="fas fa-tachometer-alt"></i> Dashboard de Evaluación Docente</h2>
            <p class="text-muted">Bienvenido al sistema de evaluación de docentes</p>
        </div>
    </div>

    <!-- Tarjetas de estadísticas -->
    <div class="row">
        <div class="col-md-3">
            <div class="stats-card text-center">
                <i class="fas fa-user-graduate"></i>
                <div class="stats-number"><?php echo $total_alumnos; ?></div>
                <div>Alumnos Registrados</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card text-center">
                <i class="fas fa-chalkboard-user"></i>
                <div class="stats-number"><?php echo $total_docentes; ?></div>
                <div>Docentes</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card text-center">
                <i class="fas fa-book"></i>
                <div class="stats-number"><?php echo $total_materias; ?></div>
                <div>Materias</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card text-center">
                <i class="fas fa-star"></i>
                <div class="stats-number"><?php echo $total_evaluaciones; ?></div>
                <div>Evaluaciones</div>
            </div>
        </div>
    </div>

    <!-- Gráficas -->
    <div class="row mt-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5><i class="fas fa-chart-line"></i> Promedio de Evaluaciones</h5>
                </div>
                <div class="card-body">
                    <canvas id="promediosChart" height="200"></canvas>
                    <hr>
                    <div class="row">
                        <div class="col-md-6">
                            <label>Asistencia y Puntualidad</label>
                            <div class="progress">
                                <div class="progress-bar bg-success" style="width: <?php echo ($promedio_puntualidad / 9) * 100; ?>%">
                                    <?php echo $promedio_puntualidad; ?>/9
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label>Resolución de Dudas</label>
                            <div class="progress">
                                <div class="progress-bar bg-info" style="width: <?php echo ($promedio_resolucion / 9) * 100; ?>%">
                                    <?php echo $promedio_resolucion; ?>/9
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5><i class="fas fa-chart-pie"></i> Distribución de Evaluaciones</h5>
                </div>
                <div class="card-body">
                    <canvas id="distribucionChart" height="200"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Últimas evaluaciones -->
    <div class="row mt-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5><i class="fas fa-clock"></i> Últimas Evaluaciones Realizadas</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Fecha</th>
                                    <th>Alumno</th>
                                    <th>Docente</th>
                                    <th>Materia</th>
                                    <th>Generación</th>
                                    <th>Puntualidad</th>
                                    <th>Resolución</th>
                                    <th>Comentario</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($ultimas_evaluaciones as $eval): ?>
                                <tr>
                                    <td><?php echo date('d/m/Y H:i', strtotime($eval['fecha_evaluacion'])); ?></td>
                                    <td><?php echo htmlspecialchars($eval['alumno_nombre'] . ' ' . $eval['alumno_apellidos']); ?></td>
                                    <td><?php echo htmlspecialchars($eval['docente_nombre'] . ' ' . $eval['docente_apellidos']); ?></td>
                                    <td><?php echo htmlspecialchars($eval['materia_nombre']); ?></td>
                                    <td><?php echo htmlspecialchars($eval['generacion_nombre']); ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo $eval['puntualidad_asistencia'] >= 7 ? 'success' : ($eval['puntualidad_asistencia'] >= 5 ? 'warning' : 'danger'); ?>">
                                            <?php echo $eval['puntualidad_asistencia']; ?>/9
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?php echo $eval['resolvio_dudas'] >= 7 ? 'success' : ($eval['resolvio_dudas'] >= 5 ? 'warning' : 'danger'); ?>">
                                            <?php echo $eval['resolvio_dudas']; ?>/9
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars(substr($eval['comentario'] ?? '', 0, 50)); ?></td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if(empty($ultimas_evaluaciones)): ?>
                                <tr>
                                    <td colspan="8" class="text-center">No hay evaluaciones registradas</td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Acciones rápidas -->
    <div class="row mt-4 mb-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5><i class="fas fa-bolt"></i> Acciones Rápidas</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <a href="?page=alumnos" class="btn btn-custom w-100 mb-2">
                                <i class="fas fa-user-graduate"></i> Gestionar Alumnos
                            </a>
                        </div>
                        <div class="col-md-3">
                            <a href="?page=docentes" class="btn btn-custom w-100 mb-2">
                                <i class="fas fa-chalkboard-user"></i> Gestionar Docentes
                            </a>
                        </div>
                        <div class="col-md-3">
                            <a href="?page=evaluaciones" class="btn btn-custom w-100 mb-2">
                                <i class="fas fa-star"></i> Realizar Evaluación
                            </a>
                        </div>
                        <div class="col-md-3">
                            <a href="?page=carga_masiva" class="btn btn-custom w-100 mb-2">
                                <i class="fas fa-upload"></i> Carga Masiva
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Gráfico de promedios
const ctx = document.getElementById('promediosChart').getContext('2d');
new Chart(ctx, {
    type: 'bar',
    data: {
        labels: ['Asistencia y Puntualidad', 'Resolución de Dudas'],
        datasets: [{
            label: 'Promedio / 9',
            data: [<?php echo $promedio_puntualidad; ?>, <?php echo $promedio_resolucion; ?>],
            backgroundColor: ['#28a745', '#17a2b8'],
            borderColor: ['#28a745', '#17a2b8'],
            borderWidth: 1
        }]
    },
    options: {
        scales: {
            y: {
                beginAtZero: true,
                max: 9,
                title: {
                    display: true,
                    text: 'Calificación'
                }
            }
        },
        plugins: {
            legend: {
                display: false
            }
        }
    }
});

// Gráfico de distribución
const ctx2 = document.getElementById('distribucionChart').getContext('2d');
new Chart(ctx2, {
    type: 'doughnut',
    data: {
        labels: ['Excelente (7-9)', 'Regular (4-6)', 'Necesita Mejorar (0-3)'],
        datasets: [{
            data: [
                <?php 
                $excelente = $db->query("SELECT COUNT(*) FROM evaluaciones WHERE puntualidad_asistencia >= 7 AND resolvio_dudas >= 7")->fetchColumn();
                $regular = $db->query("SELECT COUNT(*) FROM evaluaciones WHERE (puntualidad_asistencia BETWEEN 4 AND 6) OR (resolvio_dudas BETWEEN 4 AND 6)")->fetchColumn();
                $necesita = $db->query("SELECT COUNT(*) FROM evaluaciones WHERE puntualidad_asistencia <= 3 OR resolvio_dudas <= 3")->fetchColumn();
                echo "$excelente, $regular, $necesita";
                ?>
            ],
            backgroundColor: ['#28a745', '#ffc107', '#dc3545']
        }]
    },
    options: {
        plugins: {
            legend: {
                position: 'bottom'
            }
        }
    }
});
</script>