<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Credentials: true");

include "../connect.php";
$status = false;

if (!isset($_REQUEST["tg"])) {
    $data = ["error" => "Некорректный запрос"];
} else {
    $tg_id = $_REQUEST["tg"];

    $stmt = $db->prepare("SELECT * FROM userdata WHERE tg_id = ?");
    $stmt->bind_param("s", $tg_id);
    $stmt->execute() or die("Не удалось обработать запрос");
    $result = $stmt->get_result();

    if ($sessionData = mysqli_fetch_array($result)) {
        if ($sessionData["lastIP"] == getIP()) {
            $data = [
                "balance" => $sessionData["balance"],
            ];
            $status = true;
        } else {
            $data = ["error" => "Ошибка авторизации #2"];
        }
    } else {
        $data = ["error" => "Ошибка авторизации #1"];
    }
}

$answer = [
    "status" => $status,
    "data" => $data,
];

echo json_encode($answer, JSON_UNESCAPED_UNICODE);
?>