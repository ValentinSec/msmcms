<?php
// Lógica para General, Resets y Master Resets
$qty_c = (isset($rankings_config['qty_chars']) && is_numeric($rankings_config['qty_chars'])) ? min((int)$rankings_config['qty_chars'], $max_limit) : 10;

// [MODIFICADO] Agregamos AccountCharacter para saber CUÁL personaje está usando la cuenta realmente
$query = "SELECT TOP $qty_c Character.Name, ResetCount as Stat, cLevel as Extra, Class, MasterResetCount, 
          MEMB_STAT.ConnectStat, AccountCharacter.GameIDC
          FROM Character 
          LEFT JOIN MEMB_STAT ON Character.AccountID = MEMB_STAT.memb___id 
          LEFT JOIN AccountCharacter ON Character.AccountID = AccountCharacter.Id
          WHERE 1=1 $classFilter $exclude_clause 
          ORDER BY ResetCount DESC, cLevel DESC";

$rankingData = $db->query_fetch($query);

if(!is_array($rankingData)) {
    echo '<tr><td colspan="20" class="text-center py-4 text-white">No hay datos disponibles.</td></tr>';
} else {
    $i = 1;
    foreach($rankingData as $row) {
        $classCode = $row['Class'];
        // Lógica de Avatar
        $avatarImg = 'dw.jpg'; 
        if(in_array($classCode, [0, 1, 2, 3])) $avatarImg = 'dw.jpg';
        elseif(in_array($classCode, [16, 17, 18, 19])) $avatarImg = 'dk.jpg';
        elseif(in_array($classCode, [32, 33, 34, 35])) $avatarImg = 'elf.jpg';
        elseif(in_array($classCode, [48, 49, 50, 51])) $avatarImg = 'mg.jpg';
        elseif(in_array($classCode, [64, 65, 66, 67])) $avatarImg = 'dl.jpg';
        elseif(in_array($classCode, [80, 81, 82, 83])) $avatarImg = 'sum.jpg';
        elseif(in_array($classCode, [96, 97, 98, 99])) $avatarImg = 'rf.jpg';
        $avatarPath = __BASE_URL__ . "img/character-avatars/" . $avatarImg;

        echo '<tr>';
        echo '<td><div class="rank-pos">'.$i.'</div></td>';
        
        // Nombre con link al perfil
        echo '<td class="fw-bold text-white char-column">';
        if($rankings_config['show_profile']) {
            echo '<a href="'.__BASE_URL__.'profile/player/req/'.$row['Name'].'" style="color: inherit; text-decoration: none;">'.$row['Name'].'</a>';
        } else { echo $row['Name']; }
        echo '</td>';

        // Master Resets
        if($rankings_config['show_master_resets']) echo '<td class="text-orange fw-bold text-center">'.(isset($row['MasterResetCount']) ? $row['MasterResetCount'] : 0).'</td>';
        
        // Resets
        if($rankings_config['show_resets']) echo '<td class="text-orange fw-bold text-center">'.(isset($row['Stat']) ? $row['Stat'] : 0).'</td>';
        
        // Nivel
        echo '<td class="text-center">'.(isset($row['Extra']) ? $row['Extra'] : 0).'</td>';
        
        // Master Level
        if($rankings_config['show_master_level']) echo '<td class="text-center">0</td>'; 

        // Avatar Clase
        echo '<td class="text-center"><img src="'.$avatarPath.'" class="rank-avatar-img" title="'.getPlayerClass($classCode).'"></td>';

        // Status [CORREGIDO: Valida Cuenta Online + Personaje en Uso]
        if($rankings_config['show_status']) {
            $isAccountOnline = ($row['ConnectStat'] >= 1);
            // Comparamos el nombre de ESTE personaje con el que figura en GameIDC (el actual)
            $isCharPlaying = ($row['Name'] === $row['GameIDC']);
            
            $online = ($isAccountOnline && $isCharPlaying) ? '<span class="text-success">Online</span>' : '<span class="text-danger">Offline</span>';
            echo '<td class="text-center">'.$online.'</td>';
        }

        // Guild
        if($rankings_config['show_guild']) {
            $checkGuild = $db->query_fetch_single("SELECT G_Name FROM GuildMember WHERE Name = '".$row['Name']."'");
            $gName = ($checkGuild && isset($checkGuild['G_Name'])) ? $checkGuild['G_Name'] : false;
            echo $gName ? '<td class="text-white small text-center">'.$gName.'</td>' : '<td class="text-white small text-center"><i class="fa fa-shield-blank"></i> Sin Guild</td>';
        }
        echo '</tr>';
        $i++;
    }
}
?>