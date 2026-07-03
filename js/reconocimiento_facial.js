// js/reconocimiento_facial.js

// ==========================================
// 1. CONFIGURACIÓN
// ==========================================

// URL donde se encuentran los modelos (ajusta según tu estructura)
const MODEL_URL = './models/';

// Variables globales
let video = null;
let canvas = null;
let context = null;
let stream = null;
let detectionInterval = null;
let rostrosRegistrados = [];
let descriptorActual = null;
let fullFaceDescriptions = null;
let isCameraStarted = false;

// ==========================================
// 2. INICIALIZACIÓN
// ==========================================

async function init() {
    const estadoDiv = document.getElementById('estadoFacial');
    if (!estadoDiv) return;
    
    estadoDiv.className = 'alert estado-esperando text-center';
    estadoDiv.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Cargando modelos de reconocimiento facial...';

    try {
        // Verificar que face-api esté disponible
        if (typeof faceapi === 'undefined') {
            throw new Error('face-api.js no está cargado. Verifica la conexión a internet.');
        }

        // Cargar modelos desde la carpeta local
        console.log('📥 Cargando modelos desde:', MODEL_URL);
        
        await Promise.all([
            faceapi.nets.ssdMobilenetv1.loadFromUri(MODEL_URL),
            faceapi.nets.faceLandmark68Net.loadFromUri(MODEL_URL),
            faceapi.nets.faceRecognitionNet.loadFromUri(MODEL_URL)
        ]);

        console.log('✅ Modelos cargados correctamente');

        // Cargar rostros registrados desde localStorage
        cargarRostrosGuardados();

        estadoDiv.className = 'alert alert-success text-center';
        estadoDiv.innerHTML = '<i class="fas fa-check-circle"></i> Modelos listos. <strong>Inicia la cámara</strong> para comenzar.';

        // Habilitar botones
        document.querySelectorAll('.btn-primary, .btn-success, .btn-info').forEach(btn => {
            btn.disabled = false;
        });

    } catch (error) {
        console.error('❌ Error al cargar modelos:', error);
        estadoDiv.className = 'alert estado-fuera text-center';
        estadoDiv.innerHTML = `
            <i class="fas fa-exclamation-triangle"></i> 
            Error al cargar los modelos.<br>
            <small class="text-muted">${error.message}</small><br>
            <small>Verifica que los archivos existan en la carpeta "models".</small>
        `;
    }
}

// ==========================================
// 3. CÁMARA
// ==========================================

async function iniciarCamara() {
    const estadoDiv = document.getElementById('estadoFacial');
    if (!estadoDiv) return;

    // Si ya está iniciada, no hacer nada
    if (isCameraStarted) {
        estadoDiv.className = 'alert alert-warning text-center';
        estadoDiv.innerHTML = '<i class="fas fa-info-circle"></i> La cámara ya está activa.';
        return;
    }

    try {
        // Solicitar acceso a la cámara
        stream = await navigator.mediaDevices.getUserMedia({
            video: {
                width: { ideal: 640 },
                height: { ideal: 480 },
                facingMode: 'user'
            },
            audio: false
        });

        video = document.getElementById('videoFacial');
        canvas = document.getElementById('canvasFacial');
        
        if (!video || !canvas) {
            throw new Error('No se encontraron los elementos de video o canvas');
        }

        // Configurar video
        video.srcObject = stream;
        video.setAttribute('playsinline', true);

        // Configurar canvas
        context = canvas.getContext('2d');

        // Esperar a que el video esté listo
        await new Promise((resolve) => {
            video.onloadedmetadata = () => {
                canvas.width = video.videoWidth || 640;
                canvas.height = video.videoHeight || 480;
                resolve();
            };
        });

        await video.play();

        isCameraStarted = true;

        estadoDiv.className = 'alert alert-success text-center';
        estadoDiv.innerHTML = '<i class="fas fa-check-circle"></i> Cámara activa. Detectando rostros...';

        // Iniciar detección
        iniciarDeteccion();

        console.log('✅ Cámara iniciada correctamente');

    } catch (error) {
        console.error('❌ Error al iniciar cámara:', error);
        
        let mensaje = 'No se pudo acceder a la cámara. ';
        if (error.name === 'NotAllowedError' || error.name === 'PermissionDeniedError') {
            mensaje += 'Permiso denegado. Verifica los permisos de la cámara en tu navegador.';
        } else if (error.name === 'NotFoundError' || error.name === 'DevicesNotFoundError') {
            mensaje += 'No se encontró una cámara en tu dispositivo.';
        } else {
            mensaje += error.message;
        }

        estadoDiv.className = 'alert estado-fuera text-center';
        estadoDiv.innerHTML = `<i class="fas fa-exclamation-triangle"></i> ${mensaje}`;
        
        // Limpiar en caso de error
        if (stream) {
            stream.getTracks().forEach(track => track.stop());
            stream = null;
        }
        isCameraStarted = false;
    }
}

function cerrarCamara() {
    // Detener detección
    if (detectionInterval) {
        clearInterval(detectionInterval);
        detectionInterval = null;
    }

    // Detener stream
    if (stream) {
        stream.getTracks().forEach(track => track.stop());
        stream = null;
    }

    // Limpiar video
    if (video) {
        video.srcObject = null;
        video.pause();
    }

    // Limpiar canvas
    if (context && canvas) {
        context.clearRect(0, 0, canvas.width, canvas.height);
        canvas.width = 0;
        canvas.height = 0;
    }

    isCameraStarted = false;

    const estadoDiv = document.getElementById('estadoFacial');
    if (estadoDiv) {
        estadoDiv.className = 'alert alert-secondary text-center';
        estadoDiv.innerHTML = '<i class="fas fa-info-circle"></i> Cámara detenida. Haz clic en "Iniciar Cámara" para reactivarla.';
    }

    document.getElementById('infoRostro').style.display = 'none';
    console.log('📷 Cámara detenida');
}

// ==========================================
// 4. DETECCIÓN DE ROSTROS
// ==========================================

function iniciarDeteccion() {
    if (detectionInterval) {
        clearInterval(detectionInterval);
    }

    detectionInterval = setInterval(async () => {
        if (!video || video.paused || video.ended || !isCameraStarted) {
            return;
        }

        try {
            // Detectar rostros
            const detections = await faceapi.detectAllFaces(video)
                .withFaceLandmarks()
                .withFaceDescriptors();

            // Limpiar canvas y dibujar video
            context.clearRect(0, 0, canvas.width, canvas.height);
            context.drawImage(video, 0, 0, canvas.width, canvas.height);

            if (detections && detections.length > 0) {
                fullFaceDescriptions = detections;

                // Dibujar detecciones
                faceapi.draw.drawDetections(canvas, detections);
                faceapi.draw.drawFaceLandmarks(canvas, detections);

                // Procesar el primer rostro detectado
                const detection = detections[0];
                const descriptor = detection.descriptor;

                // Guardar descriptor para registro
                descriptorActual = descriptor;

                // Verificar si es un rostro conocido
                const conocido = buscarRostroConocido(descriptor);

                if (conocido) {
                    // Rostro reconocido
                    document.getElementById('infoRostro').style.display = 'block';
                    document.getElementById('detallesRostro').innerHTML = `
                        <div class="alert alert-success">
                            <strong>✅ Rostro reconocido</strong><br>
                            <strong>Nombre:</strong> ${conocido.nombre}<br>
                            <strong>ID:</strong> ${conocido.id}<br>
                            <small class="text-muted">Registrado: ${new Date(conocido.fecha).toLocaleString()}</small>
                        </div>
                    `;
                    document.getElementById('estadoFacial').className = 'alert alert-success text-center';
                    document.getElementById('estadoFacial').innerHTML = `<i class="fas fa-check-circle"></i> ✅ ${conocido.nombre} identificado`;
                } else {
                    // Rostro desconocido
                    document.getElementById('infoRostro').style.display = 'block';
                    document.getElementById('detallesRostro').innerHTML = `
                        <div class="alert alert-warning">
                            <strong>⚠️ Rostro no registrado</strong><br>
                            Haz clic en <strong>"Registrar Rostro"</strong> para guardarlo.
                        </div>
                    `;
                    document.getElementById('estadoFacial').className = 'alert estado-esperando text-center';
                    document.getElementById('estadoFacial').innerHTML = '<i class="fas fa-info-circle"></i> Rostro desconocido';
                }

                // Mostrar información adicional
                const infoExtra = document.getElementById('infoExtra');
                if (infoExtra) {
                    infoExtra.innerHTML = `
                        <small class="text-muted">
                            Confianza: ${detection.detection.score.toFixed(4)}<br>
                            Rostros detectados: ${detections.length}
                        </small>
                    `;
                }

            } else {
                // No hay rostros
                document.getElementById('infoRostro').style.display = 'none';
                document.getElementById('estadoFacial').className = 'alert estado-esperando text-center';
                document.getElementById('estadoFacial').innerHTML = '<i class="fas fa-info-circle"></i> No se detectan rostros';
                descriptorActual = null;
            }

        } catch (error) {
            console.error('Error en detección:', error);
        }
    }, 100); // Cada 100ms (10 FPS)
}

// ==========================================
// 5. REGISTRO DE ROSTROS
// ==========================================

function capturarRostro() {
    if (!descriptorActual) {
        alert('⚠️ No se ha detectado ningún rostro. Asegúrate de estar frente a la cámara.');
        return;
    }

    const modal = new bootstrap.Modal(document.getElementById('modalRegistrar'));
    modal.show();
}

function guardarRostro() {
    const nombre = document.getElementById('nombreRostro').value.trim();
    const id = document.getElementById('idRostro').value.trim();

    if (!nombre) {
        alert('⚠️ Por favor, ingresa el nombre de la persona.');
        document.getElementById('nombreRostro').focus();
        return;
    }

    if (!id) {
        alert('⚠️ Por favor, ingresa un identificador único.');
        document.getElementById('idRostro').focus();
        return;
    }

    // Verificar que el ID no exista
    if (rostrosRegistrados.some(r => r.id === id)) {
        alert('⚠️ El ID "' + id + '" ya está registrado. Usa otro identificador.');
        return;
    }

    // Guardar rostro
    const nuevoRostro = {
        id: id,
        nombre: nombre,
        descriptor: Array.from(descriptorActual),
        fecha: new Date().toISOString()
    };

    rostrosRegistrados.push(nuevoRostro);

    // Guardar en localStorage
    guardarRostros();

    // Actualizar lista
    actualizarListaRostros();

    // Cerrar modal
    const modal = bootstrap.Modal.getInstance(document.getElementById('modalRegistrar'));
    if (modal) modal.hide();

    alert(`✅ Rostro de "${nombre}" registrado correctamente`);

    // Limpiar campos
    document.getElementById('nombreRostro').value = '';
    document.getElementById('idRostro').value = '';

    // Actualizar estado
    document.getElementById('estadoFacial').className = 'alert alert-success text-center';
    document.getElementById('estadoFacial').innerHTML = `<i class="fas fa-check-circle"></i> ✅ ${nombre} registrado correctamente`;
}

// ==========================================
// 6. RECONOCIMIENTO DE ROSTROS
// ==========================================

function buscarRostroConocido(descriptor) {
    const umbral = 0.6; // Distancia máxima para considerar coincidencia
    let mejorCoincidencia = null;
    let mejorDistancia = Infinity;

    for (const rostro of rostrosRegistrados) {
        const rostroDescriptor = new Float32Array(rostro.descriptor);
        const distancia = faceapi.euclideanDistance(descriptor, rostroDescriptor);

        if (distancia < umbral && distancia < mejorDistancia) {
            mejorDistancia = distancia;
            mejorCoincidencia = rostro;
        }
    }

    return mejorCoincidencia;
}

function reconocerRostro() {
    if (!descriptorActual) {
        alert('⚠️ No se ha detectado ningún rostro. Asegúrate de estar frente a la cámara.');
        return;
    }

    const conocido = buscarRostroConocido(descriptorActual);

    if (conocido) {
        alert(`✅ Rostro reconocido!\n\nNombre: ${conocido.nombre}\nID: ${conocido.id}\nFecha de registro: ${new Date(conocido.fecha).toLocaleString()}`);
    } else {
        alert('⚠️ Rostro no registrado.\n\nRegístralo para futuros reconocimientos.');
    }
}

// ==========================================
// 7. GESTIÓN DE ROSTROS GUARDADOS
// ==========================================

function guardarRostros() {
    try {
        localStorage.setItem('rostrosRegistrados', JSON.stringify(rostrosRegistrados));
        console.log(`💾 ${rostrosRegistrados.length} rostros guardados en localStorage`);
    } catch (error) {
        console.error('Error al guardar rostros:', error);
    }
}

function cargarRostrosGuardados() {
    try {
        const data = localStorage.getItem('rostrosRegistrados');
        if (data) {
            rostrosRegistrados = JSON.parse(data);
            actualizarListaRostros();
            console.log(`📂 ${rostrosRegistrados.length} rostros cargados desde localStorage`);
        }
    } catch (error) {
        console.error('Error al cargar rostros:', error);
    }
}

function actualizarListaRostros() {
    const container = document.getElementById('listaRostros');
    if (!container) return;

    if (rostrosRegistrados.length === 0) {
        container.innerHTML = '<span class="text-muted">Sin rostros registrados</span>';
        return;
    }

    let html = '<div class="list-group list-group-flush">';
    // Mostrar los más recientes primero
    const sorted = [...rostrosRegistrados].reverse();
    sorted.forEach(r => {
        html += `
            <div class="list-group-item d-flex justify-content-between align-items-center py-1 px-2">
                <div>
                    <span class="badge bg-primary me-1">${r.id}</span>
                    <span>${r.nombre}</span>
                    <small class="text-muted d-block" style="font-size: 9px;">${new Date(r.fecha).toLocaleDateString()}</small>
                </div>
                <button class="btn btn-sm btn-outline-danger" onclick="eliminarRostro('${r.id}')" title="Eliminar">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        `;
    });
    html += '</div>';

    container.innerHTML = html;
}

function eliminarRostro(id) {
    if (confirm(`¿Eliminar el rostro con ID "${id}"?`)) {
        rostrosRegistrados = rostrosRegistrados.filter(r => r.id !== id);
        guardarRostros();
        actualizarListaRostros();
        console.log(`🗑️ Rostro ${id} eliminado`);
    }
}

function limpiarTodosRostros() {
    if (rostrosRegistrados.length === 0) {
        alert('No hay rostros registrados para eliminar.');
        return;
    }

    if (confirm('⚠️ ¿Eliminar TODOS los rostros registrados?')) {
        rostrosRegistrados = [];
        guardarRostros();
        actualizarListaRostros();
        alert('✅ Todos los rostros han sido eliminados.');
        console.log('🗑️ Todos los rostros eliminados');
    }
}

// ==========================================
// 8. CAPTURAR FOTO
// ==========================================

function capturarFoto() {
    if (!video || video.paused || !isCameraStarted) {
        alert('⚠️ La cámara no está activa. Inicia la cámara primero.');
        return;
    }

    // Crear un canvas temporal para capturar la imagen
    const tempCanvas = document.createElement('canvas');
    tempCanvas.width = video.videoWidth || 640;
    tempCanvas.height = video.videoHeight || 480;
    const tempCtx = tempCanvas.getContext('2d');
    tempCtx.drawImage(video, 0, 0, tempCanvas.width, tempCanvas.height);

    // Crear enlace de descarga
    const link = document.createElement('a');
    link.download = `captura_${new Date().toISOString().slice(0,19).replace(/:/g, '-')}.png`;
    link.href = tempCanvas.toDataURL('image/png');
    link.click();

    console.log('📸 Foto capturada');
}

// ==========================================
// 9. EXPORTAR FUNCIONES
// ==========================================

// Hacer funciones globales para usar desde HTML
window.init = init;
window.iniciarCamara = iniciarCamara;
window.cerrarCamara = cerrarCamara;
window.capturarRostro = capturarRostro;
window.guardarRostro = guardarRostro;
window.reconocerRostro = reconocerRostro;
window.eliminarRostro = eliminarRostro;
window.limpiarTodosRostros = limpiarTodosRostros;
window.capturarFoto = capturarFoto;

console.log('📦 Módulo de reconocimiento facial cargado');