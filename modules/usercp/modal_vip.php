<?php
/**
 * Módulo: ADQUIRIR MEMBRESÍA VIP (Modal) - IN-GAME CURRENCY & SQL LOGIC
 * Fix: Protección contra Downgrade de Nivel VIP y Tabs JS
 */

if (!defined("access") or !access) die();

$db = Connection::Database('Me_MuOnline');
$username = $_SESSION['username'];
$errorMsg = '';
$successMsg = '';

// --- 1. PROCESAR COMPRA (LÓGICA SQL) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['pack_id_confirm'])) {
    try {
        $packId = $_POST['pack_id_confirm'];
        
        $vipPacksPath = __PATH_INCLUDES__ . 'config/vip_packages.json';
        $settingsFile = __PATH_INCLUDES__ . 'config/custom_settings.json';
        
        $vipPackages = json_decode(file_get_contents($vipPacksPath), true);
        $settings = json_decode(file_get_contents($settingsFile), true);

        $selectedPack = null;
        foreach ($vipPackages as $p) {
            if (isset($p['id']) && $p['id'] === $packId) { $selectedPack = $p; break; }
        }

        if (!$selectedPack) throw new Exception("El paquete no existe.");

        $cost     = isset($selectedPack['cost']) ? (int)$selectedPack['cost'] : 0;
        $days     = isset($selectedPack['days']) ? (int)$selectedPack['days'] : 0;
        $newLevel = isset($selectedPack['vip_level']) ? (int)$selectedPack['vip_level'] : 1;
        $currency = isset($selectedPack['currency']) ? $selectedPack['currency'] : '';

        if (empty($currency)) throw new Exception("Error: Paquete sin moneda configurada.");

        $tblVip      = $settings['table_vip'] ?? 'MEMB_INFO';
        $colVipLevel = $settings['col_vip_level'] ?? 'AccountLevel';
        $colVipDate  = $settings['col_vip_expire'] ?? 'AccountExpireDate';

        // 1. Obtener estado VIP actual del usuario
        $vipData = $db->query_fetch_single("SELECT [$colVipLevel], [$colVipDate] FROM [$tblVip] WHERE memb___id = ?", [$username]);
        $currentLevel = (int)($vipData[$colVipLevel] ?? 0);

        // --- VALIDACIÓN DE DOWNGRADE ---
        if ($currentLevel > $newLevel) {
            throw new Exception("No puedes adquirir este pack porque tu nivel VIP actual es superior.");
        }

        // 2. Verificar saldo
        $coinTable = $db->query_fetch_single("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'MEMB_INFO' AND COLUMN_NAME = ?", [$currency]) ? 'MEMB_INFO' : 'CashShopData';
        $userCol = ($coinTable == 'MEMB_INFO') ? 'memb___id' : 'AccountID';

        $userData = $db->query_fetch_single("SELECT [$currency] FROM [$coinTable] WHERE [$userCol] = ?", [$username]);
        if (!$userData || (int)$userData[$currency] < $cost) throw new Exception("Saldo insuficiente de $currency.");

        // 3. Cálculo de Tiempo Acumulativo
        $now = time();
        $currentExpire = 0;
        if (!empty($vipData[$colVipDate])) {
            $currentExpire = is_array($vipData[$colVipDate]) ? strtotime($vipData[$colVipDate]['date']) : strtotime($vipData[$colVipDate]);
        }

        $baseTime = ($currentExpire > $now) ? $currentExpire : $now;
        $newExpireTimestamp = $baseTime + ($days * 86400);
        $newExpireDate = date("Y-m-d H:i:s", $newExpireTimestamp);

        // 4. Ejecutar Transacción
        $db->query("UPDATE [$coinTable] SET [$currency] = [$currency] - ? WHERE [$userCol] = ?", [$cost, $username]);
        $db->query("UPDATE [$tblVip] SET [$colVipLevel] = ?, [$colVipDate] = ? WHERE memb___id = ?", [$newLevel, $newExpireDate, $username]);

        $successMsg = "¡Compra exitosa! VIP Nivel $newLevel activado hasta " . date("d/m/Y H:i", $newExpireTimestamp);

    } catch (Exception $e) {
        $errorMsg = $e->getMessage();
    }
}

// --- 2. CARGA DE DATOS PARA LA VISTA ---
$settingsFile = __PATH_INCLUDES__ . 'config/custom_settings.json';
$settings = json_decode(file_get_contents($settingsFile), true) ?: [];

// Obtener nivel actual para pasar al JS
$tblVip      = $settings['table_vip'] ?? 'MEMB_INFO';
$colVipLevel = $settings['col_vip_level'] ?? 'AccountLevel';
$userVipInfo = $db->query_fetch_single("SELECT [$colVipLevel] FROM [$tblVip] WHERE memb___id = ?", [$username]);
$userLevelNow = (int)($userVipInfo[$colVipLevel] ?? 0);

$activeCoins = [];
for($i=1; $i<=3; $i++) {
    if (($settings["active_coin_$i"] ?? false) == true && !empty($settings["coin_{$i}_column"])) {
        $cName = trim($settings["coin_{$i}_column"]);
        $activeCoins[$cName] = $cName;
    }
}

$vipPacksRaw = json_decode(file_get_contents(__PATH_INCLUDES__ . 'config/vip_packages.json'), true) ?: [];
$packsByCoin = [];
if (is_array($vipPacksRaw)) {
    foreach ($vipPacksRaw as $pack) {
        $pCur = isset($pack['currency']) ? $pack['currency'] : '';
        if ($pCur !== '' && isset($activeCoins[$pCur])) {
            $packsByCoin[$pCur][] = $pack;
        }
    }
}

$vipStyles = [
    1 => ['name' => 'BRONZE', 'color' => '#cd7f32', 'gradient' => 'linear-gradient(135deg, #cd7f32 0%, #a05a2c 100%)'],
    2 => ['name' => 'SILVER', 'color' => '#e0e0e0', 'gradient' => 'linear-gradient(135deg, #e0e0e0 0%, #bdc3c7 100%)'],
    3 => ['name' => 'GOLD',   'color' => '#FFD700', 'gradient' => 'linear-gradient(135deg, #FFD700 0%, #f39c12 100%)']
];

// --- 3. RENDERIZADO ---

echo '<div class="modal fade" id="modalBuyVip" tabindex="-1" role="dialog" aria-hidden="true">';
    echo '<div class="modal-dialog modal-lg modal-dialog-centered" role="document">';
        echo '<div class="modal-content modal-vip-content">';
            
            echo '<div class="modal-header modal-vip-header">';
                echo '<h5 class="modal-title fw-bold" style="color: #fff;"><i class="fa fa-crown text-warning me-2"></i> ADQUIRIR MEMBRESÍA VIP</h5>';
                echo '<button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" data-dismiss="modal" aria-label="Close"></button>';
            echo '</div>';

            echo '<div class="modal-body modal-vip-body">';
                
                if (!empty($errorMsg)) echo '<div class="alert alert-danger" style="margin: 15px;">'.$errorMsg.'</div>';
                if (!empty($successMsg)) echo '<div class="alert alert-success" style="margin: 15px;">'.$successMsg.'</div>';

                if(empty($packsByCoin)) {
                    echo '<div class="text-center py-5"><h5 class="text-muted">No hay paquetes disponibles.</h5></div>';
                } else {
                    echo '<div class="vip-tabs" id="vipTabsContainer">';
                        $isFirst = true;
                        foreach($packsByCoin as $coinName => $packs) {
                            $activeClass = $isFirst ? 'active' : '';
                            $tabId = 'tab_' . preg_replace('/[^a-zA-Z0-9]/', '', $coinName);
                            // FIX: Añadimos type="button" y enviamos "this" en vez de "event"
                            echo '<button type="button" class="vip-tab-link '.$activeClass.'" onclick="openVipTab(this, \''.$tabId.'\')">'.$coinName.'</button>';
                            $isFirst = false;
                        }
                    echo '</div>';

                    echo '<div id="vipPanesContainer" style="padding: 20px;">';
                        $isFirst = true;
                        foreach($packsByCoin as $coinName => $packs) {
                            $activeClass = $isFirst ? 'active' : '';
                            $tabId = 'tab_' . preg_replace('/[^a-zA-Z0-9]/', '', $coinName);
                            $disp = $isFirst ? 'block' : 'none';

                            echo '<div id="'.$tabId.'" class="vip-tab-pane-content" style="display: '.$disp.';">';
                                echo '<div class="row g-3">';
                                    foreach($packs as $pack) {
                                        $pLvl = isset($pack['vip_level']) ? (int)$pack['vip_level'] : 1;
                                        $st = $vipStyles[$pLvl] ?? $vipStyles[1];
                                        $pCurRender = isset($pack['currency']) ? htmlspecialchars($pack['currency']) : '';
                                        $pDays = isset($pack['days']) ? $pack['days'] : 0;
                                        $pCost = isset($pack['cost']) ? $pack['cost'] : 0;
                                        $pId = isset($pack['id']) ? $pack['id'] : '';
                                        
                                        // Verificar si es downgrade
                                        $isDowngrade = ($userLevelNow > $pLvl);

                                        echo '<div class="col-md-4 col-sm-6 mb-4">';
                                            echo '<div class="vip-pack-card" style="border-top: 3px solid '.$st['color'].'; opacity: '.($isDowngrade ? '0.6' : '1').';">';
                                                echo '<div><span class="pack-badge" style="background: '.$st['gradient'].';">'.$st['name'].'</span>';
                                                echo '<div class="pack-days">'.$pDays.'<span>Días</span></div></div>';
                                                echo '<div class="pack-price">'.number_format($pCost, 0, ',', '.').' <small>'.$pCurRender.'</small></div>';
                                                
                                                if ($isDowngrade) {
                                                    echo '<button type="button" class="btn btn-secondary btn-sm disabled w-100" style="cursor:not-allowed;">Nivel inferior</button>';
                                                } else {
                                                    echo '<button type="button" class="btn-pack-buy" onclick="confirmVipOrder(\''.$pId.'\', \''.$st['name'].'\', \''.$pDays.'\', \''.number_format($pCost).'\', \''.$pCurRender.'\')">Comprar</button>';
                                                }
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
        echo '</div>';
    echo '</div>';
echo '</div>';

// --- MODAL DE CONFIRMACIÓN ---
echo '<div class="modal fade" id="modalConfirmVip" tabindex="-1" role="dialog" aria-hidden="true" style="z-index: 106000;">';
    echo '<div class="modal-dialog modal-sm modal-dialog-centered" role="document">';
        echo '<div class="modal-content text-center" style="background: #151515; border: 1px solid #333; border-radius: 10px;">';
            echo '<div class="modal-body py-4">';
                echo '<i class="fa fa-question-circle text-warning fa-3x mb-3"></i>';
                echo '<h5 class="text-white fw-bold">¿Confirmar Compra?</h5>';
                echo '<p class="text-muted px-2" id="confirmVipText" style="font-size: 14px;"></p>';
                
                echo '<form method="POST">';
                    echo '<input type="hidden" name="pack_id_confirm" id="input_pack_id_confirm">';
                    echo '<div class="d-flex justify-content-center gap-2 mt-4">';
                        echo '<button type="button" class="btn btn-secondary btn-sm px-4" data-bs-dismiss="modal" data-dismiss="modal">Cancelar</button>';
                        echo '<button type="submit" class="btn btn-warning btn-sm px-4 fw-bold">Sí, Comprar</button>';
                    echo '</div>';
                echo '</form>';
            echo '</div>';
        echo '</div>';
    echo '</div>';
echo '</div>';

// --- JAVASCRIPT CORREGIDO ---
echo '<script>
function openVipTab(btn, tabId) {
    // 1. Ocultar todos los paneles de contenido
    var panes = document.querySelectorAll(".vip-tab-pane-content");
    panes.forEach(function(pane) {
        pane.style.display = "none";
    });
    
    // 2. Quitar "active" de todos los botones
    var links = document.querySelectorAll(".vip-tab-link");
    links.forEach(function(link) {
        link.classList.remove("active");
    });
    
    // 3. Mostrar el tab seleccionado
    var targetPane = document.getElementById(tabId);
    if(targetPane) {
        targetPane.style.display = "block";
    }
    
    // 4. Marcar botón como activo
    if(btn) {
        btn.classList.add("active");
    }
}

function confirmVipOrder(id, level, days, cost, coin) {
    document.getElementById("input_pack_id_confirm").value = id;
    document.getElementById("confirmVipText").innerHTML = "Vas a adquirir <b>VIP " + level + "</b> por <b>" + days + " días</b> por un costo de <b>" + cost + " " + coin + "</b>.";
    
    var modalConf = document.getElementById("modalConfirmVip");
    
    // Mover al body para evitar conflicto visual si el modal principal se oculta
    if (modalConf && modalConf.parentNode !== document.body) {
        document.body.appendChild(modalConf);
    }
    
    if (window.bootstrap && window.bootstrap.Modal) {
        var myModal = new bootstrap.Modal(modalConf);
        myModal.show();
    } else {
        $("#modalConfirmVip").modal("show");
    }
}
</script>';
?>