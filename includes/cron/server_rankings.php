<?php
/**
 * Cron: Generador Unificado de Rankings (All-in-One)
 * Archivo: includes/cron/server_rankings.php
 * Descripción: Recopila General, Guilds, Killers, Eventos, Gens, Duels y ELO en un solo caché.
 */

if(!defined('access')) die("Access Denied");

try {
    // 1. Conexión a Base de Datos
    if(!isset($db) || !is_object($db)) {
        $db = Connection::Database('MuOnline');
    }

    $limit = 50; // Cantidad de registros por ranking
    $gmFilter = "CtlCode < 32"; // Filtrar GMs

    // Array maestro que guardará todo
    $cachePayload = [
        'updated' => time(),
        'general' => [],
        'guilds' => [],
        'killers' => [],
        'bc' => [],
        'ds' => [],
        'cc' => [],
        'gens' => [],
        'duels' => [],
        'elo' => []
    ];

    // ====================================================================================
    // 1. RANKING GENERAL (Top Players)
    // ====================================================================================
    // Incluimos cLevel, Resets, MasterReset, PkCount, etc.
    // Hacemos LEFT JOIN con GuildMember para sacar el Clan y MEMB_STAT para el Status (Online/Off)
    
    // Detectar si existe tabla MasterSkillTree para MasterLevel
    $hasMaster = $db->query_fetch_single("SELECT count(*) FROM information_schema.tables WHERE table_name = 'MasterSkillTree'");
    $mlSelect = ($hasMaster) ? ", m.MasterLevel" : ", 0 as MasterLevel";
    $mlJoin   = ($hasMaster) ? "LEFT JOIN MasterSkillTree m ON c.Name = m.Name" : "";

    $sqlGeneral = "SELECT TOP $limit 
                    c.Name, c.Class, c.cLevel, c.ResetCount, c.MasterResetCount, c.PkCount, 
                    c.Strength, c.Dexterity, c.Vitality, c.Energy, c.Leadership, c.MapNumber,
                    g.G_Name, ms.ConnectStat, ms.ServerName 
                   $mlSelect
                   FROM Character c
                   LEFT JOIN GuildMember g ON c.Name = g.Name
                   LEFT JOIN MEMB_STAT ms ON c.AccountID = ms.memb___id
                   $mlJoin
                   WHERE $gmFilter
                   ORDER BY c.ResetCount DESC, c.cLevel DESC, c.Experience DESC";

    $rows = $db->query_fetch($sqlGeneral);
    if(is_array($rows)) {
        foreach($rows as $r) {
            $cachePayload['general'][] = [
                'name'    => $r['Name'],
                'class'   => $r['Class'],
                'level'   => (int)$r['cLevel'],
                'resets'  => (int)$r['ResetCount'],
                'mresets' => (int)$r['MasterResetCount'],
                'mlevel'  => (int)$r['MasterLevel'],
                'pk'      => (int)$r['PkCount'],
                'guild'   => $r['G_Name'],
                'status'  => ($r['ConnectStat'] == 1), // true si está online
                'map'     => (int)$r['MapNumber'],
                'stats'   => [
                    'str' => (int)$r['Strength'], 'agi' => (int)$r['Dexterity'],
                    'vit' => (int)$r['Vitality'], 'ene' => (int)$r['Energy'], 'cmd' => (int)$r['Leadership']
                ]
            ];
        }
    }

    // ====================================================================================
    // 2. RANKING DE GUILDS
    // ====================================================================================
    $sqlGuilds = "SELECT TOP $limit G_Name, G_Master, G_Score, G_Mark, G_Count FROM Guild ORDER BY G_Score DESC";
    $rowsG = $db->query_fetch($sqlGuilds);
    if(is_array($rowsG)) {
        foreach($rowsG as $g) {
            $cachePayload['guilds'][] = [
                'name'    => $g['G_Name'],
                'master'  => $g['G_Master'],
                'score'   => (int)$g['G_Score'],
                'members' => (int)$g['G_Count'],
                'mark'    => bin2hex($g['G_Mark']) 
            ];
        }
    }

    // ====================================================================================
    // 3. RANKING DE KILLERS (Solo los que tengan kills)
    // ====================================================================================
    // Reutilizamos datos si ya los tenemos en memoria, o hacemos query específica optimizada
    $sqlKillers = "SELECT TOP $limit Name, Class, PkCount, cLevel FROM Character WHERE $gmFilter AND PkCount > 0 ORDER BY PkCount DESC";
    $rowsK = $db->query_fetch($sqlKillers);
    if(is_array($rowsK)) {
        foreach($rowsK as $k) {
            $cachePayload['killers'][] = [
                'name'  => $k['Name'],
                'class' => $k['Class'],
                'kills' => (int)$k['PkCount'],
                'level' => (int)$k['cLevel']
            ];
        }
    }

    // ====================================================================================
    // 4. RANKING DE EVENTOS (BC, DS, CC)
    // ====================================================================================
    // Mapeo de tablas estándar. Si tu server usa otras, ajustá los nombres aquí.
    $events = [
        'bc' => ['table' => 'RankingBloodCastle', 'col_score' => 'Score', 'col_name' => 'Name'],
        'ds' => ['table' => 'RankingDevilSquare', 'col_score' => 'Score', 'col_name' => 'Name'],
        'cc' => ['table' => 'RankingChaosCastle', 'col_score' => 'Score', 'col_name' => 'Name']
    ];

    foreach($events as $key => $cfg) {
        // Verificar si la tabla existe antes de consultar para no romper el cron
        $checkTable = $db->query_fetch_single("SELECT count(*) as c FROM information_schema.tables WHERE table_name = '".$cfg['table']."'");
        if(isset($checkTable['c']) && $checkTable['c'] > 0) {
            $sqlEvt = "SELECT TOP $limit t.".$cfg['col_name']." as Name, t.".$cfg['col_score']." as Score, c.Class 
                       FROM ".$cfg['table']." t 
                       JOIN Character c ON t.".$cfg['col_name']." = c.Name 
                       WHERE c.$gmFilter 
                       ORDER BY t.".$cfg['col_score']." DESC";
            
            $rowsEvt = $db->query_fetch($sqlEvt);
            if(is_array($rowsEvt)) {
                foreach($rowsEvt as $re) {
                    $cachePayload[$key][] = [
                        'name'  => $re['Name'],
                        'score' => (int)$re['Score'],
                        'class' => $re['Class']
                    ];
                }
            }
        }
    }

    // ====================================================================================
    // 5. RANKING GENS
    // ====================================================================================
    // Intentamos buscar tabla Gens_Rank o columna en Character
    $hasGensTable = $db->query_fetch_single("SELECT count(*) as c FROM information_schema.tables WHERE table_name = 'Gens_Rank'");
    if($hasGensTable && $hasGensTable['c'] > 0) {
        $sqlGens = "SELECT TOP $limit Name, Contribution, Family FROM Gens_Rank ORDER BY Contribution DESC";
    } else {
        // Fallback: Columna en Character
        $sqlGens = "SELECT TOP $limit Name, GensContribution as Contribution, GensFamily as Family FROM Character WHERE GensFamily > 0 AND $gmFilter ORDER BY GensContribution DESC";
    }
    
    // Ejecutamos query Gens en bloque try por si la columna no existe en fallback
    try {
        $rowsGens = $db->query_fetch($sqlGens);
        if(is_array($rowsGens)) {
            foreach($rowsGens as $rg) {
                $cachePayload['gens'][] = [
                    'name'   => $rg['Name'],
                    'points' => (int)$rg['Contribution'],
                    'family' => (int)$rg['Family'] // 1: Duprian, 2: Vanert
                ];
            }
        }
    } catch(Exception $ex) {} // Ignorar si falla Gens

    // ====================================================================================
    // 6. RANKING DUELS
    // ====================================================================================
    $checkDuel = $db->query_fetch_single("SELECT count(*) as c FROM information_schema.tables WHERE table_name = 'RankingDuel'");
    if(isset($checkDuel['c']) && $checkDuel['c'] > 0) {
        $sqlDuel = "SELECT TOP $limit t.Name, t.WinScore, t.LoseScore, c.Class 
                    FROM RankingDuel t 
                    JOIN Character c ON t.Name = c.Name 
                    WHERE c.$gmFilter 
                    ORDER BY t.WinScore DESC";
        $rowsDuel = $db->query_fetch($sqlDuel);
        if(is_array($rowsDuel)) {
            foreach($rowsDuel as $rd) {
                $cachePayload['duels'][] = [
                    'name' => $rd['Name'],
                    'win'  => (int)$rd['WinScore'],
                    'lose' => (int)$rd['LoseScore'],
                    'class'=> $rd['Class']
                ];
            }
        }
    }

    // ====================================================================================
    // 7. RANKING ELO (Custom)
    // ====================================================================================
    // Solo si existe la columna EloScore
    try {
        $sqlElo = "SELECT TOP $limit Name, EloScore, Class FROM Character WHERE $gmFilter AND EloScore > 0 ORDER BY EloScore DESC";
        $rowsElo = $db->query_fetch($sqlElo);
        if(is_array($rowsElo)) {
            foreach($rowsElo as $relo) {
                $cachePayload['elo'][] = [
                    'name'  => $relo['Name'],
                    'score' => (int)$relo['EloScore'],
                    'class' => $relo['Class']
                ];
            }
        }
    } catch(Exception $ex) {} 

    // ====================================================================================
    // FINAL: GUARDAR ARCHIVO
    // ====================================================================================
    $cachePath = __PATH_INCLUDES__ . 'cache/rankings_unified.cache';
    $fp = @fopen($cachePath, 'w');
    if($fp) {
        fwrite($fp, json_encode($cachePayload));
        fclose($fp);
        if(access == 'cron') echo "All Rankings Updated Successfully.<br>";
    } else {
        if(access == 'cron') echo "Error: Cannot write rankings_unified.cache<br>";
    }

} catch (Exception $e) {
    if(access == 'cron') echo "Ranking Cron Error: " . $e->getMessage();
}
?>