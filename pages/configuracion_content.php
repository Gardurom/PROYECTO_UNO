<div class="card">
    <div class="card-header">
        <h5><i class="fas fa-cog"></i> Configuración del Sistema</h5>
    </div>
    <div class="card-body">
        <form>
            <h6>Configuración del Mapa</h6>
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Centro del Mapa (Latitud)</label>
                        <input type="text" class="form-control" id="map_lat" value="19.4326">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Centro del Mapa (Longitud)</label>
                        <input type="text" class="form-control" id="map_lng" value="-99.1332">
                    </div>
                </div>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Nivel de Zoom</label>
                <input type="number" class="form-control" id="map_zoom" value="10">
            </div>
            
            <hr>
            <h6>Configuración de Sesión</h6>
            <div class="mb-3">
                <label class="form-label">Tiempo de sesión (minutos)</label>
                <input type="number" class="form-control" value="120" readonly>
                <small class="text-muted">Tiempo actual: 2 horas</small>
            </div>
            
            <hr>
            <h6>Configuración de Seguridad</h6>
            <div class="mb-3">
                <label class="form-label">Intentos máximos de login</label>
                <input type="number" class="form-control" value="5" readonly>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Tiempo de bloqueo (minutos)</label>
                <input type="number" class="form-control" value="15" readonly>
            </div>
            
            <button type="button" class="btn btn-custom" onclick="alert('Configuración guardada')">
                Guardar Configuración
            </button>
        </form>
    </div>
</div>