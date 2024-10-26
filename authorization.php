<?php

// Подключение к базе данных с использованием PDO
$dsn = "pgsql:host=localhost;dbname=Back";
$user = 'postgres';
$password = 'V';
try {
    $conn = new PDO($dsn, $user, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); // Включение обработки ошибок PDO
} catch (PDOException $e) {
    die("Ошибка подключения к базе данных: " . $e->getMessage());
}

if ($_SERVER['REQUEST_URI'] === '/api/doctor/login') {
    // Получение данных из запроса
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Проверка наличия всех необходимых полей
    if (!isset($email, $password)) {
        http_response_code(400);
        echo json_encode(['error' => 'Недостаточно данных']);
        exit;
    }

    // Проверка корректности формата email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode(['error' => 'Некорректный email']);
        exit;
    }

    // Подготовка запроса к базе данных
    $query = "SELECT * FROM users WHERE email = :email";
    $stmt = $conn->prepare($query);
    $stmt->bindValue(':email', $email);
    $stmt->execute();

    // Проверка существования пользователя
    if ($stmt->rowCount() === 0) {
        http_response_code(401);
        echo json_encode(['error' => 'Пользователь не найден']);
        exit;
    }

    // Получение хэшированного пароля из базы данных
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $hashedPassword = $row['password'];

    // Проверка пароля
    if (!password_verify($password, $hashedPassword)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid arguments']);
        exit;
    }

    // Генерация токена
    function generateRandomString($length = 10) {
        $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, strlen($characters) - 1)];
        }
        return $randomString;
    }
    $token = generateRandomString(10);

    // Возвращение токена
    echo json_encode(['token' => $token]);
    
    // Закрытие соединения с базой данных
    $conn = null;
} else {
    // Обработка ошибок
    http_response_code(500);
    echo json_encode(['error' => 'Iternal server error']);
}