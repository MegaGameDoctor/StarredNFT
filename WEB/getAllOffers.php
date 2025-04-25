<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Credentials: true");

include "connect.php";
$status = false;

if (
    !isset($_REQUEST["tg"]) ||
    !isset($_REQUEST["page"]) ||
    !isset($_REQUEST["limit"])
) {
    $data = ["error" => "Некорректный запрос"];
} else {
    $tg_id = $_REQUEST["tg"];
    $page = intval($_REQUEST["page"]);
    $limit = intval($_REQUEST["limit"]);

    $upperLowerSort = 0;
    global $offerPriceMin;
    global $offerPriceMax;
    $priceFrom = $offerPriceMin;
    $priceTo = $offerPriceMax;
    $rareSort = null;
    $categorySort = null;
    
    $parName = "upperLowerSort";
    if (isset($_REQUEST[$parName]) && !empty($_REQUEST[$parName])) {
        $upperLowerSort = intval($_REQUEST[$parName]);
    }
    
    $parName = "priceFrom";
    if (isset($_REQUEST[$parName]) && !empty($_REQUEST[$parName])) {
        $priceFrom = intval($_REQUEST[$parName]);
    }
    
    $parName = "priceTo";
    if (isset($_REQUEST[$parName]) && !empty($_REQUEST[$parName])) {
        $priceTo = intval($_REQUEST[$parName]);
    }
    
    $parName = "rareSort";
    if (isset($_REQUEST[$parName]) && !empty($_REQUEST[$parName])) {
        $rareSort = $_REQUEST[$parName];
    }
    
    $parName = "categorySort";
    if (isset($_REQUEST[$parName]) && !empty($_REQUEST[$parName])) {
        $categorySort = $_REQUEST[$parName];
    }

    $stmt = $db->prepare("SELECT * FROM userdata WHERE tg_id = ?");
    $stmt->bind_param("s", $tg_id);
    $stmt->execute() or die("Не удалось обработать запрос");
    $result = $stmt->get_result();

    if ($sessionData = mysqli_fetch_array($result)) {
        if ($sessionData["lastIP"] == getIP()) {
            $data = [];

            $priceAdder = "AND price >= " . $priceFrom . " AND price <= " . $priceTo;
            $orderAdder = "ORDER BY id DESC";
            if ($upperLowerSort == 1) {
                $orderAdder = "ORDER BY price";
            } elseif ($upperLowerSort == 2) {
                $orderAdder = "ORDER BY price DESC";
            }

            $rareAdder = "";
            if ($rareSort != null) {
                $rareAdder = "AND rarity = '" . $rareSort . "'";
            }

            $categoryAdder = "";
            if ($categorySort != null) {
                $categoryAdder = "AND category = '" . $categorySort . "'";
            }
            
            $stmt = $db->prepare(
                "SELECT * FROM market WHERE status = ? " .
                    $priceAdder .
                    " " .
                    $rareAdder .
                    " " .
                    $categoryAdder .
                    " " .
                    $orderAdder
            );
            
            $statusIm = "WAITING";
            $stmt->bind_param("s", $statusIm);
            $stmt->execute() or die("Не удалось обработать запрос");
            $result = $stmt->get_result();
            $toDisplay = $limit;
            $toSkip = ($page - 1) * $limit;
            global $modelsStorageURL;
            while ($offerData = mysqli_fetch_array($result)) {
                if ($toSkip > 0) {
                    $toSkip--;
                    continue;
                }
                if ($toDisplay > 0) {
                    $url = "https://nft.fragment.com/gift/" . strtolower(str_replace("-", "", $offerData["category"])) . "-" . $offerData["number"] . ".lottie.json";
                    $offer = [
                        "id" => $offerData["id"],
                        "category" => $offerData["category"],
                        "name" => $offerData["name"],
                        "nameNumber" => $offerData["nameNumber"],
                        "price" => $offerData["price"],
                        "number" => $offerData["number"],
                        "rarity" => $offerData["rarity"],
                        "patternName" => $offerData["patternName"],
                        "patternNumber" => $offerData["patternNumber"],
                        "backName" => $offerData["backName"],
                        "backNumber" => $offerData["backNumber"],
                        "icon" => $url,
                    ];
                    array_push($data, $offer);
                    $toDisplay--;
                } else {
                    break;
                }
            }
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