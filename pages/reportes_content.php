<?php
// pages/reportes_content.php
$db = getDB();
$reporte = $_GET['reporte'] ?? 'general';
?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col">
            <h2><i class="fas fa-chart-bar"></i> Reportes y Estadísticas</h2>
            <p class="text-muted">Análisis detallado de evaluaciones docentes</p>
        </div>
    </div>

    <!-- Pestañas de reportes -->
    <ul class="nav nav-tabs mb-4" id="reporteTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link <?php echo $reporte == 'general' ? 'active' : ''; ?>" onclick="cargarReporte('general')">
                <i class="fas fa-chart-line"></i> General
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link <?php echo $reporte == 'docentes' ? 'active' : ''; ?>" onclick="cargarReporte('docentes')">
                <i class="fas fa-chalkboard-user"></i> Por Docente
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link <?php echo $reporte == 'materias' ? 'active' : ''; ?>" onclick="cargarReporte('materias')">
                <i class="fas fa-book"></i> Por Materia
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link <?php echo $reporte == 'generaciones' ? 'active' : ''; ?>" onclick="cargarReporte('generaciones')">
                <i class="fas fa-users"></i> Por Generación
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link <?php echo $reporte == 'periodos' ? 'active' : ''; ?>" onclick="cargarReporte('periodos')">
                <i class="fas fa-calendar"></i> Por Periodo
            </button>
        </li>
    </ul>

    <div id="contenidoReporte">
        <?php include "reportes/{$reporte}_reporte.php"; ?>
    </div>
</div>

<script>
function cargarReporte(tipo) {
    // Actualizar URL sin recargar
    const url = new URL(window.location);
    url.searchParams.set('reporte', tipo);
    window.history.pushState({}, '', url);
    
    // Cargar contenido
    fetch(`reportes/${tipo}_reporte.php`)
        .then(response => response.text())
        .then(html => {
            document.getElementById('contenidoReporte').innerHTML = html;
            
            // Actualizar clases activas de las pestañas
            document.querySelectorAll('.nav-link').forEach(link => {
                link.classList.remove('active');
            });
            event.target.classList.add('active');
        });
}
</script>