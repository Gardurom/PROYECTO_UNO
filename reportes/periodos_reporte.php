<?php
// reportes/periodos_reporte.php
$db = getDB();

// Obtener todos los periodos
$periodos = $db->query("
    SELECT DISTINCT periodo 
    FROM materia_docente 
    WHERE activo = 1 
    ORDER BY periodo DESC
")->fetchAll(PDO::FETCH_COLUMN);

// Datos por periodo
$datos_periodos = [];
$colores = ['#667eea', '#764ba2', '#28a745', '#ffc107', '#dc3545', '#17a2b8'];

foreach ($periodos as $periodo) {
    $stmt = $db->prepare("
        SELECT 
            COUNT(DISTINCT e.id) as total_evaluaciones,
            AVG(e.puntualidad_asistencia) as avg_puntualidad,
            AVG(e.resolvio_dudas) as avg_resolucion,
            COUNT(DISTINCT a.id) as total_alumnos,
            COUNT(DISTINCT d.id) as total_docentes,
            COUNT(DISTINCT m.id) as total_materias
        FROM materia_docente md
        JOIN materias m ON md.materia_id = m.id
        JOIN docentes d ON md.docente_id = d.id
        LEFT JOIN evaluaciones e ON md.id = e.materia_docente_id
        LEFT JOIN alumnos a ON e.alumno_id = a.id
        WHERE md.periodo = ? AND md.activo = 1
    ");
    $stmt->execute([$periodo]);
    $datos_periodos[$periodo] = $stmt->fetch();
}

// Calcular evolución de promedios
$evolucion = [];
foreach ($periodos as $periodo) {
    if ($datos_periodos[$periodo]['total_evaluaciones'] > 0) {
        $evolucion[$periodo] = round(
            ($datos_periodos[$periodo]['avg_puntualidad'] + 
             $datos_periodos[$periodo]['avg_resolucion']) / 2, 2
        );
    } else {
        $evolucion[$periodo] = 0;
    }
}
?>

<div class="card">
    <div class="card-header">
        <div class="row">
            <div class="col">
                <h5><i class="fas fa-calendar-alt"></i> Reporte por Periodo</h5>
            </div>
            <div class="col text-end">
                <button class="btn btn-sm btn-success" onclick="exportarReporte('periodos')">
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
                    <i class="fas fa-clock fa-2x"></i>
                    <div class="stats-number"><?php echo count($periodos); ?></div>
                    <div>Total Periodos</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card text-center">
                    <i class="fas fa-star fa-2x"></i>
                    <div class="stats-number">
                        <?php 
                        $total_eval = array_sum(array_column($datos_periodos, 'total_evaluaciones'));
                        echo $total_eval;
                        ?>
                    </div>
                    <div>Evaluaciones Totales</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card text-center">
                    <i class="fas fa-chart-line fa-2x"></i>
                    <div class="stats-number">
                        <?php 
                        $promedios = array_filter($evolucion);
                        $promedio_total = !empty($promedios) ? round(array_sum($promedios) / count($promedios), 2) : 0;
                        echo $promedio_total;
                        ?>
                    </div>
                    <div>Promedio General</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card text-center">
                    <i class="fas fa-trend-up fa-2x"></i>
                    <div class="stats-number">
                        <?php 
                        $ultimo = end($evolucion);
                        $primero = reset($evolucion);
                        $diferencia = $ultimo - $primero;
                        echo ($diferencia > 0 ? '+' : '') . round($diferencia, 1);
                        ?>
                    </div>
                    <div>Variación</div>
                </div>
            </div>
        </div>

        <!-- Gráficos -->
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h6>Evolución de Promedios por Periodo</h6>
                    </div>
                    <div class="card-body">
                        <canvas id="evolucionChart" height="250"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h6>Distribución por Periodo</h6>
                    </div>
                    <div class="card-body">
                        <canvas id="distribucionPeriodoChart" height="250"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Comparativa de periodos -->
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h6>Comparativa Detallada por Periodo</h6>
                    </div>
                    <div class="card-body">
                        <canvas id="comparativaPeriodoChart" height="200"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabla -->
        <div class="table-responsive">
            <table class="table table-striped datatable">
                <thead>
                    <tr>
                        <th>Periodo</th>
                        <th>Alumnos</th>
                        <th>Docentes</th>
                        <th>Materias</th>
                        <th>Evaluaciones</th>
                        <th>Puntualidad</th>
                        <th>Resolución</th>
                        <th>Promedio</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($periodos as $periodo):
                        $data = $datos_periodos[$periodo];
                        $promedio = $data['total_evaluaciones'] > 0 ? 
                            round(($data['avg_puntualidad'] + $data['avg_resolucion']) / 2, 2) : 0;
                        $color = $promedio >= 7 ? 'success' : ($promedio >= 4 ? 'warning' : 'danger');
                        $tendencia = '';
                        if ($data['total_evaluaciones'] > 0) {
                            $anterior = prev($evolucion);
                            if ($anterior !== false) {
                                $tendencia = $promedio > $anterior ? '↑' : ($promedio < $anterior ? '↓' : '→');
                            }
                        }
                    ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($periodo); ?></strong></td>
                        <td><span class="badge bg-primary"><?php echo $data['total_alumnos'] ?: 0; ?></span></td>
                        <td><span class="badge bg-success"><?php echo $data['total_docentes'] ?: 0; ?></span></td>
                        <td><span class="badge bg-info"><?php echo $data['total_materias'] ?: 0; ?></span></td>
                        <td><span class="badge bg-warning text-dark"><?php echo $data['total_evaluaciones']; ?></span></td>
                        <td>
                            <?php if($data['total_evaluaciones'] > 0): ?>
                            <div class="progress" style="height: 20px;">
                                <div class="progress-bar bg-success" style="width: <?php echo ($data['avg_puntualidad'] / 9) * 100; ?>%">
                                    <?php echo round($data['avg_puntualidad'], 1); ?>/9
                                </div>
                            </div>
                            <?php else: ?>
                            <span class="text-muted">Sin evaluaciones</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if($data['total_evaluaciones'] > 0): ?>
                            <div class="progress" style="height: 20px;">
                                <div class="progress-bar bg-info" style="width: <?php echo ($data['avg_resolucion'] / 9) * 100; ?>%">
                                    <?php echo round($data['avg_resolucion'], 1); ?>/9
                                </div>
                            </div>
                            <?php else: ?>
                            <span class="text-muted">Sin evaluaciones</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <strong><?php echo $promedio; ?></strong>/9
                            <?php if($tendencia): ?>
                                <span class="text-<?php echo $tendencia == '↑' ? 'success' : ($tendencia == '↓' ? 'danger' : 'secondary'); ?>">
                                    <?php echo $tendencia; ?>
                                </span>
                            <?php endif; ?>
                        </td>
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
// Evolución
const ctx1 = document.getElementById('evolucionChart').getContext('2d');
new Chart(ctx1, {
    type: 'line',
    data: {
        labels: <?php echo json_encode($periodos); ?>,
        datasets: [{
            label: 'Promedio General',
            data: <?php echo json_encode(array_values($evolucion)); ?>,
            borderColor: '#667eea',
            backgroundColor: 'rgba(102, 126, 234, 0.1)',
            tension: 0.3,
            fill: true
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
const ctx2 = document.getElementById('distribucionPeriodoChart').getContext('2d');
new Chart(ctx2, {
    type: 'doughnut',
    data: {
        labels: <?php echo json_encode($periodos); ?>,
        datasets: [{
            data: <?php echo json_encode(array_column($datos_periodos, 'total_evaluaciones')); ?>,
            backgroundColor: <?php echo json_encode(array_slice($colores, 0, count($periodos))); ?>
        }]
    },
    options: {
        plugins: {
            legend: {
                position: 'bottom'
            },
            title: {
                display: true,
                text: 'Distribución de Evaluaciones por Periodo'
            }
        }
    }
});

// Comparativa
const ctx3 = document.getElementById('comparativaPeriodoChart').getContext('2d');
new Chart(ctx3, {
    type: 'bar',
    data: {
        labels: <?php echo json_encode($periodos); ?>,
        datasets: [
            {
                label: 'Puntualidad',
                data: <?php echo json_encode(array_map(function($d) {
                    return $d['total_evaluaciones'] > 0 ? round($d['avg_puntualidad'], 2) : 0;
                }, $datos_periodos)); ?>,
                backgroundColor: 'rgba(40, 167, 69, 0.5)',
                borderColor: '#28a745',
                borderWidth: 1
            },
            {
                label: 'Resolución',
                data: <?php echo json_encode(array_map(function($d) {
                    return $d['total_evaluaciones'] > 0 ? round($d['avg_resolucion'], 2) : 0;
                }, $datos_periodos)); ?>,
                backgroundColor: 'rgba(23, 162, 184, 0.5)',
                borderColor: '#17a2b8',
                borderWidth: 1
            }
        ]
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