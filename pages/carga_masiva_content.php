<?php
// pages/carga_masiva_content.php
?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col">
            <h2><i class="fas fa-upload"></i> Carga Masiva de Datos</h2>
            <p class="text-muted">Importa alumnos, docentes y materias desde archivos CSV</p>
        </div>
    </div>

    <div class="row">
        <!-- Carga de Alumnos -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-user-graduate"></i> Cargar Alumnos</h5>
                </div>
                <div class="card-body">
                    <div class="upload-area" onclick="document.getElementById('fileAlumnos').click()">
                        <i class="fas fa-cloud-upload-alt fa-3x mb-3"></i>
                        <p>Haz clic para seleccionar un archivo CSV</p>
                        <small class="text-muted">Formato: .csv</small>
                        <input type="file" id="fileAlumnos" accept=".csv" style="display: none;">
                    </div>
                    <div class="mt-3">
                        <button class="btn btn-sm btn-outline-primary" onclick="descargarPlantilla('alumnos')">
                            <i class="fas fa-download"></i> Descargar plantilla
                        </button>
                    </div>
                    <div id="resultadoAlumnos" class="mt-3"></div>
                </div>
            </div>
        </div>

        <!-- Carga de Docentes -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="fas fa-chalkboard-user"></i> Cargar Docentes</h5>
                </div>
                <div class="card-body">
                    <div class="upload-area" onclick="document.getElementById('fileDocentes').click()">
                        <i class="fas fa-cloud-upload-alt fa-3x mb-3"></i>
                        <p>Haz clic para seleccionar un archivo CSV</p>
                        <small class="text-muted">Formato: .csv</small>
                        <input type="file" id="fileDocentes" accept=".csv" style="display: none;">
                    </div>
                    <div class="mt-3">
                        <button class="btn btn-sm btn-outline-success" onclick="descargarPlantilla('docentes')">
                            <i class="fas fa-download"></i> Descargar plantilla
                        </button>
                    </div>
                    <div id="resultadoDocentes" class="mt-3"></div>
                </div>
            </div>
        </div>

        <!-- Carga de Materias -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0"><i class="fas fa-book"></i> Cargar Materias</h5>
                </div>
                <div class="card-body">
                    <div class="upload-area" onclick="document.getElementById('fileMaterias').click()">
                        <i class="fas fa-cloud-upload-alt fa-3x mb-3"></i>
                        <p>Haz clic para seleccionar un archivo CSV</p>
                        <small class="text-muted">Formato: .csv</small>
                        <input type="file" id="fileMaterias" accept=".csv" style="display: none;">
                    </div>
                    <div class="mt-3">
                        <button class="btn btn-sm btn-outline-info" onclick="descargarPlantilla('materias')">
                            <i class="fas fa-download"></i> Descargar plantilla
                        </button>
                    </div>
                    <div id="resultadoMaterias" class="mt-3"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Instrucciones -->
    <div class="row mt-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5><i class="fas fa-info-circle"></i> Instrucciones para la carga CSV</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <h6>📋 Alumnos</h6>
                            <ul>
                                <li><strong>matricula</strong> - Requerido</li>
                                <li><strong>nombre</strong> - Requerido</li>
                                <li><strong>apellidos</strong> - Opcional</li>
                                <li><strong>email</strong> - Opcional</li>
                                <li><strong>generacion</strong> - Opcional</li>
                                <li><strong>grupo</strong> - Opcional</li>
                            </ul>
                        </div>
                        <div class="col-md-4">
                            <h6>📋 Docentes</h6>
                            <ul>
                                <li><strong>nombre</strong> - Requerido</li>
                                <li><strong>apellidos</strong> - Requerido</li>
                                <li><strong>email</strong> - Opcional</li>
                                <li><strong>telefono</strong> - Opcional</li>
                            </ul>
                        </div>
                        <div class="col-md-4">
                            <h6>📋 Materias</h6>
                            <ul>
                                <li><strong>nombre</strong> - Requerido</li>
                                <li><strong>clave</strong> - Opcional</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function procesarCSV(tipo, fileInput, resultadoDiv) {
    let file = fileInput.files[0];
    if (!file) {
        resultadoDiv.innerHTML = '<div class="alert alert-warning">Por favor, selecciona un archivo.</div>';
        return;
    }
    
    // Verificar extensión
    let extension = file.name.split('.').pop().toLowerCase();
    if (extension !== 'csv') {
        resultadoDiv.innerHTML = '<div class="alert alert-danger">Solo se permiten archivos CSV.</div>';
        return;
    }
    
    let formData = new FormData();
    formData.append('tipo', tipo);
    formData.append('archivo', file);
    
    resultadoDiv.innerHTML = `
        <div class="alert alert-info">
            <i class="fas fa-spinner fa-spin"></i> Procesando archivo "${file.name}"...
        </div>
    `;
    
    fetch('ajax/procesar_csv.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        return response.text().then(text => {
            try {
                return JSON.parse(text);
            } catch (e) {
                console.error('Respuesta:', text);
                throw new Error('Error al procesar: ' + text.substring(0, 200));
            }
        });
    })
    .then(data => {
        if (data.success) {
            let mensaje = `
                <div class="alert alert-success">
                    <strong>✅ Éxito!</strong><br>
                    Se cargaron ${data.total} registros correctamente.
                    ${data.duplicados > 0 ? `<br>⚠️ ${data.duplicados} registros duplicados ignorados.` : ''}
                    ${data.errores > 0 ? `<br>❌ ${data.errores} errores encontrados.` : ''}
                </div>
            `;
            
            if (data.detalle_errores && data.detalle_errores.length > 0) {
                mensaje += `
                    <div class="alert alert-warning mt-2" style="max-height: 200px; overflow-y: auto;">
                        <strong>Detalle de errores:</strong><br>
                        ${data.detalle_errores.slice(0, 10).join('<br>')}
                        ${data.detalle_errores.length > 10 ? `<br>... y ${data.detalle_errores.length - 10} más` : ''}
                    </div>
                `;
            }
            
            if (data.detalle_duplicados && data.detalle_duplicados.length > 0) {
                mensaje += `
                    <div class="alert alert-info mt-2" style="max-height: 200px; overflow-y: auto;">
                        <strong>Registros duplicados:</strong><br>
                        ${data.detalle_duplicados.slice(0, 10).join('<br>')}
                        ${data.detalle_duplicados.length > 10 ? `<br>... y ${data.detalle_duplicados.length - 10} más` : ''}
                    </div>
                `;
            }
            
            resultadoDiv.innerHTML = mensaje;
            
            if (data.total > 0) {
                setTimeout(() => location.reload(), 3000);
            }
        } else {
            resultadoDiv.innerHTML = `
                <div class="alert alert-danger">
                    <strong>❌ Error:</strong> ${data.error || 'Error desconocido'}
                </div>
            `;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        resultadoDiv.innerHTML = `
            <div class="alert alert-danger">
                <strong>❌ Error al procesar el archivo:</strong><br>
                ${error.message || 'Error desconocido'}
                <br><br>
                <small class="text-muted">Verifica que el archivo tenga el formato correcto y los encabezados adecuados.</small>
            </div>
        `;
    });
}

function descargarPlantilla(tipo) {
    let contenido = '';
    let nombreArchivo = `plantilla_${tipo}.csv`;
    
    if (tipo === 'alumnos') {
        contenido = 'matricula,nombre,apellidos,email,generacion,grupo\n';
        contenido += 'A001,Juan,Pérez,juan@ejemplo.com,Generación 2024,Grupo A\n';
        contenido += 'A002,María,García,maria@ejemplo.com,Generación 2024,Grupo A\n';
        contenido += 'A003,Carlos,Rodríguez,carlos@ejemplo.com,Generación 2024,Grupo B';
    } else if (tipo === 'docentes') {
        contenido = 'nombre,apellidos,email,telefono\n';
        contenido += 'Ana,Martínez,ana@ejemplo.com,555-0101\n';
        contenido += 'Luis,Fernández,luis@ejemplo.com,555-0102\n';
        contenido += 'Laura,Sánchez,laura@ejemplo.com,555-0103';
    } else if (tipo === 'materias') {
        contenido = 'nombre,clave\n';
        contenido += 'Matemáticas,MAT101\n';
        contenido += 'Programación,PROG101\n';
        contenido += 'Base de Datos,BD101';
    }
    
    // Crear y descargar el archivo
    const blob = new Blob([contenido], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = nombreArchivo;
    link.click();
    URL.revokeObjectURL(link.href);
}

// Asignar eventos
document.getElementById('fileAlumnos').addEventListener('change', function(e) {
    procesarCSV('alumnos', this, document.getElementById('resultadoAlumnos'));
});

document.getElementById('fileDocentes').addEventListener('change', function(e) {
    procesarCSV('docentes', this, document.getElementById('resultadoDocentes'));
});

document.getElementById('fileMaterias').addEventListener('change', function(e) {
    procesarCSV('materias', this, document.getElementById('resultadoMaterias'));
});

// Estilos adicionales para el área de upload
document.querySelectorAll('.upload-area').forEach(function(area) {
    area.addEventListener('dragover', function(e) {
        e.preventDefault();
        this.style.borderColor = '#764ba2';
        this.style.background = '#f0f0f0';
    });
    
    area.addEventListener('dragleave', function(e) {
        this.style.borderColor = '#667eea';
        this.style.background = '#f8f9fa';
    });
    
    area.addEventListener('drop', function(e) {
        e.preventDefault();
        this.style.borderColor = '#667eea';
        this.style.background = '#f8f9fa';
        
        let files = e.dataTransfer.files;
        if (files.length > 0) {
            let input = this.querySelector('input[type="file"]');
            if (input) {
                input.files = files;
                input.dispatchEvent(new Event('change'));
            }
        }
    });
});
</script>

<style>
.upload-area {
    border: 2px dashed #667eea;
    border-radius: 10px;
    padding: 30px;
    text-align: center;
    background: #f8f9fa;
    transition: all 0.3s;
    cursor: pointer;
}
.upload-area:hover {
    border-color: #764ba2;
    background: #f0f0f0;
}
</style>