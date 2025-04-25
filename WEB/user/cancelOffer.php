<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Credentials: true");

include "../connect.php";
$status = false;

if (!isset($_REQUEST["tg"]) || !isset($_REQUEST["id"])) {
    $data = ["error" => "Некорректный запрос"];
} else {
    $tg_id = $_REQUEST["tg"];
    $id = $_REQUEST["id"];

    $stmt = $db->prepare("SELECT * FROM userdata WHERE tg_id = ?");
    $stmt->bind_param("s", $tg_id);
    $stmt->execute() or die("Не удалось обработать запрос");
    $result = $stmt->get_result();

    if ($sessionData = mysqli_fetch_array($result)) {
        if ($sessionData["lastIP"] == getIP()) {
            $balance = $sessionData["balance"];
            $stmt = $db->prepare(
                "SELECT * FROM market WHERE id = ? AND status = ? AND owner = ?"
            );
            $statusIm = "WAITING";
            $stmt->bind_param("sss", $id, $statusIm, $tg_id);
            $stmt->execute() or die("Не удалось обработать запрос");
            $result = $stmt->get_result();
            if ($offerData = mysqli_fetch_array($result)) {
                $stmt = $db->prepare(
                    "UPDATE market SET status = ? WHERE id = ?"
                );
                $statusIm = "ABORTED";
                $stmt->bind_param("ss", $statusIm, $id);
                $stmt->execute() or die("Не удалось обработать запрос");
                $status = true;
            } else {
                $data = ["error" => "Товар не существует или в данный момент недоступен для этого действия"];
            }
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