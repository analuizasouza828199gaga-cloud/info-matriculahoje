<?php
header('Content-Type: application/json; charset=utf-8');

$apiKey = 'sk_live_d8c08585018e6265e1dde214653adb3611dc328a73401c21104ad1f64aefbddf';
$url = 'https://api.blackcatpay.com.br/api/sales/create-sale';

$input = json_decode(file_get_contents('php://input'), true);

$nome      = trim($input['nome'] ?? '');
$cpf       = preg_replace('/\D/', '', $input['cpf'] ?? '');
$email     = trim($input['email'] ?? '');
$whatsapp  = preg_replace('/\D/', '', $input['whatsapp'] ?? '');
$valor     = $input['valor'] ?? 0;
$descricao = trim($input['descricao'] ?? 'Pagamento via PIX');

if ($nome === '' || $cpf === '' || $email === '' || $whatsapp === '' || empty($valor)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Dados obrigatórios ausentes.',
        'recebido' => $input
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$valorCentavos = (int) round(((float) $valor) * 100);

$payload = [
    'amount' => $valorCentavos,
    'currency' => 'BRL',
    'paymentMethod' => 'pix',
    'items' => [
        [
            'title' => $descricao,
            'unitPrice' => $valorCentavos,
            'quantity' => 1,
            'tangible' => false
        ]
    ],
    'customer' => [
        'name' => $nome,
        'email' => $email,
        'phone' => $whatsapp,
        'document' => [
            'number' => $cpf,
            'type' => 'cpf'
        ]
    ],
    'pix' => [
        'expiresInDays' => 1
    ]
];

$ch = curl_init($url);

curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode($payload),
    CURLOPT_TIMEOUT => 30,
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'Accept: application/json',
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
        'message' => 'Erro cURL ao gerar PIX.',
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

$transactionId = $data['data']['transactionId'] ?? null;
$status        = $data['data']['status'] ?? null;
$qrCode        = $data['data']['paymentData']['qrCode'] ?? null;
$qrCodeBase64  = $data['data']['paymentData']['qrCodeBase64'] ?? null;
$copyPaste     = $data['data']['paymentData']['copyPaste'] ?? $qrCode ?? null;
$expiresAt     = $data['data']['paymentData']['expiresAt'] ?? null;
$invoiceUrl    = $data['data']['invoiceUrl'] ?? null;

echo json_encode([
    'success' => $data['success'] ?? ($httpCode >= 200 && $httpCode < 300),
    'httpCode' => $httpCode,
    'transaction_id' => $transactionId,
    'status' => $status,
    'qr_code' => $qrCode,
    'qr_code_base64' => $qrCodeBase64,
    'pix_copia_cola' => $copyPaste,
    'expires_at' => $expiresAt,
    'invoice_url' => $invoiceUrl,
    'api_response' => $data
], JSON_UNESCAPED_UNICODE);