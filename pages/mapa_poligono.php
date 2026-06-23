<?php
session_start();

// Verificar autenticación (opcional - comenta si no necesitas login)
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mapa con Polígonos - Estado de Fuerza</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f4f6f9;
        }
        .navbar {
            background: #2c3e50;
            color: white;
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .navbar h2 {
            margin: 0;
            font-size: 1.2rem;
        }
        .navbar a {
            color: white;
            text-decoration: none;
            background: #e74c3c;
            padding: 5px 10px;
            border-radius: 5px;
            font-size: 0.8rem;
        }
        .main-container {
            display: flex;
            flex-direction: column;
            height: calc(100vh - 56px);
        }
        .map-section {
            display: flex;
            height: 55%;
            min-height: 400px;
        }
        .sidebar-stats {
            width: 320px;
            background: white;
            border-right: 1px solid #ddd;
            padding: 15px;
            overflow-y: auto;
            box-shadow: 2px 0 5px rgba(0,0,0,0.05);
            z-index: 10;
            flex-shrink: 0;
        }
        .map-container {
            flex: 1;
            position: relative;
        }
        #map {
            height: 100%;
            width: 100%;
        }
        .info-panel {
            position: absolute;
            bottom: 20px;
            left: 20px;
            background: rgba(0,0,0,0.85);
            color: white;
            padding: 10px 15px;
            border-radius: 8px;
            font-size: 12px;
            z-index: 1000;
            pointer-events: none;
            max-width: 300px;
        }
        .info-panel .color-box {
            display: inline-block;
            width: 12px;
            height: 12px;
            border-radius: 2px;
            margin-right: 5px;
        }
        .upload-area {
            border: 2px dashed #3498db;
            border-radius: 10px;
            padding: 15px;
            text-align: center;
            background: #f8f9fa;
            margin-bottom: 15px;
            transition: all 0.3s;
            cursor: pointer;
        }
        .upload-area:hover {
            border-color: #e74c3c;
            background: #eef2f7;
        }
        
        /* Sección de tabla debajo del mapa */
        .table-section {
            height: 45%;
            background: white;
            border-top: 2px solid #ddd;
            padding: 10px 15px;
            overflow: hidden;
            display: none;
        }
        .table-section h5 {
            margin-bottom: 8px;
        }
        .table-container {
            height: calc(100% - 35px);
            overflow: auto;
        }
        .table-container table {
            font-size: 12px;
            width: 100%;
            border-collapse: collapse;
        }
        .table-container table thead th {
            background: #2c3e50;
            color: white;
            position: sticky;
            top: 0;
            z-index: 10;
            padding: 8px 10px;
            text-align: left;
        }
        .table-container table tbody td {
            padding: 6px 10px;
            border-bottom: 1px solid #eee;
            vertical-align: middle;
        }
        .table-container table tbody tr:hover {
            background: #f0f0f0;
        }
        .table-container table tbody tr:nth-child(even) {
            background: #f8f9fa;
        }
        .table-container table tbody tr:nth-child(even):hover {
            background: #f0f0f0;
        }
        
        .stats-table {
            font-size: 12px;
            max-height: 250px;
            overflow-y: auto;
        }
        .stats-table table {
            width: 100%;
            border-collapse: collapse;
        }
        .stats-table th, .stats-table td {
            padding: 4px 6px;
            border-bottom: 1px solid #eee;
            text-align: left;
        }
        .stats-table th {
            background: #2c3e50;
            color: white;
            position: sticky;
            top: 0;
        }
        .badge-proc {
            background: #3498db;
            color: white;
            padding: 2px 8px;
            border-radius: 20px;
            font-size: 11px;
        }
        .loading {
            text-align: center;
            padding: 10px;
            display: none;
        }
        .polygon-legend {
            margin-top: 10px;
            background: #f8f9fa;
            padding: 8px;
            border-radius: 5px;
        }
        .polygon-legend-item {
            display: flex;
            align-items: center;
            margin-bottom: 3px;
            font-size: 11px;
        }
        .polygon-legend-item .color-box {
            width: 14px;
            height: 14px;
            border-radius: 3px;
            margin-right: 8px;
            border: 1px solid #ddd;
        }
        .polygon-legend-item .count-badge {
            margin-left: auto;
            background: #e9ecef;
            padding: 0 8px;
            border-radius: 10px;
            font-size: 10px;
            font-weight: bold;
        }
        .badge-cargo {
            font-size: 10px;
            padding: 2px 6px;
        }
        .resumen-card {
            background: #f8f9fa;
            padding: 8px;
            border-radius: 5px;
            margin-bottom: 8px;
        }
        .resumen-card .total {
            font-size: 20px;
            font-weight: bold;
            color: #2c3e50;
        }
        .btn-limpiar {
            margin-top: 8px;
        }
        .badge-ubicacion {
            padding: 3px 10px;
            border-radius: 15px;
            font-size: 11px;
            font-weight: bold;
        }
        .table-responsive-custom {
            height: 100%;
            overflow: auto;
        }
        .table-responsive-custom table {
            width: 100%;
            border-collapse: collapse;
        }
        /* Scroll personalizado */
        .table-container::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }
        .table-container::-webkit-scrollbar-track {
            background: #f1f1f1;
        }
        .table-container::-webkit-scrollbar-thumb {
            background: #888;
            border-radius: 3px;
        }
        .table-container::-webkit-scrollbar-thumb:hover {
            background: #555;
        }
    </style>
</head>
<body>
    <div class="navbar">
        <h2>🗺️ Mapa con Polígonos - Estado de Fuerza</h2>
        <a href="dashboard.php">← Volver</a>
    </div>
    
    <div class="main-container">
        <!-- Sección del mapa -->
        <div class="map-section">
            <div class="sidebar-stats">
                <h5><i class="fas fa-upload"></i> Cargar archivo Excel</h5>
                <div class="upload-area" id="uploadArea">
                    <i class="fas fa-file-excel" style="font-size: 30px; color: #28a745;"></i>
                    <p class="mt-2 mb-0">Arrastra o haz clic para subir</p>
                    <small class="text-muted">(Columnas: NOMBRE, CARGO Y/O GRADO, UBICACIÓN)</small>
                    <input type="file" id="excelFile" accept=".xlsx,.xls" style="display: none;">
                </div>
                <div class="loading" id="loadingStats">
                    <div class="spinner-border text-primary spinner-border-sm" role="status"></div> Procesando...
                </div>
                
                <!-- Resumen rápido -->
                <div id="resumenContainer" style="display: none;">
                    <hr>
                    <div class="resumen-card">
                        <div style="display: flex; justify-content: space-between;">
                            <span>Total de personal:</span>
                            <span class="total" id="totalPersonal">0</span>
                        </div>
                    </div>
                </div>
                
                <!-- Leyenda de polígonos -->
                <div class="polygon-legend" id="polygonLegend">
                    <h6><i class="fas fa-map-marked-alt"></i> Ubicaciones</h6>
                    <div class="polygon-legend-item">
                        <span class="color-box" style="background: #e74c3c;"></span>
                        <span>ANSP - SMJ</span>
                        <span class="count-badge" id="countSMJ">0</span>
                    </div>
                    <div class="polygon-legend-item">
                        <span class="color-box" style="background: #2ecc71;"></span>
                        <span>ANSP - TP</span>
                        <span class="count-badge" id="countTP">0</span>
                    </div>
                    <div class="polygon-legend-item">
                        <span class="color-box" style="background: #3498db;"></span>
                        <span>ANSP - CEDEF</span>
                        <span class="count-badge" id="countCEDEF">0</span>
                    </div>
                </div>
                
                <div class="mt-2">
                    <button class="btn btn-sm btn-outline-secondary w-100 btn-limpiar" id="resetStatsBtn" style="display: none;">
                        <i class="fas fa-trash-alt"></i> Limpiar datos
                    </button>
                </div>
            </div>
            
            <div class="map-container">
                <div id="map"></div>
                <div class="info-panel">
                    <strong>📍 Ubicaciones:</strong><br>
                    <span class="color-box" style="background: #e74c3c;"></span> SMJ (San Miguel)<br>
                    <span class="color-box" style="background: #2ecc71;"></span> TP (Torre Pedregal)<br>
                    <span class="color-box" style="background: #3498db;"></span> CEDEF
                </div>
            </div>
        </div>
        
        <!-- Sección de tabla debajo del mapa -->
        <div class="table-section" id="tableSection">
            <div class="d-flex justify-content-between align-items-center">
                <h5><i class="fas fa-table"></i> Detalle de Personal por Ubicación</h5>
                <div>
                    <span class="badge bg-secondary" id="totalRegistrosTabla">0 registros</span>
                    <button class="btn btn-sm btn-outline-secondary ms-2" onclick="exportarTabla()">
                        <i class="fas fa-file-excel"></i> Exportar
                    </button>
                </div>
            </div>
            <div class="table-container" id="tableContainer">
                <div id="tablaWrapper">
                    <table class="table table-striped table-hover" id="dataTable">
                        <thead>
                            <tr>
                                <th style="width: 40px;">#</th>
                                <th style="min-width: 200px;">Nombre</th>
                                <th style="min-width: 150px;">Cargo/Grado</th>
                                <th style="width: 80px;">EXP</th>
                                <th style="min-width: 120px;">Fecha Ingreso</th>
                                <th style="width: 100px;">Ubicación</th>
                            </tr>
                        </thead>
                        <tbody id="tableBody">
                            <tr>
                                <td colspan="6" class="text-center text-muted" style="padding: 30px;">
                                    <i class="fas fa-info-circle"></i> Cargue un archivo Excel para ver los datos
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://kit.fontawesome.com/a5c6e8a0e2.js" crossorigin="anonymous"></script>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    
    <script>
        // ==========================================
        // 1. DEFINICIÓN DE POLÍGONOS
        // ==========================================
        const POLYGONS = {
            smj: {
                id: 'SMJ',
                name: 'Academia Nacional de Seguridad Pública (San Miguel de los Jagüeyes)',
                shortName: 'ANSP-SMJ',
                color: '#e74c3c',
                fillOpacity: 0.3,
                weight: 3,
                opacity: 0.8,
                points: [
                    [19.810863, -99.275244],
                    [19.808436, -99.275143],
                    [19.803392, -99.286768],
                    [19.805834, -99.286732],
                    [19.805576, -99.289089],
                    [19.806701, -99.289152],
                    [19.806608, -99.297555],
                    [19.822904, -99.291651],
                    [19.826316, -99.294222],
                    [19.827213, -99.293689],
                    [19.833485, -99.294299],
                    [19.830866, -99.289813],
                    [19.829474, -99.290309],
                    [19.830190, -99.284456],
                    [19.829884, -99.284175],
                    [19.829727, -99.284283],
                    [19.829067, -99.283745],
                    [19.828530, -99.284247],
                    [19.826611, -99.279610],
                    [19.826963, -99.277792],
                    [19.825132, -99.275876],
                    [19.811196, -99.275251]
                ]
            },
            tp: {
                id: 'TP',
                name: 'Academia Nacional de Seguridad Pública (Torre Pedregal)',
                shortName: 'ANSP-TP',
                color: '#2ecc71',
                fillOpacity: 0.25,
                weight: 3,
                opacity: 0.8,
                points: [
                    [19.316446, -99.220016],
                    [19.316302, -99.220798],
                    [19.316157, -99.220749],
                    [19.316172, -99.220051]
                ]
            },
            cedef: {
                id: 'CEDEF',
                name: 'Academia Nacional de Seguridad Pública (CEDEF)',
                shortName: 'ANSP-CEDEF',
                color: '#3498db',
                fillOpacity: 0.25,
                weight: 3,
                opacity: 0.8,
                points: [
                    [19.404245, -99.191617],
                    [19.404158, -99.191564],
                    [19.403985, -99.191461],
                    [19.404014, -99.191060],
                    [19.404005, -99.190978],
                    [19.404014, -99.190819],
                    [19.406611, -99.191150],
                    [19.406553, -99.191480],
                    [19.406436, -99.191928],
                    [19.406242, -99.191914],
                    [19.406000, -99.191814],
                    [19.405858, -99.191768],
                    [19.405500, -99.191700],
                    [19.404901, -99.191608],
                    [19.404391, -99.191612]
                ]
            }
        };

        // Mapeo de ubicaciones a IDs de polígonos
        const UBICACION_MAP = {
            'SMJ': 'smj',
            'TP': 'tp',
            'CEDEF': 'cedef'
        };

        // Colores por ubicación
        const COLORES_UBICACION = {
            'SMJ': '#e74c3c',
            'TP': '#2ecc71',
            'CEDEF': '#3498db'
        };

        // ==========================================
        // 2. Inicializar mapa
        // ==========================================
        var map = L.map('map').setView([19.60, -99.25], 11);
        
        L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OSM</a> &copy; CartoDB',
            subdomains: 'abcd',
            maxZoom: 19
        }).addTo(map);

        // ==========================================
        // 3. Dibujar los polígonos
        // ==========================================
        var polygons = {};
        var polygonData = {};

        Object.keys(POLYGONS).forEach(function(key) {
            var config = POLYGONS[key];
            var polygon = L.polygon(config.points, {
                color: config.color,
                weight: config.weight,
                opacity: config.opacity,
                fillColor: config.color,
                fillOpacity: config.fillOpacity,
                className: 'polygon-' + key
            }).addTo(map);
            
            // Popup inicial
            polygon.bindPopup(`
                <b>${config.name}</b><br>
                <span style="color: ${config.color}; font-weight: bold;">●</span> ${config.shortName}<br>
                <hr style="margin: 5px 0;">
                <div style="text-align: center;">
                    <i>Cargue datos para ver estadísticas</i>
                </div>
            `);
            
            // Guardar referencia
            polygons[key] = polygon;
            polygonData[key] = {
                config: config,
                data: [],
                count: 0,
                polygon: polygon,
                cargos: {}
            };
            
            // Eventos hover
            polygon.on('mouseover', function(e) {
                this.setStyle({
                    fillOpacity: 0.6,
                    weight: 4
                });
                this.bringToFront();
            });
            
            polygon.on('mouseout', function(e) {
                this.setStyle({
                    fillOpacity: config.fillOpacity,
                    weight: config.weight
                });
            });
        });

        // Ajustar vista para mostrar todos los polígonos
        var group = L.featureGroup(Object.values(polygons));
        map.fitBounds(group.getBounds());

        // ==========================================
        // 4. Variables para datos
        // ==========================================
        let datosCompletos = [];
        let totalRegistros = 0;

        // ==========================================
        // 5. Función para formatear fecha de forma segura
        // ==========================================
        function formatearFecha(fecha) {
            if (!fecha) return 'N/A';
            
            // Convertir a string si no lo es
            var fechaStr = String(fecha).trim();
            
            if (!fechaStr || fechaStr === '') return 'N/A';
            
            // Si ya tiene formato dd/mm/yyyy
            if (fechaStr.match(/^\d{2}\/\d{2}\/\d{4}$/)) {
                return fechaStr;
            }
            
            // Si tiene formato yyyy-mm-dd (de Excel)
            if (fechaStr.match(/^\d{4}-\d{2}-\d{2}/)) {
                var partes = fechaStr.split('-');
                if (partes.length >= 3) {
                    return partes[2] + '/' + partes[1] + '/' + partes[0];
                }
            }
            
            // Si tiene formato dd/mm/yyyy con horas
            if (fechaStr.match(/^\d{2}\/\d{2}\/\d{4}/)) {
                var partes = fechaStr.split(' ');
                return partes[0];
            }
            
            // Si es un número (fecha de Excel)
            if (!isNaN(fechaStr) && fechaStr.length >= 5) {
                try {
                    // Convertir número de Excel a fecha
                    var fechaExcel = new Date((parseFloat(fechaStr) - 25569) * 86400 * 1000);
                    var dia = String(fechaExcel.getDate()).padStart(2, '0');
                    var mes = String(fechaExcel.getMonth() + 1).padStart(2, '0');
                    var anio = fechaExcel.getFullYear();
                    if (anio > 1900 && anio < 2100) {
                        return dia + '/' + mes + '/' + anio;
                    }
                } catch(e) {}
            }
            
            // Si tiene otro formato, devolver el original
            return fechaStr;
        }

        // ==========================================
        // 6. Función para procesar datos
        // ==========================================
        function procesarDatos(rows) {
            // Limpiar datos anteriores
            Object.keys(polygonData).forEach(function(key) {
                polygonData[key].data = [];
                polygonData[key].count = 0;
                polygonData[key].cargos = {};
            });
            
            datosCompletos = [];
            
            // Procesar cada fila
            rows.forEach(function(row, index) {
                // Obtener datos
                let nombre = row['NOMBRE'] || 'Sin nombre';
                let cargo = row['CARGO Y/O GRADO'] || 'Sin especificar';
                let exp = row['EXP'] || '';
                let fecha = row['FECHA DE INGRESO/COMISION'] || '';
                let ubicacion = row['UBICACIÓN'] || 'Sin ubicación';
                
                // Limpiar ubicación
                ubicacion = ubicacion.toString().trim().toUpperCase();
                
                // Si la ubicación es "SMJ" o "TP" o "CEDEF", asignar al polígono correspondiente
                let polygonKey = UBICACION_MAP[ubicacion] || null;
                
                // Si no tiene ubicación válida, asignar a "Otros" (no se muestra en el mapa)
                if (!polygonKey) {
                    polygonKey = 'otros';
                    if (!polygonData[polygonKey]) {
                        polygonData[polygonKey] = {
                            config: { name: 'Otras Ubicaciones', shortName: 'OTROS', color: '#95a5a6' },
                            data: [],
                            count: 0,
                            cargos: {},
                            polygon: null
                        };
                    }
                }
                
                // Formatear fecha
                var fechaFormateada = formatearFecha(fecha);
                
                // Guardar datos
                var persona = {
                    id: index + 1,
                    nombre: nombre,
                    cargo: cargo,
                    exp: exp,
                    fecha: fechaFormateada,
                    fechaOriginal: fecha,
                    ubicacion: ubicacion,
                    polygonKey: polygonKey
                };
                
                datosCompletos.push(persona);
                
                // Agregar al polígono correspondiente
                if (polygonData[polygonKey]) {
                    polygonData[polygonKey].data.push(persona);
                    polygonData[polygonKey].count++;
                    
                    // Contar por cargo
                    if (!polygonData[polygonKey].cargos[cargo]) {
                        polygonData[polygonKey].cargos[cargo] = 0;
                    }
                    polygonData[polygonKey].cargos[cargo]++;
                }
            });
            
            totalRegistros = datosCompletos.length;
            
            // Actualizar UI
            actualizarUI();
            actualizarTabla();
            
            // Mostrar secciones
            document.getElementById('tableSection').style.display = 'block';
            document.getElementById('resumenContainer').style.display = 'block';
            document.getElementById('resetStatsBtn').style.display = 'block';
            document.getElementById('totalPersonal').textContent = totalRegistros;
            document.getElementById('totalRegistrosTabla').textContent = totalRegistros + ' registros';
        }

        // ==========================================
        // 7. Actualizar UI
        // ==========================================
        function actualizarUI() {
            // Actualizar leyenda
            Object.keys(polygonData).forEach(function(key) {
                var data = polygonData[key];
                var count = data.count || 0;
                
                // Actualizar contadores en leyenda
                if (key === 'smj') {
                    document.getElementById('countSMJ').textContent = count;
                } else if (key === 'tp') {
                    document.getElementById('countTP').textContent = count;
                } else if (key === 'cedef') {
                    document.getElementById('countCEDEF').textContent = count;
                }
            });
            
            // Actualizar popups de polígonos
            Object.keys(polygonData).forEach(function(key) {
                var data = polygonData[key];
                var config = data.config;
                var count = data.count || 0;
                var cargos = data.cargos || {};
                
                // Construir tabla de cargos
                var cargosHtml = '';
                var sortedCargos = Object.keys(cargos).sort();
                sortedCargos.forEach(function(cargo) {
                    cargosHtml += `<tr>
                        <td style="font-size: 11px;">${cargo}</td>
                        <td style="text-align: center; font-weight: bold;">${cargos[cargo]}</td>
                    </tr>`;
                });
                
                if (count > 0) {
                    var popupContent = `
                        <div style="min-width: 250px; max-height: 400px; overflow-y: auto;">
                            <b>${config.name}</b><br>
                            <span style="color: ${config.color}; font-weight: bold;">●</span> ${config.shortName}
                            <hr style="margin: 5px 0;">
                            <div style="text-align: center; margin-bottom: 8px;">
                                <span style="font-size: 28px; font-weight: bold; color: ${config.color};">${count}</span>
                                <span style="font-size: 14px; color: #7f8c8d;"> personas</span>
                            </div>
                            <hr style="margin: 5px 0;">
                            <b>Detalle por cargo:</b>
                            <table style="width: 100%; font-size: 12px; margin-top: 5px;">
                                <thead>
                                    <tr><th>Cargo/Grado</th><th style="text-align: center;">Cantidad</th></tr>
                                </thead>
                                <tbody>
                                    ${cargosHtml}
                                </tbody>
                            </table>
                        </div>
                    `;
                } else {
                    var popupContent = `
                        <b>${config.name}</b><br>
                        <span style="color: ${config.color}; font-weight: bold;">●</span> ${config.shortName}<br>
                        <hr style="margin: 5px 0;">
                        <div style="text-align: center;">
                            <i>No hay personal asignado</i>
                        </div>
                    `;
                }
                
                if (data.polygon) {
                    data.polygon.bindPopup(popupContent);
                }
            });
        }

        // ==========================================
        // 8. Actualizar tabla
        // ==========================================
        function actualizarTabla() {
            var tbody = document.getElementById('tableBody');
            tbody.innerHTML = '';
            
            if (datosCompletos.length === 0) {
                tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted" style="padding: 30px;"><i class="fas fa-info-circle"></i> No hay datos para mostrar</td></tr>';
                return;
            }
            
            // Ordenar por ubicación y nombre
            var sorted = [...datosCompletos].sort((a, b) => {
                if (a.ubicacion !== b.ubicacion) return a.ubicacion.localeCompare(b.ubicacion);
                return a.nombre.localeCompare(b.nombre);
            });
            
            sorted.forEach(function(persona, index) {
                var tr = document.createElement('tr');
                var color = COLORES_UBICACION[persona.ubicacion] || '#95a5a6';
                
                tr.innerHTML = `
                    <td style="text-align: center;">${index + 1}</td>
                    <td><strong>${persona.nombre}</strong></td>
                    <td><span class="badge bg-secondary badge-cargo">${persona.cargo}</span></td>
                    <td>${persona.exp || 'N/A'}</td>
                    <td>${persona.fecha || 'N/A'}</td>
                    <td>
                        <span class="badge badge-ubicacion" style="background-color: ${color}; color: white;">
                            ${persona.ubicacion}
                        </span>
                    </td>
                `;
                tbody.appendChild(tr);
            });
        }

        // ==========================================
        // 9. Leer Excel
        // ==========================================
        function procesarExcel(file) {
            const loadingDiv = document.getElementById('loadingStats');
            loadingDiv.style.display = 'block';
            
            const reader = new FileReader();
            reader.onload = function(e) {
                try {
                    const data = new Uint8Array(e.target.result);
                    const workbook = XLSX.read(data, { type: 'array' });
                    const firstSheet = workbook.Sheets[workbook.SheetNames[0]];
                    const rows = XLSX.utils.sheet_to_json(firstSheet);
                    
                    if (rows.length === 0) {
                        loadingDiv.style.display = 'none';
                        alert('El archivo está vacío.');
                        return;
                    }
                    
                    // Verificar columnas
                    const columnas = Object.keys(rows[0]);
                    const columnasRequeridas = ['NOMBRE', 'CARGO Y/O GRADO', 'UBICACIÓN'];
                    const faltantes = columnasRequeridas.filter(col => !columnas.includes(col));
                    
                    if (faltantes.length > 0) {
                        loadingDiv.style.display = 'none';
                        alert(`Faltan columnas requeridas: ${faltantes.join(', ')}\n\nColumnas disponibles: ${columnas.join(', ')}`);
                        return;
                    }
                    
                    procesarDatos(rows);
                    
                } catch (error) {
                    alert('Error al procesar el archivo: ' + error.message);
                    console.error(error);
                }
                
                loadingDiv.style.display = 'none';
            };
            
            reader.onerror = function() {
                loadingDiv.style.display = 'none';
                alert('Error al leer el archivo.');
            };
            
            reader.readAsArrayBuffer(file);
        }

        // ==========================================
        // 10. Exportar tabla a Excel
        // ==========================================
        function exportarTabla() {
            if (datosCompletos.length === 0) {
                alert('No hay datos para exportar');
                return;
            }
            
            // Crear datos para exportar
            var exportData = [['#', 'Nombre', 'Cargo/Grado', 'EXP', 'Fecha Ingreso', 'Ubicación']];
            
            datosCompletos.forEach(function(persona, index) {
                exportData.push([
                    index + 1,
                    persona.nombre,
                    persona.cargo,
                    persona.exp || 'N/A',
                    persona.fecha || 'N/A',
                    persona.ubicacion
                ]);
            });
            
            // Crear libro de Excel
            var wb = XLSX.utils.book_new();
            var ws = XLSX.utils.aoa_to_sheet(exportData);
            XLSX.utils.book_append_sheet(wb, ws, 'Estado de Fuerza');
            
            // Descargar
            XLSX.writeFile(wb, 'Estado_Fuerza_ANSP.xlsx');
        }

        // ==========================================
        // 11. Limpiar datos
        // ==========================================
        function limpiarDatos() {
            datosCompletos = [];
            totalRegistros = 0;
            
            // Resetear datos de polígonos
            Object.keys(polygonData).forEach(function(key) {
                polygonData[key].data = [];
                polygonData[key].count = 0;
                polygonData[key].cargos = {};
                
                if (polygonData[key].polygon) {
                    var config = polygonData[key].config;
                    polygonData[key].polygon.bindPopup(`
                        <b>${config.name}</b><br>
                        <span style="color: ${config.color}; font-weight: bold;">●</span> ${config.shortName}<br>
                        <hr style="margin: 5px 0;">
                        <div style="text-align: center;">
                            <i>Cargue datos para ver estadísticas</i>
                        </div>
                    `);
                }
            });
            
            // Limpiar UI
            document.getElementById('countSMJ').textContent = '0';
            document.getElementById('countTP').textContent = '0';
            document.getElementById('countCEDEF').textContent = '0';
            document.getElementById('totalPersonal').textContent = '0';
            document.getElementById('totalRegistrosTabla').textContent = '0 registros';
            document.getElementById('tableSection').style.display = 'none';
            document.getElementById('resumenContainer').style.display = 'none';
            document.getElementById('resetStatsBtn').style.display = 'none';
            document.getElementById('tableBody').innerHTML = '<tr><td colspan="6" class="text-center text-muted" style="padding: 30px;"><i class="fas fa-info-circle"></i> Cargue un archivo Excel para ver los datos</td></tr>';
            document.getElementById('excelFile').value = '';
        }

        // ==========================================
        // 12. Eventos
        // ==========================================
        const uploadArea = document.getElementById('uploadArea');
        const excelFileInput = document.getElementById('excelFile');
        
        uploadArea.addEventListener('click', () => excelFileInput.click());
        
        uploadArea.addEventListener('dragover', (e) => {
            e.preventDefault();
            uploadArea.style.borderColor = '#e74c3c';
            uploadArea.style.background = '#eef2f7';
        });
        
        uploadArea.addEventListener('dragleave', (e) => {
            e.preventDefault();
            uploadArea.style.borderColor = '#3498db';
            uploadArea.style.background = '#f8f9fa';
        });
        
        uploadArea.addEventListener('drop', (e) => {
            e.preventDefault();
            uploadArea.style.borderColor = '#3498db';
            uploadArea.style.background = '#f8f9fa';
            const file = e.dataTransfer.files[0];
            if (file) {
                const ext = file.name.split('.').pop().toLowerCase();
                if (['xlsx', 'xls'].includes(ext)) {
                    procesarExcel(file);
                } else {
                    alert('Por favor, sube un archivo Excel válido (.xlsx o .xls)');
                }
            }
        });
        
        excelFileInput.addEventListener('change', (e) => {
            const file = e.target.files[0];
            if (file) procesarExcel(file);
        });
        
        document.getElementById('resetStatsBtn').addEventListener('click', () => {
            if (confirm('¿Eliminar todos los datos cargados?')) {
                limpiarDatos();
            }
        });
        
        // Función global para exportar
        window.exportarTabla = exportarTabla;
    </script>
</body>
</html>