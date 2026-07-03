// js/qr_module.js - Versión completa

// ==========================================
// 1. CONFIGURACIÓN
// ==========================================

let html5QrCode = null;
let isScanning = false;
let escaneoEnProceso = false;
let ultimoCodigoEscaneado = '';
let codigosEscaneados = [];
let contadorEscaneos = 0;
let qrData = null;

console.log('📦 Módulo QR cargado');

// ==========================================
// 2. GENERAR QR
// ==========================================

async function generarQR() {
    console.log('🔄 Iniciando generación de QR...');
    
    const tipo = document.getElementById('tipoQR');
    const referenciaId = document.getElementById('referenciaId');
    const datosExtra = document.getElementById('datosExtra');
    const resultadoDiv = document.getElementById('qrResultado');
    
    if (!tipo.value) {
        Swal.fire({
            icon: 'warning',
            title: 'Campos incompletos',
            text: 'Por favor, selecciona un tipo de QR'
        });
        return;
    }
    
    resultadoDiv.style.display = 'block';
    document.getElementById('qrImagenContainer').innerHTML = `
        <div class="text-center p-4">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Cargando...</span>
            </div>
            <p class="mt-2">Generando código QR...</p>
        </div>
    `;
    document.getElementById('qrCodigoGenerado').textContent = 'Generando...';
    document.getElementById('qrInfoAdicional').innerHTML = '';
    
    const formData = new FormData();
    formData.append('tipo', tipo.value);
    formData.append('referencia_id', referenciaId.value || '0');
    formData.append('datos_extra', datosExtra.value || '');
    
    console.log('📤 Enviando datos:', Object.fromEntries(formData));
    
    try {
        const response = await fetch('ajax/generar_qr.php', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        
        const textResponse = await response.text();
        console.log('📥 Respuesta del servidor:', textResponse);
        
        let data;
        try {
            data = JSON.parse(textResponse);
        } catch (e) {
            console.error('❌ Error al parsear JSON:', e);
            throw new Error('El servidor devolvió una respuesta inválida: ' + textResponse.substring(0, 200));
        }
        
        if (data.success) {
            document.getElementById('qrImagenContainer').innerHTML = `
                <div class="text-center">
                    <img id="qrImagen" src="${data.qr_image}" alt="Código QR" style="max-width: 200px; border: 2px solid #ddd; border-radius: 10px; padding: 10px;" 
                         onerror="this.onerror=null; this.src='data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%22200%22 height=%22200%22%3E%3Crect width=%22200%22 height=%22200%22 fill=%22%23f0f0f0%22/%3E%3Ctext x=%2250%22 y=%22100%22 font-size=%2216%22 fill=%22%23999%22%3EQR: ${data.codigo}%3C/text%3E%3C/svg%3E';">
                    <br>
                    <small class="text-muted">Código: ${data.codigo}</small>
                </div>
            `;
            document.getElementById('qrCodigoGenerado').textContent = data.codigo;
            
            window.qrData = {
                codigo: data.codigo,
                imagen: data.qr_image,
                url: data.url || ''
            };
            
            document.getElementById('qrInfoAdicional').innerHTML = `
                <div class="mt-2 small text-start">
                    <p><strong>Tipo:</strong> ${data.tipo || tipo.value}</p>
                    ${data.url ? `<p><strong>URL:</strong> <a href="${data.url}" target="_blank">${data.url}</a></p>` : ''}
                    <p><strong>Fecha:</strong> ${new Date().toLocaleString()}</p>
                </div>
            `;
            
            Swal.fire({
                icon: 'success',
                title: '✅ QR Generado',
                text: 'Código: ' + data.codigo,
                timer: 3000,
                timerProgressBar: true
            });
            
            cargarListadoQR();
            
        } else {
            Swal.fire({
                icon: 'error',
                title: '❌ Error al generar QR',
                text: data.error || 'Error desconocido'
            });
            
            document.getElementById('qrImagenContainer').innerHTML = `
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle"></i>
                    ${data.error || 'Error al generar el código QR'}
                </div>
            `;
        }
        
    } catch (error) {
        console.error('❌ Error en la solicitud:', error);
        Swal.fire({
            icon: 'error',
            title: '❌ Error de conexión',
            text: error.message || 'No se pudo conectar con el servidor'
        });
        document.getElementById('qrImagenContainer').innerHTML = `
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle"></i>
                Error al conectar con el servidor<br>
                <small>${error.message}</small>
            </div>
        `;
    }
}

function limpiarFormularioQR() {
    document.getElementById('tipoQR').value = '';
    document.getElementById('referenciaId').value = '';
    document.getElementById('datosExtra').value = '';
    document.getElementById('qrResultado').style.display = 'none';
    window.qrData = null;
}

function probarGeneracionQR() {
    document.getElementById('tipoQR').value = 'general';
    document.getElementById('referenciaId').value = '1';
    document.getElementById('datosExtra').value = '{"test": "Prueba de generación"}';
    generarQR();
}

// ==========================================
// 3. ESCANEAR QR
// ==========================================

function iniciarEscanner() {
    if (isScanning) {
        Swal.fire({
            icon: 'info',
            title: 'Escáner ya activo',
            text: 'El escáner ya está en funcionamiento'
        });
        return;
    }
    
    const readerElement = document.getElementById('reader');
    const estadoCamara = document.getElementById('estadoCamara');
    
    if (!readerElement) {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'No se encontró el elemento del escáner'
        });
        return;
    }
    
    readerElement.innerHTML = '';
    
    try {
        html5QrCode = new Html5Qrcode("reader");
        
        const config = {
            fps: 30,
            qrbox: { width: 280, height: 280 },
            aspectRatio: 1.0
        };
        
        estadoCamara.innerHTML = `
            <i class="fas fa-spinner fa-spin" style="color: #ffc107;"></i>
            Solicitando acceso a la cámara...
        `;
        
        html5QrCode.start(
            { facingMode: "environment" },
            config,
            onScanSuccess,
            onScanError
        ).then(() => {
            isScanning = true;
            contadorEscaneos = 0;
            codigosEscaneados = [];
            estadoCamara.innerHTML = `
                <i class="fas fa-circle" style="color: #28a745;"></i>
                Cámara activa. Escaneando códigos QR...
                <br><small>QR detectados: <span id="contadorEscaneos">0</span></small>
            `;
            document.getElementById('resultadoEscaneo').style.display = 'none';
            
            Swal.fire({
                icon: 'success',
                title: '📷 Escáner iniciado',
                text: 'Coloca códigos QR frente a la cámara. ¡Puedes escanear múltiples códigos!',
                timer: 2000,
                timerProgressBar: true
            });
            
            crearHistorialEnVivo();
            
        }).catch((error) => {
            console.error('❌ Error al iniciar escáner:', error);
            let mensaje = 'No se pudo acceder a la cámara. ';
            if (error.name === 'NotAllowedError' || error.name === 'PermissionDeniedError') {
                mensaje += 'Permiso denegado. Verifica los permisos.';
            } else if (error.name === 'NotFoundError') {
                mensaje += 'No se encontró una cámara.';
            } else {
                mensaje += error.message || 'Error desconocido.';
            }
            estadoCamara.innerHTML = `
                <i class="fas fa-circle" style="color: #dc3545;"></i>
                Error: ${mensaje}
            `;
            readerElement.innerHTML = `
                <div class="text-center text-muted p-4">
                    <i class="fas fa-exclamation-triangle fa-3x mb-3" style="color: #dc3545;"></i>
                    <p>${mensaje}</p>
                    <button class="btn btn-primary btn-sm" onclick="reiniciarEscanner()">
                        <i class="fas fa-sync"></i> Reintentar
                    </button>
                </div>
            `;
            Swal.fire({
                icon: 'error',
                title: 'Error al iniciar escáner',
                text: mensaje
            });
            isScanning = false;
            html5QrCode = null;
        });
        
    } catch (error) {
        console.error('❌ Error al crear escáner:', error);
        estadoCamara.innerHTML = `
            <i class="fas fa-circle" style="color: #dc3545;"></i>
            Error: ${error.message}
        `;
        Swal.fire({
            icon: 'error',
            title: 'Error al crear escáner',
            text: error.message || 'Error desconocido'
        });
        isScanning = false;
        html5QrCode = null;
    }
}

function detenerEscanner() {
    if (html5QrCode && isScanning) {
        html5QrCode.stop().then(() => {
            isScanning = false;
            escaneoEnProceso = false;
            document.getElementById('estadoCamara').innerHTML = `
                <i class="fas fa-circle" style="color: #6c757d;"></i>
                Escáner detenido. Total escaneos: <strong>${codigosEscaneados.length}</strong>
            `;
            const readerElement = document.getElementById('reader');
            if (readerElement) {
                readerElement.innerHTML = `
                    <div class="text-center text-muted p-4">
                        <i class="fas fa-camera fa-3x mb-3"></i>
                        <p>Escáner detenido.</p>
                        <p>Total de códigos escaneados: <strong>${codigosEscaneados.length}</strong></p>
                        <button class="btn btn-primary btn-sm mt-2" onclick="iniciarEscanner()">
                            <i class="fas fa-play"></i> Reanudar
                        </button>
                        <button class="btn btn-danger btn-sm mt-2" onclick="limpiarHistorialLive()">
                            <i class="fas fa-trash"></i> Limpiar Historial
                        </button>
                    </div>
                `;
            }
            console.log(`📷 Escáner detenido. ${codigosEscaneados.length} códigos escaneados.`);
        }).catch(err => {
            console.error('Error al detener escáner:', err);
        });
    }
}

function reiniciarEscanner() {
    console.log('🔄 Reiniciando escáner...');
    detenerEscanner();
    setTimeout(() => {
        iniciarEscanner();
    }, 1000);
}

// ==========================================
// 4. PROCESAR CÓDIGO ESCANEADO
// ==========================================

function onScanSuccess(decodedText, decodedResult) {
    if (escaneoEnProceso) return;
    if (ultimoCodigoEscaneado === decodedText) {
        console.log('⏭️ Código duplicado, ignorando...');
        return;
    }
    
    escaneoEnProceso = true;
    ultimoCodigoEscaneado = decodedText;
    contadorEscaneos++;
    
    console.log(`📱 CÓDIGO QR #${contadorEscaneos}: ${decodedText}`);
    
    const contadorElement = document.getElementById('contadorEscaneos');
    if (contadorElement) {
        contadorElement.textContent = contadorEscaneos;
    }
    
    document.getElementById('resultadoEscaneo').style.display = 'block';
    document.getElementById('resultadoEscaneo').className = 'alert alert-info';
    document.getElementById('resultadoEscaneo').innerHTML = `
        <strong>📱 Escaneando código #${contadorEscaneos}...</strong>
        <div id="contenidoQR" class="mt-2">
            <div class="text-center">
                <div class="spinner-border spinner-border-sm text-primary" role="status">
                    <span class="visually-hidden">Cargando...</span>
                </div>
                <p class="mt-2 small">Procesando: <strong>${decodedText}</strong></p>
            </div>
        </div>
    `;
    
    procesarCodigoEscaneado(decodedText);
}

function onScanError(errorMessage) {
    // No hacer nada, solo continuar escaneando
}

// ==========================================
// 5. PROCESAR CÓDIGO ESCANEADO - CONSULTA SERVIDOR
// ==========================================

async function procesarCodigoEscaneado(codigoLeido) {
    let codigoLimpio = codigoLeido.trim();
    
    if (codigoLimpio.includes('codigo=') || codigoLimpio.includes('qr=')) {
        try {
            const url = new URL(codigoLimpio);
            const params = new URLSearchParams(url.search);
            codigoLimpio = params.get('codigo') || params.get('qr') || codigoLimpio;
        } catch (e) {}
    }
    
    codigoLimpio = codigoLimpio.replace(/[^A-Za-z0-9-]/g, '');
    
    console.log(`🔍 Buscando código #${contadorEscaneos}: ${codigoLimpio}`);
    
    try {
        const response = await fetch(`ajax/leer_qr.php?codigo=${encodeURIComponent(codigoLimpio)}`);
        const data = await response.json();
        
        console.log(`📦 Respuesta #${contadorEscaneos}:`, data);
        
        agregarAlHistorialLive(codigoLeido, data);
        
        if (data.success) {
            document.getElementById('resultadoEscaneo').className = 'alert alert-success';
            document.getElementById('contenidoQR').innerHTML = `
                <div class="small">
                    <strong>✅ Código Válido</strong>
                    <br>
                    <span class="badge bg-success">${data.codigo}</span>
                    <span class="badge bg-info">${data.tipo}</span>
                    ${data.datos ? `<br><span class="text-muted">${data.datos.nombre || ''}</span>` : ''}
                </div>
            `;
            registrarEscaneo(data.codigo);
        } else {
            document.getElementById('resultadoEscaneo').className = 'alert alert-danger';
            document.getElementById('contenidoQR').innerHTML = `
                <div class="small">
                    <strong>❌ Código No Válido</strong>
                    <br>
                    <span class="badge bg-danger">${codigoLimpio}</span>
                    <br>
                    <span class="text-muted">${data.error || 'No registrado en el sistema'}</span>
                </div>
            `;
        }
        
        setTimeout(() => {
            document.getElementById('resultadoEscaneo').style.display = 'none';
        }, 3000);
        
    } catch (error) {
        console.error('❌ Error al procesar código:', error);
        document.getElementById('resultadoEscaneo').className = 'alert alert-danger';
        document.getElementById('contenidoQR').innerHTML = `
            <div class="small">
                <strong>❌ Error</strong>
                <br>
                <span class="text-muted">${error.message}</span>
            </div>
        `;
        agregarAlHistorialLive(codigoLeido, { success: false, error: error.message });
        setTimeout(() => {
            document.getElementById('resultadoEscaneo').style.display = 'none';
        }, 3000);
    }
    
    escaneoEnProceso = false;
    document.getElementById('totalEscaneosLive').textContent = codigosEscaneados.length;
}

// ==========================================
// 6. HISTORIAL EN VIVO
// ==========================================

function crearHistorialEnVivo() {
    let historialContainer = document.getElementById('historialEnVivo');
    if (!historialContainer) {
        const readerElement = document.getElementById('reader');
        if (readerElement) {
            historialContainer = document.createElement('div');
            historialContainer.id = 'historialEnVivo';
            historialContainer.className = 'mt-3';
            historialContainer.innerHTML = `
                <div class="card">
                    <div class="card-header">
                        <h6><i class="fas fa-list"></i> Historial de Escaneos en Vivo</h6>
                        <span class="badge bg-primary float-end" id="totalEscaneosLive">0</span>
                    </div>
                    <div class="card-body" id="historialLiveBody" style="max-height: 300px; overflow-y: auto;">
                        <div class="text-center text-muted">
                            <i class="fas fa-info-circle"></i> Esperando primer escaneo...
                        </div>
                    </div>
                </div>
            `;
            readerElement.parentNode.insertBefore(historialContainer, readerElement.nextSibling);
        }
    }
    
    const body = document.getElementById('historialLiveBody');
    if (body) {
        body.innerHTML = `
            <div class="text-center text-muted">
                <i class="fas fa-info-circle"></i> Esperando primer escaneo...
            </div>
        `;
    }
    document.getElementById('totalEscaneosLive').textContent = '0';
}

function agregarAlHistorialLive(codigo, data) {
    const body = document.getElementById('historialLiveBody');
    if (!body) return;
    
    if (codigosEscaneados.length === 0) {
        body.innerHTML = '';
    }
    
    const timestamp = new Date().toLocaleTimeString();
    const esValido = data && data.success;
    
    const item = document.createElement('div');
    item.className = 'list-group-item list-group-item-action d-flex justify-content-between align-items-center';
    item.style.borderLeft = `4px solid ${esValido ? '#28a745' : '#dc3545'}`;
    item.style.marginBottom = '2px';
    item.style.padding = '6px 10px';
    item.style.fontSize = '13px';
    
    let contenido = `
        <div>
            <span class="badge ${esValido ? 'bg-success' : 'bg-danger'}">
                ${esValido ? '✅' : '❌'}
            </span>
            <strong>${codigo.substring(0, 20)}${codigo.length > 20 ? '...' : ''}</strong>
            <br>
            <small class="text-muted">
                ${esValido ? `${data.tipo || 'Desconocido'} | ${data.datos?.nombre || 'Sin datos'}` : 'No registrado'}
            </small>
        </div>
        <div class="text-end">
            <small class="text-muted">${timestamp}</small>
            <br>
            <small class="text-muted">#${codigosEscaneados.length + 1}</small>
        </div>
    `;
    
    item.innerHTML = contenido;
    body.appendChild(item);
    
    codigosEscaneados.push({
        codigo: codigo,
        data: data,
        timestamp: timestamp,
        valido: esValido
    });
    
    body.scrollTop = body.scrollHeight;
    
    if (codigosEscaneados.length > 50) {
        codigosEscaneados.shift();
        if (body.firstChild) {
            body.removeChild(body.firstChild);
        }
    }
    
    document.getElementById('totalEscaneosLive').textContent = codigosEscaneados.length;
}

function limpiarHistorialLive() {
    if (codigosEscaneados.length === 0) {
        Swal.fire({
            icon: 'info',
            title: 'Sin registros',
            text: 'No hay códigos en el historial'
        });
        return;
    }
    
    Swal.fire({
        title: '¿Limpiar historial?',
        text: `Se eliminarán ${codigosEscaneados.length} registros`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Sí, limpiar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            codigosEscaneados = [];
            contadorEscaneos = 0;
            ultimoCodigoEscaneado = '';
            
            const body = document.getElementById('historialLiveBody');
            if (body) {
                body.innerHTML = `
                    <div class="text-center text-muted">
                        <i class="fas fa-info-circle"></i> Historial limpiado
                    </div>
                `;
            }
            document.getElementById('totalEscaneosLive').textContent = '0';
            document.getElementById('contadorEscaneos').textContent = '0';
            
            Swal.fire({
                icon: 'success',
                title: 'Historial limpiado',
                text: 'Todos los registros han sido eliminados',
                timer: 1500
            });
        }
    });
}

// ==========================================
// 7. REGISTRAR ESCANEO
// ==========================================

async function registrarEscaneo(codigo) {
    try {
        let ubicacion = null;
        if (navigator.geolocation) {
            try {
                const pos = await new Promise((resolve, reject) => {
                    navigator.geolocation.getCurrentPosition(resolve, reject, {
                        timeout: 5000,
                        enableHighAccuracy: true
                    });
                });
                ubicacion = {
                    lat: pos.coords.latitude,
                    lng: pos.coords.longitude
                };
            } catch (e) {}
        }
        
        const response = await fetch('ajax/registrar_qr.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ codigo, ubicacion })
        });
        
        const data = await response.json();
        if (data.success) {
            console.log('✅ Escaneo registrado correctamente');
            cargarHistorial();
        }
    } catch (error) {
        console.error('❌ Error al registrar escaneo:', error);
    }
}

// ==========================================
// 8. EXPORTAR REPORTE
// ==========================================

function exportarReporteEscaneos() {
    if (codigosEscaneados.length === 0) {
        Swal.fire({
            icon: 'info',
            title: 'Sin datos',
            text: 'No hay códigos escaneados para exportar'
        });
        return;
    }
    
    let csv = 'N°,Código,Válido,Tipo,Fecha,Información\n';
    codigosEscaneados.forEach((item, index) => {
        const info = item.data && item.data.success ? 
            `${item.data.tipo || ''} ${item.data.datos?.nombre || ''}`.trim() : 
            'No registrado';
        csv += `${index + 1},"${item.codigo}",${item.valido ? 'Sí' : 'No'},${item.data?.tipo || ''},${item.timestamp},"${info}"\n`;
    });
    
    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = `reporte_escaneos_${new Date().toISOString().slice(0,10)}.csv`;
    link.click();
    URL.revokeObjectURL(link.href);
}

// ==========================================
// 9. FUNCIONES DE QR
// ==========================================

function descargarQR() {
    if (!window.qrData) return;
    const link = document.createElement('a');
    link.download = `qr_${window.qrData.codigo}.png`;
    link.href = window.qrData.imagen;
    link.click();
}

function copiarQR() {
    if (!window.qrData) return;
    navigator.clipboard.writeText(window.qrData.codigo).then(() => {
        Swal.fire({
            icon: 'success',
            title: 'Copiado',
            text: 'Código QR copiado al portapapeles'
        });
    }).catch(() => {
        const textarea = document.createElement('textarea');
        textarea.value = window.qrData.codigo;
        document.body.appendChild(textarea);
        textarea.select();
        document.execCommand('copy');
        document.body.removeChild(textarea);
        Swal.fire({
            icon: 'success',
            title: 'Copiado',
            text: 'Código QR copiado al portapapeles'
        });
    });
}

// ==========================================
// 10. CARGAR LISTADOS
// ==========================================

async function cargarListadoQR() {
    try {
        const response = await fetch('ajax/listar_qr.php');
        const data = await response.json();
        const tbody = document.getElementById('tablaQRBody');
        tbody.innerHTML = '';
        
        if (data.length === 0) {
            tbody.innerHTML = '<tr><td colspan="8" class="text-center">No hay códigos QR generados</td></tr>';
            return;
        }
        
        data.forEach(qr => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>${qr.id}</td>
                <td><code>${qr.codigo}</code></td>
                <td><span class="badge bg-info">${qr.tipo}</span></td>
                <td>${qr.referencia_id || 'N/A'}</td>
                <td><span class="badge ${qr.estado == 1 ? 'bg-success' : 'bg-danger'}">${qr.estado == 1 ? 'Activo' : 'Inactivo'}</span></td>
                <td>${new Date(qr.fecha_generacion).toLocaleDateString()}</td>
                <td><span class="badge bg-secondary">${qr.total_escaneos || 0}</span></td>
                <td>
                    <button class="btn btn-sm btn-danger" onclick="eliminarQR(${qr.id})">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            `;
            tbody.appendChild(tr);
        });
    } catch (error) {
        console.error('Error al cargar listado QR:', error);
    }
}

async function eliminarQR(id) {
    if (!confirm('¿Estás seguro de eliminar este código QR?')) return;
    try {
        const response = await fetch('ajax/eliminar_qr.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: id })
        });
        const data = await response.json();
        if (data.success) {
            Swal.fire({
                icon: 'success',
                title: 'Eliminado',
                text: 'El código QR ha sido eliminado'
            });
            cargarListadoQR();
        }
    } catch (error) {
        console.error('Error al eliminar QR:', error);
    }
}

async function cargarHistorial() {
    try {
        const response = await fetch('ajax/historial_escaneos.php');
        const data = await response.json();
        const tbody = document.getElementById('tablaHistorialBody');
        tbody.innerHTML = '';
        
        if (data.length === 0) {
            tbody.innerHTML = '<tr><td colspan="6" class="text-center">No hay escaneos registrados</td></tr>';
            return;
        }
        
        data.forEach(scan => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>${scan.id}</td>
                <td><code>${scan.codigo}</code></td>
                <td>${scan.usuario || 'Anónimo'}</td>
                <td>${scan.ip || 'N/A'}</td>
                <td>${new Date(scan.fecha_escaneo).toLocaleString()}</td>
                <td>${scan.latitud && scan.longitud ? `${scan.latitud}, ${scan.longitud}` : 'Sin ubicación'}</td>
            `;
            tbody.appendChild(tr);
        });
    } catch (error) {
        console.error('Error al cargar historial:', error);
    }
}

// ==========================================
// 11. INICIALIZAR
// ==========================================

document.addEventListener('DOMContentLoaded', function() {
    console.log('📦 DOM cargado, inicializando módulo QR...');
    cargarListadoQR();
    cargarHistorial();
    
    document.querySelectorAll('button[data-bs-toggle="tab"]').forEach(button => {
        button.addEventListener('click', function() {
            if (this.id !== 'tab-escanear' && isScanning) {
                detenerEscanner();
            }
        });
    });
});

// Funciones globales
window.generarQR = generarQR;
window.limpiarFormularioQR = limpiarFormularioQR;
window.probarGeneracionQR = probarGeneracionQR;
window.iniciarEscanner = iniciarEscanner;
window.detenerEscanner = detenerEscanner;
window.reiniciarEscanner = reiniciarEscanner;
window.limpiarHistorialLive = limpiarHistorialLive;
window.exportarReporteEscaneos = exportarReporteEscaneos;
window.descargarQR = descargarQR;
window.copiarQR = copiarQR;
window.eliminarQR = eliminarQR;