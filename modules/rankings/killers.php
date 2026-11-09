<?php
/**
 * Rankings Module - Killers (Top Asesinos) - FIX
 * PREPARED FOR OBFUSCATION (Pure PHP Version)
 * File: modules/rankings/killers.php
 */

if(!defined('access') or !access) die();

// 1. CARGA DE CONFIGURACIÓN
$confFile = 'includes/config/custom_settings.json';
$fullPath = $_SERVER['DOCUMENT_ROOT'] . '/' . $confFile;

$tblCfg = array();
if (file_exists($fullPath)) {
    $tblCfg = json_decode(file_get_contents($fullPath), true);
} elseif (defined('__PATH_INCLUDES__') && file_exists(__PATH_INCLUDES__ . 'config/custom_settings.json')) {
    $tblCfg = json_decode(file_get_contents(__PATH_INCLUDES__ . 'config/custom_settings.json'), true);
}

// 2. DEFINICIÓN DE VARIABLES
$t_char  = !empty($tblCfg['table_kills']) ? $tblCfg['table_kills'] : 'Character';
$c_kills = !empty($tblCfg['col_kills'])   ? $tblCfg['col_kills']   : 'PkCount';

$qty_k = (isset($rankings_config['qty_killers']) && is_numeric($rankings_config['qty_killers'])) ? min((int)$rankings_config['qty_killers'], 50) : 10;

$classFilter = "";
$exclude_clause = "";

// 3. CONSULTA SQL DINÁMICA
try {
    $query = "SELECT TOP $qty_k 
                Name, 
                [$c_kills] as Stat, 
                Class, 
                cLevel as Extra 
              FROM [$t_char] 
              WHERE [$c_kills] > 0 $classFilter $exclude_clause 
              ORDER BY [$c_kills] DESC";

    $rankingData = $db->query_fetch($query);

} catch (Exception $e) {
    $rankingData = false;
    $sqlError = $e->getMessage();
}

// 4. RENDERIZADO HTML (PHP PURO)

if($rankingData === false) {
    echo '<tr><td colspan="7" class="text-center py-4 text-danger fw-bold">';
    echo '<i class="fa fa-exclamation-triangle"></i> ERROR SQL<br>';
    if(isset($sqlError)) {
        echo '<small style="color:#ffcccc;">' . $sqlError . '</small>';
    } else {
        echo '<small style="color:#ccc;">No se pudo leer la tabla <u>' . $t_char . '</u> o la columna <u>' . $c_kills . '</u>.</small>';
    }
    echo '</td></tr>';
} 
elseif(empty($rankingData)) {
    echo '<tr><td colspan="7" class="text-center py-4 text-white">';
    echo 'No hay asesinos registrados aún (Kills > 0).';
    echo '</td></tr>';
} 
else {
    $i = 1;
    foreach($rankingData as $row) {
        $classCode = isset($row['Class']) ? $row['Class'] : -1;
        $avatarImg = 'dw.jpg';
        
        if(in_array($classCode, array(0, 1, 2, 3, 7))) $avatarImg = 'dw.jpg'; 
        elseif(in_array($classCode, array(16, 17, 18, 19, 23))) $avatarImg = 'dk.jpg'; 
        elseif(in_array($classCode, array(32, 33, 34, 35, 39))) $avatarImg = 'elf.jpg'; 
        elseif(in_array($classCode, array(48, 49, 50, 51))) $avatarImg = 'mg.jpg'; 
        elseif(in_array($classCode, array(64, 65, 66, 67))) $avatarImg = 'dl.jpg'; 
        elseif(in_array($classCode, array(80, 81, 82, 83))) $avatarImg = 'sum.jpg'; 
        elseif(in_array($classCode, array(96, 97, 98, 99))) $avatarImg = 'rf.jpg'; 
        elseif(in_array($classCode, array(112, 113, 114, 115))) $avatarImg = 'gl.jpg'; 
        
        $avatarPath = __BASE_URL__ . "img/character-avatars/" . $avatarImg;

        echo '<tr>';
            // 1. POS
            echo '<td><div class="rank-pos">' . $i . '</div></td>';
            
            // 2. NOMBRE
            echo '<td class="fw-bold text-white char-column">';
            if(isset($rankings_config['show_profile']) && $rankings_config['show_profile']) {
                echo '<a href="' . __BASE_URL__ . 'profile/player/req/' . $row['Name'] . '" style="color: inherit; text-decoration: none;">' . $row['Name'] . '</a>';
            } else {
                echo $row['Name'];
            }
            echo '</td>';

            // 3. KILLS (STAT)
            echo '<td class="text-orange fw-bold text-center">' . number_format($row['Stat']) . '</td>';
            
            // 4. NIVEL (EXTRA)
            echo '<td class="text-center">' . (isset($row['Extra']) ? $row['Extra'] : 0) . '</td>';
            
            // 5. CLASE (AVATAR)
            $className = (function_exists('getPlayerClass')) ? getPlayerClass($classCode) : 'Class';
            echo '<td class="text-center"><img src="' . $avatarPath . '" class="rank-avatar-img" title="' . $className . '" alt="' . $className . '"></td>';

            // 6. STATUS
            if(isset($rankings_config['show_status']) && $rankings_config['show_status']) {
                $isOnline = (function_exists('isOnline') && isOnline($row['Name']));
                $online = $isOnline ? '<span class="text-success">Online</span>' : '<span class="text-danger">Offline</span>';
                echo '<td class="text-center">' . $online . '</td>';
            }

            // 7. GUILD
            if(isset($rankings_config['show_guild']) && $rankings_config['show_guild']) {
                $gName = '-';
                try {
                    $checkGuild = $db->query_fetch_single("SELECT G_Name FROM GuildMember WHERE Name = ?", array($row['Name']));
                    if($checkGuild && isset($checkGuild['G_Name'])) {
                        $gName = $checkGuild['G_Name'];
                    }
                } catch (Exception $ge) {}

                if($gName != '-') {
                    echo '<td class="text-white small text-center">' . $gName . '</td>';
                } else {
                    echo '<td class="text-white small text-center"><i class="fa fa-shield-blank" style="opacity:0.3"></i></td>';
                }
            }
        echo '</tr>';
        $i++;
    }
}