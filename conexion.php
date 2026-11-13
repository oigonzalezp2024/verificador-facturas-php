<?php
date_default_timezone_set('America/Bogota');

// 1. Cargar el autoloader de Composer
require_once __DIR__ . '/vendor/autoload.php';

// 2. Inicializar Dotenv apuntando a la raíz del proyecto
try {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
    $dotenv->load();
} catch (\Throwable $e) {
    // Si no encuentra el archivo .env, puedes manejar una excepción o usar valores por defecto
}

// 3. Obtener los valores de las variables de entorno
$host    = $_ENV['DB_HOST'] ?? 'localhost';
$db      = $_ENV['DB_NAME'] ?? 'aud_auditoria';
$user    = $_ENV['DB_USER'] ?? 'root';
$pass    = $_ENV['DB_PASS'] ?? '';
$charset = $_ENV['DB_CHARSET'] ?? 'utf8mb4';
$tzone   = $_ENV['DB_TIMEZONE'] ?? '-05:00';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
    $pdo->exec("SET time_zone = '$tzone'");
} catch (\PDOException $e) {
    header('Content-Type: application/json; charset=utf-8', true, 500);
    echo json_encode(['error' => 'Error de conexión a la base de datos.']);
    exit;
}
