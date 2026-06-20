<?php
// headers UTF-8
header('Content-Type: text/html; charset=utf-8');
ini_set('default_charset', 'UTF-8');
mb_internal_encoding('UTF-8');
mb_http_output('UTF-8');
// pages/generaciones_content.php
$db = getDB();
$generaciones = $db->query("SELECT * FROM generaciones ORDER BY anio DESC, nombre")->fetchAll();
?>
<div class="container">
    <h2>Gestionar Generaciones</h2>
    <div class="table-responsive">
        <table class="table datatable">
            <thead>
                <tr><th>ID</th><th>Nombre</th><th>Año</th><th>Estado</th><th>Fecha</th></tr>
            </thead>
            <tbody>
                <?php foreach($generaciones as $g): ?>
                <tr><td><?php echo $g['id']; ?></td><td><?php echo htmlspecialchars($g['nombre']); ?></td>
                <td><?php echo $g['anio']; ?></td><td><?php echo $g['activo'] ? 'Activo' : 'Inactivo'; ?></td>
                <td><?php echo date('d/m/Y', strtotime($g['created_at'])); ?></td></tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>