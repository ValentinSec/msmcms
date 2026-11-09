<?php


define('access', true);

ob_start();
$root_path = dirname(__DIR__) . '/';
require_once($root_path . 'includes/muservermaker.php');
ob_clean();

if (session_status() == PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['username']) || empty($_SESSION['username'])) {
    die("Error: Debes iniciar sesion nuevamente.");
}

$user = $_SESSION['username'];

// Cargar Configuración
$donCfgFile = $root_path . 'includes/config/donation_config.json';
if (!file_exists($donCfgFile)) die("Error: Falta configuración.");
$donCfg = json_decode(file_get_contents($donCfgFile), true);

// Capturar datos
$methodId = $_GET['method'] ?? '';
$packId = $_GET['pack_id'] ?? '';

if (empty($methodId) || empty($packId)) die("Error: Datos incompletos.");

// Buscar paquete
$selectedPack = null;
if (isset($donCfg['methods'][$methodId]['packages'])) {
    foreach ($donCfg['methods'][$methodId]['packages'] as $p) {
        if ($p['id'] == $packId) {
            $selectedPack = $p;
            break;
        }
    }
}

if (!$selectedPack) die("Error: Paquete no encontrado.");

$amount = (float)$selectedPack['price_usd'];
$coins = $selectedPack['coins'];
// Recuperamos el nombre de la columna (WCoinC, WCoinP, etc.) guardado en el pack
$targetColumn = $selectedPack['coin_type'] ?? 'WCoinC'; 

$currency = ($methodId == 'mp') ? ($donCfg['methods']['mp']['currency'] ?? 'ARS') : 'USD';

// Registrar transacción
$txnId = uniqid("TXN_");
$txnFile = dirname(__DIR__) . '/includes/config/donation_transactions.json';
$transactions = file_exists($txnFile) ? json_decode(file_get_contents($txnFile), true) : [];

$newTxn = [
    'id' => $txnId,
    'user' => $user,
    'method' => $methodId,
    'pack_id' => $packId,
    'coins' => $coins,
    'col_coin' => $targetColumn, // <--- GUARDAMOS LA COLUMNA DESTINO AQUÍ
    'amount' => $amount,
    'currency' => $currency,
    'status' => 'Pending',
    'date' => date('Y-m-d H:i:s')
];

$transactions[] = $newTxn;
file_put_contents($txnFile, json_encode($transactions, JSON_PRETTY_PRINT));

// --- PROCESAMIENTO ---
switch ($methodId) {
    case 'mp':
        $accessToken = $donCfg['methods']['mp']['token'] ?? ''; 
        if(empty($accessToken)) die("Error: Token MP no configurado.");

        $url = "https://api.mercadopago.com/checkout/preferences";
        $notificationUrl = __BASE_URL__ . "api/mercadopago_receiver.php";

        if (strpos($notificationUrl, 'localhost') !== false || strpos($notificationUrl, '127.0.0.1') !== false) {
            die("Error: MercadoPago no funciona en Localhost.");
        }

        $body = [
            "items" => [
                [
                    "title" => "$coins $targetColumn - $user", // Mostramos el nombre real de la moneda
                    "quantity" => 1,
                    "currency_id" => $currency,
                    "unit_price" => $amount
                ]
            ],
            "payer" => [ "email" => "test_user_123@test.com" ],
            "back_urls" => [
                "success" => __BASE_URL__ . "index.php?page=usercp&success=mp",
                "failure" => __BASE_URL__ . "index.php?page=usercp&error=mp",
                "pending" => __BASE_URL__ . "index.php?page=usercp&pending=mp"
            ],
            "auto_return" => "approved",
            "notification_url" => $notificationUrl,
            "external_reference" => $txnId
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Content-Type: application/json",
            "Authorization: Bearer $accessToken"
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $jsonResp = json_decode($response, true);

        if (($httpCode == 200 || $httpCode == 201) && isset($jsonResp['init_point'])) {
            header("Location: " . $jsonResp['init_point']);
        } else {
            echo "Error MP: <pre>" . print_r($jsonResp, true) . "</pre>";
        }
        break;

    case 'paypal':
        $businessEmail = $donCfg['methods']['paypal']['email'] ?? '';
        if(empty($businessEmail)) die("Error: Email PayPal no configurado.");

        $paypalURL = "https://www.paypal.com/cgi-bin/webscr?";
        $params = [
            'cmd'           => '_xclick',
            'business'      => $businessEmail,
            'item_name'     => "$coins $targetColumn - $user",
            'item_number'   => $packId,
            'amount'        => $amount,
            'currency_code' => 'USD',
            'custom'        => $txnId,
            'no_shipping'   => 1,
            'return'        => __BASE_URL__ . "index.php?page=usercp&success=pp",
            'cancel_return' => __BASE_URL__ . "index.php?page=usercp&cancel=pp",
            'notify_url'    => __BASE_URL__ . "api/paypal_receiver.php"
        ];
        header("Location: " . $paypalURL . http_build_query($params));
        break;

    default:
        header("Location: " . __BASE_URL__ . "index.php?page=usercp");
        break;
}
?>