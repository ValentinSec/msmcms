<?php
/**
 * Template: Classic (MASTER VERSION - FINAL FIX)
 * Fixes: Filtro de Razas (0.97/99b), Seguridad Visual, Velocidad.
 */

// 1. Buffer para limpieza de seguridad
ob_start();

$request_path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$path_parts = explode('/', trim($request_path, '/'));

if (isset($path_parts[0]) && !empty($path_parts[0])) $_REQUEST['page'] = $path_parts[0];
if (isset($path_parts[1]) && !empty($path_parts[1])) $_REQUEST['subpage'] = $path_parts[1];

if(!defined('access') or !access) die();
include('inc/template.functions.php');

if(isset($_REQUEST['page']) && $_REQUEST['page'] == 'usercp') {
    define('USERCP_CLEAN', true);
    include('modules/usercp.php');
    return;
}                                                            

// --- CONFIGURACIÓN BLACK MARKET ---
$bmConfigFile = __PATH_INCLUDES__ . 'config/black_market_config.json';
$bmActive = false;
if (file_exists($bmConfigFile)) {
    $bmConf = json_decode(file_get_contents($bmConfigFile), true);
    if (isset($bmConf['module_active']) && $bmConf['module_active'] == 'on') $bmActive = true;
}

// --- PARSER DE ITEMS COMPLETO (CON SOCKETS) ---
include_once('includes/config/items_manager.php'); 
if(!function_exists('bm_parse_item_index')) {
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
            // Consultamos los nombres reales de la opción 380
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

// ====================================================================================
// MÓDULO DE SEGURIDAD VISUAL (IP BLOCK & DDOS)
// ====================================================================================
$secConfigFile = __PATH_INCLUDES__ . 'config/security_config.json';
if (file_exists($secConfigFile)) {
    $secConfig = json_decode(file_get_contents($secConfigFile), true);
    $userIp = $_SERVER['REMOTE_ADDR'];
    
    // Función visual blindada
    if (!function_exists('show_access_denied')) {
        function show_access_denied($title, $msg, $ip) {
            while (ob_get_level() > 0) { @ob_end_clean(); }
            ?>
            <!DOCTYPE html>
            <html lang="es">
            <head>
                <meta charset="UTF-8">
                <title>Acceso Restringido</title>
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
                <style>
                    * { box-sizing: border-box; margin: 0; padding: 0; }
                    body, html { width: 100%; height: 100%; background-color: #050505 !important; font-family: 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; overflow: hidden; }
                    .security-overlay { position: fixed; top: 0; left: 0; right: 0; bottom: 0; width: 100vw; height: 100vh; background: radial-gradient(circle at center, #1a0a0a 0%, #000000 100%); z-index: 2147483647; display: flex; align-items: center; justify-content: center; padding: 20px; }
                    .security-card { background: rgba(20, 20, 20, 0.95); border: 1px solid #d32f2f; border-top: 4px solid #d32f2f; box-shadow: 0 0 50px rgba(211, 47, 47, 0.2); padding: 40px; border-radius: 8px; text-align: center; max-width: 480px; width: 100%; color: #fff; animation: fadeIn 0.5s ease-out; }
                    .sec-icon { font-size: 50px; color: #d32f2f; margin-bottom: 20px; display: inline-block; animation: pulse 2s infinite; }
                    .sec-title { font-size: 24px; font-weight: 700; text-transform: uppercase; margin-bottom: 15px; letter-spacing: 1px; }
                    .sec-msg { font-size: 14px; color: #aaa; line-height: 1.5; margin-bottom: 25px; }
                    .sec-ip { background: rgba(0, 0, 0, 0.3); border: 1px solid #333; padding: 8px 15px; border-radius: 4px; font-family: monospace; color: #d32f2f; font-size: 13px; display: inline-block; margin-bottom: 20px; }
                    .sec-btn { display: block; width: 100%; padding: 12px; background: #1a1a1a; border: 1px solid #444; color: #ccc; text-decoration: none; font-size: 13px; border-radius: 4px; transition: 0.3s; cursor: pointer; }
                    .sec-btn:hover { background: #d32f2f; border-color: #d32f2f; color: #fff; }
                    @keyframes pulse { 0% { transform: scale(1); opacity: 1; } 50% { transform: scale(1.1); opacity: 0.8; } 100% { transform: scale(1); opacity: 1; } }
                    @keyframes fadeIn { from { opacity: 0; transform: translateY(-20px); } to { opacity: 1; transform: translateY(0); } }
                </style>
            </head>
            <body>
                <div class="security-overlay">
                    <div class="security-card">
                        <div class="sec-icon"><i class="fa fa-ban"></i></div>
                        <h1 class="sec-title"><?php echo $title; ?></h1>
                        <p class="sec-msg"><?php echo $msg; ?></p>
                        <div class="sec-ip">IP: <?php echo $ip; ?></div>
                        <a href="javascript:location.reload()" class="sec-btn"><i class="fa fa-rotate-right"></i> REINTENTAR</a>
                    </div>
                </div>
            </body>
            </html>
            <?php
            flush(); exit(); 
        }
    }
    
    if (!empty($secConfig['blocked_ips'])) {
        $blockedList = explode("\n", str_replace("\r", "", $secConfig['blocked_ips']));
        if (in_array(trim($userIp), array_map('trim', $blockedList))) {
            show_access_denied("ACCESO RESTRINGIDO", "Tu dirección IP ha sido bloqueada permanentemente.", $userIp);
        }
    }
    
    if (isset($secConfig['ddos_active']) && $secConfig['ddos_active'] == 1) {
        if (!session_id()) @session_start();
        $limit = $secConfig['ddos_limit'] ?? 10; 
        $now = microtime(true);
        if (isset($_SESSION['ddos_penalty_until']) && $now < $_SESSION['ddos_penalty_until']) {
            $wait = ceil($_SESSION['ddos_penalty_until'] - $now);
            show_access_denied("TRÁFICO ALTO", "Hemos detectado demasiadas solicitudes. Por favor espera $wait segundos.", $userIp);
        }
        if (!isset($_SESSION['last_request_micro'])) {
            $_SESSION['last_request_micro'] = $now; $_SESSION['req_count'] = 1;
        } else {
            $timeDiff = $now - $_SESSION['last_request_micro'];
            if ($timeDiff < 0.5) { 
                $_SESSION['req_count']++;
                if ($_SESSION['req_count'] > $limit) {
                    $_SESSION['ddos_penalty_until'] = $now + 10;
                    show_access_denied("SEGURIDAD ACTIVADA", "Sistema de protección anti-flood activado.", $userIp);
                }
            } else {
                $_SESSION['last_request_micro'] = $now; $_SESSION['req_count'] = 1;
            }
        }
    }
}

// Leemos la versión una sola vez y forzamos que sea String
$SERVER_VERSION_CACHE = (string)custom_config('server_version');

function is_class_allowed($classCode) {
    global $SERVER_VERSION_CACHE;
    $ver = strtolower($SERVER_VERSION_CACHE); // Convertimos a minúsculas para comparar
    $cc = (int)$classCode; // Forzamos a número entero
    
    // Si la config no se leyó, asumimos 0.97 por seguridad para ocultar RF/DL
    if (empty($ver)) $ver = "97"; 

    // CASO 0.97 / 99b (Detecta "0.97d", "97d", "version 97", etc)
    if (strpos($ver, '97') !== false || strpos($ver, '99') !== false) {
        // Si es 0.97, solo permite DW(0), DK(16), ELF(32). 
        // El MG es 48. < 48 oculta MG, DL, SUM, RF.
        // Si tu 0.97 tiene MG, cambiá 48 por 64.
        return ($cc < 48); 
    }

    // SEASON 1, 2 (Agrega MG y DL. Oculta SUM y RF)
    if (strpos($ver, 'season 1') !== false || strpos($ver, 'season 2') !== false) {
        return ($cc < 80); 
    }

    // SEASON 3, 4, 5 (Agrega SUM. Oculta RF)
    if (strpos($ver, 'season 3') !== false || strpos($ver, 'season 4') !== false || strpos($ver, 'season 5') !== false) {
        return ($cc < 96); 
    }

    // SEASON 6+ (Muestra todo)
    return true; 
}

$cacheFilePath = __PATH_INCLUDES__ . 'cache/server_info.cache';
$realOnline = 0;
if(file_exists($cacheFilePath)) {
    $rawCache = file_get_contents($cacheFilePath);
    $cacheData = json_decode($rawCache, true);
    if(isset($cacheData[1][0])) { $srvInfo = explode("|", $cacheData[1][0]); $realOnline = isset($srvInfo[3]) ? (int)$srvInfo[3] : 0; }
}
$multOnline = (float)custom_config('fake_online');
$displayOnline = round($realOnline * ($multOnline > 0 ? $multOnline : 1));


/* --- TEMPLATE CONFIG --- */
$tplConfigPath = 'templates/classic/config.json';
$tplConfig = file_exists($tplConfigPath) ? json_decode(file_get_contents($tplConfigPath), true) : [];
$heroImage = isset($tplConfig['hero_image']) && !empty($tplConfig['hero_image']) ? $tplConfig['hero_image'] : 'hero_default.png';
$bgImage = isset($tplConfig['background_image']) && !empty($tplConfig['background_image']) ? $tplConfig['background_image'] : '';


// --- AJAX MARKET HANDLER ---
if(isset($_POST['market_ajax']) && $_POST['market_ajax'] == 'true') {
    while (ob_get_level()) ob_end_clean();
    header('Content-Type: application/json');
    try {
        $bmAction = isset($_POST['action']) ? $_POST['action'] : '';

        if($bmAction == 'load_category') {
            $data = BlackMarket::getMarketItems(isset($_POST['category']) ? $_POST['category'] : -1);
            echo json_encode(['status' => 'success', 'data' => $data], JSON_INVALID_UTF8_SUBSTITUTE);
            exit;
        }

        if($bmAction == 'buy_item') {
            if(!isLoggedIn()) throw new Exception("Debes iniciar sesi\u{00F3}n.");
            $result = BlackMarket::buyItem($_SESSION['username'], isset($_POST['id']) ? $_POST['id'] : 0);
            echo json_encode($result, JSON_INVALID_UTF8_SUBSTITUTE);
            exit;
        }

        echo json_encode(['status' => 'error', 'message' => 'Accion no valida.']);
        exit;
    } catch (Exception $e) { echo json_encode(['status' => 'error', 'message' => $e->getMessage()], JSON_INVALID_UTF8_SUBSTITUTE); exit;
    } catch (Throwable $e) { error_log('[market_ajax] ' . $e->getMessage()); echo json_encode(['status' => 'error', 'message' => 'Error interno.']); exit; }
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
    
    <link href="<?php echo __BASE_URL__; ?>templates/misc/css/navbar.css?v=2" rel="stylesheet">
    <link href="<?php echo __BASE_URL__; ?>templates/misc/css/sidebar.css" rel="stylesheet">
    <?php
    if(!function_exists('msm_v')) {
        // Version de un archivo estatico = su fecha de modificacion.
        function msm_v($ruta) {
            $t = @filemtime(dirname(__DIR__, 2) . '/' . ltrim($ruta, '/'));
            return $t ? $t : 1;
        }
    }
    ?>
    <link href="<?php echo __BASE_URL__; ?>templates/classic/css/footer.css?v=<?php echo msm_v('templates/classic/css/footer.css'); ?>" rel="stylesheet">
    <link href="<?php echo __BASE_URL__; ?>templates/classic/css/login.css?v=<?php echo msm_v('templates/classic/css/login.css'); ?>" rel="stylesheet">
    <link href="<?php echo __BASE_URL__; ?>templates/classic/css/register.css?v=<?php echo msm_v('templates/classic/css/register.css'); ?>" rel="stylesheet">
    <link href="<?php echo __BASE_URL__; ?>templates/classic/css/main.css?v=<?php echo msm_v('templates/classic/css/main.css'); ?>" rel="stylesheet">
    <link href="<?php echo __BASE_URL__; ?>templates/classic/css/rankings.css?v=<?php echo msm_v('templates/classic/css/rankings.css'); ?>" rel="stylesheet">
    <link href="<?php echo __BASE_URL__; ?>templates/classic/css/news.css?v=<?php echo msm_v('templates/classic/css/news.css'); ?>" rel="stylesheet">
    <link href="<?php echo __BASE_URL__; ?>templates/classic/css/info.css?v=<?php echo msm_v('templates/classic/css/info.css'); ?>" rel="stylesheet">
    <link href="<?php echo __BASE_URL__; ?>templates/classic/css/guides.css?v=<?php echo msm_v('templates/classic/css/guides.css'); ?>" rel="stylesheet">
    <link href="<?php echo __BASE_URL__; ?>templates/classic/css/downloads.css?v=<?php echo msm_v('templates/classic/css/downloads.css'); ?>" rel="stylesheet">
    <link rel="icon" type="image/x-icon" href="<?php echo __BASE_URL__; ?>templates/classic/img/favicon.ico?v=<?php echo msm_v('templates/classic/img/favicon.ico'); ?>">    
    <?php if(!empty($bgImage)): ?>
    <style>
        body {
            background: url('<?php echo __BASE_URL__; ?>templates/classic/img/<?php echo $bgImage; ?>') no-repeat center center fixed !important;
            background-size: cover !important;
        }
    </style>
    <?php endif; ?>

</head>
<body class="<?php echo (isset($_REQUEST['page']) && $_REQUEST['page'] == 'usercp') ? 'usercp-body' : ''; ?>">

    <?php include $_SERVER['DOCUMENT_ROOT'] . '/templates/misc/inc/modules/navbar.php'; ?>

    <div class="main-content-classic">
        <?php if(empty($_REQUEST['page'])): ?>
            
            <div class="container hero-container">
                <div class="row align-items-center">
                    <div class="col-lg-5 col-md-12 content-col">
                        <div class="hero-header">
                            <h1 class="title-main">¡Bienvenido a <?php echo custom_config('server_name'); ?>!</h1>
                            <p class="description-text"><?php echo nl2br(custom_config('server_desc')); ?></p>
                        </div>

                        <div class="server-status-widget">
                            <h3 class="widget-title">Estado del servidor</h3>
                            <div class="status-indicator">
                                <span class="status-label <?php echo ($realOnline > 0) ? 'on' : 'off'; ?>">
                                    <?php echo ($realOnline > 0) ? 'Online' : 'Offline'; ?>
                                </span>
                            </div>
                            <div class="progress-track">
                                <div class="progress-fill" style="width: <?php echo min(($displayOnline/1000)*100, 100); ?>%;"></div>
                            </div>
                            <div class="online-count">Total Online: <?php echo $displayOnline; ?></div>
                        </div>

                        <div class="home-news-section mb-5">
                            <div class="d-flex align-items-center justify-content-center mb-3">
                                <i class="fa fa-book-open fa-lg me-2" style="color: #d32f2f;"></i>
                                <h4 class="text-dark text-uppercase fw-bold m-0" style="font-family: 'Cinzel', serif; letter-spacing: 1px;">Últimas Noticias</h4>
                            </div>
    
    <hr style="border-color: rgba(0,0,0,0.1); margin: 0 0 15px 0;">
    
    <?php
    // Leer el archivo de noticias
    $newsPath = $_SERVER['DOCUMENT_ROOT'] . '/includes/config/server_news.json';
    $newsList = file_exists($newsPath) ? json_decode(file_get_contents($newsPath), true) : [];
    
    if(!empty($newsList)):
        // Limitar a las últimas 3-4 noticias para no alargar el index
        $newsList = array_slice($newsList, 0, 4);
        foreach($newsList as $index => $news):
            $nTitle = isset($news['news_title']) ? $news['news_title'] : (isset($news['title']) ? $news['title'] : 'Noticia');
            $nDate = isset($news['news_date']) ? date('Y-m-d', $news['news_date']) : (isset($news['date']) ? $news['date'] : '');
            
            // MuServerMaker a veces guarda el texto en 'content' y otras en 'news_content'
            $nContent = isset($news['news_content']) ? $news['news_content'] : (isset($news['content']) ? $news['content'] : 'Contenido no disponible.');
            
            // Sanitizar para evitar que comillas rompan el Javascript
            $jsTitle = htmlspecialchars($nTitle, ENT_QUOTES, 'UTF-8');
            $jsDate = htmlspecialchars($nDate, ENT_QUOTES, 'UTF-8');
            $newsId = 'news_content_' . $index;
    ?>
    
    <div onclick="openNewsModal('<?php echo $jsTitle; ?>', '<?php echo $jsDate; ?>', '<?php echo $newsId; ?>')" class="d-flex justify-content-between align-items-center px-4 py-3 mb-2 hover-scale" style="background: #fff; border-radius: 6px; border: 1px solid #e0e0e0; box-shadow: 0 2px 8px rgba(0,0,0,0.03); transition: all 0.3s; cursor: pointer;" onmouseover="this.style.borderColor='#d32f2f'; this.style.transform='translateX(5px)';" onmouseout="this.style.borderColor='#e0e0e0'; this.style.transform='translateX(0)';">
        <div class="text-dark" style="font-size: 14px; font-weight: 500;">
            <i class="fa fa-bullhorn me-2" style="color: #d32f2f;"></i> 
            <?php echo htmlspecialchars($nTitle); ?>
        </div>
        <div class="text-muted" style="font-size: 13px; font-weight: 600;"><?php echo $nDate; ?></div>
    </div>
    
    <div id="<?php echo $newsId; ?>" style="display:none;"><?php echo $nContent; ?></div>
    
    <?php 
        endforeach;
    else: 
    ?>
    <div class="text-center py-4 text-muted" style="background: #fff; border: 1px dashed #ccc; border-radius: 6px; font-weight: 500;">
        No hay noticias recientes.
    </div>
    <?php endif; ?>
    
    <hr style="border-color: rgba(0,0,0,0.1); margin: 15px 0 0 0;">

<style>
    .news-modal-overlay { 
        display: none; position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; 
        background: rgba(0,0,0,0.95); z-index: 2147483647 !important; /* Capa máxima por encima de todo */
        align-items: center; justify-content: center; backdrop-filter: blur(5px); 
    }
    .news-modal-box { 
        background: #111418; border: 1px solid #c8a165; padding: 30px; 
        border-radius: 8px; width: 90%; max-width: 650px; max-height: 85vh; 
        color: #fff; box-shadow: 0 0 40px rgba(200, 161, 101, 0.15); 
        display: flex; flex-direction: column; position: relative; 
    }
    .news-modal-title { 
        color: #c8a165; font-family: 'Cinzel', serif; font-weight: bold; 
        border-bottom: 1px solid #2d323e; padding-bottom: 10px; margin-bottom: 5px; 
        font-size: 22px; padding-right: 30px; 
    }
    .news-modal-date { 
        font-size: 11px; color: #888; margin-bottom: 20px; font-family: 'Roboto', sans-serif; 
    }
    .news-modal-content { 
        font-size: 14px; color: #ccc; overflow-y: auto; padding-right: 15px; 
        line-height: 1.7; flex: 1; font-family: 'Roboto', sans-serif;
    }
    .news-modal-content img {
        max-width: 100%; height: auto; border-radius: 4px; margin: 10px 0;
    }
    .news-modal-content::-webkit-scrollbar { width: 5px; }
    .news-modal-content::-webkit-scrollbar-track { background: #0b0d10; border-radius: 4px; }
    .news-modal-content::-webkit-scrollbar-thumb { background: #2d323e; border-radius: 4px; }
    .news-modal-content::-webkit-scrollbar-thumb:hover { background: #c8a165; }
    
    .btn-news-close { 
        position: absolute; top: 25px; right: 25px; background: transparent; 
        border: none; color: #888; cursor: pointer; font-size: 22px; transition: 0.3s; 
    }
    .btn-news-close:hover { color: #fff; transform: rotate(90deg); }
</style>

<div id="readNewsModal" class="news-modal-overlay">
    <div class="news-modal-box">
        <button class="btn-news-close" onclick="closeNewsModal()"><i class="fa fa-times"></i></button>
        <div id="readNewsTitle" class="news-modal-title">Título de la Noticia</div>
        <div id="readNewsDate" class="news-modal-date"><i class="fa fa-calendar-alt me-1"></i> Fecha</div>
        <div id="readNewsContent" class="news-modal-content">
            Cargando contenido...
        </div>
    </div>
</div>

<script>
    function openNewsModal(title, date, contentId) {
        document.getElementById('readNewsTitle').innerText = title;
        document.getElementById('readNewsDate').innerHTML = '<i class="fa fa-calendar-alt me-1"></i> Publicado el ' + date;
        
        var contentHtml = document.getElementById(contentId).innerHTML;
        document.getElementById('readNewsContent').innerHTML = contentHtml;
        
        document.getElementById('readNewsModal').style.display = 'flex';
    }

    function closeNewsModal() {
        document.getElementById('readNewsModal').style.display = 'none';
        document.getElementById('readNewsContent').innerHTML = ''; 
    }
</script>
                        </div><!-- cierra home-news-section -->
                    </div><!-- cierra col-lg-5 content-col -->
                    <div class="col-lg-7 col-md-12 text-center">
					
                        <div class="hero-image-wrapper">
                            <img src="<?php echo __BASE_URL__; ?>templates/classic/img/<?php echo $heroImage; ?>" class="hero-img img-fluid" alt="Hero">
                        </div>
                    </div>
                </div>
            </div>

            <?php if ($bmActive): ?>
            <section class="market-section-classic">
                <div class="container text-center">
                    <h2 class="rank-main-title">MARKETPLACE</h2>
                    <p class="rank-subtitle">Comercia items con otros jugadores en tiempo real</p>
                    
                    <div class="market-ticker-wrap">
                        <div class="market-ticker-grid">
                            <?php 
                            $mk_db = Connection::Database('Me_MuOnline');
                            try {
                                $carousel_items = $mk_db->query_fetch("SELECT TOP 10 id, item_hex, item_img, price, currency, date_posted FROM MUSERVERMAKER_MARKET_SELL ORDER BY date_posted DESC");
                                
                                if (empty($carousel_items)) {
                                     echo '<div class="market-empty-state">No hay items en venta por el momento.</div>';
                                } else {
                                    foreach($carousel_items as $cItem) {
                                        $rawHex = $cItem['item_hex'];
                                        if (!ctype_xdigit($rawHex)) { $rawHex = bin2hex($rawHex); }
                                        
                                        // 1. TOOLTIP (Con opciones)
                                        $safeTooltip = htmlspecialchars(bm_parse_item_index($rawHex), ENT_QUOTES, 'UTF-8');
                                        
                                        // 2. NOMBRE LIMPIO (Sin HTML ni opciones, solo nombre base)
                                        $idItem = hexdec(substr($rawHex, 0, 2));
                                        $byte9 = hexdec(substr($rawHex, 18, 2));
                                        $group = ($byte9 >> 4) & 0xF;
                                        $cleanName = ItemsManager::getItemName($group, $idItem);

                                        $priceStr = number_format($cItem['price']);
                                        $currencyIcon = ($cItem['currency'] == 'Credits') ? '<i class="fa fa-coins"></i>' : '<i class="fa fa-gem"></i>';
                                        $itemId = $cItem['id'];
                                        
                                        echo '<div class="market-card-white" 
                                                   data-tooltip="'.$safeTooltip.'"
                                                   onmouseenter="idx_show_tt(this)" 
                                                   onmousemove="idx_move_tt(event)" 
                                                   onmouseleave="idx_hide_tt()">
                                                
                                                <div class="mc-img-box">
                                                    <img src="'.__BASE_URL__.$cItem['item_img'].'" alt="Item">
                                                </div>
                                                
                                                <div class="mc-info-box">
                                                    <div class="mc-name">'.$cleanName.'</div>
                                                    <div class="mc-price">'.$currencyIcon.' '.$priceStr.'</div>
                                                    <button class="mc-btn-buy" onclick="openDirectBuy('.$itemId.', \''.$priceStr.'\')">COMPRAR</button>
                                                </div>
                                            </div>';
                                    }
                                }
                            } catch (Exception $e) {
                                 echo '<div class="market-empty-state">Mercado no disponible temporalmente.</div>';
                            }
                            ?>
                        </div>
                    </div>

                    <div class="mt-5">
                        <button onclick="openMarketModal()" class="btn btn-dark" style="font-family:'Cinzel'; font-weight:700; padding: 12px 30px;">
                            VER MERCADO COMPLETO <i class="fa fa-arrow-right ms-2"></i>
                        </button>
                    </div>
                </div>
            </section>
            <?php endif; ?>

            <?php
            function get_class_img($classCode) {
                $c = (int)$classCode;
                $img = 'avatar.jpg';
                if ($c < 16) $img = 'dw.jpg'; elseif ($c < 32) $img = 'dk.jpg'; elseif ($c < 48) $img = 'elf.jpg';
                elseif ($c < 64) $img = 'mg.jpg'; elseif ($c < 80) $img = 'dl.jpg'; elseif ($c < 96) $img = 'su.jpg';
                elseif ($c < 112) $img = 'rf.jpg';
                
                // FIX: Usamos la carpeta del directorio RAÍZ como pediste
                return __BASE_URL__ . 'img/character-avatars/' . $img;
            }

            $dbRank = Connection::Database('MuOnline');
            // MODIFICADO: TOP 15 para dar margen al filtrado
            $topResets = $dbRank->query_fetch("SELECT TOP 15 Name, Class, ResetCount FROM Character WITH (NOLOCK) ORDER BY ResetCount DESC");
            $topGuilds = $dbRank->query_fetch("SELECT TOP 5 G_Name, G_Score, G_Mark FROM Guild WITH (NOLOCK) ORDER BY G_Score DESC");
            try { 
                $topDuels = $dbRank->query_fetch("SELECT TOP 15 Name, Class, WinScore FROM T_DuelTournamentUser WITH (NOLOCK) ORDER BY WinScore DESC"); 
            } catch(Exception $e) { $topDuels = []; }
            $topPk = $dbRank->query_fetch("SELECT TOP 15 Name, Class, PkCount FROM Character WITH (NOLOCK) ORDER BY PkCount DESC");
            ?>

            <div class="container rankings-section">
                <div class="ranking-header">
                    <h2 class="rank-main-title">Top Rankings</h2>
                    <p class="rank-subtitle">¿Sos lo bastante bueno para estar entre los mejores? ¡Acepta el desafío!</p>
                </div>
                <div class="row g-4">
                    <div class="col-lg-3 col-md-6">
                        <div class="rank-card">
                            <div class="rank-header-box"><span>#</span><span>Personaje</span><span>Resets</span></div>
                            <div class="rank-list">
                                <?php 
                                $i=1; 
                                foreach($topResets as $p): 
                                    if(!is_class_allowed($p['Class'])) continue; // FILTRO OPTIMIZADO
                                    if($i > 5) break; 
                                ?>
                                <div class="rank-row">
                                    <div class="r-num"><?php echo $i++; ?></div>
                                    <div class="r-info"><img src="<?php echo get_class_img($p['Class']); ?>" class="r-avatar"><div class="r-data"><span class="r-name"><?php echo $p['Name']; ?></span></div></div>
                                    <div class="r-val"><?php echo number_format($p['ResetCount']); ?></div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <div class="rank-card">
                            <div class="rank-header-box"><span>#</span><span>Guild</span><span>Score</span></div>
                            <div class="rank-list">
                                <?php $i=1; foreach($topGuilds as $g): ?>
                                <div class="rank-row">
                                    <div class="r-num"><?php echo $i++; ?></div>
                                    <div class="r-info">
                                        <img src="<?php echo __BASE_URL__; ?>api/guildmark.php?data=<?php echo bin2hex($g['G_Mark']); ?>&size=30" class="r-avatar">
                                        <div class="r-data"><span class="r-name"><?php echo $g['G_Name']; ?></span></div>
                                    </div>
                                    <div class="r-val"><?php echo number_format($g['G_Score']); ?></div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <div class="rank-card">
                            <div class="rank-header-box"><span>#</span><span>Personaje</span><span>Duels</span></div>
                            <div class="rank-list">
                                <?php 
                                $i=1; 
                                if($topDuels): foreach($topDuels as $d): 
                                    if(!is_class_allowed($d['Class'])) continue; // FILTRO
                                    if($i > 5) break;
                                ?>
                                <div class="rank-row">
                                    <div class="r-num"><?php echo $i++; ?></div>
                                    <div class="r-info"><img src="<?php echo get_class_img($d['Class']); ?>" class="r-avatar"><div class="r-data"><span class="r-name"><?php echo $d['Name']; ?></span></div></div>
                                    <div class="r-val"><?php echo number_format($d['WinScore']); ?></div>
                                </div>
                                <?php endforeach; endif; ?>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <div class="rank-card">
                            <div class="rank-header-box"><span>#</span><span>Personaje</span><span>Pk</span></div>
                            <div class="rank-list">
                                <?php 
                                $i=1; 
                                foreach($topPk as $pk): 
                                    if(!is_class_allowed($pk['Class'])) continue; // FILTRO
                                    if($i > 5) break;
                                ?>
                                <div class="rank-row">
                                    <div class="r-num"><?php echo $i++; ?></div>
                                    <div class="r-info"><img src="<?php echo get_class_img($pk['Class']); ?>" class="r-avatar"><div class="r-data"><span class="r-name"><?php echo $pk['Name']; ?></span></div></div>
                                    <div class="r-val"><?php echo number_format($pk['PkCount']); ?></div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        <?php else: ?>
            <main class="container subpage-container py-5">
                
                <?php if($_REQUEST['page'] == 'usercp'): ?>
                    <div style="margin-top: 50px;"> <?php include('modules/usercp.php'); ?>
                    </div>
                <?php else: ?>
                    <div class="content-box-clean"> 
                        <?php $handler->loadModule($_REQUEST['page'], $_REQUEST['subpage']); ?>
                    </div>
                <?php endif; ?>

            </main>
        <?php endif; ?>
    </div>

    <div id="global-tt"></div>
    
    <div id="wsMarketModal" class="ws-modal-overlay" style="display:none;">
        <div class="ws-modal-content">
            <button class="btn-ws-close" onclick="closeMarketModal()"><i class="fa fa-times"></i></button>
            <div class="ws-workspace">
                <div class="ws-sidebar-left">
                    <div class="cat-header">CATEGORÍAS</div>
                    <div class="cat-list-scroll">
                        <?php 
                        $cats = [
                            -1=>['Todo', 'fa-globe'], 0=>['Espadas', 'fa-khanda'], 1=>['Hachas', 'fa-gavel'], 2=>['Mazas', 'fa-hammer'], 
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
                        <div class="search-wrapper-market">
                            <i class="fa fa-search"></i>
                            <input type="text" id="wsSearch" placeholder="Buscar items..." onkeyup="wsFilterGrid()">
                        </div>
                    </div>
                    <div class="item-grid-container" id="wsItemGrid">
                        <div class="market-placeholder">
                            <i class="fa fa-shop"></i>
                            <p>Selecciona una categoría para comenzar</p>
                        </div>
                    </div>
                    <div class="loading-overlay" id="wsLoader">
                        <div class="spinner-border text-light" role="status"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div id="alertModal" class="alert-modal-overlay" style="display:none;">
        <div class="alert-box-classic confirm-box-classic">
            <h3 id="alertTitle" class="confirm-title">AVISO</h3>
            <p id="alertBody" class="confirm-body">Mensaje</p>
            <div class="confirm-btns"><button class="btn-confirm-yes" onclick="document.getElementById('alertModal').style.display='none'">ENTENDIDO</button></div>
        </div>
    </div>
    
    <div id="buyConfirmModal" class="confirm-modal-overlay" style="display:none;">
        <div class="confirm-box-classic">
            <div class="confirm-title">CONFIRMAR COMPRA</div>
            <div class="confirm-body"><p>¿Deseas comprar este item por <span id="buyPriceLabel" style="color:#ffd700; font-weight:bold;"></span>?</p><small class="text-danger"><i class="fa fa-exclamation-triangle"></i> Tu baúl debe estar completamente <b>VACÍO</b> y debes estar <b>DESCONECTADO</b>.</small></div>
            <div class="confirm-btns"><button class="btn-confirm-no" onclick="document.getElementById('buyConfirmModal').style.display='none'">CANCELAR</button><button class="btn-confirm-yes" id="btnDoBuy">COMPRAR AHORA</button></div>
        </div>
    </div>

    <?php include(__PATH_TEMPLATE_ROOT__ . 'inc/modules/footer.php'); ?>
    <?php include_once($_SERVER['DOCUMENT_ROOT'] . '/templates/misc/sidebar.php'); ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?php echo __BASE_URL__; ?>templates/classic/js/main.js"></script>
    <?php include('includes/popup_modal.php'); ?>
    <?php include 'templates/misc/countdown.php'; ?>
    
    <?php
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
            var cronUrl = "<?php echo __BASE_URL__; ?>api/cron.php";
            try {
                fetch(cronUrl, {mode: 'no-cors'});
            } catch(e) {
                console.error("Error auto-cron", e);
            }
        }, 3000);
    });
    </script>
    <?php endif; ?>
    <script>
        var selectedBuyId = 0;
        function idx_show_tt(el) { var tt = document.getElementById('global-tt'); tt.innerHTML = el.getAttribute('data-tooltip'); tt.style.display = 'block'; }
        function idx_move_tt(e) { var tt = document.getElementById('global-tt'); tt.style.left = (e.clientX + 15) + 'px'; tt.style.top = (e.clientY + 15) + 'px'; }
        function idx_hide_tt() { document.getElementById('global-tt').style.display = 'none'; }
        function openMarketModal() { document.getElementById('wsMarketModal').style.display = 'flex'; }
        function closeMarketModal() { document.getElementById('wsMarketModal').style.display = 'none'; }
        function openDirectBuy(id, price) { selectedBuyId = id; document.getElementById('buyPriceLabel').innerText = price; document.getElementById('buyConfirmModal').style.display = 'flex'; }
        function bm_buy_loader(show) {
            var el = document.getElementById('bmBuyLoader');
            if(!el) {
                el = document.createElement('div');
                el.id = 'bmBuyLoader';
                el.style.cssText = 'display:none;position:fixed;top:0;left:0;width:100vw;height:100vh;background:rgba(0,0,0,0.85);z-index:100001;align-items:center;justify-content:center;flex-direction:column;';
                el.innerHTML = '<i class="fa fa-spinner fa-spin fa-3x" style="color:#FF8A3B;margin-bottom:12px;"></i><span style="color:#fff;font-size:13px;font-weight:bold;letter-spacing:1px;">PROCESANDO COMPRA...</span>';
                document.body.appendChild(el);
            }
            el.style.display = show ? 'flex' : 'none';
        }

        document.getElementById('btnDoBuy').addEventListener('click', function() {
            if(selectedBuyId == 0) return;
            document.getElementById('buyConfirmModal').style.display = 'none';
            bm_buy_loader(true);
            var formData = new FormData(); formData.append('market_ajax', 'true'); formData.append('action', 'buy_item'); formData.append('id', selectedBuyId);
            fetch('<?php echo __BASE_URL__; ?>index.php', { method:'POST', body:formData }).then(r => r.json()).then(data => {
                bm_buy_loader(false);
                if(data.status == 'success') {
                    document.getElementById('alertTitle').innerText = "ÉXITO"; document.getElementById('alertBody').innerText = "¡Compra realizada! Revisa tu baúl."; document.getElementById('alertModal').style.display = 'flex'; setTimeout(function(){ location.reload(); }, 2000);
                } else {
                    document.getElementById('alertTitle').innerText = "ERROR"; document.getElementById('alertBody').innerText = data.message; document.getElementById('alertModal').style.display = 'flex';
                }
            }).catch(e => { bm_buy_loader(false); document.getElementById('alertTitle').innerText = "ERROR"; document.getElementById('alertBody').innerText = "No se pudo procesar la compra. Intenta de nuevo."; document.getElementById('alertModal').style.display = 'flex'; });
        });

        function wsLoadCat(catId, btn) {
            document.querySelectorAll('.cat-btn').forEach(b => b.classList.remove('active')); if(btn) btn.classList.add('active');
            document.getElementById('wsLoader').style.display = 'flex';
            var formData = new FormData(); formData.append('market_ajax', 'true'); formData.append('action', 'load_category'); formData.append('category', catId);
            fetch('<?php echo __BASE_URL__; ?>index.php', { method:'POST', body:formData }).then(r => r.json()).then(data => {
                document.getElementById('wsLoader').style.display = 'none';
                var grid = document.getElementById('wsItemGrid'); grid.innerHTML = '';
                if(data.data.length == 0) { grid.innerHTML = '<div style="grid-column:1/-1; text-align:center; padding:50px; color:#666;">No hay items en esta categoría.</div>'; return; }
                data.data.forEach(item => {
                    var currencyIcon = (item.currency == 'Credits') ? '<i class="fa fa-coins"></i>' : '<i class="fa fa-gem"></i>';
                    var html = `<div class="market-card-white" style="background: #222; border-color: #444;" data-name="${item.item_name}" data-tooltip="${item.tooltip}" onmouseenter="idx_show_tt(this)" onmousemove="idx_move_tt(event)" onmouseleave="idx_hide_tt()"><div class="mc-img-box" style="background:#111; border-color:#333;"><img src="${item.img}"></div><div class="mc-info-box"><div class="mc-name" style="color:#ccc;">${item.item_name}</div><div class="mc-price" style="color:#FF8A3B;">${currencyIcon} ${item.price}</div><button class="mc-btn-buy" style="background:#d32f2f;" onclick="openDirectBuy(${item.id}, '${item.price}')">COMPRAR</button></div></div>`;
                    grid.innerHTML += html;
                });
            });
        }
        function wsFilterGrid() {
            var val = document.getElementById('wsSearch').value.toLowerCase();
            document.querySelectorAll('#wsItemGrid .market-card-white').forEach(card => {
                var name = card.getAttribute('data-name').toLowerCase();
                card.style.display = name.includes(val) ? 'block' : 'none';
            });
        }
    </script>
</body>
</html>