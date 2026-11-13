<?php
header('Content-Type: application/json; charset=utf-8');

try {
    require_once 'conexion.php';
    $method = $_SERVER['REQUEST_METHOD'];

    // ==========================================
    // ACCIÓN POST: Auditar, guardar y verificar
    // ==========================================
    if ($method === 'POST') {
        $proveedorNombre = trim($_POST['proveedor_nombre'] ?? '');
        $proveedorNit = trim($_POST['proveedor_nit'] ?? '');
        $proveedorTelefono = trim($_POST['proveedor_telefono'] ?? '');
        $numeroFactura = trim($_POST['numero_factura'] ?? '');
        $totalFacturaDeclarado = floatval($_POST['total_factura_declarado'] ?? 0);
        $items = $_POST['items'] ?? [];

        if (empty($proveedorNombre) || empty($proveedorNit) || empty($numeroFactura) || empty($items) || !is_array($items) || $totalFacturaDeclarado <= 0) {
            echo json_encode(['error' => 'Datos incompletos o inválidos en la petición POST (verifique datos del proveedor y factura).']);
            exit;
        }

        // --- INTENTO 1: Asumiendo PRECIOS CON IVA INCLUIDO ---
        $desgloseItems1 = [];
        $subtotalBases1 = 0;
        $totalCalculadoSuma1 = 0;

        foreach ($items as $item) {
            $nombre = trim($item['nombre'] ?? '');
            $cantidad = max(1, intval($item['cantidad'] ?? 1)); 
            $totalLineaIngresado = floatval($item['total_linea'] ?? 0);
            $tasaIva = floatval($item['tasa_iva'] ?? 19); 

            if ($totalLineaIngresado <= 0) continue;

            if ($tasaIva > 0) {
                $factor = 1 + ($tasaIva / 100);
                $baseTotalLinea = round($totalLineaIngresado / $factor, 2);
                $ivaTotalLinea = round($totalLineaIngresado - $baseTotalLinea, 2);
                $precioFinalLinea = $totalLineaIngresado;
            } else {
                $baseTotalLinea = $totalLineaIngresado;
                $ivaTotalLinea = 0;
                $precioFinalLinea = $totalLineaIngresado;
            }

            $costoUnitarioConIva = round($precioFinalLinea / $cantidad, 2);
            $subtotalBases1 += $baseTotalLinea;
            $totalCalculadoSuma1 += $precioFinalLinea;

            $desgloseItems1[] = [
                'nombre' => $nombre ?: 'Ítem sin nombre',
                'cantidad' => $cantidad,
                'costo_unitario_con_iva' => $costoUnitarioConIva,
                'base_total' => $baseTotalLinea,
                'iva_total' => $ivaTotalLinea,
                'total_linea' => $precioFinalLinea,
                'tasa_iva' => $tasaIva
            ];
        }

        $diferencia1 = abs($totalCalculadoSuma1 - $totalFacturaDeclarado);
        $esTransparente = ($totalFacturaDeclarado > 0 && $diferencia1 <= 1.0);

        // --- INTENTO 2: Si hay discrepancia, asumiendo BASE PURA ---
        $desgloseItems2 = [];
        $subtotalBases2 = 0;
        $totalCalculadoSuma2 = 0;

        if (!$esTransparente && $totalFacturaDeclarado > 0) {
            foreach ($items as $item) {
                $nombre = trim($item['nombre'] ?? '');
                $cantidad = max(1, intval($item['cantidad'] ?? 1)); 
                $baseTotalLinea = floatval($item['total_linea'] ?? 0); 
                $tasaIva = floatval($item['tasa_iva'] ?? 19); 

                if ($baseTotalLinea <= 0) continue;

                $ivaTotalLinea = round($baseTotalLinea * ($tasaIva / 100), 2);
                $precioFinalLinea = round($baseTotalLinea + $ivaTotalLinea, 2);
                $costoUnitarioConIva = round($precioFinalLinea / $cantidad, 2);

                $subtotalBases2 += $baseTotalLinea;
                $totalCalculadoSuma2 += $precioFinalLinea;

                $desgloseItems2[] = [
                    'nombre' => $nombre ?: 'Ítem sin nombre',
                    'cantidad' => $cantidad,
                    'costo_unitario_con_iva' => $costoUnitarioConIva,
                    'base_total' => $baseTotalLinea,
                    'iva_total' => $ivaTotalLinea,
                    'total_linea' => $precioFinalLinea,
                    'tasa_iva' => $tasaIva
                ];
            }
        }

        $itemsEvaluar = $esTransparente ? $desgloseItems1 : ($subtotalBases2 > 0 ? $desgloseItems2 : $desgloseItems1);

        // --- PERSISTENCIA INICIAL DE LA AUDITORÍA EN BD ---
        $pdo->beginTransaction();
        $sqlFactura = "INSERT INTO aud_auditorias_facturas 
            (proveedor_nombre, proveedor_nit, proveedor_telefono, numero_factura, total_declarado, transparente, fecha_auditoria) 
            VALUES (:p_nombre, :p_nit, :p_tel, :num_fac, :total, :trans, NOW())";
            
        $stmtFactura = $pdo->prepare($sqlFactura);
        $stmtFactura->execute([
            ':p_nombre' => $proveedorNombre,
            ':p_nit' => $proveedorNit,
            ':p_tel' => $proveedorTelefono,
            ':num_fac' => $numeroFactura,
            ':total' => $totalFacturaDeclarado,
            ':trans' => $esTransparente ? 1 : 0
        ]);
        $facturaId = intval($pdo->lastInsertId());

        // Guardar ítems auditados asociados
        $sqlItem = "INSERT INTO aud_items_auditados (factura_id, nombre, cantidad, costo_unitario_con_iva, base_total, iva_total, total_linea, tasa_iva) 
                    VALUES (:f_id, :nom, :cant, :costo, :base, :iva, :t_linea, :t_iva)";
        $stmtItem = $pdo->prepare($sqlItem);

        foreach ($itemsEvaluar as $iData) {
            $stmtItem->execute([
                ':f_id' => $facturaId,
                ':nom' => $iData['nombre'],
                ':cant' => $iData['cantidad'],
                ':costo' => $iData['costo_unitario_con_iva'],
                ':base' => $iData['base_total'],
                ':iva' => $iData['iva_total'],
                ':t_linea' => $iData['total_linea'],
                ':t_iva' => $iData['tasa_iva']
            ]);
        }
        $pdo->commit();

        // --- VERIFICAR EXISTENCIA EN TABLA aud_insumos ---
        $inventarioVerificado = [];
        $stmtCheck = $pdo->prepare("SELECT id, nombre, stock, costo_unitario, tasa_iva FROM aud_insumos WHERE nombre = :nombre");

        foreach ($itemsEvaluar as $item) {
            $stmtCheck->execute([':nombre' => $item['nombre']]);
            $existente = $stmtCheck->fetch();

            if ($existente) {
                $inventarioVerificado[] = [
                    'en_bd' => true,
                    'id_insumo' => $existente['id'],
                    'nombre' => $item['nombre'],
                    'stock_actual' => $existente['stock'],
                    'costo_actual' => $existente['costo_unitario'],
                    'stock_nuevo' => $item['cantidad'],
                    'costo_nuevo' => $item['costo_unitario_con_iva'],
                    'tasa_iva' => $item['tasa_iva']
                ];
            } else {
                $inventarioVerificado[] = [
                    'en_bd' => false,
                    'id_insumo' => null,
                    'nombre' => $item['nombre'],
                    'stock_nuevo' => $item['cantidad'],
                    'costo_nuevo' => $item['costo_unitario_con_iva'],
                    'tasa_iva' => $item['tasa_iva']
                ];
            }
        }

        echo json_encode([
            'factura_id' => $facturaId,
            'transparente' => $esTransparente,
            'total_declarado' => $totalFacturaDeclarado,
            'items_iva_incluido' => $desgloseItems1,
            'items_base_pura' => $desgloseItems2,
            'total_pagar_1' => $totalCalculadoSuma1,
            'total_pagar_2' => $totalCalculadoSuma2,
            'verificacion_aud_insumos' => $inventarioVerificado
        ]);
        exit;
    }

    // ==========================================
    // ACCIÓN PUT: Procesar decisiones (Inventario)
    // ==========================================
    if ($method === 'PUT') {
        $rawInput = file_get_contents("php://input");
        $putData = json_decode($rawInput, true);
        $decisiones = $putData['decisiones'] ?? [];

        if (empty($decisiones) || !is_array($decisiones)) {
            http_response_code(400);
            echo json_encode(['error' => 'No se recibieron decisiones de actualización válidas.']);
            exit;
        }

        $pdo->beginTransaction();

        $stmtInsert = $pdo->prepare("INSERT INTO aud_insumos (nombre, stock, costo_unitario, tasa_iva, ultima_actualizacion) 
                                     VALUES (:nombre, :stock, :costo, :iva, NOW())");

        $stmtUpdateSum = $pdo->prepare("UPDATE aud_insumos 
                                        SET stock = stock + :stock, costo_unitario = :costo, tasa_iva = :iva, ultima_actualizacion = NOW() 
                                        WHERE id = :id");

        $stmtUpdateOver = $pdo->prepare("UPDATE aud_insumos 
                                         SET stock = :stock, costo_unitario = :costo, tasa_iva = :iva, ultima_actualizacion = NOW() 
                                         WHERE id = :id");

        foreach ($decisiones as $dec) {
            $nombre = trim($dec['nombre']);
            $stock = intval($dec['stock']);
            $costo = floatval($dec['costo']);
            $iva = floatval($dec['tasa_iva']);
            $modo = $dec['modo']; 
            $idInsumo = intval($dec['id_insumo'] ?? 0);

            if ($modo === 'crear' || $idInsumo <= 0) {
                $stmtInsert->execute([
                    ':nombre' => $nombre,
                    ':stock' => $stock,
                    ':costo' => $costo,
                    ':iva' => $iva
                ]);
            } elseif ($modo === 'sumar') {
                $stmtUpdateSum->execute([
                    ':stock' => $stock,
                    ':costo' => $costo,
                    ':iva' => $iva,
                    ':id' => $idInsumo
                ]);
            } elseif ($modo === 'actualizar') {
                $stmtUpdateOver->execute([
                    ':stock' => $stock,
                    ':costo' => $costo,
                    ':iva' => $iva,
                    ':id' => $idInsumo
                ]);
            }
        }

        $pdo->commit();
        echo json_encode(['success' => true, 'mensaje' => 'Inventario actualizado correctamente.']);
        exit;
    }

    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido.']);

} catch (Exception $e) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode(['error' => 'Excepción en el servidor: ' . $e->getMessage()]);
}
