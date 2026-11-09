<?php
/**
 * Rankings Module - Gens System
 * PREPARED FOR OBFUSCATION (Pure PHP Version)
 */

if(!defined('access') or !access) die();

// --- 1. CONFIGURACIÓN DE CANTIDAD ---
$max_limit = isset($max_limit) ? $max_limit : 100;
$qty_c = (isset($rankings_config['qty_chars']) && is_numeric($rankings_config['qty_chars'])) ? min((int)$rankings_config['qty_chars'], $max_limit) : 10;

// --- 2. CONSULTA SQL ---
// g = Gens_Rank, c = Character
$query = "SELECT TOP $qty_c g.Name, g.Contribution as Stat, c.Class, c.cLevel as Extra 
          FROM Gens_Rank g
          INNER JOIN Character c ON g.Name = c.Name
          WHERE 1=1 " . (isset($classFilter) ? $classFilter : "") . " " . (isset($exclude_clause) ? $exclude_clause : "") . " 
          ORDER BY g.Contribution DESC";

$rankingData = $db->query_fetch($query);

// --- 3. RENDERIZADO HTML (PHP PURO) ---

if(!is_array($rankingData)) {
    echo '<tr><td colspan="20" class="text-center py-4 text-white">No hay datos de Gens.</td></tr>';
} else {
    $i = 1;
    foreach($rankingData as $row) {
        // Lógica de Avatar
        $classCode = $row['Class'];
        $avatarImg = 'dw.jpg'; 
        
        if(in_array($classCode, array(0, 1, 2, 3))) $avatarImg = 'dw.jpg';
        elseif(in_array($classCode, array(16, 17, 18, 19))) $avatarImg = 'dk.jpg';
        elseif(in_array($classCode, array(32, 33, 34, 35))) $avatarImg = 'elf.jpg';
        elseif(in_array($classCode, array(48, 49, 50, 51))) $avatarImg = 'mg.jpg';
        elseif(in_array($classCode, array(64, 65, 66, 67))) $avatarImg = 'dl.jpg';
        elseif(in_array($classCode, array(80, 81, 82, 83))) $avatarImg = 'sum.jpg';
        elseif(in_array($classCode, array(96, 97, 98, 99))) $avatarImg = 'rf.jpg';
        elseif(in_array($classCode, array(112, 113, 114, 115))) $avatarImg = 'gl.jpg';
        elseif(in_array($classCode, array(128, 129, 130, 131))) $avatarImg = 'rw.jpg';
        elseif(in_array($classCode, array(144, 145, 146, 147))) $avatarImg = 'sl.jpg';
        elseif(in_array($classCode, array(160, 161, 162, 163))) $avatarImg = 'gc.jpg';
        elseif(in_array($classCode, array(176, 177, 178, 179))) $avatarImg = 'km.jpg';
        elseif(in_array($classCode, array(192, 193, 194, 195))) $avatarImg = 'lm.jpg';
        
        $avatarPath = __BASE_URL__ . "img/character-avatars/" . $avatarImg;

        echo '<tr>';
            
            // 1. Posición
            echo '<td><div class="rank-pos">'.$i.'</div></td>';
            
            // 2. Nombre (Link al perfil)
            echo '<td class="fw-bold text-white char-column">';
            if($rankings_config['show_profile']) {
                echo '<a href="'.__BASE_URL__.'profile/player/req/'.$row['Name'].'" style="color: inherit; text-decoration: none;">'.$row['Name'].'</a>';
            } else {
                echo $row['Name'];
            }
            echo '</td>';

            // 3. Puntos (Gens Contribution)
            echo '<td class="text-orange fw-bold text-center">'.number_format($row['Stat']).'</td>';
            
            // 4. Avatar / Clase
            echo '<td class="text-center"><img src="'.$avatarPath.'" class="rank-avatar-img" title="'.getPlayerClass($classCode).'"></td>';
            
            // 5. Guild (Si está activado en config)
            if($rankings_config['show_guild']) {
                $checkGuild = $db->query_fetch_single("SELECT G_Name FROM GuildMember WHERE Name = ?", array($row['Name']));
                $gName = ($checkGuild && isset($checkGuild['G_Name'])) ? $checkGuild['G_Name'] : false;
                
                if($gName) {
                    echo '<td class="text-white small text-center">'.$gName.'</td>';
                } else {
                    echo '<td class="text-white small text-center"><i class="fa fa-shield-blank"></i> -</td>';
                }
            }
            
        echo '</tr>';
        $i++;
    }
}