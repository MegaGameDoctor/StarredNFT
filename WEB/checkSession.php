<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Credentials: true");

header("Content-Type: application/json");

use TgWebValid\TgWebValid;
use TgWebValid\Exceptions\ValidationException;

include __DIR__ . "/vendor/autoload.php";

include "connect.php";
$status = false;


function authWithTG($initData)
{
    try {
        global $telegramBotToken;
       
        $tgWebValid = new TgWebValid($telegramBotToken, true);
        return $tgWebValid->bot()->validateInitData($initData);
    } catch (Exception $e) {
        return false;
    }
}

if (!isset($_REQUEST["tg"])) {
    $data = ["error" => "Некорректный запрос"];
} else {
    $tg_id = $_REQUEST["tg"];
    $initData = $_REQUEST["initData"];

    $stmt = $db->prepare("SELECT * FROM userdata WHERE tg_id = ?");
    $stmt->bind_param("s", $tg_id);
    $stmt->execute() or die("Не удалось обработать запрос");
    $result = $stmt->get_result();

    if ($sessionData = mysqli_fetch_array($result)) {
        if ($sessionData["lastIP"] == getIP()) {
            $status = true;
        } else {
            if (authWithTG($initData)) {
                $stmt = $db->prepare(
                    "UPDATE userdata SET lastIP = ? WHERE tg_id = ?"
                );
                
                $stmt->bind_param("ss", getIP(), $tg_id);
                $stmt->execute() or die("Не удалось обработать запрос");
                $status = true;
            }
        }
    } else {
        
        if (authWithTG($initData)) {
            
            $stmt = $db->prepare(
                "INSERT INTO userdata (`tg_id`, `lastIP`, `balance`) VALUES (?, ?, ?)"
            );
            
            $balance = 0;
            $stmt->bind_param("sss", $tg_id, getIP(), $balance);
            $stmt->execute() or die("Не удалось обработать запрос");
            $status = true;
        }
    }
}

//$status = true;
$answer = [
    "status" => $status,
    "data" => $data,
];

echo json_encode($answer, JSON_UNESCAPED_UNICODE);
?>