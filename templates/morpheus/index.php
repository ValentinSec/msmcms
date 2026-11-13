<?php
// 1. Buffer de Salida (CRÍTICO: No tocar, permite que el Login funcione)
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

// Versión de archivos estáticos = fecha de modificación.
// El navegador los guarda y solo los vuelve a pedir cuando el archivo cambia.
if(!function_exists('msm_v')) {
    function msm_v($relPath) {
        $t = @filemtime($relPath);
        return $t ? $t : 1;
    }
}

// ====================================================================================
// [MODIFICADO] MÓDULO DE SEGURIDAD VISUAL (IP BLOCK & DDOS) - VERSIÓN FINAL
// ====================================================================================
$secConfigFile = __PATH_INCLUDES__ . 'config/security_config.json';
if (file_exists($secConfigFile)) {
    $secConfig = json_decode(file_get_contents($secConfigFile), true);
    $userIp = $_SERVER['REMOTE_ADDR'];
    
    if (!function_exists('show_access_denied')) {
        function show_access_denied($title, $msg, $ip) {
            // 1. LIMPIEZA TOTAL DEL BUFFER (Borra todo lo cargado antes)
            while (ob_get_level() > 0) {
                @ob_end_clean();
            }
            
            // 2. HTML + CSS INCRUSTADO (Blindado)
            ?>
            <!DOCTYPE html>
            <html lang="es">
            <head>
                <meta charset="UTF-8">
                <title>Acceso Restringido</title>
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
                <style>
                    /* Reset Total */
                    * { box-sizing: border-box; margin: 0; padding: 0; }
                    body, html {
                        width: 100%; height: 100%;
                        background-color: #050505 !important;
                        font-family: 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
                        overflow: hidden;
                    }
                    /* Overlay de Seguridad */
                    .security-overlay {
                        position: fixed; top: 0; left: 0; right: 0; bottom: 0;
                        width: 100vw; height: 100vh;
                        background: radial-gradient(circle at center, #1a0a0a 0%, #000000 100%);
                        z-index: 9999999; /* Máxima prioridad */
                        display: flex; align-items: center; justify-content: center;
                        padding: 20px;
                    }
                    /* Tarjeta */
                    .security-card {
                        background: rgba(20, 20, 20, 0.95);
                        border: 1px solid #d32f2f;
                        border-top: 4px solid #d32f2f;
                        box-shadow: 0 0 50px rgba(211, 47, 47, 0.2);
                        padding: 40px;
                        border-radius: 8px;
                        text-align: center;
                        max-width: 480px;
                        width: 100%;
                        color: #fff;
                        animation: fadeIn 0.5s ease-out;
                    }
                    .sec-icon {
                        font-size: 50px; color: #d32f2f; margin-bottom: 20px;
                        display: inline-block;
                        animation: pulse 2s infinite;
                    }
                    .sec-title {
                        font-size: 24px; font-weight: 700; text-transform: uppercase;
                        margin-bottom: 15px; letter-spacing: 1px; color: #fff;
                    }
                    .sec-msg {
                        font-size: 14px; color: #aaa; line-height: 1.5; margin-bottom: 25px;
                    }
                    .sec-ip {
                        background: rgba(0, 0, 0, 0.3); border: 1px solid #333;
                        padding: 8px 15px; border-radius: 4px;
                        font-family: monospace; color: #d32f2f; font-size: 13px;
                        display: inline-block; margin-bottom: 20px;
                    }
                    .sec-btn {
                        display: block; width: 100%; padding: 12px;
                        background: #1a1a1a; border: 1px solid #444; color: #ccc;
                        text-decoration: none; font-size: 13px; border-radius: 4px;
                        transition: 0.3s; cursor: pointer;
                    }
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
                        <a href="javascript:location.reload()" class="sec-btn">
                            <i class="fa fa-rotate-right"></i> REINTENTAR CONEXIÓN
                        </a>
                    </div>
                </div>
            </body>
            </html>
            <?php
            // 3. Forzar salida y matar script
            flush();
            exit(); 
        }
    }
    
    // 1. Bloqueo de IP
    if (!empty($secConfig['blocked_ips'])) {
        $blockedList = explode("\n", str_replace("\r", "", $secConfig['blocked_ips']));
        if (in_array(trim($userIp), array_map('trim', $blockedList))) {
            // Nota: Quitamos el header 403 para evitar que el navegador oculte el HTML
            show_access_denied("ACCESO RESTRINGIDO", "Tu dirección IP ha sido bloqueada permanentemente por la administración.", $userIp);
        }
    }
    
    // 2. Anti-DDoS (Rate Limiting)
    if (isset($secConfig['ddos_active']) && $secConfig['ddos_active'] == 1) {
        if (!session_id()) @session_start();
        $limit = $secConfig['ddos_limit'] ?? 10; 
        $now = microtime(true);
        
        if (isset($_SESSION['ddos_penalty_until']) && $now < $_SESSION['ddos_penalty_until']) {
            $wait = ceil($_SESSION['ddos_penalty_until'] - $now);
            show_access_denied("TRÁFICO ALTO", "Hemos detectado demasiadas solicitudes. Por favor espera $wait segundos.", $userIp);
        }
        
        if (!isset($_SESSION['last_request_micro'])) {
            $_SESSION['last_request_micro'] = $now;
            $_SESSION['req_count'] = 1;
        } else {
            $timeDiff = $now - $_SESSION['last_request_micro'];
            if ($timeDiff < 0.5) { 
                $_SESSION['req_count']++;
                if ($_SESSION['req_count'] > $limit) {
                    $_SESSION['ddos_penalty_until'] = $now + 10;
                    show_access_denied("SEGURIDAD ACTIVADA", "Sistema de protección anti-flood activado temporalmente.", $userIp);
                }
            } else {
                $_SESSION['last_request_micro'] = $now;
                $_SESSION['req_count'] = 1;
            }
        }
    }
}

// ====================================================================================
// FUNCIONES AUXILIARES (INTOCABLES)
// ====================================================================================

if (!function_exists('custom_config')) {
    function custom_config($key) {
        $path = __DIR__ . '/../../includes/config/custom_settings.json';
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

if(!function_exists('getClass')) {
    function getClass($code) {
        $code = (int)$code;
        if ($code < 16) return 'DW'; if ($code < 32) return 'DK'; if ($code < 48) return 'FE';
        if ($code < 64) return 'MG'; if ($code < 80) return 'DL'; if ($code < 96) return 'SU';
        if ($code < 112) return 'RF'; return 'GL';
    }
}

// Parser de Items Global con SOPORTE DE SOCKETS
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
// [LOGIN FUNCIONAL]
// ====================================================================================
// En /login el formulario lo procesa modules/login.php: no se valida dos veces
// (cada validacion fallida cuenta como un intento).
$msmIsLoginPage = (isset($_REQUEST['page']) && $_REQUEST['page'] === 'login');
if(!$msmIsLoginPage && ((isset($_POST['msmLogin_user']) && !empty($_POST['msmLogin_user'])) || isset($_POST['msmLogin_submit']))) {
    $_POST['msmLogin_submit'] = 'submit';
    try {
        if(class_exists('login')) {
            $userLogin = new login();
            $userLogin->validateLogin($_POST['msmLogin_user'], $_POST['msmLogin_pwd']);
            ob_clean();
            header("Location: " . __BASE_URL__ . "usercp");
            exit;
        }
    } catch (Throwable $ex) { }
}

// ====================================================================================
// HANDLERS AJAX (Ranking y Mercado)
// ====================================================================================

if((isset($_POST['market_ajax']) && $_POST['market_ajax'] == 'true') || (isset($_POST['ranking_ajax']) && $_POST['ranking_ajax'] == 'true')) {
    while (ob_get_level()) ob_end_clean();
    header('Content-Type: application/json');
    
    try {
        $db = Connection::Database('MuOnline');

        // --- AJAX RANKING ---
        if(isset($_POST['ranking_ajax'])) {
            $type = $_POST['type'];
            
            $ver = custom_config('server_version');
            $allowed = [];
            if (strpos($ver, '97') !== false) { $allowed = [0,1,2, 16,17,18, 32,33,34]; } 
            elseif (strpos($ver, '99') !== false || stripos($ver, 'Season 1') !== false || stripos($ver, 'Season 2') !== false) { $allowed = [0,1,2, 16,17,18, 32,33,34, 48,50, 64,65,66]; } 
            elseif (stripos($ver, 'Season 3') !== false || stripos($ver, 'Season 4') !== false || stripos($ver, 'Season 5') !== false) { $allowed = [0,1,2, 16,17,18, 32,33,34, 48,50, 64,65,66, 80,81,82,83]; } 
            
            $classWhere = "";
            if(!empty($allowed)) { $classWhere = "AND Class IN (" . implode(',', $allowed) . ")"; }

            $sql = "";
            if($type == 'Resets') $sql = "SELECT TOP 3 Name, ResetCount as Val, Class FROM Character WHERE CtlCode < 32 $classWhere ORDER BY ResetCount DESC, cLevel DESC";
            elseif($type == 'Level') $sql = "SELECT TOP 3 Name, cLevel as Val, Class FROM Character WHERE CtlCode < 32 $classWhere ORDER BY cLevel DESC, Experience DESC";
            elseif($type == 'Guilds') $sql = "SELECT TOP 3 G_Name as Name, G_Score as Val, G_Mark FROM Guild ORDER BY G_Score DESC";
            elseif($type == 'Killers') {
                $t = custom_config('table_kills') ?: 'Character'; $c = custom_config('col_kills') ?: 'PkCount';
                $sql = "SELECT TOP 3 Name, $c as Val, Class FROM $t WHERE CtlCode < 32 $classWhere ORDER BY $c DESC";
            }
            elseif ($type == 'Gens') {
                try {
                    $check = $db->query_fetch_single("SELECT TOP 1 * FROM Gens_Rank");
                    if($check) $sql = "SELECT TOP 3 Name, Contribution as Val, Family FROM Gens_Rank ORDER BY Contribution DESC";
                    else throw new Exception();
                } catch(Exception $e) {
                    $sql = "SELECT TOP 3 Name, GensContribution as Val, GensFamily as Family, Class FROM Character WHERE GensFamily > 0 $classWhere ORDER BY GensContribution DESC";
                }
            }
            elseif ($type == 'Duels') {
                $t = custom_config('table_duels') ?: 'RankingDuel'; $c_win = custom_config('col_duel_win') ?: 'WinScore'; $c_n = custom_config('col_duel_name') ?: 'Name';
                $sql = "SELECT TOP 3 t.$c_n as Name, t.$c_win as Val, c.Class FROM $t t JOIN Character c ON t.$c_n = c.Name WHERE c.CtlCode < 32 $classWhere ORDER BY t.$c_win DESC";
            }
            elseif ($type == 'ELO') {
                $sql = "SELECT TOP 3 Name, EloScore as Val, Class FROM Character WHERE CtlCode < 32 $classWhere ORDER BY EloScore DESC";
            }
            elseif ($type == 'Bosses') {
                $sql = "SELECT TOP 3 Name, MonsterKillCount as Val, Class FROM Character WHERE CtlCode < 32 $classWhere ORDER BY MonsterKillCount DESC";
            }
            elseif (in_array($type, ['bc','ds','cc'])) {
                $t_map = ['bc'=>'RankingBloodCastle', 'ds'=>'RankingDevilSquare', 'cc'=>'RankingChaosCastle'];
                $t = custom_config('table_event_'.$type) ?: $t_map[$type];
                $c_n = custom_config('col_event_name') ?: 'Name'; $c_s = custom_config('col_event_score') ?: 'Score';
                $sql = "SELECT TOP 3 t.$c_n as Name, t.$c_s as Val, c.Class FROM $t t JOIN Character c ON t.$c_n = c.Name WHERE c.CtlCode < 32 $classWhere ORDER BY t.$c_s DESC";
            }

            $data = [];
            if($sql) {
                $rows = $db->query_fetch($sql);
                if(is_array($rows)) {
                    foreach($rows as $r) {
                        $img = "";
                        if($type == 'Guilds') {
                            $img = '<img src="'.__BASE_URL__.'api/guildmark.php?data='.bin2hex($r['G_Mark']).'&size=20">';
                        } elseif ($type == 'Gens' && isset($r['Family'])) {
                            $fam = $r['Family']; 
                            $img = ($fam == 1) ? '<span style="color:#00deff; font-weight:bold;">D</span>' : '<span style="color:#ff0000; font-weight:bold;">V</span>';
                        } else {
                            $fname = getClassImageName($r['Class']);
                            $url = __BASE_URL__ . 'img/character-avatars/' . $fname . '.jpg';
                            $img = "<img src='$url' style='width:25px;height:25px;border-radius:50%;border:1px solid #444;object-fit:cover;' onerror=\"this.onerror=null;this.src='".__BASE_URL__."img/character-avatars/avatar.jpg'\">";
                        }
                        $data[] = ['name'=>$r['Name'], 'val'=>number_format($r['Val']), 'race_html'=>$img];
                    }
                }
            }
            echo json_encode(['status'=>'success', 'data'=>$data]);
            exit;
        }

        // --- AJAX MARKET ---
        if(isset($_POST['market_ajax'])) {
            include_once('includes/config/items_manager.php');
            $bmAction = isset($_POST['action']) ? $_POST['action'] : '';

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
                echo json_encode(['status' => 'success', 'data' => $data], JSON_INVALID_UTF8_SUBSTITUTE);
                exit;
            }

            if($bmAction == 'load_category') {
                $data = BlackMarket::getMarketItems(isset($_POST['category']) ? $_POST['category'] : -1);
                echo json_encode(['status' => 'success', 'data' => $data], JSON_INVALID_UTF8_SUBSTITUTE);
                exit;
            }

            if($bmAction == 'buy_item') {
                if(!isLoggedIn()) throw new Exception("Inicia sesi\u{00F3}n.");
                $result = BlackMarket::buyItem($_SESSION['username'], isset($_POST['id']) ? $_POST['id'] : 0);
                echo json_encode($result, JSON_INVALID_UTF8_SUBSTITUTE);
                exit;
            }

            echo json_encode(['status' => 'error', 'message' => 'Accion no valida.']);
            exit;
        }

    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        exit;
    }
}

// 8. LOGICA USERCP
if(isset($_REQUEST['page']) && $_REQUEST['page'] == 'usercp') {
    if(!defined('USERCP_CLEAN')) define('USERCP_CLEAN', true);
    // La barra de morpheus la incluye modules/usercp.php dentro de <body>.
    // Imprimirla aqui, antes del <!DOCTYPE>, ponia al navegador en "modo quirks".
    
    $modulesPath = 'modules/usercp.php'; 
    if(!file_exists($modulesPath)) $modulesPath = __DIR__ . '/../../modules/usercp.php';
    
    if(file_exists($modulesPath)) include($modulesPath);
    else echo "Error: modules/usercp.php not found.";
    
    exit(); 
}

// 9. CONFIGURACIONES GENERALES (INDEX)
$bmConfigFile = __DIR__ . '/../../includes/config/black_market_config.json';
$bmActive = false;
if (file_exists($bmConfigFile)) {
    $bmConf = json_decode(file_get_contents($bmConfigFile), true);
    if (isset($bmConf['module_active']) && ($bmConf['module_active'] == 'on' || $bmConf['module_active'] == 1)) $bmActive = true;
}

$rankXmlPath = __DIR__ . '/../../includes/config/modules/rankings.xml';
$rankConfig = null;
if(file_exists($rankXmlPath)) $rankConfig = simplexml_load_file($rankXmlPath);

function isRankActiveLocal($key, $xml) {
    if(!$xml) return true; 
    $val = (string)$xml->$key;
    return ($val === '1' || $val === 'on' || $val === 'true');
}

$stats = ['accounts' => 0, 'chars' => 0, 'guilds' => 0];
$csActive = custom_config('active_cs');
$csInfo = ['owner' => 'Sin Dueño', 'master' => '---', 'mark' => '', 'date_str' => 'No definido'];

if(empty($_REQUEST['page'])) {

    // Totales del sitio (caché de 5 minutos: templates/misc/inc/site_counts.php)
    @include_once(__DIR__ . '/../misc/inc/site_counts.php');
    if(function_exists('msm_cached_counts')) {
        $stats = msm_cached_counts();
    }

    // Castle Siege
    if($csActive) {
        try {
            $db = Connection::Database('MuOnline');
            if(is_object($db)) {
                $siegeData = $db->query_fetch_single("SELECT OWNER_GUILD FROM MuCastle_DATA");
                if(is_array($siegeData) && !empty($siegeData['OWNER_GUILD']) && $siegeData['OWNER_GUILD'] != 'No Owner') {
                    $csInfo['owner'] = $siegeData['OWNER_GUILD'];
                    $guildData = $db->query_fetch_single("SELECT G_Master, G_Mark FROM Guild WHERE G_Name = ?", [$csInfo['owner']]);
                    if(is_array($guildData)) {
                        $csInfo['master'] = $guildData['G_Master'];
                        $csInfo['mark'] = __BASE_URL__ . 'api/guildmark.php?data=' . bin2hex($guildData['G_Mark']) . '&size=80';
                    }
                }
            }
        } catch (Throwable $e) { }

        $csDayNum = (int)custom_config('cs_day');
        $csTimeStr = custom_config('cs_time');
        $daysMap = [1 => 'Lunes', 2 => 'Martes', 3 => 'Mi&eacute;rcoles', 4 => 'Jueves', 5 => 'Viernes', 6 => 'S&aacute;bado', 7 => 'Domingo'];
        $dayName = isset($daysMap[$csDayNum]) ? $daysMap[$csDayNum] : 'Domingo';
        $csInfo['date_str'] = $dayName . ' a las ' . $csTimeStr . 'hs';
    }
}

$realOnline = 0;
$cacheFilePath = __DIR__ . '/../../includes/cache/server_info.cache';
if(file_exists($cacheFilePath)) {
    $rawCache = file_get_contents($cacheFilePath);
    $cacheData = json_decode($rawCache, true);
    if(isset($cacheData[1][0])) { $srvInfo = explode("|", $cacheData[1][0]); $realOnline = isset($srvInfo[3]) ? (int)$srvInfo[3] : 0; }
}
$multOnline = (float)custom_config('fake_online');
$displayOnline = round($realOnline * ($multOnline > 0 ? $multOnline : 1));

$tplConfigPath = 'templates/morpheus/config.json';
$tplConfig = file_exists($tplConfigPath) ? json_decode(file_get_contents($tplConfigPath), true) : [];
$heroLogo = isset($tplConfig['hero_image']) ? $tplConfig['hero_image'] : 'logo_default.png'; 
$bgImage = isset($tplConfig['background_image']) ? $tplConfig['background_image'] : 'bg_default.jpg';
$logoWidth = isset($tplConfig['hero_logo_width']) ? $tplConfig['hero_logo_width'] : '450px';

// --- FORCE LOGO SIZE FIX (Asegurar que tenga px y !important) ---
if(strpos($logoWidth, 'px') === false && strpos($logoWidth, '%') === false) { $logoWidth .= 'px'; }

// --- CONFIGURACIÓN DEL SLIDER (MORPHEUS) ---
$s1_title = isset($tplConfig['slider_1_title']) ? $tplConfig['slider_1_title'] : 'Play Now';
$s1_btn   = isset($tplConfig['slider_1_btn']) ? $tplConfig['slider_1_btn'] : 'Sign up and receive free gifts';
$s1_link  = isset($tplConfig['slider_1_link']) ? $tplConfig['slider_1_link'] : 'register';
$s1_img   = file_exists('templates/morpheus/img/slider1.jpg') ? 'slider1.jpg' : 'slider1_default.jpg';

$s2_title = isset($tplConfig['slider_2_title']) ? $tplConfig['slider_2_title'] : 'New Season';
$s2_btn   = isset($tplConfig['slider_2_btn']) ? $tplConfig['slider_2_btn'] : 'Download Now';
$s2_link  = isset($tplConfig['slider_2_link']) ? $tplConfig['slider_2_link'] : 'downloads';
$s2_img   = file_exists('templates/morpheus/img/slider2.jpg') ? 'slider2.jpg' : 'slider2_default.jpg';

$dlJsonPath = __DIR__ . '/../../includes/config/modules/downloads.json';
$dlData = file_exists($dlJsonPath) ? json_decode(file_get_contents($dlJsonPath), true) : [];
$heroDownloadLink = __BASE_URL__ . 'downloads'; 
$heroDlSubtitle = "Client Full";
$foundFull = false; $foundLite = false; $liteLink = '';
if(!empty($dlData)) {
    foreach($dlData as $dl) {
        if(isset($dl['download_type']) && $dl['download_type'] == 2) {
            $heroDownloadLink = $dl['download_link']; $heroDlSubtitle = $dl['download_hosting']; $foundFull = true; break;
        }
        if(isset($dl['download_type']) && $dl['download_type'] == 1 && !$foundLite) { $liteLink = $dl['download_link']; $foundLite = true; }
    }
    if(!$foundFull && $foundLite) { $heroDownloadLink = $liteLink; $heroDlSubtitle = "Client Lite"; }
}

$newsFile = __DIR__ . '/../../includes/config/server_news.json';
$newsData = file_exists($newsFile) ? json_decode(file_get_contents($newsFile), true) : [];
usort($newsData, function($a, $b) { return strtotime($b['date']) - strtotime($a['date']); });
$featureNews = !empty($newsData) ? $newsData[0] : null; 
$listNews = array_slice($newsData, 1, 3); 

$activeRanks = []; 
if(isRankActiveLocal('active_rank_general', $rankConfig)) { $activeRanks['Resets'] = 'Resets'; $activeRanks['Level'] = 'Level'; }
if(isRankActiveLocal('active_rank_guilds', $rankConfig)) $activeRanks['Guilds'] = 'Guilds';
if(isRankActiveLocal('active_rank_killers', $rankConfig)) $activeRanks['Killers'] = 'Killers';
if(isRankActiveLocal('active_rank_events', $rankConfig)) { $activeRanks['bc']='Blood Castle'; $activeRanks['ds']='Devil Square'; $activeRanks['cc']='Chaos Castle'; }
if(isRankActiveLocal('active_rank_gens', $rankConfig)) $activeRanks['Gens'] = 'Gens';
if(isRankActiveLocal('active_rank_duels', $rankConfig)) $activeRanks['Duels'] = 'Duels';
if(isRankActiveLocal('active_rank_bosses', $rankConfig)) $activeRanks['Bosses'] = 'Bosses';
if(isRankActiveLocal('active_rank_elo', $rankConfig)) $activeRanks['ELO'] = 'PVP ELO';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo custom_config('web_title'); ?></title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;600;700;900&family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">    
    <link href="<?php echo __BASE_URL__; ?>templates/misc/css/sidebar.css?v=<?php echo msm_v('templates/misc/css/sidebar.css'); ?>" rel="stylesheet">
    <link href="<?php echo __BASE_URL__; ?>templates/morpheus/css/main.css?v=<?php echo msm_v('templates/morpheus/css/main.css'); ?>" rel="stylesheet">
    <link href="<?php echo __BASE_URL__; ?>templates/morpheus/css/login.css?v=<?php echo msm_v('templates/morpheus/css/login.css'); ?>" rel="stylesheet">
    <link href="<?php echo __BASE_URL__; ?>templates/morpheus/css/info.css?v=<?php echo msm_v('templates/morpheus/css/info.css'); ?>" rel="stylesheet">
    <link href="<?php echo __BASE_URL__; ?>templates/morpheus/css/downloads.css?v=<?php echo msm_v('templates/morpheus/css/downloads.css'); ?>" rel="stylesheet">
    <link href="<?php echo __BASE_URL__; ?>templates/morpheus/css/rankings.css?v=<?php echo msm_v('templates/morpheus/css/rankings.css'); ?>" rel="stylesheet">
    <link href="<?php echo __BASE_URL__; ?>templates/morpheus/css/news.css?v=<?php echo msm_v('templates/morpheus/css/news.css'); ?>" rel="stylesheet">
    <link href="<?php echo __BASE_URL__; ?>templates/morpheus/css/guides.css?v=<?php echo msm_v('templates/morpheus/css/guides.css'); ?>" rel="stylesheet">
    <link href="<?php echo __BASE_URL__; ?>templates/morpheus/css/forgotpassword.css?v=<?php echo msm_v('templates/morpheus/css/forgotpassword.css'); ?>" rel="stylesheet">
    <link href="<?php echo __BASE_URL__; ?>templates/morpheus/css/footer.css?v=<?php echo msm_v('templates/morpheus/css/footer.css'); ?>" rel="stylesheet">  
    
    <link rel="icon" type="image/x-icon" href="<?php echo __PATH_TEMPLATE_IMG__; ?>favicon.ico">
    
    <style>
        .hero-logo-container {
            width: auto !important;
            max-width: 100% !important;
        }
        .floating-logo {
            /* Forzar el ancho definido por PHP, anulando el 100% del CSS externo */
            width: <?php echo $logoWidth; ?> !important;
            max-width: 90vw !important; /* Seguridad para móviles */
            height: auto !important;
        }
    </style>
</head>
<body class="page-<?php echo empty($_REQUEST['page']) ? 'home' : preg_replace('/[^a-zA-Z0-9_-]/', '', $_REQUEST['page']); ?>">

    <?php include 'templates/morpheus/inc/modules/navbar.php'; ?>

    <div class="morpheus-wrapper">
        
        <?php if(empty($_REQUEST['page'])): ?>
            
            <section class="hero-section" style="background-image: url('<?php echo __BASE_URL__; ?>templates/morpheus/img/<?php echo $bgImage; ?>');">
                <div class="hero-overlay-bottom"></div>
                
                <div class="hero-logo-container">
                    <img src="<?php echo __BASE_URL__; ?>templates/morpheus/img/<?php echo $heroLogo; ?>?v=<?php echo msm_v('templates/morpheus/img/' . $heroLogo); ?>" 
                         class="floating-logo" 
                         alt="Logo">
                </div>

                <div class="hero-action-bar<?php echo isLoggedIn() ? ' is-logged' : ''; ?>">
                    <?php if(!isLoggedIn()): ?>
                    <a href="<?php echo __BASE_URL__; ?>register" class="action-panel panel-register">
                        <div class="panel-content text-left">
                            <h2 class="panel-title text-gold">REGISTRAR</h2>
                            <span class="panel-subtitle">Juega gratuitamente ahora</span>
                        </div>
                    </a>
                    <?php endif; ?>
                    <div class="online-status-container">
                        <div class="status-circle">
                            <div class="status-info">
                                <span class="online-count"><?php echo $displayOnline; ?></span>
                                <span class="online-label">Online</span>
                            </div>
                        </div>
                    </div>
                    <a href="<?php echo $heroDownloadLink; ?>" class="action-panel panel-download">
                        <div class="panel-content text-right">
                            <h2 class="panel-title text-blue">DESCARGAS</h2>
                            <span class="panel-subtitle"><?php echo $heroDlSubtitle; ?></span>
                        </div>
                    </a>
                </div>
            </section>

            <div class="container main-content-area">
                <div class="row g-4">
                    
                    <div class="col-lg-8">
                        
                        <div id="morpheusSlider" class="carousel slide morph-slider mb-4" data-bs-ride="carousel">
                            <div class="carousel-indicators">
                                <button type="button" data-bs-target="#morpheusSlider" data-bs-slide-to="0" class="active"></button>
                                <button type="button" data-bs-target="#morpheusSlider" data-bs-slide-to="1"></button>
                            </div>
                            <div class="carousel-inner">
                                <div class="carousel-item active">
                                    <div class="slider-content" style="background-image: url('<?php echo __BASE_URL__; ?>templates/morpheus/img/<?php echo $s1_img; ?>?v=<?php echo msm_v('templates/morpheus/img/' . $s1_img); ?>');">
                                        <div class="slider-overlay"></div>
                                        <div class="slider-text">
                                            <h2 class="slider-title"><?php echo $s1_title; ?></h2>
                                            <a href="<?php echo __BASE_URL__ . $s1_link; ?>" class="btn-slider"><?php echo $s1_btn; ?></a>
                                        </div>
                                    </div>
                                </div>
                                <div class="carousel-item">
                                    <div class="slider-content" style="background-image: url('<?php echo __BASE_URL__; ?>templates/morpheus/img/<?php echo $s2_img; ?>?v=<?php echo msm_v('templates/morpheus/img/' . $s2_img); ?>');">');">
                                        <div class="slider-overlay"></div>
                                        <div class="slider-text">
                                            <h2 class="slider-title"><?php echo $s2_title; ?></h2>
                                            <a href="<?php echo __BASE_URL__ . $s2_link; ?>" class="btn-slider"><?php echo $s2_btn; ?></a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="morph-news-module">
                            <?php if($featureNews): 
                                $img = !empty($featureNews['image']) ? __BASE_URL__ . $featureNews['image'] : 'https://placehold.co/800x400/111/333?text=News';
                            ?>
                                <div class="news-featured" style="background-image: url('<?php echo $img; ?>');">
                                    <div class="news-featured-overlay"></div>
                                    <div class="nf-date">
                                        <span class="nf-day"><?php echo date("d", strtotime($featureNews['date'])); ?></span>
                                        <span class="nf-month"><?php echo date("M", strtotime($featureNews['date'])); ?></span>
                                    </div>
                                    <div class="nf-content">
                                        <h3 class="nf-title"><?php echo $featureNews['title']; ?></h3>
                                        <p class="nf-desc"><?php echo mb_substr(strip_tags($featureNews['content']), 0, 100) . '...'; ?></p>
                                    </div>
                                </div>
                            <?php endif; ?>
                            <div class="news-list-container">
                                <?php if(!empty($listNews)): ?>
                                    <?php foreach($listNews as $news): ?>
                                        <a href="<?php echo __BASE_URL__; ?>news/<?php echo $news['id']; ?>" class="news-row">
                                            <span class="nr-title"><?php echo $news['title']; ?></span>
                                            <span class="nr-date"><?php echo date("d/m/Y", strtotime($news['date'])); ?></span>
                                        </a>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="p-3 text-center text-muted">No hay más noticias.</div>
                                <?php endif; ?>
                                <div class="news-footer">
                                    <a href="<?php echo __BASE_URL__; ?>news" class="btn-read-all">Leer todas</a>
                                </div>
                            </div>
                        </div>

                        <?php if($csActive): ?>
                        <div class="cs-widget">
                            <div class="cs-overlay"></div>
                            <img src="<?php echo __BASE_URL__; ?>templates/morpheus/img/cs.webp" class="cs-statue" alt="Castle Siege">
                            <div class="cs-info-container">
                                <h2 class="cs-title">Castle Siege</h2>
                                <p class="cs-desc">La batalla más épica del continente</p>
                                <div class="cs-guild-box">
                                    <div class="cs-guild-info">
                                        <span class="cs-guild-name"><?php echo $csInfo['owner']; ?></span>
                                        <span class="cs-guild-master"><?php echo $csInfo['master']; ?></span>
                                    </div>
                                    <div class="cs-guild-mark">
                                        <?php if(!empty($csInfo['mark'])): ?>
                                            <img src="<?php echo $csInfo['mark']; ?>" style="max-width:100%; max-height:100%;">
                                        <?php else: ?>
                                            <span style="font-size:24px; color:#333;">?</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="cs-next-battle">
                                    Próxima Batalla: <span><?php echo $csInfo['date_str']; ?></span>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>

                        <div class="row g-4 mt-1">
                            <div class="col-lg-6">
                                <?php if($bmActive): ?>
                                    <div class="market-widget-box" id="market-widget-content">
                                        <div class="p-4 text-center text-white">Loading Market...</div>
                                    </div>
                                    <div class="mt-3 text-center">
                                        <button type="button" onclick="openMarketModal()" class="btn-market-full">
                                            VER MERCADO COMPLETO <i class="fa fa-arrow-right ms-1"></i>
                                        </button>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="col-lg-6">
                                <div class="ranking-widget-box">
    <div class="rank-header" style="display: flex !important; flex-direction: row !important; justify-content: space-between !important; align-items: center !important; flex-wrap: nowrap !important; margin-bottom: 20px;">
        <h3 id="rank-widget-title" class="rank-title" style="margin: 0 !important; padding: 0 !important; width: auto !important; font-family: 'Cinzel', serif; font-size: 24px; font-weight: 700; color: #c8a165; line-height: 1;">TOP RANKING</h3>
        <a href="<?php echo __BASE_URL__; ?>rankings" class="rank-more" style="flex-shrink: 0 !important; width: 24px; height: 24px; border: 1px solid #444; border-radius: 4px; display: flex; align-items: center; justify-content: center; color: #666; font-size: 10px; text-decoration: none; margin-left: 10px;"><i class="fa fa-plus"></i></a>
    </div>

    <div class="rank-filter">
        <select id="rankFilterSelect" class="form-select rank-select w-100" 
                onchange="switchRanking(this.value); document.getElementById('rank-widget-title').innerText = this.options[this.selectedIndex].text;">
            <?php if(!empty($activeRanks)): ?>
                <?php foreach($activeRanks as $key => $label): ?>
                    <option value="<?php echo $key; ?>"><?php echo $label; ?></option>
                <?php endforeach; ?>
            <?php else: ?>
                <option value="Resets">Resets</option>
            <?php endif; ?>
        </select>
    </div>

    <div class="rank-list-container">
        <div class="rank-table-header">
            <span style="width:50%"># Nickname</span>
            <span style="width:20%; text-align:center;" id="rankColMid">Race</span>
            <span style="width:30%; text-align:right;" id="rankColVal">Val</span>
        </div>
        <div id="rankListContent">
            <div class="p-4 text-center text-white">Loading...</div>
        </div>
    </div>
</div>
                            </div>
                        </div>

                    </div>

                    <?php include 'templates/morpheus/inc/modules/sidebar.php'; ?>

                </div>
            </div>

        <?php else: ?>
            
            <main class="container py-5 mt-5">
                <div class="content-box-internal"> 
                    <?php $handler->loadModule($_REQUEST['page'], $_REQUEST['subpage']); ?>
                </div>
            </main>

        <?php endif; ?>

    </div>

    <div id="alertModal" class="alert-modal-overlay msm-dialog-overlay" style="display:none;">
        <div class="msm-dialog">
            <h3 id="alertTitle" class="msm-dialog-title">Aviso</h3>
            <p id="alertBody" class="msm-dialog-body">Mensaje</p>
            <button type="button" class="msm-btn msm-btn-gold" onclick="document.getElementById('alertModal').style.display='none'">Aceptar</button>
        </div>
    </div>

    <div id="buyConfirmModal" class="confirm-modal-overlay msm-dialog-overlay" style="display:none;">
        <div class="msm-dialog msm-dialog-sm">
            <div class="msm-dialog-title">Confirmar compra</div>
            <div class="msm-dialog-body">
                &iquest;Comprar este &iacute;tem por <span id="buyPriceLabel" class="msm-dialog-price"></span>?
                <small class="msm-dialog-warn">Tu ba&uacute;l debe estar <b>VAC&Iacute;O</b>.</small>
            </div>
            <div class="msm-dialog-btns">
                <button type="button" class="msm-btn msm-btn-ghost" onclick="document.getElementById('buyConfirmModal').style.display='none'">Cancelar</button>
                <button type="button" id="btnDoBuy" class="msm-btn msm-btn-gold">COMPRAR</button>
            </div>
        </div>
    </div>

    <div id="global-tt" style="display:none; position:fixed; z-index:9999999; background:rgba(10,10,12,0.98); border:1px solid #c8a165; color:#fff; padding:15px; border-radius:6px; pointer-events:none; box-shadow:0 0 20px rgba(0,0,0,0.8); width:220px; font-size:12px; text-align:center; font-family:'Roboto',sans-serif;"></div>

    <?php if(isset($bmActive) && $bmActive): ?>

    <div id="wsMarketModal" class="ws-modal-overlay" style="display:none;">
        <div class="ws-workspace">
            <button class="btn-ws-close" onclick="closeMarketModal()"><i class="fa fa-times"></i></button>
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
                        <input type="text" id="wsSearch" class="search-input" placeholder="Filtrar items..." onkeyup="wsFilterGrid()">
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

    <script>
        var bm_selected_id = -1;

        // Tooltip global
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

        // Manejo Modal Principal
        function openMarketModal() { document.getElementById('wsMarketModal').style.display = 'flex'; }
        function closeMarketModal() { document.getElementById('wsMarketModal').style.display = 'none'; }

        // Alertas Dinámicas (reutiliza el HTML existente en tu index)
        function show_modal_alert(title, msg) {
            document.getElementById('alertTitle').innerText = title;
            document.getElementById('alertBody').innerHTML = msg;
            document.getElementById('alertModal').style.display = 'flex';
        }

        // Flujo de Compra
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
                el.style.cssText = 'display:none;position:fixed;top:0;left:0;width:100vw;height:100vh;background:rgba(0,0,0,0.85);z-index:2147483647;align-items:center;justify-content:center;flex-direction:column;';
                el.innerHTML = '<i class="fa fa-spinner fa-spin fa-3x" style="color:#fff;margin-bottom:12px;"></i><span style="color:#fff;font-size:13px;font-weight:bold;letter-spacing:1px;">PROCESANDO COMPRA...</span>';
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
            }).catch(err => {
                bm_buy_loader(false);
                show_modal_alert("Error", "No se pudo procesar la compra. Intenta de nuevo.");
            });
        }

        // Carga de Categorías vía AJAX
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
                        grid.innerHTML = '<div style="grid-column: 1/-1; text-align:center; color:#666; margin-top:50px;">No hay items en esta categoría.</div>';
                        return;
                    }
                    res.data.forEach(item => {
                        var safeTt = item.tooltip.replace(/"/g, '&quot;');
                        var div = document.createElement('div');
                        div.className = 'ws-item-card';
                        div.setAttribute('data-name', item.clean_name.toLowerCase());
                        
                        // FIX: Asignamos el tooltip a TODA la tarjeta
                        div.setAttribute('data-tooltip', safeTt);
                        div.onmouseenter = function() { idx_show_tt(this); };
                        div.onmousemove = function(e) { idx_move_tt(e); };
                        div.onmouseleave = function() { idx_hide_tt(); };
                        
                        var currencyIcon = (item.currency == 'Credits') ? '<i class="fa fa-coins"></i>' : '<i class="fa fa-gem"></i>';
                        
                        // FIX: Quitamos el <i> extra alrededor de currencyIcon
                        div.innerHTML = `
                            <div style="width:100%; display:flex; flex-direction:column; align-items:center;">
                                <img src="${item.img}" class="ws-card-img">
                            </div>
                            <div class="ws-card-name">${item.clean_name}</div>
                            <div class="ws-card-price">${currencyIcon} ${new Intl.NumberFormat().format(item.price)}</div>
                            <button class="ws-card-btn" onclick="openDirectBuy(${item.id}, '${new Intl.NumberFormat().format(item.price)} ${item.currency}')">COMPRAR</button>
                        `;
                        grid.appendChild(div);
                    });
                }
            });
        }

        // Filtro buscador
        function wsFilterGrid() {
            var term = document.getElementById('wsSearch').value.toLowerCase();
            document.querySelectorAll('.ws-item-card').forEach(slot => {
                var name = slot.getAttribute('data-name');
                slot.style.display = name.includes(term) ? 'flex' : 'none';
            });
        }
    </script>
    <?php endif; ?>
    <?php include('includes/popup_modal.php'); ?>
    <?php include(__PATH_TEMPLATE_ROOT__ . 'inc/modules/footer.php'); ?>
    <?php include_once($_SERVER['DOCUMENT_ROOT'] . '/templates/misc/sidebar.php'); ?>

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
    
    // Auto Cron integrado
    document.addEventListener("DOMContentLoaded", function() {
        setTimeout(function() {
                        var cronUrl = "<?php echo __BASE_URL__; ?>api/cron.php";
            try {
                fetch(cronUrl, {mode: 'no-cors'});
            } catch(e) { }
        }, 3000);
    });
    </script>
    <?php endif; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>const baseUrl = '<?php echo __BASE_URL__; ?>';</script>
    <script src="<?php echo __BASE_URL__; ?>templates/morpheus/js/main.js?v=<?php echo msm_v('templates/morpheus/js/main.js'); ?>"></script>
    <?php include 'templates/misc/countdown.php'; ?>
</body>
</html>
