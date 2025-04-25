<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Credentials: true");

include "../connect.php";
$status = false;

if (
    !isset($_REQUEST["tg"]) ||
    !isset($_REQUEST["page"]) ||
    !isset($_REQUEST["limit"]) ||
    !isset($_REQUEST["sortBy"])
) {
    $data = ["error" => "Некорректный запрос"];
} else {
    $tg_id = $_REQUEST["tg"];
    $page = intval($_REQUEST["page"]);
    $limit = intval($_REQUEST["limit"]);
    $sortBy = intval($_REQUEST["sortBy"]);

    $stmt = $db->prepare("SELECT * FROM userdata WHERE tg_id = ?");
    $stmt->bind_param("s", $tg_id);
    $stmt->execute() or die("Не удалось обработать запрос");
    $result = $stmt->get_result();

    if ($sessionData = mysqli_fetch_array($result)) {
        if ($sessionData["lastIP"] == getIP()) {
            $items = [];
            if ($sortBy == 1) {
                $stmt = $db->prepare(
                    "SELECT * FROM market WHERE owner = ? AND status = ? ORDER BY id DESC"
                );
                $statusIm = "WAITING";
                $stmt->bind_param("ss", $tg_id, $statusIm);
            } elseif ($sortBy == 2) {
                $stmt = $db->prepare(
                    "SELECT * FROM market WHERE owner = ? AND status = ? ORDER BY id DESC"
                );
                $statusIm = "SOLD";
                $stmt->bind_param("ss", $tg_id, $statusIm);
            } elseif ($sortBy == 3) {
                $stmt = $db->prepare(
                    "SELECT * FROM market WHERE owner = ? AND status = ? ORDER BY id DESC"
                );
                $statusIm = "ABORTED";
                $stmt->bind_param("ss", $tg_id, $statusIm);
            } else {
                $stmt = $db->prepare(
                    "SELECT * FROM market WHERE owner = ? ORDER BY id DESC"
                );
                $stmt->bind_param("s", $tg_id);
            }

            $stmt->execute() or die("Не удалось обработать запрос");
            $result = $stmt->get_result();
            $toDisplay = $limit;
            $toSkip = ($page - 1) * $limit;
            global $modelsStorageURL;
            $totalItems = $result->num_rows;
            while ($offerData = mysqli_fetch_array($result)) {
                if ($toSkip > 0) {
                    $toSkip--;
                    continue;
                }
                if ($toDisplay > 0) {
      
                    $url = $modelsStorageURL . $offerData["category"] . "/" . $offerData["name"] . ".json";
                    $offer = [
                        "id" => $offerData["id"],
                        "category" => $offerData["category"],
                        "name" => $offerData["name"],
                        "price" => $offerData["price"],
                        "number" => $offerData["number"],
                        "patternName" => $offerData["patternName"],
                        "patternNumber" => $offerData["patternNumber"],
                        "backName" => $offerData["backName"],
                        "backNumber" => $offerData["backNumber"],
                        "icon" => $url,
                        "status" => $offerData["status"],
                    ];
                    array_push($items, $offer);
                    $toDisplay--;
                } else {
                    break;
                }
            }
            $data = [
                "maxPage" => ceil($totalItems / $limit),
                "items" => $items,
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