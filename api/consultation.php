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
            $query = "SELECT id,createTime,name,birthday,gender,email,phone FROM doctor WHERE id = :id";
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
                "author"=> $author,
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
}
else {
    // Отправка ответа с ошибкой
    http_response_code(401);
    echo json_encode(["message" => "Метод не разрешен"]);
}