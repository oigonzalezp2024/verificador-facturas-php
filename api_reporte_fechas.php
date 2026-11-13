<?php
require_once 'conexion.php';
require_once 'ReporteFechasController.php';

$accion = $_GET['accion'] ?? 'pdf';
$fechaInicio = trim($_GET['inicio'] ?? '');
$fechaFin = trim($_GET['fin'] ?? '');

try {
    $controlador = new ReporteFechasController($pdo);

    if ($accion === 'json') {
        header('Content-Type: application/json; charset=utf-8');
        $resultado = $controlador->obtenerDatosPorFechas($fechaInicio, $fechaFin);
        echo json_encode($resultado);
    } else {
        $controlador->generarPdfPorFechas($fechaInicio, $fechaFin);
    }
} catch (Exception $e) {
    header('Content-Type: application/json; charset=utf-8', true, 400);
    echo json_encode(['error' => $e->getMessage()]);
}
