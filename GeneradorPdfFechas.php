<?php
require_once('./fpdf/fpdf.php');

class GeneradorPdfFechas extends FPDF {
    private $datos;

    public function __construct($datos) {
        parent::__construct('P', 'mm', 'A4');
        $this->datos = $datos;
        $this->SetMargins(12, 12, 12);
        $this->SetAutoPageBreak(true, 18);
    }

    function Header() {
        $this->SetFillColor(30, 41, 59);
        $this->Rect(0, 0, 210, 4, 'F');

        $this->SetY(10);
        $this->SetFont('Arial', 'B', 15);
        $this->SetTextColor(15, 23, 42);
        $this->Cell(0, 8, utf8_decode('INFORME CONSOLIDADO DE CARGADO DE FACTURAS'), 0, 1, 'C');
        
        $this->SetFont('Arial', '', 9.5);
        $this->SetTextColor(100, 116, 139);
        $this->Cell(0, 5, utf8_decode('Sistema de Control Comercial, Auditoría e Inventario'), 0, 1, 'C');
        
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
        $this->Cell(93, 8, utf8_decode('Reporte Histórico - Control de Auditoría'), 0, 0, 'L');
        $this->Cell(93, 8, utf8_decode('Página ') . $this->PageNo() . ' de {nb} | Emitido: ' . date('Y-m-d H:i:s'), 0, 0, 'R');
    }

    private function dibujarCabeceraTabla($w) {
        $this->SetFont('Arial', 'B', 7.8);
        $this->SetFillColor(37, 99, 235);
        $this->SetTextColor(255, 255, 255);
        $this->SetDrawColor(37, 99, 235);

        $this->Cell($w[0], 8, utf8_decode('ID'), 1, 0, 'C', true);
        $this->Cell($w[1], 8, utf8_decode('Fecha Registro'), 1, 0, 'C', true);
        $this->Cell($w[2], 8, utf8_decode(' Proveedor / Factura'), 1, 0, 'L', true);
        $this->Cell($w[3], 8, utf8_decode('Estado'), 1, 0, 'C', true);
        $this->Cell($w[4], 8, utf8_decode('Ítems'), 1, 0, 'C', true);
        $this->Cell($w[5], 8, utf8_decode('Base Neta'), 1, 0, 'R', true);
        $this->Cell($w[6], 8, utf8_decode('IVA Total'), 1, 0, 'R', true);
        $this->Cell($w[7], 8, utf8_decode('Total Decl.'), 1, 1, 'R', true);
    }

    private function ajustarTexto($texto, $maxChars = 24) {
        $limpio = trim(preg_replace('/\s+/', ' ', (string)$texto));
        if (mb_strlen($limpio, 'UTF-8') > $maxChars) {
            $limpio = mb_substr($limpio, 0, $maxChars - 3, 'UTF-8') . '...';
        }
        return utf8_decode($limpio);
    }

    public function construirPDF() {
        $this->AliasNbPages();
        $this->AddPage();

        $resumen = $this->datos['resumen'];
        $facturas = $this->datos['facturas'];

        // 1. RESUMEN DEL PERÍODO
        $this->SetFont('Arial', 'B', 11);
        $this->SetTextColor(15, 23, 42);
        $this->Cell(0, 7, utf8_decode('1. Resumen Ejecutivo del Período'), 0, 1, 'L');
        $this->Ln(1);

        $this->SetFillColor(248, 250, 252);
        $this->SetDrawColor(203, 213, 225);
        $this->SetLineWidth(0.2);

        $this->SetFont('Arial', 'B', 9);
        $this->SetTextColor(71, 85, 105);
        $this->Cell(38, 8, utf8_decode(' Rango Consultado:'), 'LTB', 0, 'L', true);
        $this->SetFont('Arial', '', 9.5);
        $this->SetTextColor(15, 23, 42);
        $this->Cell(55, 8, $this->datos['inicio'] . ' al ' . $this->datos['fin'], 'TB', 0, 'L', true);

        $this->SetFont('Arial', 'B', 9);
        $this->SetTextColor(71, 85, 105);
        $this->Cell(38, 8, utf8_decode(' Facturas Auditadas:'), 'LTB', 0, 'L', true);
        $this->SetFont('Arial', 'B', 9.5);
        $this->SetTextColor(37, 99, 235);
        $this->Cell(55, 8, $resumen['total_facturas'] . ' registros', 'RTB', 1, 'L', true);

        $this->SetFont('Arial', 'B', 9);
        $this->SetTextColor(71, 85, 105);
        $this->Cell(38, 8, utf8_decode(' Total Declarado:'), 'LTB', 0, 'L', true);
        $this->SetFont('Arial', 'B', 9.5);
        $this->SetTextColor(15, 23, 42);
        $this->Cell(55, 8, '$' . number_format($resumen['gran_total'], 2), 'TB', 0, 'L', true);

        $this->SetFont('Arial', 'B', 9);
        $this->SetTextColor(71, 85, 105);
        $this->Cell(38, 8, utf8_decode(' Clasificación:'), 'LTB', 0, 'L', true);
        $this->SetFont('Arial', '', 8.8);
        $this->SetTextColor(15, 23, 42);
        $this->Cell(55, 8, utf8_decode($resumen['transparentes'] . ' Transp. | ' . $resumen['discrepancias'] . ' Base Pura'), 'RTB', 1, 'L', true);

        $this->Ln(7);

        // 2. RELACIÓN DE FACTURAS
        $this->SetFont('Arial', 'B', 11);
        $this->SetTextColor(15, 23, 42);
        $this->Cell(0, 7, utf8_decode('2. Detalle Cronológico de Facturas Cargadas'), 0, 1, 'L');
        $this->Ln(1);

        // Suma exacta = 186mm
        $w = [11, 26, 44, 21, 17, 22, 21, 24];
        $this->dibujarCabeceraTabla($w);

        $this->SetDrawColor(203, 213, 225);
        $fill = false;

        foreach ($facturas as $f) {
            $altoFila = 9.5;
            if ($this->GetY() + $altoFila > $this->PageBreakTrigger) {
                $this->AddPage();
                $this->dibujarCabeceraTabla($w);
                $this->SetDrawColor(203, 213, 225);
            }

            $this->SetFillColor(248, 250, 252);
            $this->SetTextColor(30, 41, 59);
            $this->SetFont('Arial', '', 7.6);

            $this->Cell($w[0], $altoFila, '#' . $f['id'], 1, 0, 'C', $fill);
            $this->Cell($w[1], $altoFila, $f['fecha_auditoria'], 1, 0, 'C', $fill);

            // Celda compuesta de 2 líneas: Proveedor + Factura/NIT
            $xProv = $this->GetX();
            $yProv = $this->GetY();
            $this->Rect($xProv, $yProv, $w[2], $altoFila, $fill ? 'DF' : 'D');

            $provNombre = $this->ajustarTexto($f['proveedor_nombre'] ?? 'Sin proveedor', 25);
            $subDetalle = $this->ajustarTexto(($f['numero_factura'] ?? 'S/N') . ' | ' . ($f['proveedor_nit'] ?? ''), 28);

            $this->SetXY($xProv + 1, $yProv + 1.2);
            $this->SetFont('Arial', 'B', 7.6);
            $this->Cell($w[2] - 2, 3.5, $provNombre, 0, 2, 'L');

            $this->SetFont('Arial', '', 6.8);
            $this->SetTextColor(100, 116, 139);
            $this->Cell($w[2] - 2, 3.5, $subDetalle, 0, 0, 'L');

            // Restaurar cursor y estilo para el resto de la fila
            $this->SetXY($xProv + $w[2], $yProv);
            $this->SetFont('Arial', 'B', 7.6);

            if ((int)$f['transparente'] === 1) {
                $this->SetTextColor(5, 150, 105);
                $estadoStr = 'Transparente';
            } else {
                $this->SetTextColor(220, 38, 38);
                $estadoStr = 'Base Pura';
            }
            $this->Cell($w[3], $altoFila, utf8_decode($estadoStr), 1, 0, 'C', $fill);

            $this->SetFont('Arial', '', 7.6);
            $this->SetTextColor(30, 41, 59);
            $this->Cell($w[4], $altoFila, $f['total_items'] . ' (' . $f['unidades_totales'] . 'u)', 1, 0, 'C', $fill);
            $this->Cell($w[5], $altoFila, '$' . number_format($f['suma_base'], 2), 1, 0, 'R', $fill);
            $this->Cell($w[6], $altoFila, '$' . number_format($f['suma_iva'], 2), 1, 0, 'R', $fill);

            $this->SetFont('Arial', 'B', 7.6);
            $this->Cell($w[7], $altoFila, '$' . number_format($f['total_declarado'], 2), 1, 1, 'R', $fill);

            $fill = !$fill;
        }

        // Fila de Totales
        $this->SetFont('Arial', 'B', 7.8);
        $this->SetFillColor(241, 245, 249);
        $this->SetTextColor(15, 23, 42);
        $this->Cell($w[0] + $w[1] + $w[2] + $w[3] + $w[4], 8, utf8_decode('TOTALES CONSOLIDADOS '), 1, 0, 'R', true);
        $this->Cell($w[5], 8, '$' . number_format($resumen['gran_base'], 2), 1, 0, 'R', true);
        $this->Cell($w[6], 8, '$' . number_format($resumen['gran_iva'], 2), 1, 0, 'R', true);
        $this->Cell($w[7], 8, '$' . number_format($resumen['gran_total'], 2), 1, 1, 'R', true);

        $nombreArchivo = 'Consolidado_Facturas_' . $this->datos['inicio'] . '_al_' . $this->datos['fin'] . '.pdf';
        $this->Output('I', $nombreArchivo);
    }
}
