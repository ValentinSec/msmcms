<?php
/**
 * Receptor de Pagos MercadoPago (Webhook/IPN)
 * Ubicación: api/mercadopago_receiver.php
 * Fix: Race Condition (Bloqueo de archivo para evitar pagos dobles)
 */

define('access', true);

// LOGS
$logFile = 'mp_access.log';
$date = date('Y-m-d H:i:s');

// 1. CARGA DEL NÚCLEO
$root_path = dirname(__DIR__) . '/';
ob_start();
try {
    // CAMBIO: Se busca muservermaker.php en lugar de webengine.php
    if (file_exists($root_path . 'includes/muservermaker.php')) {
        require_once($root_path . 'includes/muservermaker.php');
    } else {
        throw new Exception("No se encuentra muservermaker.php");
    }
} catch (Exception $e) {
    file_put_contents($logFile, "[$date] ERROR CRÍTICO: " . $e->getMessage() . "\n", FILE_APPEND);
    http_response_code(500);
    die();
}
ob_clean();

// 2. CARGAR CONFIGURACIÓN
$donCfgFile = $root_path . 'includes/config/donation_config.json';
if (!file_exists($donCfgFile)) die();

$donCfg = json_decode(file_get_contents($donCfgFile), true);
$accessToken = $donCfg['methods']['mp']['token'] ?? '';

if(empty($accessToken)) die();

// 3. CAPTURAR DATOS
$id = $_GET['data_id'] ?? $_GET['id'] ?? null;
$topic = $_GET['topic'] ?? $_GET['type'] ?? null;

if (!$id || !$topic) die();

if ($topic == 'payment' || $topic == 'collection') {
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://api.mercadopago.com/v1/payments/$id");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [ "Authorization: Bearer $accessToken" ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode == 200) {
        $paymentData = json_decode($response, true);
        $status = $paymentData['status'];
        $external_ref = $paymentData['external_reference'];
        $amount_paid = $paymentData['transaction_amount'];

        if ($status == 'approved') {
            attempt_reward_delivery($external_ref, $amount_paid, $id);
        }
    }
}

http_response_code(200); 

function attempt_reward_delivery($txnId, $amountPaid, $mpPaymentId) {
    global $root_path; 
    $date = date('Y-m-d H:i:s');

    $customCfgFile = $root_path . 'includes/config/custom_settings.json';
    $customCfg = json_decode(file_get_contents($customCfgFile), true);

    $txnLogFile = $root_path . 'includes/config/donation_transactions.json';
    
    // --- INICIO BLOQUEO DE ARCHIVO (FIX DOBLE PAGO) ---
    $fp = fopen($txnLogFile, 'c+'); // Abrir para lectura/escritura
    
    if (flock($fp, LOCK_EX)) { // BLOQUEO EXCLUSIVO: Nadie más puede leer/escribir hasta que terminemos
        
        $jsonContent = stream_get_contents($fp);
        $transactions = $jsonContent ? json_decode($jsonContent, true) : [];
        $updated = false;
        $found = false;

        foreach ($transactions as &$t) {
            if ($t['id'] == $txnId) {
                $found = true;
                
                // Si ya está completada, soltamos el candado y nos vamos
                if ($t['status'] == 'Completed') {
                    flock($fp, LOCK_UN);
                    fclose($fp);
                    return; 
                }

                $user = $t['user'];
                $coins_to_add = $t['coins'];
                $db_col_val = $t['col_coin'] ?? ($customCfg['col_coin_1'] ?? 'WCoinC');
                $db_table = $customCfg['table_coins'] ?? 'MEMB_INFO';
                
                if (isset($customCfg['col_user']) && !empty($customCfg['col_user'])) {
                    $db_user_col = $customCfg['col_user'];
                } else {
                    $db_user_col = (strtolower($db_table) == 'memb_info') ? 'memb___id' : 'AccountID';
                }

                try {
                    // Aseguramos la conexión usando la clase cargada por muservermaker.php
                    $db = Connection::Database('Me_MuOnline'); 

                    if (strtolower($db_table) != 'memb_info') {
                        $check = $db->query_fetch_single("SELECT $db_user_col FROM $db_table WHERE $db_user_col = ?", [$user]);
                        if(!$check) {
                            $c1 = $customCfg['col_coin_1'] ?? 'WCoinC';
                            $c2 = $customCfg['col_coin_2'] ?? 'WCoinP';
                            $c3 = $customCfg['col_coin_3'] ?? 'GoblinPoint';
                            $db->query("INSERT INTO $db_table ($db_user_col, $c1, $c2, $c3) VALUES (?, 0, 0, 0)", [$user]);
                        }
                    }

                    $sql = "UPDATE $db_table SET $db_col_val = $db_col_val + ? WHERE $db_user_col = ?";
                    $db->query($sql, [$coins_to_add, $user]);

                    $t['status'] = 'Completed';
                    $t['gateway_id'] = $mpPaymentId;
                    $updated = true;

                    file_put_contents('mp_success.log', "[$date] ÉXITO: $user recibió $coins_to_add en $db_col_val (TXN: $txnId)\n", FILE_APPEND);

                } catch (Exception $e) {
                    file_put_contents('mp_error.log', "[$date] ERROR SQL: " . $e->getMessage() . "\n", FILE_APPEND);
                }
                break;
            }
        }

        // Si hubo cambios, reescribimos el archivo JSON de forma segura
        if ($updated) {
            ftruncate($fp, 0);      // Borrar contenido anterior
            rewind($fp);            // Volver al inicio
            fwrite($fp, json_encode($transactions, JSON_PRETTY_PRINT));
            fflush($fp);            // Forzar escritura en disco
        }
        
        flock($fp, LOCK_UN); // LIBERAR BLOQUEO
    }
    
    fclose($fp);
    // --- FIN BLOQUEO DE ARCHIVO ---
}
?>