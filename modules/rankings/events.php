<?php
/**
 * Rankings Module - Events (Blood Castle, Devil Square, Chaos Castle)
 * PREPARED FOR OBFUSCATION (Pure PHP Version)
 * File: modules/rankings/events.php
 */

if(!defined('access') or !access) die();

// 1. CARGA DE CONFIGURACIÓN
$possiblePaths = [
    __PATH_INCLUDES__ . 'config/custom_settings.json',
    __DIR__ . '/../../includes/config/custom_settings.json',
    $_SERVER['DOCUMENT_ROOT'] . '/includes/config/custom_settings.json'
];

$tblCfg = [];
foreach($possiblePaths as $path) {
    if(file_exists($path)) {
        $tblCfg = json_decode(file_get_contents($path), true);
        break;
    }
}

// 2. DEFINICIÓN DE TABLAS Y COLUMNAS DE CONTEO
$t_event = '';
$col_count = '';

// Se asume que $type viene definido globalmente por el Handler de Rankings
$currentType = isset($type) ? $type : 'bc';

switch($currentType) {
    case 'bc':
        $t_event = !empty($tblCfg['table_event_bc']) ? $tblCfg['table_event_bc'] : 'RankingBloodCastle';
        $col_count = 'BCCount'; 
        break;
    case 'ds':
        $t_event = !empty($tblCfg['table_event_ds']) ? $tblCfg['table_event_ds'] : 'RankingDevilSquare';
        $col_count = 'DSCount';
        break;
    case 'cc':
        $t_event = !empty($tblCfg['table_event_cc']) ? $tblCfg['table_event_cc'] : 'RankingChaosCastle';
        $col_count = 'CCCount';
        break;
    default:
        $t_event = 'RankingBloodCastle';
        $col_count = 'BCCount';
        break;
}

// Columnas de configuración (Nombre y Score)
$c_name  = !empty($tblCfg['col_event_name'])  ? $tblCfg['col_event_name']  : 'Name';
$c_score = !empty($tblCfg['col_event_score']) ? $tblCfg['col_event_score'] : 'Score';

$qty_c = (isset($rankings_config['qty_chars']) && is_numeric($rankings_config['qty_chars'])) ? min((int)$rankings_config['qty_chars'], 50) : 10;

// 3. CONSULTA SQL (TRIPLE JOIN)
// e   = Tabla del Evento -> Solo sacamos Name y Score
// c   = Tabla Character -> Sacamos Class (Avatar)
// ent = Tabla EventEntryCount -> Sacamos las Entradas (BCCount/DSCount/CCCount)

$query = "SELECT TOP $qty_c 
            e.[{$c_name}] as Name, 
            e.[{$c_score}] as Stat, 
            c.Class, 
            ent.[{$col_count}] as EntriesToday
          FROM [{$t_event}] e
          LEFT JOIN [Character] c ON e.[{$c_name}] = c.Name
          LEFT JOIN [EventEntryCount] ent ON e.[{$c_name}] = ent.Name
          WHERE 1=1 " . (isset($classFilter) ? $classFilter : "") . " " . (isset($exclude_clause) ? $exclude_clause : "") . " 
          ORDER BY e.[{$c_score}] DESC";

$rankingData = $db->query_fetch($query);

// 4. RENDERIZADO HTML (PHP PURO)

if($rankingData === false || $rankingData === null) {
    echo '<tr><td colspan="6" class="text-center py-4 text-white">';
    echo 'No hay registros disponibles para <strong>'.strtoupper($currentType).'</strong>.';
    echo '</td></tr>';
} 
elseif(empty($rankingData)) {
    echo '<tr><td colspan="6" class="text-center py-4 text-white">';
    echo 'El ranking de <strong>'.strtoupper($currentType).'</strong> está vacío.';
    echo '</td></tr>';
} 
else {
    $i = 1;
    foreach($rankingData as $row) {
        // Lógica de Avatar
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
        
        $avatarPath = __BASE_URL__ . "img/character-avatars/" . $avatarImg;

        echo '<tr>';
            // 1. POSICIÓN
            echo '<td><div class="rank-pos">'.$i.'</div></td>';
            
            // 2. JUGADOR
            echo '<td class="fw-bold text-white char-column">';
            if($rankings_config['show_profile']) {
                echo '<a href="'.__BASE_URL__.'profile/player/req/'.$row['Name'].'" style="color: inherit; text-decoration: none;">'.$row['Name'].'</a>';
            } else {
                echo $row['Name'];
            }
            echo '</td>';

            // 3. CLASE (AVATAR)
            echo '<td class="text-center"><img src="'.$avatarPath.'" class="rank-avatar-img" title="'.getPlayerClass($classCode).'"></td>';
            
            // 4. SCORE
            echo '<td class="text-orange fw-bold text-center">'.number_format($row['Stat']).'</td>';
            
            // 5. ENTRADAS HOY
            $entradas = isset($row['EntriesToday']) ? $row['EntriesToday'] : 0;
            echo '<td class="text-white text-center fw-bold">'.$entradas.'</td>';
        echo '</tr>';
        $i++;
    }
}