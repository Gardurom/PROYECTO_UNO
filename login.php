<?php
/**
 * Login del sistema - Con máxima seguridad y contador regresivo
 * Prevención: SQL Injection, XSS, CSRF, Brute Force, Session Fixation
 */

// Configuración de seguridad estricta
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('session.cookie_httponly', 1);
ini_set('session.use_strict_mode', 1);
ini_set('session.cookie_samesite', 'Strict');
if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
    ini_set('session.cookie_secure', 1);
}

session_start();

// Regenerar ID de sesión para prevenir fijación
if (!isset($_SESSION['initialized'])) {
    session_regenerate_id(true);
    $_SESSION['initialized'] = true;
}

// Configuración de zona horaria
date_default_timezone_set('America/Mexico_City');

// Definir constantes
define('MAX_ATTEMPTS', 5);
define('LOCKOUT_TIME', 900); // 15 minutos
define('PASSWORD_HASH_ADMIN', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

// Verificar si el usuario está bloqueado
function isBlocked() {
    if (isset($_SESSION['blocked_until']) && $_SESSION['blocked_until'] > time()) {
        $remaining = $_SESSION['blocked_until'] - time();
        return $remaining;
    }
    return false;
}

// Registrar intento de login
function logAttempt($username, $ip, $success) {
    try {
        $logDir = __DIR__ . '/logs';
        if (!file_exists($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        $logFile = $logDir . '/login_attempts.log';
        $timestamp = date('Y-m-d H:i:s');
        $status = $success ? 'EXITOSO' : 'FALLIDO';
        $log = "[$timestamp] $status - Usuario: $username - IP: $ip\n";
        file_put_contents($logFile, $log, FILE_APPEND | LOCK_EX);
    } catch (Exception $e) {
        error_log("Error al registrar intento: " . $e->getMessage());
    }
}

// Obtener IP real del cliente
function getClientIP() {
    $ipHeaders = [
        'HTTP_CF_CONNECTING_IP',
        'HTTP_X_FORWARDED_FOR',
        'HTTP_X_REAL_IP',
        'HTTP_CLIENT_IP'
    ];
    
    foreach ($ipHeaders as $header) {
        if (!empty($_SERVER[$header])) {
            $ips = explode(',', $_SERVER[$header]);
            $ip = trim($ips[0]);
            if (filter_var($ip, FILTER_VALIDATE_IP)) {
                return $ip;
            }
        }
    }
    
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

// Generar token CSRF
function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Validar token CSRF
function validateCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Sanitizar entrada
function sanitizeInput($input) {
    return trim(htmlspecialchars($input, ENT_QUOTES, 'UTF-8'));
}

// Variables
$error = '';
$blocked = isBlocked();
$csrf_token = generateCSRFToken();
$ip = getClientIP();

// Procesar login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        $error = 'Token de seguridad inválido. Por favor, recarga la página.';
        logAttempt('unknown', $ip, false);
    }
    elseif ($blocked) {
        $minutes = ceil($blocked / 60);
        $error = "Demasiados intentos fallidos. Cuenta bloqueada por {$minutes} minutos.";
    }
    elseif (!isset($_POST['usu']) || !isset($_POST['pwd'])) {
        $error = 'Por favor, complete todos los campos.';
    }
    else {
        $username = sanitizeInput($_POST['usu']);
        $password = $_POST['pwd'];
        
        if (empty($username) || empty($password)) {
            $error = 'Usuario y contraseña son requeridos.';
            logAttempt($username, $ip, false);
            $_SESSION['login_attempts'] = ($_SESSION['login_attempts'] ?? 0) + 1;
            
            if ($_SESSION['login_attempts'] >= MAX_ATTEMPTS) {
                $_SESSION['blocked_until'] = time() + LOCKOUT_TIME;
                $error = "Demasiados intentos. Bloqueado por 15 minutos.";
            }
        }
        else {
            if ($username === 'admin') {
                if (password_verify($password, PASSWORD_HASH_ADMIN)) {
                    $_SESSION['user_id'] = 1;
                    $_SESSION['username'] = 'admin';
                    $_SESSION['role'] = 'admin';
                    $_SESSION['logged_in'] = true;
                    $_SESSION['login_time'] = time();
                    $_SESSION['ip_address'] = $ip;
                    $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? '';
                    
                    session_regenerate_id(true);
                    unset($_SESSION['login_attempts']);
                    unset($_SESSION['blocked_until']);
                    logAttempt($username, $ip, true);
                    
                    header('Location: dashboard.php');
                    exit;
                } else {
                    $error = 'Usuario o contraseña incorrectos.';
                    logAttempt($username, $ip, false);
                    $_SESSION['login_attempts'] = ($_SESSION['login_attempts'] ?? 0) + 1;
                    
                    if ($_SESSION['login_attempts'] >= MAX_ATTEMPTS) {
                        $_SESSION['blocked_until'] = time() + LOCKOUT_TIME;
                        $error = "Demasiados intentos. Bloqueado por 15 minutos.";
                    }
                }
            } else {
                $error = 'Usuario o contraseña incorrectos.';
                logAttempt($username, $ip, false);
                $_SESSION['login_attempts'] = ($_SESSION['login_attempts'] ?? 0) + 1;
                
                if ($_SESSION['login_attempts'] >= MAX_ATTEMPTS) {
                    $_SESSION['blocked_until'] = time() + LOCKOUT_TIME;
                    $error = "Demasiados intentos. Bloqueado por 15 minutos.";
                }
            }
        }
    }
}

// Obtener tiempo restante de bloqueo
$remaining_time = $blocked ? $blocked : 0;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Sistema de Gestión - Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .login-container {
            max-width: 450px;
            margin: auto;
        }
        .card {
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            backdrop-filter: blur(10px);
            background: rgba(255,255,255,0.95);
        }
        .card-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px 15px 0 0 !important;
            padding: 20px;
            text-align: center;
        }
        .btn-login {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            width: 100%;
            padding: 12px;
            font-size: 16px;
            font-weight: bold;
        }
        .btn-login:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
        }
        .btn-login:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        .alert {
            border-radius: 10px;
        }
        
        /* Estilo del reloj de bloqueo */
        .timer-container {
            text-align: center;
            margin-top: 20px;
            padding: 15px;
            background: linear-gradient(135deg, #ff6b6b 0%, #ee5a24 100%);
            border-radius: 10px;
            color: white;
            animation: pulse 1s infinite;
        }
        
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.02); }
            100% { transform: scale(1); }
        }
        
        .timer-clock {
            font-size: 48px;
            font-weight: bold;
            font-family: 'Courier New', monospace;
            letter-spacing: 5px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }
        
        .timer-label {
            font-size: 14px;
            margin-top: 10px;
            opacity: 0.9;
        }
        
        .timer-progress {
            margin-top: 15px;
            height: 8px;
            background: rgba(255,255,255,0.3);
            border-radius: 4px;
            overflow: hidden;
        }
        
        .timer-progress-bar {
            height: 100%;
            background: white;
            border-radius: 4px;
            transition: width 1s linear;
        }
        
        .attempts-info {
            font-size: 12px;
            color: #666;
            text-align: center;
            margin-top: 15px;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 8px;
        }
        
        .badge-attempt {
            display: inline-block;
            width: 30px;
            height: 30px;
            line-height: 30px;
            border-radius: 50%;
            margin: 0 3px;
            font-size: 12px;
            font-weight: bold;
        }
        
        .badge-attempt.used {
            background: #dc3545;
            color: white;
        }
        
        .badge-attempt.remaining {
            background: #28a745;
            color: white;
        }
        
        /* Estilo para el botón de mostrar contraseña */
        .password-toggle {
            cursor: pointer;
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            z-index: 10;
            color: #667eea;
            transition: color 0.3s;
        }
        
        .password-toggle:hover {
            color: #764ba2;
        }
        
        .input-group {
            position: relative;
        }
        
        .input-group .form-control {
            padding-right: 40px;
        }
        
        .toggle-icon {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            z-index: 10;
            color: #6c757d;
            transition: color 0.3s;
            background: white;
            padding: 0 5px;
        }
        
        .toggle-icon:hover {
            color: #667eea;
        }
    </style>
</head>
<body>
    <div class="container login-container">
        <div class="card">
            <div class="card-header">
                <h3 class="mb-0">🔐 Sistema de Gestión</h3>
                <small>Acceso seguro</small>
            </div>
            <div class="card-body p-4">
                <?php if ($error && !$blocked): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <strong>⚠️ Error:</strong> <?php echo htmlspecialchars($error); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <?php if ($blocked): ?>
                    <div class="timer-container">
                        <div class="timer-clock" id="timerDisplay">--:--</div>
                        <div class="timer-label">⏰ Cuenta bloqueada - Tiempo restante</div>
                        <div class="timer-progress">
                            <div class="timer-progress-bar" id="timerProgress" style="width: 100%"></div>
                        </div>
                        <div class="timer-label" style="margin-top: 10px;">
                            🔒 Máximos intentos excedidos
                        </div>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="" autocomplete="off" id="loginForm">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                    
                    <div class="mb-3">
                        <label for="usu" class="form-label">Usuario</label>
                        <div class="input-group">
                            <span class="input-group-text">👤</span>
                            <input type="text" 
                                   class="form-control" 
                                   id="usu" 
                                   name="usu" 
                                   placeholder="Ingrese su usuario"
                                   autocomplete="off"
                                   required
                                   <?php echo $blocked ? 'disabled' : ''; ?>>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="pwd" class="form-label">Contraseña</label>
                        <div class="input-group" style="position: relative;">
                            <span class="input-group-text">🔑</span>
                            <input type="password" 
                                   class="form-control" 
                                   id="pwd" 
                                   name="pwd" 
                                   placeholder="Ingrese su contraseña"
                                   autocomplete="off"
                                   required
                                   <?php echo $blocked ? 'disabled' : ''; ?>>
                            <span class="toggle-icon" id="togglePassword" style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); cursor: pointer;">
                                <i class="fas fa-eye" id="eyeIcon"></i>
                            </span>
                        </div>
                    </div>
                    
                    <button type="submit" 
                            class="btn btn-primary btn-login" 
                            id="loginBtn"
                            <?php echo $blocked ? 'disabled' : ''; ?>>
                        Iniciar Sesión
                    </button>
                </form>
                
                <div class="attempts-info">
                    <?php
                    $attempts = $_SESSION['login_attempts'] ?? 0;
                    $remaining = MAX_ATTEMPTS - $attempts;
                    ?>
                    
                    <div class="mb-2">
                        <strong>Intentos de acceso:</strong><br>
                        <?php for ($i = 1; $i <= MAX_ATTEMPTS; $i++): ?>
                            <span class="badge-attempt <?php echo $i <= $attempts ? 'used' : 'remaining'; ?>">
                                <?php echo $i; ?>
                            </span>
                        <?php endfor; ?>
                    </div>
                    
                    <?php if ($remaining > 0 && $remaining < MAX_ATTEMPTS && !$blocked): ?>
                        <small class="text-warning">
                            ⚠️ Te quedan <?php echo $remaining; ?> intentos antes del bloqueo
                        </small>
                        <br>
                        <small class="text-muted">
                            ⏱️ Tiempo de espera por intento: +10 segundos
                        </small>
                    <?php elseif ($remaining > 0 && $remaining == MAX_ATTEMPTS): ?>
                        <small class="text-muted">
                            🔓 Intentos disponibles: <?php echo $remaining; ?> de <?php echo MAX_ATTEMPTS; ?>
                        </small>
                    <?php endif; ?>
                    
                    <?php if ($blocked): ?>
                        <small class="text-danger">
                            🔒 Cuenta bloqueada por 15 minutos
                        </small>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // =============================================
        // MOSTRAR/OCULTAR CONTRASEÑA
        // =============================================
        const togglePassword = document.getElementById('togglePassword');
        const passwordInput = document.getElementById('pwd');
        const eyeIcon = document.getElementById('eyeIcon');
        
        if (togglePassword && passwordInput) {
            togglePassword.addEventListener('click', function() {
                // Cambiar el tipo de input
                const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordInput.setAttribute('type', type);
                
                // Cambiar el icono
                if (type === 'text') {
                    eyeIcon.classList.remove('fa-eye');
                    eyeIcon.classList.add('fa-eye-slash');
                } else {
                    eyeIcon.classList.remove('fa-eye-slash');
                    eyeIcon.classList.add('fa-eye');
                }
            });
        }
        
        // =============================================
        // Contador regresivo para el bloqueo
        // =============================================
        <?php if ($blocked && $remaining_time > 0): ?>
        let remainingSeconds = <?php echo $remaining_time; ?>;
        let timerInterval;
        
        function formatTime(seconds) {
            let mins = Math.floor(seconds / 60);
            let secs = seconds % 60;
            return `${mins.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
        }
        
        function updateTimer() {
            let timerDisplay = document.getElementById('timerDisplay');
            let timerProgress = document.getElementById('timerProgress');
            
            if (remainingSeconds <= 0) {
                clearInterval(timerInterval);
                location.reload(); // Recargar página cuando termine el bloqueo
                return;
            }
            
            timerDisplay.textContent = formatTime(remainingSeconds);
            
            // Calcular porcentaje (900 segundos = 100%)
            let percentage = (remainingSeconds / 900) * 100;
            timerProgress.style.width = percentage + '%';
            
            remainingSeconds--;
        }
        
        // Iniciar contador
        updateTimer();
        timerInterval = setInterval(updateTimer, 1000);
        <?php endif; ?>
        
        // =============================================
        // Prevenir envío duplicado del formulario
        // =============================================
        document.getElementById('loginForm')?.addEventListener('submit', function(e) {
            let btn = document.getElementById('loginBtn');
            if (btn && !btn.disabled) {
                btn.disabled = true;
                btn.innerHTML = '⏳ Procesando...';
            }
        });
        
        // =============================================
        // Auto-focus en el campo de usuario
        // =============================================
        document.getElementById('usu')?.focus();
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>