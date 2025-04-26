<?php
header("Content-Type: application/json");
require_once __DIR__ . '/../facade/codefacade.php';

$facade = new CodeFacade();

$requestData = json_decode(file_get_contents('php://input'), true);
$service = $requestData['service'] ?? '';
$action = $requestData['action'] ?? '';
$data = $requestData['data'] ?? [];

$response = $facade->processRequest($service, $action, $data);
echo json_encode($response);
?>
