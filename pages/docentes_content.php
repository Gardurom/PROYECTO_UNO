<?php
// pages/docentes_content.php
$db = getDB();

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['accion'])) {
        if ($_POST['accion'] === 'crear') {
            $nombre = trim($_POST['nombre']);
            $apellidos = trim($_POST['apellidos']);
            $email = trim($_POST['email']);
            $telefono = trim($_POST['telefono']);
            
            try {
                $stmt = $db->prepare("INSERT INTO docentes (nombre, apellidos, email, telefono) VALUES (?, ?, ?, ?)");
                $stmt->execute([$nombre, $apellidos, $email, $telefono]);
                $mensaje = "Docente creado exitosamente";
            } catch (PDOException $e) {
                $error = "Error al crear docente: " . $e->getMessage();
            }
        } 
        elseif ($_POST['accion'] === 'editar') {
            $id = intval($_POST['id']);
            $nombre = trim($_POST['nombre']);
            $apellidos = trim($_POST['apellidos']);
            $email = trim($_POST['email']);
            $telefono = trim($_POST['telefono']);
            
            try {
                $stmt = $db->prepare("UPDATE docentes SET nombre = ?, apellidos = ?, email = ?, telefono = ? WHERE id = ?");
                $stmt->execute([$nombre, $apellidos, $email, $telefono, $id]);
                $mensaje = "Docente actualizado exitosamente";
            } catch (PDOException $e) {
                $error = "Error al actualizar docente: " . $e->getMessage();
            }
        }
        elseif ($_POST['accion'] === 'eliminar') {
            $id = intval($_POST['id']);
            try {
                $stmt = $db->prepare("UPDATE docentes SET activo = 0 WHERE id = ?");
                $stmt->execute([$id]);
                $mensaje = "Docente eliminado exitosamente";
            } catch (PDOException $e) {
                $error = "Error al eliminar docente: " . $e->getMessage();
            }
        }
    }
}

// Obtener listado de docentes
$docentes = $db->query("
    SELECT d.*, 
           COUNT(DISTINCT md.id) as total_materias,
           COUNT(DISTINCT e.id) as total_evaluaciones,
           AVG(e.puntualidad_asistencia) as avg_puntualidad,
           AVG(e.resolvio_dudas) as avg_resolucion
    FROM docentes d
    LEFT JOIN materia_docente md ON d.id = md.docente_id AND md.activo = 1
    LEFT JOIN evaluaciones e ON md.id = e.materia_docente_id
    WHERE d.activo = 1
    GROUP BY d.id
    ORDER BY d.apellidos, d.nombre
")->fetchAll();
?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col">
            <h2><i class="fas fa-chalkboard-user"></i> Gestión de Docentes</h2>
            <p class="text-muted">Administra los docentes y visualiza sus evaluaciones</p>
        </div>
        <div class="col text-end">
            <button type="button" class="btn btn-custom" data-bs-toggle="modal" data-bs-target="#modalDocente">
                <i class="fas fa-plus"></i> Nuevo Docente
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

    <div class="card">
        <div class="card-header">
            <h5><i class="fas fa-list"></i> Lista de Docentes</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped datatable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nombre Completo</th>
                            <th>Email</th>
                            <th>Teléfono</th>
                            <th>Materias</th>
                            <th>Evaluaciones</th>
                            <th>Promedio</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($docentes as $docente): ?>
                        <tr>
                            <td><?php echo $docente['id']; ?></td>
                            <td><strong><?php echo htmlspecialchars($docente['nombre'] . ' ' . $docente['apellidos']); ?></strong></td>
                            <td><?php echo htmlspecialchars($docente['email']); ?></td>
                            <td><?php echo htmlspecialchars($docente['telefono'] ?: 'N/A'); ?></td>
                            <td><span class="badge bg-primary"><?php echo $docente['total_materias']; ?></span></td>
                            <td><span class="badge bg-info"><?php echo $docente['total_evaluaciones']; ?></span></td>
                            <td>
                                <?php if($docente['total_evaluaciones'] > 0): ?>
                                <div class="progress" style="height: 20px;">
                                    <div class="progress-bar bg-success" style="width: <?php echo ($docente['avg_puntualidad'] / 9) * 100; ?>%" 
                                         title="Puntualidad: <?php echo round($docente['avg_puntualidad'], 2); ?>/9">
                                        P: <?php echo round($docente['avg_puntualidad'], 1); ?>
                                    </div>
                                    <div class="progress-bar bg-info" style="width: <?php echo ($docente['avg_resolucion'] / 9) * 100; ?>%" 
                                         title="Resolución: <?php echo round($docente['avg_resolucion'], 2); ?>/9">
                                        R: <?php echo round($docente['avg_resolucion'], 1); ?>
                                    </div>
                                </div>
                                <?php else: ?>
                                <span class="text-muted">Sin evaluaciones</span>
                                <?php endif; ?>
                             </td>
                            <td>
                                <button class="btn btn-sm btn-warning" onclick="editarDocente(<?php echo htmlspecialchars(json_encode($docente)); ?>)">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-sm btn-danger" onclick="eliminarDocente(<?php echo $docente['id']; ?>, '<?php echo htmlspecialchars($docente['nombre'] . ' ' . $docente['apellidos']); ?>')">
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
</div>

<!-- Modal Docente -->
<div class="modal fade" id="modalDocente" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalDocenteTitulo">Crear Nuevo Docente</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="formDocente">
                <input type="hidden" name="accion" id="docenteAccion" value="crear">
                <input type="hidden" name="id" id="docenteId" value="">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nombre(s)</label>
                        <input type="text" class="form-control" name="nombre" id="nombreDocente" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Apellidos</label>
                        <input type="text" class="form-control" name="apellidos" id="apellidosDocente" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Correo Electrónico</label>
                        <input type="email" class="form-control" name="email" id="emailDocente" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Teléfono</label>
                        <input type="text" class="form-control" name="telefono" id="telefonoDocente">
                        <small class="text-muted">Ej: 555-1234, 5512345678</small>
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

<script>
function editarDocente(docente) {
    document.getElementById('modalDocenteTitulo').innerText = 'Editar Docente';
    document.getElementById('docenteAccion').value = 'editar';
    document.getElementById('docenteId').value = docente.id;
    document.getElementById('nombreDocente').value = docente.nombre;
    document.getElementById('apellidosDocente').value = docente.apellidos;
    document.getElementById('emailDocente').value = docente.email;
    document.getElementById('telefonoDocente').value = docente.telefono;
    
    new bootstrap.Modal(document.getElementById('modalDocente')).show();
}

function eliminarDocente(id, nombre) {
    if(confirm(`¿Estás seguro de eliminar al docente "${nombre}"?`)) {
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

document.getElementById('modalDocente').addEventListener('hidden.bs.modal', function () {
    document.getElementById('modalDocenteTitulo').innerText = 'Crear Nuevo Docente';
    document.getElementById('docenteAccion').value = 'crear';
    document.getElementById('docenteId').value = '';
    document.getElementById('nombreDocente').value = '';
    document.getElementById('apellidosDocente').value = '';
    document.getElementById('emailDocente').value = '';
    document.getElementById('telefonoDocente').value = '';
});
</script>