<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Credentials: true");

include "../connect.php";
$status = false;

if (
    !isset($_REQUEST["tg"]) ||
    !isset($_REQUEST["sum"]) ||
    !isset($_REQUEST["type"]) ||
    !isset($_REQUEST["wallet"])
) {
    $data = ["error" => "Некорректный запрос"];
} else {
    $tg_id = $_REQUEST["tg"];
    $sum = intval($_REQUEST["sum"]);
    $type = $_REQUEST["type"];
    $wallet = $_REQUEST["wallet"];

    $stmt = $db->prepare("SELECT * FROM userdata WHERE tg_id = ?");
    $stmt->bind_param("s", $tg_id);
    $stmt->execute() or die("Не удалось обработать запрос");
    $result = $stmt->get_result();

    if ($sessionData = mysqli_fetch_array($result)) {
        if ($sessionData["lastIP"] == getIP()) {
            global $withdrawMin;
            if ($sum >= $withdrawMin) {
                if ($sum <= $sessionData["balance"]) {
                    $status = true;
                    $stmt = $db->prepare(
                        "INSERT INTO withdraw_requests (`user`, `sum`, `type`, `wallet`, `status`) VALUES (?, ?, ?, ?, ?)"
                    );
                    $statusIm = "WAITING";
                    $stmt->bind_param(
                        "sssss",
                        $tg_id,
                        $sum,
                        $type,
                        $wallet,
                        $statusIm
                    );
                    $stmt->execute() or die("Не удалось обработать запрос");
                    
                    $newBalance = $sessionData["balance"] - $sum;
                    
                    $stmt = $db->prepare(
                        "UPDATE userdata SET balance = ? WHERE tg_id = ?"
                    );
                    $stmt->bind_param(
                        "ss",
                        $newBalance,
                        $tg_id
                    );
                    $stmt->execute() or die("Не удалось обработать запрос");

                    $stmt = $db->prepare(
                        "INSERT INTO msgsForSend (`target`, `message`, `additional`) VALUES (?, ?, ?)"
                    );
                    $additional = "-";
                    $message = "Вы отправили запрос на вывод " . $sum . " ⭐️ на " . $wallet . " (" . $type . ")\n\nСредства зарезервированы на 21 день. Баланс: " . $newBalance . " ⭐️";
                    $stmt->bind_param("sss", $tg_id, $message, $additional);
                    $stmt->execute() or die("Не удалось обработать запрос");
                } else {
                    $data = ["error" => "Указанная сумма превышает Ваш баланс"];
                }
            } else {
                $data = ["error" => "Минимальная сумма для вывода: " . $withdrawMin . " STARS"];
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