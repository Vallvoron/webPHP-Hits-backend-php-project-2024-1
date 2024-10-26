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

// Получение параметров запроса
$name = $_GET['name'];
$page = $_GET['page'];
$size = $_GET['size'];

// Валидация параметров
if (!isset($name) || !isset($page) || !isset($size) || !is_numeric($page) || !is_numeric($size)) {
    die("Неверные параметры запроса.");
}

// Вычисление offset для пагинации
$offset = ($page - 1) * $size;

// Подготовка запроса к базе данных с использованием LIKE для поиска по имени
$query = "SELECT REC_CODE, MKB_CODE, MKB_NAME FROM icd10 WHERE REC_CODE LIKE :name OR MKB_CODE LIKE :name OR MKB_NAME LIKE :name";
$stmt = $conn->prepare($query);
$stmt->bindValue(':name', '%' . $name . '%');

// Получение общего количества записей
$stmt->execute();
$count = $stmt->rowCount();

// Выполнение запроса с использованием LIMIT и OFFSET для пагинации
$stmt = $conn->prepare($query . " LIMIT :size OFFSET :offset");
$stmt->bindValue(':name', '%' . $name . '%');
$stmt->bindValue(':size', $size, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();

// Формирование результата
$data = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $data[] = [
        "id" => uniqid(), // Генерация случайного ID для каждой записи
        "createTime" => date("Y-m-d\TH:i:s.u\Z"), // Текущее время в формате ISO 8601
        "code" => $row['MKB_CODE'],
        "name" => $row['MKB_NAME']
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

// Закрытие подключения
$conn = null;
?>