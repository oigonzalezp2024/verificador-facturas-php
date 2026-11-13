<?php
require_once 'conexion.php';
require_once 'GeneradorPdf.php';

class ReporteController {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function generarReporteFactura($facturaId) {
        if ($facturaId <= 0) {
            throw new Exception('ID de factura inválido.');
        }

        // 1. Consultar cabecera de la factura auditada
        $stmtFactura = $this->pdo->prepare("SELECT * FROM aud_auditorias_facturas WHERE id = :id");
        $stmtFactura->execute([':id' => $facturaId]);
        $factura = $stmtFactura->fetch();

        if (!$factura) {
            throw new Exception('No se encontró la auditoría solicitada.');
        }

        // 2. Consultar los ítems asociados
        $stmtItems = $this->pdo->prepare("SELECT * FROM aud_items_auditados WHERE factura_id = :factura_id");
        $stmtItems->execute([':factura_id' => $facturaId]);
        $items = $stmtItems->fetchAll();

        // 3. Instanciar y ejecutar el generador visual PDF
        $pdf = new GeneradorPdf($factura, $items);
        $pdf->construirPDF();
    }
}
