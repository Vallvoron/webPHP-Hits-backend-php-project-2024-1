<?php
$dsn = "pgsql:host=localhost;dbname=Back";
$user = 'postgres';
$password = 'V';

try {
    $conn = new PDO($dsn, $user, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); // Включение обработки ошибок PDO
} catch (PDOException $e) {
    echo "Ошибка подключения: " . $e->getMessage();
}
if ($_SERVER['REQUEST_URI'] === '/api/consultation.php' && $_SERVER['REQUEST_METHOD'] === 'GET') {

}
else {
    // Отправка ответа с ошибкой
    http_response_code(401);
    echo json_encode(["message" => "Метод не разрешен"]);
}