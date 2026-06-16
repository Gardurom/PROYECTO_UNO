<?php
// dashboard.php - Actualizado con las nuevas páginas de evaluación docente
session_start();

// Verificar autenticación
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

// Verificar tiempo de sesión
$session_timeout = 7200; // 2 horas
if (isset($_SESSION['login_time']) && (time() - $_SESSION['login_time']) > $session_timeout) {
    session_destroy();
    header('Location: login.php?error=session_expired');
    exit;
}

// Verificar IP
$current_ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
if ($_SESSION['ip_address'] !== $current_ip) {
    session_destroy();
    header('Location: login.php?error=ip_mismatch');
    exit;
}

// Verificar User Agent
$current_ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
if ($_SESSION['user_agent'] !== $current_ua) {
    session_destroy();
    header('Location: login.php?error=ua_mismatch');
    exit;
}

$_SESSION['last_activity'] = time();

// Incluir configuración de base de datos
require_once 'includes/database.php';

// Sanitizar página - AÑADIDAS LAS NUEVAS PÁGINAS
$page = preg_replace('/[^a-z_]/', '', $_GET['page'] ?? 'dashboard');
$valid_pages = [
    'dashboard', 
    'mapa', 
    'mapa_poligono', 
    'generar_qr', 
    'perfil', 
    'configuracion',
    // Nuevas páginas para evaluación docente
    'generaciones',
    'grupos', 
    'materias', 
    'docentes', 
    'alumnos', 
    'evaluaciones', 
    'reportes',
    'carga_masiva'
];
if (!in_array($page, $valid_pages)) $page = 'dashboard';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Sistema de Gestión</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <style>
        body { background: #f4f6f9; }
        .sidebar { min-height: calc(100vh - 56px); background: white; box-shadow: 2px 0 5px rgba(0,0,0,0.1); }
        .nav-link { color: #333; padding: 12px 20px; transition: all 0.3s; }
        .nav-link:hover { background: #667eea; color: white; }
        .nav-link.active { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }
        .nav-link i { margin-right: 10px; width: 20px; }
        .content-area { padding: 20px; }
        .map-container { height: 500px; border-radius: 10px; overflow: hidden; box-shadow: 0 0 15px rgba(0,0,0,0.1); }
        #map { height: 100%; width: 100%; }
        .upload-area { border: 2px dashed #667eea; border-radius: 10px; padding: 30px; text-align: center; background: #f8f9fa; transition: all 0.3s; cursor: pointer; }
        .upload-area:hover { border-color: #764ba2; background: #f0f0f0; }
        .coordinates-table { max-height: 300px; overflow-y: auto; }
        .btn-custom { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border: none; color: white; }
        .btn-custom:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(0,0,0,0.2); }
        .stats-card { background: white; border-radius: 10px; padding: 15px; margin-bottom: 20px; box-shadow: 0 0 10px rgba(0,0,0,0.1); transition: transform 0.3s; }
        .stats-card:hover { transform: translateY(-5px); }
        .stats-card i { font-size: 2rem; color: #667eea; }
        .stats-number { font-size: 2rem; font-weight: bold; margin-top: 10px; }
        
        /* Estilos para evaluación docente */
        .rating {
            display: inline-flex;
            flex-direction: row-reverse;
            gap: 5px;
        }
        .rating input {
            display: none;
        }
        .rating label {
            font-size: 1.5rem;
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
        .progress {
            height: 25px;
        }
        .progress-bar {
            line-height: 25px;
            color: white;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-dark bg-dark">
        <div class="container-fluid">
            <span class="navbar-brand">🏠 Sistema de Gestión - Evaluación Docente</span>
            <div class="d-flex">
                <span class="navbar-text me-3">Bienvenido, <?php echo htmlspecialchars($_SESSION['username']); ?></span>
                <a href="logout.php" class="btn btn-outline-light btn-sm">Cerrar Sesión</a>
            </div>
        </div>
    </nav>
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-2 p-0 sidebar">
                <div class="nav flex-column">
                    <a href="?page=dashboard" class="nav-link <?php echo $page == 'dashboard' ? 'active' : ''; ?>">
                        <i class="fas fa-tachometer-alt"></i> Dashboard
                    </a>
                    
                    <!-- Sección de Mapas (existente) -->
                    <div class="nav-item">
                        <hr class="my-2">
                        <small class="text-muted px-3">MAPAS</small>
                    </div>
                    <a href="?page=mapa" class="nav-link <?php echo $page == 'mapa' ? 'active' : ''; ?>">
                        <i class="fas fa-map-marked-alt"></i> Mapa de Coordenadas
                    </a>
                    <a href="?page=mapa_poligono" class="nav-link <?php echo $page == 'mapa_poligono' ? 'active' : ''; ?>">
                        <i class="fas fa-draw-polygon"></i> Mapa de Polígonos
                    </a>
                    <a href="?page=generar_qr" class="nav-link <?php echo $page == 'generar_qr' ? 'active' : ''; ?>">
                        <i class="fas fa-qrcode"></i> Generar QR
                    </a>
                    
                    <!-- Nueva Sección: Evaluación Docente -->
                    <div class="nav-item">
                        <hr class="my-2">
                        <small class="text-muted px-3">EVALUACIÓN DOCENTE</small>
                    </div>
                    <a href="?page=generaciones" class="nav-link <?php echo $page == 'generaciones' ? 'active' : ''; ?>">
                        <i class="fas fa-layer-group"></i> Generaciones
                    </a>
                    <a href="?page=grupos" class="nav-link <?php echo $page == 'grupos' ? 'active' : ''; ?>">
                        <i class="fas fa-users"></i> Grupos
                    </a>
                    <a href="?page=materias" class="nav-link <?php echo $page == 'materias' ? 'active' : ''; ?>">
                        <i class="fas fa-book"></i> Materias
                    </a>
                    <a href="?page=docentes" class="nav-link <?php echo $page == 'docentes' ? 'active' : ''; ?>">
                        <i class="fas fa-chalkboard-user"></i> Docentes
                    </a>
                    <a href="?page=alumnos" class="nav-link <?php echo $page == 'alumnos' ? 'active' : ''; ?>">
                        <i class="fas fa-user-graduate"></i> Alumnos
                    </a>
                    <a href="?page=evaluaciones" class="nav-link <?php echo $page == 'evaluaciones' ? 'active' : ''; ?>">
                        <i class="fas fa-star"></i> Evaluaciones
                    </a>
                    <a href="?page=reportes" class="nav-link <?php echo $page == 'reportes' ? 'active' : ''; ?>">
                        <i class="fas fa-chart-bar"></i> Reportes
                    </a>
                    <a href="?page=carga_masiva" class="nav-link <?php echo $page == 'carga_masiva' ? 'active' : ''; ?>">
                        <i class="fas fa-upload"></i> Carga Masiva
                    </a>
                    
                    <!-- Sección de Usuario -->
                    <div class="nav-item">
                        <hr class="my-2">
                        <small class="text-muted px-3">USUARIO</small>
                    </div>
                    <a href="?page=perfil" class="nav-link <?php echo $page == 'perfil' ? 'active' : ''; ?>">
                        <i class="fas fa-user"></i> Mi Perfil
                    </a>
                    <a href="?page=configuracion" class="nav-link <?php echo $page == 'configuracion' ? 'active' : ''; ?>">
                        <i class="fas fa-cog"></i> Configuración
                    </a>
                </div>
            </div>
            <div class="col-md-10 content-area">
                <?php
                // Determinar qué archivo incluir
                $file = "pages/{$page}_content.php";
                
                // Casos especiales (como los que ya tenías)
                if ($page === 'mapa_poligono') $file = "pages/mapa_poligono.php";
                if ($page === 'generar_qr') $file = "pages/generar_qr.php";
                
                // Para las nuevas páginas de evaluación, usar el formato _content.php
                if (in_array($page, ['generaciones', 'grupos', 'materias', 'docentes', 'alumnos', 'evaluaciones', 'reportes', 'carga_masiva'])) {
                    $file = "pages/{$page}_content.php";
                }
                
                if (file_exists($file)) {
                    include $file;
                } else {
                    echo "<div class='alert alert-danger'>Página no encontrada: " . htmlspecialchars($page) . "</div>";
                }
                ?>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <script>
        $(document).ready(function() {
            // Inicializar DataTables en todas las tablas con la clase 'datatable'
            $('.datatable').DataTable({
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.11.5/i18n/es-ES.json'
                },
                pageLength: 10,
                responsive: true
            });
        });
    </script>
    
    <?php if($page == 'mapa'): ?>
    <script>
        let map, markers = [];
        function initMap() {
            map = L.map('map').setView([19.4326, -99.1332], 10);
            L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OSM</a> &copy; CartoDB',
                subdomains: 'abcd', maxZoom: 19
            }).addTo(map);
        }
        function addMarkerToMap(lat, lng, title, description) {
            let marker = L.marker([lat, lng]).addTo(map);
            marker.bindPopup(`<b>${title}</b><br><small>Lat: ${lat}, Lng: ${lng}</small><br><p>${description}</p>`);
            markers.push(marker);
            return marker;
        }
        function clearMarkers() { markers.forEach(m => map.removeLayer(m)); markers = []; }
        function fitBoundsToMarkers() { if(markers.length) map.fitBounds(L.latLngBounds(markers.map(m => m.getLatLng()))); }
        function loadSavedMarkers() {
            fetch('ajax/get_markers.php').then(r=>r.json()).then(data=>{
                if(data.success && data.markers) {
                    data.markers.forEach(m=>addMarkerToMap(m.lat, m.lng, m.title, m.description));
                    fitBoundsToMarkers();
                }
            }).catch(e=>console.error(e));
        }
        function uploadExcel(file) {
            let reader = new FileReader();
            reader.onload = function(e) {
                let workbook = XLSX.read(new Uint8Array(e.target.result), {type:'array'});
                let rows = XLSX.utils.sheet_to_json(workbook.Sheets[workbook.SheetNames[0]]);
                clearMarkers();
                let formData = new FormData();
                formData.append('excel_data', JSON.stringify(rows));
                fetch('ajax/upload_coordinates.php', {method:'POST', body:formData})
                    .then(r=>r.json()).then(result=>{
                        if(result.success) {
                            result.coordinates.forEach(c=>addMarkerToMap(c.lat, c.lng, c.title, c.description));
                            fitBoundsToMarkers();
                            alert(`Se cargaron ${result.count} coordenadas`);
                        } else alert('Error: '+result.error);
                    });
            };
            reader.readAsArrayBuffer(file);
        }
        document.addEventListener('DOMContentLoaded', ()=>{ initMap(); loadSavedMarkers(); });
    </script>
    <?php endif; ?>
</body>
</html>