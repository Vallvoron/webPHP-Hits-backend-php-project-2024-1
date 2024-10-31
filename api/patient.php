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

// Проверка метода запроса
if ($_SERVER['REQUEST_URI'] === '/api/patient.php' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // Получение данных из тела запроса
    $data = json_decode(file_get_contents('php://input'), true);

    // Проверка наличия необходимых полей
    if (!isset($data['name'], $data['birthday'], $data['gender'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Недостаточно данных']);
        exit;
    }

    $createdAt = date('Y-m-d\TH:i:s.u\Z');
    // Генерация нового ID пациента (используя функцию uniqid)
    $patientId = uniqid(); 

    // Подготовка запроса к базе данных
    $query = "INSERT INTO patient (id, name, birthday, gender, createTime) VALUES (:id, :name, :birthday, :gender, :createTime)";
    $stmt = $conn->prepare($query);
    $stmt->bindValue(':id', $patientId);
    $stmt->bindValue(':name', $data['name']);
    $stmt->bindValue(':birthday', $data['birthday']);
    $stmt->bindValue(':gender', $data['gender']);
    $stmt->bindValue(':createTime', $createdAt);

    // Выполнение запроса к базе данных
    $stmt->execute();

    // Отправка ответа
    header('Content-type: application/json');
    http_response_code(201);
    echo json_encode(["message" => "Patient was registered", "id" => $patientId]);

} elseif (preg_match('/\/api\/patient\.php\/([0-9a-f\-]+)$/i', $_SERVER['REQUEST_URI'], $matches) && $_SERVER['REQUEST_METHOD'] === 'GET') {
    // Логика для обработки запроса GET patient card
    $patientid=$matches[1];
    
    $sql = "SELECT * FROM patient WHERE id = :patientid";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':patientid', $patientid);
    $stmt->execute();
    
    $patient = $stmt->fetch(PDO::FETCH_ASSOC);
    $response = [
        'id' => $patient["id"],
        'createTime' => $patient["createtime"],
        "name" => $patient["name"],
        "birthday" => $patient["birthday"],
        "gender" => $patient["gender"],
        ];
    echo json_encode($response);
}elseif (preg_match('/\/api\/patient\.php\/([0-9a-f\-]+)\/inspections$/i', $_SERVER['REQUEST_URI'], $matches) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $patientId=$matches[1];
    $id = uniqid(); 
    $createdAt = date('Y-m-d\TH:i:s.u\Z');
    $query = "INSERT INTO inspection (id,createtime,patientid,date,anamnesis,complaints,treatment,conclusion,nextVisitDate,deathDate,previousInspectionid,author) VALUES (:id,:createtime,:patientid,:date,:anamnesis,:complaints,:treatment,:conclusion,:nextVisitDate,:deathDate,:previousInspectionId,:author)";
    $stmt = $conn->prepare($query);
    $stmt->bindValue(':id', $id);
    $stmt->bindValue(':createtime', $createdAt);
    $stmt->bindValue(':patientid', $patientId);
    $stmt->bindValue(':date', $data['date']);
    $stmt->bindValue(':anamnesis', $data['anamnesis']);
    $stmt->bindValue(':complaints', $data['complaints']);
    $stmt->bindValue(':treatment', $data['treatment']);
    $stmt->bindValue(':conclusion', $data['conclusion']);
    $stmt->bindValue(':nextVisitDate', $data['nextVisitDate']);
    $stmt->bindValue(':deathDate', $data['deathDate']);
    $stmt->bindValue(':previousInspectionId', $data['previousInspectionId']);
    $stmt->bindValue(':author', '?');
    $stmt->execute();

    // Добавление диагностических данных
    foreach ($data['diagnoses'] as $diagnosis) {
        $Did = uniqid();
        $query = "INSERT INTO diagnos (id,icdDiagnosisID,inspectionid,description,type,createtime) VALUES (:id,:icddiagnosisid,:inspectionid,:description,:type,:createtime)";
        $stmt = $conn->prepare($query);
        $stmt->bindValue(':id', $Did);
        $stmt->bindValue(':icddiagnosisid', $diagnosis['icdDiagnosisId']);
        $stmt->bindValue(':inspectionid', $id);
        $stmt->bindValue(':description', $diagnosis['description']);
        $stmt->bindValue(':type', $diagnosis['type']);
        $stmt->bindValue(':createtime', $createdAt);
        $stmt->execute();
    }

    // Добавление консультаций
    foreach ($data['consultations'] as $consultation) {
        $Cid = uniqid();
        $query = "INSERT INTO consultation (id,inspectionid,specialityId,createtime) VALUES (:id,:inspectionid,:specialityId,:createtime)";
        $stmt = $conn->prepare($query);
        $stmt->bindValue(':id', $Cid);
        $stmt->bindValue(':inspectionid', $id);
        $stmt->bindValue(':specialityId', $consultation['specialityId']);
        $stmt->bindValue(':createtime', $createdAt);
        $stmt->execute();

        // Добавление комментариев
        if (isset($consultation['comment']) && isset($consultation['comment']['content'])) {
            $Commid = uniqid();
            $query = "INSERT INTO comment (id,consultationid,author,content,createtime) VALUES (:id,:consultationid,:author,:content,:createtime)";
            $stmt = $conn->prepare($query);
            $stmt->bindValue(':id', $Commid);
            $stmt->bindValue(':consultationid', $Cid);
            $stmt->bindValue(':author', '67222a47a586c');
            $stmt->bindValue(':content', $consultation['comment']['content']);
            $stmt->bindValue(':createtime', $createdAt);
            $stmt->execute();
        }
    }

    http_response_code(200);
    echo json_encode(["message" => "Осмотр добавлен"]);
}
else {
    // Отправка ответа с ошибкой
    http_response_code(401);
    echo json_encode(["message" => "Метод не разрешен"]);
}
// Закрытие соединения с базой данных
$conn = null;

?>