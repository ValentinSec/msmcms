<?php
/**
 * MASTER INDEX TEMPLATE
 * Base sólida unificada con todas las funciones de Default, Classic y Morpheus.
 */

// 1. BUFFER DE SALIDA
ob_start();

// 2. SISTEMA DE RUTAS
$request_path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$path_parts = explode('/', trim($request_path, '/'));

if (isset($path_parts[0]) && !empty($path_parts[0])) $_REQUEST['page'] = $path_parts[0];
if (isset($path_parts[1]) && !empty($path_parts[1])) $_REQUEST['subpage'] = $path_parts[1];

// 3. SEGURIDAD BASE
if(!defined('access') or !access) die();

// 4. CARGA DE DEPENDENCIAS
include('inc/template.functions.php');
include_once('includes/config/items_manager.php');



// 6. FUNCIONES HELPER (Configuración y Clases)
if (!function_exists('custom_config')) {
    function custom_config($key) {
        $path = __DIR__ . '/../../includes/config/custom_settings.json';
        if(!file_exists($path)) $path = $_SERVER['DOCUMENT_ROOT'] . '/includes/config/custom_settings.json';
        if(!file_exists($path)) return "";
        $data = json_decode(@file_get_contents($path), true);
        return isset($data[$key]) ? $data[$key] : "";
    }
}

if(!function_exists('getClassImageName')) {
    function getClassImageName($code) {
        $code = (int)$code;
        if ($code < 16) return 'dw';  
        if ($code < 32) return 'dk';  
        if ($code < 48) return 'elf'; 
        if ($code < 64) return 'mg';  
        if ($code < 80) return 'dl';  
        if ($code < 96) return 'sum'; 
        if ($code < 112) return 'rf'; 
        return 'gl';                  
    }
}

// 7. PARSER DE ITEMS COMPLETO (Tooltips para WebShop y BlackMarket)
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
        
        // 380 Option
        $is380 = false;
        $op380Names = [];
        if (!$isSocketItem && $group != 13) {
            $is380 = (($byte9 & 16) > 0) || (($byte9 & 8) > 0);
            if ($is380 && class_exists('ItemsManager')) {
                $op380Names = ItemsManager::get380Options($group, $id);
            }
        }
        
        $name = ItemsManager::getItemName($group, $id);
        
        // HTML Build
        $html = "<div class='tt-box'>";
        $lvlStr = ($level > 0) ? " +".$level : "";
        $nameColor = (count($excOpts) > 0) ? "tt-exc" : "tt-name";
        $html .= "<span class='$nameColor' style='display:block; font-weight:bold; font-size:13px; margin-bottom:5px; border-bottom:1px solid #444; padding-bottom:5px;'>$name$lvlStr</span>";
        
        // Render 380 Options
        if($is380) {
            if(!empty($op380Names)) {
                foreach($op380Names as $op380Name) {
                    $html .= "<div class='tt-380' style='color:#d000d0; font-weight:bold;'>$op380Name</div>";
                }
            } else {
                $html .= "<div class='tt-380' style='color:#d000d0; font-weight:bold;'>380 Option Active</div>";
            }
        }

        // Harmony
        if (!$isSocketItem && $byte10 > 0) {
            $hType = 1; 
            if ($group == 5) $hType = 5; 
            if ($group >= 6 && $group <= 11) $hType = 6; 
            $optId = ($byte10 >> 4) & 0xF;
            $optLvl = ($byte10 & 0xF);

            $hNames = [
                1 => [1 => "Min. attack increase", 2 => "Max. attack increase", 3 => "Required strength decrease", 4 => "Required agility decrease", 5 => "Attack increase (Min,Max)", 6 => "Critical damage increase", 7 => "Skill attack increase", 8 => "Attack success rate increase (PVP)", 9 => "SD decrement increase", 10 => "SD bypass rate increase"],
                5 => [1 => "Wizardry increase", 2 => "Required strength decrease", 3 => "Required agility decrease", 4 => "Skill attack increase", 5 => "Critical damage increase", 6 => "SD decrement increase", 7 => "Attack success rate increase (PVP)", 8 => "SD bypass rate increase"],
                6 => [1 => "Defense increase", 2 => "Max. AG increase", 3 => "Max. HP increase", 4 => "Life auto increment increase", 5 => "Mana auto increment increase", 6 => "Defense success rate increase (PVP)", 7 => "Damage decrement increase", 8 => "SD ratio increase"]
            ];
            
            if (isset($hNames[$hType][$optId])) {
                $hName = $hNames[$hType][$optId];
                $html .= "<div style='color:#ffff00; font-weight:bold;'>$hName (Lvl $optLvl)</div>";
            }
        }
        
        if($is380 || (!$isSocketItem && $byte10 > 0)) {
            $html .= "<div style='margin-bottom: 5px;'></div>";
        }

        if($skill) $html .= "<div class='tt-opt' style='color:#9aadcd'>Skill Attack Power</div>";
        if($luck) $html .= "<div class='tt-luck' style='color:#9aadcd'>Luck (success rate +25%)</div>";
        if($option>0) $html .= "<div class='tt-opt' style='color:#9aadcd'>Additional Option +$option</div>";
        
        
        
        
        foreach($excOpts as $ex) $html .= "<div class='tt-exc' style='color:#2ecc71'>$ex</div>";

        // Sockets
        $hasSockets = false;
        $socketsHTML = "";
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
                        $sLvl = floor($sockVal / 50) + 1;
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

// 8. SEGURIDAD (Anti-DDoS / IP Block)
$secConfigFile = __PATH_INCLUDES__ . 'config/security_config.json';
if (file_exists($secConfigFile)) {
    $secConfig = json_decode(file_get_contents($secConfigFile), true);
    $userIp = $_SERVER['REMOTE_ADDR'];
    
    // Función visual de bloqueo
    if (!function_exists('show_access_denied')) {
        function show_access_denied($title, $msg, $ip) {
            while (ob_get_level() > 0) { @ob_end_clean(); }
            echo '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><title>Acceso Restringido</title><style>body{background:#000;color:#fff;font-family:sans-serif;text-align:center;padding:50px;}</style></head><body><h1>'.$title.'</h1><p>'.$msg.'</p><small>IP: '.$ip.'</small></body></html>';
            flush(); exit(); 
        }
    }
    
    // Check IP
    if (!empty($secConfig['blocked_ips'])) {
        $blockedList = explode("\n", str_replace("\r", "", $secConfig['blocked_ips']));
        if (in_array(trim($userIp), array_map('trim', $blockedList))) {
            show_access_denied("ACCESO DENEGADO", "Tu IP ha sido bloqueada.", $userIp);
        }
    }
    
    // Anti-DDoS
    if (isset($secConfig['ddos_active']) && $secConfig['ddos_active'] == 1) {
        if (!session_id()) @session_start();
        $limit = $secConfig['ddos_limit'] ?? 10; 
        $now = microtime(true);
        if (isset($_SESSION['ddos_penalty_until']) && $now < $_SESSION['ddos_penalty_until']) {
            show_access_denied("TRÁFICO ALTO", "Demasiadas solicitudes. Espera un momento.", $userIp);
        }
        if (!isset($_SESSION['last_request_micro'])) {
            $_SESSION['last_request_micro'] = $now; $_SESSION['req_count'] = 1;
        } else {
            $timeDiff = $now - $_SESSION['last_request_micro'];
            if ($timeDiff < 0.5) { 
                $_SESSION['req_count']++;
                if ($_SESSION['req_count'] > $limit) {
                    $_SESSION['ddos_penalty_until'] = $now + 10;
                    show_access_denied("SEGURIDAD ACTIVADA", "Sistema anti-flood activado.", $userIp);
                }
            } else {
                $_SESSION['last_request_micro'] = $now; $_SESSION['req_count'] = 1;
            }
        }
    }
}

// 9. LOGICA DE LOGIN (Modificada para permanecer en el sitio)
if((isset($_POST['msmLogin_user']) && !empty($_POST['msmLogin_user'])) || isset($_POST['msmLogin_submit'])) {
    $_POST['msmLogin_submit'] = 'submit';
    try {
        if(class_exists('login')) {
            $userLogin = new login();
            $userLogin->validateLogin($_POST['msmLogin_user'], $_POST['msmLogin_pwd']);
            
            // En lugar de redirigir a /usercp, recargamos la ubicación actual 
            // para que se actualicen los estados de login en el sidebar.
            ob_clean();
            header("Location: " . $_SERVER['HTTP_REFERER']); 
            exit;
        }
    } catch (Throwable $ex) {
        // Opcional: podrías guardar el error en una sesión para mostrarlo en el sidebar
    }
}

// 10. HANDLERS AJAX (Ranking y Mercado)
if((isset($_POST['market_ajax']) && $_POST['market_ajax'] == 'true') || (isset($_POST['ranking_ajax']) && $_POST['ranking_ajax'] == 'true')) {
    while (ob_get_level()) ob_end_clean();
    header('Content-Type: application/json');
    try {
        $db = Connection::Database('Me_MuOnline'); // Usar Me_MuOnline para tablas custom

        // A. Ranking AJAX Handler
        if(isset($_POST['ranking_ajax'])) {
            $type = $_POST['type'];
            $sql = "SELECT TOP 5 Name, ResetCount as Val, Class FROM Character ORDER BY ResetCount DESC"; // Default Fallback
            
            // Lógica simplificada de Rankings (puedes expandir según tu index.php original)
            if($type == 'Resets') $sql = "SELECT TOP 5 Name, ResetCount as Val, Class FROM Character WHERE CtlCode < 32 ORDER BY ResetCount DESC, cLevel DESC";
            elseif($type == 'Level') $sql = "SELECT TOP 5 Name, cLevel as Val, Class FROM Character WHERE CtlCode < 32 ORDER BY cLevel DESC";
            elseif($type == 'Guilds') $sql = "SELECT TOP 5 G_Name as Name, G_Score as Val, G_Mark FROM Guild ORDER BY G_Score DESC";
            
            $data = [];
            $rows = $db->query_fetch($sql);
            if(is_array($rows)) {
                foreach($rows as $r) {
                    $img = "";
                    if($type == 'Guilds') {
                        $img = '<img src="'.__BASE_URL__.'api/guildmark.php?data='.bin2hex($r['G_Mark']).'&size=20">';
                    } else {
                        $fname = getClassImageName($r['Class']);
                        $url = __BASE_URL__ . 'img/character-avatars/' . $fname . '.jpg';
                        $img = "<img src='$url' style='width:25px;height:25px;border-radius:50%;'>";
                    }
                    $data[] = ['name'=>$r['Name'], 'val'=>number_format($r['Val']), 'race_html'=>$img];
                }
            }
            echo json_encode(['status'=>'success', 'data'=>$data]);
            exit;
        }

        // B. Market AJAX Handler
        // B. Market AJAX Handler
        if(isset($_POST['market_ajax'])) {
            $bmAction = isset($_POST['action']) ? $_POST['action'] : '';

            // Widget del index: ultimos 3 items publicados
            if($bmAction == 'load_widget') {
                $data = [];
                $rows = $db->query_fetch("SELECT TOP 3 id, item_hex, price, currency FROM MUSERVERMAKER_MARKET_SELL ORDER BY date_posted DESC");
                if(is_array($rows)) {
                    foreach($rows as $row) {
                        $hex = $row['item_hex']; if(!ctype_xdigit((string)$hex)) $hex = bin2hex($hex);
                        $id = hexdec(substr($hex, 0, 2)); $group = (hexdec(substr($hex, 18, 2)) >> 4) & 0xF;
                        $level = (hexdec(substr($hex, 2, 2)) >> 3) & 0xF;
                        $data[] = [
                            'id' => $row['id'],
                            'name' => ItemsManager::getItemName($group, $id),
                            'level' => ($level > 0) ? "+".$level : "",
                            'price' => number_format($row['price']),
                            'currency' => $row['currency'],
                            'image' => __BASE_URL__ . "img/items/{$group}-{$id}.gif",
                            'tooltip' => htmlspecialchars(bm_parse_item_index($hex), ENT_QUOTES, 'UTF-8')
                        ];
                    }
                }
                echo json_encode(['status' => 'success', 'data' => $data]);
                exit;
            }

            // Listado por categoria (logica central)
            if($bmAction == 'load_category') {
                $data = BlackMarket::getMarketItems(isset($_POST['category']) ? $_POST['category'] : -1);
                echo json_encode(['status' => 'success', 'data' => $data]);
                exit;
            }

            // Compra (logica central: cuenta desconectada + consultas parametrizadas)
            if($bmAction == 'buy_item') {
                if(!isLoggedIn()) throw new Exception("Inicia sesión.");
                $result = BlackMarket::buyItem($_SESSION['username'], isset($_POST['id']) ? $_POST['id'] : 0);
            echo json_encode($result, JSON_INVALID_UTF8_SUBSTITUTE);
                exit;
            }

            echo json_encode(['status' => 'error', 'message' => 'Accion no valida.']);
            exit;
        }
    } catch (Exception $e) { echo json_encode(['status' => 'error', 'message' => $e->getMessage()]); exit;
    } catch (Throwable $e) { error_log('[market_ajax] ' . $e->getMessage()); echo json_encode(['status' => 'error', 'message' => 'Error interno.']); exit; }
}

// 11. PREPARACIÓN DE DATOS GLOBALES (Para usar en el HTML)
$cacheFilePath = __PATH_INCLUDES__ . 'cache/server_info.cache';
$realOnline = 0;
if(file_exists($cacheFilePath)) {
    $rawCache = file_get_contents($cacheFilePath);
    $cacheData = json_decode($rawCache, true);
    if(isset($cacheData[1][0])) { $srvInfo = explode("|", $cacheData[1][0]); $realOnline = isset($srvInfo[3]) ? (int)$srvInfo[3] : 0; }
}
$multOnline = (float)custom_config('fake_online');
$displayOnline = round($realOnline * ($multOnline > 0 ? $multOnline : 1));

// Configuraciones del template
$muservermakerJsonPath = $_SERVER['DOCUMENT_ROOT'] . '/includes/config/muservermaker.json';
$muservermakerParams = json_decode(file_get_contents($muservermakerJsonPath), true);
$activeTplName = $muservermakerParams['website_template']; 
$templateConfigPath = $_SERVER['DOCUMENT_ROOT'] . '/templates/' . $activeTplName . '/config.json';
$tplData = ['hero_logo_width' => '300px']; // Default
if(file_exists($templateConfigPath)) {
    $tplData = json_decode(file_get_contents($templateConfigPath), true);
}

// Configuración Black Market
$bmConfigFile = __PATH_INCLUDES__ . 'config/black_market_config.json';
$bmActive = false;
if (file_exists($bmConfigFile)) {
    $bmConf = json_decode(file_get_contents($bmConfigFile), true);
    if (isset($bmConf['module_active']) && $bmConf['module_active'] == 'on') $bmActive = true;
}

$seoFile = __PATH_INCLUDES__ . 'config/muservermaker.json';
        $seoData = file_exists($seoFile) ? json_decode(file_get_contents($seoFile), true) : [];
        
        $metaDesc = !empty($seoData['website_meta_description']) ? htmlspecialchars($seoData['website_meta_description']) : 'Bienvenido a ' . custom_config('server_name');
        $metaKeys = !empty($seoData['website_meta_keywords']) ? htmlspecialchars($seoData['website_meta_keywords']) : 'mu online, private server, mu server';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="<?php echo $metaDesc; ?>">
    <meta name="keywords" content="<?php echo $metaKeys; ?>">
    <meta name="author" content="MuServerMaker">
    <link rel="canonical" href="<?php echo __BASE_URL__; ?>">
    <title><?php echo custom_config('web_title'); ?></title>
    <link rel="icon" type="image/x-icon" href="<?php echo __BASE_URL__; ?>templates/canibal/img/favicon.ico?v=<?php echo time(); ?>">
    <link rel="shortcut icon" type="image/x-icon" href="<?php echo __BASE_URL__; ?>templates/canibal/img/favicon.ico?v=<?php echo time(); ?>">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;700&family=Roboto:wght@300;400;700&display=swap" rel="stylesheet">
    <link href="<?php echo __BASE_URL__; ?>templates/canibal/css/main.css?v=<?php echo time(); ?>" rel="stylesheet">
    <link href="<?php echo __BASE_URL__; ?>templates/canibal/css/casino.css?v=<?php echo time(); ?>" rel="stylesheet">
    <link href="<?php echo __BASE_URL__; ?>templates/canibal/css/news.css?v=<?php echo time(); ?>" rel="stylesheet">
    <link href="<?php echo __BASE_URL__; ?>templates/canibal/css/downloads.css?v=<?php echo time(); ?>" rel="stylesheet">
    <link href="<?php echo __BASE_URL__; ?>templates/canibal/css/rankings.css?v=<?php echo time(); ?>" rel="stylesheet">
    <link href="<?php echo __BASE_URL__; ?>templates/canibal/css/info.css?v=<?php echo time(); ?>" rel="stylesheet">
    <link href="<?php echo __BASE_URL__; ?>templates/canibal/css/config.css?v=<?php echo time(); ?>" rel="stylesheet">
    <link href="<?php echo __BASE_URL__; ?>templates/canibal/css/donate.css?v=<?php echo time(); ?>" rel="stylesheet">
    <link href="<?php echo __BASE_URL__; ?>templates/canibal/css/vip.css?v=<?php echo time(); ?>" rel="stylesheet">
    <?php 
    // Al principio de tu index.php asegurate de tener $tplConfig = json_decode(file_get_contents($templateConfigPath), true);
    $bgTop = isset($tplConfig['bg_top_img']) && !empty($tplConfig['bg_top_img']) ? __BASE_URL__.'templates/canibal/img/'.$tplConfig['bg_top_img'] : __BASE_URL__.'templates/canibal/img/bg-top.jpg';
    $bgBot = isset($tplConfig['bg_bottom_img']) && !empty($tplConfig['bg_bottom_img']) ? __BASE_URL__.'templates/canibal/img/'.$tplConfig['bg_bottom_img'] : __BASE_URL__.'templates/canibal/img/bg-bottom.jpg';
    ?>
    <style>
        .bg-top { background: url('<?php echo $bgTop; ?>') top center no-repeat; background-size: cover; }
        .bg-bottom { background: url('<?php echo $bgBot; ?>') bottom center no-repeat; background-size: cover; }
    </style>
    
</head>
<body>

    <div class="bg-layer bg-top"></div>
    <div class="bg-layer bg-bottom"></div>
    <div class="bg-overlay"></div>

    <div class="canibal-header-logo" style="position: absolute; top: 170px; left: 0; width: 100%; text-align: center; z-index: 9999; pointer-events: none;">
        <a href="<?php echo __BASE_URL__; ?>" style="display: inline-block; pointer-events: auto;">
            <img src="<?php echo __BASE_URL__; ?>templates/canibal/img/logo.png" alt="Server Logo" class="canibal-hero-logo" style="width: <?php echo isset($tplData['hero_logo_width']) ? $tplData['hero_logo_width'] : '320px'; ?> !important; max-width: 100%; height: auto; filter: drop-shadow(0 5px 15px rgba(0,0,0,0.8)); transition: transform 0.3s ease;" onmouseover="this.style.transform='scale(1.05)';" onmouseout="this.style.transform='scale(1)';">
        </a>
    </div>
    <div class="main-wrapper">
        
        <div class="canibal-container">
            
            <?php
                // Lógica de Stats para el Hero
                if(!isset($stats['accounts'])) {
                    try {
                        $dbStat = Connection::Database('MuOnline');
                        $stats['accounts'] = $dbStat->query_fetch_single("SELECT COUNT(*) as c FROM MEMB_INFO")['c'];
                    } catch (Exception $e) { $stats['accounts'] = 0; }
                }
                $maxUsers = 100; // Ajusta esto a tu capacidad real
                $porcOnline = ($displayOnline / $maxUsers) * 100;
                if($porcOnline > 100) $porcOnline = 100;
            ?>
            <div class="hero-online-widget">
                <div class="online-info-row">
                    <span class="online-label">USUARIOS EN LÍNEA:</span>
                    <span class="online-value"><?php echo $displayOnline; ?></span>
                </div>
                
                <div class="hero-progress-track">
                    <div class="hero-progress-fill" style="width: <?php echo $porcOnline; ?>%;"></div>
                </div>
                
                <div class="online-sub-info">
                    Usuarios Registrados: <span class="text-online-green"><?php echo number_format($stats['accounts']); ?></span>
                </div>
            </div>

            <?php include 'templates/canibal/inc/modules/navbar.php'; ?>
            
            <div class="main-column">
                <div id="ajax-container">
                    <?php 
                    if(isset($_REQUEST['page']) && $_REQUEST['page'] == 'usercp') {
                        // Bloqueamos acceso si no está logueado
                        if(!isLoggedIn()) {
                            echo "<script>window.location.href='".__BASE_URL__."';</script>";
                            exit;
                        }
                
                        // Definimos la constante de seguridad
                        define('USERCP_CLEAN', true);
                        
                        // Obtenemos la subpágina (por defecto config si está vacío)
                        $sub = isset($_REQUEST['subpage']) ? $_REQUEST['subpage'] : 'config';
                        $targetFile = 'modules/usercp/' . $sub . '.php';
                
                        if(file_exists($targetFile)) {
                            include($targetFile);
                        } else {
                            echo "<div class='alert alert-danger'>El módulo solicitado no existe.</div>";
                        }
                    } else if(!empty($_REQUEST['page'])) {
                        $handler->loadModule($_REQUEST['page'], $_REQUEST['subpage']);
                    } else {
                        // HOME PAGE
                        include('templates/canibal/inc/modules/home.php'); 
                    }
                    ?>
                    
                    <?php if(empty($_REQUEST['page'])): ?> <div class="home-news-section mb-5">
                        <div class="d-flex align-items-center justify-content-center mb-3">
                            <i class="fa fa-book-open fa-lg me-2" style="color: #c8a165;"></i>
                            <h4 class="text-white text-uppercase fw-bold m-0" style="font-family: 'Cinzel', serif; letter-spacing: 1px;">Últimas Noticias</h4>
                        </div>
                        
                        <hr style="border-color: rgba(255,255,255,0.1); margin: 0 0 15px 0;">
                        
                        <?php
                        $newsPath = $_SERVER['DOCUMENT_ROOT'] . '/includes/config/server_news.json';
                        $newsList = file_exists($newsPath) ? json_decode(file_get_contents($newsPath), true) : [];
                        
                        if(!empty($newsList)):
                            $newsList = array_slice($newsList, 0, 4);
                            foreach($newsList as $index => $news):
                                $nTitle = isset($news['news_title']) ? $news['news_title'] : (isset($news['title']) ? $news['title'] : 'Noticia');
                                $nDate = isset($news['news_date']) ? date('Y-m-d', $news['news_date']) : (isset($news['date']) ? $news['date'] : '');
                                $nContent = isset($news['news_content']) ? $news['news_content'] : (isset($news['content']) ? $news['content'] : 'Contenido no disponible.');
                                
                                $jsTitle = htmlspecialchars($nTitle, ENT_QUOTES, 'UTF-8');
                                $jsDate = htmlspecialchars($nDate, ENT_QUOTES, 'UTF-8');
                                $newsId = 'news_content_' . $index;
                        ?>
                        
                        <div onclick="openNewsModal('<?php echo $jsTitle; ?>', '<?php echo $jsDate; ?>', '<?php echo $newsId; ?>')" class="d-flex justify-content-between align-items-center px-4 py-2 mb-2 hover-scale" style="background: rgba(255,255,255,0.05); border-radius: 4px; border: 1px solid transparent; transition: all 0.3s; cursor: pointer;" onmouseover="this.style.borderColor='#c8a165'; this.style.background='rgba(200,161,101,0.05)';" onmouseout="this.style.borderColor='transparent'; this.style.background='rgba(255,255,255,0.05)';">
                            <div class="text-white" style="font-size: 14px;">
                                <i class="fa fa-bullhorn me-2" style="color: #c8a165;"></i> 
                                <?php echo htmlspecialchars($nTitle); ?>
                            </div>
                            <div class="text-white" style="font-size: 13px;"><?php echo $nDate; ?></div>
                        </div>
                        
                        <div id="<?php echo $newsId; ?>" style="display:none;"><?php echo $nContent; ?></div>
                        
                        <?php 
                            endforeach;
                        else: 
                        ?>
                        <div class="text-center py-3 text-muted" style="background: rgba(255,255,255,0.02); border-radius: 4px;">
                            No hay noticias recientes.
                        </div>
                        <?php endif; ?>
                        
                        <hr style="border-color: rgba(255,255,255,0.1); margin: 15px 0 0 0;">
                    </div>

                    <?php 
                    $csActive = custom_config('active_cs');
                    if($csActive === 'on' || $csActive == '1' || $csActive === true): 
                    ?>
                    <div class="home-cs-section mb-5 position-relative p-4" style="background: url('<?php echo __BASE_URL__; ?>img/cs-bg.jpg') center/cover no-repeat; border: 1px solid #2d323e; border-radius: 8px; box-shadow: 0 10px 30px rgba(0,0,0,0.6); overflow: hidden;">
                        <div style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: rgba(10, 10, 12, 0.7);"></div>
                        
                        <div class="position-relative z-1">
                            <h3 class="text-center text-white fw-bold mb-4" style="font-family: 'Cinzel', serif; text-shadow: 0 2px 5px rgba(0,0,0,0.8);">Castle Siege</h3>
                            
                            <div class="row align-items-center">
                                <div class="col-md-4 text-center mb-3 mb-md-0">
                                    <div class="cs-guild-logo mx-auto" style="width: 120px; height: 120px; background: #000; border: 1px solid #2d323e; box-shadow: 0 0 15px rgba(0,0,0,0.8);"></div>
                                </div>
                                
                                <div class="col-md-8">
                                    <div class="row text-center text-md-center g-4">
                                        <div class="col-6">
                                            <h6 style="color: #d9534f; font-weight: bold; font-size: 14px; margin-bottom: 5px;">Castle Owner:</h6>
                                            <p class="text-white fw-bold m-0" style="font-size: 14px;">N/A</p>
                                        </div>
                                        <div class="col-6">
                                            <h6 style="color: #d9534f; font-weight: bold; font-size: 14px; margin-bottom: 5px;">Stage</h6>
                                            <p class="text-white fw-bold m-0" style="font-size: 14px;">Sign Registration</p>
                                        </div>
                                        <div class="col-6">
                                            <h6 style="color: #d9534f; font-weight: bold; font-size: 14px; margin-bottom: 5px;">Guild Master</h6>
                                            <p class="text-white fw-bold m-0" style="font-size: 14px;">N/A</p>
                                        </div>
                                        <div class="col-6">
                                            <h6 style="color: #d9534f; font-weight: bold; font-size: 14px; margin-bottom: 5px;">Castle Siege Battle</h6>
                                            <p class="text-white fw-bold m-0" style="font-size: 14px;">¡Castle Siege en progreso!</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <a href="<?php echo __BASE_URL__; ?>castlesiege" class="btn w-100 mt-4 d-flex justify-content-center align-items-center" style="background: rgba(128, 128, 0, 0.3); border: 1px solid rgba(255, 255, 255, 0.2); color: #fff; border-radius: 4px; padding: 10px; font-weight: bold; transition: all 0.3s;" onmouseover="this.style.background='rgba(128, 128, 0, 0.5)';" onmouseout="this.style.background='rgba(128, 128, 0, 0.3)';">
                                <i class="fa fa-info-circle me-2"></i> Click aquí para ver Mas info
                            </a>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php 
                    $socActive = custom_config('active_social');
                    if($socActive === 'on' || $socActive == '1' || $socActive === true): 
                        $linkFB = custom_config('social_fb');
                        $linkIG = custom_config('social_ig');
                        $linkDC = custom_config('social_discord');
                        $linkWP = custom_config('social_wpp');
                        $linkYT = custom_config('social_yt');
                        $linkEM = custom_config('social_email');
                    ?>
                    <div class="home-social-section mb-5">
                        <div class="text-center py-2 mb-3" style="background: #000; border: 1px solid #2d323e; border-radius: 4px;">
                            <h5 class="text-white m-0" style="font-size: 15px; font-weight: bold;">Social Media</h5>
                        </div>
                        
                        <?php 
                            // Función para detectar la extensión automática de las imágenes por defecto
                            if (!function_exists('getSocialFallback')) {
                                function getSocialFallback($networkName) {
                                    $extensions = ['png', 'jpg', 'jpeg', 'gif', 'svg', 'webp'];
                                    $baseDir = __DIR__ . '/img/social/'; // Busca en la carpeta raíz del CMS
                                    foreach($extensions as $ext) {
                                        if(file_exists($baseDir . $networkName . '.' . $ext)) {
                                            return __BASE_URL__ . 'img/social/' . $networkName . '.' . $ext;
                                        }
                                    }
                                    // Si alguien borró todas las imágenes, devuelve un genérico para que no se rompa
                                    return __BASE_URL__ . 'img/social/' . $networkName . '.png';
                                }
                            }

                            // Verifica si se subió desde el panel de Canibal (respeta la extensión subida). 
                            // Si no, escanea la carpeta /img/social/ buscando cualquier formato válido.
                            $imgFb = !empty($tplData['soc_fb_img']) ? __BASE_URL__.'templates/canibal/img/'.$tplData['soc_fb_img'] : getSocialFallback('facebook');
                            $imgDc = !empty($tplData['soc_dc_img']) ? __BASE_URL__.'templates/canibal/img/'.$tplData['soc_dc_img'] : getSocialFallback('discord');
                            $imgWp = !empty($tplData['soc_wp_img']) ? __BASE_URL__.'templates/canibal/img/'.$tplData['soc_wp_img'] : getSocialFallback('whatsapp');
                            $imgYt = !empty($tplData['soc_yt_img']) ? __BASE_URL__.'templates/canibal/img/'.$tplData['soc_yt_img'] : getSocialFallback('youtube');
                        ?>
                        <div class="row g-2 justify-content-center">
                            <?php if(!empty($linkFB)): ?>
                            <div class="col-6 col-md-3">
                                <a href="<?php echo $linkFB; ?>" target="_blank" class="d-block hover-scale" style="transition: transform 0.3s;" onmouseover="this.style.transform='scale(1.05)';" onmouseout="this.style.transform='scale(1)';">
                                    <img src="<?php echo $imgFb; ?>" class="img-fluid rounded border border-secondary w-100" alt="Facebook">
                                </a>
                            </div>
                            <?php endif; ?>

                            <?php if(!empty($linkDC)): ?>
                            <div class="col-6 col-md-3">
                                <a href="<?php echo $linkDC; ?>" target="_blank" class="d-block hover-scale" style="transition: transform 0.3s;" onmouseover="this.style.transform='scale(1.05)';" onmouseout="this.style.transform='scale(1)';">
                                    <img src="<?php echo $imgDc; ?>" class="img-fluid rounded border border-secondary w-100" alt="Discord">
                                </a>
                            </div>
                            <?php endif; ?>

                            <?php if(!empty($linkWP)): ?>
                            <div class="col-6 col-md-3">
                                <a href="<?php echo $linkWP; ?>" target="_blank" class="d-block hover-scale" style="transition: transform 0.3s;" onmouseover="this.style.transform='scale(1.05)';" onmouseout="this.style.transform='scale(1)';">
                                    <img src="<?php echo $imgWp; ?>" class="img-fluid rounded border border-secondary w-100" alt="WhatsApp">
                                </a>
                            </div>
                            <?php endif; ?>

                            <?php if(!empty($linkYT)): ?>
                            <div class="col-6 col-md-3">
                                <a href="<?php echo $linkYT; ?>" target="_blank" class="d-block hover-scale" style="transition: transform 0.3s;" onmouseover="this.style.transform='scale(1.05)';" onmouseout="this.style.transform='scale(1)';">
                                    <img src="<?php echo $imgYt; ?>" class="img-fluid rounded border border-secondary w-100" alt="YouTube" onerror="this.src='<?php echo __BASE_URL__; ?>img/social/discord.jpg';"> 
                                </a>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if(isset($bmActive) && $bmActive): ?>

                    <style>
                        /* CSS ESPECÍFICO PARA EL TICKER DEL BLACK MARKET */
                        .market-ticker-wrap { width: 100%; overflow: hidden; position: relative; padding: 15px 0; }
                        .market-ticker { display: flex; gap: 20px; width: fit-content; animation: ticker 40s linear infinite; }
                        .market-ticker:hover { animation-play-state: paused; }
                        @keyframes ticker { 0% { transform: translateX(0); } 100% { transform: translateX(-50%); } }

                        .c-card-mini {
                            width: 260px; height: 100px; flex-shrink: 0; 
                            background: rgba(17, 20, 24, 0.8); border: 1px solid #2d323e; border-radius: 8px; padding: 12px;
                            display: flex; align-items: center; gap: 15px; cursor: pointer; transition: all 0.3s ease; position: relative; overflow: visible !important; box-shadow: 0 5px 15px rgba(0,0,0,0.4);
                        }
                        .c-card-mini:hover { border-color: #c8a165; transform: scale(1.03); background: #111418; z-index: 50; box-shadow: 0 5px 20px rgba(200, 161, 101, 0.2); }
                        
                        .c-mini-img-box { width: 60px; height: 60px; background: radial-gradient(circle, #2d323e 0%, #0b0d10 100%); border: 1px solid #1a1d24; border-radius: 6px; display: flex; align-items: center; justify-content: center; }
                        .c-mini-img-box img { max-width: 90%; max-height: 90%; object-fit: contain; }
                        
                        .c-mini-info { flex: 1; display: flex; flex-direction: column; overflow: hidden; justify-content: center; text-align: left; }
                        .c-mini-name { font-size: 13px; font-weight: bold; color: #fff; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; margin-bottom: 5px; }
                        .c-mini-price { font-size: 12px; color: #c8a165; font-family: 'Roboto', sans-serif; font-weight: bold; }
                    </style>

                    <div class="home-market-section mb-5">
                        <div class="text-center py-2 mb-3" style="background: #0b0d10; border: 1px solid #2d323e; border-radius: 4px;">
                            <h5 class="m-0" style="color: #c8a165; font-size: 15px; font-weight: bold; font-family: 'Cinzel', serif;">
                                <i class="fa fa-mask me-2"></i> Black Market
                            </h5>
                        </div>
                        
                        <div class="p-3 text-center" style="background: rgba(17, 20, 24, 0.8); border: 1px solid #2d323e; border-radius: 8px; box-shadow: 0 5px 15px rgba(0,0,0,0.4);">
                            
                            <div class="market-ticker-wrap mb-3">
                                <div class="market-ticker" id="market-ticker-inner">
                                    <div class="text-center text-muted w-100 py-3">
                                        <i class="fa fa-spinner fa-spin fa-2x mb-2" style="color: #c8a165;"></i><br>
                                        <small>Cargando ítems...</small>
                                    </div>
                                </div>
                            </div>

                            <div style="border-top: 1px solid rgba(255,255,255,0.05); padding-top: 15px;">
                                <button onclick="openMarketModal()" class="btn fw-bold px-4 py-2 w-100" style="background: transparent; border: 1px solid #c8a165; color: #c8a165; border-radius: 4px; transition: all 0.3s;" onmouseover="this.style.background='rgba(200, 161, 101, 0.1)';" onmouseout="this.style.background='transparent';">
                                    ENTRAR AL MERCADO <i class="fa fa-arrow-right ms-2"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <script>
                    document.addEventListener("DOMContentLoaded", function() {
                        const formData = new FormData();
                        formData.append('market_ajax', 'true');
                        formData.append('action', 'load_widget');

                        fetch('<?php echo __BASE_URL__; ?>index.php', {
                            method: 'POST',
                            body: formData,
                            headers: { 'X-Requested-With': 'XMLHttpRequest' }
                        })
                        .then(response => response.text())
                        .then(text => {
                            const tickerContainer = document.getElementById('market-ticker-inner');
                            try {
                                const res = JSON.parse(text);
                                if (res.status === 'success' && res.data && res.data.length > 0) {
                                    let itemsHtml = '';
                                    const duplicatedData = res.data.concat(res.data);

                                    duplicatedData.forEach((item) => {
                                        const currencyIcon = item.currency === 'Credits' ? 'fa-coins text-warning' : 'fa-gem text-info';
                                        const safeTooltip = item.tooltip.replace(/"/g, '&quot;');

                                        itemsHtml += `
                                            <div class="c-card-mini" 
                                                 onclick="openDirectBuy(${item.id}, '${item.price} ${item.currency}')"
                                                 data-tooltip="${safeTooltip}"
                                                 onmouseover="idx_show_tt(this)" 
                                                 onmousemove="idx_move_tt(event)" 
                                                 onmouseleave="idx_hide_tt()">
                                                <div class="c-mini-img-box"><img src="${item.image}" alt="Item"></div>
                                                <div class="c-mini-info">
                                                    <div class="c-mini-name">${item.name} <span style="color:#2ecc71;">${item.level}</span></div>
                                                    <div class="c-mini-price"><i class="fa ${currencyIcon} me-1"></i> ${item.price} <span style="opacity:0.7; font-size:10px;">${item.currency}</span></div>
                                                </div>
                                            </div>
                                        `;
                                    });
                                    tickerContainer.innerHTML = itemsHtml;
                                } else if (res.status === 'error') {
                                    tickerContainer.innerHTML = '<div class="text-center text-danger w-100 py-3" style="font-size:13px;"><i class="fa fa-exclamation-triangle mb-2"></i><br>' + res.message + '</div>';
                                    tickerContainer.style.animation = 'none';
                                } else {
                                    tickerContainer.innerHTML = '<div class="text-center text-muted w-100 py-3"><i class="fa fa-box-open fa-2x mb-2 opacity-25"></i><br>El mercado está vacío.</div>';
                                    tickerContainer.style.animation = 'none';
                                }
                            } catch(e) {
                                tickerContainer.innerHTML = '<div class="text-center text-danger w-100 py-3" style="font-size: 12px;"><i class="fa fa-bug mb-2"></i><br>Error interno.</div>';
                                tickerContainer.style.animation = 'none';
                            }
                        }).catch(err => {
                            document.getElementById('market-ticker-inner').innerHTML = '<div class="text-center text-danger w-100 py-3" style="font-size: 13px;"><i class="fa fa-wifi mb-2"></i><br>Fallo de red.</div>';
                            document.getElementById('market-ticker-inner').style.animation = 'none';
                        });
                    });
                    </script>
                    <?php endif; ?>

                    <?php endif; ?> </div>
            </div>

            <div class="sidebar-column">
                <?php include 'templates/canibal/inc/modules/sidebar.php'; ?>
            </div>

        </div>

        <?php if(empty($_REQUEST['page'])): ?>
        
        <style>
            #global-tt {
                position: fixed; display: none; z-index: 1000000;
                background: rgba(10, 10, 12, 0.98); border: 1px solid #c8a165; color: #ffffff;
                padding: 15px; border-radius: 6px; pointer-events: none;
                box-shadow: 0 0 20px rgba(0,0,0,0.8); width: 220px;
                font-size: 12px; text-align: center;
            }
            #global-tt .tt-name { font-size: 14px; font-weight: bold; border-bottom: 1px solid #444; padding-bottom: 5px; margin-bottom: 5px; display: block; }
        </style>
        <div id="global-tt" style="display:none;"></div>
        
        <style>
            .news-modal-overlay { 
                display: none; position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; 
                background: rgba(0,0,0,0.95); z-index: 2147483647 !important;
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

        <div id="readNewsModal" class="news-modal-overlay" style="display:none;">
            <div class="news-modal-box">
                <button class="btn-news-close" onclick="closeNewsModal()"><i class="fa fa-times"></i></button>
                <div id="readNewsTitle" class="news-modal-title">Título de la Noticia</div>
                <div id="readNewsDate" class="news-modal-date"><i class="fa fa-calendar-alt me-1"></i> Fecha</div>
                <div id="readNewsContent" class="news-modal-content">
                    Cargando contenido...
                </div>
            </div>
        </div>

        <?php if(isset($bmActive) && $bmActive): ?>
        <style>
            /* CSS DEL MODAL WORKSPACE ADAPTADO A CANIBAL */
            .ws-modal-overlay { display: none; position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(0,0,0,0.9); z-index: 99999; align-items: center; justify-content: center; backdrop-filter: blur(5px); }
            .ws-workspace { display: flex; width: 90%; max-width: 1200px; height: 80vh; background: #0b0d10; border: 1px solid #2d323e; border-radius: 8px; overflow: hidden; box-shadow: 0 0 50px rgba(0,0,0,0.8); }
            .ws-sidebar-left { width: 220px; background: #111418; border-right: 1px solid #2d323e; display: flex; flex-direction: column; }
            .cat-header { padding: 15px; font-weight: bold; color: #c8a165; border-bottom: 1px solid #2d323e; font-family: 'Cinzel', serif; text-transform: uppercase; letter-spacing: 1px; }
            .cat-list { flex: 1; overflow-y: auto; padding: 10px; }
            .cat-btn { width: 100%; text-align: left; background: transparent; border: none; padding: 10px; color: #888; font-size: 13px; border-radius: 4px; cursor: pointer; transition: 0.2s; display: flex; align-items: center; gap: 10px; border-bottom: 1px solid #1a1d24; }
            .cat-btn:hover, .cat-btn.active { background: rgba(200, 161, 101, 0.1); color: #fff; border-left: 3px solid #c8a165; }
            
            .ws-center-panel { flex: 1; display: flex; flex-direction: column; position: relative; background: #0b0d10; }
            .center-toolbar { padding: 10px 20px; border-bottom: 1px solid #2d323e; display: flex; align-items: center; background: #111418; }
            .search-wrapper { position: relative; width: 300px; }
            .search-icon { position: absolute; left: 10px; top: 50%; transform: translateY(-50%); color: #888; font-size: 12px; }
            .search-input { width: 100%; background: #0b0d10; border: 1px solid #2d323e; padding: 6px 10px 6px 30px; border-radius: 4px; color: #fff; font-size: 13px; outline: none; transition: border 0.3s; }
            .search-input:focus { border-color: #c8a165; }
            
            .item-grid-container { flex: 1; overflow-y: auto; padding: 20px; display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 15px; align-content: start; }
            .ws-item-card { background: rgba(17, 20, 24, 0.8); border: 1px solid #2d323e; border-radius: 6px; padding: 10px; display: flex; flex-direction: column; align-items: center; text-align: center; transition: 0.2s; cursor: pointer; position: relative; }
            .ws-item-card:hover { border-color: #c8a165; transform: translateY(-3px); background: #111418; box-shadow: 0 5px 15px rgba(200, 161, 101, 0.1); }
            .ws-card-img { width: 60px; height: 60px; object-fit: contain; background: radial-gradient(circle, #2d323e 0%, #0b0d10 100%); border: 1px solid #1a1d24; border-radius: 4px; margin-bottom: 10px; }
            .ws-card-name { font-size: 12px; font-weight: bold; color: #fff; margin-bottom: 5px; width: 100%; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
            .ws-card-price { font-size: 12px; color: #c8a165; font-family: 'Roboto', sans-serif; font-weight: bold; margin-bottom: 10px; }
            .ws-card-btn { width: 100%; padding: 5px; font-size: 11px; background: transparent; border: 1px solid #c8a165; border-radius: 3px; color: #c8a165; font-weight: bold; transition: 0.3s; }
            .ws-card-btn:hover { background: rgba(200, 161, 101, 0.2); color: #fff; }

            .btn-ws-close { position: absolute; top: 10px; right: 20px; background: transparent; border: none; color: #888; cursor: pointer; font-size: 20px; transition: 0.3s; z-index: 10001; }
            .btn-ws-close:hover { color: #fff; transform: rotate(90deg); }
            
            .loading-overlay { position: absolute; top:0; left:0; width:100%; height:100%; background: rgba(11, 13, 16, 0.8); display: none; align-items: center; justify-content: center; flex-direction: column; z-index: 50; }
            
            /* MODALES DE CONFIRMACIÓN CANIBAL */
            .confirm-modal-overlay, .alert-modal-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.95); z-index: 100000; align-items: center; justify-content: center; }
            .confirm-box { background: #111418; border: 1px solid #c8a165; padding: 30px; border-radius: 8px; text-align: center; width: 400px; color: #fff; box-shadow: 0 0 30px rgba(200, 161, 101, 0.15); }
            .confirm-title { color: #c8a165; font-family: 'Cinzel', serif; font-weight: bold; border-bottom: 1px solid #2d323e; padding-bottom: 10px; margin-bottom: 15px; font-size: 18px; }
            .confirm-body { font-size: 13px; color: #ccc; margin-bottom: 20px; }
            .confirm-btns { display: flex; gap: 15px; justify-content: center; }
            .btn-confirm-yes { background: #c8a165; color: #111418; border: none; padding: 10px 20px; border-radius: 4px; font-weight: bold; cursor: pointer; transition: 0.3s; }
            .btn-confirm-yes:hover { filter: brightness(1.2); }
            .btn-confirm-no { background: transparent; border: 1px solid #444; color: #888; padding: 10px 20px; border-radius: 4px; font-weight: bold; cursor: pointer; transition: 0.3s; }
            .btn-confirm-no:hover { background: #222; color: #fff; border-color: #555; }
        </style>

        <div id="wsMarketModal" class="ws-modal-overlay" style="display:none;">
            <div class="ws-workspace">
                <button class="btn-ws-close" onclick="closeMarketModal()"><i class="fa fa-times"></i></button>
                <div class="ws-sidebar-left">
                    <div class="cat-header">Categorías</div>
                    <div class="cat-list">
                        <?php 
                        $cats = [
                            -1=>['Ver Todos', 'fa-globe'], 0=>['Espadas', 'fa-khanda'], 1=>['Hachas', 'fa-gavel'], 2=>['Mazas', 'fa-hammer'], 
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
                            <input type="text" id="wsSearch" class="search-input" placeholder="Buscar items..." onkeyup="wsFilterGrid()">
                        </div>
                    </div>
                    
                    <div class="item-grid-container" id="wsItemGrid">
                        <div style="grid-column: 1 / -1; text-align: center; margin-top: 150px; color: #444;">
                            <i class="fa fa-arrow-left" style="font-size: 40px; margin-bottom: 15px; opacity: 0.5;"></i><br>
                            <p style="font-size: 14px;">Selecciona una categoría.</p>
                        </div>
                    </div>
                    
                    <div class="loading-overlay" id="wsLoader">
                        <i class="fa fa-spinner fa-spin fa-3x" style="color: #c8a165; margin-bottom: 10px;"></i>
                        <span style="color:#fff; font-size:12px; font-weight:bold;">CARGANDO...</span>
                    </div>
                </div>
            </div>
        </div>

        <div id="alertModal" class="alert-modal-overlay" style="display:none;">
            <div class="confirm-box">
                <h3 id="alertTitle" class="confirm-title">Aviso</h3>
                <p id="alertBody" class="confirm-body">Mensaje</p>
                <div class="confirm-btns">
                    <button class="btn-confirm-yes" onclick="document.getElementById('alertModal').style.display='none'">Aceptar</button>
                </div>
            </div>
        </div>

        <div id="buyConfirmModal" class="confirm-modal-overlay" style="display:none;">
            <div class="confirm-box">
                <div class="confirm-title">Confirmar Compra</div>
                <div class="confirm-body">
                    ¿Deseas comprar este item por <span id="buyPriceLabel" style="color:#c8a165; font-weight:bold;"></span>?
                    <br><br>
                    <small style="color:#e74c3c;"><i class="fa fa-exclamation-triangle"></i> Tu baúl debe estar completamente <b>VACÍO</b>.</small>
                </div>
                <div class="confirm-btns">
                    <button class="btn-confirm-no" onclick="document.getElementById('buyConfirmModal').style.display='none'">Cancelar</button>
                    <button class="btn-confirm-yes" id="btnDoBuy">COMPRAR</button>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <script>
            // Funciones de Tooltip
            function idx_show_tt(el) {
                var tt = document.getElementById('global-tt');
                if(!tt) return;
                tt.innerHTML = el.getAttribute('data-tooltip');
                tt.style.display = 'block';
            }
            function idx_move_tt(e) {
                var tt = document.getElementById('global-tt');
                if(!tt || tt.style.display !== 'block') return;
                tt.style.left = (e.clientX + 15) + 'px';
                tt.style.top = (e.clientY + 15) + 'px';
            }
            function idx_hide_tt() {
                var tt = document.getElementById('global-tt');
                if(tt) tt.style.display = 'none';
            }

            // Funciones del Modal General Mercado
            function openMarketModal() { document.getElementById('wsMarketModal').style.display = 'flex'; }
            function closeMarketModal() { document.getElementById('wsMarketModal').style.display = 'none'; }

            function show_modal_alert(title, msg) {
                document.getElementById('alertTitle').innerText = title;
                document.getElementById('alertBody').innerHTML = msg;
                document.getElementById('alertModal').style.display = 'flex';
            }

            var bm_selected_id = -1;
            function openDirectBuy(id, priceLabel) {
                if(window.event) window.event.stopPropagation();
                bm_selected_id = id;
                document.getElementById('buyPriceLabel').innerText = priceLabel;
                document.getElementById('btnDoBuy').onclick = function() { executeBuy(); };
                document.getElementById('buyConfirmModal').style.display = 'flex';
            }

            function bm_buy_loader(show) {
                var el = document.getElementById('bmBuyLoader');
                if(!el) {
                    el = document.createElement('div');
                    el.id = 'bmBuyLoader';
                    el.style.cssText = 'display:none;position:fixed;top:0;left:0;width:100vw;height:100vh;background:rgba(0,0,0,0.85);z-index:100001;align-items:center;justify-content:center;flex-direction:column;';
                    el.innerHTML = '<i class="fa fa-spinner fa-spin fa-3x" style="color:#c8a165;margin-bottom:12px;"></i><span style="color:#fff;font-size:13px;font-weight:bold;letter-spacing:1px;">PROCESANDO COMPRA...</span>';
                    document.body.appendChild(el);
                }
                el.style.display = show ? 'flex' : 'none';
            }

            function executeBuy() {
                document.getElementById('buyConfirmModal').style.display = 'none';
                bm_buy_loader(true);

                var fd = new FormData();
                fd.append('market_ajax', 'true');
                fd.append('action', 'buy_item');
                fd.append('id', bm_selected_id);

                fetch('<?php echo __BASE_URL__; ?>index.php', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(res => {
                    bm_buy_loader(false);
                    if(res.status === 'success') {
                        show_modal_alert("Éxito", "¡Compra realizada! Revisa tu baúl.");
                        var activeCat = document.querySelector('.cat-btn.active');
                        if(activeCat) activeCat.click();
                        setTimeout(function(){ location.reload(); }, 2000);
                    } else {
                        show_modal_alert("Error", res.message);
                    }
                })
                .catch(function() {
                    bm_buy_loader(false);
                    show_modal_alert("Error", "No se pudo procesar la compra. Intenta de nuevo.");
                });
            }

            function wsLoadCat(catId, btn) {
                document.querySelectorAll('.cat-btn').forEach(b => b.classList.remove('active'));
                if(btn) btn.classList.add('active');
                
                document.getElementById('wsLoader').style.display = 'flex';
                var grid = document.getElementById('wsItemGrid');
                grid.innerHTML = '';

                var fd = new FormData();
                fd.append('market_ajax', 'true');
                fd.append('action', 'load_category');
                fd.append('category', catId);

                fetch('<?php echo __BASE_URL__; ?>index.php', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(res => {
                    document.getElementById('wsLoader').style.display = 'none';
                    if(res.status === 'success') {
                        if(res.data.length === 0) {
                            grid.innerHTML = '<div style="grid-column: 1/-1; text-align:center; color:#666; margin-top:50px;">Categoría vacía.</div>';
                            return;
                        }
                        res.data.forEach(item => {
                            var safeTt = item.tooltip.replace(/"/g, '&quot;');
                            var div = document.createElement('div');
                            div.className = 'ws-item-card';
                            div.setAttribute('data-name', item.clean_name.toLowerCase());
                            div.setAttribute('data-tooltip', safeTt);
                            div.onmouseenter = function() { idx_show_tt(this); };
                            div.onmousemove = function(e) { idx_move_tt(e); };
                            div.onmouseleave = function() { idx_hide_tt(); };
                            
                            var currencyIcon = (item.currency == 'Credits') ? '<i class="fa fa-coins"></i>' : '<i class="fa fa-gem"></i>';
                            
                            div.innerHTML = `
                                <img src="${item.img}" class="ws-card-img">
                                <div class="ws-card-name">${item.clean_name}</div>
                                <div class="ws-card-price"><i class="fa ${item.currency == 'Credits' ? 'fa-coins' : 'fa-gem'}"></i> ${new Intl.NumberFormat().format(item.price)}</div>
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

            // FUNCIONES MODAL DE NOTICIAS
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
        <?php endif; ?>

        <?php include 'templates/canibal/inc/modules/footer.php'; ?>

    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        const baseUrl = '<?php echo __BASE_URL__; ?>';
    </script>

    <script src="<?php echo __BASE_URL__; ?>templates/canibal/js/main.js?v=<?php echo time(); ?>"></script>
</body>
</html>