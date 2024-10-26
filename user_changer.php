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

$userId = $_GET['id']; // Извлечение ID пользователя из URI

// Проверка метода запроса
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Подготовка запроса к базе данных
    $query = "SELECT * FROM users WHERE id = :id";
    $stmt = $conn->prepare($query);
    $stmt->bindValue(':id', $userId);
    $stmt->execute();

    // Проверка существования пользователя
    if ($stmt->rowCount() === 0) {
        http_response_code(404);
        echo json_encode(['error' => 'Пользователь не найден']);
        exit;
    }

    // Преобразование результата в массив
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // Формирование ответа в формате JSON
    $response = [
        "id" => $user["id"],
        "createTime" => $user["create_time"], // Предполагается, что в таблице есть столбец create_time
        "name" => $user["name"],
        "birthday" => $user["birthday"],
        "gender" => $user["gender"],
        "email" => $user["email"],
        "phone" => $user["phone"]
    ];

    // Отправка ответа
    header('Content-type: application/json');
    echo json_encode($response);

} elseif ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    // Получение данных из тела запроса
    $data = json_decode(file_get_contents('php://input'), true);

    // Проверка наличия необходимых полей
    if (!isset($data['email'], $data['name'], $data['birthday'], $data['gender'], $data['phone'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Недостаточно данных']);
        exit;
    }

    // Подготовка запроса к базе данных
    $query = "UPDATE users SET email = :email, name = :name, birthday = :birthday, gender = :gender, phone = :phone WHERE id = :id";
    $stmt = $conn->prepare($query);
    $stmt->bindValue(':id', $userId);
    $stmt->bindValue(':email', $data['email']);
    $stmt->bindValue(':name', $data['name']);
    $stmt->bindValue(':birthday', $data['birthday']);
    $stmt->bindValue(':gender', $data['gender']);
    $stmt->bindValue(':phone', $data['phone']);

    // Выполнение запроса к базе данных
    $stmt->execute();

    // Отправка ответа
    header('Content-type: application/json');
    http_response_code(200);
    echo json_encode(["message" => "Success"]);

} else {
    // Отправка ответа с ошибкой
    http_response_code(405);
    echo json_encode(["message" => "Метод не разрешен"]);
}

// Закрытие соединения с базой данных
$conn = null;

?>