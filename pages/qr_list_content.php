
<?php
// pages/qr_list_content.php
$db = getDB();

$asignaciones_con_qr = $db->query("
    SELECT 
        md.id,
        m.nombre as materia_nombre,
        d.nombre as docente_nombre,
        d.apellidos as docente_apellidos,
        md.periodo,
        g.nombre as grupo_nombre
    FROM materia_docente md
    JOIN materias m ON md.materia_id = m.id
    JOIN docentes d ON md.docente_id = d.id
    LEFT JOIN grupos g ON md.grupo_id = g.id
    WHERE md.activo = 1
    ORDER BY md.periodo DESC, m.nombre
")->fetchAll();
?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col">
            <h2><i class="fas fa-qrcode"></i> Códigos QR para Evaluaciones</h2>
            <p class="text-muted">Códigos QR para acceso rápido a las evaluaciones</p>
        </div>
    </div>

    <div class="row">
        <?php foreach($asignaciones_con_qr as $asignacion): ?>
        <div class="col-md-3 mb-3">
            <div class="card">
                <div class="card-body text-center">
                    <?php 
                    $url = (isset($_SERVER['HTTPS']) ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . '/evaluar.php?asignacion=' . $asignacion['id'];
                    ?>
                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=<?php echo urlencode($url); ?>" 
                         alt="QR <?php echo htmlspecialchars($asignacion['materia_nombre']); ?>"
                         class="img-fluid">
                    <h6 class="mt-2"><?php echo htmlspecialchars($asignacion['materia_nombre']); ?></h6>
                    <p class="text-muted small">
                        <?php echo htmlspecialchars($asignacion['docente_nombre'] . ' ' . $asignacion['docente_apellidos']); ?>
                        <br>
                        <span class="badge bg-info"><?php echo htmlspecialchars($asignacion['periodo']); ?></span>
                        <?php if($asignacion['grupo_nombre']): ?>
                            <span class="badge bg-secondary"><?php echo htmlspecialchars($asignacion['grupo_nombre']); ?></span>
                        <?php endif; ?>
                    </p>
                    <a href="<?php echo $url; ?>" target="_blank" class="btn btn-sm btn-primary">
                        <i class="fas fa-external-link-alt"></i> Abrir
                    </a>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>