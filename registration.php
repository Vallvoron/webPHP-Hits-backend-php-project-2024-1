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

if ($_SERVER['REQUEST_URI'] === '/api/doctor/register') {
    // Получение данных из запроса
    $name = $_POST['name'];
    $password = $_POST['password'];
    $email = $_POST['email'];
    $birthday = $_POST['birthday'];
    $gender = $_POST['gender'];
    $phone = $_POST['phone'];
    $speciality = $_POST['speciality'];

    // Проверка наличия всех необходимых полей
    if (!isset($name, $password, $email, $birthday, $gender, $phone, $speciality)) {
        http_response_code(400);
        echo json_encode(['error' => 'Недостаточно данных']);
        exit;
    }

    // Проверка корректности форматов данных
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode(['error' => 'Некорректный email']);
        exit;
    }

    // Проверка наличия существующего пользователя с таким email
    $query = "SELECT 1 FROM users WHERE email = :email";
    $stmt = $conn->prepare($query);
    $stmt->bindValue(':email', $email);
    $stmt->execute();
    if ($stmt->rowCount() > 0) {
        http_response_code(400);
        echo json_encode(['error' => 'Пользователь с таким email уже существует']);
        exit;
    }

    // Хеширование пароля
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

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

    // Подготовка запроса на добавление пользователя
    $query = "INSERT INTO users (id, name, password, email, birthday, gender, phone, speciality) VALUES (:id, :name, :password, :email, :birthday, :gender, :phone, :speciality)";
    $stmt = $conn->prepare($query);
    $stmt->bindValue(':id', $token);
    $stmt->bindValue(':name', $name);
    $stmt->bindValue(':password', $hashedPassword);
    $stmt->bindValue(':email', $email);
    $stmt->bindValue(':birthday', $birthday);
    $stmt->bindValue(':gender', $gender);
    $stmt->bindValue(':phone', $phone);
    $stmt->bindValue(':speciality', $speciality);

    // Выполнение запроса
    $stmt->execute();

    // Возвращение успешного ответа
    http_response_code(200);
    echo json_encode(['message' => 'Doctor was registered', 'token' => $token]);

    // Закрытие соединения с базой данных
    $conn = null;

} else {
    // Обработка ошибок
    http_response_code(404);
    echo json_encode(['error' => 'Страница не найдена']);
}