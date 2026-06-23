<?php
// pages/mapa_mexico_content.php
// Esta página se integrará en el dashboard.php
?>
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col">
            <h2><i class="fas fa-map"></i> Mapa de México - Estadísticas de Procedencia</h2>
            <p class="text-muted">Visualiza la procedencia de los cadetes por estado</p>
        </div>
    </div>

    <div class="row">
        <!-- Sidebar izquierda -->
        <div class="col-md-3">
            <div class="card">
                <div class="card-header">
                    <h6><i class="fas fa-upload"></i> Cargar datos</h6>
                </div>
                <div class="card-body">
                    <div class="upload-area" id="uploadArea" style="border: 2px dashed #667eea; border-radius: 10px; padding: 20px; text-align: center; background: #f8f9fa; cursor: pointer; transition: all 0.3s;">
                        <i class="fas fa-file-excel" style="font-size: 36px; color: #28a745;"></i>
                        <p class="mt-2 mb-0">Arrastra o haz clic para subir</p>
                        <small class="text-muted">Excel o CSV con columna "procedencia"</small>
                        <input type="file" id="excelFile" accept=".xlsx,.xls,.csv" style="display: none;">
                    </div>
                    
                    <div id="loadingStats" style="display: none; text-align: center; padding: 10px;">
                        <div class="spinner-border text-primary spinner-border-sm" role="status"></div> Procesando...
                    </div>
                    
                    <hr>
                    
                    <div id="statsContainer" style="display: none;">
                        <h6><i class="fas fa-chart-bar"></i> Estadísticas</h6>
                        <div id="statsTable" style="max-height: 300px; overflow-y: auto; font-size: 12px;">
                            <!-- Tabla dinámica -->
                        </div>
                        <div id="totalRegistros" class="mt-2 text-center" style="background: #f8f9fa; padding: 5px; border-radius: 5px;">
                            Total: <strong>0</strong> registros
                        </div>
                    </div>
                    
                    <div class="mt-2">
                        <button class="btn btn-sm btn-outline-secondary w-100" id="resetStatsBtn" style="display: none;">
                            <i class="fas fa-trash-alt"></i> Limpiar
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Mapa -->
        <div class="col-md-9">
            <div class="card">
                <div class="card-body p-0">
                    <div id="map" style="height: 600px; width: 100%; border-radius: 10px;"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.upload-area:hover {
    border-color: #764ba2 !important;
    background: #f0f0f0 !important;
}
.legend-color {
    width: 10px;
    height: 10px;
    display: inline-block;
    border-radius: 2px;
    margin-right: 5px;
}
.state-popup {
    min-width: 200px;
}
.state-popup h6 {
    margin: 0 0 5px 0;
    font-weight: bold;
}
.state-popup .count {
    font-size: 22px;
    font-weight: bold;
    color: #2c3e50;
}
.state-popup .percentage {
    font-size: 16px;
    color: #7f8c8d;
}
</style>

<!-- Librerías -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>

<!-- Configuración de estados -->
<script src="js/polygons/mexico-states.js"></script>

<script>
// ==========================================
// 1. Inicializar el mapa
// ==========================================
let map, allPolygons = {}, stateData = {};
let totalRegistros = 0;
let mapInitialized = false;

function initMap() {
    if (mapInitialized) return;
    
    map = L.map('map').setView([23.6345, -102.5528], 5);
    
    L.tileLayer('http://a.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OSM</a> &copy; CartoDB',
        subdomains: 'abcd',
        maxZoom: 19
    }).addTo(map);
    
    // Dibujar estados
    drawAllStates();
    
    mapInitialized = true;
    console.log('✅ Mapa de México inicializado');
}

// ==========================================
// 2. Dibujar todos los estados
// ==========================================
function drawAllStates() {
    const states = MEXICO_STATES;
    const layerGroup = L.layerGroup().addTo(map);
    
    Object.keys(states).forEach((key) => {
        const state = states[key];
        if (!state.points || state.points.length < 3) {
            console.warn(`⚠️ Estado ${state.name} sin puntos`);
            return;
        }
        
        const polygon = L.polygon(state.points, {
            color: '#2c3e50',
            weight: 1.5,
            opacity: 0.8,
            fillColor: '#95a5a6',
            fillOpacity: 0.1,
            stateId: state.id,
            stateName: state.name
        });
        
        // Popup inicial
        polygon.bindPopup(createPopupContent(state, null));
        
        // Eventos
        polygon.on('mouseover', function(e) {
            this.setStyle({ fillOpacity: 0.3, weight: 3, color: '#2c3e50' });
            this.bringToFront();
        });
        
        polygon.on('mouseout', function(e) {
            const hasData = stateData[state.id] && stateData[state.id].count > 0;
            this.setStyle({
                fillOpacity: hasData ? 0.6 : 0.1,
                weight: 1.5,
                color: '#2c3e50'
            });
        });
        
        polygon.addTo(layerGroup);
        allPolygons[state.id] = polygon;
    });
    
    // Leyenda
    addLegend();
    console.log(`✅ ${Object.keys(states).length} estados dibujados`);
}

// ==========================================
// 3. Crear contenido del popup
// ==========================================
function createPopupContent(state, data) {
    if (data && data.count > 0) {
        return `
            <div class="state-popup">
                <h6>${state.name}</h6>
                <hr>
                <p style="margin: 2px 0;">
                    <strong>Capital:</strong> ${state.capital}<br>
                    <strong>Región:</strong> ${state.region}
                </p>
                <hr>
                <div style="text-align: center;">
                    <div class="count">${data.count}</div>
                    <div style="color: #7f8c8d; font-size: 13px;">
                        ${data.percentage}% del total
                    </div>
                </div>
            </div>
        `;
    }
    
    return `
        <div class="state-popup">
            <h6>${state.name}</h6>
            <hr>
            <p style="margin: 2px 0;">
                <strong>Capital:</strong> ${state.capital}<br>
                <strong>Región:</strong> ${state.region}
            </p>
            <div style="margin-top: 5px; color: #7f8c8d; font-size: 13px;">
                <i>Sin datos disponibles</i>
            </div>
        </div>
    `;
}

// ==========================================
// 4. Agregar leyenda
// ==========================================
function addLegend() {
    const legend = L.control({ position: 'bottomright' });
    
    legend.onAdd = function() {
        const div = L.DomUtil.create('div', 'info legend');
        div.style.backgroundColor = 'white';
        div.style.padding = '10px';
        div.style.borderRadius = '5px';
        div.style.boxShadow = '0 2px 5px rgba(0,0,0,0.2)';
        div.style.maxHeight = '300px';
        div.style.overflowY = 'auto';
        div.style.minWidth = '150px';
        
        let html = '<h6 style="margin: 0 0 8px 0;"><strong>Estados</strong></h6>';
        
        const sortedStates = Object.values(MEXICO_STATES).sort((a, b) => a.name.localeCompare(b.name));
        
        sortedStates.forEach(state => {
            const hasData = stateData[state.id] && stateData[state.id].count > 0;
            const color = hasData ? stateData[state.id].color : '#95a5a6';
            
            html += `
                <div style="display: flex; align-items: center; margin-bottom: 2px; font-size: 11px;">
                    <div style="width: 10px; height: 10px; background-color: ${color}; margin-right: 6px; border-radius: 2px; border: 1px solid #ddd;"></div>
                    <span>${state.name}</span>
                    ${hasData ? `<span style="margin-left: auto; color: #2c3e50; font-weight: bold; font-size: 10px;">${stateData[state.id].count}</span>` : ''}
                </div>
            `;
        });
        
        div.innerHTML = html;
        return div;
    };
    
    legend.addTo(map);
}

// ==========================================
// 5. Procesar archivo Excel/CSV
// ==========================================
function procesarArchivo(file) {
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
            let estadoKey = null;
            const posiblesKeys = ['procedencia', 'Procedencia', 'PROCEDENCIA', 'estado', 'Estado', 'ESTADO'];
            
            for (let key of posiblesKeys) {
                if (rows.length > 0 && rows[0].hasOwnProperty(key)) {
                    estadoKey = key;
                    break;
                }
            }
            
            if (!estadoKey && rows.length > 0) {
                loadingDiv.style.display = 'none';
                alert(`No se encontró columna de procedencia. Columnas disponibles: ${Object.keys(rows[0]).join(', ')}`);
                return;
            }
            
            // Contar por estado
            const conteo = {};
            rows.forEach(row => {
                let estado = row[estadoKey] ? row[estadoKey].toString().trim() : '';
                if (estado) {
                    estado = normalizarEstado(estado);
                    conteo[estado] = (conteo[estado] || 0) + 1;
                }
            });
            
            // Mapear a IDs de estados
            const estadoData = {};
            let estadosConDatos = 0;
            
            Object.keys(conteo).forEach(nombreEstado => {
                const stateId = findStateIdByName(nombreEstado);
                if (stateId) {
                    const color = getRandomColor();
                    estadoData[stateId] = {
                        name: nombreEstado,
                        count: conteo[nombreEstado],
                        color: color,
                        percentage: ((conteo[nombreEstado] / rows.length) * 100).toFixed(1)
                    };
                    estadosConDatos++;
                }
            });
            
            stateData = estadoData;
            totalRegistros = rows.length;
            
            // Actualizar mapa
            actualizarMapa();
            mostrarEstadisticas();
            
            loadingDiv.style.display = 'none';
            document.getElementById('resetStatsBtn').style.display = 'block';
            document.getElementById('statsContainer').style.display = 'block';
            
            console.log(`✅ Procesado: ${totalRegistros} registros, ${estadosConDatos} estados con datos`);
            
        } catch (error) {
            loadingDiv.style.display = 'none';
            alert('Error al procesar: ' + error.message);
            console.error(error);
        }
    };
    
    reader.readAsArrayBuffer(file);
}

// ==========================================
// 6. Funciones auxiliares
// ==========================================
function normalizarEstado(nombre) {
    const mapeo = {
        'ags': 'Aguascalientes',
        'bc': 'Baja California',
        'bcs': 'Baja California Sur',
        'cdmx': 'Ciudad de México',
        'edomex': 'Estado de México',
        'nl': 'Nuevo León',
        'qro': 'Querétaro',
        'slp': 'San Luis Potosí'
    };
    
    const lower = nombre.toLowerCase().trim();
    return mapeo[lower] || nombre;
}

function findStateIdByName(nombre) {
    const states = MEXICO_STATES;
    
    for (let key of Object.keys(states)) {
        const state = states[key];
        if (state.name.toLowerCase() === nombre.toLowerCase() ||
            state.shortName?.toLowerCase() === nombre.toLowerCase()) {
            return key;
        }
    }
    
    for (let key of Object.keys(states)) {
        const state = states[key];
        if (state.name.toLowerCase().includes(nombre.toLowerCase()) ||
            nombre.toLowerCase().includes(state.name.toLowerCase())) {
            return key;
        }
    }
    
    return null;
}

function getRandomColor() {
    const colors = ['#FF6B6B', '#4ECDC4', '#45B7D1', '#96CEB4', '#F7DC6F',
        '#E74C3C', '#8E44AD', '#F39C12', '#2ECC71', '#3498DB',
        '#9B59B6', '#E67E22', '#1ABC9C', '#D35400', '#2C3E50',
        '#C0392B', '#27AE60', '#2980B9', '#16A085', '#D35400'
    ];
    return colors[Math.floor(Math.random() * colors.length)];
}

// ==========================================
// 7. Actualizar mapa y estadísticas
// ==========================================
function actualizarMapa() {
    Object.keys(allPolygons).forEach(stateId => {
        const polygon = allPolygons[stateId];
        const data = stateData[stateId];
        const state = MEXICO_STATES[stateId];
        
        if (data && data.count > 0) {
            polygon.setStyle({
                fillColor: data.color,
                fillOpacity: 0.6,
                weight: 2,
                color: '#2c3e50'
            });
            polygon.bindPopup(createPopupContent(state, data));
        } else {
            polygon.setStyle({
                fillColor: '#95a5a6',
                fillOpacity: 0.1,
                weight: 1.5,
                color: '#2c3e50'
            });
            polygon.bindPopup(createPopupContent(state, null));
        }
    });
    
    // Actualizar leyenda
    document.querySelector('.legend')?.remove();
    addLegend();
}

function mostrarEstadisticas() {
    const container = document.getElementById('statsContainer');
    const statsTable = document.getElementById('statsTable');
    const totalSpan = document.getElementById('totalRegistros');
    
    container.style.display = 'block';
    
    const sorted = Object.entries(stateData).sort((a, b) => b[1].count - a[1].count);
    
    let html = `<table style="width:100%; border-collapse: collapse;">
        <thead><tr><th>Estado</th><th>Cant</th><th>%</th></tr></thead>
        <tbody>`;
    
    sorted.forEach(([stateId, data]) => {
        const state = MEXICO_STATES[stateId];
        html += `<tr>
            <td><span class="legend-color" style="background-color: ${data.color};"></span>${state?.name || stateId}</td>
            <td><strong>${data.count}</strong></td>
            <td>${data.percentage}%</td>
        </tr>`;
    });
    
    html += '</tbody></table>';
    statsTable.innerHTML = html;
    totalSpan.innerHTML = `Total: <strong>${totalRegistros}</strong> registros`;
}

function limpiarDatos() {
    stateData = {};
    totalRegistros = 0;
    document.getElementById('statsContainer').style.display = 'none';
    document.getElementById('resetStatsBtn').style.display = 'none';
    actualizarMapa();
    document.getElementById('excelFile').value = '';
}

// ==========================================
// 8. Eventos
// ==========================================
document.addEventListener('DOMContentLoaded', function() {
    initMap();
    
    const uploadArea = document.getElementById('uploadArea');
    const excelFileInput = document.getElementById('excelFile');
    
    uploadArea.addEventListener('click', () => excelFileInput.click());
    
    uploadArea.addEventListener('dragover', (e) => {
        e.preventDefault();
        uploadArea.style.borderColor = '#764ba2';
        uploadArea.style.background = '#f0f0f0';
    });
    
    uploadArea.addEventListener('dragleave', (e) => {
        e.preventDefault();
        uploadArea.style.borderColor = '#667eea';
        uploadArea.style.background = '#f8f9fa';
    });
    
    uploadArea.addEventListener('drop', (e) => {
        e.preventDefault();
        uploadArea.style.borderColor = '#667eea';
        uploadArea.style.background = '#f8f9fa';
        const file = e.dataTransfer.files[0];
        if (file) procesarArchivo(file);
    });
    
    excelFileInput.addEventListener('change', (e) => {
        const file = e.target.files[0];
        if (file) procesarArchivo(file);
    });
    
    document.getElementById('resetStatsBtn').addEventListener('click', () => {
        if (confirm('¿Limpiar todos los datos?')) limpiarDatos();
    });
});
</script>