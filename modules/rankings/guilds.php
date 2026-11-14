<?php
/**
 * Perfil de guild.
 * Usa templates/misc/css/player.css (misma estetica que el perfil de personaje).
 */

if(!defined('access') or !access) die();

$gpEsc = function($s) { return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); };
$gpMsg = function($text) {
    echo '<div class="profile-wrapper guild-profile py-5"><div class="profile-card p-5 text-center"><h4 class="trajan-title m-0">' . $text . '</h4></div></div>';
};

if(!function_exists('msm_gp_class_info')) {
    // Devuelve array(nombre de la clase, archivo de avatar sin extension)
    function msm_gp_class_info($class) {
        if($class === null || $class === '') return array('Desconocida', 'avatar');
        $class = (int)$class;
        $base = intdiv($class, 16);
        $tier = $class % 16;
        $map = array(
            0 => array('dw',  array('Dark Wizard', 'Soul Master', 'Grand Master')),
            1 => array('dk',  array('Dark Knight', 'Blade Knight', 'Blade Master')),
            2 => array('elf', array('Fairy Elf', 'Muse Elf', 'High Elf')),
            3 => array('mg',  array('Magic Gladiator', 'Duel Master')),
            4 => array('dl',  array('Dark Lord', 'Lord Emperor')),
            5 => array('sum', array('Summoner', 'Bloody Summoner', 'Dimension Master')),
            6 => array('rf',  array('Rage Fighter', 'Fist Master')),
        );
        if(!isset($map[$base])) return array('Clase ' . $class, 'avatar');
        $names = $map[$base][1];
        return array($names[min($tier, count($names) - 1)], $map[$base][0]);
    }
}

// Hoja compartida con el perfil de personaje
$gpCssVer = (int)@filemtime(dirname(__DIR__, 2) . '/templates/misc/css/player.css');
echo '<link rel="stylesheet" href="' . __BASE_URL__ . 'templates/misc/css/player.css?v=' . $gpCssVer . '">';

// --- 1. Modulo habilitado ---
$gpCfg = function_exists('muservermakerConfigs') ? muservermakerConfigs() : array();
if(is_array($gpCfg) && array_key_exists('guild_profiles', $gpCfg) && !$gpCfg['guild_profiles']) {
    $gpMsg('M&oacute;dulo no habilitado');
    return;
}

// --- 2. Nombre del guild ---
$gpName = '';
foreach(array('req', 'request', 'name') as $gpKey) {
    if(isset($_GET[$gpKey]) && is_string($_GET[$gpKey]) && trim($_GET[$gpKey], " /") !== '') {
        $gpName = trim($_GET[$gpKey], " /");
        break;
    }
}
if($gpName === '') {
    $gpParts = array_values(array_filter(explode('/', (string)parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)), 'strlen'));
    $gpName = !empty($gpParts) ? rawurldecode($gpParts[count($gpParts) - 1]) : '';
}
if(strpos($gpName, '/') !== false) {
    $gpParts = array_values(array_filter(explode('/', $gpName), 'strlen'));
    $gpName = !empty($gpParts) ? $gpParts[count($gpParts) - 1] : '';
}
$gpName = trim(strip_tags($gpName));

if($gpName === '' || strlen($gpName) > 64 || in_array(strtolower($gpName), array('guild', 'profile'), true)) {
    $gpMsg('Seleccion&aacute; un guild');
    return;
}

// --- 3. Datos ---
$gpGuild = null; $gpMembers = array(); $gpIsCastleOwner = false;
try {
    $gpDb = Connection::Database('MuOnline');
    if(!is_object($gpDb)) throw new Exception('Sin conexion');

    $tG = _TBL_GUILD_;      $cGN = _CLMN_GUILD_NAME_;     $cGM = _CLMN_GUILD_MASTER_;
    $cGS = _CLMN_GUILD_SCORE_;  $cGL = _CLMN_GUILD_LOGO_;
    $tM = _TBL_GUILDMEMB_;  $cMG = _CLMN_GUILDMEMB_NAME_; $cMC = _CLMN_GUILDMEMB_CHAR_;
    $tC = _TBL_CHR_;        $cCN = _CLMN_CHR_NAME_;       $cCC = _CLMN_CHR_CLASS_;
    $cCL = _CLMN_CHR_LVL_;  $cCR = _CLMN_CHR_RSTS_;

    $gpFind = function($name) use ($gpDb, $tG, $cGN, $cGM, $cGS, $cGL) {
        return $gpDb->query_fetch_single(
            "SELECT TOP 1 $cGN AS GName, $cGM AS GMaster, $cGS AS GScore, CONVERT(varchar(64), $cGL, 2) AS GMark FROM $tG WHERE $cGN = ?",
            array($name)
        );
    };

    if(strlen($gpName) <= 8) $gpGuild = $gpFind($gpName);
    // Enlaces con el nombre codificado (opcion "encode" del modulo de perfiles)
    if(!is_array($gpGuild) && function_exists('base64url_decode')) {
        $gpDecoded = @base64url_decode($gpName);
        if(is_string($gpDecoded) && $gpDecoded !== '' && strlen($gpDecoded) <= 8) $gpGuild = $gpFind($gpDecoded);
    }

    if(is_array($gpGuild)) {
        $gpGuildName = trim((string)$gpGuild['GName']);
        $gpMaster = trim((string)$gpGuild['GMaster']);

        $gpRows = $gpDb->query_fetch(
            "SELECT gm.$cMC AS MName, c.$cCC AS MClass, c.$cCL AS MLevel, c.$cCR AS MResets
             FROM $tM gm LEFT JOIN $tC c ON c.$cCN = gm.$cMC
             WHERE gm.$cMG = ?
             ORDER BY CASE WHEN gm.$cMC = ? THEN 0 ELSE 1 END, c.$cCR DESC, c.$cCL DESC, gm.$cMC",
            array($gpGuildName, $gpMaster)
        );
        // Si el pack no tiene alguna de esas columnas, al menos se listan los nombres
        if(!is_array($gpRows)) {
            $gpRows = $gpDb->query_fetch("SELECT $cMC AS MName FROM $tM WHERE $cMG = ? ORDER BY $cMC", array($gpGuildName));
        }
        if(is_array($gpRows)) $gpMembers = $gpRows;

        $gpCastle = $gpDb->query_fetch_single("SELECT TOP 1 OWNER_GUILD FROM MuCastle_DATA");
        if(is_array($gpCastle) && isset($gpCastle['OWNER_GUILD']) && strcasecmp(trim((string)$gpCastle['OWNER_GUILD']), $gpGuildName) === 0) {
            $gpIsCastleOwner = true;
        }
    }
} catch (Throwable $gpEx) {
    $gpGuild = null;
}

if(!is_array($gpGuild)) {
    $gpMsg('El guild no existe o no est&aacute; disponible');
    return;
}

// --- 4. Presentacion ---
$gpBase = __BASE_URL__;
$gpMarkHex = strtolower(trim((string)$gpGuild['GMark']));
$gpHasMark = ($gpMarkHex !== '' && ctype_xdigit($gpMarkHex));
$gpScore = is_numeric($gpGuild['GScore']) ? number_format((float)$gpGuild['GScore']) : '0';
$gpPlayerUrl = function($name) use ($gpBase) { return $gpBase . 'profile/player/' . rawurlencode(trim((string)$name)); };

echo '<div class="container-fluid py-5 profile-wrapper guild-profile">';

    // Cabecera
    echo '<div class="profile-card gp-head p-4">';
        echo '<div class="gp-mark">';
            if($gpHasMark) {
                echo '<img src="' . $gpBase . 'api/guildmark.php?data=' . $gpMarkHex . '" alt="Emblema">';
            } else {
                echo '<i class="fa fa-shield"></i>';
            }
        echo '</div>';
        echo '<div class="gp-info">';
            echo '<div class="gp-title">';
                echo '<h2 class="trajan-title">' . $gpEsc($gpGuildName) . '</h2>';
                if($gpIsCastleOwner) echo '<span class="gp-castle"><i class="fa fa-crown"></i> Due&ntilde;o del castillo</span>';
            echo '</div>';
            echo '<div class="gp-facts">';
                echo '<div class="gp-fact"><span>Guild Master</span>';
                    echo ($gpMaster !== '') ? '<a href="' . $gpEsc($gpPlayerUrl($gpMaster)) . '">' . $gpEsc($gpMaster) . '</a>' : '<b>-</b>';
                echo '</div>';
                echo '<div class="gp-fact"><span>Puntaje</span><b>' . $gpScore . '</b></div>';
                echo '<div class="gp-fact"><span>Miembros</span><b>' . number_format(count($gpMembers)) . '</b></div>';
            echo '</div>';
        echo '</div>';
    echo '</div>';

    // Miembros
    echo '<div class="profile-card gp-members p-4">';
        echo '<h4 class="trajan-title section-title">Miembros</h4>';
        if(empty($gpMembers)) {
            echo '<div class="gp-empty">No hay miembros para mostrar.</div>';
        } else {
            echo '<div class="gp-row-head"><span>Personaje</span><span>Clase</span><span class="gp-num">Nivel</span><span class="gp-num">Resets</span></div>';
            foreach($gpMembers as $gpRow) {
                $gpMember = trim((string)$gpRow['MName']);
                if($gpMember === '') continue;
                $gpClass = msm_gp_class_info(isset($gpRow['MClass']) ? $gpRow['MClass'] : null);
                $gpLevel = (isset($gpRow['MLevel']) && is_numeric($gpRow['MLevel'])) ? number_format((float)$gpRow['MLevel']) : '-';
                $gpResets = (isset($gpRow['MResets']) && is_numeric($gpRow['MResets'])) ? number_format((float)$gpRow['MResets']) : '-';
                $gpAvatar = $gpBase . 'img/character-avatars/' . $gpClass[1] . '.jpg';
                $gpFallback = $gpBase . 'img/character-avatars/avatar.jpg';

                echo '<a class="gp-row" href="' . $gpEsc($gpPlayerUrl($gpMember)) . '">';
                    echo '<span class="gp-char">';
                        echo '<img src="' . $gpEsc($gpAvatar) . '" alt="" loading="lazy" onerror="this.onerror=null;this.src=\'' . $gpEsc($gpFallback) . '\';">';
                        echo '<span class="gp-name">' . $gpEsc($gpMember) . '</span>';
                        if($gpMaster !== '' && strcasecmp($gpMember, $gpMaster) === 0) echo '<span class="gp-tag">Master</span>';
                    echo '</span>';
                    echo '<span class="gp-class">' . $gpEsc($gpClass[0]) . '</span>';
                    echo '<span class="gp-num" data-label="Nivel">' . $gpLevel . '</span>';
                    echo '<span class="gp-num" data-label="Resets">' . $gpResets . '</span>';
                echo '</a>';
            }
        }
    echo '</div>';

echo '</div>';