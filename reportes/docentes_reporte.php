<?php
// reportes/docentes_reporte.php
$db = getDB();

// Obtener todos los docentes con sus estadísticas
$docentes = $db->query("
    SELECT 
        d.id,
        d.nombre,
        d.apellidos,
        d.email,
        d.telefono,
        COUNT(DISTINCT md.id) as total_materias,
        COUNT(DISTINCT e.id) as total_evaluaciones,
        AVG(e.puntualidad_asistencia) as avg_puntualidad,
        AVG(e.resolvio_dudas) as avg_resolucion,
        GROUP_CONCAT(DISTINCT m.nombre) as materias,
        GROUP_CONCAT(DISTINCT md.periodo) as periodos
    FROM docentes d
    LEFT JOIN materia_docente md ON d.id = md.docente_id AND md.activo = 1
    LEFT JOIN materias m ON md.materia_id = m.id
    LEFT JOIN evaluaciones e ON md.id = e.materia_docente_id
    WHERE d.activo = 1
    GROUP BY d.id
    ORDER BY (avg_puntualidad + avg_resolucion) DESC
")->fetchAll();

// Obtener top 5 docentes para gráfico
$top5 = array_slice($docentes, 0, 5);

// Contar evaluaciones por rango
$rangos = [
    'excelente' => 0,
    'regular' => 0,
    'mejorable' => 0
];

foreach ($docentes as $d) {
    if ($d['total_evaluaciones'] > 0) {
        $promedio = ($d['avg_puntualidad'] + $d['avg_resolucion']) / 2;
        if ($promedio >= 7) {
            $rangos['excelente']++;
        } elseif ($promedio >= 4) {
            $rangos['regular']++;
        } else {
            $rangos['mejorable']++;
        }
    }
}
?>

<div class="card">
    <div class="card-header">
        <div class="row">
            <div class="col">
                <h5><i class="fas fa-chalkboard-user"></i> Reporte por Docente</h5>
            </div>
            <div class="col text-end">
                <button class="btn btn-sm btn-success" onclick="exportarReporte('docentes')">
                    <i class="fas fa-file-excel"></i> Exportar a Excel
                </button>
                <button class="btn btn-sm btn-primary" onclick="imprimirReporte()">
                    <i class="fas fa-print"></i> Imprimir
                </button>
            </div>
        </div>
    </div>
    <div class="card-body">
        <!-- Resumen de docentes -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="stats-card text-center">
                    <i class="fas fa-chalkboard-user fa-2x"></i>
                    <div class="stats-number"><?php echo count($docentes); ?></div>
                    <div>Total Docentes</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card text-center">
                    <i class="fas fa-star fa-2x"></i>
                    <div class="stats-number"><?php echo $rangos['excelente']; ?></div>
                    <div>Excelentes (7-9)</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card text-center">
                    <i class="fas fa-minus-circle fa-2x"></i>
                    <div class="stats-number"><?php echo $rangos['regular']; ?></div>
                    <div>Regulares (4-6)</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card text-center">
                    <i class="fas fa-exclamation-circle fa-2x"></i>
                    <div class="stats-number"><?php echo $rangos['mejorable']; ?></div>
                    <div>Necesitan Mejorar (0-3)</div>
                </div>
            </div>
        </div>

        <!-- Gráfico Top 5 -->
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h6>Top 5 Docentes Mejor Evaluados</h6>
                    </div>
                    <div class="card-body">
                        <canvas id="topDocentesChart" height="250"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h6>Distribución de Evaluaciones por Docente</h6>
                    </div>
                    <div class="card-body">
                        <canvas id="distribucionDocentesChart" height="250"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabla detallada -->
        <div class="table-responsive">
            <table class="table table-striped datatable">
                <thead>
                    <tr>
                        <th>Docente</th>
                        <th>Email</th>
                        <th>Materias</th>
                        <th>Periodos</th>
                        <th>Evaluaciones</th>
                        <th>Puntualidad</th>
                        <th>Resolución</th>
                        <th>Promedio</th>
                        <th>Calificación</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($docentes as $docente): 
                        $promedio = $docente['total_evaluaciones'] > 0 ? 
                            round(($docente['avg_puntualidad'] + $docente['avg_resolucion']) / 2, 2) : 0;
                        $color = $promedio >= 7 ? 'success' : ($promedio >= 4 ? 'warning' : 'danger');
                    ?>
                    <tr>
                        <td>
                            <strong><?php echo htmlspecialchars($docente['nombre'] . ' ' . $docente['apellidos']); ?></strong>
                        </td>
                        <td><?php echo htmlspecialchars($docente['email']); ?></td>
                        <td><?php echo htmlspecialchars($docente['materias'] ?: 'Sin asignar'); ?></td>
                        <td><?php echo htmlspecialchars($docente['periodos'] ?: 'N/A'); ?></td>
                        <td><span class="badge bg-info"><?php echo $docente['total_evaluaciones']; ?></span></td>
                        <td>
                            <?php if($docente['total_evaluaciones'] > 0): ?>
                            <div class="progress" style="height: 20px;">
                                <div class="progress-bar bg-success" style="width: <?php echo ($docente['avg_puntualidad'] / 9) * 100; ?>%">
                                    <?php echo round($docente['avg_puntualidad'], 1); ?>/9
                                </div>
                            </div>
                            <?php else: ?>
                            <span class="text-muted">Sin evaluaciones</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if($docente['total_evaluaciones'] > 0): ?>
                            <div class="progress" style="height: 20px;">
                                <div class="progress-bar bg-info" style="width: <?php echo ($docente['avg_resolucion'] / 9) * 100; ?>%">
                                    <?php echo round($docente['avg_resolucion'], 1); ?>/9
                                </div>
                            </div>
                            <?php else: ?>
                            <span class="text-muted">Sin evaluaciones</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <strong><?php echo $promedio; ?></strong>/9
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
// Gráfico Top 5 Docentes
const ctx1 = document.getElementById('topDocentesChart').getContext('2d');
new Chart(ctx1, {
    type: 'bar',
    data: {
        labels: [<?php 
            $labels = array_map(function($d) {
                return "'" . addslashes($d['nombre'] . ' ' . $d['apellidos']) . "'";
            }, $top5);
            echo implode(', ', $labels);
        ?>],
        datasets: [{
            label: 'Promedio General',
            data: [<?php 
                $data = array_map(function($d) {
                    return $d['total_evaluaciones'] > 0 ? 
                        round(($d['avg_puntualidad'] + $d['avg_resolucion']) / 2, 2) : 0;
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

// Gráfico de distribución
const ctx2 = document.getElementById('distribucionDocentesChart').getContext('2d');
new Chart(ctx2, {
    type: 'doughnut',
    data: {
        labels: ['Excelente (7-9)', 'Regular (4-6)', 'Necesita Mejorar (0-3)'],
        datasets: [{
            data: [<?php echo $rangos['excelente'] . ', ' . $rangos['regular'] . ', ' . $rangos['mejorable']; ?>],
            backgroundColor: ['#28a745', '#ffc107', '#dc3545']
        }]
    },
    options: {
        plugins: {
            legend: {
                position: 'bottom'
            },
            title: {
                display: true,
                text: 'Distribución de Docentes por Calificación'
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