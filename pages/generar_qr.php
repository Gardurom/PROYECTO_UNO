<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$mensaje = '';
$qr_img_path = '';
$contenido_qr = '';
$tamano_seleccionado = 5;

// Directorio temporal
$tempDir = __DIR__ . '/../temp_qr/';
if (!file_exists($tempDir)) {
    mkdir($tempDir, 0755, true);
}

// Función para limpiar archivos QR antiguos (opcional)
function cleanOldQRFiles($dir, $hours = 24) {
    $files = glob($dir . 'qr_*.png');
    $now = time();
    foreach ($files as $file) {
        if (is_file($file) && ($now - filemtime($file)) > ($hours * 3600)) {
            unlink($file);
        }
    }
}
cleanOldQRFiles($tempDir, 24);

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['contenido'])) {
    $contenido = trim($_POST['contenido']);
    $tamano_seleccionado = (int)($_POST['tamano'] ?? 5);
    $correccion = $_POST['correccion'] ?? 'L';
    
    // Construir URL de la API (tamaño en píxeles = tamano * 10)
    $size = $tamano_seleccionado * 10;
    $apiUrl = "https://api.qrserver.com/v1/create-qr-code/?size={$size}x{$size}&data=" . urlencode($contenido);
    
    // Nombre único para el archivo
    $filename = 'qr_' . md5($contenido . time()) . '.png';
    $filepath = $tempDir . $filename;
    
    // Descargar la imagen desde la API y guardarla localmente
    $imageData = file_get_contents($apiUrl);
    if ($imageData !== false && file_put_contents($filepath, $imageData)) {
        $qr_img_path = 'temp_qr/' . $filename;
        $contenido_qr = $contenido;
        $mensaje = '<div class="alert alert-success">✅ Código QR generado correctamente.</div>';
    } else {
        $mensaje = '<div class="alert alert-danger">❌ Error al generar el código QR. Intente de nuevo.</div>';
    }
}
?>
<div class="card">
    <div class="card-header">
        <h5><i class="fas fa-qrcode"></i> Generador de Códigos QR</h5>
    </div>
    <div class="card-body">
        <?php echo $mensaje; ?>
        
        <form method="POST" id="qrForm">
            <div class="mb-3">
                <label for="contenido" class="form-label">Contenido (texto, URL, email, etc.)</label>
                <textarea name="contenido" id="contenido" class="form-control" rows="3" required placeholder="Ej: https://www.ejemplo.com | Texto libre | tel:+525555123456"><?php echo htmlspecialchars($_POST['contenido'] ?? ''); ?></textarea>
            </div>
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="tamano" class="form-label">Tamaño</label>
                    <select name="tamano" id="tamano" class="form-select">
                        <option value="3" <?php echo ($tamano_seleccionado == 3) ? 'selected' : ''; ?>>Pequeño (30x30)</option>
                        <option value="5" <?php echo ($tamano_seleccionado == 5) ? 'selected' : ''; ?>>Mediano (50x50)</option>
                        <option value="8" <?php echo ($tamano_seleccionado == 8) ? 'selected' : ''; ?>>Grande (80x80)</option>
                        <option value="10" <?php echo ($tamano_seleccionado == 10) ? 'selected' : ''; ?>>Extra grande (100x100)</option>
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="correccion" class="form-label">Corrección de errores</label>
                    <select name="correccion" id="correccion" class="form-select">
                        <option value="L">L - 7%</option>
                        <option value="M" selected>M - 15%</option>
                        <option value="Q">Q - 25%</option>
                        <option value="H">H - 30%</option>
                    </select>
                </div>
            </div>
            
            <button type="submit" class="btn btn-custom w-100">
                <i class="fas fa-qrcode"></i> Generar Código QR
            </button>
        </form>
        
        <div id="qrResult" style="<?php echo $qr_img_path ? '' : 'display:none;'; ?> margin-top:20px;">
            <?php if($qr_img_path): ?>
                <hr>
                <div class="text-center">
                    <img src="<?php echo $qr_img_path; ?>" class="img-fluid border p-2 bg-white" style="max-width:200px; border-radius:10px;">
                    <p class="mt-2"><strong>Contenido:</strong><br> <?php echo nl2br(htmlspecialchars($contenido_qr)); ?></p>
                    <div class="d-flex justify-content-center gap-2">
                        <a href="<?php echo $qr_img_path; ?>" download="<?php echo basename($qr_img_path); ?>" class="btn btn-success">
                            <i class="fas fa-download"></i> Descargar PNG
                        </a>
                        <button type="button" class="btn btn-secondary" id="newQrBtn">
                            <i class="fas fa-plus-circle"></i> Generar otro QR
                        </button>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
    document.getElementById('newQrBtn')?.addEventListener('click', function() {
        document.getElementById('qrResult').style.display = 'none';
        document.getElementById('contenido').value = '';
        const alertDiv = document.querySelector('.alert');
        if(alertDiv) alertDiv.remove();
        document.getElementById('qrForm').reset();
        document.getElementById('contenido').focus();
        if (window.history.replaceState) {
            window.history.replaceState({}, document.title, window.location.pathname + '?page=generar_qr');
        }
    });
    
    document.getElementById('qrForm')?.addEventListener('submit', function() {
        const btn = this.querySelector('button[type="submit"]');
        if (btn) {
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Generando...';
        }
    });
</script>