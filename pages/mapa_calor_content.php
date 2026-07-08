<?php
// pages/mapa_calor_content.php - Versión sin dependencias externas
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
            <div class="stats-card">
                <div class="map-container">
                    <div id="map" style="height: 600px; width: 100%; border-radius: 10px;"></div>
                </div>
            </div>
            
            <div class="stats-card mt-3">
                <h5><i class="fas fa-list"></i> Lista de Coordenadas</h5>
                <div class="table-responsive" style="max-height: 250px; overflow-y: auto;">
                    <table class="table table-sm table-hover">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Título</th>
                                <th>Latitud</th>
                                <th>Longitud</th>
                                <th>Acciones</th>
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
.stats-card {
    background: white;
    border-radius: 10px;
    padding: 20px;
    margin-bottom: 20px;
    box-shadow: 0 0 10px rgba(0,0,0,0.1);
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
    box-shadow: 0 2px 4px rgba(0,0,0,0.3);
    transition: all 0.3s;
    cursor: pointer;
}
.cluster-marker:hover {
    transform: scale(1.1);
}
</style>

<!-- Solo Leaflet y XLSX - sin dependencias adicionales -->
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
    clusterGroups: {}
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
        const a = Math.sin(dLat/2) * Math.sin(dLat/2) +
                  Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
                  Math.sin(dLng/2) * Math.sin(dLng/2);
        const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
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
            zoom: 12,
            zoomControl: true
        });

        L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OSM</a> &copy; CartoDB',
            subdomains: 'abcd',
            maxZoom: 19
        }).addTo(APP.map);

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
                const workbook = XLSX.read(data, {type: 'array'});
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
                            coordinates.push({lat, lng, title, description, peso});
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
// 6. MAPA DE CALOR (IMPLEMENTACIÓN PROPIA)
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
        
        const radius = 1000;//parseInt(document.getElementById('heatRadius').value) || 40;
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
// 8. CLUSTERS (IMPLEMENTACIÓN PROPIA)
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
                iconAnchor: [size/2, size/2]
            });
            
            // 📍 Marcador central del cluster
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

            // 🔷 Polígono con los puntos del cluster
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
// 13. INICIALIZAR
// ============================================================
// ============================================================

document.addEventListener('DOMContentLoaded', function() {
    Controlador.init();
});

console.log('📦 Mapa de Calor (versión sin dependencias externas)');
</script>