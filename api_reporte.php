<?php
require_once 'conexion.php';
require_once 'ReporteController.php';

$facturaId = intval($_GET['id'] ?? 0);

try {
    $controlador = new ReporteController($pdo);
    $controlador->generarReporteFactura($facturaId);
} catch (Exception $e) {
    header('Content-Type: application/json; charset=utf-8', true, 400);
    echo json_encode(['error' => $e->getMessage()]);
}
