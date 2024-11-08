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
if (preg_match('/\/api\/inspection\.php\/([0-9a-f\-]+)$/i', $_SERVER['REQUEST_URI'], $matches) && $_SERVER['REQUEST_METHOD'] === 'GET'){
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
        echo json_encode(['Not Found']);
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
        echo json_encode(['Invalid arguments']);
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
        echo json_encode(['Invalid arguments']);
        exit;
    }
    else {
        $doctor = $stmt ->fetch(PDO::FETCH_ASSOC);
    }
    //Налицие диагноза
    $query = "SELECT id,icdDiagnosisID,description,type,createtime FROM diagnos WHERE inspectionid = :id";
    $stmt = $conn->prepare($query);
    $stmt->bindValue(':id', $inspectionid);
    $stmt->execute();

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
            "createTime" => $diagnos["createtime"],
            "code" => $icd["mkb_code"],
            "name" => $icd["mkb_name"],
            "description" => $diagnos["description"],
            "type" => $diagnos["type"],
        ];
        $Dresponses[] = $Dresponse;
    }

    $query = "SELECT id,createtime,inspectionid,specialityid FROM consultation WHERE inspectionid = :id";
    $stmt = $conn->prepare($query);
    $stmt->bindValue(':id', $inspectionid);
    $stmt->execute();
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
        "createTime" => $inspection["createtime"],
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
    header('Content-type: application/json');
    http_response_code(200);
    echo json_encode(["Inspection found and successfully extracted" => $response]);
}elseif (preg_match('/\/api\/inspection\.php\/([0-9a-f\-]+)$/i', $_SERVER['REQUEST_URI'], $matches) && $_SERVER['REQUEST_METHOD'] === 'PUT'){
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
    $doctorid = $stmt->fetch(PDO::FETCH_ASSOC);
    $inspectionid=$matches[1];
    $data = json_decode(file_get_contents('php://input'), true);
    if (!isset($data['complains'], $data['treatment'], $data['conclusion'], $data['diagnoses'])) {
        http_response_code(400);
        echo json_encode(['Invalid arguments']);
        exit;
    }
    if (($data['conslusion'] !='Disease') && ($data['conclusion'] !='Recovery') && ($data['conclusion'] !='Death')) {
        http_response_code(400);
        echo json_encode(['Invalid arguments']);
        exit;
    }
    if (($data['conclusion'] =='Death') && ($data['nextVisitDate']!=null)) {
        http_response_code(400);
        echo json_encode(['Bad request']);
        exit;
    }
    if (($data['conclusion'] =='Death') && ($data['deathDate']=='null')) {
        http_response_code(400);
        echo json_encode(['Bad request']);
        exit;
    }
    $counter = 0;
    foreach ($data['diagnoses'] as $diagnos)
    {
        if($diagnos['type']!='Main' && $diagnos['type']!='Concomitant' && $diagnos['type']!='Complication')
        {
            http_response_code(400);
            echo json_encode(['Invalid arguments']);
            exit;
        }
        if($diagnos['type']=='Main')
        {
            $counter++;
        }
        if ($counter>1)
        {
            http_response_code(400);
            echo json_encode(['Invalid arguments']);
            exit;
        }
    }
    $createdAt = date('Y-m-d\TH:i:s.u\Z');
    
    $query = "SELECT * FROM inspection WHERE id = :id";
    $stmt = $conn->prepare($query);
    $stmt->bindValue(':id', $inspectionid);
    $stmt->execute();
    $author = $stmt->fetch(PDO::FETCH_ASSOC);

    if($author['author']!= $doctorid['id']){
        http_response_code(500);
        echo json_encode(["User doesn't have editing rights (not the inspection author)"]);
    }
    else{
        if ($stmt->rowCount() != 1) {
            http_response_code(400);
            echo json_encode(['Invalid arguments']);
        exit;
        }else{
            $query = "UPDATE inspection SET anamnesis=:anamnesis,complaints=:complaints,treatment=:treatment,conclusion=:conclusion,nextVisitDate=:nextVisitDate,deathDate=:deathDate WHERE id = :id";
            $stmt = $conn->prepare($query);
            $stmt->bindValue(':id', $inspectionid);
            $stmt->bindValue(':anamnesis', $data['anamnesis']);
            $stmt->bindValue(':complaints', $data['complaints']);
            $stmt->bindValue(':treatment', $data['treatment']);
            $stmt->bindValue(':conclusion', $data['conclusion']);
            $stmt->bindValue(':nextVisitDate', $data['nextVisitDate']);
            $stmt->bindValue(':deathDate', $data['deathDate']);
            $stmt->execute();
            
            $query = "DELETE FROM diagnos WHERE inspectionid = :id";
            $stmt = $conn->prepare($query);
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
            http_response_code(200);
            echo json_encode(["Sucсess"]);
        }
    }
}elseif (preg_match('/\/api\/inspection\.php\/([0-9a-f\-]+)\/chain$/i', $_SERVER['REQUEST_URI'], $matches) && $_SERVER['REQUEST_METHOD'] === 'GET') {
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
    $inspectionId = $matches[1];
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

    $inspections = getInspectionChain($inspectionId, $conn);
    http_response_code(200);
    echo json_encode(["Success" => $inspections]);
}else {
    http_response_code(404);
    echo json_encode(["Not Found"]);
}
$conn = null;
?>