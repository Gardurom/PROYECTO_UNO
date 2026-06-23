<?php
// evaluar.php - Página pública para que los alumnos evalúen
header('Content-Type: text/html; charset=utf-8');
ini_set('default_charset', 'UTF-8');
mb_internal_encoding('UTF-8');
session_start();

require_once 'includes/database.php';
$db = getDB();

$mensaje = '';
$error = '';
$alumno = null;
$materias_pendientes = [];
$alumno_id = null;

// Procesar verificación de matrícula
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['verificar_matricula'])) {
        $matricula = trim($_POST['matricula']);
        
        if (empty($matricula)) {
            $error = 'Por favor, ingresa tu matrícula.';
        } else {
            // Buscar alumno por matrícula
            $stmt = $db->prepare("
                SELECT a.*, g.nombre as generacion_nombre, gr.nombre as grupo_nombre
                FROM alumnos a
                JOIN generaciones g ON a.generacion_id = g.id
                JOIN grupos gr ON a.grupo_id = gr.id
                WHERE a.matricula = ? AND a.activo = 1
            ");
            $stmt->execute([$matricula]);
            $alumno = $stmt->fetch();
            
            if ($alumno) {
                $_SESSION['alumno_id'] = $alumno['id'];
                $_SESSION['alumno_matricula'] = $alumno['matricula'];
                $_SESSION['alumno_nombre'] = $alumno['nombre'] . ' ' . $alumno['apellidos'];
                
                // Obtener materias pendientes
                $stmt = $db->prepare("
                    SELECT 
                        md.id as materia_docente_id,
                        m.nombre as materia_nombre,
                        m.clave,
                        d.nombre as docente_nombre,
                        d.apellidos as docente_apellidos,
                        g.nombre as generacion_nombre,
                        gr.nombre as grupo_nombre,
                        md.periodo
                    FROM materia_docente md
                    JOIN materias m ON md.materia_id = m.id
                    JOIN docentes d ON md.docente_id = d.id
                    JOIN alumnos a ON a.id = ?
                    JOIN grupos gr ON a.grupo_id = gr.id
                    JOIN generaciones g ON gr.generacion_id = g.id
                    WHERE md.activo = 1
                    AND a.activo = 1
                    AND NOT EXISTS (
                        SELECT 1 FROM evaluaciones e 
                        WHERE e.materia_docente_id = md.id 
                        AND e.alumno_id = a.id
                    )
                ");
                $stmt->execute([$alumno['id']]);
                $materias_pendientes = $stmt->fetchAll();
                
                if (empty($materias_pendientes)) {
                    $mensaje = '✅ ¡Has completado todas tus evaluaciones! Gracias por tu participación.';
                } else {
                    $mensaje = '✅ Bienvenido ' . $alumno['nombre'] . ', tienes ' . count($materias_pendientes) . ' materias por evaluar.';
                }
            } else {
                $error = '❌ Matrícula no encontrada. Por favor, verifica tu matrícula.';
            }
        }
    }
    
    // Guardar evaluación
    if (isset($_POST['guardar_evaluacion'])) {
        $alumno_id = $_SESSION['alumno_id'] ?? null;
        $materia_docente_id = intval($_POST['materia_docente_id']);
        $puntualidad = intval($_POST['puntualidad']);
        $resolucion = intval($_POST['resolucion']);
        $comentario = trim($_POST['comentario'] ?? '');
        
        if (!$alumno_id) {
            $error = '❌ Sesión expirada. Por favor, verifica tu matrícula nuevamente.';
        } else {
            try {
                $stmt = $db->prepare("
                    INSERT INTO evaluaciones (alumno_id, materia_docente_id, puntualidad_asistencia, resolvio_dudas, comentario, ip_address, user_agent)
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $alumno_id,
                    $materia_docente_id,
                    $puntualidad,
                    $resolucion,
                    $comentario,
                    $_SERVER['REMOTE_ADDR'],
                    $_SERVER['HTTP_USER_AGENT']
                ]);
                
                $mensaje = '✅ ¡Evaluación guardada exitosamente! Gracias por tu participación.';
                
                // Redirigir para evitar reenvío del formulario
                header('Location: evaluar.php?success=1');
                exit;
                
            } catch (PDOException $e) {
                if (strpos($e->getMessage(), 'UNIQUE constraint failed') !== false) {
                    $error = '❌ Ya has evaluado esta materia anteriormente.';
                } else {
                    $error = '❌ Error al guardar la evaluación: ' . $e->getMessage();
                }
            }
        }
    }
}

// Si viene con parámetro de éxito
if (isset($_GET['success'])) {
    $mensaje = '✅ ¡Evaluación guardada exitosamente! Gracias por tu participación.';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Evaluación Docente</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .main-container {
            max-width: 900px;
            margin: 0 auto;
            padding: 20px;
        }
        .card {
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            backdrop-filter: blur(10px);
            background: rgba(255,255,255,0.95);
            margin-bottom: 20px;
        }
        .card-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px 15px 0 0 !important;
            padding: 20px;
        }
        .btn-evaluar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            color: white;
            padding: 10px 30px;
            font-weight: bold;
        }
        .btn-evaluar:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
            color: white;
        }
        .rating {
            display: flex;
            flex-direction: row-reverse;
            justify-content: flex-end;
            gap: 8px;
        }
        .rating input {
            display: none;
        }
        .rating label {
            font-size: 2rem;
            padding: 0 8px;
            color: #ddd;
            cursor: pointer;
            transition: all 0.2s;
            background: #f8f9fa;
            border-radius: 5px;
            min-width: 45px;
            text-align: center;
        }
        .rating input:checked ~ label {
            color: #ffc107;
            background: #fff3cd;
        }
        .rating label:hover,
        .rating label:hover ~ label {
            color: #ffc107;
            transform: scale(1.1);
        }
        .header-logo {
            text-align: center;
            padding: 30px 0;
            color: white;
        }
        .header-logo h1 {
            font-size: 2.5rem;
            font-weight: bold;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }
        .header-logo p {
            font-size: 1.1rem;
            opacity: 0.9;
        }
        .matricula-input {
            max-width: 400px;
            margin: 0 auto;
        }
        .matricula-input input {
            border-radius: 10px 0 0 10px;
            padding: 15px;
            font-size: 1.1rem;
        }
        .matricula-input button {
            border-radius: 0 10px 10px 0;
            padding: 15px 30px;
        }
        .alumno-info {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
        }
        .qr-container {
            text-align: center;
            padding: 20px;
            background: white;
            border-radius: 10px;
            border: 2px dashed #667eea;
        }
        .qr-container img {
            max-width: 200px;
            margin: 10px auto;
        }
        .nav-links {
            margin-top: 20px;
            text-align: center;
        }
        .nav-links a {
            color: white;
            text-decoration: none;
            margin: 0 15px;
            opacity: 0.8;
            transition: opacity 0.3s;
        }
        .nav-links a:hover {
            opacity: 1;
        }
        .evaluacion-card {
            border-left: 4px solid #667eea;
            transition: transform 0.3s;
        }
        .evaluacion-card:hover {
            transform: translateX(5px);
        }
        .progress-bar-custom {
            height: 8px;
            border-radius: 4px;
            background: #e9ecef;
            margin: 10px 0;
        }
        .progress-bar-custom .progress-fill {
            height: 100%;
            border-radius: 4px;
            background: linear-gradient(90deg, #667eea, #764ba2);
            transition: width 0.5s;
        }
        @media (max-width: 768px) {
            .header-logo h1 {
                font-size: 1.8rem;
            }
            .rating label {
                font-size: 1.5rem;
                min-width: 35px;
                padding: 0 5px;
            }
        }
    </style>
</head>
<body>
    <div class="main-container">
        <!-- Header -->
        <div class="header-logo">
            <h1><i class="fas fa-chalkboard-teacher"></i> Evaluación Docente</h1>
            <p>Tu opinión es importante para mejorar la calidad educativa</p>
        </div>

        <!-- Mensajes -->
        <?php if ($mensaje): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle"></i> <?php echo $mensaje; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-triangle"></i> <?php echo $error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Verificar Matrícula -->
        <?php if (!isset($_SESSION['alumno_id']) || empty($materias_pendientes)): ?>
        <div class="card">
            <div class="card-body text-center">
                <h4><i class="fas fa-user-graduate"></i> Identifícate</h4>
                <p class="text-muted">Ingresa tu matrícula para comenzar tu evaluación</p>
                
                <form method="POST" class="matricula-input">
                    <div class="input-group">
                        <input type="text" 
                               class="form-control" 
                               name="matricula" 
                               placeholder="Ingresa tu matrícula (ej: A001)"
                               required
                               autofocus>
                        <button type="submit" name="verificar_matricula" class="btn btn-evaluar">
                            <i class="fas fa-arrow-right"></i> Verificar
                        </button>
                    </div>
                    <small class="text-muted mt-2 d-block">
                        <i class="fas fa-info-circle"></i> Tu matrícula es proporcionada por tu institución
                    </small>
                </form>

                <!-- QR para escanear -->
                <hr>
                <div class="row">
                    <div class="col-md-6 mx-auto">
                        <div class="qr-container">
                            <i class="fas fa-qrcode fa-3x text-primary"></i>
                            <h6 class="mt-2">O escanea el código QR</h6>
                            <p class="text-muted small">Si tienes un código QR de evaluación, escanéalo con tu dispositivo</p>
                            <form method="GET" action="evaluar.php">
                                <div class="input-group">
                                    <input type="text" class="form-control" name="qr" placeholder="Código QR">
                                    <button class="btn btn-outline-primary" type="submit">
                                        <i class="fas fa-search"></i>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Materias por Evaluar -->
        <?php if (isset($_SESSION['alumno_id']) && !empty($materias_pendientes)): ?>
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-book"></i> Materias por Evaluar
                        <span class="badge bg-light text-dark float-end">
                            <?php echo count($materias_pendientes); ?> pendientes
                        </span>
                    </h5>
                </div>
                <div class="card-body">
                    <div class="alumno-info">
                        <div class="row">
                            <div class="col-md-6">
                                <strong><i class="fas fa-user"></i> Alumno:</strong>
                                <?php echo htmlspecialchars($_SESSION['alumno_nombre']); ?>
                            </div>
                            <div class="col-md-6">
                                <strong><i class="fas fa-id-card"></i> Matrícula:</strong>
                                <?php echo htmlspecialchars($_SESSION['alumno_matricula']); ?>
                            </div>
                        </div>
                    </div>

                    <?php foreach ($materias_pendientes as $materia): ?>
                        <div class="card evaluacion-card mb-3">
                            <div class="card-body">
                                <form method="POST" onsubmit="return confirm('¿Estás seguro de enviar esta evaluación?');">
                                    <input type="hidden" name="materia_docente_id" value="<?php echo $materia['materia_docente_id']; ?>">
                                    <input type="hidden" name="alumno_id" value="<?php echo $_SESSION['alumno_id']; ?>">
                                    
                                    <div class="row">
                                        <div class="col-md-8">
                                            <h6 class="mb-1">
                                                <?php echo htmlspecialchars($materia['materia_nombre']); ?>
                                                <span class="badge bg-secondary"><?php echo htmlspecialchars($materia['clave']); ?></span>
                                            </h6>
                                            <p class="text-muted small mb-1">
                                                <i class="fas fa-chalkboard-user"></i> Docente: <?php echo htmlspecialchars($materia['docente_nombre'] . ' ' . $materia['docente_apellidos']); ?>
                                            </p>
                                            <p class="text-muted small">
                                                <i class="fas fa-calendar"></i> Periodo: <?php echo htmlspecialchars($materia['periodo']); ?>
                                                | <i class="fas fa-users"></i> <?php echo htmlspecialchars($materia['generacion_nombre'] . ' - ' . $materia['grupo_nombre']); ?>
                                            </p>
                                        </div>
                                        <div class="col-md-4 text-md-end">
                                            <span class="badge bg-warning text-dark">Pendiente</span>
                                        </div>
                                    </div>
                                    
                                    <hr>
                                    
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label fw-bold">
                                                <i class="fas fa-clock text-success"></i> Asistencia y Puntualidad
                                            </label>
                                            <div class="rating">
                                                <?php for($i = 9; $i >= 0; $i--): ?>
                                                    <input type="radio" name="puntualidad" value="<?php echo $i; ?>" 
                                                           id="puntualidad_<?php echo $materia['materia_docente_id']; ?>_<?php echo $i; ?>" required>
                                                    <label for="puntualidad_<?php echo $materia['materia_docente_id']; ?>_<?php echo $i; ?>" 
                                                           title="<?php echo $i; ?> puntos"><?php echo $i; ?></label>
                                                <?php endfor; ?>
                                            </div>
                                            <small class="text-muted">0 = Muy malo, 9 = Excelente</small>
                                        </div>
                                        
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label fw-bold">
                                                <i class="fas fa-question-circle text-info"></i> Resolución de Dudas
                                            </label>
                                            <div class="rating">
                                                <?php for($i = 9; $i >= 0; $i--): ?>
                                                    <input type="radio" name="resolucion" value="<?php echo $i; ?>" 
                                                           id="resolucion_<?php echo $materia['materia_docente_id']; ?>_<?php echo $i; ?>" required>
                                                    <label for="resolucion_<?php echo $materia['materia_docente_id']; ?>_<?php echo $i; ?>" 
                                                           title="<?php echo $i; ?> puntos"><?php echo $i; ?></label>
                                                <?php endfor; ?>
                                            </div>
                                            <small class="text-muted">0 = Muy malo, 9 = Excelente</small>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Comentarios adicionales (opcional)</label>
                                        <textarea class="form-control" name="comentario" rows="2" 
                                                  placeholder="Escribe aquí tus comentarios sobre el docente..."></textarea>
                                    </div>
                                    
                                    <button type="submit" name="guardar_evaluacion" class="btn btn-evaluar">
                                        <i class="fas fa-paper-plane"></i> Enviar Evaluación
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Nav Links -->
        <div class="nav-links">
            <a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
            <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Cerrar Sesión</a>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Animación para las calificaciones
        document.querySelectorAll('.rating label').forEach(label => {
            label.addEventListener('mouseenter', function() {
                this.style.transform = 'scale(1.15)';
            });
            label.addEventListener('mouseleave', function() {
                this.style.transform = 'scale(1)';
            });
        });
    </script>
</body>
</html>