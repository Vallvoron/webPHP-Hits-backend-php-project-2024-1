<?php
error_reporting(E_ALL);
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

$userId = null;
if ($_SERVER['REQUEST_URI'] === '/api/doctor.php/register' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // Получение данных из запроса
    $data = json_decode(file_get_contents('php://input'), true);


    // Проверка корректности форматов данных
    if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid arguments']);
        exit;
    }

    // Проверка наличия существующего пользователя с таким email
    $query = "SELECT 1 FROM doctor WHERE email = :email";
    $stmt = $conn->prepare($query);
    $stmt->bindValue(':email', $data['email']);
    $stmt->execute();
    if ($stmt->rowCount() > 0) {
        http_response_code(400);
        echo json_encode(['error' => 'Пользователь с таким email уже существует']);
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
    $doctorId = uniqid();
    $createdAt = date('Y-m-d\TH:i:s.u\Z'); 
    // Подготовка запроса на добавление пользователя
    $query = "INSERT INTO doctor (id, createTime, name, password, email, birthday, gender, phone, speciality, token) VALUES (:id, :createTime, :name, :password, :email, :birthday, :gender, :phone, :speciality, :token)";
    $stmt = $conn->prepare($query);
    $stmt->bindValue(':id', $doctorId);
    $stmt->bindValue(':createTime', $createdAt);
    $stmt->bindValue(':name', $data['name']);
    $stmt->bindValue(':password', $data['password']);
    $stmt->bindValue(':email', $data['email']);
    $stmt->bindValue(':birthday', $data['birthday']);
    $stmt->bindValue(':gender', $data['gender']);
    $stmt->bindValue(':phone', $data['phone']);
    $stmt->bindValue(':speciality', $data['speciality']);
    $stmt->bindValue(':token', $token);

    // Выполнение запроса с обработкой ошибок
    try {
        $stmt->execute();

        // Возвращение успешного ответа
        http_response_code(200);
        echo json_encode(['message' => 'Doctor was registered', 'token' => $token]);

    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Ошибка при регистрации врача: ' . $e->getMessage()]);
    }

    // Закрытие соединения с базой данных
    $conn = null;

}
else if ($_SERVER['REQUEST_URI'] === '/api/doctor.php/login' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // Получение данных из запроса
    $data = json_decode(file_get_contents('php://input'), true);

    // Проверка корректности формата email
    if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode(['error' => 'Некорректный email']);
        exit;
    }

    // Подготовка запроса к базе данных
    $query = "SELECT id FROM doctor WHERE email = :email and password = :password";
    $stmt = $conn->prepare($query);
    $stmt->bindValue(':email', $data['email']);
    $stmt->bindValue(':password', $data['password']);
    $stmt->execute();

    // Проверка существования пользователя
    if ($stmt->rowCount() === 0) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid arguments']);
        exit;
    }
    else {
        $userId = $stmt ->fetch(PDO::FETCH_ASSOC);
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
    $query = "UPDATE doctor SET token = :token WHERE id = :id";
    $stmt = $conn->prepare($query);
    $stmt->bindValue(':id', $userId['id']);
    $stmt->bindValue(':token', $token);
    $stmt->execute();
    echo json_encode(['token' => $token]);
    
}else if ($_SERVER['REQUEST_URI'] === '/api/doctor.php/profile' && $_SERVER['REQUEST_METHOD'] === 'GET' /*&& $userID!=null*/) {
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

} elseif ($_SERVER['REQUEST_URI'] === '/api/doctor.php/profile' && $_SERVER['REQUEST_METHOD'] === 'PUT' && $userID!=null) {
    // Получение данных из тела запроса
    $data = json_decode(file_get_contents('php://input'), true);

    // Проверка наличия необходимых полей
    if (!isset($data['email'], $data['name'], $data['birthday'], $data['gender'], $data['phone'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Недостаточно данных']);
        exit;
    }

    // Подготовка запроса к базе данных
    $query = "UPDATE doctor SET email = :email, name = :name, birthday = :birthday, gender = :gender, phone = :phone WHERE id = :id";
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

} elseif ($_SERVER['REQUEST_URI'] === '/api/doctor.php/logout' && $userID!=null)
{
    $query = "UPDATE doctor SET token = NULL WHERE id = :id";
    $stmt = $conn->prepare($query);
    $stmt->bindValue(':id', $userId);
    $stmt->execute();
}
else {
    // Обработка ошибок
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
}
?>