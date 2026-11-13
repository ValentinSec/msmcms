<?php
/**
 * Dashboard Module (Home)
 * Pure PHP Version (No closing tag)
 */

if(!defined('access')) die();

if(!defined('__MUSERVERMAKER_VERSION__')) define('__MUSERVERMAKER_VERSION__', '1.0.0');
$currentVersion = __MUSERVERMAKER_VERSION__;
$dashCacheDir = __PATH_INCLUDES__ . 'cache/';

$esc = function($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); };
$fmtDate = function($value, $format = 'd/m/Y') {
    if(empty($value)) return '-';
    try { $d = new DateTime((string)$value); return $d->format($format); } catch (Exception $e) { return '-'; }
};

// =====================================================================
// 1. ACTUALIZACIONES (una consulta cada 12 h; si falla, reintenta en 1 h)
// =====================================================================
$updateAvailable = false;
$updateData = array();
$updateCacheFile = $dashCacheDir . 'update_check.json';
$updateCache = file_exists($updateCacheFile) ? json_decode((string)@file_get_contents($updateCacheFile), true) : null;
$updateFresh = false;
if(is_array($updateCache) && isset($updateCache['checked'])) {
    $ttl = !empty($updateCache['ok']) ? 43200 : 3600;
    if(time() - (int)$updateCache['checked'] < $ttl) $updateFresh = true;
}
if(!$updateFresh && function_exists('curl_init')) {
    $updateCache = array('checked' => time(), 'ok' => false, 'data' => array());
    try {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://licencias.muservermaker.com/api/check_update.php');
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(array('version' => $currentVersion, 'domain' => isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '')));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
        curl_setopt($ch, CURLOPT_TIMEOUT, 3);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if($httpCode == 200 && $response) {
            $decoded = json_decode($response, true);
            if(is_array($decoded)) { $updateCache['ok'] = true; $updateCache['data'] = $decoded; }
        }
    } catch (Throwable $e) { }
    @file_put_contents($updateCacheFile, json_encode($updateCache), LOCK_EX);
}
if(is_array($updateCache) && !empty($updateCache['data']['update']) && $updateCache['data']['update'] === true) {
    $updateAvailable = true;
    $updateData = $updateCache['data'];
}

// =====================================================================
// 2. DATOS
// =====================================================================
$totalAccounts = 0; $totalCharacters = 0; $totalGuilds = 0; $totalBanned = 0;
$onlineNow = 0;
$latestRegistrations = array();
$recentActivity = array();
$regByDay = array();
$gMembers = 0; $gNoMembers = 0;
$gHighScore = 'Ninguno'; $gLowScore = 'Ninguno';
$csOwner = 'SIN DUEÑO'; $csMaster = '-';

try {
    $db = Connection::Database('Me_MuOnline');

    $q = $db->query_fetch_single("SELECT COUNT(*) as count FROM MEMB_INFO");
    if(is_array($q)) $totalAccounts = (int)$q['count'];

    $q = $db->query_fetch_single("SELECT COUNT(*) as count FROM Character");
    if(is_array($q)) $totalCharacters = (int)$q['count'];

    $q = $db->query_fetch_single("SELECT COUNT(*) as count FROM Guild");
    if(is_array($q)) $totalGuilds = (int)$q['count'];

    $q = $db->query_fetch_single("SELECT COUNT(*) as count FROM MEMB_INFO WHERE bloc_code = 1");
    if(is_array($q)) $totalBanned = (int)$q['count'];

    // Conectados ahora
    $q = $db->query_fetch_single("SELECT COUNT(*) as count FROM MEMB_STAT WHERE ConnectStat = 1");
    if(is_array($q)) $onlineNow = (int)$q['count'];

    // Registros por dia (ultimos 7 dias)
    $rows = $db->query_fetch("SELECT CONVERT(varchar(10), appl_days, 120) AS dia, COUNT(*) AS total FROM MEMB_INFO WHERE appl_days >= DATEADD(day, -6, CONVERT(date, GETDATE())) GROUP BY CONVERT(varchar(10), appl_days, 120)");
    if(is_array($rows)) { foreach($rows as $r) { $regByDay[(string)$r['dia']] = (int)$r['total']; } }

    // Ultimas cuentas creadas
    $rows = $db->query_fetch("SELECT TOP 6 i.memb___id, i.appl_days, s.ConnectStat, s.ConnectTM FROM MEMB_INFO i LEFT JOIN MEMB_STAT s ON i.memb___id = s.memb___id ORDER BY i.appl_days DESC, i.memb___id DESC");
    if(is_array($rows)) $latestRegistrations = $rows;

    // Ultimas conexiones
    $rows = $db->query_fetch("SELECT TOP 6 memb___id, IP, ConnectTM, ConnectStat FROM MEMB_STAT ORDER BY ConnectTM DESC");
    if(is_array($rows)) $recentActivity = $rows;

    // Guilds
    $q = $db->query_fetch_single("SELECT COUNT(*) as count FROM GuildMember");
    if(is_array($q)) $gMembers = (int)$q['count'];
    $gNoMembers = max(0, $totalCharacters - $gMembers);

    $q = $db->query_fetch_single("SELECT TOP 1 G_Name FROM Guild ORDER BY G_Score DESC");
    if(is_array($q) && !empty($q['G_Name'])) $gHighScore = $q['G_Name'];

    $q = $db->query_fetch_single("SELECT TOP 1 G_Name FROM Guild ORDER BY G_Score ASC");
    if(is_array($q) && !empty($q['G_Name'])) $gLowScore = $q['G_Name'];

    // Castle Siege
    $q = $db->query_fetch_single("SELECT OWNER_GUILD FROM MuCastle_DATA");
    if(is_array($q)) {
        $owner = trim((string)$q['OWNER_GUILD']);
        if($owner !== '' && strcasecmp($owner, 'No Owner') !== 0) {
            $csOwner = $owner;
            $qm = $db->query_fetch_single("SELECT G_Master FROM Guild WHERE G_Name = ?", array($owner));
            if(is_array($qm) && !empty($qm['G_Master'])) $csMaster = $qm['G_Master'];
        }
    }
} catch (Exception $e) { }

// Aforo configurado en el panel
$maxUsers = (int)custom_config('server_max_users');
if($maxUsers <= 0) $maxUsers = (int)custom_config('maximum_online');
$occupancy = ($maxUsers > 0) ? min(100, (int)round(($onlineNow / $maxUsers) * 100)) : null;

// Serie del grafico
$chartLabels = array(); $chartValues = array(); $regWeekTotal = 0;
for($i = 6; $i >= 0; $i--) {
    $ts = strtotime("-$i days");
    $key = date('Y-m-d', $ts);
    $val = isset($regByDay[$key]) ? $regByDay[$key] : 0;
    $chartLabels[] = date('d/m', $ts);
    $chartValues[] = $val;
    $regWeekTotal += $val;
}

// Serie de visitas (includes/visit.counter.php)
$visitLabels = array(); $visitValues = array(); $visitWeekTotal = 0; $viewsWeekTotal = 0;
try {
    if(@include_once(__PATH_INCLUDES__ . 'visit.counter.php')) {
        foreach(msm_visits_read(7) as $day => $v) {
            $visitLabels[] = date('d/m', strtotime($day));
            $visitValues[] = $v['visits'];
            $visitWeekTotal += $v['visits'];
            $viewsWeekTotal += $v['views'];
        }
    }
} catch (Throwable $e) { }
if(empty($visitLabels)) { $visitLabels = $chartLabels; $visitValues = array_fill(0, count($chartLabels), 0); }

// =====================================================================
// 3. ECONOMIA (joyas en circulacion). Cache de 1 hora.
// =====================================================================
$jewelIds = array(
    'Chaos'    => array(12, 15),
    'Bless'    => array(14, 13),
    'Soul'     => array(14, 14),
    'Life'     => array(14, 16),
    'Creation' => array(14, 22)
);
$jewelStats = array();
$economyCacheFile = $dashCacheDir . 'economy_cache.json';

if(file_exists($economyCacheFile) && (time() - filemtime($economyCacheFile) < 3600)) {
    $cached = json_decode((string)@file_get_contents($economyCacheFile), true);
    if(is_array($cached)) $jewelStats = $cached;
}

if(empty($jewelStats)) {
    @ini_set('memory_limit', '512M');
    @set_time_limit(120);

    if(!function_exists('msm_dash_count_jewels')) {
        // Cada item ocupa 16 bytes (32 caracteres hex). Id = byte 0, grupo = mitad alta del byte 9.
        function msm_dash_count_jewels($binData, $jewelIds, &$holders, $account) {
            if(empty($binData)) return;
            $hex = strtoupper(bin2hex($binData));
            $len = strlen($hex);
            for($i = 0; $i + 32 <= $len; $i += 32) {
                $item = substr($hex, $i, 32);
                if(substr($item, 0, 4) === 'FFFF') continue;
                $itemId = hexdec(substr($item, 0, 2));
                $itemGroup = (hexdec(substr($item, 18, 2)) >> 4) & 0xF;
                foreach($jewelIds as $jName => $ids) {
                    if($itemGroup == $ids[0] && $itemId == $ids[1]) {
                        if(!isset($holders[$account][$jName])) $holders[$account][$jName] = 0;
                        $holders[$account][$jName]++;
                        break;
                    }
                }
            }
        }
    }

    try {
        if(!isset($db)) $db = Connection::Database('Me_MuOnline');
        $holders = array();

        // Banco de joyas, si el server lo tiene
        $chk = $db->query_fetch_single("SELECT COUNT(*) as cnt FROM information_schema.tables WHERE table_name = 'JewelBank'");
        if(is_array($chk) && $chk['cnt'] > 0) {
            $jbCols = array('Chaos' => 'JewelOfChaos', 'Bless' => 'JewelOfBless', 'Soul' => 'JewelOfSoul', 'Life' => 'JewelOfLife', 'Creation' => 'JewelOfCreation');
            $validCols = array(); $colSelect = array();
            foreach($jbCols as $key => $col) {
                if($db->query("SELECT TOP 1 $col FROM JewelBank")) { $validCols[$key] = $col; $colSelect[] = "CAST($col AS INT) as $key"; }
            }
            if(!empty($validCols)) {
                $jbData = $db->query_fetch("SELECT AccountID, " . implode(',', $colSelect) . " FROM JewelBank");
                if(is_array($jbData)) {
                    foreach($jbData as $row) {
                        $acc = $row['AccountID'];
                        foreach($validCols as $jName => $dbCol) {
                            if(!isset($holders[$acc][$jName])) $holders[$acc][$jName] = 0;
                            $holders[$acc][$jName] += (int)$row[$jName];
                        }
                    }
                }
            }
        }

        $whData = $db->query_fetch("SELECT AccountID, Items FROM warehouse");
        if(is_array($whData)) { foreach($whData as $row) msm_dash_count_jewels($row['Items'], $jewelIds, $holders, $row['AccountID']); }

        $chk = $db->query_fetch_single("SELECT COUNT(*) as cnt FROM information_schema.tables WHERE table_name = 'ExtWarehouse'");
        if(is_array($chk) && $chk['cnt'] > 0) {
            $ewhData = $db->query_fetch("SELECT AccountID, Items FROM ExtWarehouse");
            if(is_array($ewhData)) { foreach($ewhData as $row) msm_dash_count_jewels($row['Items'], $jewelIds, $holders, $row['AccountID']); }
        }

        $invData = $db->query_fetch("SELECT AccountID, Inventory FROM Character");
        if(is_array($invData)) { foreach($invData as $row) msm_dash_count_jewels($row['Inventory'], $jewelIds, $holders, $row['AccountID']); }

        foreach($jewelIds as $jName => $unused) $jewelStats[$jName] = array('count' => 0, 'holder' => '-', 'holder_amt' => 0);
        foreach($holders as $acc => $items) {
            foreach($items as $jName => $qty) {
                if(!isset($jewelStats[$jName])) continue;
                $jewelStats[$jName]['count'] += $qty;
                if($qty > $jewelStats[$jName]['holder_amt']) {
                    $jewelStats[$jName]['holder_amt'] = $qty;
                    $jewelStats[$jName]['holder'] = (string)$acc;
                }
            }
        }
        @file_put_contents($economyCacheFile, json_encode($jewelStats), LOCK_EX);
    } catch (Throwable $e) { $jewelStats = array(); }
}

// =====================================================================
// 4. DONACIONES (solo lectura de los registros existentes)
// =====================================================================
$txnFile = __PATH_INCLUDES__ . 'config/donation_transactions.json';
$cfgFile = __PATH_INCLUDES__ . 'config/donation_config.json';
$transactions = file_exists($txnFile) ? json_decode((string)@file_get_contents($txnFile), true) : array();
$donationConfig = file_exists($cfgFile) ? json_decode((string)@file_get_contents($cfgFile), true) : array();
if(!is_array($transactions)) $transactions = array();
if(!is_array($donationConfig)) $donationConfig = array();

$totals = array(); $currencies = array(); $ranking = array();
foreach($transactions as $t) {
    if(!is_array($t) || !isset($t['status']) || $t['status'] != 'Completed') continue;
    $method = isset($t['method']) ? (string)$t['method'] : 'default';
    $amount = floatval(str_replace(',', '.', isset($t['amount']) ? (string)$t['amount'] : '0'));
    if(!isset($totals[$method])) { $totals[$method] = 0; $currencies[$method] = isset($t['currency']) ? (string)$t['currency'] : ''; }
    $totals[$method] += $amount;
    $user = isset($t['user']) ? (string)$t['user'] : '-';
    if(!isset($ranking[$user])) $ranking[$user] = 0;
    $ranking[$user] += $amount;
}
arsort($ranking);
$top5 = array_slice($ranking, 0, 5, true);
$maxTotal = !empty($totals) ? max($totals) : 0;

$uiMap = array(
    'mp'      => array('icon' => 'fa-handshake-o', 'class' => 'dm-mp',      'fallback_curr' => 'ARS'),
    'paypal'  => array('icon' => 'fa-paypal',      'class' => 'dm-paypal',  'fallback_curr' => 'USD'),
    'binance' => array('icon' => 'fa-btc',         'class' => 'dm-binance', 'fallback_curr' => 'USDT'),
    'stripe'  => array('icon' => 'fa-cc-stripe',   'class' => 'dm-stripe',  'fallback_curr' => 'USD'),
    'yape'    => array('icon' => 'fa-mobile',      'class' => 'dm-mp',      'fallback_curr' => 'PEN'),
    'bank'    => array('icon' => 'fa-university',  'class' => 'dm-binance', 'fallback_curr' => 'ARS'),
    'default' => array('icon' => 'fa-credit-card', 'class' => 'dm-mp',      'fallback_curr' => '$')
);

// =====================================================================
// 5. RENDER
// =====================================================================
echo '<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>';

// --- Aviso de version nueva ---
if($updateAvailable) {
    $newVer = isset($updateData['new_version']) ? $esc($updateData['new_version']) : '?.?.?';
    $downloadUrl = (isset($updateData['download_url']) && is_string($updateData['download_url']) && strpos($updateData['download_url'], 'https://') === 0) ? $updateData['download_url'] : '';
    echo '<div class="dash-update-banner">';
        echo '<div>';
            echo '<h4><i class="fa fa-gift"></i> ¡Nueva versión disponible!</h4>';
            echo '<p>La versión <strong>' . $newVer . '</strong> está lista. (Actual: ' . $esc($currentVersion) . ')</p>';
        echo '</div>';
        if($downloadUrl !== '') {
            echo '<a href="' . $esc($downloadUrl) . '" target="_blank" rel="noopener" class="dash-update-btn"><i class="fa fa-cloud-download"></i> Descargar</a>';
        }
    echo '</div>';
}

// --- Fila superior: totales + grafico ---
echo '<div class="row dashboard-row">';

    echo '<div class="col-lg-4 col-md-5 dashboard-col-left">';

        echo '<div class="dashboard-card card-welcome">';
            echo '<div style="z-index: 2;">';
                echo '<span class="welcome-badge">MuServerMaker CMS</span>';
                echo '<h3 class="welcome-title">Hola, ' . $esc($_SESSION['username']) . '</h3>';
                echo '<p class="welcome-subtitle">Versión ' . $esc($currentVersion) . '</p>';
            echo '</div>';
            echo '<div class="card-icon-bg"><i class="fa fa-shield"></i></div>';
        echo '</div>';

        echo '<div class="dashboard-card card-online">';
            echo '<h2 class="card-title-text">' . number_format($onlineNow) . ($maxUsers > 0 ? ' <small>/ ' . number_format($maxUsers) . '</small>' : '') . '</h2>';
            echo '<p class="card-subtitle-text">Online ahora' . ($occupancy !== null ? ' · ' . $occupancy . '% de ocupación' : '') . '</p>';
            if($occupancy !== null) {
                echo '<div class="occupancy-track"><div class="occupancy-fill" style="width: ' . $occupancy . '%;"></div></div>';
            }
            echo '<div class="card-icon-bg"><i class="fa fa-signal"></i></div>';
        echo '</div>';

        echo '<div class="dashboard-card card-blue-1">';
            echo '<h2 class="card-title-text">' . number_format($totalAccounts) . '</h2>';
            echo '<p class="card-subtitle-text">Total Registrados</p>';
            echo '<div class="card-icon-bg"><i class="fa fa-users"></i></div>';
        echo '</div>';

        echo '<div class="dashboard-card card-blue-2">';
            echo '<h2 class="card-title-text">' . number_format($totalCharacters) . '</h2>';
            echo '<p class="card-subtitle-text">Total Personajes</p>';
            echo '<div class="card-icon-bg"><i class="fa fa-user"></i></div>';
        echo '</div>';

        echo '<div class="dashboard-card card-blue-3">';
            echo '<h2 class="card-title-text">' . number_format($totalGuilds) . '</h2>';
            echo '<p class="card-subtitle-text">Total Guilds</p>';
            echo '<div class="card-icon-bg"><i class="fa fa-flag"></i></div>';
        echo '</div>';

        echo '<div class="dashboard-card card-blue-4">';
            echo '<h2 class="card-title-text">' . number_format($totalBanned) . '</h2>';
            echo '<p class="card-subtitle-text">Total Baneados</p>';
            echo '<div class="card-icon-bg"><i class="fa fa-ban"></i></div>';
        echo '</div>';

    echo '</div>';

    echo '<div class="col-lg-8 col-md-7 dashboard-col-right">';
        echo '<div class="chart-card">';
            echo '<div class="chart-header">';
                echo '<div class="chart-tabs">';
                    echo '<button type="button" class="chart-tab is-active" data-chart="visits">Visitas a la página</button>';
                    echo '<button type="button" class="chart-tab" data-chart="regs">Cuentas nuevas</button>';
                echo '</div>';
                echo '<div class="chart-updated" id="dashChartTotal"></div>';
            echo '</div>';
            echo '<div class="chart-body"><canvas id="regChart"></canvas></div>';
        echo '</div>';
    echo '</div>';

echo '</div>';

// --- Fila 2: registros + actividad ---
echo '<div class="row dashboard-row-bottom">';

    echo '<div class="col-md-6">';
    echo '<div class="bottom-card">';
        echo '<div class="bottom-card-header">';
            echo '<h4 class="bottom-card-title">Últimos Registros</h4>';
            echo '<p class="bottom-card-subtitle">Las seis cuentas creadas más recientemente</p>';
        echo '</div>';
        echo '<div class="table-responsive"><table class="table table-registrations">';
            echo '<thead><tr><th>Usuario</th><th>Creada</th><th>Última conexión</th><th class="text-right">Estado</th></tr></thead><tbody>';
            if(!empty($latestRegistrations)) {
                foreach($latestRegistrations as $reg) {
                    $isOnline = (isset($reg['ConnectStat']) && $reg['ConnectStat'] == 1);
                    echo '<tr>';
                    echo '<td class="reg-user">' . $esc($reg['memb___id']) . '</td>';
                    echo '<td>' . $fmtDate($reg['appl_days']) . '</td>';
                    echo '<td>' . $fmtDate(isset($reg['ConnectTM']) ? $reg['ConnectTM'] : null, 'd/m/Y H:i') . '</td>';
                    echo '<td class="text-right"><span class="status-badge ' . ($isOnline ? 'status-online' : 'status-offline') . '">' . ($isOnline ? 'Online' : 'Offline') . '</span></td>';
                    echo '</tr>';
                }
            } else {
                echo '<tr><td colspan="4" class="text-center dash-empty">No hay registros.</td></tr>';
            }
        echo '</tbody></table></div>';
    echo '</div>';
    echo '</div>';

    echo '<div class="col-md-6">';
    echo '<div class="bottom-card">';
        echo '<div class="bottom-card-header">';
            echo '<h4 class="bottom-card-title">Actividad Reciente</h4>';
            echo '<p class="bottom-card-subtitle">Últimas conexiones al juego y su IP</p>';
        echo '</div>';
        echo '<div class="table-responsive"><table class="table table-registrations">';
            echo '<thead><tr><th>Usuario</th><th>IP</th><th>Conexión</th><th class="text-right">Estado</th></tr></thead><tbody>';
            if(!empty($recentActivity)) {
                foreach($recentActivity as $act) {
                    $isOnline = (isset($act['ConnectStat']) && $act['ConnectStat'] == 1);
                    echo '<tr>';
                    echo '<td class="reg-user">' . $esc($act['memb___id']) . '</td>';
                    echo '<td>' . $esc(isset($act['IP']) && $act['IP'] !== null && $act['IP'] !== '' ? $act['IP'] : '-') . '</td>';
                    echo '<td>' . $fmtDate(isset($act['ConnectTM']) ? $act['ConnectTM'] : null, 'd/m/Y H:i') . '</td>';
                    echo '<td class="text-right"><span class="status-badge ' . ($isOnline ? 'status-online' : 'status-offline') . '">' . ($isOnline ? 'Online' : 'Offline') . '</span></td>';
                    echo '</tr>';
                }
            } else {
                echo '<tr><td colspan="4" class="text-center dash-empty">Sin actividad registrada.</td></tr>';
            }
        echo '</tbody></table></div>';
    echo '</div>';
    echo '</div>';

echo '</div>';

// --- Fila 3: donaciones + top donadores ---
echo '<div class="row dashboard-row-bottom">';

    echo '<div class="col-md-6">';
    echo '<div class="bottom-card">';
        echo '<div class="bottom-card-header">';
            echo '<h4 class="bottom-card-title">Estadísticas de Donaciones</h4>';
            echo '<p class="bottom-card-subtitle">Total acreditado por cada método activo</p>';
        echo '</div>';
        echo '<div class="donations-grid">';
            $hasMethods = false;
            if(isset($donationConfig['methods']) && is_array($donationConfig['methods'])) {
                foreach($donationConfig['methods'] as $key => $data) {
                    if(!is_array($data) || !isset($data['active']) || $data['active'] != 1) continue;
                    $hasMethods = true;
                    $ui = isset($uiMap[$key]) ? $uiMap[$key] : $uiMap['default'];
                    $displayName = isset($data['name']) ? $data['name'] : ucfirst((string)$key);
                    $totalAmount = isset($totals[$key]) ? $totals[$key] : 0;
                    $currency = !empty($currencies[$key]) ? $currencies[$key] : (isset($data['currency']) ? $data['currency'] : $ui['fallback_curr']);
                    $percent = ($maxTotal > 0) ? round(($totalAmount / $maxTotal) * 100) : 0;
                    echo '<div class="donation-item ' . $ui['class'] . '">';
                        echo '<div class="donation-header"><i class="fa ' . $ui['icon'] . ' donation-icon"></i><span class="donation-name">' . $esc($displayName) . '</span></div>';
                        echo '<div class="donation-amount">' . number_format($totalAmount, 2, '.', ',') . ' ' . $esc($currency) . '</div>';
                        echo '<div class="donation-progress"><div class="donation-bar" style="width: ' . $percent . '%;"></div></div>';
                    echo '</div>';
                }
            }
            if(!$hasMethods) echo '<p class="dash-empty text-center">No hay métodos de pago activos.</p>';
        echo '</div>';
    echo '</div>';
    echo '</div>';

    echo '<div class="col-md-6">';
    echo '<div class="bottom-card">';
        echo '<div class="bottom-card-header">';
            echo '<h4 class="bottom-card-title">Top Donadores</h4>';
            echo '<p class="bottom-card-subtitle">Ranking por monto acumulado (histórico)</p>';
        echo '</div>';
        echo '<div class="table-responsive"><table class="table table-donators">';
            echo '<thead><tr><th class="text-center">#</th><th>Usuario</th><th class="text-right">Total</th></tr></thead><tbody>';
            if(!empty($top5)) {
                $i = 1;
                foreach($top5 as $user => $totalAmount) {
                    $rankBadge = ($i <= 3) ? '<div class="rank-badge rank-' . $i . '">' . $i . '</div>' : '<span class="dash-rank-plain">' . $i . '</span>';
                    echo '<tr>';
                    echo '<td class="text-center">' . $rankBadge . '</td>';
                    echo '<td><span class="donator-user">' . $esc($user) . '</span></td>';
                    echo '<td class="text-right text-success">' . number_format($totalAmount, 2) . '</td>';
                    echo '</tr>';
                    $i++;
                }
            } else {
                echo '<tr><td colspan="3" class="text-center dash-empty">Aún no hay donaciones registradas.</td></tr>';
            }
        echo '</tbody></table></div>';
    echo '</div>';
    echo '</div>';

echo '</div>';

// --- Fila 4: economia + guilds ---
echo '<div class="row dashboard-row-bottom">';

    echo '<div class="col-md-6">';
    echo '<div class="bottom-card">';
        echo '<div class="bottom-card-header">';
            echo '<h4 class="bottom-card-title">Economía del juego</h4>';
            echo '<p class="bottom-card-subtitle">Joyas en circulación (banco, baúl e inventario) · se actualiza cada hora</p>';
        echo '</div>';
        echo '<div class="table-responsive"><table class="table table-economy">';
            echo '<thead><tr><th>Joya</th><th class="text-center">Cantidad</th><th class="text-right">Mayor tenedor</th></tr></thead><tbody>';
            $jewelColors = array('Chaos' => '#a855f7', 'Bless' => '#d946ef', 'Soul' => '#ec4899', 'Life' => '#f43f5e', 'Creation' => '#eab308');
            if(!empty($jewelStats)) {
                foreach($jewelStats as $name => $data) {
                    $color = isset($jewelColors[$name]) ? $jewelColors[$name] : '#94a3b8';
                    $count = isset($data['count']) ? (int)str_replace(',', '', (string)$data['count']) : 0;
                    $holderAmt = isset($data['holder_amt']) ? (int)str_replace(',', '', (string)$data['holder_amt']) : 0;
                    $holder = isset($data['holder']) ? $data['holder'] : '-';
                    echo '<tr>';
                    echo '<td><i class="fa fa-diamond" style="color:' . $color . ';"></i> ' . $esc($name) . '</td>';
                    echo '<td class="text-center">' . number_format($count) . '</td>';
                    echo '<td class="text-right"><span class="economy-holder">' . ($holderAmt > 0 ? $esc($holder) . ' (' . number_format($holderAmt) . ')' : '-') . '</span></td>';
                    echo '</tr>';
                }
            } else {
                echo '<tr><td colspan="3" class="text-center dash-empty">No se pudieron calcular los datos.</td></tr>';
            }
        echo '</tbody></table></div>';
    echo '</div>';
    echo '</div>';

    echo '<div class="col-md-6">';
    echo '<div class="bottom-card">';
        echo '<div class="bottom-card-header">';
            echo '<h4 class="bottom-card-title">Info Guilds</h4>';
            echo '<p class="bottom-card-subtitle">Estadísticas de clanes y Castle Siege</p>';
        echo '</div>';
        echo '<div class="guild-stats-container">';
            $guildRows = array(
                array('Total Guilds creados:', number_format($totalGuilds), ''),
                array('Personajes con Guild:', number_format($gMembers), ''),
                array('Personajes sin Guild:', number_format($gNoMembers), 'highlight'),
                array('Más score:', $esc($gHighScore), 'highlight'),
                array('Menos score:', $esc($gLowScore), 'highlight'),
                array('<i class="fa fa-trophy" style="color:#f59e0b;"></i> Dueño de Castle Siege:', $esc($csOwner), ''),
                array('<i class="fa fa-user" style="color:#a855f7;"></i> Guild Master CS:', $esc($csMaster), '')
            );
            foreach($guildRows as $gr) {
                echo '<div class="guild-stat-row"><span class="guild-stat-label">' . $gr[0] . '</span><span class="guild-stat-value ' . $gr[2] . '">' . $gr[1] . '</span></div>';
            }
        echo '</div>';
    echo '</div>';
    echo '</div>';

echo '</div>';

// --- Grafico (dos series: visitas y cuentas nuevas) ---
$dashSeries = array(
    'visits' => array(
        'labels' => $visitLabels, 'values' => $visitValues, 'label' => 'Visitas',
        'bg' => 'rgba(96, 165, 250, 0.35)', 'border' => '#60a5fa',
        'total' => 'Visitas de la semana: ' . number_format($visitWeekTotal) . ' · Páginas vistas: ' . number_format($viewsWeekTotal)
    ),
    'regs' => array(
        'labels' => $chartLabels, 'values' => $chartValues, 'label' => 'Cuentas nuevas',
        'bg' => 'rgba(74, 222, 128, 0.35)', 'border' => '#4ade80',
        'total' => 'Total de la semana: ' . number_format($regWeekTotal)
    )
);
echo '<script>';
echo 'document.addEventListener("DOMContentLoaded", function() {';
echo 'var series = ' . json_encode($dashSeries, JSON_HEX_TAG | JSON_INVALID_UTF8_SUBSTITUTE) . ';';
echo 'var el = document.getElementById("regChart");';
echo 'var totalEl = document.getElementById("dashChartTotal");';
echo 'var tabs = document.querySelectorAll(".chart-tab");';
echo 'function setTotal(k) { if (totalEl) totalEl.textContent = series[k].total; }';
echo 'setTotal("visits");';
echo 'if (!el || typeof Chart === "undefined") return;';
echo 'function ds(k) { var s = series[k]; return { label: s.label, data: s.values, backgroundColor: s.bg, borderColor: s.border, borderWidth: 1, borderRadius: 6, maxBarThickness: 48 }; }';
echo 'var chart = new Chart(el.getContext("2d"), {';
echo 'type: "bar",';
echo 'data: { labels: series.visits.labels, datasets: [ds("visits")] },';
echo 'options: { responsive: true, maintainAspectRatio: false,';
echo 'plugins: { legend: { display: false }, tooltip: { backgroundColor: "#1e293b", titleColor: "#fff", bodyColor: "#94a3b8", borderColor: "#334155", borderWidth: 1, displayColors: false } },';
echo 'scales: { x: { grid: { display: false }, ticks: { color: "#94a3b8", font: { size: 11 } } }, y: { beginAtZero: true, suggestedMax: 5, grid: { color: "rgba(255, 255, 255, 0.05)" }, ticks: { color: "#94a3b8", font: { size: 11 }, precision: 0 } } }';
echo '}';
echo '});';
echo 'tabs.forEach(function(t) { t.addEventListener("click", function() {';
echo 'var k = t.getAttribute("data-chart"); if (!series[k]) return;';
echo 'tabs.forEach(function(x) { x.classList.remove("is-active"); }); t.classList.add("is-active");';
echo 'chart.data.labels = series[k].labels; chart.data.datasets = [ds(k)]; chart.update(); setTotal(k);';
echo '}); });';
echo '});';
echo '</script>';