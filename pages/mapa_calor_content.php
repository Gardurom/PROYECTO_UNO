<?php
// pages/mapa_calor_content.php - Versión con pantalla completa corregida
?>
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="stats-card">
                <h4><i class="fas fa-fire"></i> Mapa de Calor - Análisis de Densidad</h4>
                <p>Carga un archivo Excel con coordenadas para visualizar mapa de calor, malla de densidad y encontrar puntos cercanos</p>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-4">
            <!-- Panel izquierdo (sin cambios) -->
            <div class="stats-card">
                <h5><i class="fas fa-upload"></i> Cargar Archivo Excel</h5>
                <div class="upload-area" id="uploadArea">
                    <i class="fas fa-file-excel" style="font-size: 48px; color: #28a745;"></i>
                    <h5 class="mt-3">Arrastra o haz clic para subir</h5>
                    <p class="text-muted">Formatos soportados: .xlsx, .xls</p>
                    <input type="file" id="excelFile" accept=".xlsx,.xls" style="display: none;">
                    <button class="btn btn-custom mt-3" onclick="document.getElementById('excelFile').click()">
                        <i class="fas fa-file-upload"></i> Seleccionar Archivo
                    </button>
                </div>

                <div class="mt-4">
                    <h5><i class="fas fa-sliders-h"></i> Controles</h5>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="toggleHeatmap" checked onchange="toggleHeatmap()">
                        <label class="form-check-label" for="toggleHeatmap">Mapa de Calor</label>
                    </div>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="toggleGrid" onchange="toggleGrid()">
                        <label class="form-check-label" for="toggleGrid">Malla de Densidad</label>
                    </div>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="toggleClusters" onchange="toggleClusters()">
                        <label class="form-check-label" for="toggleClusters">Agrupar Puntos</label>
                    </div>
                    <div class="mt-2">
                        <label class="form-label">Radio del mapa de calor</label>
                        <input type="range" class="form-range" id="heatRadius" min="10" max="80" value="40" onchange="updateHeatmapRadius()">
                        <small class="text-muted">Valor: <span id="radiusValue">40</span></small>
                    </div>
                    <div class="mt-2">
                        <label class="form-label">Intensidad</label>
                        <input type="range" class="form-range" id="heatIntensity" min="1" max="10" value="5" onchange="updateHeatmap()">
                        <small class="text-muted">Valor: <span id="intensityValue">5</span></small>
                    </div>
                </div>

                <!-- Capas WMS de la NASA -->
                <div class="stats-card">
                    <h5><i class="fas fa-satellite"></i> Capas Satelitales (NASA)</h5>
                    <div id="wmsLayersContainer" style="max-height: 400px; overflow-y: auto;">
                        <!-- Las capas se generarán dinámicamente -->
                    </div>
                </div>

                <div class="mt-4">
                    <h5><i class="fas fa-info-circle"></i> Información</h5>
                    <div id="statsInfo">
                        <p><i class="fas fa-map-marker-alt"></i> Puntos: <strong id="markerCount">0</strong></p>
                        <p><i class="fas fa-layer-group"></i> Clusters: <strong id="clusterCount">0</strong></p>
                        <p><i class="fas fa-thermometer-half"></i> Densidad máxima: <strong id="maxDensity">0</strong></p>
                    </div>
                    <button class="btn btn-sm btn-danger" onclick="clearAllMarkers()">
                        <i class="fas fa-trash"></i> Limpiar Datos
                    </button>
                </div>
            </div>

            <div class="stats-card mt-3">
                <h5><i class="fas fa-search-location"></i> Puntos Cercanos</h5>
                <p class="small text-muted">Haz clic en el mapa para encontrar los 3 puntos más cercanos</p>
                <div id="nearestPoints">
                    <div class="text-muted text-center py-2">
                        <i class="fas fa-mouse-pointer"></i> Haz clic en el mapa
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <!-- Tarjeta del mapa -->
            <div class="stats-card" id="mapCard">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h5><i class="fas fa-map"></i> Mapa de Calor</h5>
                    <div>
                        <button class="btn btn-sm btn-outline-primary" onclick="toggleFullscreen()" title="Pantalla completa" id="fullscreenBtn">
                            <i class="fas fa-expand"></i> <span class="d-none d-md-inline">Pantalla Completa</span>
                        </button>
                        <button class="btn btn-sm btn-outline-secondary" onclick="centrarMapa()" title="Centrar mapa">
                            <i class="fas fa-crosshairs"></i>
                        </button>
                    </div>
                </div>
                <div class="map-container" id="mapContainer">
                    <div id="map" style="height: 600px; width: 100%; border-radius: 10px;"></div>
                </div>
            </div>

            <!-- Lista de Coordenadas -->
            <div class="stats-card mt-3" style="height: calc(13% - 15px); display: flex; flex-direction: column; min-height: 0;">
                <h5><i class="fas fa-list"></i> Lista de Coordenadas</h5>
                <div class="table-responsive" style="flex: 1; overflow-y: auto; min-height: 0; margin-top: 8px;">
                    <table class="table table-sm table-hover" style="margin-bottom: 0;">
                        <thead style="position: sticky; top: 0; background: #2c3e50; z-index: 10;">
                            <tr>
                                <th style="color: white; padding: 8px 6px; text-align: center; width: 40px;">#</th>
                                <th style="color: white; padding: 8px 6px; text-align: left;">Título</th>
                                <th style="color: white; padding: 8px 6px; text-align: center; width: 100px;">Latitud</th>
                                <th style="color: white; padding: 8px 6px; text-align: center; width: 100px;">Longitud</th>
                                <th style="color: white; padding: 8px 6px; text-align: center; width: 60px;">Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="coordinatesTableBody">
                            <tr>
                                <td colspan="5" class="text-center">No hay coordenadas cargadas</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    /* ============================================================
       ESTILOS EXISTENTES
       ============================================================ */
    .stats-card {
        background: white;
        border-radius: 10px;
        padding: 20px;
        margin-bottom: 20px;
        box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
    }

    .upload-area {
        border: 2px dashed #667eea;
        border-radius: 10px;
        padding: 30px;
        text-align: center;
        background: #f8f9fa;
        transition: all 0.3s;
        cursor: pointer;
    }

    .upload-area:hover {
        border-color: #764ba2;
        background: #eef2f7;
    }

    .map-container {
        border-radius: 10px;
        overflow: hidden;
        transition: all 0.3s ease;
    }

    .leaflet-popup-content {
        min-width: 200px;
    }

    .nearest-point-item {
        padding: 8px 12px;
        border-left: 3px solid #667eea;
        margin-bottom: 5px;
        background: #f8f9fa;
        border-radius: 4px;
        transition: all 0.3s;
    }

    .nearest-point-item:hover {
        background: #eef2f7;
        transform: translateX(5px);
    }

    .nearest-point-item .distancia {
        color: #764ba2;
        font-weight: bold;
    }

    .heat-circle {
        transition: all 0.3s;
    }

    .heat-circle:hover {
        transform: scale(1.1);
    }

    .cluster-marker {
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        color: white;
        font-weight: bold;
        font-size: 12px;
        border: 2px solid white;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
        transition: all 0.3s;
        cursor: pointer;
    }

    .cluster-marker:hover {
        transform: scale(1.1);
    }

   /* ============================================================
   ESTILOS DE PANTALLA COMPLETA - API NATIVA
   ============================================================ */

/* Modo Fullscreen nativo */
#mapCard.fullscreen-mode {
    position: fixed !important;
    top: 0 !important;
    left: 0 !important;
    right: 0 !important;
    bottom: 0 !important;
    width: 100vw !important;
    height: 100vh !important;
    max-width: 100vw !important;
    max-height: 100vh !important;
    z-index: 2147483647 !important; /* Máximo z-index */
    background: #1a1a2e !important;
    padding: 0 !important;
    margin: 0 !important;
    border-radius: 0 !important;
    overflow: hidden !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
}

#mapCard.fullscreen-mode .map-container {
    width: 100vw !important;
    height: 100vh !important;
    border-radius: 0 !important;
    overflow: hidden !important;
}

#mapCard.fullscreen-mode #map {
    height: 100vh !important;
    width: 100vw !important;
    border-radius: 0 !important;
    position: absolute !important;
    top: 0 !important;
    left: 0 !important;
    right: 0 !important;
    bottom: 0 !important;
}

/* Ocultar elementos de la tarjeta en fullscreen */
#mapCard.fullscreen-mode h5,
#mapCard.fullscreen-mode .d-flex.justify-content-between,
#mapCard.fullscreen-mode .mb-2 {
    display: none !important;
}

/* Botón de salir mejorado */
.btn-fullscreen-exit {
    position: fixed !important;
    top: 20px !important;
    right: 20px !important;
    z-index: 2147483647 !important;
    background: rgba(220, 53, 69, 0.95) !important;
    color: white !important;
    border: none !important;
    padding: 12px 24px !important;
    border-radius: 8px !important;
    font-weight: bold !important;
    font-size: 14px !important;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.5) !important;
    transition: all 0.3s ease !important;
    cursor: pointer !important;
    display: flex !important;
    align-items: center !important;
    gap: 10px !important;
    backdrop-filter: blur(10px) !important;
    border: 1px solid rgba(255, 255, 255, 0.2) !important;
}

.btn-fullscreen-exit:hover {
    transform: scale(1.05) !important;
    background: rgba(200, 35, 51, 1) !important;
    box-shadow: 0 6px 30px rgba(220, 53, 69, 0.6) !important;
}

.btn-fullscreen-exit i {
    font-size: 18px !important;
}

/* Indicador de estado */
.fullscreen-indicator {
    position: fixed !important;
    top: 20px !important;
    left: 50% !important;
    transform: translateX(-50%) !important;
    z-index: 2147483647 !important;
    background: rgba(0, 0, 0, 0.7) !important;
    color: white !important;
    padding: 8px 20px !important;
    border-radius: 20px !important;
    font-size: 12px !important;
    backdrop-filter: blur(10px) !important;
    border: 1px solid rgba(255, 255, 255, 0.1) !important;
    pointer-events: none !important;
    animation: fadeInOut 3s ease-in-out !important;
}

@keyframes fadeInOut {
    0% { opacity: 0; transform: translateX(-50%) translateY(-10px); }
    10% { opacity: 1; transform: translateX(-50%) translateY(0); }
    80% { opacity: 1; transform: translateX(-50%) translateY(0); }
    100% { opacity: 0; transform: translateX(-50%) translateY(-10px); }
}

/* Fallback CSS cuando la API nativa no está disponible */
#mapCard.fullscreen-mode-css {
    position: fixed !important;
    top: 0 !important;
    left: 0 !important;
    right: 0 !important;
    bottom: 0 !important;
    width: 100vw !important;
    height: 100vh !important;
    max-width: 100vw !important;
    max-height: 100vh !important;
    z-index: 99999 !important;
    background: #1a1a2e !important;
    padding: 0 !important;
    margin: 0 !important;
    border-radius: 0 !important;
    overflow: hidden !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
}

#mapCard.fullscreen-mode-css .map-container {
    width: 100vw !important;
    height: 100vh !important;
    border-radius: 0 !important;
    overflow: hidden !important;
}

#mapCard.fullscreen-mode-css #map {
    height: 100vh !important;
    width: 100vw !important;
    border-radius: 0 !important;
    position: absolute !important;
    top: 0 !important;
    left: 0 !important;
    right: 0 !important;
    bottom: 0 !important;
}

#mapCard.fullscreen-mode-css h5,
#mapCard.fullscreen-mode-css .d-flex.justify-content-between,
#mapCard.fullscreen-mode-css .mb-2 {
    display: none !important;
}

/* Controles del mapa en fullscreen */
#mapCard.fullscreen-mode .leaflet-control-zoom,
#mapCard.fullscreen-mode-css .leaflet-control-zoom {
    box-shadow: 0 2px 15px rgba(0, 0, 0, 0.3) !important;
}

#mapCard.fullscreen-mode .leaflet-control-zoom a,
#mapCard.fullscreen-mode-css .leaflet-control-zoom a {
    background: rgba(255, 255, 255, 0.95) !important;
    color: #333 !important;
}

#mapCard.fullscreen-mode .leaflet-control-zoom a:hover,
#mapCard.fullscreen-mode-css .leaflet-control-zoom a:hover {
    background: white !important;
}

/* Bloquear scroll */
body.fullscreen-active {
    overflow: hidden !important;
    position: fixed !important;
    width: 100% !important;
    height: 100% !important;
}

/* Transiciones suaves */
#mapCard.fullscreen-mode,
#mapCard.fullscreen-mode-css {
    animation: fullscreenIn 0.3s ease-out !important;
}

@keyframes fullscreenIn {
    0% { opacity: 0; transform: scale(0.95); }
    100% { opacity: 1; transform: scale(1); }
}

/* Responsive */
@media (max-width: 768px) {
    .btn-fullscreen-exit {
        top: 10px !important;
        right: 10px !important;
        padding: 8px 16px !important;
        font-size: 12px !important;
    }
    
    .btn-fullscreen-exit span {
        display: none !important;
    }
    
    .btn-fullscreen-exit i {
        font-size: 20px !important;
    }
}
</style>

<!-- Solo Leaflet y XLSX -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>

<script>
    // ============================================================
    // ============================================================
    // 1. CONFIGURACIÓN GLOBAL
    // ============================================================
    // ============================================================

    const APP = {
        map: null,
        heatLayer: null,
        gridLayer: null,
        clusterLayer: null,
        circleLayer: null,
        mapMarkers: [],
        currentCoordinates: [],
        heatData: [],
        heatCircles: [],
        clusterMarkers: [],
        clusterGroups: {},
        wmsLayers: {},
        activeWmsLayers: []
    };

    // ============================================================
    // ============================================================
    // 2. UTILIDADES
    // ============================================================
    // ============================================================

    const Utils = {
        escapeHtml: function(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        },

        calcularDistancia: function(lat1, lng1, lat2, lng2) {
            const R = 6371;
            const dLat = (lat2 - lat1) * Math.PI / 180;
            const dLng = (lng2 - lng1) * Math.PI / 180;
            const a = Math.sin(dLat / 2) * Math.sin(dLat / 2) +
                Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
                Math.sin(dLng / 2) * Math.sin(dLng / 2);
            const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
            return R * c;
        },

        getColorByDensity: function(count) {
            if (count >= 10) return '#dc3545';
            if (count >= 5) return '#ffc107';
            if (count >= 2) return '#17a2b8';
            return '#28a745';
        },

        getHeatColor: function(intensity, maxIntensity) {
            const ratio = intensity / maxIntensity;
            if (ratio >= 0.8) return { r: 220, g: 20, b: 20 };
            if (ratio >= 0.6) return { r: 255, g: 165, b: 0 };
            if (ratio >= 0.4) return { r: 255, g: 255, b: 0 };
            if (ratio >= 0.2) return { r: 0, g: 255, b: 255 };
            return { r: 0, g: 100, b: 255 };
        },

        getHeatColorHex: function(intensity, maxIntensity) {
            const color = this.getHeatColor(intensity, maxIntensity);
            return `rgb(${color.r}, ${color.g}, ${color.b})`;
        },

        getClusterColor: function(count) {
            if (count >= 20) return '#dc3545';
            if (count >= 10) return '#ffc107';
            if (count >= 5) return '#17a2b8';
            return '#28a745';
        }
    };

    // ============================================================
    // ============================================================
    // 3. MAPA BASE
    // ============================================================
    // ============================================================

    const MapaBase = {
        init: function() {
            APP.map = L.map('map', {
                center: [19.4326, -99.1332],
                zoom: 6,
                zoomControl: true
            });

            L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OSM</a> &copy; CartoDB',
                subdomains: 'abcd',
                maxZoom: 19
            }).addTo(APP.map);

            WmsLayers.init();

            APP.map.on('click', function(e) {
                const lat = e.latlng.lat;
                const lng = e.latlng.lng;
                PuntosCercanos.buscar(lat, lng);
            });

            console.log('🗺️ Mapa base inicializado');
            return APP.map;
        },

        centrar: function(lat, lng, zoom = 15) {
            APP.map.setView([lat, lng], zoom);
        },

        ajustarVista: function(coordinates) {
            if (coordinates && coordinates.length > 0) {
                const bounds = L.latLngBounds(coordinates.map(c => [c.lat, c.lng]));
                APP.map.fitBounds(bounds);
            }
        },

        limpiarTodo: function() {
            APP.mapMarkers.forEach(m => APP.map.removeLayer(m));
            APP.mapMarkers = [];

            if (APP.heatLayer) {
                APP.map.removeLayer(APP.heatLayer);
                APP.heatLayer = null;
                APP.heatCircles = [];
            }

            if (APP.gridLayer) {
                APP.map.removeLayer(APP.gridLayer);
                APP.gridLayer = null;
            }

            if (APP.clusterLayer) {
                APP.map.removeLayer(APP.clusterLayer);
                APP.clusterLayer = null;
                APP.clusterMarkers = [];
            }

            if (APP.circleLayer) {
                APP.map.removeLayer(APP.circleLayer);
                APP.circleLayer = null;
            }
        }
    };

    // ============================================================
    // ============================================================
    // 4. CARGA DE ARCHIVOS
    // ============================================================
    // ============================================================

    const CargaArchivos = {
        init: function() {
            document.getElementById('excelFile').addEventListener('change', function(e) {
                const file = e.target.files[0];
                if (file) CargaArchivos.procesar(file);
            });

            const uploadArea = document.getElementById('uploadArea');
            uploadArea.addEventListener('dragover', (e) => {
                e.preventDefault();
                uploadArea.style.borderColor = '#764ba2';
                uploadArea.style.background = '#e8eaf6';
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
                if (file) CargaArchivos.procesar(file);
            });
        },

        procesar: function(file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                try {
                    const data = new Uint8Array(e.target.result);
                    const workbook = XLSX.read(data, { type: 'array' });
                    const firstSheet = workbook.Sheets[workbook.SheetNames[0]];
                    const rows = XLSX.utils.sheet_to_json(firstSheet);

                    const coordinates = [];
                    rows.forEach((row, index) => {
                        let lat = row.lat || row.latitude || row.Lat || row.Latitude;
                        let lng = row.lng || row.longitude || row.Lng || row.Longitude;
                        let title = row.title || row.Titulo || row.nombre || `Punto ${index + 1}`;
                        let description = row.description || row.Descripcion || '';
                        let peso = row.peso || row.Peso || row.weight || 1;

                        if (lat && lng) {
                            lat = parseFloat(lat);
                            lng = parseFloat(lng);
                            peso = parseFloat(peso) || 1;
                            if (!isNaN(lat) && !isNaN(lng)) {
                                coordinates.push({ lat, lng, title, description, peso });
                            }
                        }
                    });

                    if (coordinates.length > 0) {
                        APP.currentCoordinates = coordinates;
                        APP.heatData = coordinates.map(c => ({
                            lat: c.lat,
                            lng: c.lng,
                            peso: c.peso || 1
                        }));

                        MapaBase.limpiarTodo();
                        Marcadores.agregar(coordinates);

                        if (document.getElementById('toggleHeatmap').checked) {
                            Heatmap.crear(APP.heatData);
                        }
                        if (document.getElementById('toggleClusters').checked) {
                            Clusters.crear(coordinates);
                        }
                        if (document.getElementById('toggleGrid').checked) {
                            Grid.crear(coordinates);
                        }

                        MapaBase.ajustarVista(coordinates);
                        Tabla.actualizar(coordinates);
                        Estadisticas.actualizar(coordinates);
                    } else {
                        alert('No se encontraron coordenadas válidas en el archivo');
                    }
                } catch (error) {
                    console.error('Error al leer el archivo:', error);
                    alert('Error al procesar el archivo: ' + error.message);
                }
            };
            reader.readAsArrayBuffer(file);
        },

        descargarEjemplo: function() {
            const exampleData = [
                { latitude: 19.4326, longitude: -99.1332, title: "Zócalo CDMX", description: "Centro histórico", peso: 2 },
                { latitude: 19.4270, longitude: -99.1676, title: "Monumento a la Revolución", description: "Monumento emblemático", peso: 1 },
                { latitude: 19.4102, longitude: -99.1295, title: "Palacio de Bellas Artes", description: "Teatro y museo", peso: 3 }
            ];

            const ws = XLSX.utils.json_to_sheet(exampleData);
            const wb = XLSX.utils.book_new();
            XLSX.utils.book_append_sheet(wb, ws, "Coordenadas");
            XLSX.writeFile(wb, "ejemplo_coordenadas.xlsx");
        }
    };

    // ============================================================
    // ============================================================
    // 5. MARCADORES
    // ============================================================
    // ============================================================

    const Marcadores = {
        agregar: function(coordinates) {
            APP.mapMarkers = [];
            coordinates.forEach(coord => {
                const marker = L.marker([coord.lat, coord.lng], {
                    title: coord.title
                }).bindPopup(`
                    <div>
                        <h6><strong>${coord.title}</strong></h6>
                        <p><strong>Lat:</strong> ${coord.lat.toFixed(6)}</p>
                        <p><strong>Lng:</strong> ${coord.lng.toFixed(6)}</p>
                        ${coord.description ? `<p>${coord.description}</p>` : ''}
                        <small class="text-muted">${new Date().toLocaleString()}</small>
                    </div>
                `);

                marker.addTo(APP.map);
                APP.mapMarkers.push(marker);
            });
        },

        limpiar: function() {
            APP.mapMarkers.forEach(m => APP.map.removeLayer(m));
            APP.mapMarkers = [];
        }
    };

    // ============================================================
    // ============================================================
    // 6. MAPA DE CALOR
    // ============================================================
    // ============================================================

    const Heatmap = {
        crear: function(data) {
            if (APP.heatLayer) {
                APP.map.removeLayer(APP.heatLayer);
                APP.heatLayer = null;
                APP.heatCircles = [];
            }

            if (!data || data.length === 0) return;

            const radius = 1000;
            const intensity = parseInt(document.getElementById('heatIntensity').value) || 5;

            const maxPeso = Math.max(...data.map(d => d.peso || 1));
            const heatGroup = L.layerGroup();
            const circles = [];

            data.forEach(point => {
                const lat = point.lat;
                const lng = point.lng;
                const peso = point.peso || 1;

                const normalizedIntensity = Math.min(peso / maxPeso, 1);
                const baseRadius = radius * (9 + normalizedIntensity * 9) * (intensity / 5);

                const layers = 5;
                for (let i = layers; i >= 0; i--) {
                    const ratio = i / layers;
                    const currentRadius = baseRadius * (0.2 + ratio * 0.8);
                    const opacity = (1 - ratio) * 0.6 * (0.3 + normalizedIntensity * 0.7);

                    if (opacity < 0.01) continue;

                    const color = Utils.getHeatColorHex(peso, maxPeso);

                    const circle = L.circle([lat, lng], {
                        radius: currentRadius,
                        color: color,
                        fillColor: color,
                        fillOpacity: opacity,
                        weight: 0,
                        className: 'heat-circle',
                        interactive: false
                    });

                    circles.push(circle);
                    heatGroup.addLayer(circle);
                }
            });

            APP.heatLayer = heatGroup.addTo(APP.map);
            APP.heatCircles = circles;

            console.log(`🔥 Mapa de calor: ${data.length} puntos, ${circles.length} círculos`);
        },

        toggle: function() {
            if (document.getElementById('toggleHeatmap').checked) {
                if (APP.heatData.length > 0) {
                    this.crear(APP.heatData);
                }
            } else {
                if (APP.heatLayer) {
                    APP.map.removeLayer(APP.heatLayer);
                    APP.heatLayer = null;
                    APP.heatCircles = [];
                }
            }
        },

        updateRadius: function() {
            const radius = document.getElementById('heatRadius').value;
            document.getElementById('radiusValue').textContent = radius;
            if (document.getElementById('toggleHeatmap').checked && APP.heatData.length > 0) {
                this.crear(APP.heatData);
            }
        },

        updateIntensity: function() {
            const intensity = document.getElementById('heatIntensity').value;
            document.getElementById('intensityValue').textContent = intensity;
            if (document.getElementById('toggleHeatmap').checked && APP.heatData.length > 0) {
                this.crear(APP.heatData);
            }
        }
    };

    // ============================================================
    // ============================================================
    // 7. MALLA DE DENSIDAD
    // ============================================================
    // ============================================================

    const Grid = {
        crear: function(coordinates) {
            if (APP.gridLayer) {
                APP.map.removeLayer(APP.gridLayer);
                APP.gridLayer = null;
            }

            if (!coordinates || coordinates.length === 0) return;

            const lats = coordinates.map(c => c.lat);
            const lngs = coordinates.map(c => c.lng);
            const minLat = Math.min(...lats);
            const maxLat = Math.max(...lats);
            const minLng = Math.min(...lngs);
            const maxLng = Math.max(...lngs);

            const cellSize = 0.1;
            const gridData = [];

            for (let lat = minLat; lat <= maxLat; lat += cellSize) {
                for (let lng = minLng; lng <= maxLng; lng += cellSize) {
                    const count = coordinates.filter(c =>
                        c.lat >= lat && c.lat < lat + cellSize &&
                        c.lng >= lng && c.lng < lng + cellSize
                    ).length;

                    if (count > 0) {
                        const color = Utils.getColorByDensity(count);
                        const opacity = Math.min(count / 10, 0.8);

                        const rect = L.rectangle([
                            [lat, lng],
                            [lat + cellSize, lng + cellSize]
                        ], {
                            color: color,
                            weight: 1,
                            opacity: 0.6,
                            fillColor: color,
                            fillOpacity: opacity,
                            interactive: true
                        }).bindPopup(`
                            <div>
                                <h6>Celda de densidad</h6>
                                <p><strong>Puntos:</strong> ${count}</p>
                            </div>
                        `);

                        gridData.push(rect);
                    }
                }
            }

            APP.gridLayer = L.layerGroup(gridData).addTo(APP.map);
            console.log(`📊 Malla de densidad: ${gridData.length} celdas`);
        },

        toggle: function() {
            if (document.getElementById('toggleGrid').checked) {
                if (APP.currentCoordinates.length > 0) {
                    this.crear(APP.currentCoordinates);
                }
            } else {
                if (APP.gridLayer) {
                    APP.map.removeLayer(APP.gridLayer);
                    APP.gridLayer = null;
                }
            }
        }
    };

    // ============================================================
    // ============================================================
    // 8. CLUSTERS
    // ============================================================
    // ============================================================

    const Clusters = {
        crear: function(coordinates) {
            if (APP.clusterLayer) {
                APP.map.removeLayer(APP.clusterLayer);
                APP.clusterLayer = null;
                APP.clusterMarkers = [];
            }

            if (!coordinates || coordinates.length === 0) return;

            const clusterSize = 0.5;
            const clusters = {};

            coordinates.forEach(coord => {
                const latKey = Math.round(coord.lat / clusterSize);
                const lngKey = Math.round(coord.lng / clusterSize);
                const key = `${latKey},${lngKey}`;

                if (!clusters[key]) {
                    clusters[key] = {
                        points: [],
                        lat: 0,
                        lng: 0
                    };
                }
                clusters[key].points.push(coord);
                clusters[key].lat += coord.lat;
                clusters[key].lng += coord.lng;
            });

            const clusterGroup = L.layerGroup();
            let clusterCount = 0;

            Object.keys(clusters).forEach(key => {
                const cluster = clusters[key];
                const count = cluster.points.length;
                const avgLat = cluster.lat / count;
                const avgLng = cluster.lng / count;

                const color = Utils.getClusterColor(count);
                const size = count >= 20 ? 40 : (count >= 10 ? 32 : (count >= 5 ? 24 : 18));

                const clusterDiv = document.createElement('div');
                clusterDiv.className = 'cluster-marker';
                clusterDiv.style.backgroundColor = color;
                clusterDiv.style.width = size + 'px';
                clusterDiv.style.height = size + 'px';
                clusterDiv.style.fontSize = (size >= 32) ? '14px' : '11px';
                clusterDiv.textContent = count;

                const icon = L.divIcon({
                    html: clusterDiv.outerHTML,
                    className: 'cluster-icon',
                    iconSize: [size, size],
                    iconAnchor: [size / 2, size / 2]
                });

                const marker = L.marker([avgLat, avgLng], {
                    icon: icon,
                    title: `Cluster de ${count} puntos`
                }).bindPopup(`
                    <div>
                        <h6>📦 Cluster</h6>
                        <p><strong>Puntos:</strong> ${count}</p>
                        <p><strong>Centro:</strong> ${avgLat.toFixed(5)}, ${avgLng.toFixed(5)}</p>
                        <hr>
                        <div style="max-height: 150px; overflow-y: auto; font-size: 11px;">
                            ${cluster.points.slice(0, 10).map(p => 
                                `<div>• ${p.title} (${p.lat.toFixed(5)}, ${p.lng.toFixed(5)})</div>`
                            ).join('')}
                            ${count > 10 ? `<div><em>... y ${count - 10} más</em></div>` : ''}
                        </div>
                    </div>
                `);

                marker.addTo(clusterGroup);
                APP.clusterMarkers.push(marker);

                if (cluster.points.length > 2) {
                    const polygonCoords = cluster.points.map(p => [p.lat, p.lng]);
                    const polygon = L.polygon(polygonCoords, {
                        color: color,
                        weight: 2,
                        fillColor: color,
                        fillOpacity: 0.2
                    }).bindPopup(`
                        <div>
                            <h6>🔷 Polígono del cluster</h6>
                            <p><strong>Puntos:</strong> ${count}</p>
                            <p>Vertices: ${cluster.points.length}</p>
                        </div>
                    `);
                    polygon.addTo(clusterGroup);
                }

                clusterCount++;
            });

            APP.clusterLayer = clusterGroup.addTo(APP.map);
            document.getElementById('clusterCount').textContent = clusterCount;
            console.log(`📦 Clusters: ${clusterCount} grupos, ${coordinates.length} puntos`);
        },

        toggle: function() {
            if (document.getElementById('toggleClusters').checked) {
                if (APP.currentCoordinates.length > 0) {
                    this.crear(APP.currentCoordinates);
                }
            } else {
                if (APP.clusterLayer) {
                    APP.map.removeLayer(APP.clusterLayer);
                    APP.clusterLayer = null;
                    APP.clusterMarkers = [];
                    document.getElementById('clusterCount').textContent = '0';
                }
            }
        }
    };

    // ============================================================
    // ============================================================
    // 9. PUNTOS CERCANOS
    // ============================================================
    // ============================================================

    const PuntosCercanos = {
        buscar: function(lat, lng) {
            if (APP.currentCoordinates.length === 0) {
                document.getElementById('nearestPoints').innerHTML = `
                    <div class="text-muted text-center py-2">
                        <i class="fas fa-info-circle"></i> No hay puntos cargados
                    </div>
                `;
                return;
            }

            const pointsWithDistance = APP.currentCoordinates.map(coord => {
                const distancia = Utils.calcularDistancia(lat, lng, coord.lat, coord.lng);
                return { ...coord, distancia };
            });

            const sorted = pointsWithDistance.sort((a, b) => a.distancia - b.distancia);
            const nearest = sorted.slice(0, 3);

            this._mostrarResultados(lat, lng, nearest);
            this._dibujarEnMapa(lat, lng, nearest);
        },

        _mostrarResultados: function(lat, lng, nearest) {
            let html = '<div class="small">';
            html += `<div class="mb-2 text-muted">
                <i class="fas fa-map-pin"></i> Punto de referencia: ${lat.toFixed(6)}, ${lng.toFixed(6)}
            </div>`;

            nearest.forEach((point, index) => {
                const icon = index === 0 ? '🥇' : (index === 1 ? '🥈' : '🥉');
                html += `
                    <div class="nearest-point-item d-flex justify-content-between align-items-center">
                        <div>
                            <span class="badge bg-primary me-2">${index + 1}</span>
                            <strong>${point.title}</strong>
                            <br>
                            <small class="text-muted">
                                ${point.lat.toFixed(6)}, ${point.lng.toFixed(6)}
                                ${point.description ? `<br>${point.description}` : ''}
                            </small>
                        </div>
                        <div class="text-end">
                            <span class="distancia">${point.distancia.toFixed(2)} km</span>
                            <br>
                            <button class="btn btn-sm btn-outline-primary" onclick="PuntosCercanos.centrar(${point.lat}, ${point.lng})">
                                <i class="fas fa-crosshairs"></i>
                            </button>
                        </div>
                    </div>
                `;
            });

            html += '</div>';
            document.getElementById('nearestPoints').innerHTML = html;
        },

        _dibujarEnMapa: function(lat, lng, nearest) {
            if (APP.circleLayer) {
                APP.map.removeLayer(APP.circleLayer);
                APP.circleLayer = null;
            }

            const circles = [];

            const refCircle = L.circle([lat, lng], {
                radius: 100,
                color: '#dc3545',
                fillColor: '#dc3545',
                fillOpacity: 0.2,
                weight: 2
            }).bindPopup(`
                <div>
                    <h6>📍 Punto de referencia</h6>
                    <p><strong>Lat:</strong> ${lat.toFixed(6)}</p>
                    <p><strong>Lng:</strong> ${lng.toFixed(6)}</p>
                </div>
            `);
            circles.push(refCircle);

            const colors = ['#28a745', '#ffc107', '#17a2b8'];
            nearest.forEach((point, index) => {
                const circle = L.circle([point.lat, point.lng], {
                    radius: 50,
                    color: colors[index],
                    fillColor: colors[index],
                    fillOpacity: 0.3,
                    weight: 2
                }).bindPopup(`
                    <div>
                        <h6>${index + 1}. ${point.title}</h6>
                        <p><strong>Distancia:</strong> ${point.distancia.toFixed(2)} km</p>
                        <p><strong>Lat:</strong> ${point.lat.toFixed(6)}</p>
                        <p><strong>Lng:</strong> ${point.lng.toFixed(6)}</p>
                    </div>
                `);
                circles.push(circle);

                const line = L.polyline([
                    [lat, lng],
                    [point.lat, point.lng]
                ], {
                    color: colors[index],
                    weight: 1,
                    opacity: 0.5,
                    dashArray: '5, 5'
                });
                circles.push(line);
            });

            APP.circleLayer = L.layerGroup(circles).addTo(APP.map);
        },

        centrar: function(lat, lng) {
            MapaBase.centrar(lat, lng, 15);
        }
    };

    // ============================================================
    // ============================================================
    // 10. TABLA Y ESTADÍSTICAS
    // ============================================================
    // ============================================================

    const Tabla = {
        actualizar: function(coordinates) {
            const tbody = document.getElementById('coordinatesTableBody');
            tbody.innerHTML = '';

            coordinates.forEach((coord, index) => {
                const row = tbody.insertRow();
                row.innerHTML = `
                    <td>${index + 1}</td>
                    <td>${Utils.escapeHtml(coord.title)}</td>
                    <td>${coord.lat.toFixed(6)}</td>
                    <td>${coord.lng.toFixed(6)}</td>
                    <td>
                        <button class="btn btn-sm btn-primary" onclick="PuntosCercanos.centrar(${coord.lat}, ${coord.lng})">
                            <i class="fas fa-crosshairs"></i>
                        </button>
                    </td>
                `;
            });

            if (coordinates.length === 0) {
                tbody.innerHTML = '<tr><td colspan="5" class="text-center">No hay coordenadas cargadas</td></tr>';
            }
        }
    };

    const Estadisticas = {
        actualizar: function(coordinates) {
            document.getElementById('markerCount').textContent = coordinates.length;

            if (coordinates.length > 0) {
                const lats = coordinates.map(c => c.lat);
                const lngs = coordinates.map(c => c.lng);
                const minLat = Math.min(...lats);
                const maxLat = Math.max(...lats);
                const minLng = Math.min(...lngs);
                const maxLng = Math.max(...lngs);
                const area = (maxLat - minLat) * (maxLng - minLng);
                const density = area > 0 ? (coordinates.length / area) : 0;
                document.getElementById('maxDensity').textContent = density.toFixed(2);
            }
        }
    };

    // ============================================================
    // ============================================================
    // 11. CONTROLADOR PRINCIPAL
    // ============================================================
    // ============================================================

    const Controlador = {
        init: function() {
            console.log('🚀 Inicializando aplicación...');
            MapaBase.init();
            CargaArchivos.init();
            this._configurarEventos();
            console.log('✅ Aplicación inicializada correctamente');
            console.log('📦 Sin dependencias externas (Leaflet + XLSX)');
        },

        _configurarEventos: function() {
            document.getElementById('toggleHeatmap').addEventListener('change', function() {
                Heatmap.toggle();
            });

            document.getElementById('toggleGrid').addEventListener('change', function() {
                Grid.toggle();
            });

            document.getElementById('toggleClusters').addEventListener('change', function() {
                Clusters.toggle();
            });

            document.getElementById('heatRadius').addEventListener('input', function() {
                Heatmap.updateRadius();
            });

            document.getElementById('heatIntensity').addEventListener('input', function() {
                Heatmap.updateIntensity();
            });
        },

        limpiarTodo: function() {
            if (confirm('¿Estás seguro de que quieres eliminar todos los marcadores?')) {
                MapaBase.limpiarTodo();
                APP.currentCoordinates = [];
                APP.heatData = [];
                Tabla.actualizar([]);
                Estadisticas.actualizar([]);
                document.getElementById('nearestPoints').innerHTML = `
                    <div class="text-muted text-center py-2">
                        <i class="fas fa-mouse-pointer"></i> Haz clic en el mapa
                    </div>
                `;
                document.getElementById('clusterCount').textContent = '0';
            }
        }
    };

    // ============================================================
    // ============================================================
    // 12. EXPOSICIÓN DE FUNCIONES GLOBALES
    // ============================================================
    // ============================================================

    window.Controlador = Controlador;
    window.Heatmap = Heatmap;
    window.Grid = Grid;
    window.Clusters = Clusters;
    window.PuntosCercanos = PuntosCercanos;
    window.CargaArchivos = CargaArchivos;

    window.centrarEnPunto = function(lat, lng) {
        PuntosCercanos.centrar(lat, lng);
    };

    window.clearAllMarkers = function() {
        Controlador.limpiarTodo();
    };

    window.downloadExample = function() {
        CargaArchivos.descargarEjemplo();
    };

    window.toggleHeatmap = function() {
        Heatmap.toggle();
    };

    window.toggleGrid = function() {
        Grid.toggle();
    };

    window.toggleClusters = function() {
        Clusters.toggle();
    };

    window.updateHeatmapRadius = function() {
        Heatmap.updateRadius();
    };

    window.updateHeatmap = function() {
        if (document.getElementById('toggleHeatmap').checked && APP.heatData.length > 0) {
            Heatmap.crear(APP.heatData);
        }
    };

    // ============================================================
    // ============================================================
    // 13. CAPAS WMS DE LA NASA
    // ============================================================
    // ============================================================

    const WmsLayers = {
        init: function() {
            this._crearUI();
            arrayGeoserverOverlay.forEach((layer, index) => {
                const wmsLayer = this._crearWMSLayer(layer);
                APP.wmsLayers[layer.key] = {
                    config: layer,
                    layer: wmsLayer,
                    active: false
                };
            });
            console.log(`🛰️ ${Object.keys(APP.wmsLayers).length} capas WMS inicializadas`);
        },

        _crearUI: function() {
            const container = document.getElementById('wmsLayersContainer');
            if (!container) return;

            let html = '<div class="small">';
            html += `<div class="mb-2 text-muted">
                <i class="fas fa-info-circle"></i> Activa/desactiva capas satelitales
            </div>`;

            arrayGeoserverOverlay.forEach((layer, index) => {
                const checked = layer.checked ? 'checked' : '';
                const legendImg = layer.legends ?
                    `<img src="${layer.legends}" alt="Leyenda" class="layer-legend" onerror="this.style.display='none'">` : '';

                html += `
                    <div class="wms-layer-item" id="wms-item-${index}" onclick="WmsLayers.toggleLayer('${layer.key}')">
                        <div class="d-flex align-items-center">
                            <input class="form-check-input" type="checkbox" id="wms-${layer.key}" ${checked} 
                                   onclick="event.stopPropagation(); WmsLayers.toggleLayer('${layer.key}')">
                            <span class="layer-name">${layer.name}</span>
                            ${legendImg}
                        </div>
                    </div>
                `;
            });

            html += '</div>';
            container.innerHTML = html;
        },

        _crearWMSLayer: function(config) {
            const params = {
                layers: config.layers,
                format: config.format || 'image/png',
                transparent: config.transparent !== undefined ? config.transparent : true,
                version: '1.3.0',
                crs: L.CRS.EPSG4326,
                styles: 'default'
            };

            if (config.opacity !== undefined) {
                params.opacity = config.opacity;
            }

            const wmsLayer = L.tileLayer.wms(config.url, params);

            if (config.opacity !== undefined) {
                wmsLayer.setOpacity(config.opacity);
            }

            if (config.minZoom !== undefined && config.maxZoom !== undefined) {
                wmsLayer.setZIndex(1000);
            }

            if (config.checked) {
                wmsLayer.addTo(APP.map);
                APP.activeWmsLayers.push(config.key);
            }

            return wmsLayer;
        },

        toggleLayer: function(key) {
            const wmsData = APP.wmsLayers[key];
            if (!wmsData) return;

            const active = !wmsData.active;
            wmsData.active = active;

            const checkbox = document.getElementById(`wms-${key}`);
            if (checkbox) {
                checkbox.checked = active;
            }

            const index = arrayGeoserverOverlay.findIndex(l => l.key === key);
            const item = document.getElementById(`wms-item-${index}`);
            if (item) {
                if (active) {
                    item.classList.add('active');
                } else {
                    item.classList.remove('active');
                }
            }

            if (active) {
                wmsData.layer.addTo(APP.map);
                APP.activeWmsLayers.push(key);
                console.log(`🛰️ Capa activada: ${wmsData.config.name}`);

                if (wmsData.config.legends) {
                    this._mostrarLeyenda(wmsData.config.legends);
                }
            } else {
                APP.map.removeLayer(wmsData.layer);
                APP.activeWmsLayers = APP.activeWmsLayers.filter(k => k !== key);
                console.log(`🛰️ Capa desactivada: ${wmsData.config.name}`);
            }
        },

        _mostrarLeyenda: function(legendUrl) {
            const existingLegend = document.querySelector('.wms-legend-control');
            if (existingLegend) {
                existingLegend.remove();
            }

            const legendControl = L.control({ position: 'bottomright' });
            legendControl.onAdd = function() {
                const div = L.DomUtil.create('div', 'wms-legend-control');
                div.style.backgroundColor = 'white';
                div.style.padding = '8px';
                div.style.borderRadius = '5px';
                div.style.boxShadow = '0 2px 5px rgba(0,0,0,0.2)';
                div.style.maxWidth = '150px';
                div.style.maxHeight = '100px';
                div.style.overflow = 'hidden';
                div.style.cursor = 'pointer';
                div.title = 'Haz clic para ocultar leyenda';

                div.innerHTML = `
                    <div style="font-size: 10px; font-weight: bold; margin-bottom: 3px;">Leyenda</div>
                    <img src="${legendUrl}" alt="Leyenda" style="max-width: 100%; max-height: 80px;">
                `;

                div.onclick = function() {
                    this.remove();
                };

                return div;
            };

            legendControl.addTo(APP.map);

            setTimeout(() => {
                const legend = document.querySelector('.wms-legend-control');
                if (legend) {
                    legend.style.opacity = '0.5';
                    legend.style.transition = 'opacity 1s';
                    setTimeout(() => {
                        if (legend && legend.parentNode) {
                            legend.parentNode.removeChild(legend);
                        }
                    }, 1000);
                }
            }, 10000);
        },

        getActiveLayers: function() {
            return APP.activeWmsLayers.map(key => APP.wmsLayers[key]);
        }
    };

    // ============================================================
    // ============================================================
    // 14. CONFIGURACIÓN WMS
    // ============================================================
    // ============================================================

    const ResourceNasa = 'https://gibs.earthdata.nasa.gov/wms/epsg4326/best/wms.cgi?';
    const Resourcelegends = 'https://gibs.earthdata.nasa.gov/wms/epsg4326/best/wms.cgi?request=GetLegendGraphic&format=image/png&width=100&height=20&layer=';
    const Transparent = true;
    const Opacity = 0.7;
    const Format = 'image/png';
    const Checked = false;
    const ZoomMaximo = 18;
    const ZoomMinimo = 3;

    const arrayGeoserverOverlay = [
        { name: '🌡️ TEMPERATURA VIIRS NOAA20', key: 'TEMPERATURA NOCHE', layers: 'VIIRS_NOAA20_Brightness_Temp_BandI5_Night', url: ResourceNasa, transparent: Transparent, opacity: Opacity, format: Format, checked: Checked, maxZoom: ZoomMaximo, minZoom: ZoomMinimo, legends: `${Resourcelegends}VIIRS_Brightness_Temp_BandI5_H.png` },
        { name: '🌡️ TEMPERATURA VIIRS SNPP', key: 'TEMPERATURA NOCHE SNPP', layers: 'VIIRS_SNPP_Brightness_Temp_BandI5_Night', url: ResourceNasa, transparent: Transparent, opacity: Opacity, format: Format, checked: Checked, maxZoom: ZoomMaximo, minZoom: ZoomMinimo, legends: `${Resourcelegends}VIIRS_Brightness_Temp_BandI5_H.png` },
        { name: '🌡️ TEMP. SUPERFICIE TERRESTRE NOCHE', key: 'TEMPERATURA DE LA SUPERFICIE DEL TERRESTRE NOCHE', layers: 'MODIS_Terra_L3_Land_Surface_Temp_Monthly_Night', url: ResourceNasa, transparent: Transparent, opacity: Opacity, format: Format, checked: Checked, maxZoom: ZoomMaximo, minZoom: ZoomMinimo, legends: `${Resourcelegends}MODIS_Land_Surface_Temp_H.png` },
        { name: '🌡️ TEMP. SUPERFICIE TERRESTRE DÍA', key: 'TEMPERATURA DE LA SUPERFICIE DEL TERRESTRE DIA', layers: 'MODIS_Terra_L3_Land_Surface_Temp_Monthly_Day', url: ResourceNasa, transparent: Transparent, opacity: Opacity, format: Format, checked: Checked, maxZoom: ZoomMaximo, minZoom: ZoomMinimo, legends: `${Resourcelegends}MODIS_Land_Surface_Temp_H.png` },
        { name: '☁️ CIRRUS SNPP', key: 'CIRRUS SNPP', layers: 'VIIRS_SNPP_Apparent_Reflectance_VNP02MOD_M09', url: ResourceNasa, transparent: Transparent, opacity: Opacity, format: Format, checked: Checked, maxZoom: ZoomMaximo, minZoom: ZoomMinimo, legends: `${Resourcelegends}VIIRS_Apparent_Reflectance_H.png` },
        { name: '☀️ CIELO LIMPIO VIIRS SNPP', key: 'CILEO LIMPIO VIIRS SNPP', layers: 'VIIRS_SNPP_Clear_Sky_Confidence_Night', url: ResourceNasa, transparent: Transparent, opacity: Opacity, format: Format, checked: Checked, maxZoom: ZoomMaximo, minZoom: ZoomMinimo, legends: `${Resourcelegends}VIIRS_Clear_Sky_Confidence_H.png` },
        { name: '☀️ CIELO LIMPIO VIIRS NOAA20', key: 'CILEO LIMPIO VIIRS NOAA20', layers: 'VIIRS_NOAA20_Clear_Sky_Confidence_Night', url: ResourceNasa, transparent: Transparent, opacity: Opacity, format: Format, checked: Checked, maxZoom: ZoomMaximo, minZoom: ZoomMinimo, legends: `${Resourcelegends}VIIRS_Clear_Sky_Confidence_H.png` },
        { name: '☁️ NUBE RADIO EFECTIVO VIIRS SNPP', key: 'NUBE RADIO EFECTIVO VIIRS SNNP', layers: 'VIIRS_SNPP_Cloud_Effective_Radius', url: ResourceNasa, transparent: Transparent, opacity: Opacity, format: Format, checked: Checked, maxZoom: ZoomMaximo, minZoom: ZoomMinimo, legends: `${Resourcelegends}MODIS_VIIRS_Cloud_Effective_Radius_H.png` },
        { name: '☁️ NUBE RADIO EFECTIVO VIIRS NOAA20', key: 'NUBE RADIO EFECTIVO VIIRS NOAA20', layers: 'VIIRS_NOAA20_Cloud_Effective_Radius', url: ResourceNasa, transparent: Transparent, opacity: Opacity, format: Format, checked: Checked, maxZoom: ZoomMaximo, minZoom: ZoomMinimo, legends: `${Resourcelegends}MODIS_VIIRS_Cloud_Effective_Radius_H.png` },
        { name: '☁️ ESPESOR ÓPTICO NUBE VIIRS SNPP', key: 'ESPESOR ÓPTICO DE LA NUBE VIIRS SNPP', layers: 'VIIRS_SNPP_Cloud_Optical_Thickness', url: ResourceNasa, transparent: Transparent, opacity: Opacity, format: Format, checked: Checked, maxZoom: ZoomMaximo, minZoom: ZoomMinimo, legends: `${Resourcelegends}MODIS_VIIRS_Cloud_Optical_Thickness_H.png` },
        { name: '☁️ ESPESOR ÓPTICO NUBE VIIRS NOAA20', key: 'ESPESOR ÓPTICO DE LA NUBE VIIRS NOAA20', layers: 'VIIRS_NOAA20_Cloud_Optical_Thickness', url: ResourceNasa, transparent: Transparent, opacity: Opacity, format: Format, checked: Checked, maxZoom: ZoomMaximo, minZoom: ZoomMinimo, legends: `${Resourcelegends}MODIS_VIIRS_Cloud_Optical_Thickness_H.png` },
        { name: '💨 POLVO MENSUAL', key: 'POLVO MENSUAL', layers: 'MERRA2_Total_Dust_Deposition_Dry_Wet_Monthly', url: ResourceNasa, transparent: Transparent, opacity: Opacity, format: Format, checked: Checked, maxZoom: ZoomMaximo, minZoom: ZoomMinimo, legends: `${Resourcelegends}MERRA2_Total_Dust_Deposition_Dry_Wet_Monthly_H.png` },
        { name: '💧 EVAPORACIÓN', key: 'EVAPORACION', layers: 'MERRA2_Evaporation_Land_Monthly', url: ResourceNasa, transparent: Transparent, opacity: Opacity, format: Format, checked: Checked, maxZoom: ZoomMaximo, minZoom: ZoomMinimo, legends: `${Resourcelegends}MERRA2_Evaporation_Land_Monthly_H.png` },
        { name: '🛰️ GEOSTACIONARIA ESTE IR', key: 'GEOSTACIONARIA ESTE LIMPIO INFRARROJO', layers: 'GOES-East_ABI_Band13_Clean_Infrared', url: ResourceNasa, transparent: Transparent, opacity: Opacity, format: Format, checked: Checked, maxZoom: ZoomMaximo, minZoom: ZoomMinimo, legends: `${Resourcelegends}Clean_Longwave_Infrared_Window_Band_H.png` },
        { name: '🛰️ GEOSTACIONARIA ESTE COLOR', key: 'GEOSTACIONARIA ESTE COLOR', layers: 'GOES-East_ABI_GeoColor', url: ResourceNasa, transparent: Transparent, opacity: 1, format: Format, checked: Checked, maxZoom: ZoomMaximo, minZoom: ZoomMinimo, legends: '' },
        { name: '🛰️ GEOSTACIONARIA OESTE IR', key: 'GEOSTACIONARIA OESTE LIMPIO INFRARROJO', layers: 'GOES-West_ABI_Band13_Clean_Infrared', url: ResourceNasa, transparent: Transparent, opacity: Opacity, format: Format, checked: Checked, maxZoom: ZoomMaximo, minZoom: ZoomMinimo, legends: `${Resourcelegends}Clean_Longwave_Infrared_Window_Band_H.png` },
        { name: '🛰️ GEOSTACIONARIA OESTE COLOR', key: 'GEOSTACIONARIA OESTE COLOR', layers: 'GOES-West_ABI_GeoColor', url: ResourceNasa, transparent: Transparent, opacity: Opacity, format: Format, checked: Checked, maxZoom: ZoomMaximo, minZoom: ZoomMinimo, legends: '' },
        { name: '🏙️ SUPERFICIE IMPERMEABLE', key: 'SUPERFICIE IMPERMEABLE', layers: 'Landsat_Global_Man-made_Impervious_Surface', url: ResourceNasa, transparent: Transparent, opacity: Opacity, format: Format, checked: Checked, maxZoom: ZoomMaximo, minZoom: ZoomMinimo, legends: `${Resourcelegends}Landsat_Global_Man-made_Impervious_Surface_H.png` },
        { name: '🌧️ PRECIPITACIÓN', key: 'PRESIPITACIÓN', layers: 'IMERG_Precipitation_Rate', url: ResourceNasa, transparent: Transparent, opacity: Opacity, format: Format, checked: Checked, maxZoom: ZoomMaximo, minZoom: ZoomMinimo, legends: `${Resourcelegends}GPM_Precipitation_Rate_H.png` },
        { name: '💧 HUMEDAD RELATIVA DÍA', key: 'HUMEDAD RELATIVA DIA', layers: 'AIRS_L3_Surface_Relative_Humidity_Monthly_Day', url: ResourceNasa, transparent: Transparent, opacity: Opacity, format: Format, checked: Checked, maxZoom: ZoomMaximo, minZoom: ZoomMinimo, legends: `${Resourcelegends}AIRS_Surface_Relative_Humidity_Monthly_Day_H.png` },
        { name: '💧 HUMEDAD RELATIVA NOCHE', key: 'HUMEDAD RELATIVA NOCHE', layers: 'AIRS_L3_Surface_Relative_Humidity_Monthly_Night', url: ResourceNasa, transparent: Transparent, opacity: Opacity, format: Format, checked: Checked, maxZoom: ZoomMaximo, minZoom: ZoomMinimo, legends: `${Resourcelegends}AIRS_Surface_Relative_Humidity_Monthly_Night_H.png` },
        { name: '💨 VIENTO', key: 'VIENTO', layers: 'MERRA2_Surface_Wind_Speed_Monthly', url: ResourceNasa, transparent: Transparent, opacity: Opacity, format: Format, checked: Checked, maxZoom: ZoomMaximo, minZoom: ZoomMinimo, legends: `${Resourcelegends}MERRA2_Surface_Wind_Speed_Monthly_H.png` }
    ];

    // ============================================================
// ============================================================
// 15. FUNCIONES DE PANTALLA COMPLETA - CON API NATIVA
// ============================================================
// ============================================================

let isFullscreen = false;

function toggleFullscreen() {
    const mapCard = document.getElementById('mapCard');
    
    if (document.fullscreenElement) {
        salirFullscreen();
    } else {
        entrarFullscreen(mapCard);
    }
}

function entrarFullscreen(element) {
    try {
        // Solicitar fullscreen usando la API nativa
        if (element.requestFullscreen) {
            element.requestFullscreen();
        } else if (element.webkitRequestFullscreen) {
            element.webkitRequestFullscreen(); // Safari
        } else if (element.msRequestFullscreen) {
            element.msRequestFullscreen(); // IE/Edge antiguo
        }
        
        // Aplicar clases y estilos adicionales
        element.classList.add('fullscreen-mode');
        document.body.classList.add('fullscreen-active');
        
        // Crear controles flotantes
        crearControlesFullscreen();
        
        // Forzar redimensionamiento del mapa después de la transición
        setTimeout(() => {
            if (APP.map) {
                APP.map.invalidateSize();
            }
        }, 300);
        
        isFullscreen = true;
        console.log('🖥️ Mapa en pantalla completa (API nativa)');
        
    } catch (error) {
        console.error('Error al entrar en pantalla completa:', error);
        // Fallback a la versión CSS
        entrarFullscreenCSS();
    }
}

function salirFullscreen() {
    try {
        // Salir de fullscreen usando la API nativa
        if (document.exitFullscreen) {
            document.exitFullscreen();
        } else if (document.webkitExitFullscreen) {
            document.webkitExitFullscreen();
        } else if (document.msExitFullscreen) {
            document.msExitFullscreen();
        }
        
        // Remover clases
        const mapCard = document.getElementById('mapCard');
        mapCard.classList.remove('fullscreen-mode');
        document.body.classList.remove('fullscreen-active');
        
        // Remover controles
        removerControlesFullscreen();
        
        // Forzar redimensionamiento
        setTimeout(() => {
            if (APP.map) {
                APP.map.invalidateSize();
            }
        }, 300);
        
        isFullscreen = false;
        console.log('🖥️ Salir de pantalla completa (API nativa)');
        
    } catch (error) {
        console.error('Error al salir de pantalla completa:', error);
        // Fallback a la versión CSS
        salirFullscreenCSS();
    }
}

function crearControlesFullscreen() {
    // Remover controles existentes
    removerControlesFullscreen();
    
    // Crear botón de salir
    const exitBtn = document.createElement('button');
    exitBtn.id = 'fullscreenExitBtn';
    exitBtn.className = 'btn-fullscreen-exit';
    exitBtn.innerHTML = '<i class="fas fa-times"></i> <span>Salir de pantalla completa</span>';
    exitBtn.onclick = salirFullscreen;
    document.body.appendChild(exitBtn);
    
    // Crear indicador de estado
    const indicator = document.createElement('div');
    indicator.id = 'fullscreenIndicator';
    indicator.className = 'fullscreen-indicator';
    indicator.innerHTML = '🖥️ Pantalla completa';
    document.body.appendChild(indicator);
}

function removerControlesFullscreen() {
    const exitBtn = document.getElementById('fullscreenExitBtn');
    if (exitBtn) {
        exitBtn.remove();
    }
    
    const indicator = document.getElementById('fullscreenIndicator');
    if (indicator) {
        indicator.remove();
    }
}

// Fallback CSS cuando la API no está disponible
function entrarFullscreenCSS() {
    const mapCard = document.getElementById('mapCard');
    mapCard.classList.add('fullscreen-mode-css');
    document.body.classList.add('fullscreen-active');
    crearControlesFullscreen();
    
    setTimeout(() => {
        if (APP.map) {
            APP.map.invalidateSize();
        }
    }, 300);
    
    isFullscreen = true;
    console.log('🖥️ Mapa en pantalla completa (fallback CSS)');
}

function salirFullscreenCSS() {
    const mapCard = document.getElementById('mapCard');
    mapCard.classList.remove('fullscreen-mode-css');
    document.body.classList.remove('fullscreen-active');
    removerControlesFullscreen();
    
    setTimeout(() => {
        if (APP.map) {
            APP.map.invalidateSize();
        }
    }, 300);
    
    isFullscreen = false;
    console.log('🖥️ Salir de pantalla completa (fallback CSS)');
}

// Manejador de eventos de fullscreen nativo
document.addEventListener('fullscreenchange', function(e) {
    if (!document.fullscreenElement) {
        // El usuario salió de fullscreen con ESC
        const mapCard = document.getElementById('mapCard');
        mapCard.classList.remove('fullscreen-mode');
        document.body.classList.remove('fullscreen-active');
        removerControlesFullscreen();
        
        setTimeout(() => {
            if (APP.map) {
                APP.map.invalidateSize();
            }
        }, 300);
        
        isFullscreen = false;
    }
});

// También para prefijos de navegadores
document.addEventListener('webkitfullscreenchange', function(e) {
    if (!document.webkitFullscreenElement) {
        const mapCard = document.getElementById('mapCard');
        mapCard.classList.remove('fullscreen-mode');
        document.body.classList.remove('fullscreen-active');
        removerControlesFullscreen();
        isFullscreen = false;
    }
});

document.addEventListener('msfullscreenchange', function(e) {
    if (!document.msFullscreenElement) {
        const mapCard = document.getElementById('mapCard');
        mapCard.classList.remove('fullscreen-mode');
        document.body.classList.remove('fullscreen-active');
        removerControlesFullscreen();
        isFullscreen = false;
    }
});

// Detectar tecla ESC manual para el fallback CSS
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape' && isFullscreen) {
        const mapCard = document.getElementById('mapCard');
        // Si estamos en modo CSS fallback, usar esa función
        if (mapCard.classList.contains('fullscreen-mode-css')) {
            salirFullscreenCSS();
        }
    }
});

function centrarMapa() {
    if (APP.currentCoordinates && APP.currentCoordinates.length > 0) {
        MapaBase.ajustarVista(APP.currentCoordinates);
    } else {
        APP.map.setView([19.4326, -99.1332], 6);
    }
}

    // ============================================================
    // ============================================================
    // 16. INICIALIZAR
    // ============================================================
    // ============================================================

    document.addEventListener('DOMContentLoaded', function() {
        Controlador.init();
    });

    console.log('📦 Mapa de Calor (versión sin dependencias externas)');
    console.log('🖥️ Pantalla completa disponible: Haz clic en el botón');
</script>
