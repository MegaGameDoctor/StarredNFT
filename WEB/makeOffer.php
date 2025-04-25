<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Credentials: true");

include "connect.php";
$status = false;

if (
    !isset($_REQUEST["tg"]) ||
    !isset($_REQUEST["price"]) ||
    !isset($_REQUEST["number"]) ||
    !isset($_REQUEST["type"])
) {
    $data = ["error" => "Некорректный запрос"];
} else {
    $tg_id = $_REQUEST["tg"];
    $type = $_REQUEST["type"];
    $number = intval($_REQUEST["number"]);
    $price = intval($_REQUEST["price"]);
    
    $giftData = getGiftVisualInfo($type, $number);
    $category = str_replace(" ", "-", $giftData['name']);
    $name = str_replace(" ", "-", $giftData['modelName']);
    $nameNumber = doubleval($giftData['modelPercent']);
    $number = intval($giftData['number']);
    $patternName = $giftData['patternName'];
    $patternNumber = doubleval($giftData['patternPercent']);
    $backName = $giftData['backName'];
    $backNumber = doubleval($giftData['backPercent']);
    
    

    $stmt = $db->prepare("SELECT * FROM userdata WHERE tg_id = ?");
    $stmt->bind_param("s", $tg_id);
    $stmt->execute() or die("Не удалось обработать запрос");
    $result = $stmt->get_result();

    if ($sessionData = mysqli_fetch_array($result)) {
        if ($sessionData["lastIP"] == getIP()) {
            global $offerCost;
            if ($sessionData["balance"] >= $offerCost) {
                global $offerPriceMin;
                global $offerPriceMax;
                if ($price < $offerPriceMax && $price > $offerPriceMin) {
                    if (is_numeric($number) && $number > 0) {
                        if (is_numeric($patternNumber) && $patternNumber > 0) {
                            if (is_numeric($backNumber) && $backNumber > 0) {
                                global $storageURL;
                                $ch = curl_init();
                                curl_setopt(
                                    $ch,
                                    CURLOPT_URL,
                                    $storageURL . "isExists.php?file=models/" . $category . "/" . $name . ".json"
                                );
                                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                                curl_setopt($ch, CURLOPT_HEADER, false);

                                $response = curl_exec($ch);

                                curl_close($ch);

                                if ($response == "yes") {
                                    $stmt = $db->prepare(
                                        "SELECT * FROM market WHERE nameNumber = ?"
                                    );
                                    $stmt->bind_param(
                                        "s",
                                        $number);
                                    
                                    $stmt->execute() or die("Не удалось обработать запрос");
    $result = $stmt->get_result();

    $oldStatus = mysqli_fetch_array($result)['status'];
    if($oldStatus != "WAITING" && $oldStatus != "WAITING_FOR_SELLER") { 
            
            if($giftData['owner'] == getUserName($tg_id)) {
                                    
                                    $stmt = $db->prepare(
                                        "INSERT INTO market (`owner`, `category`, `name`, `nameNumber`, `price`, `number`, `patternName`, `patternNumber`, `backName`, `backNumber`, `startDate`, `endDate`, `rarity`, `status`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
                                    );
                                    $end = 0;
                                    $status = "WAITING";
                                    $rarity = evaluateRarity(
                                        $number,
                                        $nameNumber,
                                        $patternNumber,
                                        $backNumber
                                    );
                                    
                                    $stmt->bind_param(
                                        "ssssssssssssss",
                                        $tg_id,
                                        $category,
                                        $name,
                                        $nameNumber,
                                        $price,
                                        $number,
                                        $patternName,
                                        $patternNumber,
                                        $backName,
                                        $backNumber,
                                        time(),
                                        $end,
                                        $rarity,
                                        $status
                                    );
                                    $stmt->execute() or
                                        die("Не удалось обработать запрос");

                                    $stmt = $db->prepare(
                                        "UPDATE userdata SET balance=balance - ? WHERE tg_id = ?"
                                    );
                                    $stmt->bind_param("ss", $offerCost, $tg_id);
                                    $stmt->execute() or
                                        die("Не удалось обработать запрос");

                                    $status = true;
            } else {
                $data = ["error" => "Подарок скрыт или не принадлежит Вам"];
            }
    } else {
        $data = ["error" => "Подарок уже выставлен"];
    }
                                } else {
                                    $data = ["error" => "Некорректно указана модель"];
                                }
                            } else {
                                $data = ["error" => "Процент фона указан некорректно"];
                            }
                        } else {
                            $data = ["error" => "Процент узора указан некорректно"];
                        }
                    } else {
                        $data = ["error" => "Номер указан некорректно"];
                    }
                } else {
                    $data = ["error" => "Цена указана некорректно"];
                }
            } else {
                $data = ["error" => "Недостаточно средств на балансе"];
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