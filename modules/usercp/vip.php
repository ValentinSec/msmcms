<?php
/**
 * Módulo: MEMBRESÍA VIP (Page Version - Modal Fix)
 * Integra lógica de compra, visualización de paquetes y validaciones.
 * Solución aplicada: Move-to-body para el modal de confirmación.
 */

if (!defined("access") or !access) die();

// --- 1. LÓGICA PHP (Procesamiento de Compra) ---
$db = Connection::Database('Me_MuOnline');
$username = $_SESSION['username'];
$errorMsg = '';
$successMsg = '';

// Procesar Formulario POST (Confirmación de Compra)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['pack_id_confirm'])) {
    try {
        $packId = $_POST['pack_id_confirm'];
        $vipPacksPath = __PATH_INCLUDES__ . 'config/vip_packages.json';
        $settingsFile = __PATH_INCLUDES__ . 'config/custom_settings.json';
        
        if(!file_exists($vipPacksPath)) throw new Exception("Error: Falta configuración de paquetes.");
        
        $vipPackages = json_decode(file_get_contents($vipPacksPath), true);
        $settings = json_decode(file_get_contents($settingsFile), true);

        $selectedPack = null;
        foreach ($vipPackages as $p) {
            if ($p['id'] === $packId) { $selectedPack = $p; break; }
        }

        if (!$selectedPack) throw new Exception("El paquete seleccionado no existe.");

        $cost = (int)$selectedPack['cost'];
        $days = (int)$selectedPack['days'];
        $newLevel = (int)$selectedPack['vip_level'];
        $currency = $selectedPack['currency'];

        // Configuración de columnas
        $tblVip = $settings['table_vip'] ?? 'MEMB_INFO';
        $colVipLevel = $settings['col_vip_level'] ?? 'AccountLevel';
        $colVipDate = $settings['col_vip_expire'] ?? 'AccountExpireDate';

        // Validar nivel actual (Anti-Downgrade)
        $vipData = $db->query_fetch_single("SELECT [$colVipLevel], [$colVipDate] FROM [$tblVip] WHERE memb___id = ?", [$username]);
        $currentLevel = (int)($vipData[$colVipLevel] ?? 0);

        if ($currentLevel > $newLevel) throw new Exception("No puedes comprar este pack: tu nivel VIP actual es superior.");

        // Validar Saldo
        $coinTable = $db->query_fetch_single("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'MEMB_INFO' AND COLUMN_NAME = ?", [$currency]) ? 'MEMB_INFO' : 'CashShopData';
        $userCol = ($coinTable == 'MEMB_INFO') ? 'memb___id' : 'AccountID';
        
        $userData = $db->query_fetch_single("SELECT [$currency] FROM [$coinTable] WHERE [$userCol] = ?", [$username]);
        if (!$userData || (int)$userData[$currency] < $cost) throw new Exception("Saldo insuficiente de $currency. Necesitas $cost.");

        // Calcular Fechas
        $now = time();
        $currentExpire = 0;
        if (!empty($vipData[$colVipDate])) {
            $currentExpire = is_array($vipData[$colVipDate]) ? strtotime($vipData[$colVipDate]['date']) : strtotime($vipData[$colVipDate]);
        }
        
        // Si ya es VIP y no ha expirado, sumamos tiempo. Si no, empezamos desde ahora.
        $baseTime = ($currentExpire > $now) ? $currentExpire : $now;
        $newExpireTimestamp = $baseTime + ($days * 86400);
        $newExpireDate = date("Y-m-d H:i:s", $newExpireTimestamp);

        // Ejecutar Transacción
        $db->query("UPDATE [$coinTable] SET [$currency] = [$currency] - ? WHERE [$userCol] = ?", [$cost, $username]);
        $db->query("UPDATE [$tblVip] SET [$colVipLevel] = ?, [$colVipDate] = ? WHERE memb___id = ?", [$newLevel, $newExpireDate, $username]);

        $successMsg = "¡Membresía VIP activada correctamente hasta " . date("d/m/Y", $newExpireTimestamp) . "!";

    } catch (Exception $e) {
        $errorMsg = $e->getMessage();
    }
}

// --- 2. PREPARACIÓN DE DATOS ---
$settings = json_decode(file_get_contents(__PATH_INCLUDES__ . 'config/custom_settings.json'), true) ?: [];
$tblVip = $settings['table_vip'] ?? 'MEMB_INFO';
$colVipLevel = $settings['col_vip_level'] ?? 'AccountLevel';

// Obtener estado actual
$userVipInfo = $db->query_fetch_single("SELECT [$colVipLevel] FROM [$tblVip] WHERE memb___id = ?", [$username]);
$userLevelNow = (int)($userVipInfo[$colVipLevel] ?? 0);

// Filtrar monedas
$activeCoins = [];
for($i=1; $i<=3; $i++) {
    if (($settings["active_coin_$i"] ?? false) == true && !empty($settings["coin_{$i}_column"])) {
        $cName = trim($settings["coin_{$i}_column"]);
        $activeCoins[$cName] = $cName;
    }
}

// Cargar paquetes
$vipPacksRaw = file_exists(__PATH_INCLUDES__ . 'config/vip_packages.json') ? json_decode(file_get_contents(__PATH_INCLUDES__ . 'config/vip_packages.json'), true) : [];
$packsByCoin = [];
foreach ($vipPacksRaw as $pack) {
    if (isset($activeCoins[$pack['currency']])) {
        $packsByCoin[$pack['currency']][] = $pack;
    }
}

// Estilos visuales
$vipStyles = [
    1 => ['name' => 'BRONZE', 'color' => '#cd7f32', 'gradient' => 'linear-gradient(135deg, #cd7f32 0%, #a05a2c 100%)'],
    2 => ['name' => 'SILVER', 'color' => '#e0e0e0', 'gradient' => 'linear-gradient(135deg, #e0e0e0 0%, #bdc3c7 100%)'],
    3 => ['name' => 'GOLD',   'color' => '#FFD700', 'gradient' => 'linear-gradient(135deg, #FFD700 0%, #f39c12 100%)'],
    4 => ['name' => 'PLATINUM', 'color' => '#e5e4e2', 'gradient' => 'linear-gradient(135deg, #50c9c3 0%, #96deda 100%)']
];

// --- 3. RENDERIZADO (TODO CON ECHO) ---

echo '<style>
    .vip-module-container { background-color: #111418; border: 1px solid #2d323e; border-radius: 8px; padding: 30px; box-shadow: 0 4px 20px rgba(0,0,0,0.5); color: #fff; }
    .vip-header-title { border-bottom: 1px solid #2d323e; padding-bottom: 15px; margin-bottom: 25px; display: flex; justify-content: space-between; align-items: center; }
    .vip-header-title h2 { color: #fff; font-family: "Cinzel", serif; margin: 0; font-weight: 700; text-transform: uppercase; font-size: 24px; }
    .current-vip-status { font-size: 13px; color: #aaa; background: rgba(255,255,255,0.05); padding: 5px 15px; border-radius: 20px; border: 1px solid #333; }
    
    .vip-page-tabs { display: flex; gap: 10px; margin-bottom: 30px; border-bottom: 1px solid #2d323e; padding-bottom: 0; }
    .vip-page-tab-link { background: transparent; border: none; border-bottom: 3px solid transparent; color: #888; padding: 10px 20px; font-weight: bold; text-transform: uppercase; font-size: 14px; cursor: pointer; transition: all 0.3s; }
    .vip-page-tab-link:hover { color: #fff; }
    .vip-page-tab-link.active { color: #c8a165; border-bottom-color: #c8a165; }

    .vip-pack-card { background: #161b22; border: 1px solid #2d323e; border-radius: 8px; padding: 30px 20px; text-align: center; height: 100%; display: flex; flex-direction: column; justify-content: space-between; transition: transform 0.3s, box-shadow 0.3s; position: relative; overflow: hidden; }
    .vip-pack-card:hover { transform: translateY(-5px); box-shadow: 0 10px 30px rgba(0,0,0,0.3); border-color: #444; background: #1c2129; }
    
    .pack-badge { display: inline-block; padding: 6px 18px; border-radius: 20px; color: #fff; font-weight: 800; font-size: 12px; margin-bottom: 20px; box-shadow: 0 4px 10px rgba(0,0,0,0.3); text-shadow: 0 1px 2px rgba(0,0,0,0.5); }
    .pack-days { font-size: 38px; font-weight: 700; color: #fff; line-height: 1; margin-bottom: 5px; }
    .pack-days span { font-size: 12px; color: #6c757d; display: block; font-weight: 600; text-transform: uppercase; letter-spacing: 1px; margin-top: 5px; }
    .pack-divider { height: 1px; background: linear-gradient(90deg, transparent, #2d323e, transparent); margin: 20px 0; }
    .pack-price { font-size: 22px; color: #c8a165; font-weight: 700; margin-bottom: 20px; font-family: "Cinzel", serif; }
    .pack-price small { font-size: 12px; color: #888; font-family: "Roboto", sans-serif; font-weight: normal; }

    .btn-vip-action { width: 100%; background: transparent; border: 1px solid #c8a165; color: #c8a165; padding: 12px; border-radius: 4px; font-weight: bold; text-transform: uppercase; font-size: 13px; cursor: pointer; transition: 0.3s; }
    .btn-vip-action:hover { background: #c8a165; color: #000; box-shadow: 0 0 15px rgba(200, 161, 101, 0.4); }
    .btn-vip-disabled { border-color: #444; color: #555; cursor: not-allowed; background: rgba(255,255,255,0.02); }
    .btn-vip-disabled:hover { box-shadow: none; background: rgba(255,255,255,0.02); color: #555; }

    /* Fix Modal Z-Index Conflict */
    #modalVipConfirmPage { z-index: 100000 !important; }
    .modal-backdrop { z-index: 99990 !important; }
</style>';

echo '<div class="vip-module-container">';
    
    echo '<div class="vip-header-title">';
        echo '<h2><i class="fa fa-crown text-warning me-2"></i> Membresía VIP</h2>';
        echo '<div class="current-vip-status">';
            $statusLabel = $userLevelNow > 0 ? "VIP $userLevelNow" : "Free User";
            echo 'Tu Nivel Actual: <span class="text-white fw-bold">' . $statusLabel . '</span>';
        echo '</div>';
    echo '</div>';

    if (!empty($errorMsg)) {
        echo '<div class="alert alert-danger border-0 d-flex align-items-center mb-4" role="alert" style="background: rgba(220, 53, 69, 0.1); border-left: 4px solid #dc3545 !important; color: #fff;">';
            echo '<i class="fa fa-exclamation-circle me-2"></i>';
            echo '<div>' . $errorMsg . '</div>';
        echo '</div>';
    }

    if (!empty($successMsg)) {
        echo '<div class="alert alert-success border-0 d-flex align-items-center mb-4" role="alert" style="background: rgba(25, 135, 84, 0.1); border-left: 4px solid #198754 !important; color: #fff;">';
            echo '<i class="fa fa-check-circle me-2"></i>';
            echo '<div>' . $successMsg . '</div>';
        echo '</div>';
    }

    if(empty($packsByCoin)) {
        echo '<div class="text-center py-5">';
            echo '<div class="mb-3"><i class="fa fa-box-open fa-3x text-muted opacity-25"></i></div>';
            echo '<h5 class="text-muted">No hay paquetes VIP configurados.</h5>';
            echo '<small class="text-muted opacity-50">Contacta al administrador.</small>';
        echo '</div>';
    } else {

        // TABS
        echo '<div class="vip-page-tabs">';
            $isFirst = true;
            foreach($packsByCoin as $coinName => $packs) {
                $activeClass = $isFirst ? 'active' : '';
                $tabId = 'vip_tab_' . preg_replace('/[^a-zA-Z0-9]/', '', $coinName);
                echo '<button class="vip-page-tab-link '.$activeClass.'" onclick="openVipPageTab(event, \''.$tabId.'\')">'.$coinName.'</button>';
                $isFirst = false;
            }
        echo '</div>';

        // LISTA DE PAQUETES
        echo '<div id="vipPageContent">';
            $isFirst = true;
            foreach($packsByCoin as $coinName => $packs) {
                $disp = $isFirst ? 'block' : 'none';
                $tabId = 'vip_tab_' . preg_replace('/[^a-zA-Z0-9]/', '', $coinName);
                
                echo '<div id="'.$tabId.'" class="vip-content-pane" style="display: '.$disp.'; animation: fadeIn 0.5s;">';
                    echo '<div class="row g-4">';
                        foreach($packs as $pack) {
                            $pLvl = (int)$pack['vip_level'];
                            $st = $vipStyles[$pLvl] ?? $vipStyles[1];
                            
                            $isDowngrade = ($userLevelNow > $pLvl);
                            $opacity = $isDowngrade ? '0.5' : '1';
                            
                            echo '<div class="col-xl-3 col-md-4 col-sm-6">';
                                echo '<div class="vip-pack-card" style="border-top: 3px solid '.$st['color'].'; opacity: '.$opacity.'">';
                                    
                                    echo '<div>';
                                        echo '<span class="pack-badge" style="background: '.$st['gradient'].';">'.$st['name'].'</span>';
                                        echo '<div class="pack-days">'.$pack['days'].'<span>DÍAS</span></div>';
                                    echo '</div>';
                                    
                                    echo '<div class="pack-divider"></div>';
                                    
                                    echo '<div>';
                                        echo '<div class="pack-price">'.number_format($pack['cost'], 0, ',', '.').' <small>'.$pack['currency'].'</small></div>';
                                        
                                        if ($isDowngrade) {
                                            echo '<button type="button" class="btn-vip-action btn-vip-disabled" disabled>Nivel Inferior</button>';
                                        } else {
                                            echo '<button type="button" class="btn-vip-action" onclick="askVipConfirmation(\''.$pack['id'].'\', \''.$st['name'].'\', \''.$pack['days'].'\', \''.number_format($pack['cost']).'\', \''.$pack['currency'].'\')">ADQUIRIR</button>';
                                        }
                                    echo '</div>';
                                    
                                echo '</div>';
                            echo '</div>';
                        }
                    echo '</div>';
                echo '</div>';
                $isFirst = false;
            }
        echo '</div>';
    }
echo '</div>';

// --- MODAL DE CONFIRMACIÓN ---
echo '<div class="modal fade" id="modalVipConfirmPage" tabindex="-1" aria-hidden="true">';
    echo '<div class="modal-dialog modal-sm modal-dialog-centered">';
        echo '<div class="modal-content text-center" style="background: #151515; border: 1px solid #333; color: #fff;">';
            echo '<div class="modal-body py-4">';
                echo '<i class="fa fa-info-circle text-warning fa-3x mb-3"></i>';
                echo '<h5 class="fw-bold text-uppercase">Confirmar</h5>';
                echo '<p class="text-muted small px-2 mb-3" id="vipConfirmMsg"></p>';
                
                echo '<form method="POST">';
                    echo '<input type="hidden" name="pack_id_confirm" id="input_vip_id">';
                    echo '<div class="d-flex justify-content-center gap-2">';
                        echo '<button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>';
                        echo '<button type="submit" class="btn btn-warning btn-sm fw-bold">Comprar</button>';
                    echo '</div>';
                echo '</form>';
            echo '</div>';
        echo '</div>';
    echo '</div>';
echo '</div>';

// --- JAVASCRIPT INCRUSTADO ---
echo '<script>
function openVipPageTab(evt, tabId) {
    var panes = document.getElementsByClassName("vip-content-pane");
    for (var i = 0; i < panes.length; i++) { panes[i].style.display = "none"; }
    
    var links = document.getElementsByClassName("vip-page-tab-link");
    for (var i = 0; i < links.length; i++) { links[i].className = links[i].className.replace(" active", ""); }
    
    document.getElementById(tabId).style.display = "block";
    if(evt) evt.currentTarget.className += " active";
}

function askVipConfirmation(id, level, days, cost, coin) {
    document.getElementById("input_vip_id").value = id;
    document.getElementById("vipConfirmMsg").innerHTML = "Vas a adquirir <b>VIP " + level + "</b><br>Duración: <b>" + days + " días</b><br>Costo: <b class=\'text-warning\'>" + cost + " " + coin + "</b>";
    
    var modalEl = document.getElementById("modalVipConfirmPage");
    
    // [CRITICAL FIX] Mover el modal al body para evitar conflictos de Z-Index/Stacking
    if (modalEl && modalEl.parentNode !== document.body) {
        document.body.appendChild(modalEl);
    }

    if (typeof bootstrap !== "undefined") {
        var myModal = new bootstrap.Modal(modalEl);
        myModal.show();
    } else {
        $("#modalVipConfirmPage").modal("show");
    }
}
</script>';
?>