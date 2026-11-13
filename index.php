<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Auditor y Desnudador de Facturas e Inventario</title>
    <style>
        :root {
            --primary: #2563eb;
            --primary-dark: #1d4ed8;
            --bg-body: #f8fafc;
            --card-bg: #ffffff;
            --border: #e2e8f0;
            --text: #1e293b;
        }
        body {
            font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background-color: var(--bg-body);
            color: var(--text);
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 1100px;
            margin: 0 auto;
            background: var(--card-bg);
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1);
        }
        h2, h3 { margin-top: 0; color: #0f172a; }
        label { display: block; font-weight: 600; margin-bottom: 6px; font-size: 0.9rem; }
        select, input[type="text"], input[type="number"] {
            width: 100%;
            padding: 10px;
            border: 1px solid var(--border);
            border-radius: 6px;
            font-size: 0.95rem;
            box-sizing: border-box;
        }
        .item-row {
            display: grid;
            grid-template-columns: 3fr 1fr 2.5fr 2fr auto;
            gap: 10px;
            align-items: center;
            background: #f1f5f9;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 10px;
        }
        a, button {
            background-color: var(--primary);
            color: white;
            border: none;
            padding: 10px 18px;
            font-weight: 600;
            border-radius: 6px;
            cursor: pointer;
            transition: background 0.2s;
        }
        button:hover { background-color: var(--primary-dark); }
        .btn-danger { background-color: #ef4444; }
        .btn-danger:hover { background-color: #dc2626; }
        .btn-secondary { background-color: #64748b; }
        .btn-secondary:hover { background-color: #475569; }
        .btn-success { background-color: #10b981; }
        .btn-success:hover { background-color: #059669; }
        .btn-pdf { background-color: #4f46e5; }
        a, .btn-pdf:hover { background-color: #4338ca; }
        
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid var(--border); font-size: 0.9rem; }
        th { background-color: #f8fafc; font-weight: 600; }
        .resumen-box { background: #eff6ff; border: 1px solid #bfdbfe; padding: 20px; border-radius: 8px; margin-top: 25px; }
        .alerta-transparente { background: #d1fae5; border: 1px solid #34d399; color: #065f46; padding: 15px; border-radius: 8px; margin-bottom: 20px; font-weight: 600; }
        .alerta-no-transparente { background: #fee2e2; border: 1px solid #f87171; color: #991b1b; padding: 15px; border-radius: 8px; margin-bottom: 20px; font-weight: 600; }
        .inventario-box { background: #fef3c7; border: 1px solid #f59e0b; padding: 20px; border-radius: 8px; margin-top: 25px; }
    </style>
</head>
<body>

<div class="container">
    <h2>Auditor y Desnudador de Facturas e Insumos</h2>
    <p>Ingresa los datos del proveedor, el total global y los ítems de la factura para auditar costos y sincronizar con el inventario.</p>

    <form id="facturaForm">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 15px; background: #f8fafc; padding: 15px; border: 1px solid var(--border); border-radius: 8px; margin-bottom: 20px;">
            <div>
                <label for="proveedor_nombre">Nombre del Proveedor:</label>
                <input type="text" name="proveedor_nombre" id="proveedor_nombre" placeholder="Ej. Aceros S.A.S." required>
            </div>
            <div>
                <label for="proveedor_nit">NIT del Proveedor:</label>
                <input type="text" name="proveedor_nit" id="proveedor_nit" placeholder="Ej. 900.123.456-7" required>
            </div>
            <div>
                <label for="proveedor_telefono">Teléfono:</label>
                <input type="text" name="proveedor_telefono" id="proveedor_telefono" placeholder="Ej. 5741234 / 310..." required>
            </div>
            <div>
                <label for="numero_factura">Número de Factura:</label>
                <input type="text" name="numero_factura" id="numero_factura" placeholder="Ej. FE-9842" required>
            </div>
            <div>
                <label for="total_factura_declarado">Total a Pagar Declarado ($):</label>
                <input type="number" step="0.01" name="total_factura_declarado" id="total_factura_declarado" placeholder="Ej. 476000.00" required>
            </div>
        </div>

        <hr style="border:0; border-top:1px solid var(--border); margin: 20px 0;">

        <label>Listado de Productos / Ítems:</label>
        <div id="itemsContainer">
            <div class="item-row">
                <input type="text" name="nombre[]" placeholder="Ej. Tubo Acero" required>
                <input type="number" min="1" name="cantidad[]" value="1" placeholder="Cant." required>
                <input type="number" step="0.01" name="total_linea[]" placeholder="Total línea" required>
                <select name="tasa_iva[]">
                    <option value="19" selected>IVA 19%</option>
                    <option value="5">IVA 5%</option>
                    <option value="0">Exento / 0%</option>
                </select>
                <button type="button" class="btn-danger" onclick="this.parentElement.remove()">X</button>
            </div>
        </div>

        <div style="margin-top: 15px; display: flex; gap: 10px;">
            <button type="button" class="btn-secondary" id="addItemBtn">+ Agregar otro producto</button>
            <button type="submit">Auditar y Verificar Inventario</button>
            <a href="reporte_fechas.php">Ver Informe por Fechas</a>
        </div>
    </form>

    <div id="resultadoContainer"></div>
    <div id="inventarioContainer"></div>
    
</div>

<script>
document.getElementById('addItemBtn').addEventListener('click', function() {
    const container = document.getElementById('itemsContainer');
    const newRow = document.createElement('div');
    newRow.className = 'item-row';
    newRow.innerHTML = `
        <input type="text" name="nombre[]" placeholder="Nombre producto" required>
        <input type="number" min="1" name="cantidad[]" value="1" placeholder="Cant." required>
        <input type="number" step="0.01" name="total_linea[]" placeholder="Total línea" required>
        <select name="tasa_iva[]">
            <option value="19" selected>IVA 19%</option>
            <option value="5">IVA 5%</option>
            <option value="0">Exento / 0%</option>
        </select>
        <button type="button" class="btn-danger" onclick="this.parentElement.remove()">X</button>
    `;
    container.appendChild(newRow);
});

let datosVerificacionGlobal = [];
let ultimaFacturaIdRegistrada = null;

document.getElementById('facturaForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = new FormData();
    formData.append('proveedor_nombre', document.getElementById('proveedor_nombre').value);
    formData.append('proveedor_nit', document.getElementById('proveedor_nit').value);
    formData.append('proveedor_telefono', document.getElementById('proveedor_telefono').value);
    formData.append('numero_factura', document.getElementById('numero_factura').value);
    formData.append('total_factura_declarado', document.getElementById('total_factura_declarado').value);

    document.querySelectorAll('input[name="nombre[]"]').forEach((_, i) => {
        formData.append(`items[${i}][nombre]`, document.querySelectorAll('input[name="nombre[]"]')[i].value);
        formData.append(`items[${i}][cantidad]`, document.querySelectorAll('input[name="cantidad[]"]')[i].value);
        formData.append(`items[${i}][total_linea]`, document.querySelectorAll('input[name="total_linea[]"]')[i].value);
        formData.append(`items[${i}][tasa_iva]`, document.querySelectorAll('select[name="tasa_iva[]"]')[i].value);
    });

    try {
        const response = await fetch('backend.php', { method: 'POST', body: formData });
        
        if (!response.ok) {
            const errorText = await response.text();
            console.error('Error HTTP del servidor:', errorText);
            alert('Error en el servidor (' + response.status + '). Revisa la consola (F12).');
            return;
        }

        const res = await response.json();
        
        if (res.error) { 
            alert('Error comercial: ' + res.error); 
            return; 
        }

        datosVerificacionGlobal = res.verificacion_aud_insumos;
        ultimaFacturaIdRegistrada = res.factura_id;

        renderizarResultados(res);
        renderizarPanelInventario(res.verificacion_aud_insumos);
    } catch (error) {
        console.error('Error de red o parseo JSON:', error);
        alert('Ocurrió un error al procesar la auditoría.');
    }
});

function formatMoney(val) {
    return Number(val).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function renderizarResultados(res) {
    const container = document.getElementById('resultadoContainer');
    let html = `<div class="resumen-box"><h3>Resultado del Diagnóstico Comercial</h3>`;
    
    if (res.transparente) {
        html += `<div class="alerta-transparente">Factura transparente. Cuadra perfectamente con el total declarado ($${formatMoney(res.total_declarado)}).</div>`;
    } else {
        html += `<div class="alerta-no-transparente">Discrepancia detectada. Se evaluó el Escenario B (Bases Puras) para cuadrar con el total declarado.</div>`;
    }
    html += `</div>`;
    container.innerHTML = html;
}

function renderizarPanelInventario(verificacion) {
    const container = document.getElementById('inventarioContainer');
    
    let html = `
        <div class="inventario-box">
            <h3>Sincronización con Tabla Insumos (Inventario)</h3>
            <p>Se detectaron elementos existentes en la base de datos. Seleccione la acción comercial a realizar para cada uno antes de actualizar.</p>
            <table>
                <thead>
                    <tr>
                        <th>Insumo</th>
                        <th>Estado BD</th>
                        <th>Stock Actual vs Nuevo</th>
                        <th>Costo Actual vs Nuevo</th>
                        <th>Decisión / Acción</th>
                    </tr>
                </thead>
                <tbody>
    `;

    verificacion.forEach((item, index) => {
        if (item.en_bd) {
            html += `
                <tr>
                    <td><strong>${escapeHtml(item.nombre)}</strong></td>
                    <td><span style="color: #b45309; font-weight: bold;">Ya Existe (ID: ${item.id_insumo})</span></td>
                    <td>Actual: <strong>${item.stock_actual}</strong> | Nuevo: <strong style="color: #2563eb;">+${item.stock_nuevo}</strong></td>
                    <td>Actual: $${formatMoney(item.costo_actual)} | Nuevo: <strong style="color: #2563eb;">$${formatMoney(item.costo_nuevo)}</strong></td>
                    <td>
                        <select id="modo_${index}" class="decision-select" data-index="${index}">
                            <option value="sumar">Sumar Stock y Actualizar Costo</option>
                            <option value="actualizar">Actualizar Manual (Sobrescribir)</option>
                        </select>
                    </td>
                </tr>
            `;
        } else {
            html += `
                <tr>
                    <td><strong>${escapeHtml(item.nombre)}</strong></td>
                    <td><span style="color: #047857; font-weight: bold;">Nuevo Insumo</span></td>
                    <td>Stock a crear: <strong>${item.stock_nuevo}</strong></td>
                    <td>Costo inicial: <strong style="color: #047857;">$${formatMoney(item.costo_nuevo)}</strong></td>
                    <td><span style="color: #64748b; font-style: italic;">Se creará automáticamente</span></td>
                </tr>
            `;
        }
    });

    html += `
                </tbody>
            </table>
            <div style="margin-top: 20px; text-align: right;">
                <button type="button" class="btn-success" id="btnEjecutarPut">Procesar y Actualizar Inventario (PUT)</button>
            </div>
        </div>
    `;

    container.innerHTML = html;
    document.getElementById('btnEjecutarPut').addEventListener('click', enviarActualizacionPut);
}

async function enviarActualizacionPut() {
    const decisiones = datosVerificacionGlobal.map((item, index) => {
        let modo = 'crear';
        if (item.en_bd) {
            const select = document.getElementById(`modo_${index}`);
            modo = select ? select.value : 'sumar';
        }
        return {
            id_insumo: item.id_insumo,
            nombre: item.nombre,
            stock: item.stock_nuevo,
            costo: item.costo_nuevo,
            tasa_iva: item.tasa_iva,
            modo: modo
        };
    });

    try {
        const response = await fetch('backend.php', {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ decisiones: decisiones })
        });
        
        const res = await response.json();
        if (res.error) {
            alert('Error: ' + res.error);
        } else {
            alert('¡' + res.mensaje + '!');
            
            document.getElementById('inventarioContainer').innerHTML = `
                <div class="alerta-transparente" style="margin-top:20px; display: flex; justify-content: space-between; align-items: center;">
                    <span>Inventario sincronizado con éxito. El proceso ha finalizado correctamente.</span>
                    <button type="button" class="btn-pdf" onclick="window.open('api_reporte.php?id=${ultimaFacturaIdRegistrada}', '_blank')">
                        📥 Descargar Informe PDF
                    </button>
                </div>
            `;
        }
    } catch (error) {
        console.error('Error PUT:', error);
        alert('Ocurrió un error al enviar la solicitud PUT al servidor.');
    }
}
</script>

</body>
</html>
