<?php
/* pension_adiestramiento_content.php
   Versión con Comparativa Detallada por Día mejorada
*/
?>
<div class="container-fluid">
    <!-- Encabezado -->
    <div class="row mb-4">
        <div class="col">
            <h2><i class="fas fa-file-invoice"></i> Pensión y Adiestramiento de Cadetes</h2>
            <p class="text-muted">Dashboard ejecutivo para carga de Excel - 4ta Generación</p>
        </div>
        <div class="col text-end">
            <button class="btn btn-success" id="btnExportar" style="display: none;" onclick="exportarReporteExcel()">
                <i class="fas fa-file-excel"></i> Exportar a Excel
            </button>
            <button class="btn btn-primary" id="btnImprimir" style="display: none;" onclick="window.print()">
                <i class="fas fa-print"></i> Imprimir
            </button>
        </div>
    </div>

    <!-- Área de carga -->
    <div class="card mb-4">
        <div class="card-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
            <h5 class="mb-0"><i class="fas fa-upload"></i> Cargar archivo Excel</h5>
        </div>
        <div class="card-body">
            <div class="upload-area" id="uploadArea" 
                 style="border: 2px dashed #667eea; border-radius: 10px; padding: 40px; text-align: center; background: #f8f9fa; cursor: pointer; transition: all 0.3s;">
                <i class="fas fa-file-excel" style="font-size: 48px; color: #28a745;"></i>
                <h5 class="mt-3">Arrastra o haz clic para subir el archivo</h5>
                <p class="text-muted">Formatos aceptados: .xlsx, .xls</p>
                <p class="text-muted small">El archivo debe contener la hoja "CADETES"</p>
                <input type="file" id="excelFile" accept=".xlsx,.xls" style="display: none;">
            </div>
            <div id="loadingStats" style="display: none; text-align: center; padding: 20px;">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Procesando...</span>
                </div>
                <p>Procesando archivo...</p>
            </div>
        </div>
    </div>

    <!-- KPI Cards -->
    <div id="kpiContainer" style="display: none;">
        <div class="row mb-4">
            <div class="col-md-2">
                <div class="card kpi-card" style="border-left: 4px solid #3498db;">
                    <div class="card-body">
                        <h6 class="text-muted">Total Cadetes</h6>
                        <h3 class="mb-0" id="kpiCadetes">0</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card kpi-card" style="border-left: 4px solid #2ecc71;">
                    <div class="card-body">
                        <h6 class="text-muted">Total Días</h6>
                        <h3 class="mb-0" id="kpiDias">0</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card kpi-card" style="border-left: 4px solid #f39c12;">
                    <div class="card-body">
                        <h6 class="text-muted">Subtotal</h6>
                        <h3 class="mb-0" id="kpiSubtotal">$0</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card kpi-card" style="border-left: 4px solid #9b59b6;">
                    <div class="card-body">
                        <h6 class="text-muted">IVA (16%)</h6>
                        <h3 class="mb-0" id="kpiIva">$0</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card kpi-card" style="border-left: 4px solid #e74c3c;">
                    <div class="card-body">
                        <h6 class="text-muted">Total</h6>
                        <h3 class="mb-0" id="kpiTotal">$0</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card kpi-card" style="border-left: 4px solid #1abc9c;">
                    <div class="card-body">
                        <h6 class="text-muted">Grupos</h6>
                        <h3 class="mb-0" id="kpiGrupos">0</h3>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Pestañas de reportes -->
    <div id="tabsContainer" style="display: none;">
        <ul class="nav nav-tabs mb-3" id="reporteTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="tab-resumen" data-bs-toggle="tab" data-bs-target="#content-resumen" type="button" role="tab">
                    <i class="fas fa-chart-bar"></i> Resumen
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="tab-detalle" data-bs-toggle="tab" data-bs-target="#content-detalle" type="button" role="tab">
                    <i class="fas fa-table"></i> Detalle por Cadete
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="tab-diario" data-bs-toggle="tab" data-bs-target="#content-diario" type="button" role="tab">
                    <i class="fas fa-calendar-day"></i> Asistencia Diaria
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="tab-comparativa" data-bs-toggle="tab" data-bs-target="#content-comparativa" type="button" role="tab">
                    <i class="fas fa-chart-line"></i> Comparativa por Grupo
                </button>
            </li>
        </ul>
    </div>

    <!-- Contenido de pestañas -->
    <div class="tab-content" id="tabContent">

        <!-- Pestaña 1: Resumen -->
        <div class="tab-pane fade show active" id="content-resumen" role="tabpanel">
            <div id="chartsContainer" style="display: none;">
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h6><i class="fas fa-chart-bar"></i> Días por Cadete (Top 20)</h6>
                            </div>
                            <div class="card-body">
                                <canvas id="chartDias" height="200"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h6><i class="fas fa-chart-pie"></i> Distribución por Grupo</h6>
                            </div>
                            <div class="card-body">
                                <canvas id="chartGrupos" height="200"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div id="resumenGruposContainer" style="display: none;">
                <div class="card mb-4">
                    <div class="card-header">
                        <h6><i class="fas fa-users"></i> Resumen por Grupo</h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-bordered" id="resumenGruposTable">
                                <thead>
                                    <tr>
                                        <th>Grupo</th>
                                        <th>Cadetes</th>
                                        <th>Total Días</th>
                                        <th>Subtotal</th>
                                        <th>IVA</th>
                                        <th>Total</th>
                                        <th>Promedio Días</th>
                                    </tr>
                                </thead>
                                <tbody id="resumenGruposBody"></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pestaña 2: Detalle por Cadete -->
        <div class="tab-pane fade" id="content-detalle" role="tabpanel">
            <div id="tablaContainer" style="display: none;">
                <div class="card">
                    <div class="card-header">
                        <div class="row align-items-center">
                            <div class="col-md-6">
                                <h6><i class="fas fa-table"></i> Detalle de Cadetes</h6>
                            </div>
                            <div class="col-md-6 text-end">
                                <div class="btn-group" role="group">
                                    <button class="btn btn-sm btn-outline-secondary" onclick="ordenarTabla('totalDias','desc')">
                                        <i class="fas fa-sort-amount-down"></i> Días ↓
                                    </button>
                                    <button class="btn btn-sm btn-outline-secondary" onclick="ordenarTabla('totalDias','asc')">
                                        <i class="fas fa-sort-amount-up"></i> Días ↑
                                    </button>
                                    <button class="btn btn-sm btn-outline-secondary" onclick="ordenarTabla('grupo','asc')">
                                        <i class="fas fa-sort-alpha-up"></i> Grupo
                                    </button>
                                    <button class="btn btn-sm btn-outline-secondary" onclick="ordenarTabla('nombre','asc')">
                                        <i class="fas fa-sort-alpha-up"></i> Nombre
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-bordered" id="reporteTable">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Grupo</th>
                                        <th>Nombre Completo</th>
                                        <th>Días</th>
                                        <th>Subtotal</th>
                                        <th>IVA</th>
                                        <th>Total</th>
                                    </tr>
                                </thead>
                                <tbody id="reporteBody"></tbody>
                                <tfoot id="reporteFooter"></tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pestaña 3: Asistencia Diaria -->
        <div class="tab-pane fade" id="content-diario" role="tabpanel">
            <div id="diarioContainer" style="display: none;">
                <div class="card">
                    <div class="card-header">
                        <div class="row align-items-center">
                            <div class="col-md-4">
                                <h6><i class="fas fa-calendar-day"></i> Asistencia Diaria</h6>
                            </div>
                            <div class="col-md-4">
                                <select id="filtroGrupoDiario" class="form-select form-select-sm" onchange="renderizarAsistenciaDiaria()">
                                    <option value="todos">Todos los grupos</option>
                                </select>
                            </div>
                            <div class="col-md-4 text-end">
                                <span class="badge bg-info" id="totalAsistenciasDiarias">0 asistencias</span>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-bordered" id="diarioTable">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Cadete</th>
                                        <th>Grupo</th>
                                        <?php for ($i = 1; $i <= 31; $i++): ?>
                                            <th style="font-size:9px; text-align:center;"><?php echo $i; ?></th>
                                        <?php endfor; ?>
                                        <th>Total</th>
                                    </tr>
                                </thead>
                                <tbody id="diarioBody"></tbody>
                                <tfoot id="diarioFooter"></tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pestaña 4: Comparativa por Grupo - MEJORADA -->
        <div class="tab-pane fade" id="content-comparativa" role="tabpanel">
            <div id="comparativaContainer" style="display: none;">
                <!-- Gráficos comparativos -->
                <div class="row">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h6><i class="fas fa-chart-bar"></i> Comparativa de Días por Grupo</h6>
                            </div>
                            <div class="card-body">
                                <canvas id="chartComparativa" height="250"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h6><i class="fas fa-chart-pie"></i> Asistencia Promedio por Grupo</h6>
                            </div>
                            <div class="card-body">
                                <canvas id="chartPromedioGrupo" height="250"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tabla Comparativa Detallada por Día - MEJORADA -->
                <div class="row mt-3">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header">
                                <div class="row align-items-center">
                                    <div class="col-md-6">
                                        <h6><i class="fas fa-table"></i> Comparativa Detallada por Día</h6>
                                        <p class="text-muted small mb-0">Análisis de asistencia día a día por grupo</p>
                                    </div>
                                    <div class="col-md-6 text-end">
                                        <div class="btn-group" role="group">
                                            <button class="btn btn-sm btn-outline-secondary" onclick="exportarComparativa()">
                                                <i class="fas fa-file-excel"></i> Exportar
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body">
                                <!-- Resumen de la comparativa -->
                                <div id="comparativaResumen" class="row mb-3"></div>
                                
                                <!-- Tabla detallada -->
                                <div class="table-responsive">
                                    <table class="table table-striped table-bordered" id="comparativaTable">
                                        <thead>
                                            <tr>
                                                <th style="position: sticky; top: 0; background: #2c3e50; color: white; z-index: 10;">Día</th>
                                                <th style="position: sticky; top: 0; background: #2c3e50; color: white; z-index: 10;">Fecha</th>
                                                <th style="position: sticky; top: 0; background: #2c3e50; color: white; z-index: 10;">Semana</th>
                                                <th id="comparativaHeader" style="position: sticky; top: 0; background: #2c3e50; color: white; z-index: 10;"></th>
                                                <th style="position: sticky; top: 0; background: #2c3e50; color: white; z-index: 10;">Total</th>
                                                <th style="position: sticky; top: 0; background: #2c3e50; color: white; z-index: 10;">% Asistencia</th>
                                            </tr>
                                        </thead>
                                        <tbody id="comparativaBody"></tbody>
                                        <tfoot id="comparativaFooter"></tfoot>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.kpi-card {
    transition: transform 0.3s;
}
.kpi-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
}
.kpi-card h3 {
    font-size: 1.8rem;
    font-weight: bold;
}
.upload-area {
    border: 2px dashed #667eea;
    border-radius: 10px;
    padding: 40px;
    text-align: center;
    background: #f8f9fa;
    cursor: pointer;
    transition: all 0.3s;
}
.upload-area:hover {
    border-color: #764ba2;
    background: #f0f0f0;
}
.upload-area.dragover {
    border-color: #28a745;
    background: #e8f5e9;
}
.table-responsive {
    max-height: 500px;
    overflow-y: auto;
}
.table thead th {
    position: sticky;
    top: 0;
    background: #2c3e50;
    color: white;
    z-index: 10;
}
.table tfoot td {
    font-weight: bold;
    background: #f8f9fa;
}
.badge-dias {
    padding: 4px 10px;
    border-radius: 15px;
}
.dia-presente {
    background-color: #d4edda !important;
    color: #155724 !important;
    font-weight: bold;
    text-align: center;
}
.dia-ausente {
    background-color: #f8d7da !important;
    color: #721c24 !important;
    text-align: center;
}
.nav-tabs .nav-link {
    color: #495057;
    font-weight: 500;
}
.nav-tabs .nav-link.active {
    color: #667eea;
    font-weight: bold;
}
.nav-tabs .nav-link:hover {
    color: #764ba2;
}
.comparativa-badge {
    padding: 3px 8px;
    border-radius: 12px;
    font-size: 11px;
}
.comparativa-badge.excelente {
    background-color: #d4edda;
    color: #155724;
}
.comparativa-badge.bueno {
    background-color: #cce5ff;
    color: #004085;
}
.comparativa-badge.regular {
    background-color: #fff3cd;
    color: #856404;
}
.comparativa-badge.bajo {
    background-color: #f8d7da;
    color: #721c24;
}
</style>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>

<script>
const COSTO_DIA = 667.97;
const IVA = 0.16;
let datosGlobales = [];
let chartDias = null;
let chartGrupos = null;
let chartComparativa = null;
let chartPromedioGrupo = null;
let ordenActual = { campo: 'totalDias', direccion: 'desc' };

// ==========================================
// 1. Procesar archivo Excel
// ==========================================
document.getElementById('excelFile').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (!file) return;
    
    const loadingDiv = document.getElementById('loadingStats');
    loadingDiv.style.display = 'block';
    
    const reader = new FileReader();
    reader.onload = function(ev) {
        try {
            const wb = XLSX.read(new Uint8Array(ev.target.result), { type: 'array' });
            const ws = wb.Sheets['CADETES'] || wb.Sheets[wb.SheetNames[0]];
            const rows = XLSX.utils.sheet_to_json(ws, { header: 1 });
            
            let registros = [];
            
            for (let i = 14; i < rows.length; i++) {
                const row = rows[i];
                if (!row || !row[1] || isNaN(row[1])) continue;
                
                const numero = row[1];
                const grupo = row[2] || '';
                const nombre = row[3] || '';
                
                const dias = [];
                for (let c = 4; c <= 34; c++) {
                    dias.push(row[c] === 1 ? 1 : 0);
                }
                
                const totalDias = dias.filter(d => d === 1).length;
                
                if (totalDias > 0 || nombre !== '') {
                    const subtotal = totalDias * COSTO_DIA;
                    const iva = subtotal * IVA;
                    const total = subtotal + iva;
                    
                    registros.push({
                        numero: numero,
                        grupo: grupo,
                        nombre: nombre,
                        dias: dias,
                        totalDias: totalDias,
                        subtotal: subtotal,
                        iva: iva,
                        total: total
                    });
                }
            }
            
            if (registros.length === 0) {
                alert('⚠️ No se encontraron datos.');
                loadingDiv.style.display = 'none';
                return;
            }
            
            datosGlobales = registros;
            
            document.getElementById('kpiContainer').style.display = 'block';
            document.getElementById('tabsContainer').style.display = 'block';
            document.getElementById('chartsContainer').style.display = 'block';
            document.getElementById('resumenGruposContainer').style.display = 'block';
            document.getElementById('tablaContainer').style.display = 'block';
            document.getElementById('diarioContainer').style.display = 'block';
            document.getElementById('comparativaContainer').style.display = 'block';
            
            renderizar(registros);
            renderizarAsistenciaDiaria();
            renderizarComparativa(registros);
            
            llenarFiltrosGrupos(registros);
            
            document.getElementById('btnExportar').style.display = 'inline-block';
            document.getElementById('btnImprimir').style.display = 'inline-block';
            
        } catch (error) {
            console.error('Error:', error);
            alert('Error al procesar el archivo: ' + error.message);
        }
        loadingDiv.style.display = 'none';
    };
    
    reader.onerror = function() {
        loadingDiv.style.display = 'none';
        alert('Error al leer el archivo.');
    };
    
    reader.readAsArrayBuffer(file);
});

// ==========================================
// 2. Funciones auxiliares
// ==========================================
function llenarFiltrosGrupos(data) {
    const grupos = new Set(data.map(r => r.grupo).filter(g => g !== ''));
    const selectDiario = document.getElementById('filtroGrupoDiario');
    while (selectDiario.options.length > 1) {
        selectDiario.remove(1);
    }
    grupos.forEach(grupo => {
        const option = document.createElement('option');
        option.value = grupo;
        option.textContent = grupo;
        selectDiario.appendChild(option);
    });
}

function money(v) {
    return v.toLocaleString('es-MX', { style: 'currency', currency: 'MXN' });
}

function getBadgeClass(porcentaje) {
    if (porcentaje >= 90) return 'excelente';
    if (porcentaje >= 75) return 'bueno';
    if (porcentaje >= 50) return 'regular';
    return 'bajo';
}

function getBadgeText(porcentaje) {
    if (porcentaje >= 90) return '🌟 Excelente';
    if (porcentaje >= 75) return '👍 Bueno';
    if (porcentaje >= 50) return '📊 Regular';
    return '⚠️ Bajo';
}

// ==========================================
// 3. Renderizar datos generales
// ==========================================
function renderizar(data) {
    let totalDias = 0, subtotal = 0, iva = 0, total = 0;
    data.forEach(r => {
        totalDias += r.totalDias;
        subtotal += r.subtotal;
        iva += r.iva;
        total += r.total;
    });
    
    const grupos = new Set(data.map(r => r.grupo));
    
    document.getElementById('kpiCadetes').textContent = data.length;
    document.getElementById('kpiDias').textContent = totalDias;
    document.getElementById('kpiSubtotal').textContent = money(subtotal);
    document.getElementById('kpiIva').textContent = money(iva);
    document.getElementById('kpiTotal').textContent = money(total);
    document.getElementById('kpiGrupos').textContent = grupos.size;
    
    renderizarGraficos(data);
    renderizarResumenGrupos(data);
    renderizarTabla(data);
}

// ==========================================
// 4. Gráficos
// ==========================================
function renderizarGraficos(data) {
    const top20 = [...data].sort((a, b) => b.totalDias - a.totalDias).slice(0, 20);
    
    if (chartDias) chartDias.destroy();
    chartDias = new Chart(document.getElementById('chartDias'), {
        type: 'bar',
        data: {
            labels: top20.map(r => r.nombre.length > 20 ? r.nombre.substring(0, 18) + '...' : r.nombre),
            datasets: [{
                label: 'Días',
                data: top20.map(r => r.totalDias),
                backgroundColor: 'rgba(102, 126, 234, 0.6)',
                borderColor: '#667eea',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, title: { display: true, text: 'Días' } }
            }
        }
    });
    
    const grupos = {};
    data.forEach(r => {
        grupos[r.grupo] = (grupos[r.grupo] || 0) + 1;
    });
    
    const colores = ['#667eea', '#764ba2', '#2ecc71', '#f39c12', '#e74c3c', '#3498db', '#1abc9c', '#9b59b6'];
    
    if (chartGrupos) chartGrupos.destroy();
    chartGrupos = new Chart(document.getElementById('chartGrupos'), {
        type: 'pie',
        data: {
            labels: Object.keys(grupos),
            datasets: [{
                data: Object.values(grupos),
                backgroundColor: colores.slice(0, Object.keys(grupos).length)
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { position: 'bottom' } }
        }
    });
}

// ==========================================
// 5. Resumen por grupo
// ==========================================
function renderizarResumenGrupos(data) {
    const grupos = {};
    data.forEach(r => {
        if (!grupos[r.grupo]) {
            grupos[r.grupo] = { cadetes: 0, dias: 0, subtotal: 0, iva: 0, total: 0 };
        }
        grupos[r.grupo].cadetes++;
        grupos[r.grupo].dias += r.totalDias;
        grupos[r.grupo].subtotal += r.subtotal;
        grupos[r.grupo].iva += r.iva;
        grupos[r.grupo].total += r.total;
    });
    
    const tbody = document.getElementById('resumenGruposBody');
    tbody.innerHTML = '';
    
    Object.keys(grupos).sort().forEach(grupo => {
        const g = grupos[grupo];
        const promedio = g.cadetes > 0 ? (g.dias / g.cadetes).toFixed(1) : 0;
        tbody.innerHTML += `
            <tr>
                <td><strong>${grupo}</strong></td>
                <td>${g.cadetes}</td>
                <td>${g.dias}</td>
                <td>${money(g.subtotal)}</td>
                <td>${money(g.iva)}</td>
                <td><strong>${money(g.total)}</strong></td>
                <td>${promedio}</td>
            </tr>
        `;
    });
}

// ==========================================
// 6. Tabla detallada
// ==========================================
function renderizarTabla(data) {
    const tbody = document.getElementById('reporteBody');
    const tfoot = document.getElementById('reporteFooter');
    tbody.innerHTML = '';
    
    let totalDias = 0, subtotal = 0, iva = 0, total = 0;
    const datosOrdenados = ordenarDatos(data, ordenActual.campo, ordenActual.direccion);
    
    datosOrdenados.forEach((r, index) => {
        totalDias += r.totalDias;
        subtotal += r.subtotal;
        iva += r.iva;
        total += r.total;
        
        let badgeClass = 'bg-success';
        if (r.totalDias < 10) badgeClass = 'bg-danger';
        else if (r.totalDias < 20) badgeClass = 'bg-warning text-dark';
        
        tbody.innerHTML += `
            <tr>
                <td>${index + 1}</td>
                <td><span class="badge bg-primary">${r.grupo}</span></td>
                <td>${r.nombre}</td>
                <td><span class="badge ${badgeClass} badge-dias">${r.totalDias}</span></td>
                <td>${money(r.subtotal)}</td>
                <td>${money(r.iva)}</td>
                <td><strong>${money(r.total)}</strong></td>
            </tr>
        `;
    });
    
    tfoot.innerHTML = `
        <tr style="background-color: #2c3e50; color: white;">
            <td colspan="3"><strong>TOTALES</strong></td>
            <td><strong>${totalDias}</strong></td>
            <td><strong>${money(subtotal)}</strong></td>
            <td><strong>${money(iva)}</strong></td>
            <td><strong>${money(total)}</strong></td>
        </tr>
    `;
}

function ordenarDatos(data, campo, direccion) {
    const sorted = [...data];
    sorted.sort((a, b) => {
        let valA = a[campo];
        let valB = b[campo];
        if (typeof valA === 'string') {
            valA = valA.toLowerCase();
            valB = valB.toLowerCase();
        }
        if (direccion === 'desc') {
            return valA > valB ? -1 : 1;
        } else {
            return valA < valB ? -1 : 1;
        }
    });
    return sorted;
}

function ordenarTabla(campo, direccion) {
    ordenActual = { campo: campo, direccion: direccion };
    renderizarTabla(datosGlobales);
}

// ==========================================
// 7. Asistencia Diaria
// ==========================================
function renderizarAsistenciaDiaria() {
    const grupoFiltro = document.getElementById('filtroGrupoDiario').value;
    let data = datosGlobales;
    if (grupoFiltro !== 'todos') {
        data = data.filter(r => r.grupo === grupoFiltro);
    }
    
    const tbody = document.getElementById('diarioBody');
    const tfoot = document.getElementById('diarioFooter');
    tbody.innerHTML = '';
    
    if (data.length === 0) {
        tbody.innerHTML = '<tr><td colspan="34" class="text-center">No hay datos</td></tr>';
        return;
    }
    
    let totalPorDia = new Array(31).fill(0);
    let totalAsistencias = 0;
    
    data.forEach((r, index) => {
        const tr = document.createElement('tr');
        tr.innerHTML = `<td>${index + 1}</td>
                        <td style="text-align:left; font-size:11px;">${r.nombre}</td>
                        <td><span class="badge bg-primary">${r.grupo}</span></td>`;
        
        let diasCadete = 0;
        for (let i = 0; i < 31; i++) {
            const valor = r.dias[i] || 0;
            diasCadete += valor;
            totalPorDia[i] += valor;
            
            const td = document.createElement('td');
            td.textContent = valor === 1 ? '✓' : '·';
            td.className = valor === 1 ? 'dia-presente' : 'dia-ausente';
            td.style.fontSize = '14px';
            tr.appendChild(td);
        }
        
        tr.innerHTML += `<td><strong>${diasCadete}</strong></td>`;
        tbody.appendChild(tr);
        totalAsistencias += diasCadete;
    });
    
    const trFooter = document.createElement('tr');
    trFooter.style.backgroundColor = '#2c3e50';
    trFooter.style.color = 'white';
    trFooter.style.fontWeight = 'bold';
    trFooter.innerHTML = `<td colspan="3"><strong>TOTALES</strong></td>`;
    
    for (let i = 0; i < 31; i++) {
        const td = document.createElement('td');
        td.textContent = totalPorDia[i];
        td.style.textAlign = 'center';
        td.style.backgroundColor = '#2c3e50';
        td.style.color = '#ffc107';
        trFooter.appendChild(td);
    }
    
    trFooter.innerHTML += `<td><strong>${totalAsistencias}</strong></td>`;
    tfoot.innerHTML = '';
    tfoot.appendChild(trFooter);
    
    document.getElementById('totalAsistenciasDiarias').textContent = totalAsistencias + ' asistencias';
}

// ==========================================
// 8. Comparativa por Grupo - MEJORADA
// ==========================================
function renderizarComparativa(data) {
    const grupos = [...new Set(data.map(r => r.grupo))].sort();
    const datosGrupos = {};
    grupos.forEach(g => {
        datosGrupos[g] = data.filter(r => r.grupo === g);
    });
    
    // Gráficos
    if (chartComparativa) chartComparativa.destroy();
    chartComparativa = new Chart(document.getElementById('chartComparativa'), {
        type: 'bar',
        data: {
            labels: grupos,
            datasets: [
                {
                    label: 'Total Días',
                    data: grupos.map(g => datosGrupos[g].reduce((sum, r) => sum + r.totalDias, 0)),
                    backgroundColor: 'rgba(102, 126, 234, 0.6)',
                    borderColor: '#667eea',
                    borderWidth: 1                },
                {
                    label: 'Promedio Días',
                    data: grupos.map(g => {
                        const total = datosGrupos[g].reduce((sum, r) => sum + r.totalDias, 0);
                        return datosGrupos[g].length > 0 ? (total / datosGrupos[g].length).toFixed(1) : 0;
                    }),
                    backgroundColor: 'rgba(46, 204, 113, 0.6)',
                    borderColor: '#2ecc71',
                    borderWidth: 1
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { position: 'top' } },
            scales: {
                y: { beginAtZero: true, title: { display: true, text: 'Días' } }
            }
        }
    });
    
    if (chartPromedioGrupo) chartPromedioGrupo.destroy();
    chartPromedioGrupo = new Chart(document.getElementById('chartPromedioGrupo'), {
        type: 'pie',
        data: {
            labels: grupos,
            datasets: [{
                label: 'Promedio Días',
                data: grupos.map(g => {
                    const total = datosGrupos[g].reduce((sum, r) => sum + r.totalDias, 0);
                    return datosGrupos[g].length > 0 ? parseFloat((total / datosGrupos[g].length).toFixed(1)) : 0;
                }),
                backgroundColor: ['#667eea', '#764ba2', '#2ecc71', '#f39c12', '#e74c3c', '#3498db', '#1abc9c', '#9b59b6']
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { position: 'bottom' } }
        }
    });
    
    // Resumen de la comparativa
    renderizarResumenComparativa(datosGrupos, grupos);
    
    // Tabla comparativa detallada
    renderizarTablaComparativa(datosGrupos, grupos);
}

// ==========================================
// 8.1 Resumen de la comparativa
// ==========================================
function renderizarResumenComparativa(datosGrupos, grupos) {
    const container = document.getElementById('comparativaResumen');
    container.innerHTML = '';
    
    let html = '';
    grupos.forEach(g => {
        const grupoData = datosGrupos[g];
        const total = grupoData.length;
        const totalDias = grupoData.reduce((sum, r) => sum + r.totalDias, 0);
        const promedio = total > 0 ? (totalDias / total).toFixed(1) : 0;
        const maxDias = total > 0 ? Math.max(...grupoData.map(r => r.totalDias)) : 0;
        const minDias = total > 0 ? Math.min(...grupoData.map(r => r.totalDias)) : 0;
        
        const color = ['#667eea', '#764ba2', '#2ecc71', '#f39c12', '#e74c3c', '#3498db', '#1abc9c', '#9b59b6'][grupos.indexOf(g) % 8];
        
        html += `
            <div class="col-md-3 col-sm-6 mb-2">
                <div class="card" style="border-left: 4px solid ${color};">
                    <div class="card-body p-2">
                        <h6 class="mb-1"><strong>${g}</strong></h6>
                        <div class="row">
                            <div class="col-6">
                                <small class="text-muted">Cadetes:</small>
                                <span class="badge bg-primary">${total}</span>
                            </div>
                            <div class="col-6">
                                <small class="text-muted">Total Días:</small>
                                <span class="badge bg-success">${totalDias}</span>
                            </div>
                        </div>
                        <div class="row mt-1">
                            <div class="col-4">
                                <small class="text-muted">Prom:</small>
                                <strong>${promedio}</strong>
                            </div>
                            <div class="col-4">
                                <small class="text-muted">Max:</small>
                                <strong>${maxDias}</strong>
                            </div>
                            <div class="col-4">
                                <small class="text-muted">Min:</small>
                                <strong>${minDias}</strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
    });
    
    container.innerHTML = html;
}

// ==========================================
// 8.2 Tabla comparativa detallada
// ==========================================
function renderizarTablaComparativa(datosGrupos, grupos) {
    const tbody = document.getElementById('comparativaBody');
    const thead = document.getElementById('comparativaHeader');
    const tfoot = document.getElementById('comparativaFooter');
    tbody.innerHTML = '';
    
    // Encabezados de grupos
    let headerHtml = '';
    grupos.forEach(g => {
        headerHtml += `<th style="text-align:center; min-width:80px;">${g}</th>`;
    });
    thead.innerHTML = headerHtml;
    
    // Nombres de días y semanas
    const diasSemana = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'];
    const semanas = ['Semana 1', 'Semana 2', 'Semana 3', 'Semana 4', 'Semana 5'];
    
    let totalesPorGrupo = {};
    grupos.forEach(g => { totalesPorGrupo[g] = 0; });
    let totalGeneral = 0;
    
    // Datos por día
    for (let dia = 0; dia < 31; dia++) {
        const tr = document.createElement('tr');
        
        // Calcular semana
        const semana = Math.floor(dia / 7) + 1;
        const diaSemana = diasSemana[dia % 7];
        const fecha = `${dia + 1}/${6}/2026`; // Junio 2026
        
        tr.innerHTML = `
            <td><strong>${dia + 1}</strong></td>
            <td style="font-size:11px;">${fecha}</td>
            <td><span class="badge bg-secondary">${semana > 5 ? 'Semana 5' : semanas[semana - 1]}</span></td>
        `;
        
        let totalDia = 0;
        let htmlGrupos = '';
        
        grupos.forEach(g => {
            const grupoData = datosGrupos[g];
            const presentes = grupoData.filter(r => r.dias[dia] === 1).length;
            const total = grupoData.length;
            const porcentaje = total > 0 ? ((presentes / total) * 100).toFixed(0) : 0;
            
            totalDia += presentes;
            totalesPorGrupo[g] += presentes;
            
            const badgeClass = getBadgeClass(porcentaje);
            const barWidth = porcentaje;
            
            htmlGrupos += `
                <td style="text-align:center;">
                    <div style="display:flex; flex-direction:column; align-items:center;">
                        <span class="comparativa-badge ${badgeClass}" style="font-size:12px;">
                            ${presentes}/${total}
                        </span>
                        <div style="width:100%; height:4px; background:#e9ecef; border-radius:2px; margin-top:2px;">
                            <div style="width:${barWidth}%; height:100%; background:${
                                porcentaje >= 90 ? '#28a745' : 
                                porcentaje >= 75 ? '#007bff' : 
                                porcentaje >= 50 ? '#ffc107' : '#dc3545'
                            }; border-radius:2px;"></div>
                        </div>
                        <small style="font-size:9px; color:#6c757d;">${porcentaje}%</small>
                    </div>
                </td>
            `;
        });
        
        tr.innerHTML += htmlGrupos;
        
        // Total del día
        const totalGeneralDia = grupos.reduce((sum, g) => {
            return sum + datosGrupos[g].filter(r => r.dias[dia] === 1).length;
        }, 0);
        const totalPosible = grupos.reduce((sum, g) => sum + datosGrupos[g].length, 0);
        const porcentajeGeneral = totalPosible > 0 ? ((totalGeneralDia / totalPosible) * 100).toFixed(0) : 0;
        
        tr.innerHTML += `
            <td><strong>${totalGeneralDia}</strong></td>
            <td>
                <span class="comparativa-badge ${getBadgeClass(porcentajeGeneral)}">
                    ${porcentajeGeneral}%
                </span>
            </td>
        `;
        
        totalGeneral += totalGeneralDia;
        tbody.appendChild(tr);
    }
    
    // Footer con totales
    const trFooter = document.createElement('tr');
    trFooter.style.backgroundColor = '#2c3e50';
    trFooter.style.color = 'white';
    trFooter.style.fontWeight = 'bold';
    trFooter.innerHTML = `
        <td colspan="3"><strong>TOTALES</strong></td>
    `;
    
    grupos.forEach(g => {
        trFooter.innerHTML += `<td style="text-align:center; background-color:#2c3e50; color:#ffc107;">
            <strong>${totalesPorGrupo[g]}</strong>
        </td>`;
    });
    
    const totalPosibleFinal = grupos.reduce((sum, g) => sum + datosGrupos[g].length * 31, 0);
    const porcentajeFinal = totalPosibleFinal > 0 ? ((totalGeneral / totalPosibleFinal) * 100).toFixed(0) : 0;
    
    trFooter.innerHTML += `
        <td style="text-align:center; background-color:#2c3e50; color:#ffc107;">
            <strong>${totalGeneral}</strong>
        </td>
        <td style="text-align:center; background-color:#2c3e50; color:#ffc107;">
            <strong>${porcentajeFinal}%</strong>
        </td>
    `;
    
    tfoot.innerHTML = '';
    tfoot.appendChild(trFooter);
}

// ==========================================
// 9. Exportar a Excel
// ==========================================
function exportarReporteExcel() {
    if (datosGlobales.length === 0) {
        alert('No hay datos para exportar');
        return;
    }
    
    const exportData = [
        ['REPORTE DE ADIESTRAMIENTO Y PENSIÓN DE CADETES'],
        ['4ta Generación - Programa de Formación Inicial con Especialización'],
        [''],
        ['No.', 'Grupo', 'Nombre Completo', 'Días', 'Subtotal', 'IVA (16%)', 'Total']
    ];
    
    let totalDias = 0, subtotal = 0, iva = 0, total = 0;
    
    datosGlobales.forEach(r => {
        totalDias += r.totalDias;
        subtotal += r.subtotal;
        iva += r.iva;
        total += r.total;
        exportData.push([r.numero, r.grupo, r.nombre, r.totalDias, r.subtotal, r.iva, r.total]);
    });
    
    exportData.push(['TOTALES', '', '', totalDias, subtotal, iva, total]);
    
    const wb = XLSX.utils.book_new();
    const ws = XLSX.utils.aoa_to_sheet(exportData);
    ws['!cols'] = [
        { wch: 8 }, { wch: 15 }, { wch: 30 }, { wch: 10 },
        { wch: 15 }, { wch: 15 }, { wch: 15 }
    ];
    
    XLSX.utils.book_append_sheet(wb, ws, 'Reporte');
    XLSX.writeFile(wb, 'Reporte_Adiestramiento_Cadetes.xlsx');
}

function exportarComparativa() {
    alert('Función de exportación de comparativa en desarrollo');
}

// ==========================================
// 10. Drag & Drop
// ==========================================
const uploadArea = document.getElementById('uploadArea');
const excelFileInput = document.getElementById('excelFile');

uploadArea.addEventListener('click', () => excelFileInput.click());

uploadArea.addEventListener('dragover', (e) => {
    e.preventDefault();
    uploadArea.classList.add('dragover');
});

uploadArea.addEventListener('dragleave', (e) => {
    e.preventDefault();
    uploadArea.classList.remove('dragover');
});

uploadArea.addEventListener('drop', (e) => {
    e.preventDefault();
    uploadArea.classList.remove('dragover');
    const file = e.dataTransfer.files[0];
    if (file) {
        const ext = file.name.split('.').pop().toLowerCase();
        if (['xlsx', 'xls'].includes(ext)) {
            excelFileInput.files = e.dataTransfer.files;
            excelFileInput.dispatchEvent(new Event('change'));
        } else {
            alert('Por favor, sube un archivo Excel válido (.xlsx o .xls)');
        }
    }
});

// Funciones globales
window.exportarReporteExcel = exportarReporteExcel;
window.ordenarTabla = ordenarTabla;
window.renderizarAsistenciaDiaria = renderizarAsistenciaDiaria;
window.exportarComparativa = exportarComparativa;
</script>