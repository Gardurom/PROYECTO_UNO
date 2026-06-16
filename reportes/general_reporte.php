<?php
// reportes/general_reporte.php
$db = getDB();

// Estadísticas generales
$total_alumnos = $db->query("SELECT COUNT(*) FROM alumnos WHERE activo = 1")->fetchColumn();
$total_docentes = $db->query("SELECT COUNT(*) FROM docentes WHERE activo = 1")->fetchColumn();
$total_materias = $db->query("SELECT COUNT(*) FROM materias WHERE activo = 1")->fetchColumn();
$total_evaluaciones = $db->query("SELECT COUNT(*) FROM evaluaciones")->fetchColumn();
$promedio_puntualidad = round($db->query("SELECT AVG(puntualidad_asistencia) FROM evaluaciones")->fetchColumn(), 2);
$promedio_resolucion = round($db->query("SELECT AVG(resolvio_dudas) FROM evaluaciones")->fetchColumn(), 2);

// Top 5 docentes mejor evaluados
$top_docentes = $db->query("
    SELECT d.nombre, d.apellidos, 
           AVG(e.puntualidad_asistencia) as avg_puntualidad,
           AVG(e.resolvio_dudas) as avg_resolucion,
           COUNT(e.id) as total_evaluaciones
    FROM docentes d
    JOIN materia_docente md ON d.id = md.docente_id
    JOIN evaluaciones e ON md.id = e.materia_docente_id
    GROUP BY d.id
    ORDER BY (avg_puntualidad + avg_resolucion) DESC
    LIMIT 5
")->fetchAll();
?>

<div class="row">
    <!-- Tarjetas de resumen -->
    <div class="col-md-3">
        <div class="stats-card text-center">
            <i class="fas fa-user-graduate fa-2x"></i>
            <div class="stats-number"><?php echo $total_alumnos; ?></div>
            <div>Total Alumnos</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stats-card text-center">
            <i class="fas fa-chalkboard-user fa-2x"></i>
            <div class="stats-number"><?php echo $total_docentes; ?></div>
            <div>Total Docentes</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stats-card text-center">
            <i class="fas fa-book fa-2x"></i>
            <div class="stats-number"><?php echo $total_materias; ?></div>
            <div>Total Materias</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stats-card text-center">
            <i class="fas fa-star fa-2x"></i>
            <div class="stats-number"><?php echo $total_evaluaciones; ?></div>
            <div>Evaluaciones Realizadas</div>
        </div>
    </div>
</div>

<div class="row mt-4">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5>Promedio General de Evaluaciones</h5>
            </div>
            <div class="card-body">
                <canvas id="promediosGenerales" height="250"></canvas>
                <div class="row mt-3">
                    <div class="col-md-6">
                        <div class="alert alert-success">
                            <strong>Asistencia y Puntualidad:</strong><br>
                            <span class="fs-1"><?php echo $promedio_puntualidad; ?></span>/9
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="alert alert-info">
                            <strong>Resolución de Dudas:</strong><br>
                            <span class="fs-1"><?php echo $promedio_resolucion; ?></span>/9
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5>Top 5 Docentes Mejor Evaluados</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Docente</th>
                                <th>Puntualidad</th>
                                <th>Resolución</th>
                                <th>Evaluaciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($top_docentes as $docente): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($docente['nombre'] . ' ' . $docente['apellidos']); ?></td>
                                <td>
                                    <div class="progress" style="height: 20px;">
                                        <div class="progress-bar bg-success" style="width: <?php echo ($docente['avg_puntualidad'] / 9) * 100; ?>%">
                                            <?php echo round($docente['avg_puntualidad'], 1); ?>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="progress" style="height: 20px;">
                                        <div class="progress-bar bg-info" style="width: <?php echo ($docente['avg_resolucion'] / 9) * 100; ?>%">
                                            <?php echo round($docente['avg_resolucion'], 1); ?>
                                        </div>
                                    </div>
                                </td>
                                <td><?php echo $docente['total_evaluaciones']; ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row mt-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h5>Distribución de Calificaciones</h5>
                <button class="btn btn-sm btn-success float-end" onclick="exportarReporte('general')">
                    <i class="fas fa-file-excel"></i> Exportar a Excel
                </button>
            </div>
            <div class="card-body">
                <canvas id="distribucionCalif" height="100"></canvas>
            </div>
        </div>
    </div>
</div>

<script>
// Gráfico de promedios generales
const ctx1 = document.getElementById('promediosGenerales').getContext('2d');
new Chart(ctx1, {
    type: 'bar',
    data: {
        labels: ['Asistencia y Puntualidad', 'Resolución de Dudas'],
        datasets: [{
            label: 'Calificación Promedio',
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
                    text: 'Calificación (0-9)'
                }
            }
        }
    }
});

// Gráfico de distribución
const ctx2 = document.getElementById('distribucionCalif').getContext('2d');
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

function exportarReporte(tipo) {
    window.location.href = `exportar_reporte.php?tipo=${tipo}`;
}
</script>