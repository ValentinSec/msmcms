<?php
/**
 * UserCP Module: PERSONAJES
 * PREPARED FOR OBFUSCATION (Pure PHP Version)
 * FIX: Texto "Desatascar" restaurado.
 */

if (!defined("access") or !access) die();
// --- FIX: CONEXIÓN A DB FORZADA PARA TEMPLATE CANIBAL ---
if (empty($userCharacters) && isset($_SESSION['username'])) {
    try {
        $db = Connection::Database('Me_MuOnline');
        $userCharacters = $db->query_fetch("SELECT * FROM Character WHERE AccountID = ?", [$_SESSION['username']]);
        if (!is_array($userCharacters)) $userCharacters = [];
        if (isset($userCharacters['Name'])) $userCharacters = [$userCharacters]; // Si es uno solo, lo hace array
    } catch (Exception $ex) {
        $userCharacters = [];
    }
}
// --------------------------------------------------------

// --- 1. ESTILOS ---
echo '<style>
    .modal-action-content { background: #15171c; border: 1px solid #2d323e; color: #fff; box-shadow: 0 0 40px rgba(0,0,0,0.8); border-radius: 8px; }
    .modal-action-header { padding: 20px; border-bottom: 1px solid #2d323e; background: rgba(0,0,0,0.2); display: flex; align-items: center; justify-content: space-between; }
    .modal-action-body { padding: 30px; text-align: center; }
    .modal-action-footer { padding: 15px; border-top: 1px solid #2d323e; background: rgba(0,0,0,0.2); display: flex; justify-content: flex-end; gap: 10px; }
    .action-icon-circle { width: 80px; height: 80px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px auto; background: rgba(255,255,255,0.03); border: 2px solid #2d323e; }
    .icon-danger { color: #e74c3c; border-color: rgba(231, 76, 60, 0.3); background: rgba(231, 76, 60, 0.1); }
    .icon-info { color: #3498db; border-color: rgba(52, 152, 219, 0.3); background: rgba(52, 152, 219, 0.1); }
    .icon-success { color: #2ecc71; border-color: rgba(46, 204, 113, 0.3); background: rgba(46, 204, 113, 0.1); }
    .stat-card-modern { display: flex; align-items: center; gap: 0.75rem; padding: 0.75rem; border-radius: 8px; border: 1px solid transparent; transition: all 0.2s ease; }
    .stat-icon-wrapper { padding: 0.5rem; border-radius: 8px; display: flex; align-items: center; justify-content: center; width: 40px; height: 40px; }
    .stat-content p { margin: 0; line-height: 1.2; }
    .stat-label { font-size: 0.75rem; font-weight: 500; text-transform: uppercase; letter-spacing: 0.5px; }
    .stat-value { font-size: 1.125rem; font-weight: 700; }
    .st-red { background: rgba(239, 68, 68, 0.1); border-color: rgba(239, 68, 68, 0.2); }
    .st-red .stat-icon-wrapper { background: rgba(127, 29, 29, 0.4); color: #f87171; }
    .st-red .stat-label { color: rgba(254, 202, 202, 0.7); }
    .st-red .stat-value { color: #fee2e2; }
    .st-green { background: rgba(34, 197, 94, 0.1); border-color: rgba(34, 197, 94, 0.2); }
    .st-green .stat-icon-wrapper { background: rgba(20, 83, 45, 0.4); color: #4ade80; }
    .st-green .stat-label { color: rgba(187, 247, 208, 0.7); }
    .st-green .stat-value { color: #dcfce7; }
    .st-blue { background: rgba(59, 130, 246, 0.1); border-color: rgba(59, 130, 246, 0.2); }
    .st-blue .stat-icon-wrapper { background: rgba(30, 58, 138, 0.4); color: #60a5fa; }
    .st-blue .stat-label { color: rgba(191, 219, 254, 0.7); }
    .st-blue .stat-value { color: #dbeafe; }
    .st-purple { background: rgba(168, 85, 247, 0.1); border-color: rgba(168, 85, 247, 0.2); }
    .st-purple .stat-icon-wrapper { background: rgba(88, 28, 135, 0.4); color: #c084fc; }
    .st-purple .stat-label { color: rgba(233, 213, 255, 0.7); }
    .st-purple .stat-value { color: #f3e8ff; }
</style>';

echo '<div class="module-title mb-4">';
    echo '<h2 class="text-color text-uppercase fw-bold">PERSONAJES</h2>';
    echo '<p class="text-color small">Tus héroes en el servidor</p>';
    echo '<div class="title-underline"></div>';
echo '</div>';

echo '<div class="donation-main-container p-4">';
    echo '<div class="row g-4">';

    if (!empty($userCharacters)) {
        foreach ($userCharacters as $char) {
            $avatarPath = __BASE_URL__ . "img/character-avatars/";
            $charImg = "default.jpg";
            $class = (int)$char["Class"];
            if ($class < 16) $charImg = "dw.jpg";
            elseif ($class < 32) $charImg = "dk.jpg";
            elseif ($class < 48) $charImg = "elf.jpg";
            elseif ($class < 64) $charImg = "mg.jpg";
            elseif ($class < 80) $charImg = "dl.jpg";
            elseif ($class < 96) $charImg = "sum.jpg";
            elseif ($class < 112) $charImg = "rf.jpg";
            elseif ($class < 128) $charImg = "gl.jpg"; 
            elseif ($class < 144) $charImg = "rw.jpg"; 
            elseif ($class < 160) $charImg = "sl.jpg"; 
            elseif ($class < 176) $charImg = "gc.jpg"; 
            elseif ($class < 192) $charImg = "km.jpg"; 
            elseif ($class < 208) $charImg = "lm.jpg"; 

            echo '<div class="col-md-6 mb-4">';
                echo '<div class="char-card-premium p-4">';
                    echo '<div class="d-flex align-items-center mb-4">';
                        echo '<div class="char-avatar-container me-3">';
                            echo '<img src="' . $avatarPath . $charImg . '" class="char-img-rank" onerror="this.src=\'' . $avatarPath . 'default.jpg\';">';
                            echo '<div class="char-level-badge">Lv. ' . $char["cLevel"] . '</div>';
                        echo '</div>';
                        echo '<div class="char-info-header text-start">';
                            echo '<h4 class="text-white fw-bold mb-0">' . strtoupper($char["Name"]) . '</h4>';
                            
                            // --- FIX: TRADUCTOR DE RAZAS (PURE PHP) ---
                            $c = (int)$char["Class"];
                            $cName = "Desconocido";
                            if($c==0) $cName="Dark Wizard"; elseif($c==1) $cName="Soul Master"; elseif($c==2||$c==3) $cName="Grand Master";
                            elseif($c==16) $cName="Dark Knight"; elseif($c==17) $cName="Blade Knight"; elseif($c==18||$c==19) $cName="Blade Master";
                            elseif($c==32) $cName="Fairy Elf"; elseif($c==33) $cName="Muse Elf"; elseif($c==34||$c==35) $cName="High Elf";
                            elseif($c==48) $cName="Magic Gladiator"; elseif($c==49||$c==50) $cName="Duel Master";
                            elseif($c==64) $cName="Dark Lord"; elseif($c==65||$c==66) $cName="Lord Emperor";
                            elseif($c==80) $cName="Summoner"; elseif($c==81) $cName="Bloody Summoner"; elseif($c==82||$c==83) $cName="Dimension Master";
                            elseif($c==96) $cName="Rage Fighter"; elseif($c==97||$c==98) $cName="Fist Master";
                            
                            echo '<p class="white small mb-1">' . $cName . '</p>';
                            // ------------------------------------------
                            
                            $resets = isset($char["ResetCount"]) ? $char["ResetCount"] : (isset($char["Resets"]) ? $char["Resets"] : 0);
                            echo '<span class="badge-resets"><i class="fa fa-sync-alt me-1"></i> ' . $resets . ' Resets</span>';
                        echo '</div>';
                    echo '</div>';
                    
                    echo '<div class="row g-2 mb-4">';
                        echo '<div class="col-6"><div class="stat-card-modern st-red"><div class="stat-icon-wrapper"><i class="fa fa-gavel"></i></div><div class="stat-content"><p class="stat-label">Fuerza</p><p class="stat-value">' . number_format($char["Strength"]) . '</p></div></div></div>';
                        echo '<div class="col-6"><div class="stat-card-modern st-green"><div class="stat-icon-wrapper"><i class="fa fa-wind"></i></div><div class="stat-content"><p class="stat-label">Agilidad</p><p class="stat-value">' . number_format($char["Dexterity"]) . '</p></div></div></div>';
                        echo '<div class="col-6"><div class="stat-card-modern st-blue"><div class="stat-icon-wrapper"><i class="fa fa-heart"></i></div><div class="stat-content"><p class="stat-label">Vitalidad</p><p class="stat-value">' . number_format($char["Vitality"]) . '</p></div></div></div>';
                        echo '<div class="col-6"><div class="stat-card-modern st-purple"><div class="stat-icon-wrapper"><i class="fa fa-bolt"></i></div><div class="stat-content"><p class="stat-label">Energía</p><p class="stat-value">' . number_format($char["Energy"]) . '</p></div></div></div>';
                    echo '</div>';
                    
                    echo '<div class="d-flex gap-2">';
                        echo '<a href="' . __BASE_URL__ . 'profile/player/req/' . $char['Name'] . '" target="_blank" style="background: linear-gradient(135deg, rgb(215, 73, 33) 0%, rgb(255, 138, 59) 100%); box-shadow: rgba(215, 73, 33, 0.4) 0px 4px 20px; border-radius: 0.75rem; color: white; font-weight: 600; transition: all 0.3s;" class="d-flex align-items-center justify-content-center flex-grow-1 py-3 text-decoration-none gap-2 hover-scale"><i class="fa fa-user"></i> Ver Perfil</a>';
                        $myCharCfgPath = __PATH_INCLUDES__ . 'config/mychar_config.json';
                        $myCharCfg = file_exists($myCharCfgPath) ? json_decode(file_get_contents($myCharCfgPath), true) : array();
                        $settingsPath = __PATH_INCLUDES__ . 'config/custom_settings.json';
                        $genSet = file_exists($settingsPath) ? json_decode(file_get_contents($settingsPath), true) : array();

                        if(isset($myCharCfg['enable_reset']) && $myCharCfg['enable_reset'] == 1) {
                            $reqLvl = isset($genSet['reset_level']) ? (int)$genSet['reset_level'] : 400;
                            $reqZen = isset($genSet['reset_money']) ? (int)$genSet['reset_money'] : 10000000;
                            $resetClearsStats = (isset($genSet['reset_clear_stats']) && $genSet['reset_clear_stats'] == 1) ? 1 : 0;
                            $canReset = ($char['cLevel'] >= $reqLvl && $char['Money'] >= $reqZen);
                            $onclickReset = $canReset ? "openActionModal('reset', '" . $char['Name'] . "', " . $resetClearsStats . ")" : "";
                            echo '<button class="d-flex align-items-center justify-content-center px-4 py-3 gap-2 fw-semibold text-decoration-none hover-scale" style="background: rgba(239, 68, 68, 0.15); border: 1px solid rgba(239, 68, 68, 0.4); color: rgb(252, 165, 165); border-radius: 0.75rem; transition: all 0.3s; ' . (!$canReset ? "opacity: 0.5; cursor: not-allowed; filter: grayscale(1);" : "") . '" ' . ($canReset ? 'onclick="' . $onclickReset . '"' : 'disabled') . '><i class="fa fa-sync-alt"></i> Reset</button>';
                        }

                        if(isset($myCharCfg['enable_unstuck']) && $myCharCfg['enable_unstuck'] == 1) {
                            echo '<button onclick="openActionModal(\'unstuck\', \'' . $char['Name'] . '\', 0)" class="d-flex align-items-center justify-content-center px-4 py-3 gap-2 fw-semibold text-decoration-none hover-scale" style="background: rgba(215, 73, 33, 0.15); border: 1px solid rgba(215, 73, 33, 0.4); color: rgb(255, 138, 59); border-radius: 0.75rem; transition: all 0.3s;"><i class="fa fa-map-marker-alt"></i> Desatascar</button>';
                        }
                    echo '</div>';

                    if(isset($myCharCfg['enable_reset']) && $myCharCfg['enable_reset'] == 1) {
                        $pct = min(($char['cLevel'] / $reqLvl) * 100, 100);
                        echo '<div class="progress mt-2" style="height: 4px; background: rgba(255,255,255,0.1);"><div class="progress-bar bg-danger" style="width: ' . $pct . '%"></div></div>';
                        echo '<small class="d-block text-end text-white" style="font-size: 9px;">Meta Reset: ' . $char['cLevel'] . '/' . $reqLvl . ' Lv</small>';
                    }
                echo '</div>';
            echo '</div>';
        }
    } else {
        echo '<div class="col-12 text-center p-5"><div class="empty-state-box p-5"><i class="fa fa-users-slash fa-3x text-white mb-3"></i><p class="text-white">No tienes personajes creados aún.</p></div></div>';
    }
    echo '</div>';
echo '</div>';

echo '<div class="modal fade" id="charActionModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content modal-action-content">
            <div class="modal-header modal-action-header">
                <h5 class="modal-title fw-bold text-uppercase" id="actionModalTitle">CONFIRMAR ACCIÓN</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body modal-action-body">
                <div id="actionIcon" class="action-icon-circle icon-info"><i class="fa fa-question fa-2x"></i></div>
                <h5 class="text-white mb-3" id="actionModalHeadline">¿Estás seguro?</h5>
                <p class="text-white-50 mb-0" id="actionModalText">Se realizará una acción sobre tu personaje.</p>
                <div id="actionErrorBox" class="alert alert-danger mt-3 d-none" role="alert"></div>
            </div>
            <div class="modal-footer modal-action-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal" id="btnActionCancel">Cancelar</button>
                <button type="button" class="btn btn-primary btn-sm fw-bold" id="btnActionConfirm">
                    <span class="spinner-border spinner-border-sm d-none me-2" role="status" aria-hidden="true"></span>Confirmar
                </button>
            </div>
        </div>
    </div>
</div>';

echo '<script>
    let currentActionType = "";
    let currentCharName = "";
    let actionModalInstance = null;
    document.addEventListener("DOMContentLoaded", function() {
        var modalEl = document.getElementById("charActionModal");
        if(modalEl) { actionModalInstance = new bootstrap.Modal(modalEl); }
        var btnConfirm = document.getElementById("btnActionConfirm");
        if(btnConfirm) { btnConfirm.addEventListener("click", performAction); }
    });
    function openActionModal(type, charName, resetsStats) {
        currentActionType = type; currentCharName = charName;
        const titleEl = document.getElementById("actionModalTitle");
        const headlineEl = document.getElementById("actionModalHeadline");
        const textEl = document.getElementById("actionModalText");
        const iconEl = document.getElementById("actionIcon");
        const btnConfirm = document.getElementById("btnActionConfirm");
        const btnCancel = document.getElementById("btnActionCancel");
        const errorBox = document.getElementById("actionErrorBox");
        if(errorBox) errorBox.classList.add("d-none");
        btnConfirm.classList.remove("d-none");
        btnCancel.classList.remove("d-none");
        btnConfirm.disabled = false;
        if(type === "reset") {
            titleEl.textContent = "RESETEAR PERSONAJE";
            headlineEl.textContent = "¿Resetear a " + charName + "?";
            let msg = "Tu personaje volverá a nivel 1.<br>";
            msg += (resetsStats == 1) ? "<strong class=\'text-danger\'>Tus stats serán borrados</strong>." : "<strong class=\'text-success\'>Stats acumulativos</strong>.";
            textEl.innerHTML = msg + "<br>Se consumirá el Zen requerido.";
            iconEl.className = "action-icon-circle icon-danger";
            iconEl.innerHTML = "<i class=\'fa fa-sync-alt fa-2x\'></i>";
            btnConfirm.className = "btn btn-danger btn-sm fw-bold";
            btnConfirm.innerHTML = "Resetear Ahora";
        } else if(type === "unstuck") {
            titleEl.textContent = "DESATASCAR PERSONAJE";
            headlineEl.textContent = "¿Mover a " + charName + "?";
            textEl.innerHTML = "Tu personaje será teletransportado a Lorencia.<br><strong class=\'text-warning\'>¡Debes estar desconectado!</strong>";
            iconEl.className = "action-icon-circle icon-info";
            iconEl.innerHTML = "<i class=\'fa fa-map-marker-alt fa-2x\'></i>";
            btnConfirm.className = "btn btn-primary btn-sm fw-bold";
            btnConfirm.innerHTML = "Mover a Lorencia";
        }
        if(actionModalInstance) actionModalInstance.show();
    }
    function performAction() {
        const btnConfirm = document.getElementById("btnActionConfirm");
        const btnCancel = document.getElementById("btnActionCancel");
        const spinner = btnConfirm.querySelector(".spinner-border");
        btnConfirm.disabled = true; btnCancel.classList.add("d-none");
        if(spinner) spinner.classList.remove("d-none");
        const data = new FormData();
        data.append("char_name", currentCharName);
        let url = (currentActionType === "reset") ? "' . __BASE_URL__ . 'api/character_reset.php" : "' . __BASE_URL__ . 'api/character_unstuck.php";
        fetch(url, { method: "POST", body: data })
        .then(r => r.text())
        .then(res => {
            if(res.trim() === "ok") {
                const iconEl = document.getElementById("actionIcon");
                iconEl.className = "action-icon-circle icon-success";
                iconEl.innerHTML = "<i class=\'fa fa-check fa-2x\'></i>";
                document.getElementById("actionModalHeadline").textContent = "¡Éxito!";
                document.getElementById("actionModalText").textContent = "Acción completada.";
                btnConfirm.className = "btn btn-success btn-sm fw-bold";
                btnConfirm.disabled = false;
                btnConfirm.innerHTML = "Continuar";
                btnConfirm.onclick = () => window.location.reload();
            } else {
                document.getElementById("actionErrorBox").textContent = res;
                document.getElementById("actionErrorBox").classList.remove("d-none");
                btnConfirm.disabled = false; btnCancel.classList.remove("d-none");
                if(spinner) spinner.classList.add("d-none");
            }
        });
    }
</script>';