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
if (preg_match('/\/api\/inspection\.php\/([0-9a-f\-]+)$/i', $_SERVER['REQUEST_URI'], $matches) && $_SERVER['REQUEST_METHOD'] === 'GET')
{
    $inspectionid=$matches[1];
    $data = json_decode(file_get_contents('php://input'), true);
    $id = uniqid(); 
    $createdAt = date('Y-m-d\TH:i:s.u\Z');
    $query = "SELECT id,createtime,date,anamnesis,complaints,treatment,conclusion,nextVisitDate,deathDate,previousInspectionId FROM inspection WHERE id = :id";
    $stmt = $conn->prepare($query);
    $stmt->bindValue(':id', $inspectionid);
    $stmt->execute();
    if ($stmt->rowCount() === 0) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid arguments']);
        exit;
    }
    else {
        $inspection = $stmt ->fetch(PDO::FETCH_ASSOC);
        echo json_encode($inspection);
    }

    $query = "SELECT patientId FROM inspection WHERE id = :inspectionId";
    $stmt = $conn->prepare($query);
    $stmt->bindValue(':inspectionId', $inspectionId);
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    $query = "SELECT id,createtime,name,birthday,gender FROM patient WHERE id = :id";
    $stmt = $conn->prepare($query);
    $stmt->bindValue(':id', $inspectionid);
    $stmt->execute();
    if ($stmt->rowCount() === 0) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid arguments']);
        exit;
    }
    else {
        $patient = $stmt ->fetch(PDO::FETCH_ASSOC);
        echo json_encode($patient);
    }










    $Did = uniqid();
    $query = "SELECT id,icdDiagnosisID,inspectionid,description,type,createtime FROM diagnos WHERE inspectionid = :id; ORDER BY id";
    $stmt = $conn->prepare($query);
    $stmt->bindValue(':id', $Did);
    $stmt->execute();
    
    $Cid= uniqid();
    $query = "INSERT INTO consultation (id,inspectionid,specialityId,createtime) VALUES (:id,:inspectionid,:specialityId,:createtime)";
    $stmt = $conn->prepare($query);
    $stmt->bindValue(':id', $Cid);
    $stmt->bindValue(':inspectionid', $id);
    $stmt->bindValue(':specialityId', $data['specialityId']);
    $stmt->bindValue(':createtime', $createdAt);
    $stmt->execute();

    $Commid= uniqid();
    $query = "INSERT INTO comment (id,consultationid,author,content,createtime) VALUES (:id,:consultationid,:author,:content,:createtime)";
    $stmt = $conn->prepare($query);
    $stmt->bindValue(':id', $Commid);
    $stmt->bindValue(':consultationid', $Cid);
    $stmt->bindValue(':author','?');
    $stmt->bindValue(':content', $data['content']);
    $stmt->bindValue(':createtime', $createdAt);
    $stmt->execute();
}
else {
    // Отправка ответа с ошибкой
    http_response_code(401);
    echo json_encode(["message" => "Метод не разрешен"]);
}

// Закрытие соединения с базой данных
$conn = null;

?>