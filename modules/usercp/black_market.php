<?php
/**
 * Black Market - HYBRID VERSION (Visuals Fix + Modals + Advanced Parser)
 * PREPARED FOR OBFUSCATION (Pure PHP Version)
 * Update: Fix Option Display, Hide +0, Socket Levels, Fix Functions
 */

if(!defined('access')) die();

// --- 1. CONFIG & BACKEND ---
$marketConfig = __PATH_INCLUDES__ . 'config/black_market_config.json';
$serverFile   = __PATH_INCLUDES__ . 'config/muservermaker.json';

$config = file_exists($marketConfig) ? json_decode(file_get_contents($marketConfig), true) : array();
$serverData = file_exists($serverFile) ? json_decode(file_get_contents($serverFile), true) : array();

$taxPercent = isset($config['tax_percent']) ? (int)$config['tax_percent'] : 10;
$currency = isset($config['allowed_currency']) ? $config['allowed_currency'] : 'Credits';
$dbName = isset($serverData['SQL_DB_NAME']) ? $serverData['SQL_DB_NAME'] : 'MuOnline';
$ITEM_HEX_LEN = 32;

if (!function_exists('bm_clean')) {
    function bm_clean($data) { return str_replace("'", "''", strip_tags($data)); }
}

// --- 2. LOGICA VISUAL DEL ITEM (DIMENSIONES) ---
if (!function_exists('bm_get_dims')) {
    function bm_get_dims($group, $id) {
        static $dims = array(
            0 => array(0=>array(1,2),1=>array(1,3),2=>array(1,3),3=>array(1,3),4=>array(1,3),5=>array(1,3),6=>array(1,3),7=>array(1,3),8=>array(1,3),9=>array(2,3),10=>array(2,4),11=>array(2,3),12=>array(2,3),13=>array(1,3),14=>array(1,3),15=>array(2,3),16=>array(1,4),17=>array(2,4),18=>array(2,3),19=>array(1,4),20=>array(1,4),21=>array(2,4),22=>array(1,4),23=>array(2,4),24=>array(2,4),25=>array(2,4),26=>array(1,4),27=>array(1,4),28=>array(1,4),31=>array(2,4),32=>array(1,2),33=>array(1,2),34=>array(1,2),35=>array(1,2)),
            1 => array(0=>array(1,3),1=>array(1,3),2=>array(1,3),3=>array(1,3),4=>array(1,3),5=>array(2,3),6=>array(2,3),7=>array(2,3),8=>array(2,3)),
            2 => array(0=>array(1,3),1=>array(1,3),2=>array(1,3),3=>array(2,3),4=>array(2,3),5=>array(2,4),6=>array(2,4),7=>array(1,3),8=>array(1,3),9=>array(1,3),10=>array(1,4),11=>array(1,4),12=>array(1,4),13=>array(1,4),14=>array(1,4),15=>array(1,4),16=>array(1,3),17=>array(1,4),18=>array(1,4)),
            3 => array(0=>array(2,4),1=>array(2,4),2=>array(2,4),3=>array(2,4),4=>array(2,4),5=>array(2,4),6=>array(2,4),7=>array(2,4),8=>array(2,4),9=>array(2,4),10=>array(2,4),11=>array(2,4)),
            4 => array(0=>array(2,3),1=>array(2,3),2=>array(2,3),3=>array(2,3),4=>array(2,4),5=>array(2,4),6=>array(2,4),7=>array(1,1),8=>array(2,2),9=>array(2,2),10=>array(2,2),11=>array(2,3),12=>array(2,3),13=>array(2,3),14=>array(2,3),15=>array(1,1),16=>array(2,4),17=>array(2,4),18=>array(2,3),19=>array(2,3),20=>array(2,4),21=>array(2,4),22=>array(2,4),23=>array(2,4),24=>array(2,4)),
            5 => array(0=>array(1,3),1=>array(2,3),2=>array(2,3),3=>array(2,4),4=>array(2,4),5=>array(1,4),6=>array(1,4),7=>array(2,4),8=>array(2,4),9=>array(1,4),10=>array(1,4),11=>array(1,4),12=>array(1,4),13=>array(1,4),14=>array(1,4),15=>array(1,4),16=>array(1,4),17=>array(1,4),18=>array(1,4),19=>array(1,4),20=>array(1,4),21=>array(1,2),22=>array(1,2),23=>array(1,2),30=>array(1,4),31=>array(1,4),32=>array(1,4),33=>array(1,4),34=>array(1,4),36=>array(1,4)),
            6 => array(0=>array(2,2),1=>array(2,2),2=>array(2,2),3=>array(2,2),4=>array(2,2),5=>array(2,2),6=>array(2,2),7=>array(2,2),8=>array(2,2),9=>array(2,2),10=>array(2,2),11=>array(2,2),12=>array(2,2),13=>array(2,2),14=>array(2,3),15=>array(2,3),16=>array(2,2),17=>array(2,2),18=>array(2,2),19=>array(2,2),20=>array(2,2),21=>array(2,2)),
            7 => array(15=>array(2,3)), 
            8 => array(1=>array(2,3),5=>array(2,3),7=>array(2,3),15=>array(2,3),16=>array(2,3),17=>array(2,3),18=>array(2,3),20=>array(2,3),21=>array(2,3),22=>array(2,3),23=>array(2,3),25=>array(2,3),26=>array(2,3),27=>array(2,3),28=>array(2,3),29=>array(2,3),30=>array(2,3),31=>array(2,3),32=>array(2,3),33=>array(2,3),34=>array(2,3),35=>array(2,3),36=>array(2,3),37=>array(2,3),38=>array(2,3),45=>array(2,3),46=>array(2,3),47=>array(2,3),48=>array(2,3),51=>array(2,3),52=>array(2,3),59=>array(2,3),60=>array(2,3),64=>array(2,3),66=>array(2,3),67=>array(2,3),71=>array(2,3),72=>array(2,3),73=>array(2,3)),
            12 => array(0=>array(3,2),1=>array(5,3),2=>array(5,2),3=>array(5,3),4=>array(5,3),5=>array(3,3),6=>array(4,2),36=>array(4,3),37=>array(4,3),38=>array(4,3),39=>array(4,3),40=>array(2,3),41=>array(4,2),42=>array(4,3),43=>array(4,3),49=>array(2,3),50=>array(2,3),130=>array(2,2),131=>array(3,2),132=>array(3,2),133=>array(3,2),134=>array(3,2),135=>array(2,2)),
            13 => array(29=>array(2,2),30=>array(2,3),37=>array(2,2)),
            14 => array(9=>array(1,2),10=>array(1,2),24=>array(1,2),46=>array(1,2),47=>array(1,2),48=>array(1,2),66=>array(1,2),67=>array(1,2),68=>array(1,2),72=>array(2,2),73=>array(2,2),74=>array(2,2),75=>array(2,2),76=>array(2,2),77=>array(2,2),78=>array(1,2),79=>array(1,2),80=>array(1,2),81=>array(1,2),82=>array(1,2),85=>array(1,2),88=>array(1,2),89=>array(1,2),90=>array(1,2),97=>array(2,2),98=>array(2,2),140=>array(2,2),151=>array(1,2),152=>array(1,2),155=>array(2,2),156=>array(1,3),164=>array(1,2)),
        );
        if (isset($dims[$group][$id])) return $dims[$group][$id];
        if ($group <= 5) return array(1,3); if ($group == 6) return array(2,2); if ($group == 7) return array(2,2);
        if ($group == 8) return array(2,2); if ($group == 9) return array(2,2); if ($group == 10) return array(2,2);
        if ($group == 11) return array(2,2); if ($group == 12) return array(4,3); if ($group == 15) return array(1,2);
        return array(1,1); 
    }
}

// --- PARSER AVANZADO ---
if (!function_exists('bm_parse_hex')) {
    function bm_parse_hex($hex) {
        $id = hexdec(substr($hex, 0, 2));
        $byte1 = hexdec(substr($hex, 2, 2));
        $byte7 = hexdec(substr($hex, 14, 2));
        $byte9 = hexdec(substr($hex, 18, 2));
        $byte10 = hexdec(substr($hex, 20, 2));
        $group = ($byte9 >> 4) & 0xF;

        $isSocketItem = false;
        if (($group >= 7 && $group <= 11) && ($id >= 45 && $id <= 65)) $isSocketItem = true;
        if ($group == 0 && $id >= 26) $isSocketItem = true;
        if ($group == 2 && $id >= 16) $isSocketItem = true;
        if ($group == 4 && $id >= 23) $isSocketItem = true;
        if ($group == 5 && $id >= 30) $isSocketItem = true;

        $level = ($byte1 >> 3) & 0xF;
        $skill = ($byte1 >> 7) & 1;
        $luck = ($byte1 >> 2) & 1;
        
        $optVal = ($byte1 & 3);
        if (($byte7 & 64) > 0) $optVal += 4;
        $option = $optVal * 4;

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

        $is380Str = "";
        $is380 = (($byte9 & 16) > 0) || (($byte9 & 8) > 0);
        if (!$isSocketItem && $is380 && $group != 13) { 
            $is380Str = "380 Option Active"; 
        }

        $harmonyStr = "";
        if (!$isSocketItem && $byte10 > 0) {
            $hType = 1;
            if ($group == 5) $hType = 5;
            if ($group >= 6 && $group <= 11) $hType = 6;

            $optId = ($byte10 >> 4) & 0xF;
            $optLvl = ($byte10 & 0xF);

            $hNames = [
                1 => [1 => "Min. attack increase", 2 => "Max. attack increase", 3 => "Required Strength decrease", 4 => "Required Dexterity decrease", 5 => "Attack damage increase", 6 => "Critical damage increase", 7 => "Skill attack increase", 8 => "Attack success rate increase", 9 => "SD decrease rate increase", 10 => "SD bypass rate increase"],
                5 => [1 => "Wizardry increase", 2 => "Required Strength decrease", 3 => "Required Dexterity decrease", 4 => "Skill attack increase", 5 => "Critical damage increase", 6 => "SD decrease rate increase", 7 => "Attack success rate increase", 8 => "SD bypass rate increase"],
                6 => [1 => "Defense increase", 2 => "Max. AG increase", 3 => "Max. HP increase", 4 => "HP recovery increase", 5 => "Mana recovery increase", 6 => "Defense success rate increase", 7 => "Damage reduction increase", 8 => "SD ratio increase"]
            ];
            
            if (isset($hNames[$hType][$optId])) {
                $harmonyStr = $hNames[$hType][$optId] . " (Lvl " . $optLvl . ")";
            }
        }

        $sockets = array();
        $socketNames = [0=>"Fire (Attack/Wizardry increase)", 1=>"Fire (Attack Speed increase)", 2=>"Fire (Max Attack/Wizardry increase)", 3=>"Fire (Min Attack/Wizardry increase)", 4=>"Fire (Attack/Wizardry increase)", 5=>"Fire (AG consumption decrease)", 10=>"Water (Block Rating increase)", 11=>"Water (Defense increase)", 12=>"Water (Shield Protection increase)", 13=>"Water (Damage Reduction)", 14=>"Water (Damage Reflection)", 16=>"Ice (Monster Die Life recovery)", 17=>"Ice (Monster Die Mana recovery)", 18=>"Ice (Skill attack power increase)", 19=>"Ice (Attack Accuracy increase)", 20=>"Ice (Item durability increase)", 21=>"Wind (Auto Life recovery increase)", 22=>"Wind (Max Life increase)", 23=>"Wind (Max Mana increase)", 24=>"Wind (Auto Mana recovery increase)", 25=>"Wind (Max AG increase)", 26=>"Wind (AG value increase)", 29=>"Lightning (Excellent Damage increase)", 30=>"Lightning (Excellent Damage Rate increase)", 31=>"Lightning (Critical Damage increase)", 32=>"Lightning (Critical Damage Rate increase)", 34=>"Earth (Strength increase)", 35=>"Earth (Dexterity increase)", 36=>"Earth (Health increase)", 37=>"Earth (Energy increase)"];

        for($k=0; $k<5; $k++) {
            $idx = 22 + ($k*2);
            if(strlen($hex) >= $idx+2) {
                $sockVal = hexdec(substr($hex, $idx, 2));
                if($sockVal != 255 && $sockVal != 0) { 
                    $sText = "";
                    $sLvl = 0;
                    if($sockVal == 254) { 
                        $sText = "Socket ".($k+1).": Vacío";
                        $sockets[] = array('type'=>'empty', 'text'=>$sText, 'lvl'=>0);
                    } else {
                        $optionId = $sockVal % 50; 
                        $sLvl = floor($sockVal / 50) + 1;
                        $optName = isset($socketNames[$optionId]) ? $socketNames[$optionId] : 'Unknown Socket';
                        $sockets[] = array('type'=>'filled', 'text'=>$optName, 'lvl'=>$sLvl);
                    }
                }
            }
        }

        $img = "img/items/{$group}-{$id}.gif";
        $name = "Item ($group-$id)";
        // FIX: Evitamos llamar a una clase que no existe
        if(class_exists('ItemsManager') && method_exists('ItemsManager', 'getItemName')) { 
            $name = ItemsManager::getItemName($group, $id); 
        }

        $dims = bm_get_dims($group, $id);

        return array(
            'name' => $name, 'img' => $img, 'w' => $dims[0], 'h' => $dims[1], 
            'level' => $level, 'skill' => $skill, 'luck' => $luck, 'option' => $option,
            'exc' => $excOpts, 'op380' => $is380Str, 'harmony' => $harmonyStr, 'sockets' => $sockets
        );
    }
}

// --- 3. PROCESADOR AJAX (Blindado) ---
if(isset($_POST['ajax_action'])) {
    while (ob_get_level() > 0) ob_end_clean();
    header('Content-Type: application/json');
    @error_reporting(0); 

    try {
        if(!isLoggedIn()) throw new Exception("Sesión expirada.");
        $username = $_SESSION['username'];
        $db = Connection::Database('Me_MuOnline'); 
        
        // --- NUEVO: VERIFICACIÓN ANTI-DUPE (ESTADO ONLINE) ---
        if(in_array($_POST['ajax_action'], ['publish', 'retract'])) {
            $onlineCheck = $db->query_fetch_single("SELECT ConnectStat FROM MEMB_STAT WHERE memb___id = ?", [$username]);
            if ($onlineCheck && $onlineCheck['ConnectStat'] == 1) {
                throw new Exception("Debes desconectarte del juego para gestionar el baúl.");
            }
        }
        
        if($_POST['ajax_action'] == 'publish') {
            $slot = (int)$_POST['slot']; $price = (int)$_POST['price'];
            if($price <= 0) throw new Exception("Precio inválido.");
            $row = $db->query_fetch_single("SELECT CAST(Items AS VARBINARY(MAX)) as Items FROM warehouse WHERE AccountId = ?", array($username));
            if(!$row) throw new Exception("Error leyendo baúl.");
            $itemsHex = bin2hex($row['Items']);
            if(strlen($itemsHex) < 3840) $itemsHex = str_pad($itemsHex, 3840, 'F', STR_PAD_RIGHT);
            $itemsHex = strtoupper($itemsHex);
            $targetHex = substr($itemsHex, $slot * 32, 32);
            if(substr($targetHex, 0, 8) === 'FFFFFFFF') throw new Exception("Item vacío.");
            $info = bm_parse_hex($targetHex);
            $pName = bm_clean($info['name']); $pImg = bm_clean($info['img']); $curr = bm_clean($currency);
            $sql = "INSERT INTO MUSERVERMAKER_MARKET_SELL (seller_account, item_hex, item_name, item_img, price, currency, date_posted) VALUES ('$username', CONVERT(varbinary(max), '$targetHex', 2), '$pName', '$pImg', $price, '$curr', GETDATE())";
            if(!$db->query($sql)) throw new Exception("Error SQL al publicar.");
            $newWh = substr_replace($itemsHex, str_repeat('F', 32), $slot * 32, 32);
            $db->query("UPDATE warehouse SET Items = 0x" . $newWh . " WHERE AccountId = ?", array($username));
            echo json_encode(array('status' => 'success')); exit;
        }

        if($_POST['ajax_action'] == 'retract') {
            $id = (int)$_POST['id'];
            $item = $db->query_fetch_single("SELECT id, CONVERT(varchar(max), item_hex, 2) as item_hex FROM MUSERVERMAKER_MARKET_SELL WHERE id = $id");
            if(!$item) throw new Exception("Item no encontrado.");
            $row = $db->query_fetch_single("SELECT CAST(Items AS VARBINARY(MAX)) as Items FROM warehouse WHERE AccountId = ?", array($username));
            $hexWh = strtoupper(bin2hex($row['Items']));
            if(!preg_match('/^[fF0]+$/', $hexWh)) throw new Exception("El baúl debe estar VACÍO.");
            $targetHex = $item['item_hex'];
            $newWh = $targetHex . str_repeat('F', 3840 - strlen($targetHex));
            $db->query("UPDATE warehouse SET Items = 0x" . $newWh . " WHERE AccountId = ?", array($username));
            $db->query("DELETE FROM MUSERVERMAKER_MARKET_SELL WHERE id = $id");
            echo json_encode(array('status' => 'success')); exit;
        }

        if($_POST['ajax_action'] == 'load_sales') {
            $list = $db->query_fetch("SELECT id, seller_account, CONVERT(varchar(max), item_hex, 2) as item_hex, item_name, item_img, price, currency, date_posted FROM MUSERVERMAKER_MARKET_SELL WHERE seller_account = '$username' ORDER BY date_posted DESC");
            $out = array();
            if(is_array($list)) {
                foreach($list as $i) {
                    $i = array_change_key_case($i, CASE_LOWER);
                    $info = bm_parse_hex($i['item_hex']);
                    $i['p_name'] = $info['name']; $i['p_img'] = $info['img']; $out[] = $i;
                }
            }
            echo json_encode(array('status' => 'success', 'data' => $out)); exit;
        }

        if($_POST['ajax_action'] == 'load_wh') {
            // Check Online para que ni siquiera cargue el baúl si está jugando
            $onlineCheck = $db->query_fetch_single("SELECT ConnectStat FROM MEMB_STAT WHERE memb___id = ?", [$username]);
            if ($onlineCheck && $onlineCheck['ConnectStat'] == 1) {
                throw new Exception("Desconéctate del juego para ver tu baúl.");
            }

            $row = $db->query_fetch_single("SELECT CAST(Items AS VARBINARY(MAX)) as Items FROM warehouse WHERE AccountId = ?", array($username));
            if(!$row) throw new Exception("Baúl no encontrado.");
            $hex = strtoupper(bin2hex($row['Items']));
            if(strlen($hex) < 3840) $hex = str_pad($hex, 3840, 'F', STR_PAD_RIGHT);
            $items = array();
            for($i=0; $i<120; $i++) {
                $sub = substr($hex, $i*32, 32);
                if(substr($sub, 0, 8) !== 'FFFFFFFF' && !preg_match('/^0+$/', $sub)) {
                    $items[] = array_merge(['slot'=>$i, 'hex'=>$sub], bm_parse_hex($sub));
                }
            }
            echo json_encode(array('status' => 'success', 'data' => $items)); exit;
        }
    } catch (Exception $e) { echo json_encode(array('status' => 'error', 'message' => $e->getMessage())); exit; }
}

// --- 4. RENDER FRONTEND ---
echo '<link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;600;700&family=Roboto:wght@300;400;700&display=swap" rel="stylesheet">';

echo '<style>
    /* --- VARIABLES GLOBALES MU ONLINE --- */
    :root {
        --mu-gold: #c8a165;
        --mu-gold-glow: rgba(200, 161, 101, 0.4);
        --mu-bg-dark: #0f1016;
        --mu-bg-panel: #161822;
        --mu-border: #2a2e3d;
        --mu-green: #2ecc71;
        --mu-red: #e74c3c;
    }

    .bm-wrap { font-family: "Roboto", sans-serif; color: #ccc; background: var(--mu-bg-dark); border: 1px solid var(--mu-border); border-radius: 8px; padding: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.8); }
    
    .bm-head { display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid var(--mu-border); padding-bottom: 15px; margin-bottom: 20px; }
    
    .bm-tab-container { display: flex; gap: 10px; }
    
    .bm-btn-tab { background: transparent; border: 1px solid var(--mu-border); color: #777; padding: 10px 25px; cursor: pointer; font-family: "Cinzel", serif; font-weight: 700; text-transform: uppercase; font-size: 13px; border-radius: 4px; transition: all 0.3s ease; }
    .bm-btn-tab:hover { color: #fff; border-color: #555; }
    .bm-btn-tab.active { background: rgba(200, 161, 101, 0.1); color: var(--mu-gold); border-color: var(--mu-gold); box-shadow: 0 0 15px var(--mu-gold-glow); }
    
    .tax-badge { font-family: "Cinzel", serif; color: var(--mu-gold); background: rgba(0,0,0,0.5); border: 1px solid var(--mu-border); padding: 5px 15px; border-radius: 20px; font-size: 12px; font-weight: bold; letter-spacing: 1px; }

    /* --- WAREHOUSE GRID --- */
    .wh-box { position: relative; width: 340px; height: 634px; background: url("'.__BASE_URL__.'templates/misc/img/wh_bg.jpg") center/cover no-repeat, #0a0b0e; border: 2px solid var(--mu-border); margin: 30px auto; border-radius: 4px; box-shadow: inset 0 0 30px rgba(0,0,0,0.9); }
    .wh-grid { display: grid; grid-template-columns: repeat(8, 42px); grid-template-rows: repeat(15, 42px); position: absolute; top: 1px; left: 1px; }
    .wh-cell { border: 1px solid rgba(255, 255, 255, 0.03); width: 42px; height: 42px; box-sizing: border-box; }
    
    .wh-item { position: absolute; cursor: pointer; border: 1px solid transparent; display: flex; align-items: center; justify-content: center; transition: background 0.2s; }
    .wh-item:hover { border-color: var(--mu-gold); background: rgba(200, 161, 101, 0.15); z-index: 99; box-shadow: 0 0 15px var(--mu-gold-glow); }
    .wh-item img { max-width: 95%; max-height: 95%; filter: drop-shadow(0 2px 5px rgba(0,0,0,0.8)); }
    /* --- LOADER DEL BAÚL --- */
    .wh-loader {
        position: absolute; top: 0; left: 0; width: 100%; height: 100%;
        background: rgba(0, 0, 0, 0.75); z-index: 100;
        display: flex; flex-direction: column; align-items: center; justify-content: center;
        color: var(--mu-gold); font-family: "Cinzel", serif; font-weight: bold; font-size: 16px;
        backdrop-filter: blur(3px); border-radius: 4px;
    }
    
    .wh-loader i { font-size: 35px; margin-bottom: 12px; }
    /* --- SALES LIST --- */
    #salesList { display: flex; flex-direction: column; gap: 10px; }
    .sale-row { background: var(--mu-bg-panel); padding: 15px 20px; display: flex; justify-content: space-between; align-items: center; border: 1px solid var(--mu-border); border-radius: 6px; transition: transform 0.2s; }
    .sale-row:hover { transform: translateX(5px); border-color: #444; }
    .sale-row b { font-family: "Cinzel", serif; font-size: 15px; color: #fff; }

    /* --- BOTONES --- */
    .bm-btn { padding: 10px 25px; border: none; cursor: pointer; font-weight: bold; border-radius: 4px; color: #fff; font-family: "Cinzel", serif; text-transform: uppercase; font-size: 12px; transition: 0.3s; }
    .bm-green { background: linear-gradient(180deg, #2ecc71 0%, #27ae60 100%); text-shadow: 0 1px 2px rgba(0,0,0,0.5); }
    .bm-green:hover { filter: brightness(1.1); box-shadow: 0 0 10px rgba(46, 204, 113, 0.4); }
    .bm-red { background: linear-gradient(180deg, #e74c3c 0%, #c0392b 100%); text-shadow: 0 1px 2px rgba(0,0,0,0.5); }
    .bm-red:hover { filter: brightness(1.1); box-shadow: 0 0 10px rgba(231, 76, 60, 0.4); }
    .bm-grey { background: #222; border: 1px solid #444; color: #aaa; }
    .bm-grey:hover { background: #333; color: #fff; }

    /* --- MODALES --- */
    .modal-mask { display: none; position: fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.85); z-index:9000; align-items:center; justify-content:center; backdrop-filter: blur(3px); }
    .modal-win { background: var(--mu-bg-panel); width: 380px; text-align: center; border: 1px solid var(--mu-border); border-radius: 8px; color: #fff; overflow: hidden; box-shadow: 0 10px 40px rgba(0,0,0,0.9); }
    .modal-head { padding: 18px; font-family: "Cinzel", serif; font-size: 18px; font-weight: bold; background: #0a0b0e; border-bottom: 1px solid var(--mu-border); color: var(--mu-gold); letter-spacing: 1px; }
    .modal-body { padding: 25px; color: #ccc; }
    .modal-foot { padding: 15px; border-top: 1px solid var(--mu-border); background: #0a0b0e; display: flex; justify-content: center; gap: 15px; }
    
    .bm-inp { width: 100%; padding: 12px; background: #0a0b0e; border: 1px solid var(--mu-border); color: var(--mu-gold); text-align: center; margin: 15px 0; border-radius: 4px; font-size: 16px; font-weight: bold; font-family: "Roboto", sans-serif; transition: 0.3s; }
    .bm-inp:focus { outline: none; border-color: var(--mu-gold); box-shadow: 0 0 10px var(--mu-gold-glow); }
    
    /* --- TOOLTIP MU ONLINE STYLE --- */
    #bm-tt { 
        position: fixed; display: none; z-index: 10000; 
        background: rgba(0, 0, 0, 0.95); border: 2px solid #1a1a1a; 
        padding: 15px; min-width: 250px; color: #fff; font-size: 12px; 
        box-shadow: 0 0 20px rgba(0,0,0,0.9), inset 0 0 15px rgba(0,0,0,0.5); 
        border-radius: 4px; pointer-events: none; text-align: center; line-height: 1.6;
        font-family: "Arial", sans-serif; /* Arial es la fuente original de los items de MU */
    }
    .tt-opt { color: #9aadcd; }
    .tt-luck { color: #9aadcd; }
    .tt-exc { color: #00ff00; }
    .tt-380 { color: #d000d0; }
    .hidden { display: none; }
</style>';

echo '<div class="bm-wrap">
    <div class="bm-head">
        <div class="bm-tab-container">
            <button class="bm-btn-tab active" id="btnTab1" onclick="bm_setTab(1)">Vender Items</button>
            <button class="bm-btn-tab" id="btnTab2" onclick="bm_setTab(2)">Mis Ventas</button>
        </div>
        <div class="tax-badge">IMPUESTO: ' . $taxPercent . '%</div>
    </div>
    
    <div id="viewSell">
        <div style="text-align:center; padding:10px; font-size:13px; color:#888;">
            <i class="fa fa-mouse-pointer" style="color: var(--mu-gold);"></i> Click derecho en un item para vender.
        </div>
        <div class="wh-box">
            <div class="wh-grid">'; for($i=0;$i<120;$i++) echo '<div class="wh-cell"></div>'; echo '</div>
            <div id="whItems"></div>
            <div id="whLoader" class="wh-loader" style="display:none;">
                <i class="fa fa-spinner fa-spin"></i>
                <span>Cargando baúl...</span>
            </div>
        </div>
        <div style="text-align:center; margin-top:20px;">
            <button class="bm-btn bm-green" onclick="bm_loadWh()"><i class="fa fa-sync"></i> Recargar Baúl</button>
        </div>
    </div>
    
    <div id="viewManage" class="hidden">
        <div id="salesList" style="padding:10px 0;"></div>
    </div>
</div>';

// Modales
echo '<div id="modalSell" class="modal-mask"><div class="modal-win">
        <div class="modal-head">VENDER ITEM</div>
        <div class="modal-body">
            <div style="background: #0a0b0e; padding: 20px; border-radius: 6px; border: 1px solid var(--mu-border); margin-bottom: 15px;">
                <img id="mImg" src="" style="filter: drop-shadow(0 5px 15px rgba(0,0,0,0.8)); margin-bottom:10px;">
                <div id="mName" style="color: var(--mu-gold); font-family: \'Cinzel\', serif; font-size: 16px; font-weight: bold;"></div>
            </div>
            <label style="font-size: 11px; color: #888; text-transform: uppercase;">Precio de Venta</label>
            <input type="number" id="mPrice" class="bm-inp" placeholder="0" onkeyup="bm_calcTax(this)">
            <div style="font-size:12px; color:#888; background: rgba(0,0,0,0.3); padding: 10px; border-radius: 4px;">
                Recibirás (Neto): <span id="mReceive" style="color:#2ecc71; font-weight:bold; font-size: 14px;">0</span>
            </div>
        </div>
        <div class="modal-foot">
            <button class="bm-btn bm-grey" onclick="document.getElementById(\'modalSell\').style.display=\'none\'">Cancelar</button>
            <button id="btnPublishItem" class="bm-btn bm-green" onclick="bm_doSell()">PUBLICAR</button>
        </div>
      </div></div>';

echo '<div id="modalMsg" class="modal-mask"><div class="modal-win">
        <div class="modal-head" id="msgTitle">AVISO</div>
        <div class="modal-body" id="msgText" style="font-size: 14px;"></div>
        <div class="modal-foot">
            <button class="bm-btn bm-green" onclick="document.getElementById(\'modalMsg\').style.display=\'none\'">ACEPTAR</button>
        </div>
      </div></div>';

echo '<div id="modalConfirm" class="modal-mask"><div class="modal-win">
        <div class="modal-head" style="color:#e74c3c;">CONFIRMAR RETIRO</div>
        <div class="modal-body">
            <p style="margin-bottom: 20px; font-size: 14px;">¿Seguro que deseas recuperar este item?</p>
            <div style="background:rgba(231,76,60,0.1); padding:15px; border-radius:4px; border:1px solid #c0392b;">
                <strong style="color:#e74c3c; font-family: \'Cinzel\', serif; font-size: 16px;">ATENCIÓN:</strong><br>
                <span style="font-size: 12px;">Para realizar esta acción, tu baúl debe estar <b style="text-decoration:underline;">COMPLETAMENTE VACÍO</b>.</span>
            </div>
        </div>
        <div class="modal-foot">
            <button class="bm-btn bm-grey" onclick="document.getElementById(\'modalConfirm\').style.display=\'none\'">CANCELAR</button>
            <button class="bm-btn bm-red" id="btnConfirmRetract">RETIRAR</button>
        </div>
      </div></div>';

echo '<div id="bm-tt"></div>';

// Javascript Intacto
echo '<script>
    var bm_tax = ' . $taxPercent . '; var bm_sel_slot = -1; var bm_retract_id = -1;
    function bm_showMsg(t, x) { document.getElementById("msgTitle").innerText = t; document.getElementById("msgText").innerHTML = x; document.getElementById("modalMsg").style.display = "flex"; }
    function bm_loadWh() {
        const layer = document.getElementById("whItems"); 
        const loader = document.getElementById("whLoader");
        
        layer.innerHTML = ""; // Vaciamos los items
        loader.style.display = "flex"; // Mostramos la pantalla opaca de carga
        
        const fd = new FormData(); fd.append("ajax_action", "load_wh");
        fetch(window.location.href, {method:"POST", body:fd})
        .then(r=>r.json())
        .then(res=>{
            loader.style.display = "none"; // Ocultamos el loader
            
            if(res.status==="success"){
                res.data.forEach(item=>{
                    const d = document.createElement("div"); d.className = "wh-item";
                    d.style.left = (item.slot % 8 * 42) + "px"; d.style.top = (Math.floor(item.slot / 8) * 42) + "px";
                    d.style.width = (item.w * 42) + "px"; d.style.height = (item.h * 42) + "px";
                    const img = document.createElement("img"); img.src = "' . __BASE_URL__ . '" + item.img; d.appendChild(img);
                    d.onmouseenter = e => bm_tt(e, item); d.onmouseleave = () => document.getElementById("bm-tt").style.display="none";
                    d.onmousemove = e => { const t = document.getElementById("bm-tt"); t.style.left = (e.clientX+15)+"px"; t.style.top = (e.clientY+15)+"px"; };
                    d.oncontextmenu = e => { e.preventDefault(); bm_openSell(item); };
                    layer.appendChild(d);
                });
            } else {
                bm_showMsg("Error", res.message);
            }
        })
        .catch(err => {
            loader.style.display = "none"; // Ocultamos si hay error de red
            bm_showMsg("Error", "Fallo de conexión al cargar el baúl.");
        });
    }
    function bm_setTab(n) {
        document.getElementById("viewSell").classList.toggle("hidden", n!==1);
        document.getElementById("viewManage").classList.toggle("hidden", n!==2);
        document.getElementById("btnTab1").classList.toggle("active", n===1);
        document.getElementById("btnTab2").classList.toggle("active", n===2);
        if(n===1) bm_loadWh(); else bm_loadSales();
    }
    function bm_openSell(item) {
        bm_sel_slot = item.slot; document.getElementById("mImg").src = "' . __BASE_URL__ . '" + item.img;
        document.getElementById("mName").innerHTML = item.name + (item.level > 0 ? "+"+item.level : "");
        document.getElementById("mPrice").value = ""; document.getElementById("mReceive").innerHTML = "0";
        document.getElementById("modalSell").style.display = "flex";
    }
    function bm_calcTax(el) { document.getElementById("mReceive").innerHTML = Math.floor((parseInt(el.value)||0) * (1 - (bm_tax/100))); }
    function bm_doSell() {
        const p = document.getElementById("mPrice").value; 
        if(p<=0) return bm_showMsg("Error", "Precio inválido");
        
        // Bloquear botón y mostrar loader
        const btn = document.getElementById("btnPublishItem");
        const originalText = btn.innerHTML;
        btn.disabled = true;
        // FIX: Comillas simples escapadas (\') para que no rompan el echo de PHP
        btn.innerHTML = \'<i class="fa fa-spinner fa-spin" style="margin-right: 5px;"></i> PUBLICANDO...\';
        btn.style.opacity = "0.7";
        btn.style.cursor = "not-allowed";

        const fd = new FormData(); 
        fd.append("ajax_action", "publish"); 
        fd.append("slot", bm_sel_slot); 
        fd.append("price", p);
        
        fetch(window.location.href, {method:"POST", body:fd})
        .then(r=>r.json())
        .then(res=>{
            // Restaurar botón
            btn.disabled = false;
            btn.innerHTML = originalText;
            btn.style.opacity = "1";
            btn.style.cursor = "pointer";

            if(res.status==="success") { 
                document.getElementById("modalSell").style.display="none"; 
                bm_showMsg("Éxito", "¡Item publicado!"); 
                bm_loadWh(); 
            } else {
                bm_showMsg("Error", res.message);
            }
        })
        .catch(err => {
            // Restaurar botón por si hay error de red
            btn.disabled = false;
            btn.innerHTML = originalText;
            btn.style.opacity = "1";
            btn.style.cursor = "pointer";
            bm_showMsg("Error", "Fallo de conexión con el servidor.");
        });
    }
    function bm_loadSales() {
        const list = document.getElementById("salesList"); list.innerHTML = "Cargando...";
        const fd = new FormData(); fd.append("ajax_action", "load_sales");
        fetch(window.location.href, {method:"POST", body:fd}).then(r=>r.json()).then(res=>{
            list.innerHTML = "";
            if(res.status==="success"){
                if(res.data.length === 0) list.innerHTML = "<div style=\'text-align:center; padding:30px; color:#555;\'>No tienes ventas activas.</div>";
                res.data.forEach(i=>{
                    const row = document.createElement("div"); row.className = "sale-row";
                    row.innerHTML = `<div style="display:flex;align-items:center;"><img src="${"' . __BASE_URL__ . '" + i.p_img}" style="width:40px;height:40px;object-fit:contain;margin-right:15px;background:#000;border:1px solid #333;border-radius:4px;padding:2px;"><b>${i.p_name}</b></div><div><span style="color:var(--mu-gold);margin-right:15px;font-weight:bold;font-size:14px;">${i.price} ${i.currency}</span><button class="bm-btn bm-red" onclick="bm_askRetract(${i.id})"><i class="fa fa-undo"></i> RETIRAR</button></div>`;
                    list.appendChild(row);
                });
            }
        });
    }
    function bm_askRetract(id) { bm_retract_id = id; document.getElementById("modalConfirm").style.display = "flex"; document.getElementById("btnConfirmRetract").onclick = bm_doRetract; }
    function bm_doRetract() {
        // Bloquear botón y mostrar loader
        const btn = document.getElementById("btnConfirmRetract");
        const originalText = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = \'<i class="fa fa-spinner fa-spin" style="margin-right: 5px;"></i> RETIRANDO...\';
        btn.style.opacity = "0.7";
        btn.style.cursor = "not-allowed";

        const fd = new FormData(); 
        fd.append("ajax_action", "retract"); 
        fd.append("id", bm_retract_id);
        
        fetch(window.location.href, {method:"POST", body:fd})
        .then(r=>r.json())
        .then(res=>{
            // Restaurar botón
            btn.disabled = false;
            btn.innerHTML = originalText;
            btn.style.opacity = "1";
            btn.style.cursor = "pointer";
            
            // Cerrar el modal de confirmación
            document.getElementById("modalConfirm").style.display = "none";

            if(res.status==="success") { 
                bm_showMsg("Éxito", "Item recuperado en tu baúl."); 
                bm_loadSales(); 
            } else {
                bm_showMsg("Error", res.message);
            }
        })
        .catch(err => {
            btn.disabled = false;
            btn.innerHTML = originalText;
            btn.style.opacity = "1";
            btn.style.cursor = "pointer";
            document.getElementById("modalConfirm").style.display = "none";
            bm_showMsg("Error", "Fallo de conexión con el servidor.");
        });
    }
    
    function bm_tt(e, item) {
        const t = document.getElementById("bm-tt");
        const esc = (s) => s.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;");
        
        let isExc = (item.exc && item.exc.length > 0);
        let nameColor = isExc ? "#00ff00" : "#fff"; 
        
        let lvlStr = (item.level > 0) ? " +"+item.level : "";
        let content = `<span style="font-weight:bold; font-size:14px; color:${nameColor}">${isExc ? "Excellent " : ""}${esc(item.name)}${lvlStr}</span><div style="margin-bottom: 5px;"></div>`;
        
        if(item.op380 && item.op380 !== "") content += `<div class="tt-380" style="font-weight:bold;">${item.op380}</div>`;
        if(item.harmony && item.harmony !== "") content += `<div style="color:#ffff00; font-weight:bold;">${item.harmony}</div>`;
        
        if((item.op380 && item.op380 !== "") || (item.harmony && item.harmony !== "")) {
             content += `<div style="margin-bottom: 8px;"></div>`;
        }

        if(item.skill == 1) content += `<div class="tt-opt">Skill Attack Power</div>`;
        if(item.luck == 1) content += `<div class="tt-luck">Luck (success rate +25%)</div><div class="tt-luck">Luck (critical damage rate +5%)</div>`;
        if(item.option > 0) content += `<div class="tt-opt">Additional Option +${item.option}</div>`;
        
        if(item.exc) {
            item.exc.forEach(x => { content += `<div class="tt-exc">${x}</div>`; });
        }

        if(item.sockets && item.sockets.length > 0) {
            let sHtml = "";
            item.sockets.forEach(s => {
                let color = (s.type == "empty") ? "#666" : "#cca4e3"; 
                let lvlTxt = (s.type == "filled") ? " +" + s.lvl : "";
                sHtml += `<div style="color:${color}">${esc(s.text)}${lvlTxt}</div>`;
            });
            content += `<div style="margin-top:8px; border-top:1px dashed #444; padding-top:6px;">${sHtml}</div>`;
        }
        
        t.innerHTML = content; 
        t.style.display = "block";
    }
    
    bm_loadWh();
</script>';
?>