<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Credentials: true");

include "connect.php";
$status = true;

$data = [
    "offerCost" => $offerCost,
    "withdrawMin" => $withdrawMin,
    "offerPriceMin" => $offerPriceMin,
    "offerPriceMax" => $offerPriceMax,
    "storageURL" => str_replace("/", "", str_replace("http://", "", str_replace("https://", "", $storageURL))),
    "storageModelsFolder" => $storageFolder
    ];

$answer = [
    "status" => $status,
    "data" => $data,
];

echo json_encode($answer, JSON_UNESCAPED_UNICODE);
?>