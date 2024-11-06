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

if ($_SERVER['REQUEST_URI'] === '/api/dictionary.php/icd10/roots'){
    $stmt = $conn->prepare('SELECT * FROM icd10 WHERE ID_PARENT IS NULL;');
    $stmt->execute();
    $roots = $stmt->fetchAll(PDO::FETCH_ASSOC);
    header('Content-Type: application/json');
    http_response_code(200);
    echo json_encode(["Root ICD-10 elements retrieved" => $roots]);
}elseif (strpos($_SERVER['REQUEST_URI'], '/api/dictionary.php/icd10')!== false && $_SERVER['REQUEST_METHOD'] === 'GET') {

    $request = $_GET['request'];
    $page = $_GET['page'];
    $size = $_GET['size'];

    if (!isset($request) || !isset($page) || !isset($size) || !is_numeric($page) || !is_numeric($size)) {
        http_response_code(400);
        echo json_encode(["Some fields in request are invalid"]);
    }
    else{
        $offset = ($page - 1) * $size;

        $query = "SELECT id, mkb_code, mkb_name FROM icd10 WHERE MKB_CODE LIKE '%' || :request || '%' OR MKB_NAME LIKE '%' || :request || '%' ";
        $stmt = $conn->prepare($query);
        $stmt->bindValue(':request', $request);
        $stmt->execute();
        $count = $stmt->rowCount();
        
        $stmt = $conn->prepare($query . " LIMIT :size OFFSET :offset");
        $stmt->bindValue(':request', '%' . $request . '%');
        $stmt->bindValue(':size', $size, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        $data = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $data[] = [
                "id" => $row['id'], 
                "createTime" => date("Y-m-d\TH:i:s.u\Z"), 
                "code" => $row['mkb_code'],
                "name" => $row['mkb_name']
            ];
        }

        $response = [
            "records" => $data,
            "pagination" => [
                "size" => $size,
                "count" => $count,
                "current" => $page
            ]
        ];

        header('Content-Type: application/json');
        http_response_code(200);
        echo json_encode(["Searching result extracted" => $response]);
    }
}else if (strpos($_SERVER['REQUEST_URI'], '/api/dictionary.php/speciality')!== false && $_SERVER['REQUEST_METHOD'] === 'GET'){
    
    $name = $_GET['name'];
    $page = $_GET['page'];
    $size = $_GET['size'];

    if (!isset($name) || !isset($page) || !isset($size) || !is_numeric($page) || !is_numeric($size)) {
        http_response_code(400);
        echo json_encode(["Invalid arguments for filtration/pagination"]);
    }
    else{
        $offset = ($page - 1) * $size;

        $query = "SELECT id, createtime, name FROM speciality WHERE name LIKE '%' || :name || '%' ";
        $stmt = $conn->prepare($query);
        $stmt->bindValue(':name', $name);
        $stmt->execute();
        $count = $stmt->rowCount();
        
        $stmt = $conn->prepare($query . " LIMIT :size OFFSET :offset");
        $stmt->bindValue(':name', '%' . $name . '%');
        $stmt->bindValue(':size', $size, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        $data = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $data[] = [
                "id" => $row['id'], 
                "createTime" => $row['createtime'],
                "name" => $row['name']
            ];
        }

        $response = [
            "records" => $data,
            "pagination" => [
                "size" => $size,
                "count" => $count,
                "current" => $page
            ]
        ];
        header('Content-Type: application/json');
        http_response_code(200);
        echo json_encode(["Specialties paged list retrieved" => $response]);
    }
}else {
    http_response_code(404);
    echo json_encode(["Not Found"]);
}
$conn = null;
?>