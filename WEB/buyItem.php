<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Credentials: true");

include "connect.php";
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
                "SELECT * FROM market WHERE id = ? AND status = ?"
            );
            $statusIm = "WAITING";
            $stmt->bind_param("ss", $id, $statusIm);
            $stmt->execute() or die("Не удалось обработать запрос");
            $result = $stmt->get_result();
            if ($offerData = mysqli_fetch_array($result)) {
                if ($offerData["owner"] != $tg_id) {
                    $stmt = $db->prepare(
                        "SELECT COUNT(*) FROM active_offers WHERE buyer = ?"
                    );
                    $stmt->bind_param("s", $tg_id);
                    $stmt->execute() or die("Не удалось обработать запрос");
                    $result = $stmt->get_result();
                    $count = mysqli_fetch_array($result)["COUNT(*)"];
                    if ($count == 0) {
                        $stmt = $db->prepare(
                            "SELECT COUNT(*) FROM market WHERE owner = ? AND status = ?"
                        );
                        $statusIm = "SELLING";
                        $stmt->bind_param("ss", $tg_id, $statusIm);
                        $stmt->execute() or die("Не удалось обработать запрос");
                        $result = $stmt->get_result();
                        $count = mysqli_fetch_array($result)["COUNT(*)"];
                        if ($count == 0) {
                            $price = $offerData["price"];
                            $seller = $offerData["owner"];
                            if ($price <= $balance) {
                                $stmt = $db->prepare(
                                    "UPDATE market SET status = ? WHERE id = ?"
                                );
                                $statusIm = "SELLING";
                                $stmt->bind_param("ss", $statusIm, $id);
                                $stmt->execute() or
                                    die("Не удалось обработать запрос");

                                $stmt = $db->prepare(
                                    "UPDATE userdata SET balance = balance - ? WHERE tg_id = ?"
                                );
                                $stmt->bind_param("ss", $price, $tg_id);
                                $stmt->execute() or
                                    die("Не удалось обработать запрос");

                                $stmt = $db->prepare(
                                    "INSERT INTO active_offers (`buyer`, `seller`, `status`) VALUES (?, ?, ?)"
                                );
                                $statusIm = "WAITING_FOR_SELLER";
                                $stmt->bind_param(
                                    "sss",
                                    $tg_id,
                                    $seller,
                                    $statusIm
                                );
                                $stmt->execute() or
                                    die("Не удалось обработать запрос");

                                $itemDescr =
                                    str_replace(
                                        "-",
                                        " ",
                                        $offerData["category"]
                                    ) .
                                    " (#" .
                                    $offerData["number"] .
                                    ")\nМодель: " .
                                    $offerData["name"] . " (" . $offerData['nameNumber'] . "%)" .
                                    "\nУзор: " .
                                    $offerData["patternName"] .
                                    " (" .
                                    $offerData["patternNumber"] .
                                    "%)\nФон: " .
                                    $offerData["backName"] .
                                    " (" .
                                    $offerData["backNumber"] .
                                    "%)";

                                $stmt = $db->prepare(
                                    "INSERT INTO msgsForSend (`target`, `message`, `additional`) VALUES (?, ?, ?)"
                                );
                                $additional =
                                    "send_confirm:" . $tg_id . ":" . $seller;
                                $message =
                                    "Получена оплата на Ваш товар:\n\n" .
                                    $itemDescr .
                                    "\n\nПередайте его пользователю %buyer% и подтвердите передачу, нажав на кнопку ниже.";
                                $stmt->bind_param(
                                    "sss",
                                    $seller,
                                    $message,
                                    $additional
                                );
                                $stmt->execute() or
                                    die("Не удалось обработать запрос");

                                $status = true;
                            } else {
                                $data = [
                                    "neededStars" => $offerData["price"] - $balance,
                                    "error" => "У вас недостаточно звёзд",
                                ];
                            }
                        } else {
                            $data = ["error" => "Продавец занят. Попробуйте позже"];
                        }
                    } else {
                        $data = ["error" => "У вас уже есть активная сделка"];
                    }
                } else {
                    $data = ["error" => "Вы не можете купить свой товар"];
                }
            } else {
                $data = ["error" => "Товар не найден или снят с продажи"];
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