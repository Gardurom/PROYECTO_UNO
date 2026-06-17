<?php
// reportes/generaciones_reporte.php
$db = getDB();

// Obtener todas las generaciones
$generaciones = $db->query("
    SELECT 
        g.id,
        g.nombre,
        g.anio,
        COUNT(DISTINCT a.id) as total_alumnos,
        COUNT(DISTINCT e.id) as total_evaluaciones,
        AVG(e.puntualidad_asistencia) as avg_puntualidad,
        AVG(e.resolvio_dudas) as avg_resolucion,
        GROUP_CONCAT(DISTINCT gr.nombre) as grupos,
        COUNT(DISTINCT gr.id) as total_grupos
    FROM generaciones g
    LEFT JOIN alumnos a ON g.id = a.generacion_id AND a.activo = 1
    LEFT JOIN grupos gr ON g.id = gr.generacion_id AND gr.activo = 1
    LEFT JOIN evaluaciones e ON a.id = e.alumno_id
    WHERE g.activo = 1
    GROUP BY g.id
    ORDER BY g.anio DESC
")->fetchAll();

// Calcular promedios por generación
$datos_grafico = [];
$labels = [];
$colores = ['#667eea', '#764ba2', '#28a745', '#ffc107', '#dc3545'];

foreach ($generaciones as $gen) {
    $labels[] = $gen['nombre'];
    $promedio = $gen['total_evaluaciones'] > 0 ? 
        round(($gen['avg_puntualidad'] + $gen['avg_resolucion']) / 2, 2) : 0;
    $datos_grafico[] = $promedio;
}
?>

<div class="card">
    <div class="card-header">
        <div class="row">
            <div class="col">
                <h5><i class="fas fa-users"></i> Reporte por Generación</h5>
            </div>
            <div class="col text-end">
                <button class="btn btn-sm btn-success" onclick="exportarReporte('generaciones')">
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
            <div class="col-md-3">
                <div class="stats-card text-center">
                    <i class="fas fa-calendar fa-2x"></i>
                    <div class="stats-number"><?php echo count($generaciones); ?></div>
                    <div>Total Generaciones</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card text-center">
                    <i class="fas fa-user-graduate fa-2x"></i>
                    <div class="stats-number">
                        <?php echo array_sum(array_column($generaciones, 'total_alumnos')); ?>
                    </div>
                    <div>Total Alumnos</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card text-center">
                    <i class="fas fa-star fa-2x"></i>
                    <div class="stats-number">
                        <?php echo array_sum(array_column($generaciones, 'total_evaluaciones')); ?>
                    </div>
                    <div>Total Evaluaciones</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card text-center">
                    <i class="fas fa-chart-line fa-2x"></i>
                    <div class="stats-number">
                        <?php 
                        $promedio_total = array_sum($datos_grafico) / count(array_filter($datos_grafico));
                        echo round($promedio_total, 2);
                        ?>
                    </div>
                    <div>Promedio General</div>
                </div>
            </div>
        </div>

        <!-- Gráficos -->
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h6>Promedio por Generación</h6>
                    </div>
                    <div class="card-body">
                        <canvas id="generacionesChart" height="250"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h6>Distribución de Alumnos y Evaluaciones</h6>
                    </div>
                    <div class="card-body">
                        <canvas id="distribucionGenChart" height="250"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabla -->
        <div class="table-responsive">
            <table class="table table-striped datatable">
                <thead>
                    <tr>
                        <th>Generación</th>
                        <th>Año</th>
                        <th>Grupos</th>
                        <th>Alumnos</th>
                        <th>Evaluaciones</th>
                        <th>Puntualidad</th>
                        <th>Resolución</th>
                        <th>Promedio</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($generaciones as $gen):
                        $promedio = $gen['total_evaluaciones'] > 0 ? 
                            round(($gen['avg_puntualidad'] + $gen['avg_resolucion']) / 2, 2) : 0;
                        $color = $promedio >= 7 ? 'success' : ($promedio >= 4 ? 'warning' : 'danger');
                    ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($gen['nombre']); ?></strong></td>
                        <td><?php echo $gen['anio']; ?></td>
                        <td><?php echo $gen['total_grupos']; ?></td>
                        <td><span class="badge bg-primary"><?php echo $gen['total_alumnos']; ?></span></td>
                        <td><span class="badge bg-info"><?php echo $gen['total_evaluaciones']; ?></span></td>
                        <td>
                            <?php if($gen['total_evaluaciones'] > 0): ?>
                            <div class="progress" style="height: 20px;">
                                <div class="progress-bar bg-success" style="width: <?php echo ($gen['avg_puntualidad'] / 9) * 100; ?>%">
                                    <?php echo round($gen['avg_puntualidad'], 1); ?>/9
                                </div>
                            </div>
                            <?php else: ?>
                            <span class="text-muted">Sin evaluaciones</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if($gen['total_evaluaciones'] > 0): ?>
                            <div class="progress" style="height: 20px;">
                                <div class="progress-bar bg-info" style="width: <?php echo ($gen['avg_resolucion'] / 9) * 100; ?>%">
                                    <?php echo round($gen['avg_resolucion'], 1); ?>/9
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
// Gráfico por generación
const ctx1 = document.getElementById('generacionesChart').getContext('2d');
new Chart(ctx1, {
    type: 'bar',
    data: {
        labels: <?php echo json_encode($labels); ?>,
        datasets: [{
            label: 'Promedio General',
            data: <?php echo json_encode($datos_grafico); ?>,
            backgroundColor: <?php echo json_encode(array_slice($colores, 0, count($labels))); ?>,
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

// Distribución
const ctx2 = document.getElementById('distribucionGenChart').getContext('2d');
new Chart(ctx2, {
    type: 'bar',
    data: {
        labels: <?php echo json_encode($labels); ?>,
        datasets: [
            {
                label: 'Alumnos',
                data: <?php echo json_encode(array_column($generaciones, 'total_alumnos')); ?>,
                backgroundColor: 'rgba(102, 126, 234, 0.5)',
                borderColor: '#667eea',
                borderWidth: 1
            },
            {
                label: 'Evaluaciones',
                data: <?php echo json_encode(array_column($generaciones, 'total_evaluaciones')); ?>,
                backgroundColor: 'rgba(40, 167, 69, 0.5)',
                borderColor: '#28a745',
                borderWidth: 1
            }
        ]
    },
    options: {
        scales: {
            y: {
                beginAtZero: true,
                title: {
                    display: true,
                    text: 'Cantidad'
                }
            }
        },
        plugins: {
            legend: {
                position: 'top'
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