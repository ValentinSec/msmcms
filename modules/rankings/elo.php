<?php
/**
 * Rankings Module - ELO System (Fixed Path & Logic)
 * PREPARED FOR OBFUSCATION
 * Location: modules/rankings/elo.php
 */

if(!defined('access') or !access) die();

// ==========================================================
// 1. LÓGICA DE RANGOS (RECALIBRADA)
// ==========================================================

if (!function_exists('getEloRankInfo')) {
    function getEloRankInfo($score) {
        $basePath = __BASE_URL__ . 'img/elo/';
        
        if ($score < 1000) return array('name' => 'Hierro 1', 'img' => $basePath . 'hierro1.png');

        if ($score <= 1199) return array('name' => 'Hierro 2', 'img' => $basePath . 'hierro2.png');
        if ($score <= 1399) return array('name' => 'Hierro 3', 'img' => $basePath . 'hierro3.png');

        if ($score <= 1699) return array('name' => 'Bronce 1', 'img' => $basePath . 'bronce1.png');
        if ($score <= 1999) return array('name' => 'Bronce 2', 'img' => $basePath . 'bronce2.png');
        if ($score <= 2299) return array('name' => 'Bronce 3', 'img' => $basePath . 'bronce3.png');

        if ($score <= 2699) return array('name' => 'Plata 1', 'img' => $basePath . 'plata1.png');
        if ($score <= 3099) return array('name' => 'Plata 2', 'img' => $basePath . 'plata2.png');
        if ($score <= 3499) return array('name' => 'Plata 3', 'img' => $basePath . 'plata3.png');

        if ($score <= 3999) return array('name' => 'Oro 1', 'img' => $basePath . 'oro1.png');
        if ($score <= 4499) return array('name' => 'Oro 2', 'img' => $basePath . 'oro2.png');
        if ($score <= 4999) return array('name' => 'Oro 3', 'img' => $basePath . 'oro3.png');

        if ($score <= 5599) return array('name' => 'Platino 1', 'img' => $basePath . 'platino1.png');
        if ($score <= 6199) return array('name' => 'Platino 2', 'img' => $basePath . 'platino2.png');
        if ($score <= 6799) return array('name' => 'Platino 3', 'img' => $basePath . 'platino3.png');

        if ($score <= 7499) return array('name' => 'Diamante 1', 'img' => $basePath . 'diamante1.png');
        if ($score <= 8199) return array('name' => 'Diamante 2', 'img' => $basePath . 'diamante2.png');
        if ($score <= 8899) return array('name' => 'Diamante 3', 'img' => $basePath . 'diamante3.png');

        if ($score <= 9699)  return array('name' => 'Legenda 1', 'img' => $basePath . 'legenda1.png');
        if ($score <= 10499) return array('name' => 'Legenda 2', 'img' => $basePath . 'legenda2.png');
        if ($score <= 11299) return array('name' => 'Legenda 3', 'img' => $basePath . 'legenda3.png');

        return array('name' => 'Challenger', 'img' => $basePath . 'challenger.png');
    }
}

if (!function_exists('getEloAvatar')) {
    function getEloAvatar($code) {
        $code = (int)$code;
        if(in_array($code, array(0, 1, 2, 3, 7))) return 'dw';
        if(in_array($code, array(16, 17, 18, 19))) return 'dk';
        if(in_array($code, array(32, 33, 34, 35))) return 'elf';
        if(in_array($code, array(48, 49, 50, 51))) return 'mg';
        if(in_array($code, array(64, 65, 66, 67))) return 'dl';
        if(in_array($code, array(80, 81, 82, 83))) return 'sum';
        if(in_array($code, array(96, 97, 98, 99))) return 'rf';
        if(in_array($code, array(112, 113, 114))) return 'gl';
        if(in_array($code, array(128, 129, 130))) return 'rw';
        if(in_array($code, array(144, 145, 146))) return 'sl';
        if(in_array($code, array(160, 161, 162))) return 'gc';
        return 'avatar';
    }
}

// ==========================================================
// 2. CONSULTA SQL
// ==========================================================

$db = Connection::Database('Me_MuOnline');
$tableName = 'PvPSystem';

$query = "SELECT TOP 50 e.Name, e.Elo, c.Class FROM " . $tableName . " e LEFT JOIN Character c ON e.Name = c.Name ORDER BY e.Elo DESC";
$rankingData = $db->query_fetch($query);

if($rankingData === false || $rankingData === null) {
    $tableName = 'MUSERVERMAKER_PVP_SYSTEM';
    $query = "SELECT TOP 50 e.Name, e.Elo, c.Class FROM " . $tableName . " e LEFT JOIN Character c ON e.Name = c.Name ORDER BY e.Elo DESC";
    $rankingData = $db->query_fetch($query);
}

// ==========================================================
// 3. RENDERIZADO
// ==========================================================

if($rankingData === false || $rankingData === null) {
    echo '<tr><td colspan="4" class="text-center py-4 text-danger fw-bold"><i class="fa fa-exclamation-triangle"></i> ERROR SQL</td></tr>';
} 
elseif(empty($rankingData)) {
    echo '<tr><td colspan="4" class="text-center py-4 text-white"><i class="fa fa-info-circle"></i> Aún no hay registros en el Ranking ELO.</td></tr>';
} 
else {
    foreach($rankingData as $row) {
        $name = htmlspecialchars($row['Name']);
        $score = (int)$row['Elo'];
        $classCode = isset($row['Class']) ? $row['Class'] : -1;
        
        $avatarName = getEloAvatar($classCode);
        $avatarPath = __BASE_URL__ . 'img/character-avatars/' . $avatarName . '.jpg';
        $rankInfo = getEloRankInfo($score);
        
        echo '<tr>';
            echo '<td class="text-center" style="vertical-align: middle;">';
                echo '<img src="' . $rankInfo['img'] . '" alt="' . $rankInfo['name'] . '" title="' . $rankInfo['name'] . '" style="width: 45px; height: auto; display:block; margin: 0 auto;">';
                echo '<span style="font-size:9px; color:#aaa; font-weight:700; text-transform:uppercase; margin-top:4px; display:block;">' . $rankInfo['name'] . '</span>';
            echo '</td>';
            
            echo '<td style="vertical-align: middle;">';
                echo '<span style="font-size: 1rem; font-weight: bold; color: #fff;">' . $name . '</span>';
            echo '</td>';
            
            echo '<td class="text-center" style="vertical-align: middle;">';
                echo '<img src="' . $avatarPath . '" class="rank-avatar-img" title="Class" onerror="this.src=\'' . __BASE_URL__ . 'img/character-avatars/avatar.jpg\'">';
            echo '</td>';
            
            echo '<td class="text-center" style="vertical-align: middle;">';
                echo '<span style="color: #f39c12; font-weight: 800; font-size: 1.1rem; text-shadow: 0 0 10px rgba(243, 156, 18, 0.3);">' . number_format($score) . '</span>';
            echo '</td>';
        echo '</tr>';
    }
}