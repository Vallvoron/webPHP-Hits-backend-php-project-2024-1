<?php

// Подключение к базе данных с использованием PDO
$dsn = "pgsql:host=localhost;dbname=your_database_name";
$user = 'your_username';
$password = 'your_password';
try {
    $conn = new PDO($dsn, $user, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); // Включение обработки ошибок PDO
} catch (PDOException $e) {
    die("Ошибка подключения к базе данных: " . $e->getMessage());
}

// Проверка метода запроса
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Получение данных из тела запроса
    $data = json_decode(file_get_contents('php://input'), true);

    // Проверка наличия необходимых полей
    if (!isset($data['name'], $data['birthday'], $data['gender'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Недостаточно данных']);
        exit;
    }

    // Генерация нового ID пациента (используя функцию uniqid)
    $patientId = uniqid(); 

    // Подготовка запроса к базе данных
    $query = "INSERT INTO patients (id, name, birthday, gender) VALUES (:id, :name, :birthday, :gender)";
    $stmt = $conn->prepare($query);
    $stmt->bindValue(':id', $patientId);
    $stmt->bindValue(':name', $data['name']);
    $stmt->bindValue(':birthday', $data['birthday']);
    $stmt->bindValue(':gender', $data['gender']);

    // Выполнение запроса к базе данных
    $stmt->execute();

    // Отправка ответа
    header('Content-type: application/json');
    http_response_code(201);
    echo json_encode(["message" => "Patient was registered", "id" => $patientId]);

} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Логика для обработки GET-запроса
    // ...
} else {
    // Отправка ответа с ошибкой
    http_response_code(401);
    echo json_encode(["message" => "Метод не разрешен"]);
}

// Закрытие соединения с базой данных
$conn = null;

?>