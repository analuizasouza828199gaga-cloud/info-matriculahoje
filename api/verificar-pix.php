<?php
header('Content-Type: application/json; charset=utf-8');

$apiKey = 'sk_live_d8c08585018e6265e1dde214653adb3611dc328a73401c21104ad1f64aefbddf';

$transactionId = trim($_GET['transactionId'] ?? $_GET['hash'] ?? '');

if ($transactionId === '') {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'transactionId não informado.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$url = 'https://api.blackcatpay.com.br/api/sales/' . urlencode($transactionId) . '/status';

$ch = curl_init($url);

curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPGET => true,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_HTTPHEADER => [
        'Accept: application/json',
        'Content-Type: application/json',
        'X-API-Key: ' . $apiKey
    ]
]);

$response  = curl_exec($ch);
$httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);

curl_close($ch);

if ($curlError) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erro cURL ao consultar transação.',
        'error' => $curlError
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$data = json_decode($response, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Resposta inválida da API.',
        'raw_response' => $response
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$status = strtoupper($data['data']['status'] ?? '');

$pago = ($status === 'PAID');

echo json_encode([
    'success' => $data['success'] ?? ($httpCode >= 200 && $httpCode < 300),
    'httpCode' => $httpCode,
    'transactionId' => $data['data']['transactionId'] ?? $transactionId,
    'status' => $status,
    'pago' => $pago,
    'paymentMethod' => $data['data']['paymentMethod'] ?? null,
    'amount' => $data['data']['amount'] ?? null,
    'netAmount' => $data['data']['netAmount'] ?? null,
    'fees' => $data['data']['fees'] ?? null,
    'paidAt' => $data['data']['paidAt'] ?? null,
    'endToEndId' => $data['data']['endToEndId'] ?? null,
    'api_response' => $data
], JSON_UNESCAPED_UNICODE);