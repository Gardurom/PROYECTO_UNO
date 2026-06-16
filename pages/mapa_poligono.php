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
    <title>Mapa con Polígono - Estadísticas de Procedencia</title>
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
            background: rgba(0,0,0,0.7);
            color: white;
            padding: 8px 12px;
            border-radius: 8px;
            font-size: 12px;
            z-index: 1000;
            pointer-events: none;
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
    </style>
</head>
<body>
    <div class="navbar">
        <h2>🗺️ Mapa con Polígono - Estadísticas de Procedencia</h2>
        <a href="dashboard.php">← Volver</a>
    </div>
    
    <div class="main-container">
        <div class="sidebar-stats">
            <h5><i class="fas fa-upload"></i> Cargar archivo Excel</h5>
            <div class="upload-area" id="uploadArea">
                <i class="fas fa-file-excel" style="font-size: 36px; color: #28a745;"></i>
                <p class="mt-2 mb-0">Arrastra o haz clic para subir</p>
                <small class="text-muted">(Columnas: id, id_becario, nombre_completo, id_generacion, id_grupo, procedencia)</small>
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
            
            <div class="mt-3">
                <button class="btn btn-sm btn-outline-secondary w-100" id="resetStatsBtn" style="display: none;">
                    <i class="fas fa-trash-alt"></i> Limpiar estadísticas
                </button>
            </div>
        </div>
        
        <div class="map-container">
            <div id="map"></div>
            <div class="info-panel">
                <strong>📍 Polígono:</strong> Campo Militar No. 37-C (ANSP)<br>
                <strong>📐 Vértices:</strong> 22 puntos<br>
                <strong>🎨 Color:</strong> Rojo
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://kit.fontawesome.com/a5c6e8a0e2.js" crossorigin="anonymous"></script>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    
    <script>
        // ==========================================
        // 1. Inicializar mapa y polígono
        // ==========================================
        var map = L.map('map').setView([19.810863, -99.275244], 15);
        
        L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OSM</a> &copy; CartoDB',
            subdomains: 'abcd',
            maxZoom: 19
        }).addTo(map);
        
        // Coordenadas del polígono (Campo Militar No. 37-C)
        var polygonPoints = [
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
        ];
        
        var polygon = L.polygon(polygonPoints, {
            color: '#e74c3c',
            weight: 3,
            opacity: 0.8,
            fillColor: '#e74c3c',
            fillOpacity: 0.3
        }).addTo(map);
        
        // Popup inicial (se actualizará después de cargar datos)
        polygon.bindPopup(`
            <b>🏛️ Campo Militar No. 37-C (San Miguel de los Jagüeyes)</b><br>
            Academia Nacional de Seguridad Pública (ANSP)<br>
            <i>Cargue un archivo Excel para ver estadísticas de procedencia.</i>
        `);
        
        map.fitBounds(polygon.getBounds());
        
        // ==========================================
        // 2. Variables para almacenar estadísticas
        // ==========================================
        let estadisticas = {};
        let totalRegistros = 0;
        
        // ==========================================
        // 3. Función para leer Excel y calcular estadísticas
        // ==========================================
        function procesarExcel(file) {
            const loadingDiv = document.getElementById('loadingStats');
            loadingDiv.style.display = 'block';
            
            const reader = new FileReader();
            reader.onload = function(e) {
                const data = new Uint8Array(e.target.result);
                const workbook = XLSX.read(data, { type: 'array' });
                const firstSheet = workbook.Sheets[workbook.SheetNames[0]];
                const rows = XLSX.utils.sheet_to_json(firstSheet);
                
                // Buscar la columna 'procedencia' (puede tener variantes)
                let procedenciaKey = null;
                const posiblesKeys = ['procedencia', 'Procedencia', 'PROCEDENCIA', 'estado', 'Estado'];
                for (let key of posiblesKeys) {
                    if (rows.length > 0 && rows[0].hasOwnProperty(key)) {
                        procedenciaKey = key;
                        break;
                    }
                }
                
                if (!procedenciaKey && rows.length > 0) {
                    // Si no encuentra, mostrar error
                    loadingDiv.style.display = 'none';
                    alert('No se encontró la columna "procedencia" en el archivo. Verifique los encabezados.');
                    return;
                }
                
                // Contar frecuencias
                const conteo = {};
                rows.forEach(row => {
                    let proc = row[procedenciaKey] ? row[procedenciaKey].toString().trim() : 'Sin Localidad';
                    if (proc === '') proc = 'Sin Localidad';
                    conteo[proc] = (conteo[proc] || 0) + 1;
                });
                
                // Ordenar alfabéticamente (opcional)
                const sorted = Object.keys(conteo).sort().reduce((obj, key) => {
                    obj[key] = conteo[key];
                    return obj;
                }, {});
                
                estadisticas = sorted;
                totalRegistros = rows.length;
                
                // Mostrar estadísticas en la sidebar
                mostrarEstadisticas();
                
                // Actualizar el popup del polígono con las estadísticas
                actualizarPopupPoligono();
                
                loadingDiv.style.display = 'none';
                document.getElementById('resetStatsBtn').style.display = 'block';
            };
            
            reader.onerror = function() {
                loadingDiv.style.display = 'none';
                alert('Error al leer el archivo.');
            };
            
            reader.readAsArrayBuffer(file);
        }
        
        // ==========================================
        // 4. Mostrar tabla de estadísticas en sidebar
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
        // 5. Actualizar contenido del popup del polígono
        // ==========================================
        function actualizarPopupPoligono() {
            if (Object.keys(estadisticas).length === 0) {
                polygon.bindPopup(`
                    <b>🏛️ Campo Militar No. 37-C (ANSP)</b><br>
                    <i>Cargue un archivo Excel para ver estadísticas de procedencia.</i>
                `);
                return;
            }
            
            // Crear HTML con las estadísticas (máximo 15 filas para no saturar el popup)
            let filas = '';
            let count = 0;
            for (let [estado, cantidad] of Object.entries(estadisticas)) {
                if (count < 15) {
                    let porcentaje = ((cantidad / totalRegistros) * 100).toFixed(1);
                    filas += `<tr><td style="font-size:11px;">${estado}</td><td style="text-align:center;">${cantidad}</td><td style="text-align:center;">${porcentaje}%</td></tr>`;
                }
                count++;
            }
            let restantes = Object.keys(estadisticas).length - 15;
            let mensajeExtra = restantes > 0 ? `<tr><td colspan="3" style="font-size:10px; text-align:center;">... y ${restantes} más (ver panel lateral)</td></tr>` : '';
            
            let popupContent = `
                <div style="min-width: 280px; max-height: 400px; overflow-y: auto;">
                    <b>🏛️ Academia Nacional de Seguridad Pública (ANSP)</b><br>
                    <small>Campo Militar No. 37-C</small>
                    <hr style="margin:5px 0;">
                    <b>📊 Procedencia de becarios</b>
                    <table style="width:100%; font-size:12px; margin-top:5px;">
                        <thead><tr><th>Estado</th><th>Cant</th><th>%</th></tr></thead>
                        <tbody>
                            ${filas}
                            ${mensajeExtra}
                        </tbody>
                    </table>
                    <hr style="margin:5px 0;">
                    <div style="font-size:11px; text-align:center;">Total becarios: <b>${totalRegistros}</b></div>
                </div>
            `;
            
            polygon.bindPopup(popupContent);
        }
        
        // ==========================================
        // 6. Limpiar estadísticas
        // ==========================================
        function limpiarEstadisticas() {
            estadisticas = {};
            totalRegistros = 0;
            document.getElementById('statsContainer').style.display = 'none';
            document.getElementById('resetStatsBtn').style.display = 'none';
            actualizarPopupPoligono();
        }
        
        // ==========================================
        // 7. Eventos de carga de archivo (drag & drop + clic)
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
                excelFileInput.value = '';
            }
        });
    </script>
</body>
</html>