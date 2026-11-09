<?php
/**
 * Dashboard Module (Home) - FIXED LAYOUT
 * Pure PHP Version (No closing tags)
 */

// =====================================================================
// INICIO: LÓGICA DE ACTUALIZACIONES
// =====================================================================
$updateAvailable = false;
$updateData = [];

if (!defined('__MUSERVERMAKER_VERSION__')) define('__MUSERVERMAKER_VERSION__', '1.0.0');
$currentVersion = __MUSERVERMAKER_VERSION__;

try {
    $licenseUrl = 'https://licencias.muservermaker.com/api/check_update.php'; 
    $postData = ['version' => $currentVersion, 'domain' => $_SERVER['HTTP_HOST']];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $licenseUrl);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 3);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode == 200 && $response) {
        $json = json_decode($response, true);
        if (isset($json['update']) && $json['update'] === true) {
            $updateAvailable = true;
            $updateData = $json;
        }
    }
} catch (Exception $e) {}
// =====================================================================

// --- 1. DATOS FALSOS PARA GRÁFICO ---
$visitorDates = [];
$visitorCounts = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $visitorDates[] = $date;
    $visitorCounts[] = rand(250, 450); 
}
$jsonDates = json_encode($visitorDates);
$jsonCounts = json_encode($visitorCounts);

// --- 2. VARIABLES ---
$totalAccounts = 0;
$totalCharacters = 0;
$totalGuilds = 0;
$totalBanned = 0;
$latestRegistrations = [];
$gMembers = 0;
$gNoMembers = 0;
$gHighScore = "Ninguno";
$gLowScore = "Ninguno";
$csOwner = "SIN DUEÑO";
$csMaster = "-";

$jewelStats = [
    'Chaos'    => ['count' => 0, 'holder' => '-', 'holder_amt' => 0],
    'Bless'    => ['count' => 0, 'holder' => '-', 'holder_amt' => 0],
    'Soul'     => ['count' => 0, 'holder' => '-', 'holder_amt' => 0],
    'Life'     => ['count' => 0, 'holder' => '-', 'holder_amt' => 0],
    'Creation' => ['count' => 0, 'holder' => '-', 'holder_amt' => 0],
];

// --- 3. CONEXIÓN Y CONSULTAS ---
try {
    $db = Connection::Database('Me_MuOnline');

    $qAccounts = $db->query_fetch_single("SELECT COUNT(*) as count FROM MEMB_INFO");
    if(is_array($qAccounts)) $totalAccounts = $qAccounts['count'];

    $qChars = $db->query_fetch_single("SELECT COUNT(*) as count FROM Character");
    if(is_array($qChars)) $totalCharacters = $qChars['count'];

    $qGuilds = $db->query_fetch_single("SELECT COUNT(*) as count FROM Guild");
    if(is_array($qGuilds)) $totalGuilds = $qGuilds['count'];

    $qBanned = $db->query_fetch_single("SELECT COUNT(*) as count FROM MEMB_INFO WHERE bloc_code = 1");
    if(is_array($qBanned)) $totalBanned = $qBanned['count'];

    $sqlRegistros = "SELECT TOP 6 i.memb___id, i.appl_days, s.ConnectStat 
                     FROM MEMB_INFO i
                     LEFT JOIN MEMB_STAT s ON i.memb___id = s.memb___id
                     ORDER BY i.appl_days DESC, i.memb_guid DESC";
    $latestRegistrations = $db->query_fetch($sqlRegistros);

    $qGMem = $db->query_fetch_single("SELECT COUNT(*) as count FROM GuildMember");
    if(is_array($qGMem)) $gMembers = $qGMem['count'];
    $gNoMembers = $totalCharacters - $gMembers;
    if($gNoMembers < 0) $gNoMembers = 0;

    $qHigh = $db->query_fetch_single("SELECT TOP 1 G_Name FROM Guild ORDER BY G_Score DESC");
    if(is_array($qHigh) && !empty($qHigh['G_Name'])) $gHighScore = $qHigh['G_Name'];

} catch (Exception $e) { }

// --- LÓGICA ECONOMÍA ---
$itemSize = 32; 
$jewelIds = [
    'Chaos'    => [12, 15], 
    'Bless'    => [14, 13], 
    'Soul'     => [14, 14], 
    'Life'     => [14, 16], 
    'Creation' => [14, 22]
];

$cacheFile = __PATH_INCLUDES__ . 'config/economy_cache.json';
$cacheTime = 3600; 
$jewelStats = [];

if (file_exists($cacheFile) && (time() - filemtime($cacheFile) < $cacheTime)) {
    $jewelStats = json_decode(file_get_contents($cacheFile), true);
} else {
    ini_set('memory_limit', '512M');
    set_time_limit(120); 

    try {
        if(!isset($db)) $db = Connection::Database('MuOnline');
        $holders = [];
        foreach($jewelIds as $name => $code) $totalCounts[$name] = 0;

        $chkTable = $db->query_fetch_single("SELECT count(*) as cnt FROM information_schema.tables WHERE table_name = 'JewelBank'");
        if ($chkTable && $chkTable['cnt'] > 0) {
            $jbCols = ['Chaos' => 'JewelOfChaos', 'Bless' => 'JewelOfBless', 'Soul' => 'JewelOfSoul', 'Life' => 'JewelOfLife', 'Creation' => 'JewelOfCreation'];
            $validCols = []; $colSelect = [];
            foreach($jbCols as $key => $col) {
                try { $db->query("SELECT TOP 1 $col FROM JewelBank"); $validCols[$key] = $col; $colSelect[] = "CAST($col AS INT) as $key"; } catch(Exception $e) { continue; }
            }
            if(!empty($validCols)) {
                $sqlJB = "SELECT AccountID, " . implode(',', $colSelect) . " FROM JewelBank";
                $jbData = $db->query_fetch($sqlJB);
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

        if (!function_exists('parse_items_hex')) {
            function parse_items_hex($binData, $itemSize, $jewelIds, &$holders, $account) {
                if(empty($binData)) return;
                $hex = bin2hex($binData);
                $hexLen = strlen($hex);
                $itemHexLen = $itemSize * 2; 
                for ($i = 0; $i < $hexLen; $i += $itemHexLen) {
                    $itemHex = substr($hex, $i, $itemHexLen);
                    if(strlen($itemHex) < $itemHexLen) continue; 
                    if(strtoupper(substr($itemHex, 0, 4)) == 'FFFF') continue;
                    $byte0 = hexdec(substr($itemHex, 0, 2));
                    $byte1 = hexdec(substr($itemHex, 2, 2));
                    $itemID = $byte0; 
                    if(($byte1 & 128) == 128) $itemID += 256;
                    $itemGroup = ($byte1 & 240) >> 4;
                    foreach($jewelIds as $jName => $ids) {
                        if($itemGroup == $ids[0] && $itemID == $ids[1]) {
                             if(!isset($holders[$account][$jName])) $holders[$account][$jName] = 0;
                             $holders[$account][$jName]++;
                             break;
                        }
                    }
                }
            }
        }

        $whData = $db->query_fetch("SELECT AccountID, Items FROM warehouse");
        if(is_array($whData)) { foreach($whData as $row) parse_items_hex($row['Items'], $itemSize, $jewelIds, $holders, $row['AccountID']); }

        $chkExt = $db->query_fetch_single("SELECT count(*) as cnt FROM information_schema.tables WHERE table_name = 'ExtWarehouse'");
        if ($chkExt && $chkExt['cnt'] > 0) {
            $ewhData = $db->query_fetch("SELECT AccountID, Items FROM ExtWarehouse");
            if(is_array($ewhData)) { foreach($ewhData as $row) parse_items_hex($row['Items'], $itemSize, $jewelIds, $holders, $row['AccountID']); }
        }

        $invData = $db->query_fetch("SELECT AccountID, Inventory FROM Character");
        if(is_array($invData)) { foreach($invData as $row) parse_items_hex($row['Inventory'], $itemSize, $jewelIds, $holders, $row['AccountID']); }

        foreach($jewelIds as $jName => $v) $jewelStats[$jName] = ['count' => 0, 'holder' => '-', 'holder_amt' => 0];
        foreach($holders as $acc => $items) {
            foreach($items as $jName => $qty) {
                if(!isset($jewelStats[$jName])) continue;
                $jewelStats[$jName]['count'] += $qty;
                if($qty > $jewelStats[$jName]['holder_amt']) {
                    $jewelStats[$jName]['holder_amt'] = $qty;
                    $jewelStats[$jName]['holder'] = $acc; 
                }
            }
        }
        foreach($jewelStats as &$stat) {
            $stat['count'] = number_format($stat['count']);
            $stat['holder_amt'] = number_format($stat['holder_amt']);
        }
        file_put_contents($cacheFile, json_encode($jewelStats));
    } catch (Exception $e) { $jewelStats = []; }
}

// --- RENDERIZADO HTML ---
echo '<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>';

// =========================================================================
// CORRECCIÓN: EL BANNER VA AFUERA DE LA FILA PRINCIPAL (FULL WIDTH)
// =========================================================================
if ($updateAvailable) {
    $newVer = isset($updateData['new_version']) ? htmlspecialchars($updateData['new_version']) : '?.?.?';
    $downloadUrl = isset($updateData['download_url']) ? $updateData['download_url'] : '#'; 

    echo '<div class="row mb-4">'; 
        echo '<div class="col-12">';
            // Banner simplificado sin changelog
            echo '<div class="dashboard-card shadow-sm" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; border:none; display: flex; flex-direction: row; align-items: center; justify-content: space-between; padding: 20px; border-radius: 8px;">';
                
                // COLUMNA IZQUIERDA (SOLO TEXTO PRINCIPAL)
                echo '<div>';
                    echo '<h4 style="margin: 0; font-weight: bold;"><i class="fa fa-gift" style="margin-right: 10px;"></i> ¡Nueva versión disponible!</h4>';
                    echo '<p style="margin: 5px 0 0 0; opacity: 0.95;">La versión <strong>'.$newVer.'</strong> está lista. (Actual: '.$currentVersion.')</p>';
                echo '</div>';
                
                // COLUMNA DERECHA (BOTÓN BLANCO / LETRA NEGRA)
                if ($downloadUrl !== '#') {
                    echo '<a href="'.$downloadUrl.'" target="_blank" class="btn btn-light shadow" style="color: #000; font-weight: bold; padding: 10px 30px; border-radius: 50px; text-transform: uppercase; border: none;">';
                        echo '<i class="fa fa-cloud-download"></i> Descargar';
                    echo '</a>';
                }
            echo '</div>';
        echo '</div>';
    echo '</div>';
}
// =========================================================================


// AHORA SÍ EMPIEZA LA FILA DEL DASHBOARD (IZQ Y DER)
echo '<div class="row dashboard-row">';
    
    // COLUMNA IZQUIERDA (STATS CARDS)
    echo '<div class="col-lg-4 col-md-5 dashboard-col-left">';
        
        // Card Welcome
        echo '<div class="dashboard-card card-welcome">';
            echo '<div style="z-index: 2;">';
                echo '<span class="welcome-badge">Lifetime License</span>';
                echo '<h3 class="welcome-title">Bienvenido/a a tu ADMINCP</h3>';
                echo '<p class="welcome-subtitle">License Status: ON</p>';
            echo '</div>';
            echo '<div class="card-icon-bg"><i class="fa fa-shield"></i></div>';
        echo '</div>';

        // Card Total Accounts
        echo '<div class="dashboard-card card-blue-1">';
            echo '<h2 class="card-title-text">' . number_format($totalAccounts) . '</h2>';
            echo '<p class="card-subtitle-text">Total Registrados</p>';
            echo '<div class="card-icon-bg"><i class="fa fa-users"></i></div>';
        echo '</div>';

        // Card Total Characters
        echo '<div class="dashboard-card card-blue-2">';
            echo '<h2 class="card-title-text">' . number_format($totalCharacters) . '</h2>';
            echo '<p class="card-subtitle-text">Total Personajes</p>';
            echo '<div class="card-icon-bg"><i class="fa fa-user"></i></div>';
        echo '</div>';

        // Card Total Guilds
        echo '<div class="dashboard-card card-blue-3">';
            echo '<h2 class="card-title-text">' . number_format($totalGuilds) . '</h2>';
            echo '<p class="card-subtitle-text">Total Guilds</p>';
            echo '<div class="card-icon-bg"><i class="fa fa-flag"></i></div>';
        echo '</div>';

        // Card Banned
        echo '<div class="dashboard-card card-blue-4">';
            echo '<h2 class="card-title-text">' . number_format($totalBanned) . '</h2>';
            echo '<p class="card-subtitle-text">Total Banned</p>';
            echo '<div class="card-icon-bg"><i class="fa fa-ban"></i></div>';
        echo '</div>';

    echo '</div>'; // Fin col-left

    // COLUMNA DERECHA (CHART)
    echo '<div class="col-lg-8 col-md-7 dashboard-col-right">';
        echo '<div class="chart-card">';
            echo '<div class="chart-header">';
                echo '<div class="chart-title">Visitor Log</div>';
                echo '<div class="chart-updated">Updated 1 min ago</div>';
            echo '</div>';
            echo '<div class="chart-body">';
                echo '<canvas id="visitorChart"></canvas>';
            echo '</div>';
        echo '</div>';
    echo '</div>'; // Fin col-right

echo '</div>'; // Fin row top (Dashboard Row)

// --- SECCION INFERIOR ---
echo '<div class="row dashboard-row-bottom">';
    
    // 1. ÚLTIMOS REGISTROS
    echo '<div class="col-lg-4 col-md-5">';
    echo '<div class="bottom-card">';
        echo '<div class="bottom-card-header">';
            echo '<h4 class="bottom-card-title">Últimos Registros</h4>';
            echo '<p class="bottom-card-subtitle">Estadísticas basadas en los últimos 5 minutos</p>';
        echo '</div>';
        echo '<div class="table-responsive">';
            echo '<table class="table table-registrations">';
                echo '<thead><tr><th>País</th><th>Usuario</th><th>Fecha</th><th class="text-right">Estado</th></tr></thead>';
                echo '<tbody>';
                
                if(is_array($latestRegistrations) && count($latestRegistrations) > 0) {
                    foreach($latestRegistrations as $reg) {
                        $isOnline = ($reg['ConnectStat'] == 1);
                        $statusClass = $isOnline ? 'status-online' : 'status-offline';
                        $statusText = $isOnline ? 'Online' : 'Offline';
                        
                        $dateFormatted = "N/A";
                        if (!empty($reg['appl_days'])) {
                            try {
                                $dateObj = new DateTime($reg['appl_days']);
                                $dateFormatted = $dateObj->format('d/m/Y');
                            } catch (Exception $e) { $dateFormatted = "N/A"; }
                        }
                        
                        echo '<tr>';
                        echo '<td><i class="fa fa-flag" style="color: #fff;"></i></td>';
                        echo '<td class="reg-user">' . htmlspecialchars($reg['memb___id']) . '</td>';
                        echo '<td>' . $dateFormatted . '</td>';
                        echo '<td class="text-right"><span class="status-badge ' . $statusClass . '">' . $statusText . '</span></td>';
                        echo '</tr>';
                    }
                } else {
                    echo '<tr><td colspan="4" class="text-center" style="padding: 20px; color: #64748b;">No hay registros recientes.</td></tr>';
                }
                
                echo '</tbody></table>';
        echo '</div>';
    echo '</div>';
    echo '</div>'; // Fin Registros

    // 2. ESTADISTICAS DONACIONES (Middle)
    echo '<div class="col-lg-8 col-md-7">';
    echo '<div class="bottom-card">';
    echo '<div class="bottom-card-header">';
        echo '<h4 class="bottom-card-title">Estadísticas de Donaciones</h4>';
        echo '<p class="bottom-card-subtitle">Estadísticas basadas en métodos Automáticos</p>';
    echo '</div>';
    
    echo '<div class="donations-grid">';
        
        $txnFile = __PATH_INCLUDES__ . 'config/donation_transactions.json';
        $cfgFile = __PATH_INCLUDES__ . 'config/donation_config.json';

        $transactions = file_exists($txnFile) ? json_decode(file_get_contents($txnFile), true) : [];
        $config = file_exists($cfgFile) ? json_decode(file_get_contents($cfgFile), true) : ['methods' => []];

        $totals = [];
        $currencies = [];

        if (is_array($transactions)) {
            foreach ($transactions as $t) {
                if (isset($t['status']) && $t['status'] == 'Completed') {
                    $method = $t['method'];
                    $rawAmount = str_replace(',', '.', $t['amount']);
                    $amount = floatval($rawAmount);
                    
                    if (!isset($totals[$method])) {
                        $totals[$method] = 0;
                        $currencies[$method] = $t['currency'];
                    }
                    $totals[$method] += $amount;
                }
            }
        }

        $maxTotal = !empty($totals) ? max($totals) : 0;

        $uiMap = [
            'mp' => ['icon' => 'fa-handshake-o', 'class' => 'dm-mp', 'fallback_curr' => 'ARS'],
            'paypal' => ['icon' => 'fa-paypal', 'class' => 'dm-paypal', 'fallback_curr' => 'USD'],
            'binance' => ['icon' => 'fa-btc', 'class' => 'dm-binance', 'fallback_curr' => 'USDT'],
            'stripe' => ['icon' => 'fa-cc-stripe', 'class' => 'dm-stripe', 'fallback_curr' => 'USD'],
            'yape' => ['icon' => 'fa-mobile', 'class' => 'dm-mp', 'fallback_curr' => 'PEN'],
            'bank' => ['icon' => 'fa-university', 'class' => 'dm-binance', 'fallback_curr' => 'ARS'],
            'default' => ['icon' => 'fa-credit-card', 'class' => 'dm-mp', 'fallback_curr' => '$']
        ];

        $hasMethods = false;
        if (isset($config['methods']) && is_array($config['methods'])) {
            foreach ($config['methods'] as $key => $data) {
                if (!isset($data['active']) || $data['active'] != 1) continue;
                $hasMethods = true;
                $ui = isset($uiMap[$key]) ? $uiMap[$key] : $uiMap['default'];
                $displayName = isset($data['name']) ? $data['name'] : ucfirst($key);
                $totalAmount = isset($totals[$key]) ? $totals[$key] : 0;
                $currency = isset($currencies[$key]) ? $currencies[$key] : (isset($data['currency']) ? $data['currency'] : $ui['fallback_curr']);
                $percent = ($maxTotal > 0) ? ($totalAmount / $maxTotal) * 100 : 0;
                $displayAmount = number_format($totalAmount, 2, '.', ',') . ' ' . $currency;
                
                echo '<div class="donation-item ' . $ui['class'] . '">';
                    echo '<div class="donation-header">';
                        echo '<i class="fa ' . $ui['icon'] . ' donation-icon"></i>';
                        echo '<span class="donation-name">' . htmlspecialchars($displayName) . '</span>';
                    echo '</div>';
                    echo '<div class="donation-amount">' . $displayAmount . '</div>';
                    echo '<div class="donation-progress">';
                        echo '<div class="donation-bar" style="width: ' . $percent . '%;"></div>';
                    echo '</div>';
                echo '</div>';
            }
        }
        
        if (!$hasMethods) {
            echo '<p style="padding:20px; text-align:center; color:#666; width:100%;">No hay métodos de pago activos.</p>';
        }
        
    echo '</div>'; // Fin grid
    echo '</div>'; // Fin card
    echo '</div>'; // Fin col
echo '</div>'; // Fin row

// --- SECCION ECONOMIA, GUILDS, TOP DONATORS ---
echo '<div class="row dashboard-row-bottom">';
    
    // 3. ECONOMIA
    echo '<div class="col-lg-4 col-md-6">';
    echo '<div class="bottom-card">';
        echo '<div class="bottom-card-header">';
            echo '<h4 class="bottom-card-title">Economía del juego</h4>';
            echo '<p class="bottom-card-subtitle">Joyas en circulación (Banco + Baúl + Inventario)</p>';
        echo '</div>';
        echo '<div class="table-responsive">';
            echo '<table class="table table-economy">';
                echo '<thead><tr><th>Joya</th><th class="text-center">Cant.</th><th class="text-right">Mayor Tenedor</th></tr></thead>';
                echo '<tbody>';
                
                if(!empty($jewelStats)) {
                    foreach($jewelStats as $name => $data) {
                        $color = ($name == 'Chaos') ? '#a855f7' : (($name == 'Bless') ? '#d946ef' : (($name == 'Soul') ? '#ec4899' : (($name == 'Life') ? '#f43f5e' : '#eab308')));
                        echo '<tr>';
                        echo '<td><i class="fa fa-diamond" style="color:' . $color . ';"></i> ' . $name . '</td>';
                        echo '<td class="text-center">' . $data['count'] . '</td>';
                        echo '<td class="text-right"><span class="economy-holder">' . htmlspecialchars($data['holder']) . ' (' . $data['holder_amt'] . ')</span></td>';
                        echo '</tr>';
                    }
                } else {
                    echo '<tr><td colspan="3" class="text-center">Cargando datos o Caché vacío...</td></tr>';
                }
                echo '</tbody></table>';
        echo '</div>';
    echo '</div>';
    echo '</div>';

    // 4. INFO GUILDS
    echo '<div class="col-lg-4 col-md-6">';
    echo '<div class="bottom-card">';
        echo '<div class="bottom-card-header">';
            echo '<h4 class="bottom-card-title">Info Guilds</h4>';
            echo '<p class="bottom-card-subtitle">Estadísticas útiles sobre clanes</p>';
        echo '</div>';
        echo '<div class="info-header-bar">Información</div>';
        echo '<div class="guild-stats-container">';
            
            echo '<div class="guild-stat-row">';
            echo '<span class="guild-stat-label">Total Guilds creados:</span>';
            echo '<span class="guild-stat-value">' . number_format($totalGuilds) . '</span>';
            echo '</div>';

            echo '<div class="guild-stat-row">';
            echo '<span class="guild-stat-label">Total Personajes con Guilds:</span>';
            echo '<span class="guild-stat-value">' . number_format($gMembers) . '</span>';
            echo '</div>';

            echo '<div class="guild-stat-row">';
            echo '<span class="guild-stat-label">Total Personajes sin Guilds:</span>';
            echo '<span class="guild-stat-value" style="color: #f59e0b;">' . number_format($gNoMembers) . '</span>';
            echo '</div>';

            echo '<div class="guild-stat-row">';
            echo '<span class="guild-stat-label">Más score:</span>';
            echo '<span class="guild-stat-value highlight">' . htmlspecialchars($gHighScore) . '</span>';
            echo '</div>';

            echo '<div class="guild-stat-row">';
            echo '<span class="guild-stat-label">Menos score:</span>';
            echo '<span class="guild-stat-value highlight">' . htmlspecialchars($gLowScore) . '</span>';
            echo '</div>';

            echo '<div class="guild-stat-row">';
            echo '<span class="guild-stat-label"><i class="fa fa-trophy" style="color:#f59e0b;"></i> Guild Owner Castle Siege:</span>';
            echo '<span class="guild-stat-value">' . htmlspecialchars($csOwner) . '</span>';
            echo '</div>';

            echo '<div class="guild-stat-row">';
            echo '<span class="guild-stat-label"><i class="fa fa-user" style="color:#a855f7;"></i> Guild Master CS:</span>';
            echo '<span class="guild-stat-value">' . htmlspecialchars($csMaster) . '</span>';
            echo '</div>';

        echo '</div>';
    echo '</div>';
    echo '</div>';

    // 5. TOP DONADORES
    echo '<div class="col-lg-4 col-md-6">';
    echo '<div class="bottom-card">';
        echo '<div class="bottom-card-header">';
            echo '<h4 class="bottom-card-title">Top Donadores</h4>';
            echo '<p class="bottom-card-subtitle">Ranking por monto acumulado (Histórico)</p>';
        echo '</div>';
        echo '<div class="table-responsive">';
            echo '<table class="table table-donators">';
                echo '<thead><tr><th class="text-center">#</th><th>Usuario</th><th class="text-right">Total</th></tr></thead>';
                echo '<tbody>';
                
                $txnFile = __PATH_INCLUDES__ . 'config/donation_transactions.json';
                $ranking = [];

                if (file_exists($txnFile)) {
                    $data = json_decode(file_get_contents($txnFile), true);
                    if (is_array($data)) {
                        foreach ($data as $txn) {
                            if (isset($txn['status']) && $txn['status'] == 'Completed') {
                                $user = $txn['user'];
                                $amount = (float)$txn['amount'];
                                if (!isset($ranking[$user])) $ranking[$user] = 0;
                                $ranking[$user] += $amount;
                            }
                        }
                        arsort($ranking);
                        $top5 = array_slice($ranking, 0, 5, true);
                    }
                }

                if (!empty($top5)) {
                    $i = 1;
                    foreach ($top5 as $user => $totalAmount) {
                        if ($i == 1) $rankBadge = '<div class="rank-badge rank-1">1</div>';
                        elseif ($i == 2) $rankBadge = '<div class="rank-badge rank-2">2</div>';
                        elseif ($i == 3) $rankBadge = '<div class="rank-badge rank-3">3</div>';
                        else $rankBadge = '<span style="color:#64748b; font-size:11px;">'.$i.'</span>';
                        
                        $montoFormateado = '$' . number_format($totalAmount, 2);
                        
                        echo '<tr>';
                        echo '<td class="text-center">' . $rankBadge . '</td>';
                        echo '<td><span class="donator-user">' . htmlspecialchars($user) . '</span></td>';
                        echo '<td class="text-right text-success">' . $montoFormateado . '</td>';
                        echo '</tr>';
                        $i++;
                    }
                } else {
                    echo '<tr><td colspan="3" class="text-center" style="padding:15px; color:#666;">Aún no hay donaciones registradas.</td></tr>';
                }
                echo '</tbody></table>';
        echo '</div>';
    echo '</div>';
    echo '</div>';

echo '</div>'; // Fin row bottom

// --- JAVASCRIPT ---
echo '<script>';
echo 'document.addEventListener("DOMContentLoaded", function() {';
    echo 'const ctx = document.getElementById("visitorChart").getContext("2d");';
    echo 'let gradient = ctx.createLinearGradient(0, 0, 0, 400);';
    echo 'gradient.addColorStop(0, "rgba(74, 222, 128, 0.2)");';
    echo 'gradient.addColorStop(1, "rgba(74, 222, 128, 0)");';

    echo 'const visitorChart = new Chart(ctx, {';
        echo 'type: "line",';
        echo 'data: {';
            echo 'labels: ' . $jsonDates . ',';
            echo 'datasets: [{';
                echo 'label: "Visits",';
                echo 'data: ' . $jsonCounts . ',';
                echo 'borderColor: "#4ade80",';
                echo 'backgroundColor: gradient,';
                echo 'borderWidth: 2,';
                echo 'pointBackgroundColor: "#0f1115",';
                echo 'pointBorderColor: "#4ade80",';
                echo 'pointHoverBackgroundColor: "#4ade80",';
                echo 'pointHoverBorderColor: "#fff",';
                echo 'tension: 0.4,';
                echo 'fill: true';
            echo '}]';
        echo '},';
        echo 'options: {';
            echo 'responsive: true,';
            echo 'maintainAspectRatio: false,';
            echo 'plugins: {';
                echo 'legend: { display: false },';
                echo 'tooltip: {';
                    echo 'backgroundColor: "#1e293b",';
                    echo 'titleColor: "#fff",';
                    echo 'bodyColor: "#94a3b8",';
                    echo 'borderColor: "#334155",';
                    echo 'borderWidth: 1,';
                    echo 'displayColors: false';
                echo '}';
            echo '},';
            echo 'scales: {';
                echo 'x: { grid: { color: "rgba(255, 255, 255, 0.05)", borderColor: "transparent" }, ticks: { color: "#94a3b8", font: { size: 11 } } },';
                echo 'y: { grid: { color: "rgba(255, 255, 255, 0.05)", borderColor: "transparent" }, ticks: { color: "#94a3b8", font: { size: 11 }, beginAtZero: true } }';
            echo '}';
        echo '}';
    echo '});';
echo '});';
echo '</script>';
?>