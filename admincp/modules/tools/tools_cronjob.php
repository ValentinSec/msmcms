<?php
/**
 * Tareas programadas y cache (AdminCP > Herramientas > Cron Job)
 */

if(!defined('access') or !access) die();

if(!defined('__PATH_CRON__')) define('__PATH_CRON__', __PATH_INCLUDES__ . 'cron/');

$currentTab = (isset($_GET['tab']) && $_GET['tab'] === 'cache') ? 'cache' : 'cron';
$baseUrl = 'index.php?module=tools_cronjob';
$esc = function($s) { return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); };
$goTo = function($tab) use ($baseUrl) { echo '<script>window.location.href = ' . json_encode($baseUrl . '&tab=' . $tab) . ';</script>'; };
$ago = function($ts) {
    $ts = (int)$ts;
    if($ts <= 0) return 'Nunca';
    $d = time() - $ts;
    if($d < 60) return 'hace instantes';
    if($d < 3600) return 'hace ' . floor($d / 60) . ' min';
    if($d < 86400) return 'hace ' . floor($d / 3600) . ' h';
    return 'hace ' . floor($d / 86400) . ' d';
};
$alert = '';

// ====================================================================================
// TAREAS PROGRAMADAS
// ====================================================================================
// archivo => titulo (HTML), nombre guardado en la base (ASCII), descripcion, intervalo, recomendada
$cronCatalog = array(
    'server_info.php'       => array('title' => 'Usuarios en l&iacute;nea y totales', 'plain' => 'Usuarios en linea y totales', 'desc' => 'Usuarios conectados y totales de cuentas, personajes y guilds que muestra el sitio.', 'every' => 60,  'rec' => true),
    'online_characters.php' => array('title' => 'Personajes conectados',             'plain' => 'Personajes conectados',       'desc' => 'Lista de personajes que est&aacute;n jugando en este momento.',                      'every' => 60,  'rec' => true),
    'castle_siege.php'      => array('title' => 'Castle Siege',                      'plain' => 'Castle Siege',                'desc' => 'Due&ntilde;o del castillo, guilds anotados y calendario del asedio.',                'every' => 300, 'rec' => true),
    'temporal_bans.php'     => array('title' => 'Baneos temporales',                 'plain' => 'Baneos temporales',           'desc' => 'Levanta solos los baneos que ya cumplieron su plazo.',                               'every' => 300, 'rec' => true),
    'server_rankings.php'   => array('title' => 'Rankings unificados',               'plain' => 'Rankings unificados',         'desc' => 'Genera una cach&eacute; con todos los rankings.',                                    'every' => 300, 'rec' => false),
);
$cronTimes = array(60 => '1 minuto', 180 => '3 minutos', 300 => '5 minutos', 900 => '15 minutos', 3600 => '1 hora', 86400 => '1 d&iacute;a');

$crons = array(); $cronFiles = array(); $cronStatus = array(); $installed = array(); $pendingRec = 0; $lastTick = 0;

if($currentTab == 'cron') {
    $db = Connection::Database('Me_MuOnline');
    $cronManager = new CronManager();

    $scan = @scandir(__PATH_CRON__);
    if(is_array($scan)) {
        foreach($scan as $f) {
            if($f !== 'cron.php' && is_file(__PATH_CRON__ . $f) && strtolower(pathinfo($f, PATHINFO_EXTENSION)) === 'php') $cronFiles[] = $f;
        }
    }

    $rows = $db->query_fetch("SELECT cron_file_run FROM MUSERVERMAKER_CRON");
    if(is_array($rows)) { foreach($rows as $r) $installed[] = strtolower(trim((string)$r['cron_file_run'])); }

    $addCron = function($file, $name, $every) use ($db) {
        return $db->query(
            "INSERT INTO MUSERVERMAKER_CRON (cron_name, cron_description, cron_file_run, cron_run_time, cron_status, cron_protected, cron_file_md5, cron_last_run) VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
            array($name, '', $file, (int)$every, 1, 0, (string)@md5_file(__PATH_CRON__ . $file), 0)
        );
    };

    if($_SERVER['REQUEST_METHOD'] === 'POST') {
        $flash = null;

        if(isset($_POST['install_recommended'])) {
            $n = 0;
            foreach($cronCatalog as $file => $c) {
                if(!$c['rec'] || in_array($file, $installed, true) || !in_array($file, $cronFiles, true)) continue;
                if($addCron($file, $c['plain'], $c['every'])) $n++;
            }
            $flash = array(true, $n . ' tarea(s) instalada(s).');

        } elseif(isset($_POST['add_cron'])) {
            $file = isset($_POST['cron_file']) ? basename((string)$_POST['cron_file']) : '';
            $every = isset($_POST['cron_time']) ? (int)$_POST['cron_time'] : 0;
            if(!in_array($file, $cronFiles, true)) {
                $flash = array(false, 'El archivo elegido no existe.');
            } elseif(!isset($cronTimes[$every])) {
                $flash = array(false, 'Intervalo no v&aacute;lido.');
            } elseif(in_array(strtolower($file), $installed, true)) {
                $flash = array(false, 'Esa tarea ya est&aacute; en la lista.');
            } else {
                $name = isset($cronCatalog[$file]) ? $cronCatalog[$file]['plain'] : $file;
                $flash = $addCron($file, $name, $every) ? array(true, 'Tarea agregada.') : array(false, 'No se pudo agregar la tarea.');
            }

        } elseif(isset($_POST['run_id'])) {
            $res = $cronManager->runOne((int)$_POST['run_id']);
            if(!is_array($res)) {
                $flash = array(false, 'La tarea no existe.');
            } elseif($res['ok']) {
                $flash = array(true, 'Tarea ejecutada en ' . (int)$res['ms'] . ' ms.');
            } else {
                $flash = array(false, 'La tarea fall&oacute;: ' . $esc($res['message']));
            }

        } elseif(isset($_POST['toggle_id'])) {
            $db->query("UPDATE MUSERVERMAKER_CRON SET cron_status = CASE WHEN cron_status = 1 THEN 0 ELSE 1 END WHERE cron_id = ?", array((int)$_POST['toggle_id']));
            $flash = array(true, 'Estado de la tarea actualizado.');

        } elseif(isset($_POST['delete_id'])) {
            $db->query("DELETE FROM MUSERVERMAKER_CRON WHERE cron_id = ?", array((int)$_POST['delete_id']));
            $flash = array(true, 'Tarea quitada.');
        }

        if($flash !== null) {
            $_SESSION['msm_cron_flash'] = $flash;
            $goTo('cron');
            return;
        }
    }

    $rows = $db->query_fetch("SELECT * FROM MUSERVERMAKER_CRON ORDER BY cron_id ASC");
    if(is_array($rows)) $crons = $rows;
    $cronStatus = $cronManager->getStatus();
    $lastTick = (int)@filemtime(__PATH_INCLUDES__ . 'cache/cron.tick');
    foreach($cronCatalog as $file => $c) {
        if($c['rec'] && !in_array($file, $installed, true) && in_array($file, $cronFiles, true)) $pendingRec++;
    }

    if(isset($_SESSION['msm_cron_flash']) && is_array($_SESSION['msm_cron_flash'])) {
        $fl = $_SESSION['msm_cron_flash'];
        $alert = '<div class="' . ($fl[0] ? 'alert-success-custom' : 'alert-error') . '">' . $fl[1] . '</div>';
    }
    unset($_SESSION['msm_cron_flash']);
}

// ====================================================================================
// CACHE MANAGER (sin cambios de logica)
// ====================================================================================
$cacheFileList = [];
$guildCacheInfo = ['count' => 0, 'size' => 0];
$playerCacheInfo = ['count' => 0, 'size' => 0];

if ($currentTab == 'cache') {
    try {
        $cacheManager = new CacheManager();
        $cacheFileList = $cacheManager->getCacheFileListAndData();
        if(isset($_POST['cache_action'])) {
            $act = $_POST['cache_action'];
            if ($act == 'clear' && isset($_POST['file'])) {
                $cacheManager->setFile($_POST['file']);
                $cacheManager->clearCacheData();
            }
            if ($act == 'delete_guild') $cacheManager->deleteGuildCache();
            if ($act == 'delete_player') $cacheManager->deletePlayerCache();
            $_SESSION['msm_cache_flash'] = 1;
            $goTo('cache');
            return;
        }
        $guildProfilesCache = $cacheManager->getCacheFileListAndData('guild');
        $guildSize = 0; $guildCount = 0;
        if(is_array($guildProfilesCache)) {
            $guildCount = count($guildProfilesCache);
            foreach($guildProfilesCache as $gc) $guildSize += $gc['size'];
        }
        $guildCacheInfo = ['count' => $guildCount, 'size' => $guildSize];
        $playerProfilesCache = $cacheManager->getCacheFileListAndData('player');
        $playerSize = 0; $playerCount = 0;
        if(is_array($playerProfilesCache)) {
            $playerCount = count($playerProfilesCache);
            foreach($playerProfilesCache as $pc) $playerSize += $pc['size'];
        }
        $playerCacheInfo = ['count' => $playerCount, 'size' => $playerSize];
    } catch (Exception $e) { $alert = '<div class="alert-error">Cache Error: ' . $esc($e->getMessage()) . '</div>'; }

    if(!empty($_SESSION['msm_cache_flash'])) $alert = '<div class="alert-success-custom">Operaci&oacute;n realizada con &eacute;xito.</div>';
    unset($_SESSION['msm_cache_flash']);
}

// --- RENDERIZADO ---
echo '<style>
    .sys-container { font-size: 12px; color: #ffffff; }
    .sys-tabs { display: flex; gap: 10px; margin-bottom: 25px; border-bottom: 1px solid #2b2b40; padding-bottom: 15px; }
    .sys-tab-link { padding: 10px 25px; border-radius: 4px; font-weight: 600; text-transform: uppercase; text-decoration: none !important; color: #7e8299; border: 1px solid #2b2b40; background: #151521; transition: 0.3s; }
    .sys-tab-link:hover { color: #fff; border-color: #10b981; }
    .sys-tab-link.active { background: #10b981; color: #fff; border-color: #10b981; }
    .settings-section { background-color: #1b1b28; border-radius: 8px; padding: 20px; border: 1px solid #2b2b40; margin-bottom: 20px; }
    .settings-title { font-size: 14px; font-weight: 700; margin: 0 0 15px; padding-bottom: 10px; border-bottom: 1px solid #2b2b40; color: #ffffff; text-transform: uppercase; }
    .settings-label { display: block; font-size: 11px; font-weight: 500; color: #7e8299; margin-bottom: 5px; text-transform: uppercase; }
    .form-control-dark { width: 100%; background-color: #151521; border: 1px solid #2b2b40; border-radius: 4px; padding: 8px 10px; color: #ffffff; outline: none; }
    .sys-container .btn-save-green { float: none; width: 100%; background-color: #00d084; color: #fff; border: none; padding: 10px; border-radius: 4px; cursor: pointer; font-weight: 500; margin-top: 10px; }
    .sys-container .settings-section { height: auto; }
    .btn-purge { display: block; width: 100%; text-align: center; background: rgba(246, 78, 96, 0.1); border: 1px dashed #f64e60; color: #f64e60; padding: 10px; border-radius: 6px; font-weight: 600; margin-top: 15px; cursor: pointer; }
    .btn-purge:hover { background: #f64e60; color: #fff; }
    .sys-table { width: 100%; border-collapse: collapse; }
    .sys-table th { text-align: left; padding: 10px; color: #7e8299; border-bottom: 1px solid #2b2b40; text-transform: uppercase; font-size: 11px; white-space: nowrap; }
    .sys-table td { padding: 10px; border-bottom: 1px dashed #2b2b40; vertical-align: middle; color: #fff; }
    .btn-icon { background: transparent; border: none; font-size: 14px; cursor: pointer; padding: 0 6px; }
    .alert-error { background: #1b1b28; border-left: 4px solid #ef4444; color: #fff; padding: 15px; border-radius: 4px; margin-bottom: 20px; }
    .alert-success-custom { background: #1b1b28; border-left: 4px solid #00d084; color: #fff; padding: 15px; margin-bottom: 20px; border-radius: 4px; }
    .badge-ok { background: rgba(27, 197, 189, 0.2); color: #1bc5bd; padding: 3px 8px; border-radius: 4px; font-size: 10px; white-space: nowrap; }
    .badge-no { background: rgba(246, 78, 96, 0.2); color: #f64e60; padding: 3px 8px; border-radius: 4px; font-size: 10px; white-space: nowrap; }
    .cron-name { font-weight: 600; font-size: 13px; }
    .cron-desc { color: #8b95a7; font-size: 11px; margin-top: 2px; }
    .cron-file { color: #5f6b7d; font-size: 10px; font-family: monospace; }
    .cron-note { color: #8b95a7; font-size: 12px; line-height: 1.5; margin: 0; }
    .cron-actions { white-space: nowrap; text-align: right; }
    .cron-actions form { display: inline; }
</style>';

echo '<div class="sys-container">';
echo '<div class="sys-tabs">';
echo '<a href="' . $baseUrl . '&tab=cron" class="sys-tab-link ' . ($currentTab == 'cron' ? 'active' : '') . '"><i class="fa fa-clock-o"></i> Tareas programadas</a>';
echo '<a href="' . $baseUrl . '&tab=cache" class="sys-tab-link ' . ($currentTab == 'cache' ? 'active' : '') . '"><i class="fa fa-database"></i> Cache Manager</a>';
echo '</div>';

if(!empty($alert)) echo $alert;

if ($currentTab == 'cron') {
    echo '<div class="row">';

    // Columna izquierda
    echo '<div class="col-md-4">';

        echo '<div class="settings-section"><h3 class="settings-title">Funcionamiento</h3>';
        echo '<p class="cron-note">Las tareas se ejecutan solas mientras el sitio recibe visitas. No hace falta configurar nada en el hosting.</p>';
        echo '<p class="cron-note" style="margin-top:10px;">&Uacute;ltimo disparo autom&aacute;tico: <b style="color:#fff;">' . $ago($lastTick) . '</b></p>';
        if($pendingRec > 0) {
            echo '<form action="" method="POST"><button type="submit" name="install_recommended" value="1" class="btn-save-green"><i class="fa fa-magic"></i> Instalar tareas recomendadas (' . $pendingRec . ')</button></form>';
        }
        echo '</div>';

        echo '<div class="settings-section"><h3 class="settings-title">Agregar tarea</h3><form action="" method="POST">';
        echo '<div class="form-group"><label class="settings-label">Tarea</label><select name="cron_file" class="form-control-dark">';
        $available = 0;
        foreach($cronFiles as $file) {
            if(in_array(strtolower($file), $installed, true)) continue;
            $label = isset($cronCatalog[$file]) ? $cronCatalog[$file]['title'] . ' (' . $esc($file) . ')' : $esc($file);
            echo '<option value="' . $esc($file) . '">' . $label . '</option>';
            $available++;
        }
        if($available === 0) echo '<option value="" disabled selected>No quedan tareas por agregar</option>';
        echo '</select></div>';
        echo '<div class="form-group"><label class="settings-label">Repetir cada</label><select name="cron_time" class="form-control-dark">';
        foreach($cronTimes as $sec => $lbl) echo '<option value="' . $sec . '"' . ($sec == 300 ? ' selected' : '') . '>' . $lbl . '</option>';
        echo '</select></div>';
        echo '<button type="submit" name="add_cron" value="1" class="btn-save-green"><i class="fa fa-plus-circle"></i> Agregar</button></form></div>';

    echo '</div>';

    // Columna derecha
    echo '<div class="col-md-8"><div class="settings-section"><h3 class="settings-title">Tareas instaladas</h3><div class="table-responsive"><table class="sys-table"><thead><tr><th>Tarea</th><th>Cada</th><th>&Uacute;ltima vez</th><th>Pr&oacute;xima</th><th>Resultado</th><th style="text-align:right;">Acciones</th></tr></thead><tbody>';
    if(!empty($crons)) {
        foreach($crons as $c) {
            $file = trim((string)$c['cron_file_run']);
            $cat = isset($cronCatalog[$file]) ? $cronCatalog[$file] : null;
            $every = (int)$c['cron_run_time'];
            $last = (int)$c['cron_last_run'];
            $active = ((int)$c['cron_status'] === 1);

            if(!$active) {
                $nextTxt = 'Pausada';
            } elseif($last + max(60, $every) <= time()) {
                $nextTxt = 'En la pr&oacute;xima visita';
            } else {
                $nextTxt = 'en ' . max(1, (int)ceil(($last + max(60, $every) - time()) / 60)) . ' min';
            }

            $st = isset($cronStatus[strtolower($file)]) ? $cronStatus[strtolower($file)] : null;
            if(!is_array($st)) {
                $resTxt = '<span style="color:#5f6b7d;">-</span>';
            } elseif(!empty($st['ok'])) {
                $resTxt = '<span class="badge-ok">Correcta &middot; ' . (int)$st['ms'] . ' ms</span>';
            } else {
                $resTxt = '<span class="badge-no" title="' . $esc(isset($st['msg']) ? $st['msg'] : '') . '">Error</span><div class="cron-desc">' . $esc(isset($st['msg']) ? $st['msg'] : '') . '</div>';
            }

            echo '<tr>';
            echo '<td><div class="cron-name">' . ($cat ? $cat['title'] : $esc($c['cron_name'])) . '</div>';
            if($cat) echo '<div class="cron-desc">' . $cat['desc'] . '</div>';
            echo '<div class="cron-file">' . $esc($file) . '</div></td>';
            echo '<td>' . (isset($cronTimes[$every]) ? $cronTimes[$every] : $every . ' s') . '</td>';
            echo '<td style="color:#8b95a7;">' . $ago($last) . '</td>';
            echo '<td style="color:#8b95a7;">' . $nextTxt . '</td>';
            echo '<td>' . $resTxt . '</td>';
            echo '<td class="cron-actions">';
                echo '<form method="POST" action=""><input type="hidden" name="run_id" value="' . (int)$c['cron_id'] . '"><button class="btn-icon" style="color:#3b82f6" title="Ejecutar ahora"><i class="fa fa-play"></i></button></form>';
                echo '<form method="POST" action=""><input type="hidden" name="toggle_id" value="' . (int)$c['cron_id'] . '"><button class="btn-icon" style="color:' . ($active ? '#10b981' : '#f59e0b') . '" title="' . ($active ? 'Pausar' : 'Activar') . '"><i class="fa ' . ($active ? 'fa-pause' : 'fa-power-off') . '"></i></button></form>';
                echo '<form method="POST" action="" onsubmit="return confirm(\'Quitar esta tarea de la lista?\');"><input type="hidden" name="delete_id" value="' . (int)$c['cron_id'] . '"><button class="btn-icon" style="color:#ef4444" title="Quitar"><i class="fa fa-trash"></i></button></form>';
            echo '</td>';
            echo '</tr>';
        }
    } else {
        echo '<tr><td colspan="6" style="text-align:center; color:#8b95a7;">No hay tareas instaladas.</td></tr>';
    }
    echo '</tbody></table></div></div></div>';

    echo '</div>';
}

if ($currentTab == 'cache') {
    echo '<div class="row"><div class="col-md-12"><div class="settings-section"><h3 class="settings-title"><i class="fa fa-server" style="color:#3699ff; margin-right:10px;"></i> System Cache Files</h3><div class="table-responsive"><table class="sys-table"><thead><tr><th>File</th><th>Size</th><th>Updated</th><th>Writable</th><th class="text-right">Action</th></tr></thead><tbody>';
    if(!empty($cacheFileList)) {
        foreach($cacheFileList as $row) {
            $status = ($row['write'] == 1) ? '<span class="badge-ok">Writable</span>' : '<span class="badge-no">Locked</span>';
            echo '<tr><td>' . $esc($row['file']) . '</td><td>' . readableFileSize($row['size'], 2) . '</td><td>' . ($row['edit'] == 'Unknown' ? '-' : date("d/m/Y H:i", (int)$row['edit'])) . '</td><td>' . $status . '</td>';
            echo '<td align="right"><form method="POST" action=""><input type="hidden" name="cache_action" value="clear"><input type="hidden" name="file" value="' . $esc($row['file']) . '"><button class="btn-icon" style="color:#f64e60; font-size:12px; border:1px solid #f64e60; border-radius:3px; padding:2px 8px;">CLEAR</button></form></td></tr>';
        }
    }
    echo '</tbody></table></div></div></div>';
    echo '<div class="col-md-6"><div class="settings-section"><h3 class="settings-title"><i class="fa fa-users" style="color:#f39c12; margin-right:10px;"></i> Guild Profiles</h3><p style="color:#7e8299;">Files: <strong style="color:#fff">' . number_format($guildCacheInfo['count']) . '</strong></p><p style="color:#7e8299;">Size: <strong style="color:#fff">' . readableFileSize($guildCacheInfo['size'], 2) . '</strong></p>';
    if($guildCacheInfo['count'] > 0) echo '<form method="POST" action=""><button type="submit" name="cache_action" value="delete_guild" class="btn-purge"><i class="fa fa-eraser"></i> PURGE ALL</button></form>';
    echo '</div></div>';
    echo '<div class="col-md-6"><div class="settings-section"><h3 class="settings-title"><i class="fa fa-user" style="color:#8950fc; margin-right:10px;"></i> Player Profiles</h3><p style="color:#7e8299;">Files: <strong style="color:#fff">' . number_format($playerCacheInfo['count']) . '</strong></p><p style="color:#7e8299;">Size: <strong style="color:#fff">' . readableFileSize($playerCacheInfo['size'], 2) . '</strong></p>';
    if($playerCacheInfo['count'] > 0) echo '<form method="POST" action=""><button type="submit" name="cache_action" value="delete_player" class="btn-purge"><i class="fa fa-eraser"></i> PURGE ALL</button></form>';
    echo '</div></div></div>';
}

echo '</div>';