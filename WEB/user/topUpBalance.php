<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Credentials: true");

include "../connect.php";
$status = false;

if (!isset($_REQUEST["tg"]) || !isset($_REQUEST["sum"])) {
    $data = ["error" => "Некорректный запрос"];
} else {
    $tg_id = $_REQUEST["tg"];
    $sum = intval($_REQUEST["sum"]);

    $stmt = $db->prepare("SELECT * FROM userdata WHERE tg_id = ?");
    $stmt->bind_param("s", $tg_id);
    $stmt->execute() or die("Не удалось обработать запрос");
    $result = $stmt->get_result();

    if ($sessionData = mysqli_fetch_array($result)) {
        if ($sessionData["lastIP"] == getIP()) {
            if ($sum > 0 && $sum <= 1000000) {
                $status = true;
                $stmt = $db->prepare(
                    "INSERT INTO msgsForSend (`target`, `message`, `additional`) VALUES (?, ?, ?)"
                );
                $additional = "buy_stars:" . $tg_id . ":" . $sum;
                $message = "-";
                $stmt->bind_param("sss", $tg_id, $message, $additional);
                $stmt->execute() or die("Не удалось обработать запрос");
            } else {
                $data = ["error" => "Некорректно указана сумма"];
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