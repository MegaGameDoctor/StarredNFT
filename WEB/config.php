<?php

# Подключение к базе данных
$host = "localhost";
$dbuser = "-";
$dbpass = "-";
$dbname = "-";

# Токен бота Телеграмм для авторизации в Mini App
$telegramBotToken = "-";

# Стоимость размещения подарка в звездах
$offerCost = 1;

# Процентная комиссия за размещение товара
$offerComissionPercent = 1;

# Минимальная сумма в звёздах для вывода
$withdrawMin = 500;

# Минимальная и максимальная сумма товара
$offerPriceMin = 1;
$offerPriceMax = 1000000;

# Общая ссылка на хранилище
$storageURL = "https://nft.fragment.com/";

# Путь к дирректории с моделями
$storageFolder = "gift";

# Ссылка на место хранения моделей
$modelsStorageURL = $storageURL . $storageFolder . "/";

if (
    !function_exists("gen_token") &&
    !function_exists("getIP") &&
    !function_exists("evaluateRarity") &&
    !function_exists("evaluatePercentage")
) {
    function gen_token()
    {
        $chars =
            "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
        return substr(str_shuffle($chars), 0, 10);
    }

    function evaluateRarity(
        $serialNumber,
        $modelPercent,
        $patternPercent,
        $backgroundPercent
    ) {
        $score = 0;

        if ($serialNumber >= 1 && $serialNumber <= 10) {
            $score += 50; // Легендарный
        } elseif ($serialNumber >= 20 && $serialNumber <= 100) {
            $score += 30; // Редкий
        } elseif ($serialNumber >= 1000 && $serialNumber <= 10000) {
            $score += 10; // Средний
        } else {
            $score += 1; // Обычный
        }

        $score += evaluatePercentage($modelPercent);
        $score += evaluatePercentage($patternPercent);
        $score += evaluatePercentage($backgroundPercent);

        if ($score >= 130) {
            return "LEGENDARY";
        } elseif ($score >= 80) {
            return "EPIC";
        } elseif ($score >= 30) {
            return "RARE";
        } else {
            return "COMMON";
        }
    }

    function evaluatePercentage($percent)
    {
        if ($percent >= 0.1 && $percent <= 0.3) {
            return 50; // Легендарный
        } elseif ($percent >= 0.5 && $percent <= 1) {
            return 30; // Редкий
        } elseif ($percent > 1 && $percent <= 2) {
            return 10; // Средний
        } else {
            return 1; // Обычный
        }
    }

    function getIP()
    {
        if (!empty($_SERVER["HTTP_CLIENT_IP"])) {
            $ip = $_SERVER["HTTP_CLIENT_IP"];
        } elseif (!empty($_SERVER["HTTP_X_FORWARDED_FOR"])) {
            $ip = $_SERVER["HTTP_X_FORWARDED_FOR"];
        } else {
            $ip = $_SERVER["REMOTE_ADDR"];
        }
        return $ip;
    }

    function getGiftVisualInfo($type, $number)
    {
        $url = "https://t.me/nft/" . $type . "-" . $number;

        $options = [
            "http" => [
                "header" =>
                    "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64)\r\n",
            ],
        ];
        $context = stream_context_create($options);
        $html = file_get_contents($url, false, $context);

        $doc = new DOMDocument();
        libxml_use_internal_errors(true);
        $doc->loadHTML($html);
        libxml_clear_errors();

        $xpath = new DOMXPath($doc);

        $metaTags = [];
        foreach ($xpath->query("//meta[@property]") as $meta) {
            $metaTags[$meta->getAttribute("property")] = $meta->getAttribute(
                "content"
            );
        }

        $data = [];
        foreach (
            $xpath->query("//table[contains(@class, 'tgme_gift_table')]//tr")
            as $row
        ) {
            $th = $xpath->query(".//th", $row);
            $td = $xpath->query(".//td", $row);
            if ($th->length > 0 && $td->length > 0) {
                $key = trim($th->item(0)->textContent);
                $value = trim($td->item(0)->textContent);
                $data[$key] = $value;
            }
        }

        $status = true;

        $lastPart = rtrim(end(explode(" ", trim($str))), "%");

        $data = [
            "name" => str_replace("’", "", extractName($metaTags["og:title"])),
            "number" => extractNumber($metaTags["og:title"]),
            "owner" => $data['Owner'],
            "modelName" => str_replace("’", "", removePercentageNumber($data["Model"])),
            "modelPercent" => rtrim(
                end(explode(" ", trim($data["Model"]))),
                "%"
            ),
            "backName" => str_replace("’", "", removePercentageNumber($data["Backdrop"])),
            "backPercent" => rtrim(
                end(explode(" ", trim($data["Backdrop"]))),
                "%"
            ),
            "patternName" => str_replace("’", "", removePercentageNumber($data["Symbol"])),
            "patternPercent" => rtrim(
                end(explode(" ", trim($data["Symbol"]))),
                "%"
            ),
        ];

        return $data;
    }
    
    function removePercentageNumber($str) {
    $parts = explode(' ', trim($str));
    
    $lastPart = end($parts);
    if (substr($lastPart, -1) === "%") {
        $number = rtrim($lastPart, "%");

        if (is_numeric($number)) {
            array_pop($parts);
        }
    }

    return implode(' ', $parts);
}

function extractName($str) {
    $parts = explode(' ', trim($str));

    $lastPart = end($parts);

    if (substr($lastPart, 0, 1) === "#" && is_numeric(ltrim($lastPart, "#"))) {
        array_pop($parts);
    }
    
    return implode(' ', $parts);
}

function extractNumber($str) {
    $parts = explode(' ', trim($str));
    
    $lastPart = end($parts);

    if (substr($lastPart, 0, 1) === "#" && is_numeric(ltrim($lastPart, "#"))) {
        return (int)ltrim($lastPart, "#");
    }

    return null;
}

function getUserName($userID) {
global $telegramBotToken;
$url = "https://api.telegram.org/bot" . $telegramBotToken . "/getChat?chat_id=" . $userID;
$response = file_get_contents($url);
$data = json_decode($response, true);

if ($data['ok']) {
    return $data['result']['first_name'];
} else {
    return "NONE";
}
}
}
?>