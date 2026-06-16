<?php
// pages/grupos_content.php
$db = getDB();

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['accion'])) {
        if ($_POST['accion'] === 'crear') {
            $nombre = trim($_POST['nombre']);
            $generacion_id = intval($_POST['generacion_id']);
            
            try {
                $stmt = $db->prepare("INSERT INTO grupos (nombre, generacion_id) VALUES (?, ?)");
                $stmt->execute([$nombre, $generacion_id]);
                $mensaje = "Grupo creado exitosamente";
            } catch (PDOException $e) {
                $error = "Error al crear grupo: " . $e->getMessage();
            }
        } 
        elseif ($_POST['accion'] === 'editar') {
            $id = intval($_POST['id']);
            $nombre = trim($_POST['nombre']);
            $generacion_id = intval($_POST['generacion_id']);
            
            try {
                $stmt = $db->prepare("UPDATE grupos SET nombre = ?, generacion_id = ? WHERE id = ?");
                $stmt->execute([$nombre, $generacion_id, $id]);
                $mensaje = "Grupo actualizado exitosamente";
            } catch (PDOException $e) {
                $error = "Error al actualizar grupo: " . $e->getMessage();
            }
        }
        elseif ($_POST['accion'] === 'eliminar') {
            $id = intval($_POST['id']);
            try {
                $stmt = $db->prepare("UPDATE grupos SET activo = 0 WHERE id = ?");
                $stmt->execute([$id]);
                $mensaje = "Grupo eliminado exitosamente";
            } catch (PDOException $e) {
                $error = "Error al eliminar grupo: " . $e->getMessage();
            }
        }
    }
}

// Obtener listado de grupos
$grupos = $db->query("
    SELECT g.*, ge.nombre as generacion_nombre, ge.anio 
    FROM grupos g
    JOIN generaciones ge ON g.generacion_id = ge.id
    WHERE g.activo = 1
    ORDER BY ge.anio DESC, g.nombre
")->fetchAll();

// Obtener generaciones para selects
$generaciones = $db->query("SELECT * FROM generaciones WHERE activo = 1 ORDER BY anio DESC")->fetchAll();
?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col">
            <h2><i class="fas fa-layer-group"></i> Gestión de Grupos</h2>
            <p class="text-muted">Administra los grupos académicos por generación</p>
        </div>
        <div class="col text-end">
            <button type="button" class="btn btn-custom" data-bs-toggle="modal" data-bs-target="#modalGrupo">
                <i class="fas fa-plus"></i> Nuevo Grupo
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
            <h5><i class="fas fa-list"></i> Lista de Grupos</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped datatable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nombre del Grupo</th>
                            <th>Generación</th>
                            <th>Año</th>
                            <th>Fecha Creación</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($grupos as $grupo): ?>
                        <tr>
                            <td><?php echo $grupo['id']; ?>;</td>
                            <td><strong><?php echo htmlspecialchars($grupo['nombre']); ?></strong></td>
                            <td><?php echo htmlspecialchars($grupo['generacion_nombre']); ?></td>
                            <td><?php echo $grupo['anio']; ?></td>
                            <td><?php echo date('d/m/Y', strtotime($grupo['created_at'])); ?></td>
                            <td>
                                <button class="btn btn-sm btn-warning" onclick="editarGrupo(<?php echo htmlspecialchars(json_encode($grupo)); ?>)">
                                    <i class="fas fa-edit"></i> Editar
                                </button>
                                <button class="btn btn-sm btn-danger" onclick="eliminarGrupo(<?php echo $grupo['id']; ?>, '<?php echo htmlspecialchars($grupo['nombre']); ?>')">
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

<!-- Modal para crear/editar grupo -->
<div class="modal fade" id="modalGrupo" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitulo">Crear Nuevo Grupo</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="formGrupo">
                <input type="hidden" name="accion" id="accion" value="crear">
                <input type="hidden" name="id" id="grupoId" value="">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nombre del Grupo</label>
                        <input type="text" class="form-control" name="nombre" id="nombreGrupo" required>
                        <small class="text-muted">Ej: Grupo A, Grupo B, Sección 1, etc.</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Generación</label>
                        <select class="form-control" name="generacion_id" id="generacionId" required>
                            <option value="">Seleccionar generación</option>
                            <?php foreach($generaciones as $gen): ?>
                            <option value="<?php echo $gen['id']; ?>">
                                <?php echo htmlspecialchars($gen['nombre'] . ' (' . $gen['anio'] . ')'); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
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
function editarGrupo(grupo) {
    document.getElementById('modalTitulo').innerText = 'Editar Grupo';
    document.getElementById('accion').value = 'editar';
    document.getElementById('grupoId').value = grupo.id;
    document.getElementById('nombreGrupo').value = grupo.nombre;
    document.getElementById('generacionId').value = grupo.generacion_id;
    
    new bootstrap.Modal(document.getElementById('modalGrupo')).show();
}

function eliminarGrupo(id, nombre) {
    if(confirm(`¿Estás seguro de eliminar el grupo "${nombre}"?`)) {
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

// Resetear formulario cuando se cierra el modal
document.getElementById('modalGrupo').addEventListener('hidden.bs.modal', function () {
    document.getElementById('modalTitulo').innerText = 'Crear Nuevo Grupo';
    document.getElementById('accion').value = 'crear';
    document.getElementById('grupoId').value = '';
    document.getElementById('nombreGrupo').value = '';
    document.getElementById('generacionId').value = '';
});
</script>