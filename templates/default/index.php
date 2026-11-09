<?php

$request_path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$path_parts = explode('/', trim($request_path, '/'));

if (isset($path_parts[0]) && !empty($path_parts[0])) {
    $_REQUEST['page'] = $path_parts[0];
}
if (isset($path_parts[1]) && !empty($path_parts[1])) {
    $_REQUEST['subpage'] = $path_parts[1];
}
// --- FIN PARCHE RUTAS AMIGABLES ---

if(!defined('access') or !access) die();
include('inc/template.functions.php');

// --- 0. CARGAR CONFIGURACIÓN DEL MÓDULO BLACK MARKET ---
$bmConfigFile = __PATH_INCLUDES__ . 'config/black_market_config.json';
$bmActive = false;
if (file_exists($bmConfigFile)) {
    $bmConf = json_decode(file_get_contents($bmConfigFile), true);
    if (isset($bmConf['module_active']) && $bmConf['module_active'] == 'on') {
        $bmActive = true;
    }
}

// --- INCLUIR PARSER DE ITEMS PARA TOOLTIPS DE INDEX ---
include_once('includes/config/items_manager.php'); 
if(!function_exists('bm_parse_item_index')) {
    // Función ligera para el frontend (FULL DATA)
    function bm_parse_item_index($hex) {
        $id = hexdec(substr($hex, 0, 2));
        $byte1 = hexdec(substr($hex, 2, 2));
        $byte7 = hexdec(substr($hex, 14, 2));
        $byte9 = hexdec(substr($hex, 18, 2));
        $byte10 = hexdec(substr($hex, 20, 2)); 
        $group = ($byte9 >> 4) & 0xF;
        
        // Detección S4 / Socket
        $isSocketItem = false;
        if (($group >= 7 && $group <= 11) && ($id >= 45 && $id <= 65)) $isSocketItem = true; 
        if ($group == 0 && $id >= 26) $isSocketItem = true; 
        if ($group == 2 && $id >= 16) $isSocketItem = true; 
        if ($group == 4 && $id >= 23) $isSocketItem = true; 
        if ($group == 5 && $id >= 30) $isSocketItem = true; 

        $level = ($byte1 >> 3) & 0xF;
        $skill = ($byte1 >> 7) & 1;
        $luck = ($byte1 >> 2) & 1;
        
        // Fix Option Calc (PHP)
        $optVal = ($byte1 & 3);
        if (($byte7 & 64) > 0) $optVal += 4;
        $option = $optVal * 4;
        
        // Excellent Options Real Names
        $exeType = 'DEFENSE';
        if ($group < 6) $exeType = 'ATTACK';
        if ($group == 12) $exeType = 'WINGS';
        if ($group == 13) $exeType = 'DEFENSE';

        $excOpts = [];
        $bits = [32, 16, 8, 4, 2, 1]; 
        foreach ($bits as $bit) {
            if ($byte7 & $bit) {
                if ($exeType == 'ATTACK') {
                    if($bit==32) $excOpts[] = "Excellent Damage Rate +10%";
                    if($bit==16) $excOpts[] = "Increase Damage +level/20";
                    if($bit==8)  $excOpts[] = "Increase Damage +2%";
                    if($bit==4)  $excOpts[] = "Attack Speed +7";
                    if($bit==2)  $excOpts[] = "Increase Life after monster +life/8";
                    if($bit==1)  $excOpts[] = "Increase Mana after monster +mana/8";
                } elseif ($exeType == 'DEFENSE') {
                    if($bit==32) $excOpts[] = "Increase Max HP +4%";
                    if($bit==16) $excOpts[] = "Increase Max Mana +4%";
                    if($bit==8)  $excOpts[] = "Damage Decrease +4%";
                    if($bit==4)  $excOpts[] = "Reflect Damage +5%";
                    if($bit==2)  $excOpts[] = "Defense Success Rate +10%";
                    if($bit==1)  $excOpts[] = "Increases acquisition rate of Zen after hunting monsters +30%";
                } elseif ($exeType == 'WINGS') {
                    if($bit==1)  $excOpts[] = "Ignore opponent's defensive power by 5%";
                    if($bit==2)  $excOpts[] = "Return's the enemy's attack power by 5%";
                    if($bit==4)  $excOpts[] = "Complete recovery of HP in 5% rate";
                    if($bit==8)  $excOpts[] = "Complete recovery of Mana in 5% rate";
                }
            }
        }
        
        // 380 Option (Fix Joyeria)
        // 380 Option (Fix Joyeria)
        $is380 = false;
        $op380Names = [];
        if (!$isSocketItem && $group != 13) {
            $is380 = (($byte9 & 16) > 0) || (($byte9 & 8) > 0);
            // Obtenemos los nombres de las opciones 380 de forma segura consultando la clase
            if ($is380 && class_exists('ItemsManager') && method_exists('ItemsManager', 'get380Options')) {
                $op380Names = ItemsManager::get380Options($group, $id);
            }
        }
        
        $name = ItemsManager::getItemName($group, $id);
        $isExc = count($excOpts) > 0;
        
        // HTML Build (Strict Order: Name > Skill > Luck > Add > 380 > Harmony > Exc > Socket)
        $html = "<div class='tt-box'>";
        
        // 1. Name (+Level Hidden if 0)
        $lvlStr = ($level > 0) ? " +".$level : "";
        $nameColor = $isExc ? "tt-exc" : "tt-name";
        // Estilos inline para asegurar visualización en Index
        $html .= "<span class='$nameColor' style='display:block; font-weight:bold; font-size:13px; margin-bottom:5px; border-bottom:1px solid #444; padding-bottom:5px; color:".($isExc?"#2ecc71":"#fff")."'>$name$lvlStr</span>";
        
        // 5. 380
        if($is380) {
            if(!empty($op380Names)) {
                foreach($op380Names as $opName) {
                    $html .= "<div class='tt-380' style='color:#d000d0; font-weight:bold;'>$opName</div>";
                }
            } else {
                $html .= "<div class='tt-380' style='color:#d000d0; font-weight:bold;'>380 Option Active</div>";
            }
        }
        
        // 6. Harmony
        if (!$isSocketItem && $byte10 > 0) {
            $hType = 1; 
            if ($group == 5) $hType = 5; 
            if ($group >= 6 && $group <= 11) $hType = 6; 

            $optId = ($byte10 >> 4) & 0xF;
            $optLvl = ($byte10 & 0xF);

            $hNames = [
                1 => [1 => "Min. attack increase", 2 => "Max. attack increase", 3 => "Required strength decrease", 4 => "Required agility decrease", 5 => "Attack increase (Min,Max)", 6 => "Critical damage increase", 7 => "Skill attack increase", 8 => "Attack sucess rate increase (PVP)", 9 => "SD decrement increase", 10 => "SD bypass rate increase"],
                5 => [1 => "Wizardry increase", 2 => "Required strength decrease", 3 => "Required agility decrease", 4 => "Skill attack increase", 5 => "Critical damage increase", 6 => "SD decrement increase", 7 => "Attack success rate increase (PVP)", 8 => "SD bypass rate increase"],
                6 => [1 => "Defense increase", 2 => "Max. AG increase", 3 => "Max. HP increase", 4 => "Life auto increment increase", 5 => "Mana auto increment increase", 6 => "Defense success rate increase (PVP)", 7 => "Damage decrement increase", 8 => "SD ratio increase"]
            ];
            
            // Simplified Harmony Value Logic for Display
            if (isset($hNames[$hType][$optId])) {
                $hName = $hNames[$hType][$optId];
                $html .= "<div style='color:#ffff00'>$hName (Lvl $optLvl)</div>";
            }
        }
        // 2. Skill
        if($skill) $html .= "<div class='tt-opt' style='color:#9aadcd'>Skill Attack Power</div>";
        
        // 3. Luck
        if($luck) $html .= "<div class='tt-luck' style='color:#9aadcd'>Luck (success rate +25%)</div><div class='tt-luck' style='color:#9aadcd'>Luck (critical damage rate +5%)</div>";
        
        // 4. Add Option
        if($option>0) $html .= "<div class='tt-opt' style='color:#9aadcd'>Additional Option +$option</div>";
        
        // 7. Excellent
        foreach($excOpts as $ex) $html .= "<div class='tt-exc' style='color:#2ecc71'>$ex</div>";

        // 8. Sockets
        $socketsHTML = "";
        $hasSockets = false;
        $elements = [0=>'Fire', 1=>'Water', 2=>'Ice', 3=>'Wind', 4=>'Earth', 5=>'Lightning'];
        $socketNames = [0=>"Attack/Wizardry increase", 1=>"Attack Speed increase", 2=>"Max Attack/Wizardry increase", 3=>"Min Attack/Wizardry increase", 4=>"Attack/Wizardry increase", 5=>"AG consumption decrease", 10=>"Block Rating increase", 11=>"Defense increase", 12=>"Shield Protection increase", 13=>"Damage Reduction", 14=>"Damage Reflection", 16=>"Monster Die Life recovery", 17=>"Monster Die Mana recovery", 18=>"Skill attack power increase", 19=>"Attack Accuracy increase", 20=>"Item durability increase", 21=>"Auto Life recovery increase", 22=>"Max Life increase", 23=>"Max Mana increase", 24=>"Auto Mana recovery increase", 25=>"Max AG increase", 26=>"AG value increase", 29=>"Excellent Damage increase", 30=>"Excellent Damage Rate increase", 31=>"Critical Damage increase", 32=>"Critical Damage Rate increase", 34=>"Strength increase", 35=>"Dexterity increase", 36=>"Health increase", 37=>"Energy increase"];

        for($k=0; $k<5; $k++) {
            $idx = 22 + ($k*2);
            if(strlen($hex) >= $idx+2) {
                $sockVal = hexdec(substr($hex, $idx, 2));
                if($sockVal != 255 && $sockVal != 0) { 
                    $hasSockets = true;
                    if($sockVal == 254) { 
                        $socketsHTML .= "<div class='tt-opt' style='color:#666'>Socket ".($k+1).": Vacío</div>";
                    } else {
                        $seedType = floor($sockVal / 50);
                        $seedId = $sockVal % 50;
                        $sLvl = floor($sockVal / 50) + 1; // Level Fix
                        
                        $elName = isset($elements[$seedType]) ? $elements[$seedType] : 'Seed';
                        $optName = isset($socketNames[$seedId]) ? $socketNames[$seedId] : 'Unknown';
                        $socketsHTML .= "<div class='tt-opt' style='color:#cca4e3'>Socket ".($k+1).": $elName ($optName) +$sLvl</div>";
                    }
                }
            }
        }
        if($hasSockets) {
            $html .= "<div style='margin-top:5px; border-top:1px solid #444; padding-top:2px;'>$socketsHTML</div>";
        }

        $html .= "</div>";
        return $html;
    }
}

// --- INICIO MÓDULO DE SEGURIDAD GLOBAL ---
$secConfigFile = __PATH_INCLUDES__ . 'config/security_config.json';
if (file_exists($secConfigFile)) {
    $secConfig = json_decode(file_get_contents($secConfigFile), true);
    $userIp = $_SERVER['REMOTE_ADDR'];
    if (!empty($secConfig['blocked_ips'])) {
        $blockedList = explode("\n", str_replace("\r", "", $secConfig['blocked_ips']));
        if (in_array(trim($userIp), array_map('trim', $blockedList))) {
            header('HTTP/1.1 403 Forbidden');
            die("Access Denied");
        }
    }
    if (isset($secConfig['ddos_active']) && $secConfig['ddos_active'] == 1) {
        if (!session_id()) @session_start();
        $limit = $secConfig['ddos_limit'] ?? 10; 
        $responseTime = ($secConfig['ddos_response'] ?? 500) / 1000;
        $penaltyTime = 10; 
        $now = microtime(true);
        if (isset($_SESSION['ddos_penalty_until']) && $now < $_SESSION['ddos_penalty_until']) {
            header('HTTP/1.1 429 Too Many Requests');
            die("Access Restricted");
        }
        if (!isset($_SESSION['last_request_micro'])) {
            $_SESSION['last_request_micro'] = $now;
            $_SESSION['req_count'] = 1;
        } else {
            $timeDiff = $now - $_SESSION['last_request_micro'];
            if ($timeDiff < $responseTime) {
                $_SESSION['req_count']++;
                if ($_SESSION['req_count'] > $limit) {
                    $_SESSION['ddos_penalty_until'] = $now + $penaltyTime;
                    header('HTTP/1.1 429 Too Many Requests');
                    die("Access Restricted");
                }
            } else {
                $_SESSION['last_request_micro'] = $now;
                $_SESSION['req_count'] = 1;
            }
        }
    }
}

// --- FIX LECTURA DE CACHÉ ---
// Intentamos leer manualmente el archivo json generado por el cron nuevo
$cacheFilePath = __PATH_INCLUDES__ . 'cache/server_info.cache';
$realOnline = 0;

if(file_exists($cacheFilePath)) {
    $rawCache = file_get_contents($cacheFilePath);
    $cacheData = json_decode($rawCache, true);
    
    // Verificamos si tiene la estructura que guardamos [time, [data]]
    if(isset($cacheData[1][0])) {
        $srvInfo = explode("|", $cacheData[1][0]);
        // Index 3 es el online (cuentas|pjs|guilds|ONLINE)
        $realOnline = isset($srvInfo[3]) ? (int)$srvInfo[3] : 0;
    }
}
// --- FIN FIX ---

$multOnline = (float)custom_config('fake_online');
$displayOnline = round($realOnline * ($multOnline > 0 ? $multOnline : 1));
$maxOnline = (int)custom_config('server_max_users') ?: 1000; 
$porcentajeOnline = ($displayOnline / $maxOnline) * 100;
if($porcentajeOnline > 100) $porcentajeOnline = 100; 

if(!isset($_REQUEST['page'])) { $_REQUEST['page'] = ''; }
if(!isset($_REQUEST['subpage'])) { $_REQUEST['subpage'] = ''; }

if($_REQUEST['page'] == 'usercp') {
    define('USERCP_CLEAN', true); 
    include('modules/usercp.php');
    exit();
}

$muservermakerParams = muservermakerConfigs();
$activeTplName = $muservermakerParams['website_template']; 

$templateConfigPath = $_SERVER['DOCUMENT_ROOT'] . '/templates/' . $activeTplName . '/config.json';
$tplData = ['hero_logo_width' => '300px'];
if(file_exists($templateConfigPath)) {
    $tplContent = file_get_contents($templateConfigPath);
    $tplData = json_decode($tplContent, true);
}

// --- AJAX HANDLER PARA EL MERCADO DEL INDEX (BLACK MARKET) ---
if(isset($_POST['market_ajax']) && $_POST['market_ajax'] == 'true') {
    while (ob_get_level()) ob_end_clean();
    header('Content-Type: application/json');
    
    try {
        $db = Connection::Database('Me_MuOnline');
        $serverFile = 'includes/config/muservermaker.json';
        $serverData = file_exists($serverFile) ? json_decode(file_get_contents($serverFile), true) : [];
        $dbName = isset($serverData['SQL_DB_NAME']) ? $serverData['SQL_DB_NAME'] : 'MuOnline';
        
        if($_POST['action'] == 'load_category') {
            $cat = (int)$_POST['category'];
            $items = $db->query_fetch("SELECT TOP 200 id, seller_account, CONVERT(varchar(max), item_hex, 2) as item_hex, item_name, price, currency, date_posted FROM MUSERVERMAKER_MARKET_SELL ORDER BY date_posted DESC");
            $filtered = [];
            
            foreach($items as $item) {
                $hex = $item['item_hex'];
                $id = hexdec(substr($hex, 0, 2));
                $byte9 = hexdec(substr($hex, 18, 2));
                $group = ($byte9 >> 4) & 0xF;
                
                if($cat == -1 || $group == $cat) {
                    $item = array_change_key_case($item, CASE_LOWER);
                    $item['img'] = __BASE_URL__ . "img/items/{$group}-{$id}.gif";
                    $item['tooltip'] = bm_parse_item_index($hex); 
                    $item['clean_name'] = explode('+', strip_tags($item['tooltip']))[0];
                    $filtered[] = $item;
                }
            }
            echo json_encode(['status' => 'success', 'data' => $filtered]);
            exit;
        }
        
        if($_POST['action'] == 'buy_item') {
            if(!isLoggedIn()) throw new Exception("Debes iniciar sesión.");
            $username = $_SESSION['username'];
            $id = (int)$_POST['id'];
            
            $itemData = $db->query_fetch_single("SELECT id, seller_account, CONVERT(varchar(max), item_hex, 2) as item_hex, item_name, price, currency FROM MUSERVERMAKER_MARKET_SELL WHERE id = $id");
            if(!$itemData) throw new Exception("Item no disponible.");
            if($itemData['seller_account'] == $username) throw new Exception("No puedes auto-comprarte.");
            
            $price = (int)$itemData['price'];
            $seller = $itemData['seller_account'];
            $currency = $itemData['currency'];
            
            $colCoin = 'WCoinC'; $tableCoin = 'CashShopData'; $colUser = 'AccountID'; 
            if($currency == 'Credits') { $tableCoin = 'MEMB_INFO'; $colCoin = 'Credits'; $colUser = 'memb___id'; }
            
            $buyerData = $db->query_fetch_single("SELECT $colCoin FROM [$dbName].[dbo].[$tableCoin] WHERE $colUser = '$username'");
            if(!$buyerData || $buyerData[$colCoin] < $price) throw new Exception("Saldo insuficiente.");
            
            $whData = $db->query_fetch_single("SELECT Items FROM [$dbName].[dbo].[warehouse] WHERE AccountId = '$username'");
            $hexWh = strtoupper((!ctype_xdigit((string)$whData['Items'])) ? bin2hex($whData['Items']) : $whData['Items']);
            if(!preg_match('/^[F0]+$/', $hexWh)) throw new Exception("Tu baúl debe estar VACÍO.");
            
            $db->query("UPDATE [$dbName].[dbo].[$tableCoin] SET $colCoin = $colCoin - $price WHERE $colUser = '$username'");
            $pay = floor($price * 0.9);
            $db->query("UPDATE [$dbName].[dbo].[$tableCoin] SET $colCoin = $colCoin + $pay WHERE $colUser = '$seller'");
            
            $targetHex = $itemData['item_hex'];
            $newWh = $targetHex . str_repeat('F', 3840 - strlen($targetHex));
            $bin = "0x" . $newWh;
            $db->query("UPDATE [$dbName].[dbo].[warehouse] SET Items = $bin WHERE AccountId = '$username'");
            $db->query("DELETE FROM MUSERVERMAKER_MARKET_SELL WHERE id = $id");
            
            try {
                $hexStr = $itemData['item_hex'];
                $idItem = hexdec(substr($hexStr, 0, 2));
                $byte9  = hexdec(substr($hexStr, 18, 2));
                $group  = ($byte9 >> 4) & 0xF;
                $realName = ItemsManager::getItemName($group, $idItem); 
                $realName = str_replace("'", "", $realName); 

                $sqlLog = "INSERT INTO [$dbName].[dbo].[MUSERVERMAKER_MARKET_BUY] 
                          (buyer_account, seller_account, item_hex, item_name, price_paid, currency, transaction_date) 
                          VALUES 
                          ('$username', '$seller', CONVERT(varbinary(max), '0x$targetHex', 1), '$realName', $price, '$currency', GETDATE())";
                
                $db->query($sqlLog);
            } catch (Exception $logEx) { }
            
            echo json_encode(['status' => 'success']);
            exit;
        }
        
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo custom_config('web_title'); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="icon" type="image/x-icon" href="<?php echo __BASE_URL__; ?>templates/<?php echo $activeTplName; ?>/img/favicon.ico?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="<?php echo __BASE_URL__; ?>templates/misc/css/sidebar.css">
    <link href="<?php echo __PATH_TEMPLATE_CSS__; ?>main.css?v=1.1" rel="stylesheet">
    <link href="/templates/misc/css/main.css?v=1.1" rel="stylesheet">
    <link href="<?php echo __PATH_TEMPLATE_CSS__; ?>rankings.css?v=1.1" rel="stylesheet">
    <link href="<?php echo __BASE_URL__; ?>templates/misc/css/navbar.css" rel="stylesheet">
    <link href="<?php echo __PATH_TEMPLATE_CSS__; ?>login.css?v=1.1" rel="stylesheet">
    <link href="<?php echo __PATH_TEMPLATE_CSS__; ?>footer.css?v=1.0" rel="stylesheet">
    <link href="<?php echo __PATH_TEMPLATE_CSS__; ?>info.css?v=1.0" rel="stylesheet">
    <link href="<?php echo __PATH_TEMPLATE_CSS__; ?>news.css?v=1.0" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@700&family=Roboto:wght@300;400&display=swap" rel="stylesheet">
    <style>
        .news-container { display: flex; flex-direction: column; gap: 30px; width: 100%; }
        .news-item { background: rgba(0, 0, 0, 0.4); border: 1px solid rgba(255, 255, 255, 0.05); border-radius: 12px; overflow: hidden; }
        .news-header { padding: 20px; border-bottom: 1px solid rgba(255, 255, 255, 0.05); }
        .news-title-text { font-family: 'Cinzel', serif; color: #FF8A3B; font-size: 22px; margin: 0; text-transform: uppercase; }
        .news-body-text { font-family: 'Roboto', sans-serif; padding: 20px; color: #cbd5e1; line-height: 1.7; font-size: 15px; }
        .news-date { font-size: 11px; color: #64748b; padding: 0 20px 20px 20px; }
        .news-full { position: relative; min-height: 350px; display: flex; align-items: flex-end; background-size: cover; background-position: center; border:none; }
        .news-full .news-overlay { position: absolute; top:0; left:0; width:100%; height:100%; background: linear-gradient(to top, rgba(0,0,0,0.95) 10%, rgba(0,0,0,0.2) 100%); }
        .news-full .news-content-wrapper { position: relative; z-index: 2; padding: 30px; width: 100%; }
        .news-full .news-title-text { color: #fff; text-shadow: 0 2px 4px rgba(0,0,0,0.5); }
        .news-expanded-img { width: 100%; height: auto; max-height: 400px; object-fit: cover; border-bottom: 1px solid rgba(255, 255, 255, 0.05); }
        .news-cascada-img { width: 100%; height: auto; padding: 0 20px 20px 20px; border-radius: 0 0 12px 12px; }

        /* MARKETPLACE STYLES FIXED */
        .market-ticker-wrap {
            width: 100%; overflow: hidden; background: rgba(0, 0, 0, 0.3); padding: 20px 0;
            border-top: 1px solid rgba(255,255,255,0.05); border-bottom: 1px solid rgba(255,255,255,0.05); position: relative;
        }
        .market-ticker { display: flex; gap: 20px; width: fit-content; animation: ticker 40s linear infinite; }
        .market-ticker:hover { animation-play-state: paused; }
        @keyframes ticker { 0% { transform: translateX(0); } 100% { transform: translateX(-50%); } }

        /* --- CONTENEDORES MÁS GRANDES --- */
        .m-card-mini {
            width: 260px; height: 110px; flex-shrink: 0; 
            background: rgba(13, 17, 23, 0.9); border: 1px solid #30363d;
            border-radius: 6px; padding: 15px; display: flex; align-items: center; gap: 15px;
            cursor: pointer; transition: 0.2s; position: relative; overflow: visible !important;
        }
        .m-card-mini:hover { border-color: #FF8A3B; transform: scale(1.02); background: #1a202c; z-index: 50; }
        .m-card-mini img { width: 60px; height: 60px; object-fit: contain; background: #000; border: 1px solid #333; border-radius: 4px; }
        
        .m-mini-info { flex: 1; display: flex; flex-direction: column; overflow: hidden; justify-content: center; }
        .m-mini-name { font-size: 13px; font-weight: bold; color: #e2e8f0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .m-mini-price { font-size: 12px; color: #ffd700; font-family: 'Roboto'; font-weight:bold; margin-bottom: 5px; }
        .m-btn-buy {
            background: #238636; 
            color: #fff; 
            border: none; 
            border-radius: 3px; 
            font-size: 10px; 
            font-weight: bold;
            padding: 4px 10px; 
            cursor: pointer; 
            text-transform: uppercase; 
            width: fit-content;
            
            /* ESTO CENTRA EL BOTÓN: */
            margin: 0 auto; 
            display: block; 
        }
        .m-btn-buy:hover { background: #2ea043; }

        /* ESTILOS COMUNES TOOLTIP (Negro fondo, letras colores) */
        .tt-box { text-align: center; }
        /* tt-name, tt-exc, etc se definen inline o en las clases del parser */

        /* Tooltip Global para Carrusel y Modal */
        #global-tt {
            position: fixed; display: none; z-index: 100000; /* Siempre encima */
            background: rgba(0, 0, 0, 0.95); border: 1px solid #555;
            padding: 10px; border-radius: 6px; pointer-events: none;
            box-shadow: 0 0 20px rgba(0,0,0,0.8); width: 220px;
            font-size: 12px;
        }

        /* MARKET WORKSPACE MODAL */
        .ws-modal-overlay {
            display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.9); z-index: 9999; align-items: center; justify-content: center; backdrop-filter: blur(5px);
        }
        .ws-workspace {
            display: flex; width: 90%; max-width: 1200px; height: 80vh;
            background: #0d1117; border: 1px solid #30363d; border-radius: 8px; overflow: hidden; box-shadow: 0 0 50px rgba(0,0,0,0.8);
        }
        .ws-sidebar-left { width: 220px; background: #161b22; border-right: 1px solid #30363d; display: flex; flex-direction: column; }
        .cat-header { padding: 15px; font-weight: bold; color: #8b949e; border-bottom: 1px solid #30363d; font-family: 'Cinzel', serif; }
        .cat-list { flex: 1; overflow-y: auto; padding: 10px; }
        .cat-btn {
            width: 100%; text-align: left; background: transparent; border: none; padding: 10px;
            color: #c9d1d9; font-size: 13px; border-radius: 4px; cursor: pointer; transition: 0.2s; display: flex; align-items: center; gap: 10px;
        }
        .cat-btn:hover, .cat-btn.active { background: rgba(56, 139, 253, 0.1); color: #58a6ff; }
        
        .ws-center-panel { flex: 1; display: flex; flex-direction: column; position: relative; background: #0d1117; }
        .center-toolbar { padding: 10px 20px; border-bottom: 1px solid #30363d; display: flex; align-items: center; background: #161b22; }
        .search-wrapper { position: relative; width: 300px; }
        .search-icon { position: absolute; left: 10px; top: 50%; transform: translateY(-50%); color: #8b949e; font-size: 12px; }
        .search-input {
            width: 100%; background: #0d1117; border: 1px solid #30363d; padding: 6px 10px 6px 30px;
            border-radius: 4px; color: #fff; font-size: 13px; outline: none;
        }
        
        /* GRID CARDS EN EL MODAL */
        .item-grid-container { flex: 1; overflow-y: auto; padding: 20px; display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 15px; align-content: start; }
        .ws-item-card {
            background: rgba(22, 27, 34, 0.8); border: 1px solid #30363d; border-radius: 6px;
            padding: 10px; display: flex; flex-direction: column; align-items: center; text-align: center;
            transition: 0.2s; cursor: pointer; position: relative;
        }
        .ws-item-card:hover { border-color: #FF8A3B; transform: translateY(-3px); background: #1c2128; }
        .ws-card-img { width: 60px; height: 60px; object-fit: contain; background: #000; border: 1px solid #333; border-radius: 4px; margin-bottom: 10px; }
        .ws-card-name { font-size: 12px; font-weight: bold; color: #e2e8f0; margin-bottom: 5px; width: 100%; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .ws-card-price { font-size: 12px; color: #ffd700; font-family: 'Roboto'; font-weight: bold; margin-bottom: 10px; }
        .ws-card-btn { width: 100%; padding: 5px; font-size: 11px; background: #238636; border: none; border-radius: 3px; color: #fff; font-weight: bold; }
        .ws-card-btn:hover { background: #2ea043; }

        .btn-ws-close { position: absolute; top: 10px; right: 20px; background: transparent; border: none; color: #8b949e; cursor: pointer; font-size: 20px; }
        
        .loading-overlay { position: absolute; top:0; left:0; width:100%; height:100%; background: rgba(13,17,23,0.8); display: none; align-items: center; justify-content: center; flex-direction: column; z-index: 50; }
        .spinner { width: 30px; height: 30px; border: 3px solid rgba(255,255,255,0.3); border-radius: 50%; border-top-color: #fff; animation: spin 1s ease-in-out infinite; margin-bottom: 10px; }
        @keyframes spin { to { transform: rotate(360deg); } }

        /* CONFIRMATION MODAL */
        .confirm-modal-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.95); z-index: 10001; align-items: center; justify-content: center; }
        .confirm-box { background: #161b22; border: 1px solid #30363d; padding: 20px; border-radius: 8px; text-align: center; width: 300px; color: #fff; }
        .confirm-title { color: #FF8A3B; font-weight: bold; margin-bottom: 15px; font-size: 16px; }
        .confirm-body { font-size: 13px; color: #ccc; margin-bottom: 20px; }
        .confirm-btns { display: flex; gap: 10px; justify-content: center; }
        .btn-confirm-yes { background: #238636; color: #fff; border: none; padding: 8px 20px; border-radius: 4px; font-weight: bold; cursor: pointer; }
        .btn-confirm-no { background: transparent; border: 1px solid #444; color: #888; padding: 8px 20px; border-radius: 4px; cursor: pointer; }

        /* ALERT MODAL */
        .alert-modal-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.9); z-index: 10002; align-items: center; justify-content: center; }
        .alert-box { background: #161b22; border: 1px solid #30363d; padding: 20px; border-radius: 8px; text-align: center; width: 300px; color: #fff; }
        .alert-btn { background: #1f6feb; color: #fff; border: none; padding: 8px 20px; border-radius: 4px; margin-top: 15px; cursor: pointer; }
    </style>
    <?php 
        $secConfigFile = __PATH_INCLUDES__ . 'config/security_config.json';
        if (file_exists($secConfigFile)) {
            $secConfig = json_decode(file_get_contents($secConfigFile), true);
            if (isset($secConfig['recaptcha_active']) && $secConfig['recaptcha_active'] == 1) {
                // Esta es la librería que "dibuja" el recaptcha
                echo '<script src="https://www.google.com/recaptcha/api.js?hl=es" async defer></script>';
            }
        }
    ?>
    <?php 
        $secConfigFile = __PATH_INCLUDES__ . 'config/security_config.json';
        if (file_exists($secConfigFile)) {
            $secConfig = json_decode(file_get_contents($secConfigFile), true);
            
            if (isset($secConfig['analytics_active']) && $secConfig['analytics_active'] == 1 && !empty($secConfig['analytics_key'])) {
                $gaKey = htmlspecialchars($secConfig['analytics_key']);
                ?>
                <script async src="https://www.googletagmanager.com/gtag/js?id=<?php echo $gaKey; ?>"></script>
                <script>
                  window.dataLayer = window.dataLayer || [];
                  function gtag(){dataLayer.push(arguments);}
                  gtag('js', new Date());
                  gtag('config', '<?php echo $gaKey; ?>');
                </script>
                <?php 
            }
        }
    ?>
</head>
<body>

    <div class="main-overlay"></div>

    <?php include $_SERVER['DOCUMENT_ROOT'] . '/templates/misc/inc/modules/navbar.php'; ?>

    <?php if(empty($_REQUEST['page'])): ?>
    <header class="hero">
    <div class="video-background-container">
        <iframe src="https://player.vimeo.com/video/1143233657?h=468905b58b&badge=0&autopause=0&player_id=vimeo-background&app_id=58479&background=1&muted=1&loop=1&autoplay=1" 
                frameborder="0" 
                allow="autoplay; fullscreen" 
                class="vimeo-video-full"></iframe>
    </div>
    
    <div class="hero-video-overlay"></div>

    <div class="container text-center hero-content-wrapper">
    <img src="<?php echo __PATH_TEMPLATE_IMG__; ?>logo.png" 
     style="width: <?php echo $tplData['hero_logo_width']; ?> !important; height: auto !important; max-width: 100%;" 
     alt="Logo">
    <h1 class="hero-title">BIENVENIDOS A <?php echo strtoupper(custom_config('server_name')); ?></h1>

    <div class="online-progress-wrapper mt-4 mb-2 mx-auto" style="max-width: 300px;">
        <div class="d-flex justify-content-between mb-2 px-1 small fw-bold text-uppercase" style="color: #E6D6C6; letter-spacing: 0.5px; font-size: 10px;">
            <span>Capacidad</span>
            <span><?php echo $displayOnline; ?> / <?php echo $maxOnline; ?></span>
        </div>
        <div class="progress custom-progress-bg">
            <div class="progress-bar progress-fill-gradient" 
                 role="progressbar" 
                 style="width: <?php echo $porcentajeOnline; ?>%;" 
                 aria-valuenow="<?php echo $porcentajeOnline; ?>" 
                 aria-valuemin="0" 
                 aria-valuemax="100">
            </div>
        </div>
    </div>
    
    <div class="row justify-content-center mt-5 g-3">
        <div class="col-md-2 col-6">
            <div class="stat-card">
                <i class="fas fa-users-cog stat-icon"></i>
                <span>Jugadores</span>
                <strong class="online-dot"><?php echo $displayOnline; ?></strong>
            </div>
        </div>
        <div class="col-md-2 col-6">
            <div class="stat-card">
                <i class="fas fa-bolt stat-icon"></i>
                <span>Estado</span>
                <strong style="color:#00ff88;">• Online</strong>
            </div>
        </div>
        <div class="col-md-2 col-6">
            <a href="<?php echo __BASE_URL__; ?>info" class="stat-card-link">
                <div class="stat-card clickable">
                    <i class="far fa-calendar-alt stat-icon"></i>
                    <span>Rates</span>
                    <strong>Info</strong>
                </div>
            </a>
        </div>
        <div class="col-md-2 col-6">
            <a href="<?php echo __BASE_URL__; ?>downloads" class="stat-card-link">
                <div class="stat-card clickable">
                    <i class="fas fa-download stat-icon"></i>
                    <span>Season</span>
                    <strong><?php echo custom_config('server_version'); ?></strong>
                </div>
            </a>
        </div>
    </div>

    <div class="hero-actions mt-5">
        <a href="<?php echo __BASE_URL__; ?>downloads" class="btn btn-download btn-lg">DESCARGAR CLIENTE</a>
        <a href="<?php echo custom_config('social_discord'); ?>" class="btn btn-discord btn-lg"><i class="fab fa-discord"></i> ÚNETE A DISCORD</a>
    </div>
    
    <div class="scroll-container">
        <span class="scroll-text">SCROLL</span>
        <div class="mouse-icon">
            <div class="wheel"></div>
        </div>
    </div>
</div>


</header>
    <?php else: ?>
        <div style="margin-top: 120px;"></div>
    <?php endif; ?>
    
    <main class="container my-5 content-section">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="content-wrapper-clean"> 
                    <?php 
                        if(!empty($_REQUEST['page'])) {
                            // Carga automática del sistema WebEngine para login, register, etc.
                            $handler->loadModule($_REQUEST['page'], $_REQUEST['subpage']);
                        } else {
                            $newsFile = __PATH_INCLUDES__ . 'config/server_news.json';
                            $settingsFile = __PATH_INCLUDES__ . 'config/custom_settings.json';
                            $newsData = file_exists($newsFile) ? json_decode(file_get_contents($newsFile), true) : [];
                            $conf = file_exists($settingsFile) ? json_decode(file_get_contents($settingsFile), true) : [];
                            $layoutType = $conf['news_layout_type'] ?? 'cascada';

                            echo '<div class="news-header-container">
                                <h2 class="news-header-title">Últimas Noticias</h2>
                                <a href="'.__BASE_URL__.'news" class="news-header-button">
                                    <span>Ver todas las noticias</span>
                                    <i class="fa fa-chevron-right"></i>
                                </a>
                            </div>';                                
                            // Definir rutas manuales si las constantes fallan en el index
                            $newsFilePath = 'includes/config/server_news.json';
                            $settingsFilePath = 'includes/config/custom_settings.json';
                            
                            $newsData = file_exists($newsFilePath) ? json_decode(file_get_contents($newsFilePath), true) : [];
                            $conf = file_exists($settingsFilePath) ? json_decode(file_get_contents($settingsFilePath), true) : [];
                            $layoutType = $conf['news_layout_type'] ?? 'cascada';
                        
                            echo '<div class="news-container">';
                            if(empty($newsData)) {
                                echo '<p class="text-center" style="color: #64748b;">No hay noticias publicadas.</p>';
                            } else {
                                // Ordenar por fecha (más reciente arriba)
                                usort($newsData, function($a, $b) {
                                    return strtotime($b['date']) - strtotime($a['date']);
                                });
                        
                                if($layoutType === 'cascada') {
                                    // Estilo CASCADA
                                    foreach($newsData as $news) {
                                        $newsUrl = __BASE_URL__ . 'news/' . ($news['slug'] ?? $news['id']) . '/';
                                        echo '<a href="'.$newsUrl.'" style="text-decoration:none; display:block; margin-bottom: 12px;">';
                                            echo '<div class="news-item" style="padding: 18px 25px; display:flex; justify-content:space-between; align-items:center; background: rgba(0,0,0,0.5); border: 1px solid rgba(255,255,255,0.05); border-radius: 8px;">';
                                                echo '<h2 class="news-title-text" style="font-size: 16px; margin:0; color:#FF8A3B; font-family: Cinzel, serif;">'.htmlspecialchars($news['title']).'</h2>';
                                                echo '<span style="font-size: 12px; color: #818cf8; font-family: sans-serif; font-weight: 500;">'.$news['date'].'</span>';
                                            echo '</div>';
                                        echo '</a>';
                                    }
                                } elseif($layoutType === 'expandida') { 
                                    /* */
                                echo '<div class="news-grid-custom">';
                                    $displayNews = array_slice($newsData, 0, 3);
                                    foreach($displayNews as $index => $news) {
                                        $newsUrl = __BASE_URL__ . 'news/' . ($news['slug'] ?? $news['id']) . '/';
                                        $img = !empty($news['image']) ? __BASE_URL__ . $news['image'] : 'https://placehold.co/600x400/1e293b/FFF?text=MU';
                                        $short_desc = mb_substr(strip_tags($news['content']), 0, 120) . '...';
                                        
                                        // Asignamos una clase única a cada tarjeta según su posición
                                        $cardClass = ($index === 0) ? 'card-big' : 'card-small';
                                        
                                        echo '<a class="news-card '.$cardClass.'" href="'.$newsUrl.'">
                                                <img src="'.$img.'" class="bg-img">
                                                <div class="overlay-dark"></div>
                                                <div class="card-content">
                                                    '.($index === 0 ? '<div class="badge-destacado"><span>Destacado</span></div>' : '').'
                                                    <h3 class="card-title">'.htmlspecialchars($news['title']).'</h3>
                                                    <div class="card-date">'.date("d M Y", strtotime($news['date'])).'</div>
                                                    '.($index === 0 ? '<p class="card-desc">'.$short_desc.'</p>' : '').'
                                                    <div class="card-link">Leer más <i class="fa fa-chevron-right"></i></div>
                                                </div>
                                                </a>';
                                    }
                                echo '</div>';
                                } else {
                                    // Formato FULL (el estándar de WebEngine)
                                    foreach($newsData as $news) {
                                        $img = !empty($news['image']) ? __BASE_URL__ . $news['image'] : '';
                                        echo '<div class="news-item" style="margin-bottom:30px; background: rgba(0,0,0,0.3); padding: 20px; border-radius: 8px;">';
                                            echo '<h2 class="news-title-text" style="color:#FF8A3B; font-family: Cinzel, serif; margin-bottom: 15px;">'.htmlspecialchars($news['title']).'</h2>';
                                            if($img) echo '<img src="'.$img.'" style="width:100%; border-radius: 8px; margin-bottom: 15px;">';
                                            echo '<div class="news-body-text" style="color:#e6d6c6;">'.$news['content'].'</div>';
                                            echo '<div style="margin-top:15px; font-size:12px; color:#818cf8;">'.$news['date'].'</div>';
                                        echo '</div>';
                                    }
                                }
                            }
                            echo '</div>';
                        }
                    ?>
                </div>
            </div>
        </div>
    </main>

    <?php if(empty($_REQUEST['page'])): ?>
    <?php
// Consultamos al Top 1 (Personaje con más Resets y luego más Level)
$db = Connection::Database('MuOnline');
$topPlayer = $db->query_fetch_single("SELECT TOP 1 Name, Class, cLevel, ResetCount FROM Character ORDER BY ResetCount DESC, cLevel DESC");

// Consultamos la Guild del Top 1 (opcional)
$topGuild = "Sin Guild";
if($topPlayer) {
    $guildMember = $db->query_fetch_single("SELECT G_Name FROM GuildMember WHERE Name = ?", array($topPlayer['Name']));
    if($guildMember) {
        $topGuild = $guildMember['G_Name'];
    }
}

// Lógica de Imagen de Clase (la misma que usamos en UserCP)
$topAvatar = "default.jpg";
if($topPlayer) {
    if ($topPlayer['Class'] < 16) { $topAvatar = "dw.jpg"; }
    elseif ($topPlayer['Class'] < 32) { $topAvatar = "dk.jpg"; }
    elseif ($topPlayer['Class'] < 48) { $topAvatar = "elf.jpg"; }
    elseif ($topPlayer['Class'] < 64) { $topAvatar = "mg.jpg"; }
    elseif ($topPlayer['Class'] < 80) { $topAvatar = "dl.jpg"; }
    elseif ($topPlayer['Class'] < 96) { $topAvatar = "su.jpg"; }
    elseif ($topPlayer['Class'] < 112) { $topAvatar = "rf.jpg"; }
}
?>

<section class="rank-highlight py-5 bg-dark-opacity">
    <div class="container text-center">
        <h2 class="section-title mb-5">LOS MEJORES EN <?php echo strtoupper(custom_config('server_name')); ?></h2>
        <div class="row g-4 justify-content-center">
            
            <?php
// --- FUNCIÓN DE FILTRADO DE CLASES POR VERSIÓN ---
if (!function_exists('is_class_allowed')) {
    function is_class_allowed($classCode) {
        // Obtenemos la versión desde tu configuración
        $ver = strtolower(custom_config('server_version')); 
        $cc = (int)$classCode; 
        
        if (empty($ver)) $ver = "97"; 

        // CASO 0.97 / 99b
        if (strpos($ver, '97') !== false || strpos($ver, '99') !== false) {
            // DW(0), DK(16), ELF(32). Si tu server tiene MG en 97, cambiá 48 por 64.
            return ($cc < 48); 
        }

        // SEASON 1, 2
        if (strpos($ver, 'season 1') !== false || strpos($ver, 'season 2') !== false) {
            return ($cc < 80); 
        }

        // SEASON 3, 4, 5
        if (strpos($ver, 'season 3') !== false || strpos($ver, 'season 4') !== false || strpos($ver, 'season 5') !== false) {
            return ($cc < 96); 
        }

        // SEASON 6+ (Muestra todo)
        return true; 
    }
}

// --- LOGICA DEL TOP 1 (Con Filtro Aplicado) ---
$db = Connection::Database('MuOnline');
// Traemos el TOP 5 para filtrar por si el primero es una clase inválida (ej: admin con RF en versión 97)
$candidates = $db->query_fetch("SELECT TOP 5 Name, Class, cLevel, ResetCount FROM Character ORDER BY ResetCount DESC, cLevel DESC");

$topPlayer = null;
if ($candidates && is_array($candidates)) {
    foreach ($candidates as $candidate) {
        // Aplicamos el filtro que pediste
        if (is_class_allowed($candidate['Class'])) {
            $topPlayer = $candidate;
            break; // Encontramos al mejor posicionado válido, cortamos el bucle.
        }
    }
}

// Consultamos la Guild del Top 1 seleccionado
$topGuild = "Sin Guild";
$topAvatar = "default.jpg";

if($topPlayer) {
    $guildMember = $db->query_fetch_single("SELECT G_Name FROM GuildMember WHERE Name = ?", array($topPlayer['Name']));
    if($guildMember) {
        $topGuild = $guildMember['G_Name'];
    }

    // Lógica de Imagen de Clase
    if ($topPlayer['Class'] < 16) { $topAvatar = "dw.jpg"; }
    elseif ($topPlayer['Class'] < 32) { $topAvatar = "dk.jpg"; }
    elseif ($topPlayer['Class'] < 48) { $topAvatar = "elf.jpg"; }
    elseif ($topPlayer['Class'] < 64) { $topAvatar = "mg.jpg"; }
    elseif ($topPlayer['Class'] < 80) { $topAvatar = "dl.jpg"; }
    elseif ($topPlayer['Class'] < 96) { $topAvatar = "su.jpg"; }
    elseif ($topPlayer['Class'] < 112) { $topAvatar = "rf.jpg"; }
}
?>

            <div class="col-lg-4 col-md-6">
                <div class="rank-card gold-glow">
                    <div class="card-header-icon"><i class="fa fa-trophy"></i> TOP 1 RANKING</div>
                    <div class="rank-body">
                        <div class="player-info d-flex align-items-center justify-content-center mb-3">
                            <img src="<?php echo __PATH_TEMPLATE_IMG__; ?>character-avatars/<?php echo $topAvatar; ?>" class="rank-avatar me-3" onerror="this.src='<?php echo __PATH_TEMPLATE_IMG__; ?>character-avatars/default.jpg';">
                            <div class="text-start">
                                <span class="player-name text-uppercase fw-bold" style="color: #FF8A3B; font-family: 'Trajan Pro', serif;">
                                    <?php echo $topPlayer ? $topPlayer['Name'] : 'Nadie aún'; ?>
                                </span><br>
                                <span class="guild-name text-color small"><?php echo $topGuild; ?></span>
                            </div>
                        </div>
                        <div class="row rank-stats">
                            <div class="col-6">
                                <small class="text-color text-uppercase" style="font-size: 10px;">Resets</small><br>
                                <strong style="font-family: 'Roboto';"><?php echo $topPlayer ? number_format($topPlayer['ResetCount']) : '0'; ?></strong>
                            </div>
                            <div class="col-6">
                                <small class="text-color text-uppercase" style="font-size: 10px;">Level</small><br>
                                <strong style="font-family: 'Roboto';"><?php echo $topPlayer ? $topPlayer['cLevel'] : '0'; ?></strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4 col-md-6">
                <div class="rank-card orange-glow">
                    <div class="card-header-icon"><i class="fa fa-shield-halved"></i> CASTLE SIEGE</div>
                    <div class="rank-body text-center d-flex flex-column justify-content-center" style="min-height: 115px;">
                        <i class="fa fa-chess-rook fa-2x mb-2 text-orange"></i>
                        <h4 class="guild-owner-name text-white text-uppercase" style="font-family: 'Trajan Pro'; letter-spacing: 1px;">
                            <?php 
                                $csOwner = $db->query_fetch_single("SELECT OWNER_GUILD FROM MuCastle_DATA");
                                echo ($csOwner && $csOwner['OWNER_GUILD'] != "") ? $csOwner['OWNER_GUILD'] : 'VACANTE'; 
                            ?>
                        </h4>
                        <p class="text-color small m-0">Dueños del Castillo</p>
                    </div>
                </div>
            </div>

        </div>
    </div>
</section>

<?php if ($bmActive): ?>
<section class="market-section py-5" style="border-top: 1px solid rgba(255,255,255,0.05); background: rgba(0,0,0,0.2);">
    <div class="container text-center">
        <h2 class="section-subtitle" style="color: #FF8A3B; font-family: 'Cinzel', serif; letter-spacing: 2px;">MARKETPLACE</h2>
        <div class="title-divider mb-4" style="width: 50px; height: 3px; background: #FF8A3B; margin: 10px auto;"></div>
        
        <div class="market-ticker-wrap">
            <div class="market-ticker">
                <?php 
                $mk_db = Connection::Database('Me_MuOnline');
                // Use try-catch to avoid breaking page if table missing
                try {
                    // FIX: Convert varbinary column to varchar for proper PHP reading
                    $carousel_items = $mk_db->query_fetch("SELECT TOP 10 id, item_hex, item_img, price, currency, date_posted FROM MUSERVERMAKER_MARKET_SELL ORDER BY date_posted DESC");
                    
                    if (empty($carousel_items)) {
                         echo '<div class="text-center p-5 text-muted" style="width: 100%;">Ningún usuario está vendiendo sus items en este momento</div>';
                    } else {
                        if(count($carousel_items) > 0 && count($carousel_items) < 10) {
                            $carousel_items = array_merge($carousel_items, $carousel_items);
                        }

                        foreach($carousel_items as $cItem) {
                            // Convert binary to hex string if needed
                            $rawHex = $cItem['item_hex'];
                            if (!ctype_xdigit($rawHex)) {
                                $rawHex = bin2hex($rawHex);
                            }
                            
                            $cHtml = bm_parse_item_index($rawHex);
                            $cName = explode('+', strip_tags($cHtml))[0];
                            $safeTooltip = htmlspecialchars($cHtml, ENT_QUOTES, 'UTF-8');
                            $priceStr = number_format($cItem['price']).' '.$cItem['currency'];
                            
                            $itemId = $cItem['id'];
                            
                            echo '<div class="m-card-mini" 
                                       data-tooltip="'.$safeTooltip.'"
                                       onmouseenter="idx_show_tt(this)" 
                                       onmousemove="idx_move_tt(event)" 
                                       onmouseleave="idx_hide_tt()">
                                    <img src="'.__BASE_URL__.$cItem['item_img'].'">
                                    <div class="m-mini-info">
                                        <div class="m-mini-name">'.$cName.'</div>
                                        <div class="m-mini-price">'.$priceStr.'</div>
                                        <button class="m-btn-buy" onclick="openDirectBuy('.$itemId.', \''.$priceStr.'\')">COMPRAR</button>
                                    </div>
                                  </div>';
                        }
                    }
                } catch (Exception $e) {
                     echo '<div class="text-center p-5 text-muted">Ningún usuario está vendiendo sus items en este momento</div>';
                }
                ?>
            </div>
        </div>

        <div class="mt-4">
            <button onclick="openMarketModal()" class="btn btn-outline-light btn-sm" style="font-size: 11px; letter-spacing: 1px;">
                VER TODO EL MERCADO <i class="fa fa-arrow-right ms-1"></i>
            </button>
        </div>
    </div>
</section>
<?php endif; ?>

<div id="global-tt"></div>

<div id="wsMarketModal" class="ws-modal-overlay">
    <button class="btn-ws-close" onclick="closeMarketModal()" style="position:absolute; top:20px; right:30px; color:#fff; z-index:10001;"><i class="fa fa-times"></i></button>
    
    <div id="view_individual" class="ws-workspace">
        <div class="ws-sidebar-left">
            <div class="cat-header">Categorías</div>
            <div class="cat-list">
                <?php 
                $cats = [
                    -1=>['Ver Todos', 'fa-globe'],
                    0=>['Espadas', 'fa-khanda'], 1=>['Hachas', 'fa-gavel'], 2=>['Mazas', 'fa-hammer'], 
                    3=>['Lanzas', 'fa-pen-nib'], 4=>['Arcos', 'fa-bullseye'], 5=>['Bastones', 'fa-magic'], 
                    6=>['Escudos', 'fa-shield-alt'], 7=>['Cascos', 'fa-hard-hat'], 8=>['Armaduras', 'fa-tshirt'], 
                    9=>['Pantalones', 'fa-socks'], 10=>['Guantes', 'fa-mitten'], 11=>['Botas', 'fa-shoe-prints'], 
                    12=>['Alas/Orb', 'fa-feather'], 13=>['Joyas', 'fa-gem'], 14=>['Misc', 'fa-box-open'], 15=>['Scrolls', 'fa-scroll']
                ];
                foreach($cats as $id => $data) {
                    echo '<button class="cat-btn" onclick="wsLoadCat('.$id.', this)"><i class="fa-solid '.$data[1].'"></i> '.$data[0].'</button>';
                }
                ?>
            </div>
        </div>

        <div class="ws-center-panel">
            <div class="center-toolbar">
                <div class="search-wrapper">
                    <i class="fa fa-search search-icon"></i>
                    <input type="text" id="wsSearch" class="search-input" placeholder="Filtrar..." onkeyup="wsFilterGrid()">
                </div>
            </div>
            
            <div class="item-grid-container" id="wsItemGrid">
                <div style="grid-column: 1 / -1; text-align: center; margin-top: 150px; color: #444;">
                    <i class="fa fa-arrow-left" style="font-size: 40px; margin-bottom: 15px; opacity: 0.5;"></i><br>
                    <p style="font-size: 14px;">Selecciona una categoría.</p>
                </div>
            </div>
            
            <div class="loading-overlay" id="wsLoader">
                <div class="spinner"></div><span style="color:#fff; font-size:12px; font-weight:600;">CARGANDO...</span>
            </div>
        </div>
    </div>
</div>

<div id="alertModal" class="alert-modal-overlay">
    <div class="alert-box">
        <h3 id="alertTitle" style="color:#FF8A3B; margin-bottom:10px;">Aviso</h3>
        <p id="alertBody" style="font-size:13px; color:#ccc;">Mensaje</p>
        <button class="alert-btn" onclick="document.getElementById('alertModal').style.display='none'">Aceptar</button>
    </div>
</div>

<div id="buyConfirmModal" class="confirm-modal-overlay">
    <div class="confirm-box">
        <div class="confirm-title">Confirmar Compra</div>
        <div class="confirm-body">
            ¿Comprar este item por <span id="buyPriceLabel" style="color:#ffd700; font-weight:bold;"></span>?
            <br><br>
            <small style="color:#e74c3c;">Tu baúl debe estar <b>VACÍO</b>.</small>
        </div>
        <div class="confirm-btns">
            <button class="btn-confirm-no" onclick="document.getElementById('buyConfirmModal').style.display='none'">Cancelar</button>
            <button class="btn-confirm-yes" id="btnDoBuy">COMPRAR</button>
        </div>
    </div>
</div>

<script>
    var bm_selected_id = -1;

    // --- TOOLTIP LOGIC ---
    function idx_show_tt(el) {
        var tt = document.getElementById('global-tt');
        tt.innerHTML = el.getAttribute('data-tooltip');
        tt.style.display = 'block';
    }
    function idx_move_tt(e) {
        var tt = document.getElementById('global-tt');
        tt.style.left = (e.clientX + 15) + 'px';
        tt.style.top = (e.clientY + 15) + 'px';
    }
    function idx_hide_tt() {
        document.getElementById('global-tt').style.display = 'none';
    }

    // --- MODAL WORKSPACE ---
    function openMarketModal() { document.getElementById('wsMarketModal').style.display = 'flex'; }
    function closeMarketModal() { document.getElementById('wsMarketModal').style.display = 'none'; }

    // --- ALERT SYSTEM ---
    function show_modal_alert(title, msg) {
        document.getElementById('alertTitle').innerText = title;
        document.getElementById('alertBody').innerHTML = msg;
        document.getElementById('alertModal').style.display = 'flex';
    }

    // --- BUY SYSTEM ---
    function openDirectBuy(id, priceLabel) {
        // Evita que el click se propague si viene del card wrapper
        if(window.event) window.event.stopPropagation();
        
        bm_selected_id = id;
        document.getElementById('buyPriceLabel').innerText = priceLabel;
        document.getElementById('btnDoBuy').onclick = function() { executeBuy(); };
        document.getElementById('buyConfirmModal').style.display = 'flex';
    }

    function executeBuy() {
        document.getElementById('buyConfirmModal').style.display = 'none';
        
        var fd = new FormData();
        fd.append('market_ajax', 'true');
        fd.append('action', 'buy_item');
        fd.append('id', bm_selected_id);

        fetch(window.location.href, { method: 'POST', body: fd })
        .then(r => r.json())
        .then(res => {
            if(res.status === 'success') {
                show_modal_alert("Éxito", "¡Compra realizada! Revisa tu baúl.");
                // Recargar si el modal grande está abierto
                var activeCat = document.querySelector('.cat-btn.active');
                if(activeCat) activeCat.click();
                // Opcional: Recargar página para actualizar carrusel
                setTimeout(function(){ location.reload(); }, 2000);
            } else {
                show_modal_alert("Error", res.message);
            }
        });
    }

    // --- CATEGORY LOADER (MODAL GRANDE) ---
    function wsLoadCat(catId, btn) {
        document.querySelectorAll('.cat-btn').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        
        document.getElementById('wsLoader').style.display = 'flex';
        var grid = document.getElementById('wsItemGrid');
        grid.innerHTML = '';

        var fd = new FormData();
        fd.append('market_ajax', 'true');
        fd.append('action', 'load_category');
        fd.append('category', catId);

        fetch(window.location.href, { method: 'POST', body: fd })
        .then(r => r.json())
        .then(res => {
            document.getElementById('wsLoader').style.display = 'none';
            if(res.status === 'success') {
                if(res.data.length === 0) {
                    grid.innerHTML = '<div style="grid-column: 1/-1; text-align:center; color:#666; margin-top:50px;">Categoría vacía.</div>';
                    return;
                }
                res.data.forEach(item => {
                    // Item Card HTML
                    var div = document.createElement('div');
                    div.className = 'ws-item-card';
                    div.setAttribute('data-name', item.clean_name.toLowerCase());
                    
                    // Tooltip data
                    var safeTt = item.tooltip.replace(/"/g, '&quot;');
                    
                    div.innerHTML = `
                        <div onmouseenter="idx_show_tt(this)" onmousemove="idx_move_tt(event)" onmouseleave="idx_hide_tt()" data-tooltip="${safeTt}">
                            <img src="${item.img}" class="ws-card-img">
                        </div>
                        <div class="ws-card-name">${item.clean_name}</div>
                        <div class="ws-card-price">${new Intl.NumberFormat().format(item.price)} ${item.currency}</div>
                        <button class="ws-card-btn" onclick="openDirectBuy(${item.id}, '${new Intl.NumberFormat().format(item.price)} ${item.currency}')">COMPRAR</button>
                    `;
                    grid.appendChild(div);
                });
            }
        });
    }

    function wsFilterGrid() {
        var term = document.getElementById('wsSearch').value.toLowerCase();
        document.querySelectorAll('.ws-item-card').forEach(slot => {
            var name = slot.getAttribute('data-name');
            slot.style.display = name.includes(term) ? 'flex' : 'none';
        });
    }
</script>

<section class="features-section py-5">
        <div class="container text-center">
            <h2 class="section-subtitle">¿POR QUÉ ELEGIRNOS?</h2>
            <div class="title-divider mb-5"></div>
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="feature-box">
                        <div class="feature-icon-circle bg-orange-grad"><i class="fa fa-bolt"></i></div>
                        <h4 class="feature-title">CLIENTE OPTIMIZADO</h4>
                        <p class="feature-text">60 FPS reales con fluidez única y exclusiva.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-box">
                        <div class="feature-icon-circle bg-orange-grad"><i class="fa fa-bullseye"></i></div>
                        <h4 class="feature-title">EVENTOS EXCLUSIVOS</h4>
                        <p class="feature-text">Vive eventos únicos como Battlegrounds y Dungeons.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-box">
                        <div class="feature-icon-circle bg-orange-grad"><i class="fa fa-users"></i></div>
                        <h4 class="feature-title">COMUNIDAD ACTIVA</h4>
                        <p class="feature-text">Actividad constante las 24/7 con miles de usuarios.</p>
                    </div>
                </div>
            </div>
            <div class="mt-5">
                <?php if(!isLoggedIn()): ?>
                    <a href="<?php echo __BASE_URL__; ?>register" class="btn btn-register-now">REGÍSTRATE AHORA <i class="fa fa-arrow-right ms-2"></i></a>
                <?php endif; ?>
            </div>
        </div>
    </section>
    
<?php endif; ?>
<?php include(__PATH_TEMPLATE_ROOT__ . 'inc/modules/footer.php'); ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<?php include_once($_SERVER['DOCUMENT_ROOT'] . '/templates/misc/sidebar.php'); ?>

<?php

// LEER CONFIGURACIÓN DE STREAMS
$streamConfFile = 'includes/config/stream_config.json';
$stData = [];
$stActive = false;

if (file_exists($streamConfFile)) {
    $stData = json_decode(file_get_contents($streamConfFile), true);
    if (isset($stData['module_active']) && $stData['module_active'] == 'on') {
        $stActive = true;
    }
}

if ($stActive): 
?>
    <div id="streamWidgetRoot">
        
        <div id="streamsPanel" class="stream-panel-container">
            <div class="stream-panel-header">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="15" x="2" y="7" rx="2" ry="2"></rect><polyline points="17 2 12 7 7 2"></polyline></svg>
                Nuestros Streamers
            </div>
            
            <div class="stream-list">
                <?php if (!empty($stData['streamers'])): ?>
                    <?php foreach ($stData['streamers'] as $st): ?>
                        <div class="stream-item">
                            <div class="stream-user-label">
                                <span><?php echo htmlspecialchars($st['user']); ?></span>
                                <span style="text-transform: uppercase; font-size:10px; opacity:0.7;"><?php echo $st['platform']; ?></span>
                            </div>
                            <div class="stream-embed-responsive">
                                <?php 
                                    $domain = $_SERVER['HTTP_HOST'];
                                    // Removemos 'www.' si existe para evitar problemas con Twitch
                                    $domain = preg_replace('/^www\./', '', $domain);

                                    if ($st['platform'] == 'twitch') {
                                        echo '<iframe src="https://player.twitch.tv/?channel='.htmlspecialchars($st['user']).'&parent='.$domain.'&muted=true" allowfullscreen></iframe>';
                                    } elseif ($st['platform'] == 'kick') {
                                        echo '<iframe src="https://player.kick.com/'.htmlspecialchars($st['user']).'?muted=true" allowfullscreen></iframe>';
                                    }
                                ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div style="padding: 20px; text-align: center; color: #7e8299; font-size: 0.9rem;">
                        No hay streams configurados.
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <button id="btnToggleStreams" class="stream-float-btn" onclick="toggleStreamPanel()">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="15" x="2" y="7" rx="2" ry="2"></rect><polyline points="17 2 12 7 7 2"></polyline></svg>
            <span>Streams</span>
            <svg id="arrowIcon" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="transition: transform 0.3s;"><path d="m18 15-6-6-6 6"></path></svg>
        </button>
    </div>

    <script>
    function toggleStreamPanel() {
        var panel = document.getElementById('streamsPanel');
        var arrow = document.getElementById('arrowIcon');
        
        if (panel.style.display === 'flex') {
            panel.style.display = 'none';
            arrow.style.transform = 'rotate(0deg)';
        } else {
            panel.style.display = 'flex';
            arrow.style.transform = 'rotate(180deg)';
        }
    }
    
    document.addEventListener("DOMContentLoaded", function() {
        setTimeout(function() {
            // PHP inyecta la URL base y la Key automáticamente aquí:
            var cronUrl = "<?php echo __BASE_URL__; ?>api/cron.php?key=<?php echo isset($muservermakerParams['cron_api_key']) ? $muservermakerParams['cron_api_key'] : '123456'; ?>";
            
            // Ejecutamos el cron silenciosamente
            try {
                fetch(cronUrl, {mode: 'no-cors'});
                console.log("Auto-Cron ejecutado en: " + cronUrl);
            } catch(e) {
                console.error("Error auto-cron", e);
            }
        }, 3000);
    });
</script>
<?php endif; ?>
<?php include('includes/popup_modal.php'); ?>
<?php include 'templates/misc/countdown.php'; ?>
</body>
</html>