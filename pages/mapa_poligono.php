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
    <title>Mapa con Polígonos - Estadísticas de Procedencia</title>
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
            height: calc(100vh - 56px);
        }
        .sidebar-stats {
            width: 320px;
            background: white;
            border-right: 1px solid #ddd;
            padding: 15px;
            overflow-y: auto;
            box-shadow: 2px 0 5px rgba(0,0,0,0.05);
            z-index: 10;
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
            padding: 20px;
            text-align: center;
            background: #f8f9fa;
            margin-bottom: 20px;
            transition: all 0.3s;
            cursor: pointer;
        }
        .upload-area:hover {
            border-color: #e74c3c;
            background: #eef2f7;
        }
        .stats-table {
            font-size: 13px;
            max-height: 400px;
            overflow-y: auto;
        }
        .stats-table table {
            width: 100%;
            border-collapse: collapse;
        }
        .stats-table th, .stats-table td {
            padding: 6px 8px;
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
        .btn-custom {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            color: white;
            width: 100%;
            padding: 8px;
        }
        .btn-custom:hover {
            transform: translateY(-2px);
        }
        .loading {
            text-align: center;
            padding: 10px;
            display: none;
        }
        .polygon-legend {
            margin-top: 15px;
            background: #f8f9fa;
            padding: 10px;
            border-radius: 5px;
        }
        .polygon-legend-item {
            display: flex;
            align-items: center;
            margin-bottom: 4px;
            font-size: 12px;
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
        }
    </style>
</head>
<body>
    <div class="navbar">
        <h2>🗺️ Mapa con Polígonos - Estadísticas de Procedencia</h2>
        <a href="dashboard.php">← Volver</a>
    </div>
    
    <div class="main-container">
        <div class="sidebar-stats">
            <h5><i class="fas fa-upload"></i> Cargar archivo Excel</h5>
            <div class="upload-area" id="uploadArea">
                <i class="fas fa-file-excel" style="font-size: 36px; color: #28a745;"></i>
                <p class="mt-2 mb-0">Arrastra o haz clic para subir</p>
                <small class="text-muted">(Columnas: id, nombre, procedencia)</small>
                <input type="file" id="excelFile" accept=".xlsx,.xls" style="display: none;">
            </div>
            <div class="loading" id="loadingStats">
                <div class="spinner-border text-primary spinner-border-sm" role="status"></div> Procesando...
            </div>
            
            <div id="statsContainer" style="display: none;">
                <hr>
                <h6><i class="fas fa-chart-bar"></i> Estadísticas de Procedencia</h6>
                <div class="stats-table" id="statsTable">
                    <!-- Aquí se mostrará la tabla de conteos -->
                </div>
                <p class="text-muted small mt-2" id="totalRegistros"></p>
            </div>
            
            <!-- Leyenda de polígonos -->
            <div class="polygon-legend" id="polygonLegend">
                <h6><i class="fas fa-map-marked-alt"></i> Polígonos</h6>
                <div class="polygon-legend-item">
                    <span class="color-box" style="background: #e74c3c;"></span>
                    <span>Campo Militar 37-C (ANSP)</span>
                    <span class="count-badge" id="countPoly1">0</span>
                </div>
                <div class="polygon-legend-item">
                    <span class="color-box" style="background: #2ecc71;"></span>
                    <span>Zona Militar (ZM)</span>
                    <span class="count-badge" id="countPoly2">0</span>
                </div>
                <div class="polygon-legend-item">
                    <span class="color-box" style="background: #3498db;"></span>
                    <span>Base Aérea (BA)</span>
                    <span class="count-badge" id="countPoly3">0</span>
                </div>
            </div>
            
            <div class="mt-3">
                <button class="btn btn-sm btn-outline-secondary w-100" id="resetStatsBtn" style="display: none;">
                    <i class="fas fa-trash-alt"></i> Limpiar estadísticas
                </button>
            </div>
        </div>
        
        <div class="map-container">
            <div id="map"></div>
            <div class="info-panel">
                <strong>📍 Polígonos:</strong><br>
                <span class="color-box" style="background: #e74c3c;"></span> ANSP (Rojo)<br>
                <span class="color-box" style="background: #2ecc71;"></span> ZM (Verde)<br>
                <span class="color-box" style="background: #3498db;"></span> BA (Azul)
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://kit.fontawesome.com/a5c6e8a0e2.js" crossorigin="anonymous"></script>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    
    <script>
        // ==========================================
        // 1. DEFINICIÓN DE POLÍGONOS
        // ==========================================
        const POLYGONS = {
            // Polígono 1: Campo Militar 37-C (ANSP) - Rojo
            ansp: {
                id: 'ansp',
                name: 'Campo Militar 37-C (ANSP)',
                shortName: 'ANSP',
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
            
            // Polígono 2: Zona Militar - Verde (ejemplo)
            zm: {
                id: 'zm',
                name: 'Zona Militar',
                shortName: 'ZM',
                color: '#2ecc71',
                fillOpacity: 0.25,
                weight: 3,
                opacity: 0.8,
                points: [
                    [19.780000, -99.250000],
                    [19.780000, -99.230000],
                    [19.800000, -99.230000],
                    [19.800000, -99.250000],
                    [19.780000, -99.250000]
                ]
            },
            
            // Polígono 3: Base Aérea - Azul (ejemplo)
            ba: {
                id: 'ba',
                name: 'Base Aérea',
                shortName: 'BA',
                color: '#3498db',
                fillOpacity: 0.25,
                weight: 3,
                opacity: 0.8,
                points: [
                    [19.850000, -99.280000],
                    [19.850000, -99.260000],
                    [19.870000, -99.260000],
                    [19.870000, -99.280000],
                    [19.850000, -99.280000]
                ]
            }
        };

        // ==========================================
        // 2. Inicializar mapa
        // ==========================================
        var map = L.map('map').setView([19.82, -99.27], 13);
        
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
                <i>Cargue datos para ver estadísticas</i>
            `);
            
            // Guardar referencia
            polygons[key] = polygon;
            polygonData[key] = {
                config: config,
                count: 0,
                polygon: polygon
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
        // 4. Variables para estadísticas
        // ==========================================
        let estadisticas = {};
        let totalRegistros = 0;

        // ==========================================
        // 5. Función para contar puntos en polígonos
        // ==========================================
        function contarPuntosEnPoligonos(rows, procedenciaKey) {
            // Reiniciar conteos
            Object.keys(polygonData).forEach(function(key) {
                polygonData[key].count = 0;
            });
            
            // Para cada fila, verificar en qué polígono cae
            rows.forEach(function(row) {
                // Obtener procedencia
                let proc = row[procedenciaKey] ? row[procedenciaKey].toString().trim() : 'Sin Localidad';
                
                // Aquí podrías tener lógica para determinar en qué polígono cae
                // Por ejemplo, basado en la procedencia o coordenadas
                
                // Ejemplo: Asignar basado en la procedencia
                if (proc.toLowerCase().includes('ansp') || proc.toLowerCase().includes('campo militar')) {
                    polygonData.ansp.count++;
                } else if (proc.toLowerCase().includes('zm') || proc.toLowerCase().includes('zona militar')) {
                    polygonData.zm.count++;
                } else if (proc.toLowerCase().includes('ba') || proc.toLowerCase().includes('base aerea')) {
                    polygonData.ba.count++;
                }
                // Si no coincide con ningún polígono, no se cuenta
            });
            
            // Actualizar popups y leyenda
            Object.keys(polygonData).forEach(function(key) {
                var data = polygonData[key];
                var config = data.config;
                var count = data.count;
                
                // Actualizar popup
                var popupContent = `
                    <b>${config.name}</b><br>
                    <span style="color: ${config.color}; font-weight: bold;">●</span> ${config.shortName}<br>
                    <hr style="margin: 5px 0;">
                    <div style="text-align: center;">
                        <div style="font-size: 24px; font-weight: bold; color: ${config.color};">${count}</div>
                        <div style="font-size: 12px; color: #7f8c8d;">personas en esta zona</div>
                    </div>
                `;
                data.polygon.bindPopup(popupContent);
            });
            
            // Actualizar leyenda
            document.getElementById('countPoly1').textContent = polygonData.ansp.count;
            document.getElementById('countPoly2').textContent = polygonData.zm.count;
            document.getElementById('countPoly3').textContent = polygonData.ba.count;
        }

        // ==========================================
        // 6. Función para leer Excel
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
                    
                    // Buscar columna de procedencia
                    let procedenciaKey = null;
                    const posiblesKeys = ['procedencia', 'Procedencia', 'PROCEDENCIA', 'estado', 'Estado', 'zona'];
                    
                    for (let key of posiblesKeys) {
                        if (rows.length > 0 && rows[0].hasOwnProperty(key)) {
                            procedenciaKey = key;
                            break;
                        }
                    }
                    
                    if (!procedenciaKey && rows.length > 0) {
                        loadingDiv.style.display = 'none';
                        alert('No se encontró la columna "procedencia" en el archivo. Verifique los encabezados.');
                        return;
                    }
                    
                    // Contar frecuencias por procedencia
                    const conteo = {};
                    rows.forEach(row => {
                        let proc = row[procedenciaKey] ? row[procedenciaKey].toString().trim() : 'Sin Localidad';
                        if (proc === '') proc = 'Sin Localidad';
                        conteo[proc] = (conteo[proc] || 0) + 1;
                    });
                    
                    // Ordenar
                    const sorted = Object.keys(conteo).sort().reduce((obj, key) => {
                        obj[key] = conteo[key];
                        return obj;
                    }, {});
                    
                    estadisticas = sorted;
                    totalRegistros = rows.length;
                    
                    // Mostrar estadísticas en sidebar
                    mostrarEstadisticas();
                    
                    // Contar puntos en polígonos
                    contarPuntosEnPoligonos(rows, procedenciaKey);
                    
                    // Mostrar botón limpiar
                    document.getElementById('resetStatsBtn').style.display = 'block';
                    
                } catch (error) {
                    alert('Error al procesar el archivo: ' + error.message);
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
        // 7. Mostrar estadísticas en sidebar
        // ==========================================
        function mostrarEstadisticas() {
            const container = document.getElementById('statsContainer');
            const statsTableDiv = document.getElementById('statsTable');
            const totalSpan = document.getElementById('totalRegistros');
            
            if (Object.keys(estadisticas).length === 0) {
                container.style.display = 'none';
                return;
            }
            
            container.style.display = 'block';
            
            let html = '<table class="table table-sm table-hover"><thead><tr><th>Procedencia</th><th>Cantidad</th><th>%</th></tr></thead><tbody>';
            for (let [estado, count] of Object.entries(estadisticas)) {
                let porcentaje = ((count / totalRegistros) * 100).toFixed(1);
                html += `<tr>
                            <td>${estado}</td>
                            <td><span class="badge-proc">${count}</span></td>
                            <td>${porcentaje}%</td>
                         </tr>`;
            }
            html += '</tbody></table>';
            statsTableDiv.innerHTML = html;
            totalSpan.innerHTML = `Total registros: <strong>${totalRegistros}</strong>`;
        }

        // ==========================================
        // 8. Limpiar estadísticas
        // ==========================================
        function limpiarEstadisticas() {
            estadisticas = {};
            totalRegistros = 0;
            
            // Resetear conteos
            Object.keys(polygonData).forEach(function(key) {
                polygonData[key].count = 0;
                var config = polygonData[key].config;
                polygonData[key].polygon.bindPopup(`
                    <b>${config.name}</b><br>
                    <span style="color: ${config.color}; font-weight: bold;">●</span> ${config.shortName}<br>
                    <i>Cargue datos para ver estadísticas</i>
                `);
            });
            
            document.getElementById('statsContainer').style.display = 'none';
            document.getElementById('resetStatsBtn').style.display = 'none';
            document.getElementById('countPoly1').textContent = '0';
            document.getElementById('countPoly2').textContent = '0';
            document.getElementById('countPoly3').textContent = '0';
            
            document.getElementById('excelFile').value = '';
        }

        // ==========================================
        // 9. Eventos de carga
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
            if (file && (file.type === 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' || file.type === 'application/vnd.ms-excel')) {
                procesarExcel(file);
            } else {
                alert('Por favor, sube un archivo Excel válido (.xlsx o .xls)');
            }
        });
        
        excelFileInput.addEventListener('change', (e) => {
            const file = e.target.files[0];
            if (file) procesarExcel(file);
        });
        
        document.getElementById('resetStatsBtn').addEventListener('click', () => {
            if (confirm('¿Eliminar las estadísticas actuales?')) {
                limpiarEstadisticas();
            }
        });
    </script>
</body>
</html>