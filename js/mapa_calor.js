// ============================================================
// js/mapa_calor.js - Versión modular optimizada
// ============================================================

// =====================
// 1. CONFIGURACIÓN GLOBAL Y UTILIDADES
// =====================

const APP = {
  map: null,
  heatLayer: null,
  gridLayer: null,
  clusterLayer: null,
  circleLayer: null,
  mapMarkers: [],
  currentCoordinates: [],
  heatData: [],
  markerClusterGroup: null
};

const COLORS = {
  danger: '#dc3545',
  warning: '#ffc107',
  info: '#17a2b8',
  success: '#28a745',
  primary: '#667eea'
};

const Utils = {
  escapeHtml: (text) => {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
  },

  calcularDistancia: (lat1, lng1, lat2, lng2) => {
    const R = 6371;
    const dLat = (lat2 - lat1) * Math.PI / 180;
    const dLng = (lng2 - lng1) * Math.PI / 180;
    const a = Math.sin(dLat/2) ** 2 +
              Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
              Math.sin(dLng/2) ** 2;
    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
    return R * c;
  },

  getColorByDensity: (count) => {
    if (count >= 10) return COLORS.danger;
    if (count >= 5) return COLORS.warning;
    if (count >= 2) return COLORS.info;
    return COLORS.success;
  },

  parseCoordinate: (row, index) => {
    const lat = parseFloat(row.lat || row.latitude || row.Lat || row.Latitude);
    const lng = parseFloat(row.lng || row.longitude || row.Lng || row.Longitude);
    if (isNaN(lat) || isNaN(lng)) return null;
    return {
      lat,
      lng,
      title: row.title || row.Titulo || row.nombre || `Punto ${index + 1}`,
      description: row.description || row.Descripcion || '',
      peso: parseFloat(row.peso || row.Peso || row.weight) || 1
    };
  }
};

// =====================
// 2. COMPONENTE: MAPA BASE
// =====================

const MapaBase = {
  init: () => {
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

    APP.map.on('click', (e) => {
      PuntosCercanos.buscar(e.latlng.lat, e.latlng.lng);
    });

    console.log('🗺️ Mapa base inicializado');
    return APP.map;
  },

  centrar: (lat, lng, zoom = 19) => APP.map.setView([lat, lng], zoom),

  ajustarVista: (coordinates) => {
    if (coordinates.length > 0) {
      const bounds = L.latLngBounds(coordinates.map(c => [c.lat, c.lng]));
      APP.map.fitBounds(bounds);
    }
  },

  limpiarTodo: () => {
    APP.mapMarkers.forEach(m => APP.map.removeLayer(m));
    APP.mapMarkers = [];

    [APP.heatLayer, APP.gridLayer, APP.clusterLayer, APP.circleLayer].forEach(layer => {
      if (layer) APP.map.removeLayer(layer);
    });

    APP.heatLayer = APP.gridLayer = APP.clusterLayer = APP.circleLayer = null;
  }
};

// =====================
// 3. COMPONENTE: CARGA DE ARCHIVOS
// =====================

const CargaArchivos = {
  init: () => {
    document.getElementById('excelFile').addEventListener('change', async (e) => {
      const file = e.target.files[0];
      if (file) await CargaArchivos.procesar(file);
    });

    const uploadArea = document.getElementById('uploadArea');
    uploadArea.addEventListener('dragover', (e) => {
      e.preventDefault();
      uploadArea.style.borderColor = COLORS.primary;
      uploadArea.style.background = '#e8eaf6';
    });

    uploadArea.addEventListener('dragleave', (e) => {
      e.preventDefault();
      uploadArea.style.borderColor = COLORS.primary;
      uploadArea.style.background = '#f8f9fa';
    });

    uploadArea.addEventListener('drop', async (e) => {
      e.preventDefault();
      uploadArea.style.borderColor = COLORS.primary;
      uploadArea.style.background = '#f8f9fa';
      const file = e.dataTransfer.files[0];
      if (file) await CargaArchivos.procesar(file);
    });
  },

  procesar: async (file) => {
    try {
      const rows = await leerArchivoExcel(file);
      const coordinates = rows.map(Utils.parseCoordinate).filter(Boolean);

      if (coordinates.length > 0) {
        APP.currentCoordinates = coordinates;
        APP.heatData = coordinates.map(c => [c.lat, c.lng, c.peso]);

        MapaBase.limpiarTodo();
        Marcadores.agregar(coordinates);

        if (document.getElementById('toggleHeatmap').checked) Heatmap.crear(APP.heatData);
        if (document.getElementById('toggleClusters').checked) Clusters.crear(coordinates);
        if (document.getElementById('toggleGrid').checked) Grid.crear(coordinates);

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
  }
};

// ============================================================
// ============================================================
// 4. COMPONENTE: MARCADORES
// ============================================================
// ============================================================

const Marcadores = {
    // Agregar marcadores al mapa
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
    
    // Limpiar marcadores
    limpiar: function() {
        APP.mapMarkers.forEach(m => APP.map.removeLayer(m));
        APP.mapMarkers = [];
    }
};




// ============================================================
// ============================================================
// 5. COMPONENTE: MAPA DE CALOR
// ============================================================
// ============================================================

const Heatmap = {
  crear: function(data) {
    if (APP.heatLayer) {
      APP.map.removeLayer(APP.heatLayer);
      APP.heatLayer = null;
    }

    if (!data || data.length === 0) return;

    // Radio dinámico según zoom actual
    const zoom = APP.map.getZoom();
    const radius = this._calcularRadioPorZoom(zoom);

    APP.heatLayer = L.heatLayer(data, {
      radius: radius,
      blur: 15,
      maxZoom: 17,
      gradient: {
        0.2: 'blue',
        0.4: 'cyan',
        0.6: 'lime',
        0.8: 'yellow',
        1.0: 'red'
      }
    }).addTo(APP.map);

    console.log(`🔥 Heatmap creado con ${data.length} puntos y radio ${radius}`);
  },

  // Función auxiliar para calcular radio dinámico
  _calcularRadioPorZoom: function(zoom) {
    // Ejemplo: a mayor zoom, mayor radio
    if (zoom >= 16) return 40;
    if (zoom >= 14) return 30;
    if (zoom >= 12) return 20;
    return 15; // zoom bajo → radio pequeño
  },

  // Activar/desactivar
  toggle: function() {
    if (document.getElementById('toggleHeatmap').checked) {
      if (APP.heatData.length > 0) {
        this.crear(APP.heatData);
      }
    } else {
      if (APP.heatLayer) {
        APP.map.removeLayer(APP.heatLayer);
        APP.heatLayer = null;
      }
    }
  },

  // Vincular actualización automática al zoom
  bindZoomUpdate: function() {
    APP.map.on('zoomend', () => {
      if (document.getElementById('toggleHeatmap').checked && APP.heatData.length > 0) {
        this.crear(APP.heatData);
      }
    });
  }
};
    
    // Fallback: usar círculos con opacidad
    _crearFallback: function(data) {
        const heatGroup = L.layerGroup();
        
        data.forEach(point => {
            const lat = point[0];
            const lng = point[1];
            const intensity = point[2] || 1;
            
            const radius = 20 + (intensity * 10);
            const opacity = Math.min(intensity / 5, 0.6);
            const color = intensity > 3 ? '#dc3545' : (intensity > 2 ? '#ffc107' : '#17a2b8');
            
            const circle = L.circle([lat, lng], {
                radius: radius,
                color: color,
                fillColor: color,
                fillOpacity: opacity,
                weight: 0
            });
            
            heatGroup.addLayer(circle);
        });
        
        APP.heatLayer = heatGroup.addTo(APP.map);
        console.log(`🔥 Heatmap fallback creado con ${data.length} puntos`);
    },
    
    // Activar/desactivar
    toggle: function() {
        if (document.getElementById('toggleHeatmap').checked) {
            if (APP.heatData.length > 0) {
                this.crear(APP.heatData);
            }
        } else {
            if (APP.heatLayer) {
                APP.map.removeLayer(APP.heatLayer);
                APP.heatLayer = null;
            }
        }
    },
    
    // Actualizar radio
    updateRadius: function() {
        const radius = document.getElementById('heatRadius').value;
        document.getElementById('radiusValue').textContent = radius;
        if (document.getElementById('toggleHeatmap').checked) {
            this.toggle();
            setTimeout(() => {
                if (document.getElementById('toggleHeatmap').checked) {
                    this.crear(APP.heatData);
                }
            }, 100);
        }
    }
};

// ============================================================
// ============================================================
// 6. COMPONENTE: MALLA DE DENSIDAD (GRID)
// ============================================================
// ============================================================

const Grid = {
    // Crear malla de densidad
    crear: function(coordinates) {
        // Limpiar layer anterior
        if (APP.gridLayer) {
            APP.map.removeLayer(APP.gridLayer);
            APP.gridLayer = null;
        }
        
        if (!coordinates || coordinates.length === 0) return;
        
        // Calcular límites
        const lats = coordinates.map(c => c.lat);
        const lngs = coordinates.map(c => c.lng);
        const minLat = Math.min(...lats);
        const maxLat = Math.max(...lats);
        const minLng = Math.min(...lngs);
        const maxLng = Math.max(...lngs);
        
        const cellSize = 0.01;
        const gridData = [];
        
        for (let lat = minLat; lat <= maxLat; lat += cellSize) {
            for (let lng = minLng; lng <= maxLng; lng += cellSize) {
                const count = coordinates.filter(c => 
                    c.lat >= lat && c.lat < lat + cellSize &&
                    c.lng >= lng && c.lng < lng + cellSize
                ).length;
                
                if (count > 0) {
                    const centerLat = lat + cellSize / 2;
                    const centerLng = lng + cellSize / 2;
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
                            <p><strong>Centro:</strong> ${centerLat.toFixed(4)}, ${centerLng.toFixed(4)}</p>
                        </div>
                    `);
                    
                    gridData.push(rect);
                }
            }
        }
        
        APP.gridLayer = L.layerGroup(gridData).addTo(APP.map);
        console.log(`📊 Malla de densidad creada con ${gridData.length} celdas`);
    },
    
    // Activar/desactivar
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
// 7. COMPONENTE: CLUSTERS
// ============================================================
// ============================================================

const Clusters = {
    // Crear clusters
    crear: function(coordinates) {
        // Limpiar layer anterior
        if (APP.clusterLayer) {
            APP.map.removeLayer(APP.clusterLayer);
            APP.clusterLayer = null;
        }
        
        if (!coordinates || coordinates.length === 0) return;
        
        APP.clusterLayer = L.markerClusterGroup({
            showCoverageOnHover: true,
            zoomToBoundsOnClick: true,
            spiderfyOnMaxZoom: true,
            maxClusterRadius: 80,
            iconCreateFunction: function(cluster) {
                const count = cluster.getChildCount();
                const size = count > 50 ? 'big' : (count > 20 ? 'medium' : 'small');
                const color = count > 50 ? '#dc3545' : (count > 20 ? '#ffc107' : '#28a745');
                
                return L.divIcon({
                    html: `<div style="background-color: ${color}; color: white; border-radius: 50%; width: ${size === 'big' ? 40 : (size === 'medium' ? 32 : 24)}px; height: ${size === 'big' ? 40 : (size === 'medium' ? 32 : 24)}px; display: flex; align-items: center; justify-content: center; font-weight: bold; border: 2px solid white; box-shadow: 0 2px 4px rgba(0,0,0,0.3);">${count}</div>`,
                    className: 'cluster-icon',
                    iconSize: size === 'big' ? [40, 40] : (size === 'medium' ? [32, 32] : [24, 24])
                });
            }
        });
        
        coordinates.forEach(coord => {
            const marker = L.circleMarker([coord.lat, coord.lng], {
                radius: 3,
                fillColor: '#667eea',
                color: '#667eea',
                weight: 1,
                opacity: 0.5,
                fillOpacity: 0.8
            }).bindPopup(`
                <div>
                    <h6><strong>${coord.title}</strong></h6>
                    <p><strong>Lat:</strong> ${coord.lat.toFixed(6)}</p>
                    <p><strong>Lng:</strong> ${coord.lng.toFixed(6)}</p>
                    ${coord.description ? `<p>${coord.description}</p>` : ''}
                </div>
            `);
            
            APP.clusterLayer.addLayer(marker);
        });
        
        APP.clusterLayer.addTo(APP.map);
        console.log(`📦 Clusters creados con ${coordinates.length} puntos`);
    },
    
    // Activar/desactivar
    toggle: function() {
        if (document.getElementById('toggleClusters').checked) {
            if (APP.currentCoordinates.length > 0) {
                this.crear(APP.currentCoordinates);
            }
        } else {
            if (APP.clusterLayer) {
                APP.map.removeLayer(APP.clusterLayer);
                APP.clusterLayer = null;
            }
        }
    }
};

// ============================================================
// ============================================================
// 8. COMPONENTE: PUNTOS CERCANOS
// ============================================================
// ============================================================

const PuntosCercanos = {
    // Buscar los 3 puntos más cercanos
    buscar: function(lat, lng) {
        if (APP.currentCoordinates.length === 0) {
            document.getElementById('nearestPoints').innerHTML = `
                <div class="text-muted text-center py-2">
                    <i class="fas fa-info-circle"></i> No hay puntos cargados
                </div>
            `;
            return;
        }
        
        // Calcular distancias
        const pointsWithDistance = APP.currentCoordinates.map(coord => {
            const distancia = Utils.calcularDistancia(lat, lng, coord.lat, coord.lng);
            return { ...coord, distancia };
        });
        
        // Ordenar y obtener los 3 más cercanos
        const sorted = pointsWithDistance.sort((a, b) => a.distancia - b.distancia);
        const nearest = sorted.slice(0, 3);
        
        // Mostrar resultados en la UI
        this._mostrarResultados(lat, lng, nearest);
        
        // Dibujar en el mapa
        this._dibujarEnMapa(lat, lng, nearest);
    },
    
    // Mostrar resultados en la UI
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
    
    // Dibujar círculos y líneas en el mapa
    _dibujarEnMapa: function(lat, lng, nearest) {
        // Limpiar círculos anteriores
        if (APP.circleLayer) {
            APP.map.removeLayer(APP.circleLayer);
            APP.circleLayer = null;
        }
        
        const circles = [];
        
        // Círculo en el punto de referencia
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
        
        // Círculos de los puntos cercanos
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
            
            // Línea de conexión
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
    
    // Centrar en un punto
    centrar: function(lat, lng) {
        MapaBase.centrar(lat, lng, 15);
    }
};

// ============================================================
// ============================================================
// 9. COMPONENTE: TABLA DE COORDENADAS
// ============================================================
// ============================================================

const Tabla = {
    // Actualizar tabla
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

// ============================================================
// ============================================================
// 10. COMPONENTE: ESTADÍSTICAS
// ============================================================
// ============================================================

const Estadisticas = {
    // Actualizar estadísticas
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
// 11. COMPONENTE: CONTROLADOR PRINCIPAL
// ============================================================
// ============================================================

const Controlador = {
    // Inicializar todo
    init: function() {
        console.log('🚀 Inicializando aplicación...');
        
        // 1. Inicializar mapa
        MapaBase.init();
        
        // 2. Inicializar carga de archivos
        CargaArchivos.init();
        
        // 3. Configurar eventos de controles
        this._configurarEventos();

        // Vincular actualización de heatmap al zoom    
        Heatmap.bindZoomUpdate();
        
        // 4. Verificar dependencias
        this._verificarDependencias();
        
        console.log('✅ Aplicación inicializada correctamente');
    },
    
    // Configurar eventos de la interfaz
    _configurarEventos: function() {
        // Botones de control
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
    },
    
    // Verificar dependencias
    _verificarDependencias: function() {
        // Verificar L.heatLayer
        if (typeof L.heatLayer === 'function') {
            console.log('✅ L.heatLayer disponible');
        } else {
            console.warn('⚠️ L.heatLayer NO disponible, usando fallback');
        }
        
        // Verificar L.markerClusterGroup
        if (typeof L.markerClusterGroup === 'function') {
            console.log('✅ L.markerClusterGroup disponible');
        } else {
            console.warn('⚠️ L.markerClusterGroup NO disponible');
        }
    },
    
    // Limpiar todos los datos
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
        }
    }
};

// ============================================================
// ============================================================
// 12. EXPOSICIÓN DE FUNCIONES GLOBALES
// ============================================================
// ============================================================

// Hacer funciones accesibles globalmente para los botones HTML
window.Controlador = Controlador;
window.MapaBase = MapaBase;
window.Heatmap = Heatmap;
window.Grid = Grid;
window.Clusters = Clusters;
window.PuntosCercanos = PuntosCercanos;
window.CargaArchivos = CargaArchivos;
window.Tabla = Tabla;
window.Estadisticas = Estadisticas;

// Funciones específicas para eventos HTML
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

// ============================================================
// ============================================================
// 13. INICIALIZAR APLICACIÓN
// ============================================================
// ============================================================

document.addEventListener('DOMContentLoaded', function() {
    Controlador.init();
});

console.log('📦 Módulo de Mapa de Calor (versión modular) cargado');

// =====================
// Función auxiliar para leer Excel con Promesas
// =====================

const leerArchivoExcel = (file) => {
  return new Promise((resolve, reject) => {
    const reader = new FileReader();
    reader.onload = (e) => {
      try {
        const data = new Uint8Array(e.target.result);
        const workbook = XLSX.read(data, { type: 'array' });
        const firstSheet = workbook.Sheets[workbook.SheetNames[0]];
        const rows = XLSX.utils.sheet_to_json(firstSheet);
        resolve(rows);
      } catch (error) {
        reject(error);
      }
    };
    reader.onerror = reject;
    reader.readAsArrayBuffer(file);
  });
};