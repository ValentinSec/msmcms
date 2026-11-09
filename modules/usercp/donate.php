<?php
/**
 * Módulo: DONACIONES (Frontend)
 * Seguridad: Access Check + XSS Output Escaping
 */

// 1. BLOQUEO DE ACCESO DIRECTO (CRÍTICO)
if(!defined('access') or !access) die();

// Configuración de Donaciones
$donCfgFile = __PATH_INCLUDES__ . 'config/donation_config.json';
if (file_exists($donCfgFile)) {
    $donCfg = json_decode(file_get_contents($donCfgFile), true);
} else {
    $donCfg = ['methods' => [], 'packages' => []];
}
$module_active = $donCfg['module_active'] ?? 0;

// --- BLOQUE DE SEGURIDAD: OBTENER MONEDAS REALMENTE ACTIVAS ---
$customSettingsFile = __PATH_INCLUDES__ . 'config/custom_settings.json';
$activeCoinsList = [];
$allowedCoinsLower = []; 

if (file_exists($customSettingsFile)) {
    $cSet = json_decode(file_get_contents($customSettingsFile), true);
    for($i=1; $i<=3; $i++) {
        $isActive = isset($cSet["active_coin_$i"]) && ($cSet["active_coin_$i"] === "on" || $cSet["active_coin_$i"] == 1);
        if($isActive && !empty($cSet["col_coin_$i"])) {
            // Sanitización básica del nombre de la moneda
            $coinName = trim(strip_tags($cSet["col_coin_$i"]));
            $activeCoinsList[] = $coinName;
            $allowedCoinsLower[] = strtolower($coinName);
        }
    }
}
// Fallback
if(empty($activeCoinsList)) {
    $activeCoinsList = ['Coins'];
    $allowedCoinsLower = ['coins'];
}
$showTabs = count($activeCoinsList) > 1;
?>

<div class="module-title mb-4">
    <h2 class="text-white text-uppercase fw-bold">DONACIONES</h2>
    <p class="text-white small">Apoya al servidor y obtén beneficios</p>
    <div class="title-underline"></div>
</div>

<div class="donation-main-container p-4">
    <?php if (isset($donCfg['module_active']) && $donCfg['module_active'] == 1): ?>
        <div class="row g-4">
            <div class="col-md-4">
                <h6 class="section-label-small mb-3 text-uppercase">Método de Pago</h6>
                <nav class="nav flex-column gap-3" id="donationMethods" role="tablist">
                    <?php foreach($donCfg['methods'] as $id => $m): if($m['active']): 
                        // Sanitización de IDs para evitar romper el HTML
                        $safeId = htmlspecialchars($id, ENT_QUOTES, 'UTF-8');
                        
                        switch($id) {
                            case 'paypal': $icon = 'fa-paypal'; $color = '#003087'; $brand = 'fa-brands'; break;
                            case 'mp': $icon = 'fa-handshake'; $color = '#009ee3'; $brand = 'fa'; break;
                            case 'binance': $icon = 'fa-bitcoin'; $color = '#f3ba2f'; $brand = 'fa-brands'; break;
                            case 'yape': $icon = 'fa-bolt'; $color = '#742384'; $brand = 'fa'; break;
                            case 'bank': $icon = 'fa-university'; $color = '#2ecc71'; $brand = 'fa'; break;
                            default: $icon = 'fa-credit-card'; $color = '#f39c12'; $brand = 'fa';
                        }
                    ?>
                        <button class="payment-card-premium border-0" data-bs-toggle="pill" data-bs-target="#pack-<?php echo $safeId; ?>" type="button" role="tab">
                            <div class="d-flex align-items-center w-100">
                                <div class="payment-icon-box me-3">
                                    <i class="<?php echo $brand . ' ' . $icon; ?>" style="font-size: 24px; color: <?php echo $color; ?>;"></i>
                                </div>
                                <div class="payment-text text-start flex-grow-1">
                                    <h5 class="mb-0"><?php echo htmlspecialchars(strtoupper($m['name'])); ?></h5>
                                    <small><?php echo htmlspecialchars($m['currency']); ?> Local</small>
                                </div>
                                <div class="check-badge"><i class="fa fa-check"></i></div>
                            </div>
                        </button>
                    <?php endif; endforeach; ?>
                </nav>
            </div>

            <div class="col-md-8">
                <h6 class="section-label-small mb-3 text-uppercase">Selecciona tu paquete</h6>
                
                <div class="tab-content donation-packages-wrapper p-4" id="donationPacks">
                    <div class="tab-pane fade show active" id="pack-empty" role="tabpanel">
                        <div class="empty-state-container">
                            <div class="empty-wallet-icon mb-4"><i class="fa fa-wallet fa-4x text-orange"></i></div>
                            <p class="text-white text-center opacity-50">Selecciona un método de pago para ver los paquetes disponibles</p>
                        </div>
                    </div>

                    <?php foreach($donCfg['methods'] as $methodId => $mInfo): 
                        $safeMethodId = htmlspecialchars($methodId, ENT_QUOTES, 'UTF-8');
                    ?>
                        <div class="tab-pane fade" id="pack-<?php echo $safeMethodId; ?>" role="tabpanel">
                            <?php 
                            $allPacks = $mInfo['packages'] ?? [];
                            $currentMethodPacks = array_filter($allPacks, function($p) use ($allowedCoinsLower, $activeCoinsList) {
                                $pCoin = strtolower(trim($p['coin_type'] ?? $activeCoinsList[0]));
                                return in_array($pCoin, $allowedCoinsLower);
                            });

                            if (!empty($currentMethodPacks)): ?>
                                <div class="d-flex justify-content-between align-items-center mb-4">
                                    <?php if($showTabs): ?>
                                        <div class="nav nav-pills nav-pills-coins" style="background: rgba(0,0,0,0.2); border-radius: 20px; padding: 3px; gap: 5px;">
                                            <?php foreach($activeCoinsList as $index => $coinName): 
                                                $safeCoinName = htmlspecialchars($coinName, ENT_QUOTES, 'UTF-8');
                                            ?>
                                                <button type="button" class="nav-link btn-filter-coin <?php echo ($index === 0) ? 'active' : ''; ?>" 
                                                        onclick="filterPacks(this, '<?php echo $safeMethodId; ?>', '<?php echo $safeCoinName; ?>')"
                                                        style="border-radius: 15px; padding: 4px 12px; font-size: 10px; text-transform: uppercase; cursor: pointer;">
                                                    <?php echo $safeCoinName; ?>
                                                </button>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <div class="donation-grid" id="grid-<?php echo $safeMethodId; ?>">
                                    <?php foreach($currentMethodPacks as $pack): 
                                        $packCoin = $pack['coin_type'] ?? $activeCoinsList[0];
                                        $safePackCoin = htmlspecialchars($packCoin, ENT_QUOTES, 'UTF-8');
                                        $finalPrice = $pack['price_usd'];
                                        $initialDisplay = ($showTabs) ? ((strtolower(trim($packCoin)) == strtolower(trim($activeCoinsList[0]))) ? 'block' : 'none') : 'block';
                                        
                                        // [SEGURIDAD] Validación de imagen para evitar Path Traversal
                                        $imgFile = $pack['image'] ?? 'Pack1.png';
                                        $imgFile = basename($imgFile); // Forzar solo nombre de archivo
                                    ?>
                                            <button type="button" class="donation-pack-btn pack-item-<?php echo $safeMethodId; ?>" 
                                                data-coin="<?php echo $safePackCoin; ?>"
                                                onclick="selectDonationPack(this, '<?php echo $safeMethodId; ?>', '<?php echo htmlspecialchars($pack['id']); ?>')"
                                                style="width: 100%; max-width: 240px; margin: 0 auto; display: <?php echo $initialDisplay; ?>; position: relative;">
                                            
                                            <?php if(!empty($pack['bonus'])): ?>
                                                <div class="bonus-badge"><?php echo htmlspecialchars($pack['bonus']); ?></div>
                                            <?php endif; ?>
                                            
                                            <div class="pack-icon-area" style="width: 100%; height: 160px; overflow: hidden; padding: 0 !important; background-color: #15171c; position: relative;">
                                                <img src="<?php echo __BASE_URL__; ?>img/packs/<?php echo $imgFile; ?>" style="width: 100% !important; height: 100% !important; object-fit: cover; display: block; max-width: none !important;">
                                            </div>
                                            
                                            <div class="pack-details">
                                                <div class="pack-check"><i class="fa fa-check"></i></div>
                                                <div class="bc-amount"><?php echo number_format($pack['coins']); ?> <span style="font-size:0.8em; opacity:0.8;"><?php echo $safePackCoin; ?></span></div>
                                                <div class="fiat-amount"><?php echo htmlspecialchars($mInfo['symbol']) . ' ' . number_format($finalPrice, 2); ?> <span class="small"><?php echo htmlspecialchars($mInfo['currency']); ?></span></div>
                                                <?php if(!empty($pack['badge'])): ?>
                                                    <small class="text-orange d-block mt-1" style="font-size:10px; font-weight: bold;"><?php echo htmlspecialchars(strtoupper($pack['badge'])); ?></small>
                                                <?php endif; ?>
                                            </div>
                                    </button>
                                    <?php endforeach; ?>
                                </div>

                                <div class="mt-4 text-center border-top border-dark pt-3">
                                    <button id="btn-pay-<?php echo $safeMethodId; ?>" disabled class="btn-proceed-payment"><i class="fa fa-check-circle me-2"></i> Proceder al Pago</button>
                                </div>
                            <?php else: ?>
                                <div class="d-flex flex-column align-items-center justify-content-center text-center p-5" style="border: 2px dashed #2d323e; border-radius: 10px; background: rgba(0,0,0,0.2); min-height: 250px;">
                                    <div style="width: 80px; height: 80px; background: #1c1f26; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-bottom: 20px;"><i class="fa fa-wallet fa-3x" style="color: #3d4452;"></i></div>
                                    <h4 style="color: #8a8d93; font-size: 16px; font-weight: 600;">No hay paquetes disponibles <br><span style="font-size: 12px; color: #535b69;">para <?php echo htmlspecialchars($mInfo['name']); ?> actualmente</span></h4>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="col-12 text-center p-5">
            <div class="empty-state-box p-5 opacity-50"><i class="fa fa-lock fa-3x text-white mb-3"></i><p class="text-white">El módulo de donaciones se encuentra temporalmente desactivado por el administrador.</p></div>
        </div>
    <?php endif; ?>
</div>