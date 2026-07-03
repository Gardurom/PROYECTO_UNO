<?php
// pages/qr_module_content.php
?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col">
            <h2><i class="fas fa-qrcode"></i> Módulo de Códigos QR</h2>
            <p class="text-muted">Genera, escanea y gestiona códigos QR para tu sistema</p>
        </div>
    </div>

    <!-- Pestañas -->
    <ul class="nav nav-tabs mb-3" id="qrTabs" role="tablist">
        <li class="nav-item">
            <button class="nav-link active" id="tab-generar" data-bs-toggle="tab" data-bs-target="#content-generar" type="button" role="tab">
                <i class="fas fa-plus-circle"></i> Generar QR
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link" id="tab-escanear" data-bs-toggle="tab" data-bs-target="#content-escanear" type="button" role="tab">
                <i class="fas fa-camera"></i> Escanear QR
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link" id="tab-listado" data-bs-toggle="tab" data-bs-target="#content-listado" type="button" role="tab">
                <i class="fas fa-list"></i> Listado de QR
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link" id="tab-historial" data-bs-toggle="tab" data-bs-target="#content-historial" type="button" role="tab">
                <i class="fas fa-history"></i> Historial de Escaneos
            </button>
        </li>
    </ul>

    <div class="tab-content">

        <!-- ========================================== -->
        <!-- PESTAÑA 1: GENERAR QR -->
        <!-- ========================================== -->
        <div class="tab-pane fade show active" id="content-generar" role="tabpanel">
            <div class="card">
                <div class="card-header">
                    <h5><i class="fas fa-plus-circle"></i> Generar Nuevo Código QR</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <form id="formGenerarQR" onsubmit="return false;">
                                <div class="mb-3">
                                    <label class="form-label">Tipo de QR <span class="text-danger">*</span></label>
                                    <select class="form-control" name="tipo" id="tipoQR" required>
                                        <option value="">Seleccionar tipo...</option>
                                        <option value="cadete">Cadete</option>
                                        <option value="docente">Docente</option>
                                        <option value="materia">Materia</option>
                                        <option value="evaluacion">Evaluación</option>
                                        <option value="general">General</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Referencia ID</label>
                                    <input type="number" class="form-control" name="referencia_id" id="referenciaId" placeholder="ID del registro relacionado">
                                    <small class="text-muted">Opcional: ID del cadete, docente, materia, etc.</small>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Datos adicionales</label>
                                    <textarea class="form-control" name="datos_extra" id="datosExtra" rows="2" placeholder='{"info": "Datos adicionales"}'></textarea>
                                    <small class="text-muted">JSON con información extra (opcional)</small>
                                </div>
                                <div class="d-flex gap-2">
                                    <button type="button" class="btn btn-primary" onclick="generarQR()">
                                        <i class="fas fa-qrcode"></i> Generar QR
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary" onclick="limpiarFormularioQR()">
                                        <i class="fas fa-eraser"></i> Limpiar
                                    </button>
                                    <button type="button" class="btn btn-outline-warning" onclick="probarGeneracionQR()">
                                        <i class="fas fa-bug"></i> Probar
                                    </button>
                                </div>
                            </form>
                        </div>
                        <div class="col-md-6">
                            <div id="qrResultado" style="display: none;">
                                <div class="card">
                                    <div class="card-body text-center">
                                        <h6>QR Generado</h6>
                                        <div id="qrImagenContainer">
                                            <div class="text-center p-4">
                                                <div class="spinner-border text-primary" role="status">
                                                    <span class="visually-hidden">Cargando...</span>
                                                </div>
                                                <p class="mt-2">Generando código QR...</p>
                                            </div>
                                        </div>
                                        <div class="mt-2">
                                            <p><strong>Código:</strong> <span id="qrCodigoGenerado"></span></p>
                                            <button class="btn btn-sm btn-success" onclick="descargarQR()">
                                                <i class="fas fa-download"></i> Descargar
                                            </button>
                                            <button class="btn btn-sm btn-primary" onclick="copiarQR()">
                                                <i class="fas fa-copy"></i> Copiar
                                            </button>
                                        </div>
                                        <div id="qrInfoAdicional" class="mt-2 small"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ========================================== -->
        <!-- PESTAÑA 2: ESCANEAR QR -->
        <!-- ========================================== -->
        <div class="tab-pane fade" id="content-escanear" role="tabpanel">
            <div class="card">
                <div class="card-header">
                    <h5><i class="fas fa-camera"></i> Escanear Código QR</h5>
                    <p class="text-muted small">Escaneo continuo - Puedes escanear múltiples códigos sin detener la cámara</p>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-8">
                            <div id="reader" style="width: 100%; max-width: 500px; margin: 0 auto; min-height: 300px; background: #f0f0f0; border-radius: 10px; display: flex; align-items: center; justify-content: center;">
                                <div id="mensajeCamara" class="text-center text-muted p-4">
                                    <i class="fas fa-camera fa-3x mb-3"></i>
                                    <p>Haz clic en <strong>"Iniciar Escáner"</strong> para activar la cámara</p>
                                    <p class="small">El escáner permanecerá activo para leer múltiples códigos</p>
                                </div>
                            </div>
                            <div id="resultadoEscaneo" class="mt-3" style="display: none;">
                                <div class="alert alert-info">
                                    <strong>📱 Escaneando...</strong>
                                    <div id="contenidoQR" class="mt-2"></div>
                                </div>
                            </div>
                            <!-- El historial en vivo se crea dinámicamente -->
                        </div>
                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-header">
                                    <h6><i class="fas fa-info-circle"></i> Control de Escaneo</h6>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <div class="d-flex justify-content-between">
                                            <span>Estado:</span>
                                            <span id="estadoCamara" class="badge bg-secondary">Inactivo</span>
                                        </div>
                                        <div class="d-flex justify-content-between mt-1">
                                            <span>Escaneos:</span>
                                            <span id="contadorEscaneos" class="badge bg-primary">0</span>
                                        </div>
                                    </div>
                                    <button class="btn btn-primary w-100 mb-2" onclick="iniciarEscanner()">
                                        <i class="fas fa-play"></i> Iniciar Escáner
                                    </button>
                                    <button class="btn btn-secondary w-100 mb-2" onclick="detenerEscanner()">
                                        <i class="fas fa-stop"></i> Detener Escáner
                                    </button>
                                    <button class="btn btn-outline-danger w-100 mb-2" onclick="reiniciarEscanner()">
                                        <i class="fas fa-sync"></i> Reiniciar Escáner
                                    </button>
                                    <hr>
                                    <div class="d-grid gap-2">
                                        <button class="btn btn-sm btn-outline-success" onclick="exportarReporteEscaneos()">
                                            <i class="fas fa-file-excel"></i> Exportar Reporte
                                        </button>
                                        <button class="btn btn-sm btn-outline-danger" onclick="limpiarHistorialLive()">
                                            <i class="fas fa-trash"></i> Limpiar Historial
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ========================================== -->
        <!-- PESTAÑA 3: LISTADO DE QR -->
        <!-- ========================================== -->
        <div class="tab-pane fade" id="content-listado" role="tabpanel">
            <div class="card">
                <div class="card-header">
                    <h5><i class="fas fa-list"></i> Códigos QR Generados</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped datatable" id="tablaQR">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Código</th>
                                    <th>Tipo</th>
                                    <th>Referencia</th>
                                    <th>Estado</th>
                                    <th>Fecha</th>
                                    <th>Escaneos</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody id="tablaQRBody">
                                <tr>
                                    <td colspan="8" class="text-center">Cargando códigos QR...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- ========================================== -->
        <!-- PESTAÑA 4: HISTORIAL DE ESCANEOS -->
        <!-- ========================================== -->
        <div class="tab-pane fade" id="content-historial" role="tabpanel">
            <div class="card">
                <div class="card-header">
                    <h5><i class="fas fa-history"></i> Historial de Escaneos</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped datatable" id="tablaHistorial">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Código QR</th>
                                    <th>Usuario</th>
                                    <th>IP</th>
                                    <th>Fecha</th>
                                    <th>Ubicación</th>
                                </tr>
                            </thead>
                            <tbody id="tablaHistorialBody">
                                <tr>
                                    <td colspan="6" class="text-center">Cargando historial...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<!-- Librerías para QR -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/html5-qrcode/2.3.4/html5-qrcode.min.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
#reader {
    border: 2px solid #667eea;
    border-radius: 10px;
    overflow: hidden;
}
#reader video {
    border-radius: 8px;
}
</style>

<script src="js/qr_module.js"></script>