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
    exit();
}

if (strpos($_SERVER['REQUEST_URI'], '/api/report.php/icdrootsreport')!== false && $_SERVER['REQUEST_METHOD'] === 'GET') {

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

    $flag=true;
    if($_GET['icdRoots']!= ''){
        $roots = explode(',', $_GET['icdRoots']);
        $flag=true;
    }else {$roots = [null]; $flag=false;}

    $query = "SELECT * FROM diagnos" ;
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $diagnoses=$stmt->fetchAll(PDO::FETCH_ASSOC);
    $filtered = [];
    $query = "SELECT * FROM diagnos" ;
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $diagnoses=$stmt->fetchAll(PDO::FETCH_ASSOC);
    $filtered = [];
    if (!$flag){

    }
    else{
        foreach ($roots as $root){
            $Rooted=[];
            foreach($diagnoses as $diagnos){

                $idparent = 0;
                $idcurent = $diagnos['icddiagnosisid'];
                $query = "SELECT id_parent FROM icd10 WHERE (id = :id)";
                $stmt = $conn->prepare($query);
                $stmt->bindValue(':id', $idcurent);
                $stmt->execute();
                $idparent=$stmt->fetch(PDO::FETCH_ASSOC);

                while($idparent['id_parent']!=NULL){
                    $idcurent= $idparent['id_parent'];
                    $query = "SELECT id_parent FROM icd10 WHERE (id = :id)";
                    $stmt = $conn->prepare($query);
                    $stmt->bindValue(':id', $idcurent);
                    $stmt->execute();
                    $idparent=$stmt->fetch(PDO::FETCH_ASSOC);
                }
                if ($idcurent == $root){
                    $Rooted[] = $diagnos['id'];
                    break;
                }
            }

            if (empty($filtered)) {
                $Rooted[]=null;
            }
            $RootedWithQuotes = array_map(function($value) {
                return "'$value'"; 
            }, $Rooted);
            $Rooted=$RootedWithQuotes;
            $filtered[]=$Rooted;
        }
    }

    function getInspections($patientId, $conn, $filtered) {
        $query = "SELECT i.* 
        FROM inspection i 
        ";

        if (!empty($filtered)) {

            $query .= "JOIN diagnos d ON i.id = d.inspectionid ";
        }

        $query .= "WHERE i.patientid = :id ";

        if (!empty($filtered)) {

            $query .= "AND d.id IN (:icdIds) ";
        }

        $query .= "GROUP BY i.id";
        if (!empty($filtered)) {
            $query = str_replace(':icdIds', implode(', ', $filtered), $query);
        }
        $stmt = $conn->prepare($query);
        $stmt->bindValue(':id', $patientId);
        $stmt->execute();
        $Pinspection = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return count($Pinspection);
    }

    $query = "SELECT * FROM patient";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $patients = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($patients);
    $records=[];
    $summ=[];
    foreach ($patients as $patient) 
    {
        $visit=[];
        $Psumm=0;
        for ($i=0;$i<count($roots);$i++){
            $count=getInspections($patient['id'], $conn, $filtered[$i]);
            $visit[]=$count;
            $Psumm+=$count;
        }
        $summ[]=$Psumm;
        $record=[
            "patientName" => $patient['name'],
            "patientBirthdate"=>$patient['birthday'],
            "gender"=>$patient['gender'],
            "visitsByRoot"=> [$filtered[$i]=>$visit],
        ];
        $records[]=$record;
    }
    $filters = [
        "start"=> "2024-11-08T08:40:53.388Z",
        "end"=> "2024-11-08T08:40:53.389Z",
        "icdRoots"=> $roots 
    ];
    $response=[
        "filters"=>$filters,
        "records"=>$records,
        "summaryByRoot"=>$summ,
    ];
    echo json_encode($response);
}else {
    http_response_code(404);
    echo json_encode(["message" => "Not Found"]);
}
$conn = null;
?>