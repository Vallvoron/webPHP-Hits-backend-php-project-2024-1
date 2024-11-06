<?php

// Подключение к базе данных с использованием PDO
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

if ($_SERVER['REQUEST_URI'] === '/api/patient.php' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (true){
        $query = "SELECT id FROM doctor WHERE token = :token";
        $stmt = $conn->prepare($query);
        $stmt->bindValue(':token', $token);
        $stmt->execute();
        if ($stmt->rowCount() === 0) {
        http_response_code(401);
        echo json_encode(['Unauthorized']);
        exit;
        }
    }
    $data = json_decode(file_get_contents('php://input'), true);
    if (!isset($data['name'], $data['birthday'], $data['gender'])) {
        http_response_code(400);
        echo json_encode(['Invalid arguments']);
        exit;
    }

    $createdAt = date('Y-m-d\TH:i:s.u\Z');
    $patientId = uniqid(); 

    $query = "INSERT INTO patient (id, name, birthday, gender, createTime) VALUES (:id, :name, :birthday, :gender, :createTime)";
    $stmt = $conn->prepare($query);
    $stmt->bindValue(':id', $patientId);
    $stmt->bindValue(':name', $data['name']);
    $stmt->bindValue(':birthday', $data['birthday']);
    $stmt->bindValue(':gender', $data['gender']);
    $stmt->bindValue(':createTime', $createdAt);
    $stmt->execute();

    header('Content-type: application/json');
    http_response_code(200);
    echo json_encode(["Patient was registered" => $patientId]);
} elseif (preg_match('/\/api\/patient\.php\/([0-9a-f\-]+)\/inspections$/i', $_SERVER['REQUEST_URI'], $matches) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (true){
        $headers = getallheaders();
        $token = "";
        if (isset($headers['Authorization'])) {
        $token = explode(' ', $headers['Authorization'])[1];
        }
        $query = "SELECT id FROM doctor WHERE token = :token";
        $stmt = $conn->prepare($query);
        $stmt->bindValue(':token', $token);
        $stmt->execute();
        if ($stmt->rowCount() === 0) {
        http_response_code(401);
        echo json_encode(['Unauthorized']);
        exit;
        }
    }
    $data = json_decode(file_get_contents('php://input'), true);
    $patientId=$matches[1];
    $headers = getallheaders();
    $token = "";
    if (isset($headers['Authorization'])) {
        $token = explode(' ', $headers['Authorization'])[1];
    }
    $query = "SELECT id FROM doctor WHERE token = :token";
    $stmt = $conn->prepare($query);
    $stmt->bindValue(':token', $token);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    $token =$user['id'];
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
    $stmt->bindValue(':author', $token);
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
            $query = "INSERT INTO comment (id,consultationid,parentid,author,content,createtime,modifytime) VALUES (:id,:consultationid,:id,:author,:content,:createtime,:createtime)";
            $stmt = $conn->prepare($query);
            $stmt->bindValue(':id', $Commid);
            $stmt->bindValue(':consultationid', $Cid);
            $stmt->bindValue(':author', $token);
            $stmt->bindValue(':content', $consultation['comment']['content']);
            $stmt->bindValue(':createtime', $createdAt);
            $stmt->execute();
        }
    }

    header('Content-type: application/json');
    http_response_code(200);
    echo json_encode(["Success" => $Cid]);
} elseif (preg_match('/\/api\/patient\.php\/([0-9a-f\-]+)$/i', $_SERVER['REQUEST_URI'], $matches) && $_SERVER['REQUEST_METHOD'] === 'GET') {
    if (true){
        $headers = getallheaders();
        $token = "";
        if (isset($headers['Authorization'])) {
        $token = explode(' ', $headers['Authorization'])[1];
        }
        $query = "SELECT id FROM doctor WHERE token = :token";
        $stmt = $conn->prepare($query);
        $stmt->bindValue(':token', $token);
        $stmt->execute();
        if ($stmt->rowCount() === 0) {
        http_response_code(401);
        echo json_encode(['Unauthorized']);
        exit;
        }
    }
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
    header('Content-type: application/json');
    http_response_code(200);
    echo json_encode(["Success" => $response]);
}elseif (strpos($_SERVER['REQUEST_URI'], '/api/patient.php')!== false && $_SERVER['REQUEST_METHOD'] === 'GET'){
   $headers = getallheaders();
        $token = "";
        if (isset($headers['Authorization'])) {
        $token = explode(' ', $headers['Authorization'])[1];
        }
        $query = "SELECT id FROM doctor WHERE token = :token";
        $stmt = $conn->prepare($query);
        $stmt->bindValue(':token', $token);
        $stmt->execute();
        if ($stmt->rowCount() === 0) {
        http_response_code(401);
        echo json_encode(['Unauthorized']);
        exit;
        }

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

    // Преобразование результата в массив
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // Получение параметров запроса
    $name = $_GET['name'] ?? '';
    $conclusions = $_GET['conclusions'] ?? '';
    $sorting = $_GET['sorting'] ?? 'NameAsc';
    $sheduledVisits = filter_var($_GET['sheduledVisits'], FILTER_VALIDATE_BOOLEAN) ?? false;
    $onlyMine = filter_var($_GET['onlyMine'], FILTER_VALIDATE_BOOLEAN) ?? false;
    $page = $_GET['page'] ?? 1;
    $size = $_GET['size'] ?? 10;

    // Получение ID доктора из токена (не реализовано в данном примере, нужно добавить свою логику)

    // Создание SQL-запроса
    $sql = "SELECT patient.id AS id, patient.createTime AS createTime, patient.name AS name, patient.birthday AS birthday, patient.gender AS gender
        FROM patient 
        JOIN (
            SELECT patientId, MAX(createTime) as maxCreateTime
            FROM inspection
            GROUP BY patientId
        ) latest_inspection ON patient.id = latest_inspection.patientId
        JOIN inspection ON patient.id = inspection.patientId AND inspection.createTime = latest_inspection.maxCreateTime";

    // Фильтр по имени пациента
    if (!empty($name)) {
        $sql .= " WHERE patient.name ILIKE '%' || :name || '%'"; 
    } else {
        $sql .= " WHERE TRUE";
    }

    // Фильтр по заключениям
    if ($conclusions!='') {
        $sql .= " AND inspection.conclusion = :conclusions";
    }

    // Фильтр по id доктора
    if ($onlyMine && !empty($user)) {
        $sql .= " AND inspection.author = :doctorId";
    }

    // Фильтр по запланированным визитам
    if ($sheduledVisits) {
        $sql .= " AND inspection.nextVisitDate IS NOT NULL";
    }

    // Сортировка
    switch ($sorting) {
        case 'NameAsc':
            $sql .= " GROUP BY patient.id, patient.createTime, patient.name ORDER BY patient.name ASC";
            break;
        case 'NameDesc':
            $sql .= " GROUP BY patient.id, patient.createTime, patient.name ORDER BY patient.name DESC";
            break;
        case 'CreateAsc':
            $sql .= " GROUP BY patient.id, patient.createTime, patient.name ORDER BY patient.createTime ASC";
            break;
        case 'CreateDesc':
            $sql .= " GROUP BY patient.id, patient.createTime, patient.name ORDER BY patient.createTime DESC";
            break;
        case 'InspectionAsc':
            $sql .= " GROUP BY patient.id, patient.createTime, patient.name, inspection.nextVisitDate ORDER BY inspection.nextVisitDate ASC";
            break;
        case 'InspectionDesc':
            $sql .= " GROUP BY patient.id, patient.createTime, patient.name, inspection.nextVisitDate ORDER BY inspection.nextVisitDate DESC";
            break;
    }

    // Пагинация
    $offset = ($page - 1) * $size;
    $sql .= " LIMIT :size OFFSET :offset";

    // Подготовка запроса
    $stmt = $conn->prepare($sql);

    // Привязка параметров
    $stmt->bindParam(':name', $name);
    if ($conclusions!='') {
    $stmt->bindParam(':conclusions', $conclusions);}
    if ($onlyMine=='true' && !empty($user)) {
    $stmt->bindParam(':doctorId', $user['id']);}
    $stmt->bindParam(':size', $size, PDO::PARAM_INT);
    $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);

    // Выполнение запроса
    $stmt->execute();

    // Извлечение данных
    $patients = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Подсчет общего количества пациентов
    $countSql = "SELECT COUNT(DISTINCT patient.id) 
                FROM patient 
                JOIN inspection ON patient.id = inspection.patientId";

    // Добавление условий фильтрации из исходного запроса
    $countSql .= substr($sql, strpos($sql, ' WHERE'));
    $countSql = str_replace("LIMIT :size OFFSET :offset", "", $countSql);
    $countStmt = $conn->prepare($countSql);

    // Привязка параметров
    $countStmt->bindParam(':name', $name);
    if ($conclusions!='') {
    $countStmt->bindParam(':conclusions', $conclusions);}
    if ($onlyMine=='true' && !empty($user)) {
    $countStmt->bindParam(':doctorId', $doctorId);}

    // Выполнение запроса
    $countStmt->execute();
    $count = $countStmt->fetchColumn();

    // Формирование ответа
    $response = [
        'patients' => $patients,
        'pagination' => [
            'size' => $size,
            'count' => $count,
            'current' => $page
        ]
    ];

    // Отправка ответа
    header('Content-Type: application/json');
    echo json_encode($response);
}elseif (preg_match('/\/api\/patient\.php\/([0-9a-f\-]+)\/inspections$/i', $_SERVER['REQUEST_URI'], $matches) && $_SERVER['REQUEST_METHOD'] === 'GET') {
    if (true){
        $headers = getallheaders();
        $token = "";
        if (isset($headers['Authorization'])) {
        $token = explode(' ', $headers['Authorization'])[1];
        }
        $query = "SELECT id FROM doctor WHERE token = :token";
        $stmt = $conn->prepare($query);
        $stmt->bindValue(':token', $token);
        $stmt->execute();
        if ($stmt->rowCount() === 0) {
        http_response_code(401);
        echo json_encode(['Unauthorized']);
        exit;
        }
    }

    $grouped = filter_var($_GET['grouped'], FILTER_VALIDATE_BOOLEAN) ?? false;
    $page = $_GET['page'] ?? 1;
    $size = $_GET['size'] ?? 10;

    $patientId = $matches[1];
    $inspections = [];
    function getInspectionChain($inspectionId, $conn) {

        $query = "SELECT * FROM inspection WHERE id = :inspectionId";
        $stmt = $conn->prepare($query);
        $stmt->bindValue(':inspectionId', $inspectionId);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $inspection = $stmt->fetch(PDO::FETCH_ASSOC);

            $query = "SELECT name FROM doctor WHERE id = :id";
            $stmt = $conn->prepare($query);
            $stmt->bindValue(':id', $inspection['author']);
            $stmt->execute();
            $doctor = $stmt->fetch(PDO::FETCH_ASSOC);

            $query = "SELECT name FROM patient WHERE id = :id";
            $stmt = $conn->prepare($query);
            $stmt->bindValue(':id', $inspection['patientid']);
            $stmt->execute();
            $patient = $stmt->fetch(PDO::FETCH_ASSOC);

            $query = "SELECT id,icdDiagnosisID,description,type,createtime FROM diagnos WHERE inspectionid = :id";
            $stmt = $conn->prepare($query);
            $stmt->bindValue(':id', $inspectionId);
            $stmt->execute();
            $diagnoses = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $Dresponses=[];
            foreach ($diagnoses as $diagnos)
            {
                $query = "SELECT mkb_code,mkb_name FROM icd10 WHERE id = :id";
                $stmt = $conn->prepare($query);
                $stmt->bindValue(':id', $diagnos['icddiagnosisid']);
                $stmt->execute();
                $icd = $stmt ->fetch(PDO::FETCH_ASSOC);
                $Dresponse = [
                    "id" => $diagnos["id"],
                    "createTime" => $diagnos["createtime"],
                    "code" => $icd["mkb_code"],
                    "name" => $icd["mkb_name"],
                    "description" => $diagnos["description"],
                    "type" => $diagnos["type"],
                ];
                $Dresponses[] = $Dresponse;
            }

            $query = "SELECT id FROM inspection WHERE previousinspectionid = :id";
            $stmt = $conn->prepare($query);
            $stmt->bindValue(':id', $inspectionId);
            $stmt->execute();
            $nests=$stmt->fetchAll(PDO::FETCH_ASSOC);
            $inspectionData = [
                "id" => $inspection["id"],
                "createTime" => $inspection["createtime"],
                "previousId" => $inspection["previousinspectionid"],
                "date" => $inspection["date"],
                "conclusion" => $inspection["conclusion"],
                "doctorId" => $inspection["author"],
                "doctor" => $doctor['name'],
                "patientId" => $inspection["patientid"],
                "patient" => $patient['name'],
                "diagnosis" => $Dresponses,
                "hasChain" => !is_null($inspection["previousinspectionid"]),
                "hasNested" => $stmt->rowCount() > 0
            ];

            $inspections[] = $inspectionData;
            
            foreach($nests as $nest)
            {
                $inspections = array_merge($inspections, getInspectionChain( $nest['id'], $conn));
            }
            return $inspections;
        } else {
            http_response_code(400);
            echo json_encode(["Bad Request"]);
        }
    }
    if($grouped)
    {
        $query = "SELECT * FROM inspection WHERE patientid = :id AND previousinspectionid=null";
        $stmt = $conn->prepare($query);
        $stmt->bindValue(':id', $patientId);
        $stmt->execute();
        $inspection = $stmt->fetch(PDO::FETCH_ASSOC);

            $query = "SELECT name FROM doctor WHERE id = :id";
            $stmt = $conn->prepare($query);
            $stmt->bindValue(':id', $inspection['author']);
            $stmt->execute();
            $doctor = $stmt->fetch(PDO::FETCH_ASSOC);

            $query = "SELECT name FROM patient WHERE id = :id";
            $stmt = $conn->prepare($query);
            $stmt->bindValue(':id', $inspection['patientid']);
            $stmt->execute();
            $patient = $stmt->fetch(PDO::FETCH_ASSOC);

            $query = "SELECT id,icdDiagnosisID,description,type,createtime FROM diagnos WHERE inspectionid = :id";
            $stmt = $conn->prepare($query);
            $stmt->bindValue(':id', $inspectionId);
            $stmt->execute();
            $diagnoses = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $Dresponses=[];
            foreach ($diagnoses as $diagnos)
            {
                $query = "SELECT mkb_code,mkb_name FROM icd10 WHERE id = :id";
                $stmt = $conn->prepare($query);
                $stmt->bindValue(':id', $diagnos['icddiagnosisid']);
                $stmt->execute();
                $icd = $stmt ->fetch(PDO::FETCH_ASSOC);
                $Dresponse = [
                    "id" => $diagnos["id"],
                    "createTime" => $diagnos["createtime"],
                    "code" => $icd["mkb_code"],
                    "name" => $icd["mkb_name"],
                    "description" => $diagnos["description"],
                    "type" => $diagnos["type"],
                ];
                $Dresponses[] = $Dresponse;
            }

            $inspectionData = [
                "id" => $inspection["id"],
                "createTime" => $inspection["createtime"],
                "previousId" => $inspection["previousinspectionid"],
                "date" => $inspection["date"],
                "conclusion" => $inspection["conclusion"],
                "doctorId" => $inspection["author"],
                "doctor" => $doctor['name'],
                "patientId" => $inspection["patientid"],
                "patient" => $patient['name'],
                "diagnosis" => $Dresponses,
                "hasChain" => !is_null($inspection["previousinspectionid"]),
                "hasNested" => $stmt->rowCount() > 0
            ];

            $inspections[] = $inspectionData;
        $inspections = getInspectionChain($inspectionId, $conn);
    }
    else{
        
    }
    $inspections = getInspectionChain($inspectionId, $conn);
    http_response_code(200);
    echo json_encode(["Success" => $inspections]);
}else {
    http_response_code(404);
    echo json_encode(["Not Found"]);
}
$conn = null;
?>