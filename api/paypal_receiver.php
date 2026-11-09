<?php
/**
 * Receptor Automático PayPal
 * Ubicación: api/paypal_receiver.php
 * Fix: Race Condition (Bloqueo de archivo)
 */

define('access', true);
$root_path = dirname(__DIR__) . '/';

// Leer POST
$raw_post_data = file_get_contents('php://input');
$raw_post_array = explode('&', $raw_post_data);
$myPost = [];
foreach ($raw_post_array as $keyval) {
    $keyval = explode('=', $keyval);
    if (count($keyval) == 2) $myPost[$keyval[0]] = urldecode($keyval[1]);
}

$req = 'cmd=_notify-validate';
foreach ($myPost as $key => $value) {
    $value = urlencode($value);
    $req .= "&$key=$value";
}

$ch = curl_init('https://www.paypal.com/cgi-bin/webscr');
curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, $req);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_HTTPHEADER, array('Connection: Close'));
$res = curl_exec($ch);
curl_close($ch);

if (strcmp($res, "VERIFIED") == 0) {
    
    $txn_id = $myPost['custom'];
    $payment_status = $myPost['payment_status'];
    $receiver_email = $myPost['receiver_email']; 

    $donCfg = json_decode(file_get_contents($root_path . 'includes/config/donation_config.json'), true);
    $adminEmail = $donCfg['methods']['paypal']['email'] ?? '';

    if ($payment_status == 'Completed' && strtolower($receiver_email) == strtolower($adminEmail)) {
        
        ob_start();
        require_once($root_path . 'includes/muservermaker.php');
        ob_clean();

        $customCfg = json_decode(file_get_contents($root_path . 'includes/config/custom_settings.json'), true);
        $txnLogFile = $root_path . 'includes/config/donation_transactions.json';
        
        // --- INICIO BLOQUEO SEGURO ---
        $fp = fopen($txnLogFile, 'c+');
        if (flock($fp, LOCK_EX)) {
            
            $jsonContent = stream_get_contents($fp);
            $transactions = $jsonContent ? json_decode($jsonContent, true) : [];
            $updated = false;
            
            foreach ($transactions as &$t) {
                if ($t['id'] == $txn_id) {
                    
                    // Si ya está completada, nos vamos sin hacer nada
                    if ($t['status'] == 'Completed') {
                        flock($fp, LOCK_UN);
                        fclose($fp);
                        exit; 
                    }

                    $user = $t['user'];
                    $coins = $t['coins'];
                    
                    $db_col_val = $t['col_coin'] ?? ($customCfg['col_coin_1'] ?? 'WCoinC');
                    $db_table = $customCfg['table_coins'] ?? 'MEMB_INFO';
                    
                    if (isset($customCfg['col_user']) && !empty($customCfg['col_user'])) {
                        $db_user_col = $customCfg['col_user'];
                    } else {
                        $db_user_col = (strtolower($db_table) == 'memb_info') ? 'memb___id' : 'AccountID';
                    }

                    try {
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
                        $db->query($sql, [$coins, $user]);

                        $t['status'] = 'Completed';
                        $updated = true;
                        
                        file_put_contents('paypal_success.log', "[" . date('Y-m-d H:i:s') . "] PP OK: $user +$coins en $db_col_val\n", FILE_APPEND);
                        
                    } catch (Exception $e) {
                        file_put_contents('paypal_error.log', "SQL Error: " . $e->getMessage() . "\n", FILE_APPEND);
                    }
                    break;
                }
            }
            
            if ($updated) {
                ftruncate($fp, 0);
                rewind($fp);
                fwrite($fp, json_encode($transactions, JSON_PRETTY_PRINT));
                fflush($fp);
            }
            
            flock($fp, LOCK_UN);
        }
        fclose($fp);
        // --- FIN BLOQUEO SEGURO ---
    }
}
?>