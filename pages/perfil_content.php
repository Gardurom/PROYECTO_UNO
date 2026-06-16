<div class="card">
    <div class="card-header">
        <h5><i class="fas fa-user"></i> Mi Perfil</h5>
    </div>
    <div class="card-body">
        <form id="profileForm">
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Usuario</label>
                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($_SESSION['username']); ?>" readonly>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Rol</label>
                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($_SESSION['role']); ?>" readonly>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">IP de acceso</label>
                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($_SESSION['ip_address']); ?>" readonly>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Último acceso</label>
                        <input type="text" class="form-control" value="<?php echo date('Y-m-d H:i:s', $_SESSION['login_time']); ?>" readonly>
                    </div>
                </div>
            </div>
            
            <hr>
            <h6>Cambiar Contraseña</h6>
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Nueva Contraseña</label>
                        <input type="password" class="form-control" id="new_password">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Confirmar Contraseña</label>
                        <input type="password" class="form-control" id="confirm_password">
                    </div>
                </div>
            </div>
            
            <button type="submit" class="btn btn-custom">Actualizar Perfil</button>
        </form>
    </div>
</div>