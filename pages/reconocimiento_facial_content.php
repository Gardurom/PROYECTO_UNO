<?php
// pages/reconocimiento_facial_content.php
?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col">
            <h2><i class="fas fa-user-shield"></i> Módulo de Reconocimiento Facial</h2>
            <p class="text-muted">Detecta y reconoce rostros usando la cámara de tu dispositivo</p>
        </div>
        <div class="col text-end">
            <button class="btn btn-danger" onclick="cerrarCamara()">
                <i class="fas fa-stop"></i> Detener Cámara
            </button>
        </div>
    </div>

    <div class="row">
        <!-- Panel de control -->
        <div class="col-md-4">
            <div class="card mb-3">
                <div class="card-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                    <h5 class="mb-0"><i class="fas fa-sliders-h"></i> Control</h5>
                </div>
                <div class="card-body">
                    <!-- Estado -->
                    <div id="estadoFacial" class="alert alert-secondary text-center">
                        <i class="fas fa-spinner fa-spin"></i> Inicializando...
                    </div>

                    <!-- Botones -->
                    <div class="d-grid gap-2">
                        <button class="btn btn-primary" onclick="iniciarCamara()">
                            <i class="fas fa-camera"></i> Iniciar Cámara
                        </button>
                        <button class="btn btn-success" onclick="capturarRostro()">
                            <i class="fas fa-user-plus"></i> Registrar Rostro
                        </button>
                        <button class="btn btn-info" onclick="reconocerRostro()">
                            <i class="fas fa-search"></i> Buscar Rostro
                        </button>
                    </div>
                    <div class="d-grid gap-2 mt-2">
                        <button class="btn btn-outline-secondary" onclick="capturarFoto()">
                            <i class="fas fa-camera"></i> Capturar Foto
                        </button>
                        <button class="btn btn-outline-danger" onclick="limpiarTodosRostros()">
                            <i class="fas fa-trash-alt"></i> Eliminar Todos los Rostros
                        </button>
                    </div>

                    <!-- Agregar en la información del rostro -->
                    <div id="infoExtra" class="mt-2"></div>
                    <hr>

                    <!-- Información del rostro detectado -->
                    <div id="infoRostro" style="display: none;">
                        <h6><i class="fas fa-id-card"></i> Información</h6>
                        <div id="detallesRostro" class="small"></div>
                    </div>

                    <!-- Lista de rostros registrados -->
                    <div class="mt-3">
                        <h6><i class="fas fa-users"></i> Rostros Registrados</h6>
                        <div id="listaRostros" class="small">
                            <span class="text-muted">Sin rostros registrados</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Video -->
        <div class="col-md-8">
            <div class="card">
                <div class="card-body p-0" style="position: relative;">
                    <video id="videoFacial" width="100%" height="auto" autoplay muted playsinline style="border-radius: 10px 10px 0 0;"></video>
                    <canvas id="canvasFacial" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; border-radius: 10px 10px 0 0;"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal para registrar rostro -->
<div class="modal fade" id="modalRegistrar" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-user-plus"></i> Registrar Rostro</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Nombre de la persona</label>
                    <input type="text" id="nombreRostro" class="form-control" placeholder="Ej: Juan Pérez">
                </div>
                <div class="mb-3">
                    <label class="form-label">Identificador único</label>
                    <input type="text" id="idRostro" class="form-control" placeholder="Ej: JPER001">
                </div>
                <div id="previewRostro" class="text-center">
                    <p class="text-muted">Se capturará el rostro detectado en la cámara</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-success" onclick="guardarRostro()">
                    <i class="fas fa-save"></i> Guardar
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

<script src="https://unpkg.com/face-api.js@0.22.2/dist/face-api.min.js"></script>

<style>
    #videoFacial,
    #canvasFacial {
        width: 100%;
        height: auto;
        max-height: 500px;
        object-fit: cover;
    }
</style>

<script>
    // ==========================================
    // 1. CONFIGURACIÓN
    // ==========================================

    const MODEL_URL = './models/';
    let video = null;
    let canvas = null;
    let context = null;
    let stream = null;
    let detectionInterval = null;
    let rostrosRegistrados = [];
    let descriptorActual = null;

    // ==========================================
    // 2. INICIALIZACIÓN
    // ==========================================

    async function init() {
        const estadoDiv = document.getElementById('estadoFacial');
        estadoDiv.className = 'alert estado-esperando text-center';
        estadoDiv.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Cargando modelos de reconocimiento facial...';

        try {
            // Cargar modelos
            await Promise.all([
                faceapi.nets.ssdMobilenetv1.loadFromUri(MODEL_URL),
                faceapi.nets.faceLandmark68Net.loadFromUri(MODEL_URL),
                faceapi.nets.faceRecognitionNet.loadFromUri(MODEL_URL)
            ]);

            console.log('✅ Modelos cargados correctamente');

            // Cargar rostros registrados desde localStorage
            cargarRostrosGuardados();

            estadoDiv.className = 'alert alert-success text-center';
            estadoDiv.innerHTML = '<i class="fas fa-check-circle"></i> Modelos listos. Inicia la cámara para comenzar.';

        } catch (error) {
            console.error('❌ Error al cargar modelos:', error);
            estadoDiv.className = 'alert estado-fuera text-center';
            estadoDiv.innerHTML = '<i class="fas fa-exclamation-triangle"></i> Error al cargar los modelos. Verifica que los archivos existan en la carpeta "models".';
        }
    }

    // ==========================================
    // 3. CÁMARA
    // ==========================================

    async function iniciarCamara() {
        const estadoDiv = document.getElementById('estadoFacial');

        try {
            stream = await navigator.mediaDevices.getUserMedia({
                video: {
                    width: {
                        ideal: 640
                    },
                    height: {
                        ideal: 480
                    },
                    facingMode: 'user'
                }
            });

            video = document.getElementById('videoFacial');
            video.srcObject = stream;

            canvas = document.getElementById('canvasFacial');
            context = canvas.getContext('2d');

            // Ajustar canvas al tamaño del video
            video.addEventListener('loadedmetadata', function() {
                canvas.width = video.videoWidth;
                canvas.height = video.videoHeight;
            });

            await video.play();

            estadoDiv.className = 'alert alert-success text-center';
            estadoDiv.innerHTML = '<i class="fas fa-check-circle"></i> Cámara activa. Detectando rostros...';

            // Iniciar detección
            iniciarDeteccion();

        } catch (error) {
            console.error('❌ Error al iniciar cámara:', error);
            estadoDiv.className = 'alert estado-fuera text-center';
            estadoDiv.innerHTML = '<i class="fas fa-exclamation-triangle"></i> No se pudo acceder a la cámara. Verifica los permisos.';
        }
    }

    function cerrarCamara() {
        if (detectionInterval) {
            clearInterval(detectionInterval);
            detectionInterval = null;
        }

        if (stream) {
            stream.getTracks().forEach(track => track.stop());
            stream = null;
        }

        if (video) {
            video.srcObject = null;
        }

        const estadoDiv = document.getElementById('estadoFacial');
        estadoDiv.className = 'alert alert-secondary text-center';
        estadoDiv.innerHTML = '<i class="fas fa-info-circle"></i> Cámara detenida.';

        // Limpiar canvas
        if (context && canvas) {
            context.clearRect(0, 0, canvas.width, canvas.height);
        }
    }

    // ==========================================
    // 4. DETECCIÓN DE ROSTROS
    // ==========================================

    function iniciarDeteccion() {
        if (detectionInterval) {
            clearInterval(detectionInterval);
        }

        detectionInterval = setInterval(async () => {
            if (!video || video.paused || video.ended) return;

            try {
                const detections = await faceapi.detectAllFaces(video)
                    .withFaceLandmarks()
                    .withFaceDescriptors();

                // Limpiar canvas
                context.clearRect(0, 0, canvas.width, canvas.height);

                // Dibujar video
                context.drawImage(video, 0, 0, canvas.width, canvas.height);

                if (detections.length > 0) {
                    // Dibujar detecciones
                    faceapi.draw.drawDetections(canvas, detections);
                    faceapi.draw.drawFaceLandmarks(canvas, detections);

                    // Mostrar información del primer rostro detectado
                    const detection = detections[0];
                    const descriptor = detection.descriptor;

                    // Verificar si es un rostro conocido
                    const conocido = buscarRostroConocido(descriptor);

                    if (conocido) {
                        document.getElementById('infoRostro').style.display = 'block';
                        document.getElementById('detallesRostro').innerHTML = `
                        <div class="alert alert-success">
                            <strong>✅ Rostro reconocido</strong><br>
                            <strong>Nombre:</strong> ${conocido.nombre}<br>
                            <strong>ID:</strong> ${conocido.id}
                        </div>
                    `;
                        document.getElementById('estadoFacial').className = 'alert alert-success text-center';
                        document.getElementById('estadoFacial').innerHTML = `<i class="fas fa-check-circle"></i> ✅ ${conocido.nombre} identificado`;
                    } else {
                        document.getElementById('infoRostro').style.display = 'block';
                        document.getElementById('detallesRostro').innerHTML = `
                        <div class="alert alert-warning">
                            <strong>⚠️ Rostro no registrado</strong><br>
                            Haz clic en "Registrar Rostro" para guardarlo.
                        </div>
                    `;
                        document.getElementById('estadoFacial').className = 'alert estado-esperando text-center';
                        document.getElementById('estadoFacial').innerHTML = '<i class="fas fa-info-circle"></i> Rostro desconocido';
                    }

                    // Guardar descriptor para registro
                    descriptorActual = descriptor;

                } else {
                    document.getElementById('infoRostro').style.display = 'none';
                    document.getElementById('estadoFacial').className = 'alert estado-esperando text-center';
                    document.getElementById('estadoFacial').innerHTML = '<i class="fas fa-info-circle"></i> No se detectan rostros';
                }

            } catch (error) {
                console.error('Error en detección:', error);
            }
        }, 100); // Cada 100ms
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

        if (!nombre || !id) {
            alert('⚠️ Por favor, completa todos los campos');
            return;
        }

        // Verificar que el ID no exista
        if (rostrosRegistrados.some(r => r.id === id)) {
            alert('⚠️ El ID ya está registrado. Usa otro identificador.');
            return;
        }

        // Guardar rostro
        rostrosRegistrados.push({
            id: id,
            nombre: nombre,
            descriptor: Array.from(descriptorActual),
            fecha: new Date().toISOString()
        });

        // Guardar en localStorage
        guardarRostros();

        // Actualizar lista
        actualizarListaRostros();

        // Cerrar modal
        bootstrap.Modal.getInstance(document.getElementById('modalRegistrar')).hide();

        alert(`✅ Rostro de "${nombre}" registrado correctamente`);

        // Limpiar campos
        document.getElementById('nombreRostro').value = '';
        document.getElementById('idRostro').value = '';
    }

    // ==========================================
    // 6. RECONOCIMIENTO DE ROSTROS
    // ==========================================

    function buscarRostroConocido(descriptor) {
        const umbral = 0.6; // Distancia máxima para considerar coincidencia

        for (const rostro of rostrosRegistrados) {
            const rostroDescriptor = new Float32Array(rostro.descriptor);
            const distancia = faceapi.euclideanDistance(descriptor, rostroDescriptor);

            if (distancia < umbral) {
                return rostro;
            }
        }

        return null;
    }

    function reconocerRostro() {
        if (!descriptorActual) {
            alert('⚠️ No se ha detectado ningún rostro. Asegúrate de estar frente a la cámara.');
            return;
        }

        const conocido = buscarRostroConocido(descriptorActual);

        if (conocido) {
            alert(`✅ Rostro reconocido: ${conocido.nombre} (${conocido.id})`);
        } else {
            alert('⚠️ Rostro no registrado. Regístralo para futuros reconocimientos.');
        }
    }

    // ==========================================
    // 7. GESTIÓN DE ROSTROS GUARDADOS
    // ==========================================

    function guardarRostros() {
        try {
            localStorage.setItem('rostrosRegistrados', JSON.stringify(rostrosRegistrados));
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
            }
        } catch (error) {
            console.error('Error al cargar rostros:', error);
        }
    }

    function actualizarListaRostros() {
        const container = document.getElementById('listaRostros');

        if (rostrosRegistrados.length === 0) {
            container.innerHTML = '<span class="text-muted">Sin rostros registrados</span>';
            return;
        }

        let html = '<ul class="list-unstyled">';
        rostrosRegistrados.forEach(r => {
            html += `
            <li class="border-bottom py-1 d-flex justify-content-between align-items-center">
                <span>
                    <span class="badge bg-primary">${r.id}</span>
                    ${r.nombre}
                </span>
                <button class="btn btn-sm btn-danger" onclick="eliminarRostro('${r.id}')">
                    <i class="fas fa-trash"></i>
                </button>
            </li>
        `;
        });
        html += '</ul>';

        container.innerHTML = html;
    }

    function eliminarRostro(id) {
        if (confirm(`¿Eliminar el rostro con ID "${id}"?`)) {
            rostrosRegistrados = rostrosRegistrados.filter(r => r.id !== id);
            guardarRostros();
            actualizarListaRostros();
        }
    }

    // ==========================================
    // 8. LIMPIAR TODO
    // ==========================================

    function limpiarTodo() {
        if (confirm('¿Eliminar todos los rostros registrados?')) {
            rostrosRegistrados = [];
            guardarRostros();
            actualizarListaRostros();
            alert('✅ Todos los rostros han sido eliminados.');
        }
    }

    // ==========================================
    // 9. EXPORTAR FUNCIONES
    // ==========================================

    window.init = init;
    window.iniciarCamara = iniciarCamara;
    window.cerrarCamara = cerrarCamara;
    window.capturarRostro = capturarRostro;
    window.guardarRostro = guardarRostro;
    window.reconocerRostro = reconocerRostro;
    window.eliminarRostro = eliminarRostro;
    window.limpiarTodo = limpiarTodo;

    // ==========================================
    // 10. INICIALIZAR
    // ==========================================

    document.addEventListener('DOMContentLoaded', function() {
        init();
    });
</script>