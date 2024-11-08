<?php
$dsn = "pgsql:host=localhost;dbname=Back";
$user = 'postgres';
$password = 'V';
try {
    $conn = new PDO($dsn, $user, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); // Включение обработки ошибок PDO
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["Internal Server Error"]);
}
if ($_SERVER['REQUEST_URI'] === '/api/doctor.php/register' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // Получение данных из запроса
    $data = json_decode(file_get_contents('php://input'), true);
    if (!isset($data['name'], $data['password'], $data['gender'], $data['speciality'])) {
        http_response_code(400);
        echo json_encode(['Invalid arguments']);
        exit;
    }
    if (($data['gender'] !='Male') && ($data['gender'] !='Female')) {
        http_response_code(400);
        echo json_encode(['Invalid arguments']);
        exit;
    }

    // Проверка корректности форматов данных
    if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode(['Invalid arguments']);
        exit;
    }

    // Проверка наличия существующего пользователя с таким email
    $query = "SELECT 1 FROM doctor WHERE email = :email";
    $stmt = $conn->prepare($query);
    $stmt->bindValue(':email', $data['email']);
    $stmt->execute();
    if ($stmt->rowCount() > 0) {
        http_response_code(400);
        echo json_encode(['Пользователь с таким email уже существует']);
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
        http_response_code(200);
        echo json_encode(['Doctor was registered', 'token' => $token]);

    } catch (PDOException $e) {
        http_response_code(400);
        echo json_encode(['Invalid arguments']);
    }

    // Закрытие соединения с базой данных
    $conn = null;

}else if ($_SERVER['REQUEST_URI'] === '/api/doctor.php/login' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    
    $query = "SELECT id FROM doctor WHERE email = :email and password = :password";
    $stmt = $conn->prepare($query);
    $stmt->bindValue(':email', $data['email']);
    $stmt->bindValue(':password', $data['password']);
    $stmt->execute();

    if ($stmt->rowCount() === 0) {
        http_response_code(400);
        echo json_encode(['Invalid arguments']);
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
    http_response_code(200);
    echo json_encode(['Success', 'token' => $token]);
    
    
}else if ($_SERVER['REQUEST_URI'] === '/api/doctor.php/profile' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $headers = getallheaders();
    $token = "";
    if (isset($headers['Authorization'])) {
    $token = explode(' ', $headers['Authorization'])[1];
    }

    $query = "SELECT id,createTime,name,birthday,gender,email,phone FROM doctor WHERE token = :token";
    $stmt = $conn->prepare($query);
    $stmt->bindValue(':token', $token);
    $stmt->execute();

    if ($stmt->rowCount() === 0) {
        http_response_code(401);
        echo json_encode(['Unauthorized']);
        exit;
    }

    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    $response = [
        "id" => $user["id"],
        "createTime" => $user["createtime"], 
        "name" => $user["name"],
        "birthday" => $user["birthday"],
        "gender" => $user["gender"],
        "email" => $user["email"],
        "phone" => $user["phone"]
    ];

    // Отправка ответа
    header('Content-type: application/json');
    http_response_code(200);
    echo json_encode(["Success" => $response]);

} elseif ($_SERVER['REQUEST_URI'] === '/api/doctor.php/profile' && $_SERVER['REQUEST_METHOD'] === 'PUT') {
    // Получение данных из тела запроса
    $data = json_decode(file_get_contents('php://input'), true);
    if (!isset($data['name'], $data['password'], $data['gender'])) {
        http_response_code(400);
        echo json_encode(['Invalid arguments']);
        exit;
    }
    if (($data['gender'] !='Male') && ($data['gender'] !='Female')) {
        http_response_code(400);
        echo json_encode(['Invalid arguments']);
        exit;
    }

    $headers = getallheaders();
    $token = "";
    if (isset($headers['Authorization'])) {
    $token = explode(' ', $headers['Authorization'])[1];
    }
    // Подготовка запроса к базе данных
    $query = "SELECT id,createTime,name,birthday,gender,email,phone FROM doctor WHERE token = :token";
    $stmt = $conn->prepare($query);
    $stmt->bindValue(':token', $token);
    $stmt->execute();

    // Проверка существования пользователя
    if ($stmt->rowCount() === 0) {
        http_response_code(401);
        echo json_encode(['Unauthorized']);
        exit;
    }
    // Подготовка запроса к базе данных
    $query = "UPDATE doctor SET email = :email, name = :name, birthday = :birthday, gender = :gender, phone = :phone WHERE token = :token";
    $stmt = $conn->prepare($query);
    $stmt->bindValue(':token', $token);
    $stmt->bindValue(':email', $data['email']);
    $stmt->bindValue(':name', $data['name']);
    $stmt->bindValue(':birthday', $data['birthday']);
    $stmt->bindValue(':gender', $data['gender']);
    $stmt->bindValue(':phone', $data['phone']);

    try {
        $stmt->execute();
        header('Content-type: application/json');
        http_response_code(200);
        echo json_encode(["Success"]);

    } catch (PDOException $e) {
        http_response_code(400);
        echo json_encode(['Invalid arguments']);
    }
} elseif ($_SERVER['REQUEST_URI'] === '/api/doctor.php/logout' && $_SERVER['REQUEST_METHOD'] === 'POST'){
    $headers = getallheaders();
    $token = "";
    if (isset($headers['Authorization'])) {
    $token = explode(' ', $headers['Authorization'])[1];
    }
    // Подготовка запроса к базе данных
    $query = "SELECT * FROM doctor WHERE token = :token";
    $stmt = $conn->prepare($query);
    $stmt->bindValue(':token', $token);
    $stmt->execute();

    // Проверка существования пользователя
    if ($stmt->rowCount() === 0) {
        http_response_code(401);
        echo json_encode(['Unauthorized']);
        exit;
    }
    $query = "UPDATE doctor SET token = NULL WHERE token = :token";
    $stmt = $conn->prepare($query);
    $stmt->bindValue(':token', $token);
    $stmt->execute();
    http_response_code(200);
    echo json_encode(['Sucsess']);
}else {
    http_response_code(404);
    echo json_encode(["Not Found"]);
}

?>  