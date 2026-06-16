<?php
// pages/alumnos_content.php
$db = getDB();

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['accion'])) {
        if ($_POST['accion'] === 'crear') {
            $matricula = trim($_POST['matricula']);
            $nombre = trim($_POST['nombre']);
            $apellidos = trim($_POST['apellidos']);
            $email = trim($_POST['email']);
            $generacion_id = intval($_POST['generacion_id']);
            $grupo_id = intval($_POST['grupo_id']);
            
            try {
                $stmt = $db->prepare("INSERT INTO alumnos (matricula, nombre, apellidos, email, generacion_id, grupo_id) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$matricula, $nombre, $apellidos, $email, $generacion_id, $grupo_id]);
                $mensaje = "Alumno creado exitosamente";
            } catch (PDOException $e) {
                $error = "Error al crear alumno: " . $e->getMessage();
            }
        } 
        elseif ($_POST['accion'] === 'editar') {
            $id = intval($_POST['id']);
            $matricula = trim($_POST['matricula']);
            $nombre = trim($_POST['nombre']);
            $apellidos = trim($_POST['apellidos']);
            $email = trim($_POST['email']);
            $generacion_id = intval($_POST['generacion_id']);
            $grupo_id = intval($_POST['grupo_id']);
            
            try {
                $stmt = $db->prepare("UPDATE alumnos SET matricula = ?, nombre = ?, apellidos = ?, email = ?, generacion_id = ?, grupo_id = ? WHERE id = ?");
                $stmt->execute([$matricula, $nombre, $apellidos, $email, $generacion_id, $grupo_id, $id]);
                $mensaje = "Alumno actualizado exitosamente";
            } catch (PDOException $e) {
                $error = "Error al actualizar alumno: " . $e->getMessage();
            }
        }
        elseif ($_POST['accion'] === 'eliminar') {
            $id = intval($_POST['id']);
            try {
                $stmt = $db->prepare("UPDATE alumnos SET activo = 0 WHERE id = ?");
                $stmt->execute([$id]);
                $mensaje = "Alumno eliminado exitosamente";
            } catch (PDOException $e) {
                $error = "Error al eliminar alumno: " . $e->getMessage();
            }
        }
    }
}

// Obtener listado de alumnos
$alumnos = $db->query("
    SELECT a.*, 
           g.nombre as generacion_nombre, 
           gr.nombre as grupo_nombre,
           COUNT(DISTINCT e.id) as total_evaluaciones
    FROM alumnos a
    JOIN generaciones g ON a.generacion_id = g.id
    JOIN grupos gr ON a.grupo_id = gr.id
    LEFT JOIN evaluaciones e ON a.id = e.alumno_id
    WHERE a.activo = 1
    GROUP BY a.id
    ORDER BY a.created_at DESC
")->fetchAll();

// Obtener generaciones y grupos para selects
$generaciones = $db->query("SELECT * FROM generaciones WHERE activo = 1 ORDER BY anio DESC")->fetchAll();
$grupos = $db->query("SELECT * FROM grupos WHERE activo = 1 ORDER BY nombre")->fetchAll();
?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col">
            <h2><i class="fas fa-user-graduate"></i> Gestión de Alumnos</h2>
            <p class="text-muted">Administra los alumnos y visualiza su progreso</p>
        </div>
        <div class="col text-end">
            <button type="button" class="btn btn-custom" data-bs-toggle="modal" data-bs-target="#modalAlumno">
                <i class="fas fa-plus"></i> Nuevo Alumno
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
            <h5><i class="fas fa-list"></i> Lista de Alumnos</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped datatable">
                    <thead>
                        <tr>
                            <th>Matrícula</th>
                            <th>Nombre Completo</th>
                            <th>Email</th>
                            <th>Generación</th>
                            <th>Grupo</th>
                            <th>Evaluaciones</th>
                            <th>Fecha Registro</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($alumnos as $alumno): ?>
                        <tr>
                            <td><code><?php echo htmlspecialchars($alumno['matricula']); ?></code></td>
                            <td><strong><?php echo htmlspecialchars($alumno['nombre'] . ' ' . $alumno['apellidos']); ?></strong></td>
                            <td><?php echo htmlspecialchars($alumno['email'] ?: 'N/A'); ?></td>
                            <td><span class="badge bg-secondary"><?php echo htmlspecialchars($alumno['generacion_nombre']); ?></span></td>
                            <td><span class="badge bg-info"><?php echo htmlspecialchars($alumno['grupo_nombre']); ?></span></td>
                            <td><span class="badge bg-primary"><?php echo $alumno['total_evaluaciones']; ?></span></td>
                            <td><?php echo date('d/m/Y', strtotime($alumno['created_at'])); ?></td>
                            <td>
                                <button class="btn btn-sm btn-warning" onclick="editarAlumno(<?php echo htmlspecialchars(json_encode($alumno)); ?>)">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-sm btn-danger" onclick="eliminarAlumno(<?php echo $alumno['id']; ?>, '<?php echo htmlspecialchars($alumno['nombre'] . ' ' . $alumno['apellidos']); ?>')">
                                    <i class="fas fa-trash"></i>
                                </button>
                                <button class="btn btn-sm btn-success" onclick="verEvaluaciones(<?php echo $alumno['id']; ?>)">
                                    <i class="fas fa-star"></i>
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

<!-- Modal Alumno -->
<div class="modal fade" id="modalAlumno" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalAlumnoTitulo">Crear Nuevo Alumno</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="formAlumno">
                <input type="hidden" name="accion" id="alumnoAccion" value="crear">
                <input type="hidden" name="id" id="alumnoId" value="">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Matrícula</label>
                        <input type="text" class="form-control" name="matricula" id="matriculaAlumno" required>
                        <small class="text-muted">Identificador único del alumno</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nombre(s)</label>
                        <input type="text" class="form-control" name="nombre" id="nombreAlumno" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Apellidos</label>
                        <input type="text" class="form-control" name="apellidos" id="apellidosAlumno" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Correo Electrónico</label>
                        <input type="email" class="form-control" name="email" id="emailAlumno">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Generación</label>
                        <select class="form-control" name="generacion_id" id="generacionAlumno" required>
                            <option value="">Seleccionar generación</option>
                            <?php foreach($generaciones as $gen): ?>
                            <option value="<?php echo $gen['id']; ?>">
                                <?php echo htmlspecialchars($gen['nombre'] . ' (' . $gen['anio'] . ')'); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Grupo</label>
                        <select class="form-control" name="grupo_id" id="grupoAlumno" required>
                            <option value="">Seleccionar grupo</option>
                            <?php foreach($grupos as $gr): ?>
                            <option value="<?php echo $gr['id']; ?>">
                                <?php echo htmlspecialchars($gr['nombre']); ?>
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

<!-- Modal Ver Evaluaciones -->
<div class="modal fade" id="modalEvaluaciones" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Evaluaciones Realizadas</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="contenidoEvaluaciones">
                Cargando...
            </div>
        </div>
    </div>
</div>

<script>
function editarAlumno(alumno) {
    document.getElementById('modalAlumnoTitulo').innerText = 'Editar Alumno';
    document.getElementById('alumnoAccion').value = 'editar';
    document.getElementById('alumnoId').value = alumno.id;
    document.getElementById('matriculaAlumno').value = alumno.matricula;
    document.getElementById('nombreAlumno').value = alumno.nombre;
    document.getElementById('apellidosAlumno').value = alumno.apellidos;
    document.getElementById('emailAlumno').value = alumno.email;
    document.getElementById('generacionAlumno').value = alumno.generacion_id;
    document.getElementById('grupoAlumno').value = alumno.grupo_id;
    
    new bootstrap.Modal(document.getElementById('modalAlumno')).show();
}

function eliminarAlumno(id, nombre) {
    if(confirm(`¿Estás seguro de eliminar al alumno "${nombre}"?`)) {
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

function verEvaluaciones(id) {
    fetch(`ajax/ver_evaluaciones_alumno.php?id=${id}`)
        .then(response => response.text())
        .then(html => {
            document.getElementById('contenidoEvaluaciones').innerHTML = html;
            new bootstrap.Modal(document.getElementById('modalEvaluaciones')).show();
        });
}

document.getElementById('modalAlumno').addEventListener('hidden.bs.modal', function () {
    document.getElementById('modalAlumnoTitulo').innerText = 'Crear Nuevo Alumno';
    document.getElementById('alumnoAccion').value = 'crear';
    document.getElementById('alumnoId').value = '';
    document.getElementById('matriculaAlumno').value = '';
    document.getElementById('nombreAlumno').value = '';
    document.getElementById('apellidosAlumno').value = '';
    document.getElementById('emailAlumno').value = '';
    document.getElementById('generacionAlumno').value = '';
    document.getElementById('grupoAlumno').value = '';
});
</script>