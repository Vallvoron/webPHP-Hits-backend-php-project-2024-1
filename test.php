<?php
error_reporting(E_ALL);
// Подключение к базе данных с использованием PDO
$dsn = "pgsql:host=localhost;dbname=Back";
$user = 'postgres';
$password = 'V';
try {
    $conn = new PDO($dsn, $user, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); // Включение обработки ошибок PDO
    echo "Подключение установлено успешно!"; 
} catch (PDOException $e) {
    echo "Ошибка подключения: " . $e->getMessage();
}
?>