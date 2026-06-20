<?php
// pages/asignaciones_content.php
$db = getDB();

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['accion'])) {
        if ($_POST['accion'] === 'crear') {
            $materia_id = intval($_POST['materia_id']);
            $docente_id = intval($_POST['docente_id']);
            $periodo = trim($_POST['periodo']);
            $grupo_id = intval($_POST['grupo_id']);
            
            try {
                $stmt = $db->prepare("
                    INSERT INTO materia_docente (materia_id, docente_id, periodo) 
                    VALUES (?, ?, ?)
                ");
                $stmt->execute([$materia_id, $docente_id, $periodo]);
                $mensaje = "✅ Asignación creada exitosamente";
            } catch (PDOException $e) {
                $error = "❌ Error al crear asignación: " . $e->getMessage();
            }
        } 
        elseif ($_POST['accion'] === 'eliminar') {
            $id = intval($_POST['id']);
            try {
                $stmt = $db->prepare("UPDATE materia_docente SET activo = 0 WHERE id = ?");
                $stmt->execute([$id]);
                $mensaje = "✅ Asignación eliminada exitosamente";
            } catch (PDOException $e) {
                $error = "❌ Error al eliminar asignación: " . $e->getMessage();
            }
        }
    }
}

// Obtener datos para selects
$materias = $db->query("SELECT * FROM materias WHERE activo = 1 ORDER BY nombre")->fetchAll();
$docentes = $db->query("SELECT * FROM docentes WHERE activo = 1 ORDER BY nombre, apellidos")->fetchAll();
$grupos = $db->query("SELECT g.*, gen.nombre as generacion_nombre FROM grupos g JOIN generaciones gen ON g.generacion_id = gen.id WHERE g.activo = 1 ORDER BY gen.anio DESC, g.nombre")->fetchAll();

// Obtener asignaciones actuales
$asignaciones = $db->query("
    SELECT 
        md.*,
        m.nombre as materia_nombre,
        m.clave,
        d.nombre as docente_nombre,
        d.apellidos as docente_apellidos,
        g.nombre as grupo_nombre,
        gen.nombre as generacion_nombre,
        gen.anio
    FROM materia_docente md
    JOIN materias m ON md.materia_id = m.id
    JOIN docentes d ON md.docente_id = d.id
    LEFT JOIN grupos g ON g.id = md.grupo_id
    LEFT JOIN generaciones gen ON g.generacion_id = gen.id
    WHERE md.activo = 1
    ORDER BY md.periodo DESC, m.nombre
")->fetchAll();
?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col">
            <h2><i class="fas fa-link"></i> Asignaciones Materia-Profesor-Grupo</h2>
            <p class="text-muted">Administra qué profesor imparte qué materia en qué grupo</p>
        </div>
        <div class="col text-end">
            <button type="button" class="btn btn-custom" data-bs-toggle="modal" data-bs-target="#modalAsignacion">
                <i class="fas fa-plus"></i> Nueva Asignación
            </button>
        </div>
    </div>

    <?php if (isset($mensaje)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo $mensaje; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($error)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php echo $error; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Tabla de Asignaciones -->
    <div class="card">
        <div class="card-header">
            <h5><i class="fas fa-list"></i> Lista de Asignaciones</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped datatable">
                    <thead>
                        <tr>
                            <th>Materia</th>
                            <th>Profesor</th>
                            <th>Grupo</th>
                            <th>Generación</th>
                            <th>Periodo</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($asignaciones as $asignacion): ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($asignacion['materia_nombre']); ?></strong>
                                <br><small class="text-muted"><?php echo htmlspecialchars($asignacion['clave']); ?></small>
                            </td>
                            <td><?php echo htmlspecialchars($asignacion['docente_nombre'] . ' ' . $asignacion['docente_apellidos']); ?></td>
                            <td><?php echo htmlspecialchars($asignacion['grupo_nombre'] ?? 'No asignado'); ?></td>
                            <td><?php echo htmlspecialchars($asignacion['generacion_nombre'] ?? 'N/A'); ?></td>
                            <td><span class="badge bg-info"><?php echo htmlspecialchars($asignacion['periodo']); ?></span></td>
                            <td>
                                <span class="badge bg-success">Activo</span>
                            </td>
                            <td>
                                <button class="btn btn-sm btn-danger" onclick="eliminarAsignacion(<?php echo $asignacion['id']; ?>, '<?php echo htmlspecialchars($asignacion['materia_nombre']); ?>')">
                                    <i class="fas fa-trash"></i> Eliminar
                                </button>
                                <button class="btn btn-sm btn-primary" onclick="generarQR(<?php echo $asignacion['id']; ?>)">
                                    <i class="fas fa-qrcode"></i> QR
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal Crear Asignación -->
<div class="modal fade" id="modalAsignacion" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-plus-circle"></i> Nueva Asignación</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="accion" value="crear">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Materia <span class="text-danger">*</span></label>
                            <select class="form-control" name="materia_id" required>
                                <option value="">Seleccionar materia</option>
                                <?php foreach($materias as $materia): ?>
                                <option value="<?php echo $materia['id']; ?>">
                                    <?php echo htmlspecialchars($materia['clave'] . ' - ' . $materia['nombre']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Profesor <span class="text-danger">*</span></label>
                            <select class="form-control" name="docente_id" required>
                                <option value="">Seleccionar profesor</option>
                                <?php foreach($docentes as $docente): ?>
                                <option value="<?php echo $docente['id']; ?>">
                                    <?php echo htmlspecialchars($docente['nombre'] . ' ' . $docente['apellidos']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Grupo</label>
                            <select class="form-control" name="grupo_id">
                                <option value="">Sin grupo específico</option>
                                <?php foreach($grupos as $grupo): ?>
                                <option value="<?php echo $grupo['id']; ?>">
                                    <?php echo htmlspecialchars($grupo['nombre'] . ' (' . $grupo['generacion_nombre'] . ')'); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Periodo <span class="text-danger">*</span></label>
                            <select class="form-control" name="periodo" required>
                                <option value="">Seleccionar periodo</option>
                                <option value="2025-1">2025-1 (Enero-Junio 2025)</option>
                                <option value="2025-2">2025-2 (Agosto-Diciembre 2025)</option>
                                <option value="2026-1">2026-1 (Enero-Junio 2026)</option>
                                <option value="2026-2">2026-2 (Agosto-Diciembre 2026)</option>
                            </select>
                        </div>
                    </div>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        <strong>Nota:</strong> Esta asignación permitirá que los alumnos de este grupo evalúen al profesor en esta materia.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-custom">Crear Asignación</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal para mostrar QR -->
<div class="modal fade" id="modalQR" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-qrcode"></i> Código QR</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center" id="contenidoQR">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Cargando...</span>
                </div>
                <p class="mt-2">Generando código QR...</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-primary" onclick="descargarQR()">
                    <i class="fas fa-download"></i> Descargar
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function eliminarAsignacion(id, nombre) {
    if(confirm(`¿Estás seguro de eliminar la asignación de "${nombre}"?`)) {
        let form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="accion" value="eliminar">
            <input type="hidden" name="id" value="${id}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

function generarQR(asignacionId) {
    let modal = new bootstrap.Modal(document.getElementById('modalQR'));
    let contenido = document.getElementById('contenidoQR');
    
    contenido.innerHTML = `
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Cargando...</span>
        </div>
        <p class="mt-2">Generando código QR...</p>
    `;
    
    modal.show();
    
    fetch(`ajax/generar_qr.php?asignacion_id=${asignacionId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                contenido.innerHTML = `
                    <img src="${data.qr_image}" class="img-fluid" alt="Código QR">
                    <p class="mt-2 text-muted small">
                        <i class="fas fa-info-circle"></i> Escanea este QR para evaluar
                    </p>
                    <p class="text-muted small">
                        URL: ${data.url}
                    </p>
                    <input type="hidden" id="qrUrl" value="${data.url}">
                `;
            } else {
                contenido.innerHTML = `
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle"></i> ${data.error}
                    </div>
                `;
            }
        })
        .catch(error => {
            contenido.innerHTML = `
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle"></i> Error al generar el QR
                </div>
            `;
        });
}

function descargarQR() {
    let img = document.querySelector('#contenidoQR img');
    if (img) {
        let link = document.createElement('a');
        link.download = 'qr_evaluacion.png';
        link.href = img.src;
        link.click();
    }
}
</script>