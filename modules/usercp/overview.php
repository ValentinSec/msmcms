<?php
// Módulo: RESUMEN DE CUENTA
?>
<div class="module-title mb-4">
    <h2 class="text-color text-uppercase fw-bold">CUENTA</h2>
    <p class="text-color small">Información de tu cuenta y estadísticas</p>
    <div class="title-underline"></div>
</div>

<div class="donation-main-container p-4">
    <div class="account-details-banner mb-5 p-4">
        <div class="d-flex align-items-center justify-content-between mb-3">
            <div class="d-flex align-items-center">
                <div class="icon-box-shield me-3"><i class="fa fa-shield-alt"></i></div>
                <div>
                    <h4 class="m-0 text-color fw-bold"><?php echo strtoupper($_SESSION["username"]); ?></h4>
                    <small class="text-color">Detalles de la Cuenta</small>
                </div>
            </div>
            <div class="status-badge-disconnected"><i class="fa fa-circle me-1"></i> Desconectado</div>
        </div>
        <div class="account-email-box px-3 py-2">
            <i class="fa fa-envelope me-2 text-orange"></i> 
            <span class="small text-white"><?php echo $accountInfo["mail_addr"] ?? "Sin correo vinculado"; ?></span>
        </div>
    </div>

    <div class="section-label mb-3"><i class="fa fa-bolt text-orange me-2"></i> BALANCE Y PUNTOS</div>
    <div class="row g-4 mb-5">
        <?php
        $settingsPath = __PATH_INCLUDES__ . 'config/custom_settings.json';
        $cSet = file_exists($settingsPath) ? json_decode(file_get_contents($settingsPath), true) : [];
        $coinStyles = [
            1 => ['css' => 'b-gold',  'label' => 'Principal'],
            2 => ['css' => 'b-blue',  'label' => 'Secundaria'],
            3 => ['css' => 'b-green', 'label' => 'Extra']
        ];
        for ($i = 1; $i <= 3; $i++):
            $isActive = isset($cSet["active_coin_$i"]) && ($cSet["active_coin_$i"] === "on" || $cSet["active_coin_$i"] == 1);
            $colName = $cSet["col_coin_$i"] ?? '';
            if ($isActive && !empty($colName)):
                $realBalance = $balances[$colName] ?? $balances[strtolower($colName)] ?? 0;
                $style = $coinStyles[$i];
        ?>
            <div class="col-md-4">
                <div class="balance-card <?php echo $style['css']; ?>">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="text-uppercase fw-bold"><?php echo $colName; ?></span>
                        <small class="balance-tag"><?php echo $style['label']; ?></small>
                    </div>
                    <div class="value"><?php echo number_format((float)$realBalance); ?></div>
                    <div class="progress-line"></div>
                </div>
            </div>
        <?php
            endif;
        endfor;
        ?>
    </div>

    <div class="section-label mb-3"><i class="fa fa-crown text-orange me-2"></i> ESTADO VIP</div>
    <?php
        $vipLevel = $vipData['level'] ?? 0;
        $vipExpire = $vipData['expire'];
        $isExpired = false;
        if(!empty($vipExpire)) {
            $expireTime = is_numeric($vipExpire) ? $vipExpire : strtotime($vipExpire);
            if($expireTime && $expireTime < time()) {
                $isExpired = true; $vipLevel = 0;
            }
        }
        switch($vipLevel) {
            case 1: $vipName = "BRONZE"; $vipColor = "#cd7f32"; break;
            case 2: $vipName = "SILVER"; $vipColor = "#C0C0C0"; break;
            case 3: $vipName = "GOLD";    $vipColor = "#FFD700"; break;
            case 4: $vipName = "PLATINUM"; $vipColor = "#e5e4e2"; break;
            default: $vipName = "FREE"; $vipColor = "#6c757d"; break;
        }
    ?>
    <div class="vip-status-container p-4" style="border-left: 4px solid <?php echo $vipColor; ?>;">
        <div class="d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center">
                <div class="vip-icon-box me-3" style="border-color: <?php echo $vipColor; ?>; color: <?php echo $vipColor; ?>;">
                    <i class="fa fa-crown"></i>
                </div>
                <div>
                    <small class="text-white-50 d-block text-uppercase" style="font-size: 10px; letter-spacing: 1px;">Membresía Actual</small>
                    <h4 class="fw-bold m-0 text-uppercase" style="color: <?php echo $vipColor; ?>; text-shadow: 0 0 10px <?php echo $vipColor; ?>40;">
                        <?php echo $vipName; ?>
                    </h4>
                </div>
            </div>
            <?php if($vipLevel > 0 && !$isExpired): ?>
                <div class="text-end">
                    <small class="text-white-50 d-block" style="font-size: 10px;">Expira el:</small>
                    <span class="text-white fw-bold" style="font-size: 12px;">
                        <?php echo is_numeric($vipExpire) ? date("d/m/Y", $vipExpire) : $vipExpire; ?>
                    </span>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>