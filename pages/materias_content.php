<?php
// pages/materias_content.php
$db = getDB();

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['accion'])) {
        if ($_POST['accion'] === 'crear') {
            $nombre = trim($_POST['nombre']);
            $clave = trim($_POST['clave']);
            
            try {
                $stmt = $db->prepare("INSERT INTO materias (nombre, clave) VALUES (?, ?)");
                $stmt->execute([$nombre, $clave]);
                $mensaje = "Materia creada exitosamente";
            } catch (PDOException $e) {
                $error = "Error al crear materia: " . $e->getMessage();
            }
        } 
        elseif ($_POST['accion'] === 'editar') {
            $id = intval($_POST['id']);
            $nombre = trim($_POST['nombre']);
            $clave = trim($_POST['clave']);
            
            try {
                $stmt = $db->prepare("UPDATE materias SET nombre = ?, clave = ? WHERE id = ?");
                $stmt->execute([$nombre, $clave, $id]);
                $mensaje = "Materia actualizada exitosamente";
            } catch (PDOException $e) {
                $error = "Error al actualizar materia: " . $e->getMessage();
            }
        }
        elseif ($_POST['accion'] === 'eliminar') {
            $id = intval($_POST['id']);
            try {
                $stmt = $db->prepare("UPDATE materias SET activo = 0 WHERE id = ?");
                $stmt->execute([$id]);
                $mensaje = "Materia eliminada exitosamente";
            } catch (PDOException $e) {
                $error = "Error al eliminar materia: " . $e->getMessage();
            }
        }
        elseif ($_POST['accion'] === 'asignar_docente') {
            $materia_id = intval($_POST['materia_id']);
            $docente_id = intval($_POST['docente_id']);
            $periodo = trim($_POST['periodo']);
            
            try {
                $stmt = $db->prepare("INSERT INTO materia_docente (materia_id, docente_id, periodo) VALUES (?, ?, ?)");
                $stmt->execute([$materia_id, $docente_id, $periodo]);
                $mensaje = "Docente asignado exitosamente";
            } catch (PDOException $e) {
                $error = "Error al asignar docente: " . $e->getMessage();
            }
        }
        elseif ($_POST['accion'] === 'eliminar_asignacion') {
            $id = intval($_POST['asignacion_id']);
            try {
                $stmt = $db->prepare("DELETE FROM materia_docente WHERE id = ?");
                $stmt->execute([$id]);
                $mensaje = "Asignación eliminada exitosamente";
            } catch (PDOException $e) {
                $error = "Error al eliminar asignación: " . $e->getMessage();
            }
        }
    }
}

// Obtener listado de materias
$materias = $db->query("
    SELECT * FROM materias WHERE activo = 1 ORDER BY nombre
")->fetchAll();

// Obtener docentes para asignación
$docentes = $db->query("SELECT * FROM docentes WHERE activo = 1 ORDER BY nombre, apellidos")->fetchAll();

// Obtener asignaciones actuales
$asignaciones = $db->query("
    SELECT md.*, m.nombre as materia_nombre, d.nombre as docente_nombre, d.apellidos as docente_apellidos
    FROM materia_docente md
    JOIN materias m ON md.materia_id = m.id
    JOIN docentes d ON md.docente_id = d.id
    WHERE md.activo = 1
    ORDER BY md.periodo DESC, m.nombre
")->fetchAll();
?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col">
            <h2><i class="fas fa-book"></i> Gestión de Materias</h2>
            <p class="text-muted">Administra las materias y asigna docentes</p>
        </div>
        <div class="col text-end">
            <button type="button" class="btn btn-custom" data-bs-toggle="modal" data-bs-target="#modalMateria">
                <i class="fas fa-plus"></i> Nueva Materia
            </button>
            <button type="button" class="btn btn-custom" data-bs-toggle="modal" data-bs-target="#modalAsignarDocente">
                <i class="fas fa-user-plus"></i> Asignar Docente
            </button>
        </div>
    </div>

    <?php if (isset($mensaje)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle"></i> <?php echo $mensaje; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($error)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle"></i> <?php echo $error; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Lista de Materias -->
    <div class="card mb-4">
        <div class="card-header">
            <h5><i class="fas fa-list"></i> Lista de Materias</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped datatable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Clave</th>
                            <th>Nombre de la Materia</th>
                            <th>Fecha Creación</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($materias as $materia): ?>
                        <tr>
                            <td><?php echo $materia['id']; ?></td>
                            <td><code><?php echo htmlspecialchars($materia['clave']); ?></code></td>
                            <td><strong><?php echo htmlspecialchars($materia['nombre']); ?></strong></td>
                            <td><?php echo date('d/m/Y', strtotime($materia['created_at'])); ?></td>
                            <td>
                                <button class="btn btn-sm btn-warning" onclick="editarMateria(<?php echo htmlspecialchars(json_encode($materia)); ?>)">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-sm btn-danger" onclick="eliminarMateria(<?php echo $materia['id']; ?>, '<?php echo htmlspecialchars($materia['nombre']); ?>')">
                                    <i class="fas fa-trash"></i>
                                </button>
                             </td>
                         </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Asignaciones Docente-Materia -->
    <div class="card">
        <div class="card-header">
            <h5><i class="fas fa-chalkboard-user"></i> Asignaciones de Docentes por Materia</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Materia</th>
                            <th>Docente</th>
                            <th>Periodo</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($asignaciones as $asignacion): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($asignacion['materia_nombre']); ?></strong></td>
                            <td><?php echo htmlspecialchars($asignacion['docente_nombre'] . ' ' . $asignacion['docente_apellidos']); ?></td>
                            <td><span class="badge bg-info"><?php echo htmlspecialchars($asignacion['periodo']); ?></span></td>
                            <td>
                                <button class="btn btn-sm btn-danger" onclick="eliminarAsignacion(<?php echo $asignacion['id']; ?>)">
                                    <i class="fas fa-trash"></i> Eliminar
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

<!-- Modal Materia -->
<div class="modal fade" id="modalMateria" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalMateriaTitulo">Crear Nueva Materia</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="formMateria">
                <input type="hidden" name="accion" id="materiaAccion" value="crear">
                <input type="hidden" name="id" id="materiaId" value="">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Clave de la Materia</label>
                        <input type="text" class="form-control" name="clave" id="claveMateria" required>
                        <small class="text-muted">Ej: MAT-101, PROG-201, etc.</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nombre de la Materia</label>
                        <input type="text" class="form-control" name="nombre" id="nombreMateria" required>
                        <small class="text-muted">Nombre completo de la materia</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-custom">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Asignar Docente -->
<div class="modal fade" id="modalAsignarDocente" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Asignar Docente a Materia</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="accion" value="asignar_docente">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Materia</label>
                        <select class="form-control" name="materia_id" required>
                            <option value="">Seleccionar materia</option>
                            <?php foreach($materias as $materia): ?>
                            <option value="<?php echo $materia['id']; ?>">
                                <?php echo htmlspecialchars($materia['clave'] . ' - ' . $materia['nombre']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Docente</label>
                        <select class="form-control" name="docente_id" required>
                            <option value="">Seleccionar docente</option>
                            <?php foreach($docentes as $docente): ?>
                            <option value="<?php echo $docente['id']; ?>">
                                <?php echo htmlspecialchars($docente['nombre'] . ' ' . $docente['apellidos'] . ' - ' . $docente['email']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Periodo</label>
                        <select class="form-control" name="periodo" required>
                            <option value="">Seleccionar periodo</option>
                            <option value="2024-1">2024-1 (Enero-Junio 2024)</option>
                            <option value="2024-2">2024-2 (Agosto-Diciembre 2024)</option>
                            <option value="2025-1">2025-1 (Enero-Junio 2025)</option>
                            <option value="2025-2">2025-2 (Agosto-Diciembre 2025)</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-custom">Asignar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function editarMateria(materia) {
    document.getElementById('modalMateriaTitulo').innerText = 'Editar Materia';
    document.getElementById('materiaAccion').value = 'editar';
    document.getElementById('materiaId').value = materia.id;
    document.getElementById('claveMateria').value = materia.clave;
    document.getElementById('nombreMateria').value = materia.nombre;
    
    new bootstrap.Modal(document.getElementById('modalMateria')).show();
}

function eliminarMateria(id, nombre) {
    if(confirm(`¿Estás seguro de eliminar la materia "${nombre}"?`)) {
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

function eliminarAsignacion(id) {
    if(confirm('¿Estás seguro de eliminar esta asignación?')) {
        let form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="accion" value="eliminar_asignacion">
            <input type="hidden" name="asignacion_id" value="${id}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

document.getElementById('modalMateria').addEventListener('hidden.bs.modal', function () {
    document.getElementById('modalMateriaTitulo').innerText = 'Crear Nueva Materia';
    document.getElementById('materiaAccion').value = 'crear';
    document.getElementById('materiaId').value = '';
    document.getElementById('claveMateria').value = '';
    document.getElementById('nombreMateria').value = '';
});
</script>