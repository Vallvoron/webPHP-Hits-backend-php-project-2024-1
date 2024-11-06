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
if ($_SERVER['REQUEST_URI'] === '/api/consultation.php' && $_SERVER['REQUEST_METHOD'] === 'GET') {

}else if (preg_match('/\/api\/consultation\.php\/([0-9a-f\-]+)$/i', $_SERVER['REQUEST_URI'], $matches) && $_SERVER['REQUEST_METHOD'] === 'GET'){
    $consultationid=$matches[1];
    $query = "SELECT id,createtime,inspectionid,specialityid FROM consultation WHERE id = :id";
    $stmt = $conn->prepare($query);
    $stmt->bindValue(':id', $consultationid);
    $stmt->execute();
    if ($stmt->rowCount() === 0) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid arguments']);
        exit;
    }
    else {
        $consultation = $stmt ->fetch(PDO::FETCH_ASSOC);
        $query = "SELECT id,name,createTime FROM speciality WHERE id = :id";
        $stmt = $conn->prepare($query);
        $stmt->bindValue(':id', $consultation['specialityid']);
        $stmt->execute();
        $speciality = $stmt ->fetch(PDO::FETCH_ASSOC);
        $query = "SELECT id,author,createTime,modifyTime,content,parentid FROM comment WHERE consultationid = :id";
        $stmt = $conn->prepare($query);
        $stmt->bindValue(':id', $consultation['id']);
        $stmt->execute();
        $comments = $stmt ->fetchAll(PDO::FETCH_ASSOC);
        foreach($comments as $comment){
            $query = "SELECT name FROM doctor WHERE id = :id";
            $stmt = $conn->prepare($query);
            $stmt->bindValue(':id', $comment['author']);
            $stmt->execute();
            $author = $stmt ->fetch(PDO::FETCH_ASSOC);
            $Commresponse=[
                "id" => $comment["id"],
                "createTime" => $comment["createtime"],
                "modifiedDate"=>$comment["modifytime"],
                "content"=>$comment["content"],
                "authorId"=>$comment["author"],
                "author"=> $author['name'],
                "parentId"=> $comment["parentid"]
            ];
            $Cresponse = [
                "id" => $consultation["id"],
                "createTime" => $consultation["createtime"],
                "inspectionId" => $consultation["inspectionid"],
                "speciality" => $speciality,
                "comments" => $Commresponse,
            ];
        }
        $Cresponse = [
            "id" => $consultation["id"],
            "createTime" => $consultation["createtime"],
            "inspectionId" => $consultation["inspectionid"],
            "speciality" => $speciality,
            "comments" => $Commresponse,
        ];
        echo json_encode($Cresponse);
   }
}else if (preg_match('/\/api\/consultation\.php\/([0-9a-f\-]+)\/comment$/i', $_SERVER['REQUEST_URI'], $matches) && $_SERVER['REQUEST_METHOD'] === 'POST'){
    $data = json_decode(file_get_contents('php://input'), true);
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
        http_response_code(404);
        echo json_encode(['error' => 'Пользователь не найден']);
        exit;
    }

    // Преобразование результата в массив
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    $consultationid=$matches[1];

    $Commid = uniqid();
    $createdAt = date('Y-m-d\TH:i:s.u\Z');
    $query = "INSERT INTO comment (id,consultationid,parentid,author,content,createtime,modifytime) VALUES (:id,:consultationid,:parentid,:author,:content,:createtime,:createtime)";
    $stmt = $conn->prepare($query);
    $stmt->bindValue(':id', $Commid);
    $stmt->bindValue(':parentid', $data['parentId']);
    $stmt->bindValue(':consultationid', $consultationid);
    $stmt->bindValue(':author', $user['id']);
    $stmt->bindValue(':content', $data['content']);
    $stmt->bindValue(':createtime', $createdAt);
    $stmt->execute();
    echo json_encode(['Sucsess'=>$Commid]);
}elseif (preg_match('/\/api\/consultation\.php\/comment\/([0-9a-f\-]+)$/i', $_SERVER['REQUEST_URI'], $matches) && $_SERVER['REQUEST_METHOD'] === 'PUT'){
    $data = json_decode(file_get_contents('php://input'), true);
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
        http_response_code(404);
        echo json_encode(['error' => 'Пользователь не найден']);
        exit;
    }

    // Преобразование результата в массив
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    $commentid=$matches[1];

    $query = "SELECT author FROM comment WHERE id = :id";
    $stmt = $conn->prepare($query);
    $stmt->bindValue(':id', $commentid);
    $stmt->execute();
    $author = $stmt->fetch(PDO::FETCH_ASSOC);

    $query = "SELECT id FROM doctor WHERE token = :token";
    $stmt = $conn->prepare($query);
    $stmt->bindValue(':token', $token);
    $stmt->execute();
    $doctorid = $stmt->fetch(PDO::FETCH_ASSOC);
    if($author['author']!= $doctorid['id']){
        http_response_code(500);
        echo json_encode(["User is not the author of the comment"]);
        echo json_encode($author['author']);
        echo json_encode($doctorid['id']);
    }
    else
    {
        $query = "UPDATE comment SET content=:content WHERE id = :id";
        $stmt = $conn->prepare($query);
        $stmt->bindValue(':content', $data['content']);
        $stmt->bindValue(':id', $commentid);
        $stmt->execute();
        echo json_encode(['Sucsess']);
    }
}
else {
    // Отправка ответа с ошибкой
    http_response_code(401);
    echo json_encode(["message" => "Метод не разрешен"]);
}