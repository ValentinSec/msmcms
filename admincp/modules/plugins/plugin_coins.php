<?php
/**
 * Donation Manager (Coins) - PREPARED FOR OBFUSCATION
 * Pure PHP Version (Concatenation Safe)
 */

// --- CONFIGURACIÓN Y CARGA DE DATOS ---
$json_file = __PATH_INCLUDES__ . 'config/donation_config.json';

// Verificación de permisos de escritura
if (!is_writable($json_file)) { @chmod($json_file, 0777); }

$config_all = file_exists($json_file) ? json_decode(file_get_contents($json_file), true) : [];
if (!is_array($config_all)) $config_all = [];
if (!isset($config_all['methods'])) $config_all['methods'] = [];

// Función auxiliar
function saveJsonConfig($file, $data) {
    if(file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) === false) {
        die("<div style='background:#f44336; color:#fff; padding:15px; text-align:center;'>Error: No se pudo escribir en $file. Verifica permisos (CHMOD 777).</div>");
    }
}

// --- LOGICA 1: AGREGAR PACK ---
if (isset($_POST['add_pack_modal'])) {
    $tid = $_POST['m_tid'];
    if (!isset($config_all['methods'][$tid]['packages'])) {
        $config_all['methods'][$tid]['packages'] = [];
    }
    
    $coins = (int)$_POST['m_coins'];
    $price = (float)$_POST['m_price'];
    $selectedImage = isset($_POST['m_image_selected']) ? $_POST['m_image_selected'] : '';
    $coinType = isset($_POST['m_coin_type']) ? $_POST['m_coin_type'] : ''; 

    if (!empty($selectedImage)) {
        $finalImage = $selectedImage;
    } else {
        if ($coins <= 1000) { $finalImage = 'Pack1.png'; }
        elseif ($coins <= 3000) { $finalImage = 'Pack2.png'; }
        elseif ($coins <= 5000) { $finalImage = 'Pack3.png'; }
        elseif ($coins <= 10000) { $finalImage = 'Pack4.png'; }
        elseif ($coins <= 25000) { $finalImage = 'Pack5.png'; }
        elseif ($coins <= 50000) { $finalImage = 'Pack6.png'; }
        elseif ($coins <= 100000) { $finalImage = 'Pack7.png'; }
        else { $finalImage = 'Pack8.png'; }
    }
    
    $newPack = [
        'id' => uniqid(),
        'coins' => $coins,
        'price_usd' => $price,
        'image' => $finalImage,
        'badge' => isset($_POST['m_badge']) ? $_POST['m_badge'] : '',
        'bonus' => '',
        'coin_type' => $coinType 
    ];
    
    $config_all['methods'][$tid]['packages'][] = $newPack;
    saveJsonConfig($json_file, $config_all);
    header("Location: index.php?module=plugin_coins&success=1");
    exit;
}

// --- LOGICA 2: BORRAR PACK ---
if (isset($_GET['delete_pack']) && isset($_GET['method'])) {
    $tid = $_GET['method'];
    $id = $_GET['delete_pack'];
    
    if (isset($config_all['methods'][$tid]['packages'])) {
        $config_all['methods'][$tid]['packages'] = array_values(array_filter($config_all['methods'][$tid]['packages'], function($item) use ($id) {
            return (string)$item['id'] !== (string)$id;
        }));
        saveJsonConfig($json_file, $config_all);
    }
    header("Location: index.php?module=plugin_coins&success=1");
    exit;
}

// --- LOGICA 3: ORDENAR PACKS ---
if (isset($_GET['move']) && isset($_GET['id']) && isset($_GET['method'])) {
    $tid = $_GET['method'];
    $id = $_GET['id'];
    $direction = $_GET['move'];
    
    if (isset($config_all['methods'][$tid]['packages'])) {
        $packages = &$config_all['methods'][$tid]['packages'];
        $index = -1;
        foreach ($packages as $k => $item) {
            if ($item['id'] == $id) { $index = $k; break; }
        }
        
        if ($index !== -1) {
            if ($direction == 'up' && $index > 0) {
                $tmp = $packages[$index];
                $packages[$index] = $packages[$index - 1];
                $packages[$index - 1] = $tmp;
            } elseif ($direction == 'down' && $index < count($packages) - 1) {
                $tmp = $packages[$index];
                $packages[$index] = $packages[$index + 1];
                $packages[$index + 1] = $tmp;
            }
            saveJsonConfig($json_file, $config_all);
        }
    }
    header("Location: index.php?module=plugin_coins&success=1");
    exit;
}

// --- LOGICA 4: GUARDAR CONFIGURACIÓN PASARELAS ---
if (isset($_POST['save_payment_config'])) {
    $config_all['module_active'] = 1;
    $keys = ['mp', 'paypal', 'binance', 'yape', 'bank'];
    
    foreach ($keys as $key) {
        $existing_packages = isset($config_all['methods'][$key]['packages']) ? $config_all['methods'][$key]['packages'] : [];
        $activeName = ($key == 'paypal') ? 'pp_active' : (($key == 'binance') ? 'bnc_active' : $key . '_active');
        
        $config_all['methods'][$key]['active'] = isset($_POST[$activeName]) ? 1 : 0;
        
        $nombres = [
            'mp' => 'MercadoPago', 
            'paypal' => 'PayPal', 
            'binance' => 'Binance Pay', 
            'yape' => 'Yape', 
            'bank' => 'Transferencia'
        ];
        $config_all['methods'][$key]['name'] = isset($nombres[$key]) ? $nombres[$key] : ucfirst($key);
        $config_all['methods'][$key]['symbol'] = ($key == 'yape') ? 'S/' : '$';
        
        if($key == 'mp') {
            $config_all['methods'][$key]['token'] = isset($_POST['mp_token']) ? $_POST['mp_token'] : '';
            $config_all['methods'][$key]['currency'] = isset($_POST['mp_currency']) ? $_POST['mp_currency'] : 'ARS';
        } elseif($key == 'paypal') {
            $config_all['methods'][$key]['email'] = isset($_POST['pp_email']) ? $_POST['pp_email'] : '';
            $config_all['methods'][$key]['currency'] = isset($_POST['pp_currency']) ? $_POST['pp_currency'] : 'USD';
        } elseif($key == 'binance') {
            $config_all['methods'][$key]['api_key'] = isset($_POST['bnc_key']) ? $_POST['bnc_key'] : '';
            $config_all['methods'][$key]['currency'] = isset($_POST['bnc_currency']) ? $_POST['bnc_currency'] : 'USDT';
        } elseif($key == 'yape') {
            $config_all['methods'][$key]['number'] = isset($_POST['yape_number']) ? $_POST['yape_number'] : '';
            $config_all['methods'][$key]['holder'] = isset($_POST['yape_name']) ? $_POST['yape_name'] : '';
            $config_all['methods'][$key]['currency'] = 'PEN';
        } elseif($key == 'bank') {
            $config_all['methods'][$key]['details'] = isset($_POST['bank_details']) ? $_POST['bank_details'] : '';
            $config_all['methods'][$key]['currency'] = 'ARS';
        }
        
        $config_all['methods'][$key]['packages'] = $existing_packages;
    }

    saveJsonConfig($json_file, $config_all);
    header("Location: index.php?module=plugin_coins&success=1");
    exit;
}

$methods = isset($config_all['methods']) ? $config_all['methods'] : [];
$mp = isset($methods['mp']) ? $methods['mp'] : [];
$pp = isset($methods['paypal']) ? $methods['paypal'] : [];
$bnc = isset($methods['binance']) ? $methods['binance'] : [];
$yape = isset($methods['yape']) ? $methods['yape'] : [];
$bank = isset($methods['bank']) ? $methods['bank'] : [];
$packImgPath = '../img/packs/';

// --- LÓGICA CRÍTICA: OBTENER MONEDAS ACTIVAS ---
$customCfgPath = __PATH_INCLUDES__ . 'config/custom_settings.json';
$customCfg = file_exists($customCfgPath) ? json_decode(file_get_contents($customCfgPath), true) : [];

$activeCoinTypesLower = []; 
$defaultCoinName = ''; 

for($i=1; $i<=3; $i++) {
    // 1. Obtener Nombre de la moneda
    $col = isset($customCfg["col_coin_$i"]) ? trim($customCfg["col_coin_$i"]) : '';
    
    // 2. Verificar si está activa
    $isActive = false;
    if(isset($customCfg["active_coin_$i"])) {
        $val = $customCfg["active_coin_$i"];
        if($val === "on" || $val === "1" || $val === 1 || $val === true) {
            $isActive = true;
        }
    }
    
    if($i == 1) $defaultCoinName = $col;

    if($isActive && !empty($col)) {
        $activeCoinTypesLower[] = strtolower(trim($col));
    }
}

// --- RENDERIZADO HTML (PHP PURO) ---

echo '<div class="row pc-wrap">';
    
    // COLUMNA IZQUIERDA (CONFIGURACIÓN)
    echo '<div class="col-md-4">';
        
        if(isset($_GET['success'])) {
            echo '<div class="alert-success-custom">Cambios guardados correctamente.</div>';
        }

        echo '<div class="settings-section">';
            echo '<form action="" method="POST">';
                
                // MercadoPago
                echo '<h3 class="settings-title">Configuración MercadoPago</h3>';
                echo '<div class="form-group switch-container">';
                    echo '<label class="switch">';
                        echo '<input type="checkbox" name="mp_active" ' . ((isset($mp['active']) && $mp['active']) ? 'checked' : '') . '>';
                        echo '<span class="slider"></span>';
                    echo '</label>';
                    echo '<span class="switch-label">Activar MercadoPago</span>';
                echo '</div>';
                echo '<div class="form-group">';
                    echo '<label class="settings-label">TOKEN DE ACCESO</label>';
                    echo '<input type="text" name="mp_token" class="form-control-dark" value="' . htmlspecialchars(isset($mp['token']) ? $mp['token'] : '') . '" placeholder="Access Token">';
                echo '</div>';
                echo '<div class="form-group">';
                    echo '<label class="settings-label">Moneda MP</label>';
                    echo '<select name="mp_currency" class="form-control-dark">';
                        echo '<option value="ARS" ' . ((isset($mp['currency']) && $mp['currency'] == "ARS") ? "selected" : "") . '>ARS</option>';
                        echo '<option value="USD" ' . ((isset($mp['currency']) && $mp['currency'] == "USD") ? "selected" : "") . '>USD</option>';
                    echo '</select>';
                echo '</div>';

                echo '<hr style="border-color: #2b2b40; margin: 30px 0;">';

                // PayPal
                echo '<h3 class="settings-title">Configuración PayPal</h3>';
                echo '<div class="form-group switch-container">';
                    echo '<label class="switch">';
                        echo '<input type="checkbox" name="pp_active" ' . ((isset($pp['active']) && $pp['active']) ? 'checked' : '') . '>';
                        echo '<span class="slider"></span>';
                    echo '</label>';
                    echo '<span class="switch-label">Activar PayPal</span>';
                echo '</div>';
                echo '<div class="form-group">';
                    echo '<label class="settings-label">EMAIL BUSINESS</label>';
                    echo '<input type="email" name="pp_email" class="form-control-dark" value="' . htmlspecialchars(isset($pp['email']) ? $pp['email'] : '') . '" placeholder="Email">';
                echo '</div>';

                echo '<hr style="border-color: #2b2b40; margin: 30px 0;">';

                // Binance
                echo '<h3 class="settings-title">Configuración Binance</h3>';
                echo '<div class="form-group switch-container">';
                    echo '<label class="switch">';
                        echo '<input type="checkbox" name="bnc_active" ' . ((isset($bnc['active']) && $bnc['active']) ? 'checked' : '') . '>';
                        echo '<span class="slider"></span>';
                    echo '</label>';
                    echo '<span class="switch-label">Activar Binance</span>';
                echo '</div>';
                echo '<div class="form-group">';
                    echo '<label class="settings-label">API KEY</label>';
                    echo '<input type="text" name="bnc_key" class="form-control-dark" value="' . htmlspecialchars(isset($bnc['api_key']) ? $bnc['api_key'] : '') . '" placeholder="Key">';
                echo '</div>';

                echo '<hr style="border-color: #2b2b40; margin: 30px 0;">';

                // Yape
                echo '<h3 class="settings-title">Configuración Yape</h3>';
                echo '<div class="form-group switch-container">';
                    echo '<label class="switch">';
                        echo '<input type="checkbox" name="yape_active" ' . ((isset($yape['active']) && $yape['active']) ? 'checked' : '') . '>';
                        echo '<span class="slider"></span>';
                    echo '</label>';
                    echo '<span class="switch-label">Activar Yape</span>';
                echo '</div>';
                echo '<div class="form-group">';
                    echo '<label class="settings-label">CELULAR</label>';
                    echo '<input type="text" name="yape_number" class="form-control-dark" value="' . htmlspecialchars(isset($yape['number']) ? $yape['number'] : '') . '" placeholder="Nro Celular">';
                echo '</div>';
                echo '<div class="form-group">';
                    echo '<label class="settings-label">TITULAR</label>';
                    echo '<input type="text" name="yape_name" class="form-control-dark" value="' . htmlspecialchars(isset($yape['holder']) ? $yape['holder'] : '') . '" placeholder="Nombre Titular">';
                echo '</div>';

                echo '<hr style="border-color: #2b2b40; margin: 30px 0;">';

                // Transferencia
                echo '<h3 class="settings-title">Configuración Transferencia</h3>';
                echo '<div class="form-group switch-container">';
                    echo '<label class="switch">';
                        echo '<input type="checkbox" name="bank_active" ' . ((isset($bank['active']) && $bank['active']) ? 'checked' : '') . '>';
                        echo '<span class="slider"></span>';
                    echo '</label>';
                    echo '<span class="switch-label">Activar Transferencia</span>';
                echo '</div>';
                echo '<div class="form-group">';
                    echo '<label class="settings-label">DATOS BANCARIOS</label>';
                    echo '<textarea name="bank_details" class="form-control-dark" rows="3">' . htmlspecialchars(isset($bank['details']) ? $bank['details'] : '') . '</textarea>';
                echo '</div>';

                echo '<div class="form-group" style="margin-top:20px;">';
                    echo '<button type="submit" name="save_payment_config" class="btn-save-green">Guardar Configuración</button>';
                echo '</div>';
            echo '</form>';
        echo '</div>';
    echo '</div>';

    // COLUMNA DERECHA (TABS Y PACKS)
    echo '<div class="col-md-8">';
        
        // Navigation Tabs
        echo '<ul class="nav nav-tabs nav-tabs-custom" id="paymentTabs">';
            $first = true;
            foreach($methods as $tid => $tData) {
                if((isset($tData['active']) ? $tData['active'] : 0) == 1) {
                    $activeClass = $first ? 'active' : '';
                    echo '<li class="' . $activeClass . '">';
                    echo '<a href="#panel-' . $tid . '" data-toggle="tab" class="nav-link-tab">' . strtoupper($tData['name']) . '</a>';
                    echo '</li>';
                    $first = false;
                }
            }
        echo '</ul>';

        // Tab Content
        echo '<div class="tab-content tab-content-custom">';
            $first = true;
            foreach($methods as $tid => $tData) {
                if((isset($tData['active']) ? $tData['active'] : 0) == 1) {
                    $activeClass = $first ? 'active' : '';
                    echo '<div class="tab-pane ' . $activeClass . '" id="panel-' . $tid . '">';
                        
                        echo '<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">';
                            echo '<div class="currency-badge">MONEDA: ' . $tData['currency'] . ' (' . $tData['symbol'] . ')</div>';
                            echo '<button type="button" class="btn btn-success btn-sm" onclick="openWizardModal(\'' . $tid . '\', \'' . $tData['name'] . '\')" style="background:#2ecc71; border:none;">+ AGREGAR PACK</button>';
                        echo '</div>';

                        echo '<table class="table-dark-custom" style="width:100%;">';
                            echo '<thead>';
                                echo '<tr style="border-bottom: 2px solid #2d323e; color: #8a8d93; font-size: 11px;">';
                                    echo '<th>COINS</th><th>COSTO</th><th style="text-align: right;">MENÚ</th>';
                                echo '</tr>';
                            echo '</thead>';
                            echo '<tbody>';
                                
                                $localPacks = isset($tData['packages']) ? $tData['packages'] : [];
                                $visiblePacks = 0;
                                foreach($localPacks as $p) {
                                    $pCoinType = isset($p['coin_type']) ? trim($p['coin_type']) : '';
                                    if (empty($pCoinType)) $pCoinType = $defaultCoinName;

                                    // Filtro de Monedas Activas
                                    if (empty($pCoinType) || !in_array(strtolower(trim($pCoinType)), $activeCoinTypesLower)) {
                                        continue; 
                                    }
                                    
                                    $visiblePacks++;
                                    $packImg = isset($p['image']) ? $p['image'] : 'Pack1.png';
                                    $displayCoin = !empty($p['coin_type']) ? $p['coin_type'] : 'Default';

                                    echo '<tr style="background: #242830; border-bottom: 4px solid #1c1f26;">';
                                        echo '<td style="padding: 15px;">';
                                            echo '<div style="display: flex; align-items: center;">';
                                                echo '<img src="' . $packImgPath . $packImg . '" width="30" style="margin-right: 10px;">';
                                                echo '<div>';
                                                    echo '<div style="font-weight:bold;">' . number_format($p['coins']) . ' ' . $p['coin_type'] . '</div>';
                                                    echo '<span style="font-size:9px; color:#555; text-transform:uppercase;">' . $displayCoin . '</span>';
                                                echo '</div>';
                                            echo '</div>';
                                        echo '</td>';
                                        echo '<td style="padding: 15px; color: #f39c12; font-weight: bold;">' . $tData['symbol'] . ' ' . number_format($p['price_usd'], 2) . '</td>';
                                        echo '<td style="padding: 15px; text-align: right;">';
                                            echo '<a href="index.php?module=plugin_coins&move=up&method=' . $tid . '&id=' . $p['id'] . '" class="btn-action btn-nav"><i class="fa fa-chevron-up"></i></a>';
                                            echo '<a href="index.php?module=plugin_coins&move=down&method=' . $tid . '&id=' . $p['id'] . '" class="btn-action btn-nav"><i class="fa fa-chevron-down"></i></a>';
                                            echo '<a href="javascript:void(0);" class="btn-action btn-delete" onclick="openDeleteModal(\'index.php?module=plugin_coins&method=' . $tid . '&delete_pack=' . $p['id'] . '\')"><i class="fa fa-trash"></i></a>';
                                        echo '</td>';
                                    echo '</tr>';
                                }
                                
                                if($visiblePacks == 0) {
                                    echo '<tr><td colspan="3" class="text-center p-4" style="color:#666;">No hay paquetes visibles (Verifica las monedas activas en Configuración).</td></tr>';
                                }

                            echo '</tbody>';
                        echo '</table>';

                    echo '</div>';
                    $first = false;
                }
            }
        echo '</div>'; // End Tab Content
    echo '</div>'; // End Col Right

echo '</div>'; // End Row

// --- MODAL WIZARD ---
echo '<div id="modal-add-pack" class="custom-modal">';
    echo '<div class="modal-content" style="max-width: 500px;">';
        echo '<form method="post" action="" id="form-add-pack-wizard">';
            echo '<input type="hidden" name="add_pack_modal" value="1">';
            echo '<input type="hidden" name="m_tid" id="m_tid_add">';
            echo '<input type="hidden" name="m_coin_type" id="selected_coin_column">';

            // Paso 1: Selección Moneda
            echo '<div class="modal-slide" id="slide-1">';
                echo '<h4 style="color:#f39c12; margin-bottom:20px; text-align:center;">1. Elige el tipo de Moneda</h4>';
                echo '<div class="currency-selector-grid" style="display:grid; grid-template-columns: 1fr 1fr; gap:10px;">';
                    
                    $foundActive = false;
                    for($i=1; $i<=3; $i++) {
                        $colName = isset($customCfg["col_coin_$i"]) ? trim($customCfg["col_coin_$i"]) : '';
                        $isActive = isset($customCfg["active_coin_$i"]) && ($customCfg["active_coin_$i"] === "on" || $customCfg["active_coin_$i"] == 1 || $customCfg["active_coin_$i"] === true);
                        
                        if($isActive && !empty($colName)) {
                            $foundActive = true;
                            echo '<div class="currency-option" style="background:#2d323e; padding:15px; border-radius:8px; cursor:pointer; text-align:center; border:1px solid #3e4452; transition:0.2s;" ';
                            echo 'onclick="nextSlide(2, \'' . $colName . '\', ' . $i . ')" ';
                            echo 'onmouseover="this.style.borderColor=\'#f39c12\'; this.style.background=\'#373d4a\'" ';
                            echo 'onmouseout="this.style.borderColor=\'#3e4452\'; this.style.background=\'#2d323e\'">';
                                echo '<i class="fa fa-coins" style="font-size:24px; color:#f39c12; margin-bottom:10px; display:block;"></i>';
                                echo '<span style="color:#fff; font-weight:bold; display:block;">' . $colName . '</span>';
                                echo '<small style="color:#8a8d93;">Moneda ' . $i . '</small>';
                            echo '</div>';
                        }
                    }
                    if(!$foundActive) {
                        echo "<p style='grid-column: span 2; color:#e74c3c; text-align:center;'>No hay monedas activas en Configuración.</p>";
                    }
                echo '</div>';
                echo '<div style="margin-top:20px; text-align:center;">';
                    echo '<button type="button" class="btn-cancel" onclick="closeModal(\'modal-add-pack\')" style="background:transparent; border:1px solid #555; color:#fff; padding:5px 15px; border-radius:4px;">Cancelar</button>';
                echo '</div>';
            echo '</div>';

            // Paso 2: Detalles
            echo '<div class="modal-slide" id="slide-2" style="display:none;">';
                echo '<h4 style="color:#f39c12; margin-bottom:20px; text-align:center;">2. Configurar <span id="display-coin-name" style="color:#fff;"></span></h4>';
                echo '<div class="row">';
                    echo '<div class="col-md-6 form-group">';
                        echo '<label>Cantidad</label>';
                        echo '<input type="number" name="m_coins" class="form-control-dark" required placeholder="Ej: 1000">';
                    echo '</div>';
                    echo '<div class="col-md-6 form-group">';
                        echo '<label>Precio (USD)</label>';
                        echo '<input type="text" name="m_price" class="form-control-dark" required placeholder="Ej: 10.00">';
                    echo '</div>';
                echo '</div>';
                
                echo '<div class="form-group" style="margin-top:15px;">';
                    echo '<label>Imagen del Pack</label>';
                    echo '<div class="image-selector-container">';
                        $searchPath = '../img/packs/*.png';
                        $packImages = glob($searchPath);
                        if (empty($packImages)) {
                            $rootPath = dirname(__PATH_INCLUDES__); 
                            $packImages = glob($rootPath . '/img/packs/*.png');
                        }
                    
                        if ($packImages) {
                            foreach($packImages as $imgPath) {
                                $imgName = basename($imgPath);
                                echo '<img src="'.$packImgPath.$imgName.'" class="pack-img-option" onclick="selectImage(this, \''.$imgName.'\')" title="'.$imgName.'">';
                            }
                        } else {
                            echo '<small style="color:#aaa; padding:10px;">No se encontraron imágenes .png en /img/packs/</small>';
                        }
                        echo '<input type="hidden" name="m_image_selected" id="m_image_selected">';
                    echo '</div>';
                echo '</div>';

                echo '<div class="form-group" style="margin-top:15px;">';
                    echo '<label>Etiqueta (Opcional)</label>';
                    echo '<input type="text" name="m_badge" class="form-control-dark" placeholder="Ej: OFERTA">';
                echo '</div>';

                echo '<div class="modal-actions" style="display: flex; justify-content: space-between; margin-top: 20px;">';
                    echo '<button type="button" class="btn-cancel" onclick="prevSlide(1)" style="background:#555; color:#fff; border:none; padding:8px 15px; border-radius:4px;">&larr; Volver</button>';
                    echo '<button type="submit" class="btn-save" style="background:#f39c12; color:#fff; border:none; padding:8px 15px; border-radius:4px; font-weight:bold;">Guardar Pack</button>';
                echo '</div>';
            echo '</div>';

            // Paso 3: Spinner
            echo '<div class="modal-slide" id="slide-3" style="display:none; text-align:center; padding:40px;">';
                echo '<i class="fa fa-circle-notch fa-spin" style="font-size:40px; color:#f39c12;"></i>';
                echo '<p style="color:#fff; margin-top:15px;">Guardando configuración...</p>';
            echo '</div>';

        echo '</form>';
    echo '</div>';
echo '</div>';

// --- MODAL DELETE ---
echo '<div id="customDeleteModal" class="modal-overlay">';
    echo '<div class="modal-box">';
        echo '<div class="modal-header"><i class="fa fa-exclamation-triangle"></i> Confirmar Eliminación</div>';
        echo '<div class="modal-body">';
            echo '<p>¿Estás seguro de que deseas eliminar este pack?</p>';
            echo '<span class="modal-subtext">Esta acción no se puede deshacer.</span>';
        echo '</div>';
        echo '<div class="modal-footer">';
            echo '<button class="btn-modal-cancel" onclick="closeDeleteModal()">Cancelar</button>';
            echo '<a href="#" id="btnConfirmDelete" class="btn-modal-confirm">Eliminar</a>';
        echo '</div>';
    echo '</div>';
echo '</div>';

// --- SCRIPTS ---
echo '<script>
function openWizardModal(tid, name) {
    var inputTid = document.getElementById("m_tid_add");
    if(inputTid) inputTid.value = tid;

    document.querySelectorAll(".modal-slide").forEach(s => s.style.display = "none");
    document.getElementById("slide-1").style.display = "block";

    var modal = document.getElementById("modal-add-pack");
    if(modal) modal.style.display = "flex";
}

function closeModal(modalId) {
    document.getElementById(modalId).style.display = "none";
}

function nextSlide(step, coinName, index) {
    if(step === 2) {
        document.getElementById("selected_coin_column").value = coinName;
        var displaySpan = document.getElementById("display-coin-name");
        if(displaySpan) displaySpan.innerText = coinName;
    }
    document.querySelectorAll(".modal-slide").forEach(s => s.style.display = "none");
    document.getElementById("slide-" + step).style.display = "block";
}

function prevSlide(step) {
    document.querySelectorAll(".modal-slide").forEach(s => s.style.display = "none");
    document.getElementById("slide-" + step).style.display = "block";
}

function selectImage(img, filename) {
    document.querySelectorAll(".pack-img-option").forEach(i => i.classList.remove("selected"));
    img.classList.add("selected");
    document.getElementById("m_image_selected").value = filename;
}

var formWizard = document.getElementById("form-add-pack-wizard");
if(formWizard) {
    formWizard.onsubmit = function() {
        nextSlide(3);
        return true;
    };
}

function openDeleteModal(url) {
    document.getElementById("btnConfirmDelete").href = url;
    document.getElementById("customDeleteModal").style.display = "flex";
}

function closeDeleteModal() {
    document.getElementById("customDeleteModal").style.display = "none";
}

window.onclick = function(event) {
    var modal = document.getElementById("customDeleteModal");
    var modalPack = document.getElementById("modal-add-pack");
    
    if (event.target == modal) {
        modal.style.display = "none";
    }
    if (event.target == modalPack) {
        modalPack.style.display = "none";
    }
}

document.addEventListener("DOMContentLoaded", function(){
    if(typeof bootstrap !== "undefined" && bootstrap.Tooltip) {
        var tooltipTriggerList = [].slice.call(document.querySelectorAll("[data-bs-toggle=\'tooltip\']"));
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    }
});
</script>';

// --- CSS ---
echo '<style>
    /* --- Columna de configuracion --- */
    .pc-wrap .settings-section hr { border-color: var(--ad-line, #1f2733) !important; margin: 24px 0 !important; }
    #page-wrapper .pc-wrap .settings-section .btn-save-green { float: none !important; width: 100%; }
    .pc-wrap > [class*="col-"] { margin-bottom: 20px; }

    /* --- Pestanas de metodos: pasan a dos renglones si no entran --- */
    #page-wrapper .pc-wrap .nav-tabs-custom { display: flex; flex-wrap: wrap; gap: 0 4px; margin: 0; padding: 4px 12px 0; background: var(--ad-surface, #0f1115); border: 1px solid var(--ad-line, #1f2733); border-radius: 10px 10px 0 0; }
    #page-wrapper .pc-wrap .nav-tabs-custom::before,
    #page-wrapper .pc-wrap .nav-tabs-custom::after { display: none; }
    #page-wrapper .pc-wrap .nav-tabs-custom > li { float: none; margin: 0 0 -1px; }
    #page-wrapper .pc-wrap .nav-tabs-custom > li > a { margin: 0; padding: 12px 12px; border: 0 !important; border-bottom: 2px solid transparent !important; border-radius: 0; background: transparent !important; color: var(--ad-muted, #8b95a7); font-size: 12px; font-weight: 700; letter-spacing: .3px; white-space: nowrap; outline: none; box-shadow: none; }
    #page-wrapper .pc-wrap .nav-tabs-custom > li > a:hover { color: #fff; }
    #page-wrapper .pc-wrap .nav-tabs-custom > li.active > a,
    #page-wrapper .pc-wrap .nav-tabs-custom > li.active > a:hover,
    #page-wrapper .pc-wrap .nav-tabs-custom > li.active > a:focus { color: #fff !important; border-bottom-color: var(--ad-accent, #10b981) !important; background: transparent !important; }

    /* --- Contenido: un solo recuadro junto con las pestanas --- */
    #page-wrapper .pc-wrap .tab-content-custom { background: var(--ad-surface, #0f1115); border: 1px solid var(--ad-line, #1f2733); border-top: 0; border-radius: 0 0 10px 10px; padding: 20px; }
    .pc-wrap .tab-pane > div:first-child { flex-wrap: wrap; gap: 10px; margin-bottom: 12px !important; }
    .pc-wrap .currency-badge { margin: 0; padding: 6px 12px; border-radius: 999px; background: rgba(245, 158, 11, 0.1); color: #f59e0b; border: 1px solid rgba(245, 158, 11, 0.3); font-size: 11px; font-weight: 700; }
    #page-wrapper .pc-wrap .tab-pane .btn-success { display: inline-flex; align-items: center; height: 34px; padding: 0 14px; font-size: 12px; font-weight: 700; border-radius: 8px; }

    /* --- Lista de packs: filas separadas por una linea, sin recuadros internos --- */
    .pc-wrap .table-dark-custom { width: 100%; border-collapse: collapse; border-spacing: 0; }
    .pc-wrap .table-dark-custom thead tr { border-bottom: 1px solid var(--ad-line-strong, #2a3545) !important; }
    .pc-wrap .table-dark-custom th { color: var(--ad-muted, #8b95a7); font-size: 11px; text-transform: uppercase; padding: 10px 8px; border-bottom: 0; }
    .pc-wrap .table-dark-custom tbody tr { background: transparent !important; border-bottom: 1px solid var(--ad-line, #1f2733) !important; }
    .pc-wrap .table-dark-custom tbody tr:last-child { border-bottom: 0 !important; }
    .pc-wrap .table-dark-custom td { background: transparent !important; padding: 12px 8px !important; border-radius: 0 !important; color: var(--ad-text, #e5e7eb); vertical-align: middle; }
    .pc-wrap .table-dark-custom td:nth-child(2) { color: #f59e0b !important; white-space: nowrap; }
    .pc-wrap .table-dark-custom td:last-child { white-space: nowrap; }

    /* --- Botones de cada pack --- */
    .pc-wrap .btn-action { display: inline-flex; align-items: center; justify-content: center; width: 32px; height: 32px; padding: 0; margin-left: 4px; border-radius: 8px; font-size: 12px; text-decoration: none; cursor: pointer; transition: 0.2s; }
    .pc-wrap .btn-nav { background: transparent; border: 1px solid var(--ad-line-strong, #2a3545); color: var(--ad-text, #e5e7eb); }
    .pc-wrap .btn-nav:hover { border-color: var(--ad-info, #3b82f6); color: #fff; }
    .pc-wrap .btn-delete { background: var(--ad-danger, #ef4444); border: 1px solid var(--ad-danger, #ef4444); color: #fff; }
    .pc-wrap .btn-delete:hover { filter: brightness(1.1); color: #fff; }

    /* --- Ventana "Agregar pack" --- */
    .custom-modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; padding: 16px; background: rgba(0,0,0,0.7); z-index: 9999; justify-content: center; align-items: center; }
    #modal-add-pack .modal-content { background: #0f1115; padding: 26px; border-radius: 10px; border: 1px solid #2a3545; width: 100%; max-width: 500px; box-shadow: 0 20px 60px rgba(0,0,0,0.6); position: relative; }
    #modal-add-pack h4 { color: #fff !important; font-size: 16px; font-weight: 700; }
    #modal-add-pack label { color: #8b95a7; font-size: 12px; font-weight: 600; }
    #modal-add-pack .currency-option { background: #0b0d12 !important; border: 1px solid #2a3545 !important; }
    #modal-add-pack .currency-option:hover { border-color: #10b981 !important; background: rgba(16, 185, 129, 0.08) !important; }
    #modal-add-pack .currency-option i { color: #10b981 !important; }
    #modal-add-pack .fa-coins:before { content: "\f0d6"; }
    #modal-add-pack .fa-circle-notch:before { content: "\f1ce"; }
    #modal-add-pack .fa-circle-notch { color: #10b981 !important; }
    #modal-add-pack .image-selector-container { display: flex; flex-wrap: wrap; gap: 5px; background: #0b0d12; padding: 10px; border-radius: 8px; border: 1px solid #2a3545; }
    #modal-add-pack .pack-img-option { width: 50px; height: 50px; border: 2px solid transparent; border-radius: 6px; cursor: pointer; margin: 2px; }
    #modal-add-pack .pack-img-option.selected { border-color: #10b981; background: rgba(16, 185, 129, 0.1); }
    #modal-add-pack .btn-cancel { background: transparent !important; border: 1px solid #2a3545 !important; color: #e5e7eb !important; height: 38px; padding: 0 16px !important; border-radius: 8px !important; cursor: pointer; }
    #modal-add-pack .btn-save { background: #10b981 !important; height: 38px; padding: 0 18px !important; border-radius: 8px !important; cursor: pointer; }
    #modal-add-pack .btn-save:hover { background: #0ea371 !important; }

    /* --- Ventana de borrado --- */
    .modal-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.7); z-index: 9999; justify-content: center; align-items: center; }
    #customDeleteModal .modal-box { background: #0f1115; border: 1px solid #2a3545; border-radius: 10px; width: 400px; max-width: 90%; box-shadow: 0 20px 60px rgba(0,0,0,0.6); animation: fadeInModal 0.2s ease-out; overflow: hidden; }
    #customDeleteModal .modal-header { padding: 15px 20px; border-bottom: 1px solid #1f2733; font-weight: bold; color: #fff; font-size: 16px; display: flex; align-items: center; gap: 10px; }
    #customDeleteModal .modal-header i { color: #ef4444; }
    #customDeleteModal .modal-body { padding: 20px; color: #8b95a7; text-align: center; }
    #customDeleteModal .modal-subtext { display: block; font-size: 12px; color: #5b6677; margin-top: 5px; }
    #customDeleteModal .modal-footer { padding: 15px 20px; background: transparent; display: flex; justify-content: flex-end; gap: 10px; border-top: 1px solid #1f2733; }
    .btn-modal-cancel { background: transparent; border: 1px solid #2a3545; color: #e5e7eb; height: 38px; padding: 0 16px; border-radius: 8px; cursor: pointer; font-size: 13px; }
    .btn-modal-cancel:hover { border-color: #8b95a7; }
    .btn-modal-confirm { display: inline-flex; align-items: center; background: #ef4444; border: none; color: #fff; height: 38px; padding: 0 16px; border-radius: 8px; text-decoration: none; font-size: 13px; font-weight: 600; }
    .btn-modal-confirm:hover { background: #dc2626; color: #fff; text-decoration: none; }
    @keyframes fadeInModal { from { opacity: 0; transform: translateY(-20px); } to { opacity: 1; transform: translateY(0); } }
</style>';
?>