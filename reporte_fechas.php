<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Informe de Cargado de Facturas por Fechas</title>
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
        .header-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid var(--border);
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        h2 { margin: 0; color: #0f172a; }
        .btn-back {
            text-decoration: none;
            background: #64748b;
            color: #fff;
            padding: 8px 14px;
            border-radius: 6px;
            font-size: 0.9rem;
            font-weight: 600;
        }
        .btn-back:hover { background: #475569; }
        .filter-box {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            align-items: flex-end;
            background: #f1f5f9;
            padding: 20px;
            border-radius: 8px;
            border: 1px solid var(--border);
        }
        .filter-group { flex: 1; min-width: 180px; }
        label { display: block; font-weight: 600; margin-bottom: 6px; font-size: 0.9rem; }
        input[type="date"] {
            width: 100%;
            padding: 10px;
            border: 1px solid var(--border);
            border-radius: 6px;
            font-size: 0.95rem;
            box-sizing: border-box;
        }
        button {
            background-color: var(--primary);
            color: white;
            border: none;
            padding: 11px 18px;
            font-weight: 600;
            border-radius: 6px;
            cursor: pointer;
            transition: background 0.2s;
        }
        button:hover { background-color: var(--primary-dark); }
        .btn-pdf { background-color: #4f46e5; }
        .btn-pdf:hover { background-color: #4338ca; }
        .kpi-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 25px;
        }
        .kpi-card {
            background: #f8fafc;
            border: 1px solid var(--border);
            padding: 15px;
            border-radius: 8px;
        }
        .kpi-card span { display: block; font-size: 0.85rem; color: #64748b; font-weight: 600; }
        .kpi-card strong { display: block; font-size: 1.4rem; color: #0f172a; margin-top: 5px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid var(--border); font-size: 0.9rem; }
        th { background-color: #f8fafc; font-weight: 600; color: #334155; }
        .badge-ok { background: #d1fae5; color: #065f46; padding: 4px 8px; border-radius: 4px; font-weight: 600; font-size: 0.8rem; }
        .badge-warn { background: #fee2e2; color: #991b1b; padding: 4px 8px; border-radius: 4px; font-weight: 600; font-size: 0.8rem; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
    </style>
</head>
<body>

<div class="container">
    <div class="header-bar">
        <div>
            <h2>Informe Histórico de Cargado de Facturas</h2>
            <p style="margin: 4px 0 0; color: #64748b; font-size: 0.9rem;">Consulta consolidada de auditorías registradas entre fechas.</p>
        </div>
        <a href="index.php" class="btn-back">← Volver al Auditor</a>
    </div>

    <div class="filter-box">
        <div class="filter-group">
            <label for="fecha_inicio">Fecha Inicial:</label>
            <input type="date" id="fecha_inicio" required>
        </div>
        <div class="filter-group">
            <label for="fecha_fin">Fecha Final:</label>
            <input type="date" id="fecha_fin" required>
        </div>
        <div style="display: flex; gap: 10px;">
            <button type="button" onclick="consultarFacturas()">Consultar en Pantalla</button>
            <button type="button" class="btn-pdf" onclick="descargarPdfRango()">Descargar PDF Consolidado</button>
        </div>
    </div>

    <div id="resultadosRango"></div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const hoy = new Date().toISOString().split('T')[0];
    document.getElementById('fecha_inicio').value = hoy;
    document.getElementById('fecha_fin').value = hoy;
});

function formatMoney(val) {
    return Number(val).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text ?? '';
    return div.innerHTML;
}

async function consultarFacturas() {
    const inicio = document.getElementById('fecha_inicio').value;
    const fin = document.getElementById('fecha_fin').value;

    if (!inicio || !fin || inicio > fin) {
        alert('Verifica que el rango de fechas sea válido.');
        return;
    }

    try {
        const res = await fetch(`api_reporte_fechas.php?accion=json&inicio=${encodeURIComponent(inicio)}&fin=${encodeURIComponent(fin)}`);
        const data = await res.json();

        if (!res.ok || data.error) {
            alert(data.error || 'Error al consultar las facturas.');
            return;
        }

        renderizarTabla(data);
    } catch (e) {
        console.error(e);
        alert('Error de conexión con el servidor.');
    }
}

function renderizarTabla(data) {
    const container = document.getElementById('resultadosRango');
    const { resumen, facturas } = data;

    if (facturas.length === 0) {
        container.innerHTML = `<p style="margin-top:20px; color:#64748b;">No se encontraron facturas registradas en el rango seleccionado.</p>`;
        return;
    }

    let html = `
        <div class="kpi-grid">
            <div class="kpi-card"><span>Total Facturas</span><strong>${resumen.total_facturas}</strong></div>
            <div class="kpi-card"><span>Base Neta Acumulada</span><strong>$${formatMoney(resumen.gran_base)}</strong></div>
            <div class="kpi-card"><span>IVA Total Acumulado</span><strong>$${formatMoney(resumen.gran_iva)}</strong></div>
            <div class="kpi-card"><span>Total Declarado Global</span><strong>$${formatMoney(resumen.gran_total)}</strong></div>
        </div>
        <table>
            <thead>
                <tr>
                    <th class="text-center">ID</th>
                    <th>Fecha y Hora</th>
                    <th>Proveedor / Factura</th>
                    <th class="text-center">Estado</th>
                    <th class="text-center">Ítems (Unds)</th>
                    <th class="text-right">Base Neta</th>
                    <th class="text-right">IVA Total</th>
                    <th class="text-right">Total Declarado</th>
                    <th class="text-center">Detalle</th>
                </tr>
            </thead>
            <tbody>
    `;

    facturas.forEach(f => {
        const badge = Number(f.transparente) === 1
            ? `<span class="badge-ok">Transparente</span>`
            : `<span class="badge-warn">Base Pura</span>`;

        const proveedor = escapeHtml(f.proveedor_nombre || 'Sin proveedor');
        const nit = escapeHtml(f.proveedor_nit || 'N/A');
        const numFac = escapeHtml(f.numero_factura || 'S/N');

        html += `
            <tr>
                <td class="text-center"><strong>#${f.id}</strong></td>
                <td>${f.fecha_auditoria}</td>
                <td>
                    <strong>${proveedor}</strong><br>
                    <small style="color: #64748b;">NIT: ${nit} &bull; Fac: <strong>${numFac}</strong></small>
                </td>
                <td class="text-center">${badge}</td>
                <td class="text-center">${f.total_items} (${f.unidades_totales} u.)</td>
                <td class="text-right">$${formatMoney(f.suma_base)}</td>
                <td class="text-right">$${formatMoney(f.suma_iva)}</td>
                <td class="text-right"><strong>$${formatMoney(f.total_declarado)}</strong></td>
                <td class="text-center">
                    <button type="button" style="padding: 5px 10px; font-size: 0.8rem;" onclick="window.open('api_reporte.php?id=${f.id}', '_blank')">PDF</button>
                </td>
            </tr>
        `;
    });

    html += `</tbody></table>`;
    container.innerHTML = html;
}

async function descargarPdfRango() {
    const inicio = document.getElementById('fecha_inicio').value;
    const fin = document.getElementById('fecha_fin').value;

    if (!inicio || !fin || inicio > fin) {
        alert('Verifica que el rango de fechas sea válido.');
        return;
    }

    const url = `api_reporte_fechas.php?accion=pdf&inicio=${encodeURIComponent(inicio)}&fin=${encodeURIComponent(fin)}`;

    try {
        const check = await fetch(url);
        const contentType = check.headers.get('content-type');
        if (contentType && contentType.includes('application/json')) {
            const err = await check.json();
            alert(err.error || 'No se pudo generar el PDF.');
            return;
        }
        window.open(url, '_blank');
    } catch (e) {
        console.error(e);
        alert('Error al intentar descargar el PDF.');
    }
}
</script>
</body>
</html>
