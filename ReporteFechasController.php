<?php
require_once 'conexion.php';
require_once 'GeneradorPdfFechas.php';

class ReporteFechasController {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function obtenerDatosPorFechas($fechaInicio, $fechaFin) {
        if (empty($fechaInicio) || empty($fechaFin)) {
            throw new Exception('Debe proporcionar ambas fechas.');
        }

        $sql = "SELECT 
                    af.id,
                    af.proveedor_nombre,
                    af.proveedor_nit,
                    af.numero_factura,
                    af.total_declarado,
                    af.transparente,
                    af.fecha_auditoria,
                    COUNT(ia.id) AS total_items,
                    COALESCE(SUM(ia.cantidad), 0) AS unidades_totales,
                    COALESCE(SUM(ia.base_total), 0) AS suma_base,
                    COALESCE(SUM(ia.iva_total), 0) AS suma_iva
                FROM aud_auditorias_facturas af
                LEFT JOIN aud_items_auditados ia ON af.id = ia.factura_id
                WHERE DATE(af.fecha_auditoria) BETWEEN :inicio AND :fin
                GROUP BY 
                    af.id, 
                    af.proveedor_nombre, 
                    af.proveedor_nit, 
                    af.numero_factura, 
                    af.total_declarado, 
                    af.transparente, 
                    af.fecha_auditoria
                ORDER BY af.fecha_auditoria DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':inicio' => $fechaInicio,
            ':fin'    => $fechaFin
        ]);
        $facturas = $stmt->fetchAll();

        $resumen = [
            'total_facturas' => count($facturas),
            'transparentes'  => 0,
            'discrepancias'  => 0,
            'gran_base'      => 0,
            'gran_iva'       => 0,
            'gran_total'     => 0
        ];

        foreach ($facturas as $f) {
            if ((int)$f['transparente'] === 1) {
                $resumen['transparentes']++;
            } else {
                $resumen['discrepancias']++;
            }
            $resumen['gran_base']  += (float)$f['suma_base'];
            $resumen['gran_iva']   += (float)$f['suma_iva'];
            $resumen['gran_total'] += (float)$f['total_declarado'];
        }

        return [
            'inicio'   => $fechaInicio,
            'fin'      => $fechaFin,
            'resumen'  => $resumen,
            'facturas' => $facturas
        ];
    }

    public function generarPdfPorFechas($fechaInicio, $fechaFin) {
        $datos = $this->obtenerDatosPorFechas($fechaInicio, $fechaFin);
        if (empty($datos['facturas'])) {
            throw new Exception('No hay registros en el rango de fechas seleccionado para generar el PDF.');
        }

        $pdf = new GeneradorPdfFechas($datos);
        $pdf->construirPDF();
    }
}
