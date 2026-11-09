<?php
/**
 * Rankings Module - Guild System
 * PREPARED FOR OBFUSCATION (Pure PHP Version)
 */

if(!defined('access') or !access) die();

// --- 1. CONFIGURACIÓN DE CANTIDAD ---
$max_limit = isset($max_limit) ? $max_limit : 100;
$qty_g = (isset($rankings_config['qty_guilds']) && is_numeric($rankings_config['qty_guilds'])) ? min((int)$rankings_config['qty_guilds'], $max_limit) : 10;

// --- 2. CONSULTA SQL ---
$query = "SELECT TOP $qty_g G_Name as Name, G_Mark, G_Master, G_Score as Stat, (SELECT COUNT(*) FROM GuildMember WHERE G_Name = Guild.G_Name) as Extra FROM Guild ORDER BY G_Score DESC";
$rankingData = $db->query_fetch($query);

// --- 3. RENDERIZADO HTML (PHP PURO) ---

if(!is_array($rankingData)) {
    echo '<tr><td colspan="20" class="text-center py-4 text-white">No hay Guilds registradas.</td></tr>';
} else {
    $i = 1;
    foreach($rankingData as $row) {
        echo '<tr>';
            
            // 1. Posición
            echo '<td><div class="rank-pos">' . $i . '</div></td>';
            
            // 2. Nombre de la Guild
            echo '<td class="fw-bold text-white">' . htmlspecialchars($row['Name']) . '</td>';
            
            // 3. Logo (G_Mark)
            $guildMark = urlencode(bin2hex($row['G_Mark']));
            echo '<td><img src="' . __BASE_URL__ . 'api/guildmark.php?data=' . $guildMark . '" width="20" height="20"></td>';
            
            // 4. Guild Master
            echo '<td>' . htmlspecialchars($row['G_Master']) . '</td>';
            
            // 5. Score (Stat)
            echo '<td class="text-orange fw-bold">' . number_format($row['Stat']) . '</td>';
            
            // 6. Miembros (Extra)
            echo '<td>' . number_format($row['Extra']) . '</td>';
            
        echo '</tr>';
        $i++;
    }
}