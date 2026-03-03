<?php

$file = "data.json";

// Se non esiste lo creo
if (!file_exists($file)) {
    file_put_contents($file, json_encode([]));
}

$data = json_decode(file_get_contents($file), true);
$method = $_SERVER['REQUEST_METHOD'];

$contentType = $_SERVER["CONTENT_TYPE"] ?? "";
$accept = $_SERVER["HTTP_ACCEPT"] ?? "application/json";

$inputRaw = file_get_contents("php://input");

// === Funzione per convertire in XML ===
function toXML($array) {
    $xml = new SimpleXMLElement('<root/>');
    foreach ($array as $item) {
        $user = $xml->addChild('user');
        foreach ($item as $key => $value) {
            $user->addChild($key, $value);
        }
    }
    return $xml->asXML();
}

// === Funzione risposta ===
function sendResponse($data, $accept) {
    if ($accept == "application/xml") {
        header("Content-Type: application/xml");
        echo toXML($data);
    } else {
        header("Content-Type: application/json");
        echo json_encode($data, JSON_PRETTY_PRINT);
    }
    exit;
}

// === Parsing input ===
if ($contentType == "application/xml") {
    $xml = simplexml_load_string($inputRaw);
    $input = json_decode(json_encode($xml), true);
} else {
    $input = json_decode($inputRaw, true);
}

// ===== CREATE =====
if ($method == "POST") {

    $new = [
        "id" => time(),
        "nome" => $input["nome"]
    ];

    $data[] = $new;
    file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT));

    sendResponse($new, $accept);
}

// ===== READ =====
elseif ($method == "GET") {
    sendResponse($data, $accept);
}

// ===== UPDATE =====
elseif ($method == "PUT") {

    foreach ($data as &$item) {
        if ($item["id"] == $input["id"]) {
            $item["nome"] = $input["nome"];
        }
    }

    file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT));
    sendResponse(["message" => "Aggiornato"], $accept);
}

// ===== DELETE =====
elseif ($method == "DELETE") {

    $data = array_filter($data, function($item) use ($input) {
        return $item["id"] != $input["id"];
    });

    file_put_contents($file, json_encode(array_values($data), JSON_PRETTY_PRINT));
    sendResponse(["message" => "Eliminato"], $accept);
}

?>