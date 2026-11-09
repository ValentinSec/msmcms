<?php
/**
 * Plugins Manager Module - PREPARED FOR OBFUSCATION
 * Pure PHP Version (Concatenation Safe)
 */

// 1. DEFINICIÓN DE RUTAS
$basePath = __DIR__ . '/../../../includes/config/';
$realBasePath = realpath($basePath);
if ($realBasePath === false) { $realBasePath = $basePath; } else { $realBasePath .= '/'; }

$fileSettings = $realBasePath . 'custom_settings.json';
$fileDonation = $realBasePath . 'donation_config.json';

// HELPERS
function clean_response($status, $debugInfo = '') {
    while (ob_get_level() > 0) ob_end_clean();
    if($status === 'success') exit('success');
    else exit("ERROR: $debugInfo");
}

function save_json($file, $data) {
    if (!file_exists($file)) {
        $dir = dirname($file);
        if (!is_writable($dir)) return "Permiso denegado en la carpeta CONFIG. Ruta: $dir";
    } else {
        if (!is_writable($file)) return "Permiso denegado en el archivo. Ruta: $file";
    }
    $result = file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT));
    if ($result === false) return "Fallo al escribir en: $file";
    return true;
}

// --- HANDLERS (AJAX) ---

// 0. WEBSHOP
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_action']) && $_POST['ajax_action'] === 'toggle_webshop_module') {
    $currentData = file_exists($fileSettings) ? json_decode(file_get_contents($fileSettings), true) : [];
    if(!is_array($currentData)) $currentData = [];
    $currentData['module_webshop_active'] = $_POST['status']; 
    $res = save_json($fileSettings, $currentData);
    if($res === true) clean_response('success');
    else clean_response('error', $res);
}

// 1. DONACIONES
if (isset($_GET['action']) && $_GET['action'] == 'toggle_coins') {
    $currentData = file_exists($fileDonation) ? json_decode(file_get_contents($fileDonation), true) : [];
    if(!is_array($currentData)) $currentData = [];
    $currentData['module_active'] = (int)$_GET['status'];
    $res = save_json($fileDonation, $currentData);
    if($res === true) clean_response('success');
    else clean_response('error', $res);
}

// 2. VIP
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_action']) && $_POST['ajax_action'] === 'toggle_vip_module') {
    $currentData = file_exists($fileSettings) ? json_decode(file_get_contents($fileSettings), true) : [];
    if(!is_array($currentData)) $currentData = [];
    $currentData['module_vip_active'] = $_POST['status']; 
    $res = save_json($fileSettings, $currentData);
    if($res === true) clean_response('success');
    else clean_response('error', $res);
}

// 3. MY CHAR
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_action']) && $_POST['ajax_action'] === 'toggle_mychar_module') {
    $currentData = file_exists($fileSettings) ? json_decode(file_get_contents($fileSettings), true) : [];
    if(!is_array($currentData)) $currentData = [];
    $currentData['module_mychar_active'] = $_POST['status'];
    $res = save_json($fileSettings, $currentData);
    if($res === true) clean_response('success');
    else clean_response('error', $res);
}

// 4. VOTOS
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_action']) && $_POST['ajax_action'] === 'toggle_votes_module') {
    $currentData = file_exists($fileSettings) ? json_decode(file_get_contents($fileSettings), true) : [];
    if(!is_array($currentData)) $currentData = [];
    $currentData['module_votes_active'] = $_POST['status']; 
    $res = save_json($fileSettings, $currentData);
    if($res === true) clean_response('success');
    else clean_response('error', $res);
}

// 5. STREAM
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_action']) && $_POST['ajax_action'] === 'toggle_stream_module') {
    $fileStream = __DIR__ . '/../../../includes/config/stream_config.json';
    if (!file_exists($fileStream)) {
        $baseData = ['module_active' => 0, 'streamers' => []];
        file_put_contents($fileStream, json_encode($baseData, JSON_PRETTY_PRINT));
    }
    $currentData = json_decode(file_get_contents($fileStream), true);
    if(!is_array($currentData)) $currentData = [];
    $currentData['module_active'] = $_POST['status']; 
    if(file_put_contents($fileStream, json_encode($currentData, JSON_PRETTY_PRINT)) !== false) {
        clean_response('success');
    } else {
        clean_response('Error: Permisos de escritura en stream_config.json');
    }
}

// 6. BANNED
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_action']) && $_POST['ajax_action'] === 'toggle_banned_module') {
    $currentData = file_exists($fileSettings) ? json_decode(file_get_contents($fileSettings), true) : [];
    if(!is_array($currentData)) $currentData = [];
    $currentData['module_banned_active'] = $_POST['status']; 
    $res = save_json($fileSettings, $currentData);
    if($res === true) clean_response('success');
    else clean_response('error', $res);
}

// 7. CASINO GOBLIN
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_action']) && $_POST['ajax_action'] === 'toggle_casino_module') {
    $currentData = file_exists($fileSettings) ? json_decode(file_get_contents($fileSettings), true) : [];
    if(!is_array($currentData)) $currentData = [];
    $currentData['module_casino_active'] = $_POST['status']; 
    $res = save_json($fileSettings, $currentData);
    if($res === true) clean_response('success');
    else clean_response('error', $res);
}

// 8. BLACK MARKET
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_action']) && $_POST['ajax_action'] === 'toggle_bm_module') {
    $fileBM = __DIR__ . '/../../../includes/config/black_market_config.json';
    if (!file_exists($fileBM)) {
        $base = ['module_active' => 'off', 'tax_percent' => 10, 'allowed_currency' => 'WCoinC', 'market_title' => 'Mercado Negro'];
        file_put_contents($fileBM, json_encode($base, JSON_PRETTY_PRINT));
    }
    
    $currentData = json_decode(file_get_contents($fileBM), true);
    if(!is_array($currentData)) $currentData = [];
    
    $currentData['module_active'] = $_POST['status'];
    
    if(file_put_contents($fileBM, json_encode($currentData, JSON_PRETTY_PRINT)) !== false) {
        clean_response('success');
    } else {
        clean_response('Error: Permisos en black_market_config.json');
    }
}

// --- PUENTE DE CARGA (SUB-PLUGINS) ---
if (isset($_GET['load_plugin'])) {
    $pluginName = preg_replace('/[^a-zA-Z0-9_]/', '', $_GET['load_plugin']); 
    $pathA = __DIR__ . '/' . $pluginName . '.php';             
    $pathB = __DIR__ . '/' . $pluginName . '/' . $pluginName . '.php';
    $pathC = __DIR__ . '/../' . $pluginName . '.php';

    if (file_exists($pathA)) { include($pathA); return; } 
    elseif (file_exists($pathB)) { include($pathB); return; } 
    elseif (file_exists($pathC)) { include($pathC); return; }
    else { echo '<div class="alert alert-danger">Error: No se encuentra el archivo <b>' . $pluginName . '.php</b></div>'; return; }
}

// --- RENDERIZADO TABLA PRINCIPAL (HTML PURO PHP) ---

echo '<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">';

echo '<div class="container-fluid">';
    echo '<div class="row">';
        echo '<div class="col-lg-12">';
            echo '<div class="settings-section" style="padding: 0;">'; 
                echo '<div style="padding: 25px 25px 10px 25px;">';
                    echo '<h3 class="settings-title" style="border:none; margin:0;">Mis Plugins</h3>';
                echo '</div>';

                echo '<div class="table-responsive">';
                    echo '<table class="table-plugins">';
                        echo '<thead>';
                            echo '<tr>';
                                echo '<th style="text-align: left; padding-left: 20px;">Nombre</th>';
                                echo '<th>Administrar</th>';
                                echo '<th>On / Off</th>';
                            echo '</tr>';
                        echo '</thead>';
                        echo '<tbody>';
                            
                            // 1. WEBSHOP
                            $wsData = file_exists($fileSettings) ? json_decode(file_get_contents($fileSettings), true) : [];
                            $wsStatus = (isset($wsData['module_webshop_active']) && $wsData['module_webshop_active'] == 'on') ? 'checked' : '';
                            
                            echo '<tr>';
                                echo '<td style="padding-left: 20px; font-weight: 600; font-size: 14px; color: #fff;">';
                                    echo '<i class="fa fa-shopping-cart" style="color: #ec4899; font-size: 20px; margin-right: 12px; vertical-align: middle;"></i> WebShop (Item Mall)';
                                echo '</td>';
                                echo '<td><a href="index.php?module=my_plugins&load_plugin=webshop" class="btn-config-outline"><i class="fa fa-laptop"></i> Configurar</a></td>';
                                echo '<td>';
                                    echo '<label class="switch" style="margin:0;">';
                                        echo '<input type="checkbox" ' . $wsStatus . ' onchange="updateWebShopModule(this.checked)">';
                                        echo '<span class="slider"></span>';
                                    echo '</label>';
                                echo '</td>';
                            echo '</tr>';

                            // 2. DONACIONES
                            $dData = file_exists($fileDonation) ? json_decode(file_get_contents($fileDonation), true) : [];
                            $dStatus = (isset($dData['module_active']) && $dData['module_active'] == 1) ? 'checked' : '';

                            echo '<tr>';
                                echo '<td style="padding-left: 20px; font-weight: 600; font-size: 14px; color: #fff;">';
                                    echo '<i class="fa fa-credit-card" style="color: #ff9800; font-size: 20px; margin-right: 12px; vertical-align: middle;"></i> Donaciones (Coins)';
                                echo '</td>';
                                echo '<td><a href="index.php?module=plugin_coins" class="btn-config-outline"><i class="fa fa-laptop"></i> Configurar</a></td>';
                                echo '<td>';
                                    echo '<label class="switch" style="margin:0;">';
                                        echo '<input type="checkbox" ' . $dStatus . ' onchange="updateDonationGlobal(this.checked)">';
                                        echo '<span class="slider"></span>';
                                    echo '</label>';
                                echo '</td>';
                            echo '</tr>';

                            // 3. VIP
                            $vStatus = (isset($wsData['module_vip_active']) && $wsData['module_vip_active'] == 'on') ? 'checked' : '';

                            echo '<tr>';
                                echo '<td style="padding-left: 20px; font-weight: 600; font-size: 14px; color: #fff;">';
                                    echo '<i class="fas fa-crown" style="color: #FFD700; font-size: 20px; margin-right: 12px; vertical-align: middle;"></i> Membresías VIP';
                                echo '</td>';
                                echo '<td><a href="index.php?module=vip" class="btn-config-outline"><i class="fa fa-laptop"></i> Configurar</a></td>';
                                echo '<td>';
                                    echo '<label class="switch" style="margin:0;">';
                                        echo '<input type="checkbox" ' . $vStatus . ' onchange="updateVipModule(this.checked)">';
                                        echo '<span class="slider"></span>';
                                    echo '</label>';
                                echo '</td>';
                            echo '</tr>';

                            // 4. MY CHAR
                            $cStatus = (isset($wsData['module_mychar_active']) && $wsData['module_mychar_active'] == 'on') ? 'checked' : '';

                            echo '<tr>';
                                echo '<td style="padding-left: 20px; font-weight: 600; font-size: 14px; color: #fff;">';
                                    echo '<i class="fas fa-users" style="color: #3b82f6; font-size: 20px; margin-right: 12px; vertical-align: middle;"></i> Mis Personajes';
                                echo '</td>';
                                echo '<td><a href="index.php?module=my_plugins&load_plugin=mychar" class="btn-config-outline"><i class="fa fa-laptop"></i> Configurar</a></td>';
                                echo '<td>';
                                    echo '<label class="switch" style="margin:0;">';
                                        echo '<input type="checkbox" ' . $cStatus . ' onchange="updateMyCharModule(this.checked)">';
                                        echo '<span class="slider"></span>';
                                    echo '</label>';
                                echo '</td>';
                            echo '</tr>';

                            // 5. VOTOS
                            $voteStatus = (isset($wsData['module_votes_active']) && $wsData['module_votes_active'] == 'on') ? 'checked' : '';

                            echo '<tr>';
                                echo '<td style="padding-left: 20px; font-weight: 600; font-size: 14px; color: #fff;">';
                                    echo '<i class="fas fa-thumbs-up" style="color: #10b981; font-size: 20px; margin-right: 12px; vertical-align: middle;"></i> Sistema de Votos';
                                echo '</td>';
                                echo '<td><a href="index.php?module=my_plugins&load_plugin=votes" class="btn-config-outline"><i class="fa fa-laptop"></i> Configurar</a></td>';
                                echo '<td>';
                                    echo '<label class="switch" style="margin:0;">';
                                        echo '<input type="checkbox" ' . $voteStatus . ' onchange="updateVotesModule(this.checked)">';
                                        echo '<span class="slider"></span>';
                                    echo '</label>';
                                echo '</td>';
                            echo '</tr>';

                            // 6. STREAM
                            $fileStream = __DIR__ . '/../../../includes/config/stream_config.json';
                            $sStream = file_exists($fileStream) ? json_decode(file_get_contents($fileStream), true) : [];
                            $stStatus = (isset($sStream['module_active']) && $sStream['module_active'] == 'on') ? 'checked' : '';

                            echo '<tr>';
                                echo '<td style="padding-left: 20px; font-weight: 600; font-size: 14px; color: #fff;">';
                                    echo '<i class="fas fa-video" style="color: #a855f7; font-size: 20px; margin-right: 12px; vertical-align: middle;"></i> Streamers';
                                echo '</td>';
                                echo '<td><a href="index.php?module=my_plugins&load_plugin=stream" class="btn-config-outline"><i class="fa fa-laptop"></i> Configurar</a></td>';
                                echo '<td>';
                                    echo '<label class="switch" style="margin:0;">';
                                        echo '<input type="checkbox" ' . $stStatus . ' onchange="updateStreamModule(this.checked)">';
                                        echo '<span class="slider"></span>';
                                    echo '</label>';
                                echo '</td>';
                            echo '</tr>';

                            // 7. BANNED
                            $banStatus = (isset($wsData['module_banned_active']) && $wsData['module_banned_active'] == 'on') ? 'checked' : '';

                            echo '<tr>';
                                echo '<td style="padding-left: 20px; font-weight: 600; font-size: 14px; color: #fff;">';
                                    echo '<i class="fas fa-ban" style="color: #e74c3c; font-size: 20px; margin-right: 12px; vertical-align: middle;"></i> Sistema de Banneos';
                                echo '</td>';
                                echo '<td><a href="index.php?module=my_plugins&load_plugin=banned" class="btn-config-outline"><i class="fa fa-laptop"></i> Configurar</a></td>';
                                echo '<td>';
                                    echo '<label class="switch" style="margin:0;">';
                                        echo '<input type="checkbox" ' . $banStatus . ' onchange="updateBannedModule(this.checked)">';
                                        echo '<span class="slider"></span>';
                                    echo '</label>';
                                echo '</td>';
                            echo '</tr>';

                            // 8. CASINO
                            $casinoStatus = (isset($wsData['module_casino_active']) && $wsData['module_casino_active'] == 'on') ? 'checked' : '';

                            echo '<tr>';
                                echo '<td style="padding-left: 20px; font-weight: 600; font-size: 14px; color: #fff; vertical-align: middle;">';
                                    echo '<i class="fas fa-dice" style="color: #2ecc71; font-size: 20px; margin-right: 12px; vertical-align: middle;"></i> Casino Goblin';
                                echo '</td>';
                                echo '<td style="vertical-align: middle;"><a href="index.php?module=my_plugins&load_plugin=casino" class="btn-config-outline"><i class="fa fa-laptop"></i> Configurar</a></td>';
                                echo '<td style="vertical-align: middle;">';
                                    echo '<label class="switch" style="margin:0;">';
                                        echo '<input type="checkbox" ' . $casinoStatus . ' onchange="updateCasinoModule(this.checked)">';
                                        echo '<span class="slider"></span>';
                                    echo '</label>';
                                echo '</td>';
                            echo '</tr>';

                            // 9. BLACK MARKET
                            $fileBM = __DIR__ . '/../../../includes/config/black_market_config.json';
                            $bmData = file_exists($fileBM) ? json_decode(file_get_contents($fileBM), true) : [];
                            $bmStatus = (isset($bmData['module_active']) && $bmData['module_active'] == 'on') ? 'checked' : '';

                            echo '<tr>';
                                echo '<td style="padding-left: 20px; font-weight: 600; font-size: 14px; color: #fff; vertical-align: middle;">';
                                    echo '<i class="fas fa-user-secret" style="color: #9b59b6; font-size: 20px; margin-right: 12px; vertical-align: middle;"></i> Black Market (Mercado)';
                                echo '</td>';
                                echo '<td style="vertical-align: middle;"><a href="index.php?module=my_plugins&load_plugin=black_market" class="btn-config-outline"><i class="fa fa-laptop"></i> Configurar</a></td>';
                                echo '<td style="vertical-align: middle;">';
                                    echo '<label class="switch" style="margin:0;">';
                                        echo '<input type="checkbox" ' . $bmStatus . ' onchange="updateBlackMarketModule(this.checked)">';
                                        echo '<span class="slider"></span>';
                                    echo '</label>';
                                echo '</td>';
                            echo '</tr>';

                        echo '</tbody>';
                    echo '</table>';
                echo '</div>';
            echo '</div>';
        echo '</div>';
    echo '</div>';
echo '</div>';

// JavaScript
echo '<script>
function handleResponse(response, name) {
    if(response.trim() === "success") {
        console.log(name + " actualizado correctamente.");
    } else {
        alert("ERROR: " + response);
        location.reload();
    }
}

function updateWebShopModule(isChecked) {
    var status = isChecked ? "on" : "off";
    var formData = new FormData();
    formData.append("ajax_action", "toggle_webshop_module");
    formData.append("status", status);
    fetch(window.location.href, { method: "POST", body: formData })
    .then(r => r.text())
    .then(data => handleResponse(data, "WebShop"));
}

function updateDonationGlobal(isChecked) {
    const active = isChecked ? 1 : 0;
    fetch("index.php?module=my_plugins&action=toggle_coins&status=" + active)
    .then(r => r.text())
    .then(data => handleResponse(data, "Donaciones"));
}

function updateVipModule(isChecked) {
    var status = isChecked ? "on" : "off";
    var formData = new FormData();
    formData.append("ajax_action", "toggle_vip_module");
    formData.append("status", status);
    fetch(window.location.href, { method: "POST", body: formData })
    .then(r => r.text())
    .then(data => handleResponse(data, "VIP"));
}

function updateMyCharModule(isChecked) {
    var status = isChecked ? "on" : "off";
    var formData = new FormData();
    formData.append("ajax_action", "toggle_mychar_module");
    formData.append("status", status);
    fetch(window.location.href, { method: "POST", body: formData })
    .then(r => r.text())
    .then(data => handleResponse(data, "MyChar"));
}

function updateVotesModule(isChecked) {
    var status = isChecked ? "on" : "off";
    var formData = new FormData();
    formData.append("ajax_action", "toggle_votes_module");
    formData.append("status", status);
    fetch(window.location.href, { method: "POST", body: formData })
    .then(r => r.text())
    .then(data => handleResponse(data, "Votos"));
}

function updateStreamModule(isChecked) {
    var status = isChecked ? "on" : "off";
    var formData = new FormData();
    formData.append("ajax_action", "toggle_stream_module");
    formData.append("status", status);
    fetch(window.location.href, { method: "POST", body: formData })
    .then(r => r.text())
    .then(data => handleResponse(data, "Streams"));
}

function updateBannedModule(isChecked) {
    var status = isChecked ? "on" : "off";
    var formData = new FormData();
    formData.append("ajax_action", "toggle_banned_module");
    formData.append("status", status);
    fetch(window.location.href, { method: "POST", body: formData })
    .then(r => r.text())
    .then(data => handleResponse(data, "Sistema Banned"));
}

function updateCasinoModule(isChecked) {
    var status = isChecked ? "on" : "off";
    var formData = new FormData();
    formData.append("ajax_action", "toggle_casino_module");
    formData.append("status", status);
    fetch(window.location.href, { method: "POST", body: formData })
    .then(r => r.text())
    .then(data => handleResponse(data, "Casino Goblin"));
}

function updateBlackMarketModule(isChecked) {
    var status = isChecked ? "on" : "off";
    var formData = new FormData();
    formData.append("ajax_action", "toggle_bm_module");
    formData.append("status", status);
    fetch(window.location.href, { method: "POST", body: formData })
    .then(r => r.text())
    .then(data => handleResponse(data, "Black Market"));
}
</script>';
?>