<div class="row">
    <div class="col-md-12">
        <div class="alert alert-success">
            <h4>✅ Inicio de sesión exitoso</h4>
            <p>Bienvenido al sistema de gestión.</p>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-4">
        <div class="stats-card">
            <h5><i class="fas fa-map-marker-alt"></i> Coordenadas Activas</h5>
            <h2 id="totalCoordenadas">0</h2>
            <p>Puntos cargados en el mapa</p>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="stats-card">
            <h5><i class="fas fa-calendar-alt"></i> Último Acceso</h5>
            <h2><?php echo date('d/m/Y'); ?></h2>
            <p><?php echo date('H:i:s'); ?></p>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="stats-card">
            <h5><i class="fas fa-user"></i> Usuario</h5>
            <h2><?php echo htmlspecialchars($_SESSION['username']); ?></h2>
            <p>Rol: <?php echo htmlspecialchars($_SESSION['role']); ?></p>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h5>Información de sesión</h5>
            </div>
            <div class="card-body">
                <ul>
                    <li>Usuario: <?php echo htmlspecialchars($_SESSION['username']); ?></li>
                    <li>Rol: <?php echo htmlspecialchars($_SESSION['role']); ?></li>
                    <li>IP: <?php echo htmlspecialchars($_SESSION['ip_address']); ?></li>
                    <li>Login: <?php echo date('Y-m-d H:i:s', $_SESSION['login_time']); ?></li>
                </ul>
            </div>
        </div>
    </div>
</div>

<script>
// Cargar total de coordenadas al dashboard
fetch('ajax/get_markers.php')
    .then(response => response.json())
    .then(data => {
        if (data.success && data.markers) {
            document.getElementById('totalCoordenadas').textContent = data.markers.length;
        }
    });
</script>