<?php
// pages/geocerca_content.php
?>

<div class="container-fluid">
    <!-- Encabezado -->
    <div class="row mb-4">
        <div class="col">
            <h2><i class="fas fa-map-marked-alt"></i> Módulo de Geocerca</h2>
            <p class="text-muted">Verifica si tu ubicación está dentro del polígono de la geocerca</p>
        </div>
        <div class="col text-end">
            <button class="btn btn-success" onclick="descargarReporte()">
                <i class="fas fa-file-excel"></i> Exportar Reporte
            </button>
            <button class="btn btn-primary" onclick="centrarMapa()">
                <i class="fas fa-home"></i> Centrar Mapa
            </button>
            <button class="btn btn-danger" onclick="limpiarHistorial()">
                <i class="fas fa-trash"></i> Limpiar Historial
            </button>
        </div>
    </div>

    <div class="row">
        <!-- Panel de control -->
        <div class="col-md-4">
            <div class="card mb-3">
                <div class="card-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                    <h5 class="mb-0"><i class="fas fa-satellite-dish"></i> Control de Ubicación</h5>
                </div>
                <div class="card-body">
                    <!-- Estado de la geocerca -->
                    <div id="estadoGeocerca" class="alert alert-secondary text-center">
                        <i class="fas fa-spinner fa-spin"></i> Esperando ubicación...
                    </div>

                    <!-- Coordenadas actuales -->
                    <div class="row mb-2">
                        <div class="col-6">
                            <label class="form-label text-muted small">Latitud</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-arrow-up"></i></span>
                                <input type="text" id="latitudActual" class="form-control" placeholder="--" readonly>
                            </div>
                        </div>
                        <div class="col-6">
                            <label class="form-label text-muted small">Longitud</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-arrow-right"></i></span>
                                <input type="text" id="longitudActual" class="form-control" placeholder="--" readonly>
                            </div>
                        </div>
                    </div>

                    <!-- Precisión -->
                    <div class="mb-2">
                        <label class="form-label text-muted small">Precisión</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-bullseye"></i></span>
                            <input type="text" id="precisionActual" class="form-control" placeholder="--" readonly>
                        </div>
                    </div>

                    <!-- Botones -->
                    <div class="d-grid gap-2">
                        <button class="btn btn-primary" onclick="obtenerUbicacion()">
                            <i class="fas fa-crosshairs"></i> Obtener Ubicación
                        </button>
                        <button class="btn btn-outline-secondary" onclick="simularUbicacion()">
                            <i class="fas fa-map-pin"></i> Simular Ubicación
                        </button>
                        <button class="btn btn-outline-danger" onclick="limpiarUbicacion()">
                            <i class="fas fa-eraser"></i> Limpiar
                        </button>
                    </div>
                </div>
            </div>

            <!-- Información de la geocerca -->
            <div class="card">
                <div class="card-header">
                    <h6><i class="fas fa-info-circle"></i> Información de la Geocerca</h6>
                </div>
                <div class="card-body">
                    <div id="infoGeocerca">
                        <p><strong>Nombre:</strong> <span id="geocercaNombre">ANSP - San Miguel</span></p>
                        <p><strong>Vértices:</strong> <span id="geocercaVertices">0</span></p>
                        <p><strong>Área:</strong> <span id="geocercaArea">0</span> km²</p>
                        <p><strong>Estado:</strong> <span id="geocercaEstado" class="badge bg-secondary">Sin verificar</span></p>
                    </div>
                    <hr>
                    <div id="historialCoordenadas" style="max-height: 150px; overflow-y: auto;">
                        <h6><i class="fas fa-history"></i> Historial</h6>
                        <div id="listaHistorial" class="small"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Mapa -->
        <div class="col-md-8">
            <div class="card">
                <div class="card-body p-0">
                    <div id="mapaGeocerca" style="height: 600px; width: 100%; border-radius: 10px;"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal para simular ubicación -->
<div class="modal fade" id="modalSimular" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-map-pin"></i> Simular Ubicación</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Ingresa las coordenadas que deseas simular:</p>
                <div class="row">
                    <div class="col-6">
                        <label class="form-label">Latitud</label>
                        <input type="number" id="simLat" class="form-control" step="0.000001" value="19.812382">
                        <small class="text-muted">Ej: 19.812382</small>
                    </div>
                    <div class="col-6">
                        <label class="form-label">Longitud</label>
                        <input type="number" id="simLng" class="form-control" step="0.000001" value="-99.274552">
                        <small class="text-muted">Ej: -99.274552</small>
                    </div>
                </div>
                <div class="mt-2">
                    <label class="form-label">O haz clic en el mapa</label>
                    <p class="text-muted small">También puedes hacer clic directamente en el mapa para simular una ubicación</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" onclick="simularCoordenadas()">
                    <i class="fas fa-check"></i> Simular
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Librerías -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- Turf.js para detección de puntos en polígonos -->
<script src="https://cdn.jsdelivr.net/npm/@turf/turf@6/turf.min.js"></script>

<style>
#mapaGeocerca {
    border-radius: 10px;
}
.estado-dentro {
    background-color: #d4edda !important;
    color: #155724 !important;
    border-color: #c3e6cb !important;
}
.estado-fuera {
    background-color: #f8d7da !important;
    color: #721c24 !important;
    border-color: #f5c6cb !important;
}
.estado-esperando {
    background-color: #fff3cd !important;
    color: #856404 !important;
    border-color: #ffeaa7 !important;
}
.click-marker {
    animation: pulse 1s ease-in-out infinite;
}
@keyframes pulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.15); }
    100% { transform: scale(1); }
}
</style>

<script>
// ==========================================
// 1. CONFIGURACIÓN DE LA GEOCERCA
// ==========================================

const POLIGONO_GEOCERCA = {
    id: 'ansp_smj',
    nombre: 'ANSP - San Miguel de los Jagüeyes',
    color: '#e74c3c',
    puntos: [
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
};

// ==========================================
// 2. VARIABLES GLOBALES
// ==========================================

let map;
let polygon;
let marcadorUsuario;
let circuloPrecision;
let ultimaUbicacion = null;
let historial = [];
let dentroGeocerca = false;
let marcadorClick = null;

// ==========================================
// 3. INICIALIZAR MAPA
// ==========================================

function initMap() {
    const container = document.getElementById('mapaGeocerca');
    if (!container) return;

    // Verificar que Turf.js esté cargado
    if (typeof turf === 'undefined') {
        console.error('❌ Turf.js no está cargado. Verifica la conexión a internet.');
        alert('Error: No se pudo cargar la librería Turf.js. Verifica tu conexión a internet.');
        return;
    }

    const center = calcularCentroPoligono(POLIGONO_GEOCERCA.puntos);

    map = L.map('mapaGeocerca', {
        center: center,
        zoom: 15,
        zoomControl: true
    });

    L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OSM</a> &copy; CartoDB',
        subdomains: 'abcd',
        maxZoom: 19
    }).addTo(map);

    dibujarPoligono();

    // Evento click en el mapa
    map.on('click', function(e) {
        const lat = e.latlng.lat;
        const lng = e.latlng.lng;
        document.getElementById('simLat').value = lat;
        document.getElementById('simLng').value = lng;
        procesarClick(lat, lng);
        
        const modal = bootstrap.Modal.getInstance(document.getElementById('modalSimular'));
        if (modal) modal.hide();
    });

    // Evento click en el polígono
    polygon.on('click', function(e) {
        L.DomEvent.stopPropagation(e);
        const lat = e.latlng.lat;
        const lng = e.latlng.lng;
        document.getElementById('simLat').value = lat;
        document.getElementById('simLng').value = lng;
        procesarClick(lat, lng);
    });

    map.on('moveend', function() {
        actualizarInfoGeocerca();
    });

    setTimeout(function() {
        obtenerUbicacion();
    }, 1000);

    actualizarInfoGeocerca();

    console.log('🗺️ Mapa de Geocerca inicializado');
    console.log('📍 Polígono con ' + POLIGONO_GEOCERCA.puntos.length + ' vértices');
}

// ==========================================
// 4. PROCESAR CLICK
// ==========================================

function procesarClick(lat, lng) {
    const dentro = puntoDentroPoligono(lat, lng, POLIGONO_GEOCERCA.puntos);
    
    console.log(`🖱️ Click: (${lat.toFixed(6)}, ${lng.toFixed(6)}) → ${dentro ? '✅ DENTRO' : '❌ FUERA'}`);
    
    mostrarPuntoClick(lat, lng, dentro);
    procesarUbicacion(lat, lng, 10);
    agregarHistorial(lat, lng, dentro);
}

// ==========================================
// 5. MOSTRAR PUNTO DE CLICK
// ==========================================

function mostrarPuntoClick(lat, lng, dentro) {
    if (marcadorClick) {
        map.removeLayer(marcadorClick);
    }
    
    const color = dentro ? '#28a745' : '#dc3545';
    const icono = dentro ? '✅' : '❌';
    const texto = dentro ? 'DENTRO' : 'FUERA';
    
    const iconHtml = `
        <div style="
            background-color: ${color};
            width: 40px;
            height: 40px;
            border-radius: 50%;
            border: 4px solid white;
            box-shadow: 0 0 20px rgba(0,0,0,0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            font-size: 18px;
            color: white;
            font-weight: bold;
            text-align: center;
            line-height: 1.1;
        ">
            <div style="font-size: 20px;">${icono}</div>
            <div style="font-size: 8px; margin-top: 2px;">${texto}</div>
        </div>
    `;
    
    const icon = L.divIcon({
        html: iconHtml,
        className: 'click-marker',
        iconSize: [48, 48],
        iconAnchor: [24, 24]
    });
    
    marcadorClick = L.marker([lat, lng], {
        icon: icon,
        title: dentro ? '✅ Dentro de la geocerca' : '❌ Fuera de la geocerca'
    }).addTo(map);
    
    marcadorClick.bindPopup(`
        <div style="min-width: 200px;">
            <h6>📍 Punto seleccionado</h6>
            <p style="margin: 2px 0;">
                <strong>Lat:</strong> ${lat.toFixed(6)}<br>
                <strong>Lng:</strong> ${lng.toFixed(6)}<br>
                <strong>Estado:</strong> 
                <span class="badge ${dentro ? 'bg-success' : 'bg-danger'}">
                    ${dentro ? '✅ DENTRO' : '❌ FUERA'}
                </span>
            </p>
            <hr style="margin: 5px 0;">
            <small class="text-muted">${new Date().toLocaleString()}</small>
        </div>
    `);
    
    setTimeout(() => {
        if (marcadorClick) {
            marcadorClick.openPopup();
        }
    }, 300);
}

// ==========================================
// 6. DIBUJAR POLÍGONO
// ==========================================

function dibujarPoligono() {
    if (polygon) {
        map.removeLayer(polygon);
    }

    polygon = L.polygon(POLIGONO_GEOCERCA.puntos, {
        color: POLIGONO_GEOCERCA.color,
        weight: 3,
        opacity: 0.8,
        fillColor: POLIGONO_GEOCERCA.color,
        fillOpacity: 0.2,
        className: 'geocerca-polygon',
        interactive: true
    }).addTo(map);

    polygon.bindPopup(`
        <b>${POLIGONO_GEOCERCA.nombre}</b><br>
        <span style="color: ${POLIGONO_GEOCERCA.color}; font-weight: bold;">●</span> 
        <span id="popupEstado">Sin verificar</span>
    `);

    polygon.on('mouseover', function() {
        this.setStyle({
            fillOpacity: 0.4,
            weight: 4
        });
        this.bringToFront();
    });

    polygon.on('mouseout', function() {
        this.setStyle({
            fillOpacity: 0.2,
            weight: 3
        });
    });

    map.fitBounds(polygon.getBounds());

    document.getElementById('geocercaVertices').textContent = POLIGONO_GEOCERCA.puntos.length;
    const area = calcularAreaPoligono(POLIGONO_GEOCERCA.puntos);
    document.getElementById('geocercaArea').textContent = area.toFixed(2);
}

// ==========================================
// 7. FUNCIONES DE GEOMETRÍA
// ==========================================

function calcularCentroPoligono(puntos) {
    let lat = 0, lng = 0;
    puntos.forEach(p => {
        lat += p[0];
        lng += p[1];
    });
    return [lat / puntos.length, lng / puntos.length];
}

function calcularAreaPoligono(puntos) {
    let area = 0;
    const n = puntos.length;
    for (let i = 0; i < n; i++) {
        const j = (i + 1) % n;
        area += puntos[i][0] * puntos[j][1];
        area -= puntos[j][0] * puntos[i][1];
    }
    area = Math.abs(area) / 2;
    return area * 111 * 111;
}

// ==========================================
// 8. FUNCIÓN CORREGIDA - USANDO TURF.JS
// ==========================================

function puntoDentroPoligono(lat, lng, puntos) {
    try {
        // Verificar que Turf.js esté disponible
        if (typeof turf === 'undefined') {
            console.error('❌ Turf.js no está disponible');
            return false;
        }

        // Turf usa [longitud, latitud] (invertido)
        const coordenadas = puntos.map(p => [p[1], p[0]]);

        // Cerrar el polígono si aún no está cerrado
        const primera = coordenadas[0];
        const ultima = coordenadas[coordenadas.length - 1];

        if (primera[0] !== ultima[0] || primera[1] !== ultima[1]) {
            coordenadas.push(primera);
        }

        // Crear polígono y punto en Turf
        const polygon = turf.polygon([coordenadas]);
        const point = turf.point([lng, lat]);

        // Verificar si el punto está dentro del polígono
        return turf.booleanPointInPolygon(point, polygon);
        
    } catch (error) {
        console.error('❌ Error en puntoDentroPoligono:', error);
        return false;
    }
}

// ==========================================
// 9. OBTENER UBICACIÓN DEL DISPOSITIVO
// ==========================================

function obtenerUbicacion() {
    const estadoDiv = document.getElementById('estadoGeocerca');
    estadoDiv.className = 'alert estado-esperando text-center';
    estadoDiv.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Obteniendo ubicación...';

    if (!navigator.geolocation) {
        estadoDiv.className = 'alert estado-fuera text-center';
        estadoDiv.innerHTML = '<i class="fas fa-exclamation-triangle"></i> Tu navegador no soporta geolocalización. Usa la simulación.';
        return;
    }

    navigator.geolocation.getCurrentPosition(
        function(position) {
            const lat = position.coords.latitude;
            const lng = position.coords.longitude;
            const precision = position.coords.accuracy;
            procesarUbicacion(lat, lng, precision);
        },
        function(error) {
            let mensaje = 'Error al obtener ubicación: ';
            switch(error.code) {
                case error.PERMISSION_DENIED:
                    mensaje += 'Permiso denegado. Usa la simulación.';
                    break;
                case error.POSITION_UNAVAILABLE:
                    mensaje += 'Posición no disponible. Usa la simulación.';
                    break;
                case error.TIMEOUT:
                    mensaje += 'Tiempo de espera agotado. Usa la simulación.';
                    break;
                default:
                    mensaje += error.message;
            }
            estadoDiv.className = 'alert estado-fuera text-center';
            estadoDiv.innerHTML = '<i class="fas fa-exclamation-triangle"></i> ' + mensaje;
        },
        {
            enableHighAccuracy: true,
            timeout: 10000,
            maximumAge: 0
        }
    );
}

// ==========================================
// 10. PROCESAR UBICACIÓN
// ==========================================

function procesarUbicacion(lat, lng, precision) {
    ultimaUbicacion = { lat, lng, precision };
    
    document.getElementById('latitudActual').value = lat.toFixed(6);
    document.getElementById('longitudActual').value = lng.toFixed(6);
    document.getElementById('precisionActual').value = precision ? precision.toFixed(1) + ' m' : 'N/A';

    const dentro = puntoDentroPoligono(lat, lng, POLIGONO_GEOCERCA.puntos);
    dentroGeocerca = dentro;

    console.log(`📍 Verificación: (${lat.toFixed(6)}, ${lng.toFixed(6)}) → ${dentro ? '✅ DENTRO' : '❌ FUERA'}`);

    const estadoDiv = document.getElementById('estadoGeocerca');
    if (dentro) {
        estadoDiv.className = 'alert estado-dentro text-center';
        estadoDiv.innerHTML = '<i class="fas fa-check-circle"></i> ✅ DENTRO de la geocerca';
    } else {
        estadoDiv.className = 'alert estado-fuera text-center';
        estadoDiv.innerHTML = '<i class="fas fa-times-circle"></i> ❌ FUERA de la geocerca';
    }

    actualizarPopupPoligono(dentro);

    const estadoBadge = document.getElementById('geocercaEstado');
    if (dentro) {
        estadoBadge.className = 'badge bg-success';
        estadoBadge.textContent = '✅ Dentro';
    } else {
        estadoBadge.className = 'badge bg-danger';
        estadoBadge.textContent = '❌ Fuera';
    }

    dibujarMarcador(lat, lng, dentro, precision);
    agregarHistorial(lat, lng, dentro);
    mostrarNotificacion(dentro);
    registrarEnServidor(lat, lng, dentro);
}

// ==========================================
// 11. DIBUJAR MARCADOR
// ==========================================

function dibujarMarcador(lat, lng, dentro, precision) {
    if (marcadorUsuario) {
        map.removeLayer(marcadorUsuario);
    }
    if (circuloPrecision) {
        map.removeLayer(circuloPrecision);
    }

    const iconColor = dentro ? '#28a745' : '#dc3545';
    const iconHtml = `
        <div style="
            background-color: ${iconColor};
            width: 24px;
            height: 24px;
            border-radius: 50%;
            border: 3px solid white;
            box-shadow: 0 0 15px rgba(0,0,0,0.4);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            color: white;
        ">
            <i class="fas fa-user"></i>
        </div>
    `;

    const icon = L.divIcon({
        html: iconHtml,
        className: 'custom-marker',
        iconSize: [30, 30],
        iconAnchor: [15, 15]
    });

    marcadorUsuario = L.marker([lat, lng], {
        icon: icon,
        title: dentro ? '✅ Dentro de la geocerca' : '❌ Fuera de la geocerca'
    }).addTo(map);

    const popupContent = `
        <div style="min-width: 200px;">
            <h6>📍 Tu ubicación</h6>
            <p style="margin: 2px 0;">
                <strong>Lat:</strong> ${lat.toFixed(6)}<br>
                <strong>Lng:</strong> ${lng.toFixed(6)}<br>
                <strong>Estado:</strong> 
                <span class="badge ${dentro ? 'bg-success' : 'bg-danger'}">
                    ${dentro ? '✅ Dentro' : '❌ Fuera'}
                </span>
                ${precision ? `<br><strong>Precisión:</strong> ${precision.toFixed(1)} m` : ''}
            </p>
            <hr style="margin: 5px 0;">
            <small class="text-muted">${new Date().toLocaleString()}</small>
        </div>
    `;
    marcadorUsuario.bindPopup(popupContent);

    if (precision && precision < 100) {
        circuloPrecision = L.circle([lat, lng], {
            radius: precision,
            color: dentro ? '#28a745' : '#dc3545',
            fillColor: dentro ? '#28a745' : '#dc3545',
            fillOpacity: 0.1,
            weight: 1
        }).addTo(map);
    }

    map.setView([lat, lng], 16);
    actualizarPopupPoligono(dentro);
}

// ==========================================
// 12. ACTUALIZAR POPUP DEL POLÍGONO
// ==========================================

function actualizarPopupPoligono(dentro) {
    if (!polygon) return;
    
    const estado = dentro ? '✅ Dentro' : '❌ Fuera';
    const color = dentro ? '#28a745' : '#dc3545';
    
    polygon.setPopupContent(`
        <div style="min-width: 200px;">
            <b>${POLIGONO_GEOCERCA.nombre}</b><br>
            <span style="color: ${color}; font-weight: bold;">●</span> 
            <span style="color: ${color}; font-weight: bold;">${estado}</span>
            ${ultimaUbicacion ? `<br><small>Última verificación: ${new Date().toLocaleTimeString()}</small>` : ''}
            <br><small class="text-muted">${ultimaUbicacion ? `(${ultimaUbicacion.lat.toFixed(6)}, ${ultimaUbicacion.lng.toFixed(6)})` : ''}</small>
        </div>
    `);
}

// ==========================================
// 13. HISTORIAL
// ==========================================

function agregarHistorial(lat, lng, dentro) {
    const timestamp = new Date().toLocaleString();
    historial.push({ lat, lng, dentro, timestamp });
    if (historial.length > 20) historial.shift();
    actualizarHistorial();
}

function actualizarHistorial() {
    const container = document.getElementById('listaHistorial');
    container.innerHTML = '';
    if (historial.length === 0) {
        container.innerHTML = '<span class="text-muted">Sin registros</span>';
        return;
    }
    historial.slice(-10).reverse().forEach(item => {
        const div = document.createElement('div');
        div.className = 'mb-1 p-1 border-bottom';
        div.style.borderLeft = `3px solid ${item.dentro ? '#28a745' : '#dc3545'}`;
        div.innerHTML = `
            <span class="badge ${item.dentro ? 'bg-success' : 'bg-danger'}">
                ${item.dentro ? '✅' : '❌'}
            </span>
            <small>${item.lat.toFixed(5)}, ${item.lng.toFixed(5)}</small>
            <small class="text-muted float-end">${item.timestamp}</small>
        `;
        container.appendChild(div);
    });
}

// ==========================================
// 14. NOTIFICACIONES
// ==========================================

function mostrarNotificacion(dentro) {
    if (!('Notification' in window)) return;
    if (Notification.permission === 'granted') {
        new Notification('Geocerca', {
            body: dentro ? '✅ Estás dentro de la geocerca' : '❌ Estás fuera de la geocerca',
            icon: dentro ? 'https://cdn-icons-png.flaticon.com/512/190/190411.png' : 'https://cdn-icons-png.flaticon.com/512/1828/1828843.png'
        });
    } else if (Notification.permission !== 'denied') {
        Notification.requestPermission();
    }
}

// ==========================================
// 15. REGISTRAR EN EL SERVIDOR
// ==========================================

function registrarEnServidor(lat, lng, dentro) {
    $.ajax({
        url: 'ajax/verificar_geocerca.php',
        type: 'POST',
        data: {
            lat: lat,
            lng: lng,
            dentro: dentro ? 1 : 0,
            precision: document.getElementById('precisionActual').value
        },
        success: function(response) {
            console.log('✅ Registro enviado al servidor');
        },
        error: function() {
            console.log('⚠️ Error al registrar en el servidor');
        }
    });
}

// ==========================================
// 16. SIMULAR UBICACIÓN
// ==========================================

function simularUbicacion() {
    const modal = new bootstrap.Modal(document.getElementById('modalSimular'));
    modal.show();
}

function simularCoordenadas() {
    const lat = parseFloat(document.getElementById('simLat').value);
    const lng = parseFloat(document.getElementById('simLng').value);
    if (isNaN(lat) || isNaN(lng)) {
        alert('Por favor, ingresa coordenadas válidas');
        return;
    }
    const modal = bootstrap.Modal.getInstance(document.getElementById('modalSimular'));
    if (modal) modal.hide();
    procesarClick(lat, lng);
}

// ==========================================
// 17. LIMPIAR UBICACIÓN
// ==========================================

function limpiarUbicacion() {
    if (marcadorUsuario) {
        map.removeLayer(marcadorUsuario);
        marcadorUsuario = null;
    }
    if (circuloPrecision) {
        map.removeLayer(circuloPrecision);
        circuloPrecision = null;
    }
    if (marcadorClick) {
        map.removeLayer(marcadorClick);
        marcadorClick = null;
    }
    document.getElementById('latitudActual').value = '';
    document.getElementById('longitudActual').value = '';
    document.getElementById('precisionActual').value = '';
    document.getElementById('estadoGeocerca').className = 'alert estado-esperando text-center';
    document.getElementById('estadoGeocerca').innerHTML = '<i class="fas fa-info-circle"></i> Sin ubicación';
    document.getElementById('geocercaEstado').className = 'badge bg-secondary';
    document.getElementById('geocercaEstado').textContent = 'Sin verificar';
    actualizarPopupPoligono(false);
    ultimaUbicacion = null;
    dentroGeocerca = false;
}

function limpiarHistorial() {
    if (historial.length === 0) {
        alert('No hay registros en el historial');
        return;
    }
    if (confirm('¿Estás seguro de que quieres eliminar todo el historial?')) {
        historial = [];
        actualizarHistorial();
        console.log('🗑️ Historial limpiado');
    }
}

// ==========================================
// 18. OTRAS FUNCIONES
// ==========================================

function centrarMapa() {
    if (ultimaUbicacion) {
        map.setView([ultimaUbicacion.lat, ultimaUbicacion.lng], 16);
    } else {
        const center = calcularCentroPoligono(POLIGONO_GEOCERCA.puntos);
        map.setView(center, 15);
    }
}

function actualizarInfoGeocerca() {
    document.getElementById('geocercaNombre').textContent = POLIGONO_GEOCERCA.nombre;
}

function descargarReporte() {
    if (historial.length === 0) {
        alert('No hay datos para exportar');
        return;
    }
    let csv = 'Fecha,Hora,Latitud,Longitud,Estado\n';
    historial.forEach(item => {
        const fecha = new Date(item.timestamp);
        csv += `${fecha.toLocaleDateString()},${fecha.toLocaleTimeString()},${item.lat.toFixed(6)},${item.lng.toFixed(6)},${item.dentro ? 'Dentro' : 'Fuera'}\n`;
    });
    const blob = new Blob([csv], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'reporte_geocerca.csv';
    a.click();
    window.URL.revokeObjectURL(url);
}

// ==========================================
// 19. FUNCIONES GLOBALES
// ==========================================

window.obtenerUbicacion = obtenerUbicacion;
window.simularUbicacion = simularUbicacion;
window.simularCoordenadas = simularCoordenadas;
window.limpiarUbicacion = limpiarUbicacion;
window.limpiarHistorial = limpiarHistorial;
window.centrarMapa = centrarMapa;
window.descargarReporte = descargarReporte;

window.probarPunto = function(lat, lng) {
    const dentro = puntoDentroPoligono(lat, lng, POLIGONO_GEOCERCA.puntos);
    console.log(`📍 Punto (${lat}, ${lng}) → ${dentro ? '✅ DENTRO' : '❌ FUERA'}`);
    mostrarPuntoClick(lat, lng, dentro);
    procesarUbicacion(lat, lng, 10);
    agregarHistorial(lat, lng, dentro);
    return dentro;
};

// ==========================================
// 20. INICIALIZAR
// ==========================================

document.addEventListener('DOMContentLoaded', function() {
    initMap();
    if ('Notification' in window && Notification.permission === 'default') {
        Notification.requestPermission();
    }
});
</script>