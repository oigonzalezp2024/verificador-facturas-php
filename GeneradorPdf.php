<?php
require('./fpdf/fpdf.php');

class GeneradorPdf extends FPDF {
    private $factura;
    private $items;

    public function __construct($factura, $items) {
        parent::__construct('P', 'mm', 'A4');
        $this->factura = $factura;
        $this->items = $items;
        $this->SetMargins(12, 12, 12);
        $this->SetAutoPageBreak(true, 18);
    }

    function Header() {
        // Franja superior decorativa
        $this->SetFillColor(30, 41, 59);
        $this->Rect(0, 0, 210, 4, 'F');

        $this->SetY(10);
        $this->SetFont('Arial', 'B', 15);
        $this->SetTextColor(15, 23, 42);
        $this->Cell(0, 8, utf8_decode('INFORME DE AUDITORÍA Y REGISTRO DE INVENTARIO'), 0, 1, 'C');
        
        $this->SetFont('Arial', '', 9.5);
        $this->SetTextColor(100, 116, 139);
        $this->Cell(0, 5, utf8_decode('Sistema de Control Comercial y Costos Unitarios'), 0, 1, 'C');
        
        $this->SetDrawColor(203, 213, 225);
        $this->SetLineWidth(0.4);
        $this->Line(12, 26, 198, 26);
        $this->Ln(8);
    }

    function Footer() {
        $this->SetY(-14);
        $this->SetDrawColor(226, 232, 240);
        $this->Line(12, $this->GetY(), 198, $this->GetY());
        $this->SetFont('Arial', 'I', 8);
        $this->SetTextColor(148, 163, 184);
        $this->Cell(93, 8, utf8_decode('Documento generado automáticamente - Control de Auditoría'), 0, 0, 'L');
        $this->Cell(93, 8, utf8_decode('Página ') . $this->PageNo() . ' de {nb} | Emitido: ' . date('Y-m-d H:i:s'), 0, 0, 'R');
    }

    private function dibujarCabeceraProductos($w) {
        $this->SetFont('Arial', 'B', 8.5);
        $this->SetFillColor(37, 99, 235);
        $this->SetTextColor(255, 255, 255);
        $this->SetDrawColor(37, 99, 235);

        $this->Cell($w[0], 8, utf8_decode(' Producto / Insumo'), 1, 0, 'L', true);
        $this->Cell($w[1], 8, utf8_decode('Cant.'), 1, 0, 'C', true);
        $this->Cell($w[2], 8, utf8_decode('Costo U. c/IVA'), 1, 0, 'R', true);
        $this->Cell($w[3], 8, utf8_decode('Base Neta'), 1, 0, 'R', true);
        $this->Cell($w[4], 8, utf8_decode('IVA Total'), 1, 0, 'R', true);
        $this->Cell($w[5], 8, utf8_decode('Total Línea'), 1, 1, 'R', true);
    }

    private function formatearTextoCelda($texto, $anchoColumna) {
        $textoLimpio = trim(preg_replace('/\s+/', ' ', (string)$texto));
        $textoCapitalizado = mb_convert_case($textoLimpio, MB_CASE_TITLE, "UTF-8");
        return utf8_decode($textoCapitalizado);
    }

    public function construirPDF() {
        $this->AliasNbPages();
        $this->AddPage();

        // 1. DATOS DEL PROVEEDOR Y DIAGNÓSTICO
        $this->SetFont('Arial', 'B', 11);
        $this->SetTextColor(15, 23, 42);
        $this->Cell(0, 7, utf8_decode('1. Datos del Proveedor y Diagnóstico de Recepción'), 0, 1, 'L');
        $this->Ln(1);

        $this->SetFillColor(248, 250, 252);
        $this->SetDrawColor(203, 213, 225);
        $this->SetLineWidth(0.2);

        // Fila 1: Proveedor y NIT
        $this->SetFont('Arial', 'B', 9);
        $this->SetTextColor(71, 85, 105);
        $this->Cell(32, 8, utf8_decode(' Proveedor:'), 'LTB', 0, 'L', true);
        $this->SetFont('Arial', '', 9);
        $this->SetTextColor(15, 23, 42);
        $this->Cell(63, 8, utf8_decode($this->factura['proveedor_nombre']), 'TB', 0, 'L', true);

        $this->SetFont('Arial', 'B', 9);
        $this->SetTextColor(71, 85, 105);
        $this->Cell(25, 8, utf8_decode(' NIT:'), 'TB', 0, 'L', true);
        $this->SetFont('Arial', '', 9);
        $this->SetTextColor(15, 23, 42);
        $this->Cell(66, 8, utf8_decode($this->factura['proveedor_nit']), 'RTB', 1, 'L', true);

        // Fila 2: Teléfono y Factura N°
        $this->SetFont('Arial', 'B', 9);
        $this->SetTextColor(71, 85, 105);
        $this->Cell(32, 8, utf8_decode(' Teléfono:'), 'LTB', 0, 'L', true);
        $this->SetFont('Arial', '', 9);
        $this->SetTextColor(15, 23, 42);
        $this->Cell(63, 8, utf8_decode($this->factura['proveedor_telefono']), 'TB', 0, 'L', true);

        $this->SetFont('Arial', 'B', 9);
        $this->SetTextColor(71, 85, 105);
        $this->Cell(25, 8, utf8_decode(' N° Factura:'), 'TB', 0, 'L', true);
        $this->SetFont('Arial', 'B', 9);
        $this->SetTextColor(37, 99, 235);
        $this->Cell(66, 8, utf8_decode($this->factura['numero_factura']), 'RTB', 1, 'L', true);

        // Fila 3: ID Auditoría y Fecha
        $this->SetFont('Arial', 'B', 9);
        $this->SetTextColor(71, 85, 105);
        $this->Cell(32, 8, utf8_decode(' ID Auditoría:'), 'LTB', 0, 'L', true);
        $this->SetFont('Arial', '', 9);
        $this->SetTextColor(15, 23, 42);
        $this->Cell(63, 8, '#' . $this->factura['id'], 'TB', 0, 'L', true);

        $this->SetFont('Arial', 'B', 9);
        $this->SetTextColor(71, 85, 105);
        $this->Cell(25, 8, utf8_decode(' F. Auditoría:'), 'TB', 0, 'L', true);
        $this->SetFont('Arial', '', 9);
        $this->SetTextColor(15, 23, 42);
        $this->Cell(66, 8, $this->factura['fecha_auditoria'], 'RTB', 1, 'L', true);

        // Fila 4: Total Declarado y Estado
        $this->SetFont('Arial', 'B', 9);
        $this->SetTextColor(71, 85, 105);
        $this->Cell(32, 8, utf8_decode(' Total Decl.:'), 'LTB', 0, 'L', true);
        $this->SetFont('Arial', 'B', 9);
        $this->SetTextColor(15, 23, 42);
        $this->Cell(63, 8, '$' . number_format($this->factura['total_declarado'], 2), 'TB', 0, 'L', true);

        $this->SetFont('Arial', 'B', 9);
        $this->SetTextColor(71, 85, 105);
        $this->Cell(25, 8, utf8_decode(' Estado:'), 'TB', 0, 'L', true);
        
        if ($this->factura['transparente']) {
            $this->SetTextColor(5, 150, 105);
            $estadoTexto = 'Transparente';
        } else {
            $this->SetTextColor(220, 38, 38);
            $estadoTexto = 'Discrepancia';
        }
        $this->SetFont('Arial', 'B', 9);
        $this->Cell(66, 8, utf8_decode($estadoTexto), 'RTB', 1, 'L', true);

        $this->Ln(7);

        // 2. DESGLOSE DE PRODUCTOS
        $this->SetFont('Arial', 'B', 11);
        $this->SetTextColor(15, 23, 42);
        $this->Cell(0, 7, utf8_decode('2. Desglose de Productos Expuestos en Línea'), 0, 1, 'L');
        $this->Ln(1);

        $w = [82, 10, 26, 24, 18, 26];
        $this->dibujarCabeceraProductos($w);

        $this->SetFont('Arial', '', 8.5);
        $this->SetTextColor(30, 41, 59);
        $this->SetDrawColor(203, 213, 225);
        
        $fill = false;
        $sumaBase = 0;
        $sumaIva = 0;
        $sumaTotal = 0;

        foreach ($this->items as $item) {
            $sumaBase += $item['base_total'];
            $sumaIva += $item['iva_total'];
            $sumaTotal += $item['total_linea'];

            $this->SetFillColor(248, 250, 252);
            
            $nombreFormateado = $this->formatearTextoCelda($item['nombre'], $w[0]);
            $nb = $this->NbLines($w[0] - 4, $nombreFormateado);
            $h = max(7, ($nb * 4.5) + 3);

            if ($this->GetY() + $h > $this->PageBreakTrigger) {
                $this->AddPage();
                $this->dibujarCabeceraProductos($w);
                $this->SetFont('Arial', '', 8.5);
                $this->SetTextColor(30, 41, 59);
                $this->SetDrawColor(203, 213, 225);
            }

            $x = $this->GetX();
            $y = $this->GetY();

            $this->Rect($x, $y, $w[0], $h, $fill ? 'DF' : 'D');
            $this->SetXY($x + 2, $y + 1.5);
            $this->MultiCell($w[0] - 4, 4.5, $nombreFormateado, 0, 'L');
            
            $this->SetXY($x + $w[0], $y);
            $this->Cell($w[1], $h, $item['cantidad'], 1, 0, 'C', $fill);
            $this->Cell($w[2], $h, '$' . number_format($item['costo_unitario_con_iva'], 2), 1, 0, 'R', $fill);
            $this->Cell($w[3], $h, '$' . number_format($item['base_total'], 2), 1, 0, 'R', $fill);
            $this->Cell($w[4], $h, '$' . number_format($item['iva_total'], 2), 1, 0, 'R', $fill);
            $this->Cell($w[5], $h, '$' . number_format($item['total_linea'], 2), 1, 1, 'R', $fill);
            
            $fill = !$fill;
        }

        // Fila de Totales
        $this->SetFont('Arial', 'B', 8.5);
        $this->SetFillColor(241, 245, 249);
        $this->SetTextColor(15, 23, 42);
        $this->Cell($w[0] + $w[1] + $w[2], 7.5, utf8_decode('TOTALES AUDITADOS '), 1, 0, 'R', true);
        $this->Cell($w[3], 7.5, '$' . number_format($sumaBase, 2), 1, 0, 'R', true);
        $this->Cell($w[4], 7.5, '$' . number_format($sumaIva, 2), 1, 0, 'R', true);
        $this->Cell($w[5], 7.5, '$' . number_format($sumaTotal, 2), 1, 1, 'R', true);

        $this->Ln(7);

        // 3. REGISTRO EN INVENTARIO
        if ($this->GetY() + 30 > $this->PageBreakTrigger) {
            $this->AddPage();
        }

        $this->SetFont('Arial', 'B', 11);
        $this->SetTextColor(15, 23, 42);
        $this->Cell(0, 7, utf8_decode('3. Sincronización en la Tabla de Insumos'), 0, 1, 'L');

        $this->SetFont('Arial', '', 8.8);
        $this->SetTextColor(71, 85, 105);
        $this->MultiCell(0, 4.5, utf8_decode('Los ítems fueron validados y sincronizados aplicando los costos unitarios reales calculados y actualizando existencias según la decisión del operador.'), 0, 'L');
        $this->Ln(2.5);

        $wInv = [106, 20, 32, 28];
        $this->dibujarCabeceraInventario($wInv);

        $this->SetFont('Arial', '', 8.5);
        $this->SetTextColor(30, 41, 59);
        $this->SetDrawColor(203, 213, 225);
        $fill = false;

        foreach ($this->items as $item) {
            $this->SetFillColor(240, 253, 244);
            
            $nombreFormateado = $this->formatearTextoCelda($item['nombre'], $wInv[0]);
            $nb = $this->NbLines($wInv[0] - 4, $nombreFormateado);
            $h = max(7, ($nb * 4.5) + 3);

            if ($this->GetY() + $h > $this->PageBreakTrigger) {
                $this->AddPage();
                $this->dibujarCabeceraInventario($wInv);
                $this->SetFont('Arial', '', 8.5);
                $this->SetTextColor(30, 41, 59);
                $this->SetDrawColor(203, 213, 225);
            }

            $x = $this->GetX();
            $y = $this->GetY();

            $this->Rect($x, $y, $wInv[0], $h, $fill ? 'DF' : 'D');
            $this->SetXY($x + 2, $y + 1.5);
            $this->MultiCell($wInv[0] - 4, 4.5, $nombreFormateado, 0, 'L');
            
            $this->SetXY($x + $wInv[0], $y);
            $this->Cell($wInv[1], $h, number_format($item['tasa_iva'], 2) . '%', 1, 0, 'C', $fill);
            $this->Cell($wInv[2], $h, '$' . number_format($item['costo_unitario_con_iva'], 2), 1, 0, 'R', $fill);
            $this->Cell($wInv[3], $h, $item['cantidad'] . ' Unidades', 1, 1, 'R', $fill);
            
            $fill = !$fill;
        }

        $this->Output('I', 'Informe_Auditoria_' . $this->factura['id'] . '.pdf');
    }

    private function dibujarCabeceraInventario($wInv) {
        $this->SetFont('Arial', 'B', 8.5);
        $this->SetFillColor(16, 185, 129);
        $this->SetTextColor(255, 255, 255);
        $this->SetDrawColor(16, 185, 129);

        $this->Cell($wInv[0], 8, utf8_decode(' Insumo Registrado'), 1, 0, 'L', true);
        $this->Cell($wInv[1], 8, utf8_decode('Tasa IVA'), 1, 0, 'C', true);
        $this->Cell($wInv[2], 8, utf8_decode('Costo U. Consolidado'), 1, 0, 'R', true);
        $this->Cell($wInv[3], 8, utf8_decode('Cantidad Stock'), 1, 1, 'R', true);
    }

    function NbLines($w, $txt) {
        $cw = isset($this->CurrentFont['cw']) ? $this->CurrentFont['cw'] : null;
        $s = str_replace("\r", '', (string)$txt);
        $nb = strlen($s);
        
        if ($nb == 0) return 1;
        
        $sep = -1;
        $i = 0;
        $j = 0;
        $l = 0;
        $nl = 1;
        
        while ($i < $nb) {
            $c = $s[$i];
            if ($c == "\n") {
                $i++;
                $sep = -1;
                $j = $i;
                $l = 0;
                $nl++;
                continue;
            }
            if ($c == ' ') {
                $sep = $i;
            }
            
            $l += $this->GetCharWidth($c);
            
            if ($l > $w) {
                if ($sep == -1) {
                    if ($i == $j) {
                        $i++;
                    }
                } else {
                    $i = $sep + 1;
                }
                $sep = -1;
                $j = $i;
                $l = 0;
                $nl++;
            } else {
                $i++;
            }
        }
        return $nl;
    }

    private function GetCharWidth($c) {
        if (isset($this->CurrentFont['cw'][$c])) {
            return ($this->CurrentFont['cw'][$c] * $this->FontSize) / 1000;
        }
        return $this->GetStringWidth($c);
    }
}
