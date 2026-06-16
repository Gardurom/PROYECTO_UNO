<div class="row">
    <div class="col-md-12">
        <div class="stats-card">
            <h4><i class="fas fa-map-marked-alt"></i> Mapa de Coordenadas</h4>
            <p>Carga un archivo Excel con coordenadas para visualizarlas en el mapa</p>
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
                <h5><i class="fas fa-download"></i> Formato de ejemplo</h5>
                <p class="small text-muted">El archivo Excel debe contener las siguientes columnas:</p>
                <ul class="small">
                    <li><strong>lat</strong> o <strong>latitude</strong> - Latitud (ej: 19.4326)</li>
                    <li><strong>lng</strong> o <strong>longitude</strong> - Longitud (ej: -99.1332)</li>
                    <li><strong>title</strong> - Título del marcador</li>
                    <li><strong>description</strong> - Descripción (opcional)</li>
                </ul>
                <button class="btn btn-sm btn-outline-success" onclick="downloadExample()">
                    <i class="fas fa-download"></i> Descargar Ejemplo
                </button>
            </div>
            
            <div class="mt-4">
                <h5><i class="fas fa-chart-bar"></i> Estadísticas</h5>
                <div id="statsInfo">
                    <p><i class="fas fa-map-marker-alt"></i> Marcadores: <strong id="markerCount">0</strong></p>
                    <p><i class="fas fa-eye"></i> Marcadores visibles: <strong id="visibleCount">0</strong></p>
                </div>
                <button class="btn btn-sm btn-danger" onclick="clearAllMarkers()">
                    <i class="fas fa-trash"></i> Limpiar Marcadores
                </button>
            </div>
        </div>
    </div>
    
    <div class="col-md-8">
        <div class="stats-card">
            <div class="map-container">
                <div id="map"></div>
            </div>
        </div>
        
        <div class="stats-card mt-3">
            <h5><i class="fas fa-list"></i> Lista de Coordenadas</h5>
            <div class="coordinates-table" id="coordinatesList">
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

<script>
let currentCoordinates = [];

document.getElementById('excelFile').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        uploadExcel(file);
    }
});

// Drag and drop
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
    if (file && (file.type === 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' || 
                 file.type === 'application/vnd.ms-excel')) {
        uploadExcel(file);
    } else {
        alert('Por favor, sube un archivo Excel válido (.xlsx o .xls)');
    }
});

function uploadExcel(file) {
    const reader = new FileReader();
    reader.onload = function(e) {
        const data = new Uint8Array(e.target.result);
        const workbook = XLSX.read(data, {type: 'array'});
        const firstSheet = workbook.Sheets[workbook.SheetNames[0]];
        const rows = XLSX.utils.sheet_to_json(firstSheet);
        
        // Procesar coordenadas
        const coordinates = [];
        rows.forEach(row => {
            let lat = row.lat || row.latitude || row.Lat || row.Latitude;
            let lng = row.lng || row.longitude || row.Lng || row.Longitude;
            let title = row.title || row.Titulo || row.nombre || `Punto ${coordinates.length + 1}`;
            let description = row.description || row.Descripcion || '';
            
            if (lat && lng) {
                lat = parseFloat(lat);
                lng = parseFloat(lng);
                if (!isNaN(lat) && !isNaN(lng)) {
                    coordinates.push({lat, lng, title, description});
                }
            }
        });
        
        if (coordinates.length > 0) {
            currentCoordinates = coordinates;
            updateMapMarkers(coordinates);
            updateCoordinatesTable(coordinates);
            updateStats(coordinates.length);
        } else {
            alert('No se encontraron coordenadas válidas en el archivo');
        }
    };
    reader.readAsArrayBuffer(file);
}

function updateMapMarkers(coordinates) {
    clearMarkers();
    coordinates.forEach(coord => {
        addMarkerToMap(coord.lat, coord.lng, coord.title, coord.description);
    });
    updateMarkerCount();
    if (coordinates.length > 0) {
        fitBoundsToMarkers();
    }
}

function updateCoordinatesTable(coordinates) {
    const tbody = document.getElementById('coordinatesTableBody');
    tbody.innerHTML = '';
    
    coordinates.forEach((coord, index) => {
        const row = tbody.insertRow();
        row.innerHTML = `
            <td>${index + 1}</td>
            <td>${escapeHtml(coord.title)}</td>
            <td>${coord.lat}</td>
            <td>${coord.lng}</td>
            <td>
                <button class="btn btn-sm btn-primary" onclick="centerToMarker(${index})">
                    <i class="fas fa-crosshairs"></i>
                </button>
            </td>
        `;
    });
    
    if (coordinates.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" class="text-center">No hay coordenadas cargadas</td></tr>';
    }
}

function centerToMarker(index) {
    const coord = currentCoordinates[index];
    if (coord && map) {
        map.setView([coord.lat, coord.lng], 15);
    }
}

function clearAllMarkers() {
    if (confirm('¿Estás seguro de que quieres eliminar todos los marcadores?')) {
        clearMarkers();
        currentCoordinates = [];
        updateCoordinatesTable([]);
        updateStats(0);
    }
}

function updateStats(count) {
    document.getElementById('markerCount').textContent = count;
}

function updateMarkerCount() {
    document.getElementById('visibleCount').textContent = markers.length;
}

function downloadExample() {
    const exampleData = [
        { latitude: 19.4326, longitude: -99.1332, title: "Centro Histórico CDMX", description: "Zócalo de la Ciudad de México" },
        { latitude: 19.4270, longitude: -99.1676, title: "Monumento a la Revolución", description: "Monumento emblemático" },
        { latitude: 19.4102, longitude: -99.1295, title: "Palacio de Bellas Artes", description: "Teatro y museo" }
    ];
    
    const ws = XLSX.utils.json_to_sheet(exampleData);
    const wb = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(wb, ws, "Coordenadas");
    XLSX.writeFile(wb, "ejemplo_coordenadas.xlsx");
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
</script>