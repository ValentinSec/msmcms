<?php
/**
 * User Data Loader Core - PREPARED FOR OBFUSCATION
 * Pure PHP Version (Logic Only)
 */

if(!defined('access') or !access) die();

try {
    // 1. CONEXIÓN Y DATOS BÁSICOS
    $db = Connection::Database('Me_MuOnline');
    $username = isset($_SESSION['username']) ? $_SESSION['username'] : '';

    // Email siempre se busca en MEMB_INFO para la cuenta
    $accountInfo = $db->query_fetch_single("SELECT mail_addr FROM MEMB_INFO WHERE memb___id = ?", [$username]);

    // 2. CARGA DE CONFIGURACIÓN (Desde custom_settings.json)
    $configPath = __DIR__ . '/config/custom_settings.json';
    $cSet = []; 
    
    if (file_exists($configPath)) {
        $content = file_get_contents($configPath);
        $decoded = json_decode($content, true);
        if(is_array($decoded)) {
            $cSet = $decoded;
        }
    }

    // 3. DEFINICIÓN DE TABLA Y COLUMNAS DE MONEDAS
    $rawTableCoins = isset($cSet['table_coins']) && !empty($cSet['table_coins']) ? $cSet['table_coins'] : 'CashShopData';
    $tableCoins = preg_replace('/[^a-zA-Z0-9_]/', '', $rawTableCoins);

    // Identificador (ID) de la Tabla
    $colId = (strcasecmp($tableCoins, 'MEMB_INFO') === 0) ? 'memb___id' : 'AccountID';

    // Columnas a consultar
    $colsToFetch = [];
    $defaults = [1 => 'WCoinC', 2 => 'WCoinP', 3 => 'GoblinPoint'];

    for($i=1; $i<=3; $i++) {
        $isActive = isset($cSet["active_coin_$i"]) && ($cSet["active_coin_$i"] === "on" || $cSet["active_coin_$i"] == 1);
        $colName = isset($cSet["col_coin_$i"]) && !empty($cSet["col_coin_$i"]) ? $cSet["col_coin_$i"] : $defaults[$i];

        if($isActive) {
            $cleanName = preg_replace("/[^a-zA-Z0-9_]/", "", $colName);
            if(!empty($cleanName)) {
                $colsToFetch[] = $cleanName;
            }
        }
    }
    
    if(empty($colsToFetch)) { $colsToFetch = ['WCoinC']; }
    $colsToFetch = array_unique($colsToFetch);

    // 4. CONSULTA DE SALDOS (Dinámica)
    $columnsStr = implode(", ", $colsToFetch);
    $balances = [];

    try {
        $result = $db->query_fetch_single("SELECT $columnsStr FROM $tableCoins WHERE $colId = ?", [$username]);
        
        if(is_array($result)) {
            $balances = $result;
            foreach($result as $k => $v) {
                $balances[strtolower($k)] = $v;
            }
        } else {
            foreach($colsToFetch as $col) {
                $balances[$col] = 0;
                $balances[strtolower($col)] = 0;
            }
        }
    } catch (Exception $sqlErr) {
        foreach($colsToFetch as $col) {
            $balances[$col] = 0;
            $balances[strtolower($col)] = 0;
        }
    }

    // 5. PERSONAJES (Stats)
    try {
        $userCharacters = $db->query_fetch("SELECT Name, Class, cLevel, ResetCount, Money, Strength, Dexterity, Vitality, Energy FROM Character WHERE AccountID = ? ORDER BY Name ASC", [$username]);
    } catch (Exception $charErr) {
        $userCharacters = [];
    }

    // 6. SISTEMA VIP (Lectura Dinámica)
    $rawVipTable = isset($cSet['table_vip']) && !empty($cSet['table_vip']) ? $cSet['table_vip'] : 'MEMB_INFO';
    $vipTable = preg_replace('/[^a-zA-Z0-9_]/', '', $rawVipTable);

    $rawVipLevel = isset($cSet['col_vip_level']) && !empty($cSet['col_vip_level']) ? $cSet['col_vip_level'] : 'AccountLevel';
    $vipColLevel = preg_replace('/[^a-zA-Z0-9_]/', '', $rawVipLevel);

    $rawVipExpire = isset($cSet['col_vip_expire']) && !empty($cSet['col_vip_expire']) ? $cSet['col_vip_expire'] : 'AccountExpireDate';
    $vipColExpire = preg_replace('/[^a-zA-Z0-9_]/', '', $rawVipExpire);
    
    $vipColId = (strcasecmp($vipTable, 'MEMB_INFO') === 0) ? 'memb___id' : 'AccountID';
    $vipData = ['level' => 0, 'expire' => ''];

    try {
        $vipResult = $db->query_fetch_single("SELECT $vipColLevel, $vipColExpire FROM $vipTable WHERE $vipColId = ?", [$username]);
        if(is_array($vipResult)) {
            $vLevel  = isset($vipResult[$vipColLevel]) ? $vipResult[$vipColLevel] : (isset($vipResult[strtolower($vipColLevel)]) ? $vipResult[strtolower($vipColLevel)] : 0);
            $vExpire = isset($vipResult[$vipColExpire]) ? $vipResult[$vipColExpire] : (isset($vipResult[strtolower($vipColExpire)]) ? $vipResult[strtolower($vipColExpire)] : '');
            $vipData = ['level' => (int)$vLevel, 'expire' => $vExpire];
        }
    } catch (Exception $e) {
        $vipData = ['level' => 0, 'expire' => ''];
    }

} catch (Exception $e) {
    $accountInfo = [];
    $balances = [];
    $userCharacters = [];
}

/* --- FUNCIONES AUXILIARES --- */
if (!function_exists('getClassName')) {
    function getClassName($class_id) {
        $classes = [
            0 => 'Dark Wizard', 1 => 'Soul Master', 2 => 'Grand Master',
            16 => 'Dark Knight', 17 => 'Blade Knight', 18 => 'Blade Master',
            32 => 'Elf', 33 => 'Muse Elf', 34 => 'High Elf',
            48 => 'Magic Gladiator', 50 => 'Duel Master',
            64 => 'Dark Lord', 66 => 'Lord Emperor',
            80 => 'Summoner', 81 => 'Bloody Summoner', 82 => 'Dimension Master',
            96 => 'Rage Fighter', 98 => 'Fist Master'
        ];
        return isset($classes[$class_id]) ? $classes[$class_id] : 'Unknown';
    }
}

if (!function_exists('getClassIcon')) {
    function getClassIcon($class_id) {
        if($class_id < 16) return 'fa-wand-sparkles';
        if($class_id < 32) return 'fa-gavel';
        if($class_id < 48) return 'fa-leaf';
        return 'fa-bolt';
    }
}