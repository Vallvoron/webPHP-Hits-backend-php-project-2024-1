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

if (strpos($_SERVER['REQUEST_URI'], '/api/dictionary.php/icd10')!== false) {// Получение параметров запроса
$request = $_GET['request'];
$page = $_GET['page'];
$size = $_GET['size'];

// Валидация параметров
if (!isset($request) || !isset($page) || !isset($size) || !is_numeric($page) || !is_numeric($size)) {
    die("Неверные параметры запроса.");
}

// Вычисление offset для пагинации
$offset = ($page - 1) * $size;

// Подготовка запроса к базе данных с использованием LIKE для поиска по имени
$query = "SELECT ID, MKB_CODE, MKB_NAME FROM icd10 WHERE MKB_CODE LIKE :request OR MKB_NAME LIKE :request";
$stmt = $conn->prepare($query);
$stmt->bindValue(':request', '%' . $request . '%');

// Получение общего количества записей
$stmt->execute();
$count = $stmt->rowCount();

// Выполнение запроса с использованием LIMIT и OFFSET для пагинации
$stmt = $conn->prepare($query . " LIMIT :size OFFSET :offset");
$stmt->bindValue(':request', '%' . $request . '%'); 
$stmt->bindValue(':size', $size, PDO::PARAM_INT); 
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT); // Добавлен `bindValue` для `:offset`
$stmt->execute();

// Формирование результата
$data = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $data[] = [
        "id" => $row['id'], // Генерация случайного ID для каждой записи
        "createTime" => date("Y-m-d\TH:i:s.u\Z"), // Текущее время в формате ISO 8601
        "code" => $row['mkb_code'],
        "name" => $row['mkb_name']
    ];
}

// Формирование JSON-ответа
$response = [
    "records" => $data,
    "pagination" => [
        "size" => $size,
        "count" => $count,
        "current" => $page
    ]
];

// Вывод результата
header('Content-Type: application/json');
echo json_encode($response);
}
else if ($_SERVER['REQUEST_URI'] === '/api/dictionary.php/icd10/roots')
{
    $stmt = $pdo->prepare('SELECT * FROM icd10 WHERE ID_PARENT IS NULL;');
    $stmt->execute();
    $roots = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($roots);
}
else {
    // Отправка ответа с ошибкой
    http_response_code(401);
    echo json_encode(["message" => "Метод не разрешен"]);
}
// Закрытие подключения
$conn = null;
?>