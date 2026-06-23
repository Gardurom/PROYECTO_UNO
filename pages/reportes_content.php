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
        <li class="nav-item">
            <a class="nav-link <?php echo $reporte == 'general' ? 'active' : ''; ?>" 
               href="?page=reportes&reporte=general">
                <i class="fas fa-chart-line"></i> General
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo $reporte == 'docentes' ? 'active' : ''; ?>" 
               href="?page=reportes&reporte=docentes">
                <i class="fas fa-chalkboard-user"></i> Por Docente
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo $reporte == 'materias' ? 'active' : ''; ?>" 
               href="?page=reportes&reporte=materias">
                <i class="fas fa-book"></i> Por Materia
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo $reporte == 'generaciones' ? 'active' : ''; ?>" 
               href="?page=reportes&reporte=generaciones">
                <i class="fas fa-users"></i> Por Generación
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo $reporte == 'periodos' ? 'active' : ''; ?>" 
               href="?page=reportes&reporte=periodos">
                <i class="fas fa-calendar"></i> Por Periodo
            </a>
        </li>
    </ul>

    <!-- Contenido del reporte -->
    <?php
    $file = "reportes/{$reporte}_reporte.php";
    if (file_exists($file)) {
        include $file;
    } else {
        echo "<div class='alert alert-danger'>Reporte no encontrado</div>";
    }
    ?>
</div>