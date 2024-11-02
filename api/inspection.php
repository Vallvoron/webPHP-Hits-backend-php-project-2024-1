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
    $query = "SELECT * FROM inspection WHERE id = :id";
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
    }

    $patientid=$inspection['patientid'];

    $query = "SELECT id,createtime,name,birthday,gender FROM patient WHERE id = :id";
    $stmt = $conn->prepare($query);
    $stmt->bindValue(':id', $patientid);
    $stmt->execute();
    if ($stmt->rowCount() === 0) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid arguments']);
        exit;
    }
    else {
        $patient = $stmt ->fetch(PDO::FETCH_ASSOC);
    }

    $doctorid=$inspection['author'];

    $query = "SELECT id,createTime,name,birthday,gender,email,phone FROM doctor WHERE id = :id";
    $stmt = $conn->prepare($query);
    $stmt->bindValue(':id', $doctorid);
    $stmt->execute();
    if ($stmt->rowCount() === 0) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid arguments']);
        exit;
    }
    else {
        $doctor = $stmt ->fetch(PDO::FETCH_ASSOC);
    }

    $query = "SELECT id,icdDiagnosisID,description,type,createtime FROM diagnos WHERE inspectionid = :id";
    $stmt = $conn->prepare($query);
    $stmt->bindValue(':id', $inspectionid);
    $stmt->execute();
    if ($stmt->rowCount() === 0) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid arguments']);
        exit;
    }
    else {
        $Dresponses=[];
        $diagnoses = $stmt ->fetchAll(PDO::FETCH_ASSOC);
        foreach ($diagnoses as $diagnos)
        {
            $query = "SELECT mkb_code,mkb_name FROM icd10 WHERE id = :id";
            $stmt = $conn->prepare($query);
            $stmt->bindValue(':id', $diagnos['icddiagnosisid']);
            $stmt->execute();
            $icd = $stmt ->fetch(PDO::FETCH_ASSOC);
            $Dresponse = [
                "id" => $diagnos["id"],
                "createTime" => $diagnos["createtime"], // Предполагается, что в таблице есть столбец create_time
                "code" => $icd["mkb_code"],
                "name" => $icd["mkb_name"],
                "description" => $diagnos["description"],
                "type" => $diagnos["type"],
            ];
            $Dresponses[] = $Dresponse;
        }
    }

    $query = "SELECT id,createtime,inspectionid,specialityid FROM consultation WHERE inspectionid = :id";
    $stmt = $conn->prepare($query);
    $stmt->bindValue(':id', $inspectionid);
    $stmt->execute();
    if ($stmt->rowCount() === 0) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid arguments']);
        exit;
    }
    else {
        $consultations = $stmt ->fetchAll(PDO::FETCH_ASSOC);
        $Cresponses=[];
        foreach($consultations as $consultation){
            $query = "SELECT id,name,createTime FROM speciality WHERE id = :id";
            $stmt = $conn->prepare($query);
            $stmt->bindValue(':id', $consultation['specialityid']);
            $stmt->execute();
            $speciality = $stmt ->fetch(PDO::FETCH_ASSOC);

            $query = "SELECT id,author,createTime,content,modifyTime FROM comment WHERE consultationid = :id AND parentid=id";
            $stmt = $conn->prepare($query);
            $stmt->bindValue(':id', $consultation['id']);
            $stmt->execute();
            $comment = $stmt ->fetch(PDO::FETCH_ASSOC);
            $query = "SELECT id,createTime,name,birthday,gender,email,phone FROM doctor WHERE id = :id";
            $stmt = $conn->prepare($query);
            $stmt->bindValue(':id', $comment['author']);
            $stmt->execute();
            $author = $stmt ->fetch(PDO::FETCH_ASSOC);
            $Commresponse =[
                "id" => $comment["id"],
                "createTime" => $comment["createtime"],
                "content"=>$comment["content"],
                "author"=> $author,
                "modifyTime"=>$comment["modifytime"]
            ];
            $Cresponse = [
                "id" => $consultation["id"],
                "createTime" => $consultation["createtime"],
                "inspectionId" => $consultation["inspectionid"],
                "speciality" => $speciality,
                "rootComment" => $Commresponse,
            ];
            $Cresponses[] = $Cresponse;
        }
        $response = [
            "id" => $inspection["id"],
            "createTime" => $inspection["createtime"], // Предполагается, что в таблице есть столбец create_time
            "date" => $inspection["date"],
            "anamnesis" => $inspection["anamnesis"],
            "complaints" => $inspection["complaints"],
            "treatment" => $inspection["treatment"],
            "conclusion" => $inspection["conclusion"],
            "nextVisitDate" => $inspection["nextvisitdate"],
            "deathDate" => $inspection["deathdate"],
            "previousInspectionId" => $inspection["previousinspectionid"],
            "patient" => $patient,
            "doctor" => $doctor,
            "diagnoses" => $Dresponses,
            "consultations" => $Cresponses
        ];
        echo json_encode($response);
    }
}elseif (preg_match('/\/api\/inspection\.php\/([0-9a-f\-]+)$/i', $_SERVER['REQUEST_URI'], $matches) && $_SERVER['REQUEST_METHOD'] === 'PUT'){
    $inspectionid=$matches[1];
    $data = json_decode(file_get_contents('php://input'), true);
    $createdAt = date('Y-m-d\TH:i:s.u\Z');
    
    $query = "SELECT * FROM inspection WHERE id = :id";
    $stmt = $conn->prepare($query);
    $stmt->bindValue(':id', $inspectionid);
    $stmt->execute();

    if ($stmt->rowCount() != 1) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid arguments']);
    exit;
    }else{
        $request = "UPDATE inspection SET anamnesis=:anamnesis,complaints=:complaints,treatment=:treatment,conclusion=:conclusion,nextVisitDate=:nextVisitDate,deathDate=:deathDate WHERE id = :id";
        $stmt = $conn->prepare($request);
        $stmt->bindValue(':id', $inspectionid);
        $stmt->bindValue(':anamnesis', $data['anamnesis']);
        $stmt->bindValue(':complaints', $data['complaints']);
        $stmt->bindValue(':treatment', $data['treatment']);
        $stmt->bindValue(':conclusion', $data['conclusion']);
        $stmt->bindValue(':nextVisitDate', $data['nextVisitDate']);
        $stmt->bindValue(':deathDate', $data['deathDate']);
        $stmt->execute();
        
        $request = "DELETE FROM diagnos WHERE inspectionid = :id";
        $stmt = $conn->prepare($request);
        $stmt->bindParam(':id', $inspectionid);
        $stmt->execute();
        
        foreach ($data['diagnoses'] as $diagnosis) {
            $Did = uniqid();
            $query = "INSERT INTO diagnos (id,icdDiagnosisID,inspectionid,description,type,createtime) VALUES (:id,:icddiagnosisid,:inspectionid,:description,:type,:createtime)";
            $stmt = $conn->prepare($query);
            $stmt->bindValue(':id', $Did);
            $stmt->bindValue(':icddiagnosisid', $diagnosis['icdDiagnosisId']);
            $stmt->bindValue(':inspectionid', $inspectionid);
            $stmt->bindValue(':description', $diagnosis['description']);
            $stmt->bindValue(':type', $diagnosis['type']);
            $stmt->bindValue(':createtime', $createdAt);
            $stmt->execute();
        }
        echo json_encode(["Sucsess"]);
    }
}elseif (preg_match('/\/api\/inspection\.php\/([0-9a-f\-]+)\/chain$/i', $_SERVER['REQUEST_URI'], $matches) && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $inspectionId = $matches[1];
    $inspections = [];
    function getInspectionChain($inspectionId, $conn) {

        $query = "SELECT * FROM inspection WHERE id = :inspectionId";
        $stmt = $conn->prepare($query);
        $stmt->bindValue(':inspectionId', $inspectionId);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $inspection = $stmt->fetch(PDO::FETCH_ASSOC);

            $query = "SELECT id,createTime,name,birthday,gender,email,phone FROM doctor WHERE id = :id";
            $stmt = $conn->prepare($query);
            $stmt->bindValue(':id', $inspection['author']);
            $stmt->execute();
            $doctor = $stmt->fetch(PDO::FETCH_ASSOC);

            $query = "SELECT id,createTime,name,birthday,gender FROM patient WHERE id = :id";
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
                "doctor" => $doctor,
                "patientId" => $inspection["patientid"],
                "patient" => $patient,
                "diagnosis" => $Dresponses,
                "hasChain" => !is_null($inspection["previousinspectionid"]),
                "hasNested" => !is_null($inspection["previousinspectionid"])
            ];

            $inspections[] = $inspectionData;

            if (!is_null($inspection["previousinspectionid"])) {
                $inspections = array_merge($inspections, getInspectionChain($inspection["previousinspectionid"], $conn));
            }

            return $inspections;
        } else {
            return [];
        }
    }

    $inspections = getInspectionChain($inspectionId, $conn);
    echo json_encode($inspections);
}
else {
    // Отправка ответа с ошибкой
    http_response_code(401);
    echo json_encode(["message" => "Метод не разрешен"]);
}

// Закрытие соединения с базой данных
$conn = null;

?>