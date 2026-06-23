<?php
// reportes/materias_reporte.php
$db = getDB();

// Obtener todas las materias con sus estadísticas
$materias = $db->query("
    SELECT 
        m.id,
        m.nombre,
        m.clave,
        COUNT(DISTINCT md.docente_id) as total_docentes,
        COUNT(DISTINCT e.id) as total_evaluaciones,
        AVG(e.puntualidad_asistencia) as avg_puntualidad,
        AVG(e.resolvio_dudas) as avg_resolucion,
        GROUP_CONCAT(DISTINCT d.nombre || ' ' || d.apellidos) as docentes,
        GROUP_CONCAT(DISTINCT md.periodo) as periodos
    FROM materias m
    LEFT JOIN materia_docente md ON m.id = md.materia_id AND md.activo = 1
    LEFT JOIN docentes d ON md.docente_id = d.id
    LEFT JOIN evaluaciones e ON md.id = e.materia_docente_id
    WHERE m.activo = 1
    GROUP BY m.id
    ORDER BY (avg_puntualidad + avg_resolucion) DESC
")->fetchAll();

// Obtener top 5 materias
$top5 = array_slice($materias, 0, 5);

// Estadísticas por materia
$total_materias = count($materias);
$materias_con_evaluaciones = 0;
$promedio_general = 0;

foreach ($materias as $m) {
    if ($m['total_evaluaciones'] > 0) {
        $materias_con_evaluaciones++;
        $promedio_general += ($m['avg_puntualidad'] + $m['avg_resolucion']) / 2;
    }
}
$promedio_general = $materias_con_evaluaciones > 0 ? round($promedio_general / $materias_con_evaluaciones, 2) : 0;
?>

<div class="card">
    <div class="card-header">
        <div class="row">
            <div class="col">
                <h5><i class="fas fa-book"></i> Reporte por Materia</h5>
            </div>
            <div class="col text-end">
                <button class="btn btn-sm btn-success" onclick="exportarReporte('materias')">
                    <i class="fas fa-file-excel"></i> Exportar a Excel
                </button>
                <button class="btn btn-sm btn-primary" onclick="imprimirReporte()">
                    <i class="fas fa-print"></i> Imprimir
                </button>
            </div>
        </div>
    </div>
    <div class="card-body">
        <!-- Resumen -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="stats-card text-center">
                    <i class="fas fa-book fa-2x"></i>
                    <div class="stats-number"><?php echo $total_materias; ?></div>
                    <div>Total Materias</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stats-card text-center">
                    <i class="fas fa-star fa-2x"></i>
                    <div class="stats-number"><?php echo $materias_con_evaluaciones; ?></div>
                    <div>Materias con Evaluaciones</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stats-card text-center">
                    <i class="fas fa-chart-line fa-2x"></i>
                    <div class="stats-number"><?php echo $promedio_general; ?></div>
                    <div>Promedio General</div>
                </div>
            </div>
        </div>

        <!-- Gráficos -->
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h6>Top 5 Materias Mejor Evaluadas</h6>
                    </div>
                    <div class="card-body">
                        <canvas id="topMateriasChart" height="250"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h6>Desempeño por Materia</h6>
                    </div>
                    <div class="card-body">
                        <canvas id="desempenioMateriasChart" height="250"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabla -->
        <div class="table-responsive">
            <table class="table table-striped datatable">
                <thead>
                    <tr>
                        <th>Clave</th>
                        <th>Materia</th>
                        <th>Docentes</th>
                        <th>Periodos</th>
                        <th>Evaluaciones</th>
                        <th>Puntualidad</th>
                        <th>Resolución</th>
                        <th>Promedio</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($materias as $materia):
                        $promedio = $materia['total_evaluaciones'] > 0 ? 
                            round(($materia['avg_puntualidad'] + $materia['avg_resolucion']) / 2, 2) : 0;
                        $color = $promedio >= 7 ? 'success' : ($promedio >= 4 ? 'warning' : 'danger');
                    ?>
                    <tr>
                        <td><code><?php echo htmlspecialchars($materia['clave']); ?></code></td>
                        <td><strong><?php echo htmlspecialchars($materia['nombre']); ?></strong></td>
                        <td><?php echo htmlspecialchars($materia['docentes'] ?: 'Sin asignar'); ?></td>
                        <td><?php echo htmlspecialchars($materia['periodos'] ?: 'N/A'); ?></td>
                        <td><span class="badge bg-info"><?php echo $materia['total_evaluaciones']; ?></span></td>
                        <td>
                            <?php if($materia['total_evaluaciones'] > 0): ?>
                            <div class="progress" style="height: 20px;">
                                <div class="progress-bar bg-success" style="width: <?php echo ($materia['avg_puntualidad'] / 9) * 100; ?>%">
                                    <?php echo round($materia['avg_puntualidad'], 1); ?>/9
                                </div>
                            </div>
                            <?php else: ?>
                            <span class="text-muted">Sin evaluaciones</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if($materia['total_evaluaciones'] > 0): ?>
                            <div class="progress" style="height: 20px;">
                                <div class="progress-bar bg-info" style="width: <?php echo ($materia['avg_resolucion'] / 9) * 100; ?>%">
                                    <?php echo round($materia['avg_resolucion'], 1); ?>/9
                                </div>
                            </div>
                            <?php else: ?>
                            <span class="text-muted">Sin evaluaciones</span>
                            <?php endif; ?>
                        </td>
                        <td><strong><?php echo $promedio; ?></strong>/9</td>
                        <td>
                            <span class="badge bg-<?php echo $color; ?>">
                                <?php echo $promedio >= 7 ? '🌟 Excelente' : ($promedio >= 4 ? '📊 Regular' : '⚠️ Mejorar'); ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
// Top 5 Materias
const ctx1 = document.getElementById('topMateriasChart').getContext('2d');
new Chart(ctx1, {
    type: 'bar',
    data: {
        labels: [<?php 
            $labels = array_map(function($m) {
                return "'" . addslashes($m['nombre']) . "'";
            }, $top5);
            echo implode(', ', $labels);
        ?>],
        datasets: [{
            label: 'Promedio General',
            data: [<?php 
                $data = array_map(function($m) {
                    return $m['total_evaluaciones'] > 0 ? 
                        round(($m['avg_puntualidad'] + $m['avg_resolucion']) / 2, 2) : 0;
                }, $top5);
                echo implode(', ', $data);
            ?>],
            backgroundColor: ['#28a745', '#17a2b8', '#ffc107', '#dc3545', '#6610f2'],
            borderColor: ['#28a745', '#17a2b8', '#ffc107', '#dc3545', '#6610f2'],
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
        },
        plugins: {
            legend: {
                display: false
            }
        }
    }
});

// Desempeño por materia
const ctx2 = document.getElementById('desempenioMateriasChart').getContext('2d');
new Chart(ctx2, {
    type: 'radar',
    data: {
        labels: ['Puntualidad', 'Resolución', 'Promedio'],
        datasets: [{
            label: 'Promedio General',
            data: [<?php 
                $promedio_puntualidad = $db->query("SELECT AVG(puntualidad_asistencia) FROM evaluaciones")->fetchColumn();
                $promedio_resolucion = $db->query("SELECT AVG(resolvio_dudas) FROM evaluaciones")->fetchColumn();
                echo round($promedio_puntualidad, 2) . ', ' . round($promedio_resolucion, 2) . ', ' . $promedio_general;
            ?>],
            backgroundColor: 'rgba(102, 126, 234, 0.2)',
            borderColor: '#667eea',
            pointBackgroundColor: '#667eea'
        }]
    },
    options: {
        scales: {
            r: {
                beginAtZero: true,
                max: 9,
                ticks: {
                    stepSize: 1
                }
            }
        }
    }
});

function exportarReporte(tipo) {
    window.location.href = `exportar_reporte.php?tipo=${tipo}`;
}

function imprimirReporte() {
    window.print();
}
</script>