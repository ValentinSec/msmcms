<?php
/**
 * Rankings Module - Duels (FIXED VERSION)
 */

// 1. BLINDAJE DE VARIABLES GLOBALES Y CONSTANTES
global $db; 
$baseUrl = defined('__BASE_URL__') ? __BASE_URL__ : './';

// 2. CARGA ROBUSTA DE CONFIGURACIÓN
$possiblePaths = [
    defined('__PATH_INCLUDES__') ? __PATH_INCLUDES__ . 'config/custom_settings.json' : '',
    __DIR__ . '/../../includes/config/custom_settings.json',
    $_SERVER['DOCUMENT_ROOT'] . '/includes/config/custom_settings.json'
];

$tblCfg = [];
foreach($possiblePaths as $path) {
    if(!empty($path) && file_exists($path)) {
        $json = file_get_contents($path);
        if($json) {
            $tblCfg = json_decode($json, true);
            break;
        }
    }
}

// 3. DEFINICIÓN DE VARIABLES
$t_duel = !empty($tblCfg['table_duels'])    ? $tblCfg['table_duels']    : 'RankingDuel';
$c_name = !empty($tblCfg['col_duel_name']) ? $tblCfg['col_duel_name'] : 'Name';
$c_win  = !empty($tblCfg['col_duel_win'])  ? $tblCfg['col_duel_win']  : 'WinScore';
$c_lose = !empty($tblCfg['col_duel_lose']) ? $tblCfg['col_duel_lose'] : 'LoseScore';

// 4. CONSULTA SQL
$qty_c = 10;
$query = "SELECT TOP $qty_c 
            d.{$c_name} as Name, 
            d.{$c_win} as Wins, 
            d.{$c_lose} as Losses, 
            (d.{$c_win} * 3) as Score, 
            c.Class 
          FROM {$t_duel} d
          LEFT JOIN Character c ON d.{$c_name} = c.Name
          ORDER BY Score DESC, Wins DESC";

// Ejecución segura
$rankingData = [];
if(isset($db)) {
    // Silenciamos errores potenciales para evitar que el script muera si la tabla no existe
    try {
        $rankingData = $db->query_fetch($query);
    } catch (Exception $e) {
        $rankingData = false;
    }
}

// 5. RENDERIZADO LÓGICO
// Aquí está el cambio: Si es false, null o array vacío, mostramos "No hay registros"
if(empty($rankingData) || !is_array($rankingData)) {
    echo '<tr><td colspan="6" class="text-center py-4 text-white">';
    echo '<i class="fa fa-info-circle" style="font-size: 20px; margin-bottom: 10px; opacity: 0.7;"></i><br>';
    echo 'Aún no hay duelos registrados en el servidor.<br>';
    echo '<small class="text-muted">¡Sé el primero en inaugurar el ranking!</small>';
    echo '</td></tr>';
} 
else {
    $i = 1;
    foreach($rankingData as $row) {
        // Validación extra por si viene un row vacío
        if(!isset($row['Name'])) continue;

        $classCode = isset($row['Class']) ? $row['Class'] : -1;
        $avatarImg = 'dw.jpg';
        
        if(in_array($classCode, [0, 1, 2, 3])) $avatarImg = 'dw.jpg';
        elseif(in_array($classCode, [16, 17, 18, 19])) $avatarImg = 'dk.jpg';
        elseif(in_array($classCode, [32, 33, 34, 35])) $avatarImg = 'elf.jpg';
        elseif(in_array($classCode, [48, 49, 50, 51])) $avatarImg = 'mg.jpg';
        elseif(in_array($classCode, [64, 65, 66, 67])) $avatarImg = 'dl.jpg';
        elseif(in_array($classCode, [80, 81, 82, 83])) $avatarImg = 'sum.jpg';
        elseif(in_array($classCode, [96, 97, 98, 99])) $avatarImg = 'rf.jpg';
        elseif(in_array($classCode, [112, 113, 114, 115])) $avatarImg = 'gl.jpg';
        elseif(in_array($classCode, [128, 129, 130, 131])) $avatarImg = 'rw.jpg';
        elseif(in_array($classCode, [144, 145, 146, 147])) $avatarImg = 'sl.jpg';
        elseif(in_array($classCode, [160, 161, 162, 163])) $avatarImg = 'gc.jpg';
        elseif(in_array($classCode, [176, 177, 178, 179])) $avatarImg = 'km.jpg';
        elseif(in_array($classCode, [192, 193, 194, 195])) $avatarImg = 'lm.jpg';
        
        $avatarPath = $baseUrl . "img/character-avatars/" . $avatarImg;

        echo '<tr>';
        echo '<td><div class="rank-pos">'.$i.'</div></td>';
        
        echo '<td class="fw-bold text-white char-column">';
        echo $row['Name'];
        echo '</td>';

        echo '<td class="text-center"><img src="'.$avatarPath.'" class="rank-avatar-img" width="30" style="border-radius: 50%; border: 1px solid #444;"></td>';
        echo '<td class="text-success fw-bold text-center">'.number_format($row['Wins']).'</td>';
        echo '<td class="text-danger fw-bold text-center">'.number_format($row['Losses']).'</td>';
        echo '<td class="text-orange fw-bolder text-center" style="font-size: 1.1em;">'.number_format($row['Score']).'</td>';
        echo '</tr>';
        $i++;
    }
}
?>