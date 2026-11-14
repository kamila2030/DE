<?php
// /opt/lampp/htdocs/DE/dezhursrtvo/dezhurstvo/halpers/db.php
$host   = 'localhost';
$dbname = 'dezhurstvo'; // <-- ИСПРАВЛЕНО НА ПРАВИЛЬНУЮ БАЗУ
$user   = 'root';
$pass   = '';

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $user, $pass,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    die('Ошибка подключения к базе данных: ' . $e->getMessage());
}