<?php
/**
 * Referral System Module
 * UserCP Integration (Win-Win Reward: Pays Both Users)
 * PREPARED FOR OBFUSCATION (Pure PHP Version)
 */

if (!defined("access") or !access) die();

// 1. CARGAR CONFIGURACIÓN ESPECÍFICA
$customConfigPath = __PATH_INCLUDES__ . 'config/custom_settings.json';
$refConfig = array();
if (file_exists($customConfigPath)) {
    $refConfig = json_decode(file_get_contents($customConfigPath), true);
}

// Variables de Configuración
$rewardCoin   = isset($refConfig['referral_reward_coin']) ? $refConfig['referral_reward_coin'] : 'WCoinC'; 
$rewardAmount = isset($refConfig['referral_reward_amount']) ? (int)$refConfig['referral_reward_amount'] : 0;
$conditionRes = isset($refConfig['referral_condition']) ? (int)$refConfig['referral_condition'] : 10;
$refActive    = (isset($refConfig['referral_active']) && $refConfig['referral_active'] == 1);

// Si el sistema está desactivado
if(!$refActive) {
    echo '<div class="alert alert-warning text-center">El sistema de referidos se encuentra desactivado.</div>';
    return;
}

try {
    $db = Connection::Database('Me_MuOnline');
    $myUser = $_SESSION['username'];

    // ---------------------------------------------------------
    // 2. LÓGICA DE COBRO (CLAIM REWARD)
    // ---------------------------------------------------------
    if(isset($_POST['claim_reward']) && !empty($_POST['referral_char'])) {
        try {
            $targetUser = trim($_POST['referral_char']); 
            
            if(empty($targetUser)) throw new Exception("Usuario inválido.");
            if($targetUser == $myUser) throw new Exception("No puedes reclamar por ti mismo.");

            $checkRef = $db->query_fetch_single("SELECT memb___id, referral_by, referral_claimed FROM MEMB_INFO WHERE memb___id = ?", array($targetUser));
            
            if(!$checkRef) throw new Exception("El usuario '$targetUser' no existe.");
            
            if(trim(strtolower($checkRef['referral_by'])) !== trim(strtolower($myUser))) {
                throw new Exception("Este usuario no es tu referido.");
            }

            if($checkRef['referral_claimed'] == 1) {
                throw new Exception("Ya has reclamado esta recompensa anteriormente.");
            }

            $charData = $db->query_fetch_single("SELECT MAX(ResetCount) as MaxResets FROM Character WHERE AccountID = ?", array($targetUser));
            $userResets = isset($charData['MaxResets']) ? (int)$charData['MaxResets'] : 0;

            if($userResets < $conditionRes) {
                throw new Exception("El usuario tiene $userResets resets. Necesita llegar a $conditionRes.");
            }

            $coinTable = (isset($refConfig['table_coins']) ? $refConfig['table_coins'] : 'CashShopData');
            $coinCol   = $rewardCoin; 
            $idCol     = ($coinTable == 'CashShopData') ? 'AccountID' : 'memb___id';

            // PAGO AL REFERENTE
            $checkCoinMy = $db->query_fetch_single("SELECT $idCol FROM $coinTable WHERE $idCol = ?", array($myUser));
            if(!$checkCoinMy) {
                if($coinTable == 'CashShopData') {
                    $db->query("INSERT INTO CashShopData (AccountID, WCoinC, WCoinP, GoblinPoint) VALUES (?, 0, 0, 0)", array($myUser));
                } else {
                    $db->query("INSERT INTO $coinTable ($idCol, $coinCol) VALUES (?, ?)", array($myUser, 0));
                }
            }
            $db->query("UPDATE $coinTable SET $coinCol = $coinCol + ? WHERE $idCol = ?", array($rewardAmount, $myUser));

            // PAGO AL REFERIDO
            $checkCoinFriend = $db->query_fetch_single("SELECT $idCol FROM $coinTable WHERE $idCol = ?", array($targetUser));
            if(!$checkCoinFriend) {
                if($coinTable == 'CashShopData') {
                    $db->query("INSERT INTO CashShopData (AccountID, WCoinC, WCoinP, GoblinPoint) VALUES (?, 0, 0, 0)", array($targetUser));
                } else {
                    $db->query("INSERT INTO $coinTable ($idCol, $coinCol) VALUES (?, ?)", array($targetUser, 0));
                }
            }
            $db->query("UPDATE $coinTable SET $coinCol = $coinCol + ? WHERE $idCol = ?", array($rewardAmount, $targetUser));

            $db->query("UPDATE MEMB_INFO SET referral_claimed = 1 WHERE memb___id = ?", array($targetUser));
            message('success', "¡Éxito! Ambos han recibido " . number_format($rewardAmount) . " $rewardCoin.");

        } catch (Exception $e) {
            message('error', $e->getMessage());
        }
    }

    // 3. OBTENER DATOS PARA LA VISTA
    $myReferralLink = __BASE_URL__ . 'register?ref=' . $myUser;
    $queryRefs = "SELECT m.memb___id as name, m.appl_days as date, m.referral_claimed as claimed, (SELECT MAX(ResetCount) FROM Character WHERE AccountID = m.memb___id) as resets FROM MEMB_INFO m WHERE m.referral_by = ? ORDER BY m.appl_days DESC";
    $myReferrals = $db->query_fetch($queryRefs, array($myUser));
    if(!is_array($myReferrals)) $myReferrals = array();

    $totalEarned = 0; $pending = 0;
    foreach($myReferrals as $r) {
        if($r['claimed'] == 1) $totalEarned += $rewardAmount;
        if($r['claimed'] == 0 && (int)$r['resets'] >= $conditionRes) $pending++;
    }

} catch (Exception $e) {
    echo '<div class="alert alert-danger"><b>Error de Sistema:</b> '.$e->getMessage().'</div>';
    return;
}

// --- RENDERIZADO (Estética Original) ---

echo '<style>
    .usercp-module-title { border-bottom: 1px solid rgba(255, 255, 255, 0.1); padding-bottom: 20px; margin-bottom: 30px; }
    .usercp-module-title h3 { color: #fff; font-family: "Cinzel", serif; font-weight: 700; margin: 0; font-size: 28px; text-shadow: 0 2px 10px rgba(0,0,0,0.5); }
    .usercp-module-title p { color: #999; font-size: 14px; margin: 8px 0 0 0; }
    .settings-section { background: linear-gradient(to bottom, #1a1c24, #15171c); border: 1px solid #2d323e; box-shadow: 0 10px 30px rgba(0,0,0,0.3); padding: 25px; border-radius: 10px; margin-bottom: 30px; }
    .settings-title { font-family: "Cinzel", serif; color: #e0d2ad; font-size: 18px; font-weight: 700; margin-bottom: 20px; padding-bottom: 10px; border-bottom: 1px solid rgba(224, 210, 173, 0.1); }
    .ref-stat-card { background: linear-gradient(145deg, #1e2129, #15171c); border: 1px solid #2d323e; border-radius: 12px; padding: 25px; display: flex; align-items: center; gap: 20px; transition: all 0.3s; box-shadow: 0 5px 15px rgba(0,0,0,0.2); position: relative; overflow: hidden; }
    .ref-stat-card:hover { transform: translateY(-5px); box-shadow: 0 15px 30px rgba(0,0,0,0.4); border-color: #444; }
    .ref-stat-card .icon { width: 60px; height: 60px; background: rgba(255,255,255,0.03); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 24px; color: #777; border: 1px solid rgba(255,255,255,0.05); }
    .ref-stat-card.money .icon { color: #ffb700; background: rgba(255, 183, 0, 0.05); border-color: rgba(255, 183, 0, 0.2); }
    .ref-stat-card.pending .icon { color: #00e096; background: rgba(0, 224, 150, 0.05); border-color: rgba(0, 224, 150, 0.2); }
    .ref-stat-card .info h4 { font-family: "Cinzel", serif; margin: 0; color: #fff; font-weight: 700; font-size: 32px; line-height: 1; }
    .ref-stat-card .info span { font-size: 13px; color: #888; text-transform: uppercase; font-weight: 600; letter-spacing: 1px; }
    .referral-link-box { background: rgba(0,0,0,0.3); padding: 25px; border-radius: 10px; border: 1px solid #2d323e; }
    .input-group { display: flex; gap: 15px; align-items: stretch; }
    #refLinkInput.form-control-dark { background: #111318; border: 1px solid #333; color: #cba660; font-family: monospace; font-size: 14px; padding: 12px 15px; flex-grow: 1; }
    .btn-copy { background: linear-gradient(to bottom, #2d323e, #22262f); border: 1px solid #444; color: #ddd; width: 50px; border-radius: 6px; cursor: pointer; transition: 0.3s; display: flex; align-items: center; justify-content: center; font-size: 18px; }
    .btn-copy:hover { background: linear-gradient(to bottom, #ffb700, #e0a100); color: #111; border-color: #ffb700; }
    #copyMsg { margin-top: 10px; color: #00e096; font-size: 13px; font-weight: 600; opacity: 0; visibility: hidden; transition: 0.4s; }
    #copyMsg.visible { opacity: 1; visibility: visible; }
    .table-dark-custom { width: 100%; border-collapse: separate; border-spacing: 0 8px; }
    .table-dark-custom th { color: #888; font-size: 11px; text-transform: uppercase; padding: 15px 20px; }
    .table-dark-custom td { background: #13151b; padding: 20px; border-top: 1px solid #1f222b; border-bottom: 1px solid #1f222b; }
    .avatar-mini { width: 34px; height: 34px; background: linear-gradient(135deg, #333, #222); border: 1px solid #444; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #777; }
    .badge-status { font-size: 11px; padding: 6px 12px; border-radius: 20px; font-weight: 700; text-transform: uppercase; display: inline-flex; align-items: center; gap: 5px; }
    .badge-status.pending { background: rgba(255,255,255,0.05); color: #999; border: 1px solid #333; }
    .badge-status.claimed { background: rgba(0, 224, 150, 0.1); color: #00e096; border: 1px solid rgba(0, 224, 150, 0.3); }
    .btn-claim-reward { background: linear-gradient(135deg, #ffb700, #ff8a00); border: none; color: #111; padding: 8px 20px; border-radius: 30px; font-size: 12px; font-weight: 800; text-transform: uppercase; cursor: pointer; font-family: "Cinzel", serif; transition: 0.3s; display: inline-flex; align-items: center; gap: 8px; }
    .btn-claim-reward:hover { transform: translateY(-3px); box-shadow: 0 10px 25px rgba(255, 138, 0, 0.5); background: linear-gradient(135deg, #ffc800, #ff9a00); }
    .progress { background: #0f1116; border-radius: 10px; height: 6px; border: 1px solid #222; }
    .progress-bar { border-radius: 10px; transition: width 0.6s ease; }
    .progress-bar.bg-success { background: linear-gradient(90deg, #00b09b, #96c93d); }
    .progress-bar.bg-warning { background: linear-gradient(90deg, #f7971e, #ffd200); }
</style>';

echo '<div class="usercp-module-title">';
    echo '<h3>Sistema de Referidos</h3>';
    echo '<p>Invita jugadores a <b>' . (function_exists('custom_config') ? custom_config("server_name") : "Server") . '</b> y gana recompensas.</p>';
echo '</div>';

echo '<div class="row mb-4">';
    echo '<div class="col-md-4"><div class="ref-stat-card"><div class="icon"><i class="fa fa-users"></i></div><div class="info"><h4>' . count($myReferrals) . '</h4><span>Invitados Totales</span></div></div></div>';
    echo '<div class="col-md-4"><div class="ref-stat-card money"><div class="icon"><i class="fa fa-coins"></i></div><div class="info"><h4>' . number_format($totalEarned) . '</h4><span>' . $rewardCoin . ' Ganados</span></div></div></div>';
    echo '<div class="col-md-4"><div class="ref-stat-card pending"><div class="icon"><i class="fa fa-hourglass-half"></i></div><div class="info"><h4>' . $pending . '</h4><span>Disponibles</span></div></div></div>';
echo '</div>';

echo '<div class="settings-section mb-4">';
    echo '<h3 class="settings-title">Tu Link Único</h3>';
    echo '<div class="referral-link-box">';
        echo '<p style="color:#aaa; font-size:13px; margin-bottom:10px;">Comparte este enlace. Cuando un amigo alcance los <b>' . $conditionRes . ' Resets</b>, ambos ganarán <span style="color:#00d084; font-weight:bold;">' . number_format($rewardAmount) . ' ' . $rewardCoin . '</span>.</p>';
        echo '<div class="input-group">';
            echo '<input type="text" id="refLinkInput" class="form-control-dark" value="' . $myReferralLink . '" readonly>';
            echo '<button class="btn-copy" onclick="copyToClipboard()"><i class="fa fa-copy"></i></button>';
        echo '</div>';
        echo '<div id="copyMsg"><i class="fa fa-check-circle"></i> ¡Copiado!</div>';
    echo '</div>';
echo '</div>';

echo '<div class="settings-section">';
    echo '<h3 class="settings-title">Mis Invitados</h3>';
    echo '<div class="table-responsive"><table class="table table-dark-custom"><thead><tr><th>Cuenta</th><th>Fecha</th><th>Progreso</th><th class="text-end">Estado</th></tr></thead><tbody>';
    if(empty($myReferrals)) {
        echo '<tr><td colspan="4" class="text-center py-4" style="color:#666;">Aún no has invitado a nadie.</td></tr>';
    } else {
        foreach($myReferrals as $ref) {
            $currRes = (int)$ref['resets'];
            $pct = min(($currRes / $conditionRes) * 100, 100);
            $ready = ($currRes >= $conditionRes);
            $claimed = ($ref['claimed'] == 1);
            $dateFmt = ($ref['date']) ? date("d/m/Y", strtotime($ref['date'])) : 'N/A';
            
            echo '<tr>';
                echo '<td><div class="d-flex align-items-center"><div class="avatar-mini"><i class="fa fa-user"></i></div><span style="font-weight:bold; color:#fff; margin-left:10px;">' . $ref['name'] . '</span></div></td>';
                echo '<td style="color:#888;">' . $dateFmt . '</td>';
                echo '<td style="width: 30%;"><div class="progress-wrapper"><div class="d-flex justify-content-between mb-1" style="font-size:11px;"><span>' . $currRes . ' Resets</span><span>' . $conditionRes . ' Meta</span></div><div class="progress"><div class="progress-bar ' . ($ready ? "bg-success" : "bg-warning") . '" style="width: ' . $pct . '%"></div></div></div></td>';
                echo '<td class="text-end">';
                if($claimed) {
                    echo '<span class="badge-status claimed"><i class="fa fa-check-double"></i> Cobrado</span>';
                } elseif($ready) {
                    echo '<form method="post" style="display:inline;"><input type="hidden" name="referral_char" value="' . trim($ref['name']) . '"><button type="submit" name="claim_reward" class="btn-claim-reward"><i class="fa fa-gift"></i> Reclamar</button></form>';
                } else {
                    echo '<span class="badge-status pending"><i class="fa fa-clock"></i> En Proceso</span>';
                }
                echo '</td>';
            echo '</tr>';
        }
    }
    echo '</tbody></table></div>';
echo '</div>';

echo '<script>
function copyToClipboard() {
    var copyText = document.getElementById("refLinkInput");
    copyText.select(); copyText.setSelectionRange(0, 99999);
    if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(copyText.value).then(function() { showFeedback(); });
    } else {
        document.execCommand("copy"); showFeedback();
    }
}
function showFeedback() {
    var msg = document.getElementById("copyMsg");
    msg.classList.add("visible");
    setTimeout(function(){ msg.classList.remove("visible"); }, 2500);
}
</script>';