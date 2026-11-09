<?php
/**
 * System Manager (Cron & Cache Unified) - PREPARED FOR OBFUSCATION
 * Pure PHP Version (Concatenation Safe)
 * Fix: Dynamic URL Routing & PHP 8 Compatibility
 */

if(!defined('access') or !access) die();

// --- 1. CONFIGURACIÓN GENERAL ---
if(!defined('__PATH_CRON__')) define('__PATH_CRON__', __PATH_INCLUDES__ . 'cron/');
if(!defined('__PATH_CONFIGS__')) define('__PATH_CONFIGS__', __PATH_INCLUDES__ . 'config/');

$db = Connection::Database('Me_MuOnline');
$currentTab = isset($_GET['tab']) ? $_GET['tab'] : 'cron';

// --- FIX ROUTING: Reconstrucción dinámica de URL ---
$queryParams = $_GET;
unset($queryParams['success'], $queryParams['error'], $queryParams['tab'], $queryParams['add_cron'], $queryParams['cache_action']);
$baseUrl = 'index.php?' . http_build_query($queryParams);

$alert = "";

// ====================================================================================
// LÓGICA: CRON JOBS
// ====================================================================================
$validCronFiles = [];
$crons = [];
$apiKey = 'No Key Found';

if ($currentTab == 'cron') {
    if(is_dir(__PATH_CRON__)) {
        $files = scandir(__PATH_CRON__);
        foreach($files as $file) {
            if(is_file(__PATH_CRON__ . $file) && strtolower(pathinfo($file, PATHINFO_EXTENSION)) == 'php') {
                $validCronFiles[] = $file;
            }
        }
    }

    if(isset($_POST['add_cron'])) {
        if(!empty($_POST['cron_name']) && !empty($_POST['cron_file']) && !empty($_POST['cron_time'])) {
            $fileInput = basename($_POST['cron_file']);
            if(!in_array($fileInput, $validCronFiles)) {
                $alert = '<div class="alert-error">Error: Archivo no existe.</div>';
            } else {
                try {
                    $check = $db->query_fetch_single("SELECT COUNT(*) as count FROM MUSERVERMAKER_CRON WHERE cron_file_run = ?", [$fileInput]);
                    if(isset($check['count']) && $check['count'] > 0) {
                        $alert = '<div class="alert-error">Error: Este archivo ya existe en la lista.</div>';
                    } else {
                        $filePath = __PATH_CRON__ . $fileInput;
                        $fileMd5 = file_exists($filePath) ? md5_file($filePath) : '';
                        $sql = "INSERT INTO MUSERVERMAKER_CRON (cron_name, cron_description, cron_file_run, cron_run_time, cron_status, cron_protected, cron_file_md5, cron_last_run) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                        $params = [$_POST['cron_name'], '', $fileInput, $_POST['cron_time'], 1, 0, $fileMd5, 0];
                        $db->query($sql, $params);
                        echo '<script>window.location.href = "'.$baseUrl.'&tab=cron&success=1";</script>';
                    }
                } catch (Exception $e) {
                    $alert = '<div class="alert-error">DB Error: ' . $e->getMessage() . '</div>';
                }
            }
        }
    }

    try {
        if(isset($_POST['action_all'])) {
            $act = $_POST['action_all'];
            if($act == 'enable') $db->query("UPDATE MUSERVERMAKER_CRON SET cron_status = 1");
            if($act == 'disable') $db->query("UPDATE MUSERVERMAKER_CRON SET cron_status = 0");
            if($act == 'reset') $db->query("UPDATE MUSERVERMAKER_CRON SET cron_status = 1, cron_last_run = 0");
            echo '<script>window.location.href = "'.$baseUrl.'&tab=cron&success=1";</script>';
        }
        if(isset($_POST['delete_id'])) {
            $db->query("DELETE FROM MUSERVERMAKER_CRON WHERE cron_id = ?", [$_POST['delete_id']]);
            echo '<script>window.location.href = "'.$baseUrl.'&tab=cron&success=1";</script>';
        }
        if(isset($_POST['toggle_id'])) {
            $db->query("UPDATE MUSERVERMAKER_CRON SET cron_status = CASE WHEN cron_status = 1 THEN 0 ELSE 1 END WHERE cron_id = ?", [$_POST['toggle_id']]);
            echo '<script>window.location.href = "'.$baseUrl.'&tab=cron&success=1";</script>';
        }
        if(isset($_POST['reset_id'])) {
            $db->query("UPDATE MUSERVERMAKER_CRON SET cron_last_run = 0 WHERE cron_id = ?", [$_POST['reset_id']]);
            echo '<script>window.location.href = "'.$baseUrl.'&tab=cron&success=1";</script>';
        }
        if(isset($_POST['run_id'])) {
            $cData = $db->query_fetch_single("SELECT * FROM MUSERVERMAKER_CRON WHERE cron_id = ?", [$_POST['run_id']]);
            if($cData) {
                $fPath = __PATH_CRON__ . basename($cData['cron_file_run']);
                if(file_exists($fPath)) { include($fPath); $db->query("UPDATE MUSERVERMAKER_CRON SET cron_last_run = ? WHERE cron_id = ?", [time(), $_POST['run_id']]); }
            }
            echo '<script>window.location.href = "'.$baseUrl.'&tab=cron&success=1";</script>';
        }
    } catch (Exception $e) { $alert = '<div class="alert-error">Error: ' . $e->getMessage() . '</div>'; }

    try {
        $crons = $db->query_fetch("SELECT * FROM MUSERVERMAKER_CRON ORDER BY cron_id ASC");
    } catch (Exception $e) { $alert = '<div class="alert-error">Tabla MUSERVERMAKER_CRON no encontrada.</div>'; }

    $muservermakerJson = __PATH_CONFIGS__ . 'muservermaker.json';
    if(file_exists($muservermakerJson)) {
        $weConfig = json_decode(file_get_contents($muservermakerJson), true);
        if(isset($weConfig['cron_api_key'])) $apiKey = $weConfig['cron_api_key'];
    }
}

// ====================================================================================
// LÓGICA: CACHE MANAGER
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
            echo '<script>window.location.href = "'.$baseUrl.'&tab=cache&success=1";</script>';
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
    } catch (Exception $e) { $alert = '<div class="alert-error">Cache Error: ' . $e->getMessage() . '</div>'; }
}

$cronTimes = [60 => '1 Minuto', 180 => '3 Minutos', 300 => '5 Minutos', 900 => '15 Minutos', 3600 => '1 Hora', 86400 => '1 Día'];

// --- RENDERIZADO ---
echo '<link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">';
echo '<style>
    .sys-container { font-family: "Roboto", sans-serif; font-size: 12px; color: #ffffff; }
    .sys-tabs { display: flex; gap: 10px; margin-bottom: 25px; border-bottom: 1px solid #2b2b40; padding-bottom: 15px; }
    .sys-tab-link { padding: 10px 25px; border-radius: 4px; font-weight: 600; text-transform: uppercase; text-decoration: none; color: #7e8299; border: 1px solid #2b2b40; background: #151521; transition: 0.3s; }
    .sys-tab-link:hover { color: #fff; border-color: #3699ff; }
    .sys-tab-link.active { background: #3699ff; color: #fff; border-color: #3699ff; box-shadow: 0 4px 15px rgba(54, 153, 255, 0.3); }
    .settings-section { background-color: #1b1b28; border-radius: 8px; padding: 20px; border: 1px solid #2b2b40; box-shadow: 0 0 20px rgba(0,0,0,0.2); margin-bottom: 20px; }
    .settings-title { font-size: 14px; font-weight: 700; margin-bottom: 15px; padding-bottom: 10px; border-bottom: 1px solid #2b2b40; color: #ffffff; text-transform: uppercase; }
    .settings-label { display: block; font-size: 11px; font-weight: 500; color: #7e8299; margin-bottom: 5px; text-transform: uppercase; }
    .form-control-dark { width: 100%; background-color: #151521; border: 1px solid #2b2b40; border-radius: 4px; padding: 8px 10px; color: #ffffff; outline: none; }
    .btn-save-green { width: 100%; background-color: #00d084; color: #fff; border: none; padding: 10px; border-radius: 4px; cursor: pointer; font-weight: 500; transition: 0.3s; margin-top: 10px; }
    .btn-purge { display: block; width: 100%; text-align: center; background: rgba(246, 78, 96, 0.1); border: 1px dashed #f64e60; color: #f64e60; padding: 10px; border-radius: 6px; font-weight: 600; margin-top: 15px; cursor:pointer; }
    .btn-purge:hover { background: #f64e60; color: #fff; }
    .sys-table { width: 100%; border-collapse: collapse; }
    .sys-table th { text-align: left; padding: 10px; color: #7e8299; border-bottom: 1px solid #2b2b40; text-transform: uppercase; font-size: 11px; }
    .sys-table td { padding: 10px; border-bottom: 1px dashed #2b2b40; vertical-align: middle; color: #fff; }
    .btn-icon { background: transparent; border: none; font-size: 14px; cursor: pointer; padding: 0 5px; transition: transform 0.2s; }
    .alert-error { background: #f1416c; color: white; padding: 15px; border-radius: 4px; margin-bottom: 20px; border-left: 4px solid #a11535; }
    .alert-success-custom { background: #1b1b28; border-left: 4px solid #00d084; color: #fff; padding: 15px; margin-bottom: 20px; border-radius: 4px; }
    .badge-ok { background: rgba(27, 197, 189, 0.2); color: #1bc5bd; padding: 3px 8px; border-radius: 4px; font-size: 10px; }
    .badge-no { background: rgba(246, 78, 96, 0.2); color: #f64e60; padding: 3px 8px; border-radius: 4px; font-size: 10px; }
</style>';

echo '<div class="sys-container">';
echo '<div class="sys-tabs">';
echo '<a href="'.$baseUrl.'&tab=cron" class="sys-tab-link '.($currentTab == 'cron' ? 'active' : '').'"><i class="fa fa-clock-o"></i> Cron Jobs</a>';
echo '<a href="'.$baseUrl.'&tab=cache" class="sys-tab-link '.($currentTab == 'cache' ? 'active' : '').'"><i class="fa fa-database"></i> Cache Manager</a>';
echo '</div>';

if(!empty($alert)) echo $alert;
if(isset($_GET['success']) && empty($alert)) echo '<div class="alert-success-custom">Operación realizada con éxito.</div>';

if ($currentTab == 'cron') {
    echo '<div class="row">';
    echo '<div class="col-md-4">';
    echo '<div class="settings-section"><h3 class="settings-title">Add New Cron</h3><form action="" method="POST">';
    echo '<div class="form-group mb-3"><label class="settings-label">Nombre</label><input type="text" name="cron_name" class="form-control-dark" placeholder="Ej: Ranking Update" required></div>';
    echo '<div class="form-group mb-3"><label class="settings-label">Archivo (/includes/cron/)</label><select name="cron_file" class="form-control-dark">';
    if(!empty($validCronFiles)) { foreach($validCronFiles as $file) echo '<option value="'.$file.'">'.$file.'</option>'; }
    else { echo '<option value="" disabled>No files found</option>'; }
    echo '</select></div>';
    echo '<div class="form-group mb-3"><label class="settings-label">Intervalo</label><select name="cron_time" class="form-control-dark">';
    foreach($cronTimes as $sec => $lbl) echo '<option value="'.$sec.'">'.$lbl.'</option>';
    echo '</select></div><button type="submit" name="add_cron" class="btn-save-green"><i class="fa fa-plus-circle"></i> Add Cron</button></form></div>';
    
    echo '<div class="settings-section"><h3 class="settings-title">Global Actions</h3><form action="" method="POST" style="display:flex; gap:10px;">';
    echo '<button type="submit" name="action_all" value="enable" class="btn-save-green" style="background:#151521; border:1px solid #2b2b40; margin:0; flex:1;"><i class="fa fa-check"></i> Enable</button>';
    echo '<button type="submit" name="action_all" value="disable" class="btn-save-green" style="background:#151521; border:1px solid #2b2b40; margin:0; flex:1;"><i class="fa fa-ban"></i> Disable</button>';
    echo '<button type="submit" name="action_all" value="reset" class="btn-save-green" style="background:#151521; border:1px solid #2b2b40; margin:0; flex:1;"><i class="fa fa-sync"></i> Reset</button></form></div>';
    
    echo '<div class="settings-section"><h3 class="settings-title">Cron API URL</h3><div style="position:relative;"><input type="text" class="form-control-dark" value="'.__BASE_URL__.'api/cron.php?key='.$apiKey.'" readonly onclick="this.select()"><i class="fa fa-key" style="position:absolute; right:10px; top:10px; color:#7e8299;"></i></div><small style="color:#555; display:block; margin-top:5px; font-size:10px;">Use this URL for external cron services.</small></div>';
    echo '</div>';

    echo '<div class="col-md-8"><div class="settings-section"><h3 class="settings-title">Tasks List</h3><div class="table-responsive"><table class="sys-table"><thead><tr><th>Name</th><th>File</th><th>Repeat</th><th>Last Run</th><th class="text-end">Actions</th></tr></thead><tbody>';
    if(!empty($crons)) {
        foreach($crons as $c) {
            echo '<tr><td>'.$c['cron_name'].'</td><td style="color:#3699ff;">'.$c['cron_file_run'].'</td><td>'.(isset($cronTimes[$c['cron_run_time']]) ? $cronTimes[$c['cron_run_time']] : $c['cron_run_time'].'s').'</td><td style="color:#7e8299;">'.($c['cron_last_run'] == 0 ? 'Never' : date("d/m H:i", (int)$c['cron_last_run'])).'</td>';
            echo '<td class="text-end"><form method="POST" style="display:inline;"><input type="hidden" name="reset_id" value="'.$c['cron_id'].'"><button class="btn-icon" style="color:#ffa800"><i class="fa fa-sync-alt"></i></button></form>';
            echo '<form method="POST" style="display:inline;"><input type="hidden" name="run_id" value="'.$c['cron_id'].'"><button class="btn-icon" style="color:#3699ff"><i class="fa fa-play"></i></button></form>';
            echo '<form method="POST" style="display:inline;"><input type="hidden" name="toggle_id" value="'.$c['cron_id'].'"><button class="btn-icon" style="color:'.($c['cron_status']?'#50cd89':'#f1416c').'"><i class="fa fa-power-off"></i></button></form>';
            echo '<form method="POST" style="display:inline;" onsubmit="return confirm(\'Delete?\');"><input type="hidden" name="delete_id" value="'.$c['cron_id'].'"><button class="btn-icon" style="color:#f1416c"><i class="fa fa-trash"></i></button></form></td></tr>';
        }
    } else { echo '<tr><td colspan="5" align="center">No crons found.</td></tr>'; }
    echo '</tbody></table></div></div></div></div>';
}

if ($currentTab == 'cache') {
    echo '<div class="row"><div class="col-md-12"><div class="settings-section"><h3 class="settings-title"><i class="fa fa-server" style="color:#3699ff; margin-right:10px;"></i> System Cache Files</h3><div class="table-responsive"><table class="sys-table"><thead><tr><th>File</th><th>Size</th><th>Updated</th><th>Writable</th><th class="text-right">Action</th></tr></thead><tbody>';
    if(!empty($cacheFileList)) {
        foreach($cacheFileList as $row) {
            $status = ($row['write'] == 1) ? '<span class="badge-ok">Writable</span>' : '<span class="badge-no">Locked</span>';
            echo '<tr><td>'.$row['file'].'</td><td>'.readableFileSize($row['size'], 2).'</td><td>'.($row['edit'] == 'Unknown' ? '-' : date("d/m/Y H:i", (int)$row['edit'])).'</td><td>'.$status.'</td>';
            echo '<td align="right"><form method="POST"><input type="hidden" name="cache_action" value="clear"><input type="hidden" name="file" value="'.$row['file'].'"><button class="btn-icon" style="color:#f64e60; font-size:12px; border:1px solid #f64e60; border-radius:3px; padding:2px 8px;">CLEAR</button></form></td></tr>';
        }
    }
    echo '</tbody></table></div></div></div>';
    echo '<div class="col-md-6"><div class="settings-section"><h3 class="settings-title"><i class="fa fa-users" style="color:#f39c12; margin-right:10px;"></i> Guild Profiles</h3><p style="color:#7e8299;">Files: <strong style="color:#fff">'.number_format($guildCacheInfo['count']).'</strong></p><p style="color:#7e8299;">Size: <strong style="color:#fff">'.readableFileSize($guildCacheInfo['size'], 2).'</strong></p>';
    if($guildCacheInfo['count'] > 0) echo '<form method="POST"><button type="submit" name="cache_action" value="delete_guild" class="btn-purge"><i class="fa fa-eraser"></i> PURGE ALL</button></form>';
    echo '</div></div>';
    echo '<div class="col-md-6"><div class="settings-section"><h3 class="settings-title"><i class="fa fa-user" style="color:#8950fc; margin-right:10px;"></i> Player Profiles</h3><p style="color:#7e8299;">Files: <strong style="color:#fff">'.number_format($playerCacheInfo['count']).'</strong></p><p style="color:#7e8299;">Size: <strong style="color:#fff">'.readableFileSize($playerCacheInfo['size'], 2).'</strong></p>';
    if($playerCacheInfo['count'] > 0) echo '<form method="POST"><button type="submit" name="cache_action" value="delete_player" class="btn-purge"><i class="fa fa-eraser"></i> PURGE ALL</button></form>';
    echo '</div></div></div>';
}

echo '</div>';