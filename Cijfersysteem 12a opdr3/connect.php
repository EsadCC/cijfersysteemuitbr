<?php
$host = 'localhost';
$db   = 'cijfersysteem';
$user = 'root';
$pass = '';
$dsn = "mysql:host=$host;dbname=$db;charset=utf8mb4";

$opties = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

// Maak verbinding
try {
    $conn = new PDO($dsn, $user, $pass, $opties);

} catch (PDOException $e) {
    // foutmelding
    die('Verbinding mislukt: ' . $e->getMessage());
}
?>