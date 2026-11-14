<?php
/**
 * Banned Manager Plugin
 */
if(!defined('access') or !access) die();

$bannedFile = __PATH_INCLUDES__ . 'config/banned_list.json';
$currentBans = file_exists($bannedFile) ? json_decode((string)@file_get_contents($bannedFile), true) : array();
if(!is_array($currentBans)) $currentBans = array();

$bnH = function($s) { return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); };
$bnUrl = 'index.php?module=my_plugins&load_plugin=banned';
$errorMsg = '';

// --- 1. SANCIONAR ---
if(isset($_POST['submit_ban'])) {
    try {
        $charName = trim((string)(isset($_POST['ban_char']) ? $_POST['ban_char'] : ''));
        $reason   = trim((string)(isset($_POST['ban_reason']) ? $_POST['ban_reason'] : ''));
        $type     = (isset($_POST['ban_type']) && $_POST['ban_type'] === 'temporal') ? 'temporal' : 'permanent';
        $days     = ($type === 'temporal') ? (int)(isset($_POST['ban_days']) ? $_POST['ban_days'] : 0) : 0;

        if($charName === '' || strlen($charName) > 10) throw new Exception('El personaje no existe.');
        if($reason === '') throw new Exception('Indicá el motivo de la sanción.');
        $reason = function_exists('mb_substr') ? mb_substr($reason, 0, 100, 'UTF-8') : substr($reason, 0, 100);
        if($type === 'temporal' && ($days < 1 || $days > 3650)) throw new Exception('La duración debe ser de 1 a 3650 días.');

        $db = Connection::Database('MuOnline');
        $check = $db->query_fetch_single("SELECT Name FROM Character WHERE Name = ?", array($charName));
        if(!is_array($check)) throw new Exception('El personaje no existe.');

        $db->query("UPDATE Character SET CtlCode = 1 WHERE Name = ?", array($charName));

        $currentBans[$charName] = array(
            'name' => $charName,
            'reason' => $reason,
            'type' => $type,
            'days' => $days,
            'date_banned' => date('Y-m-d H:i:s'),
            'expire_date' => ($type === 'temporal') ? date('Y-m-d H:i:s', strtotime('+' . $days . ' days')) : null
        );

        file_put_contents($bannedFile, json_encode($currentBans, JSON_PRETTY_PRINT));
        header('Location: ' . $bnUrl . '&success=1');
        exit;
    } catch (Exception $e) {
        $errorMsg = $e->getMessage();
    }
}

// --- 2. REVOCAR (solo por POST: queda cubierto por el token anti-CSRF del panel) ---
if(isset($_POST['revoke_char'])) {
    try {
        $target = trim((string)$_POST['revoke_char']);
        if($target === '' || strlen($target) > 10) throw new Exception('Personaje inválido.');

        $db = Connection::Database('MuOnline');
        $db->query("UPDATE Character SET CtlCode = 0 WHERE Name = ?", array($target));

        if(isset($currentBans[$target])) {
            unset($currentBans[$target]);
            file_put_contents($bannedFile, json_encode($currentBans, JSON_PRETTY_PRINT));
        }
        header('Location: ' . $bnUrl . '&revoked=1');
        exit;
    } catch (Exception $e) {
        $errorMsg = 'Error al revocar: ' . $e->getMessage();
    }
}

// --- RENDERIZADO ---
echo '<div class="row">';

    // COLUMNA IZQUIERDA (FORMULARIO)
    echo '<div class="col-md-4">';

        if(isset($_GET['success'])) {
            echo '<div class="alert-custom alert-success-custom"><div class="alert-icon"><i class="fa fa-check"></i></div><div class="alert-content">Sanción aplicada correctamente.</div></div>';
        }
        if(isset($_GET['revoked'])) {
            echo '<div class="alert-custom alert-warning-custom"><div class="alert-icon"><i class="fa fa-unlock"></i></div><div class="alert-content">Sanción revocada y personaje liberado.</div></div>';
        }
        if($errorMsg !== '') {
            echo '<div class="alert-custom alert-error-custom"><div class="alert-icon"><i class="fa fa-exclamation-triangle"></i></div><div class="alert-content">' . $bnH($errorMsg) . '</div></div>';
        }

        echo '<div class="settings-card">';
            echo '<div class="card-header-custom">';
                echo '<div class="header-icon"><i class="fa fa-gavel"></i></div>';
                echo '<div class="header-text"><h3>Sancionar Personaje</h3><p>Bloqueo de acceso (CtlCode)</p></div>';
            echo '</div>';

            echo '<form action="' . $bnH($bnUrl) . '" method="post" class="card-body-custom">';

                echo '<div class="form-group mb-3">';
                    echo '<label class="input-label">NOMBRE DEL PERSONAJE</label>';
                    echo '<div class="input-group-custom"><span class="input-icon"><i class="fa fa-user"></i></span>';
                    echo '<input type="text" name="ban_char" id="ban_char" class="form-input" maxlength="10" placeholder="Ej: PlayerOne" required></div>';
                echo '</div>';

                echo '<div class="form-group mb-3">';
                    echo '<label class="input-label">RAZÓN / MOTIVO</label>';
                    echo '<div class="input-group-custom"><span class="input-icon"><i class="fa fa-commenting-o"></i></span>';
                    echo '<input type="text" name="ban_reason" id="ban_reason" class="form-input" maxlength="100" placeholder="Ej: Uso de hacks..." required></div>';
                echo '</div>';

                echo '<div class="form-group mb-3">';
                    echo '<label class="input-label">TIPO DE SANCIÓN</label>';
                    echo '<div class="select-wrapper">';
                        echo '<select name="ban_type" id="ban_type_select" class="form-select-custom" onchange="toggleDurationField()">';
                            echo '<option value="permanent">Permanente</option>';
                            echo '<option value="temporal">Temporal</option>';
                        echo '</select>';
                        echo '<i class="fa fa-chevron-down select-arrow"></i>';
                    echo '</div>';
                echo '</div>';

                echo '<div class="form-group mb-3" id="duration_container" style="display: none;">';
                    echo '<label class="input-label">DURACIÓN DEL BLOQUEO</label>';
                    echo '<div class="input-group-custom group-joined"><span class="input-icon"><i class="fa fa-clock-o"></i></span>';
                    echo '<input type="number" name="ban_days" id="ban_days" class="form-input" min="1" max="3650" placeholder="Cant.">';
                    echo '<span class="input-addon">DÍAS</span></div>';
                echo '</div>';

                echo '<div class="mt-4"><button type="submit" name="submit_ban" class="btn-submit-ban">EJECUTAR SANCIÓN <i class="fa fa-arrow-right"></i></button></div>';

            echo '</form>';
        echo '</div>';
    echo '</div>';

    // COLUMNA DERECHA (TABLA)
    echo '<div class="col-md-8">';
        echo '<div class="settings-card">';
            echo '<div class="card-header-custom header-dark">';
                echo '<div class="header-icon icon-yellow"><i class="fa fa-list-ul"></i></div>';
                echo '<div class="header-text"><h3>Historial de Sanciones</h3><p>Lista de personajes bloqueados actualmente</p></div>';
            echo '</div>';

            echo '<div class="table-responsive">';
                echo '<table class="table-bans">';
                    echo '<thead><tr>';
                        echo '<th style="padding-left:25px;">PERSONAJE</th><th>RAZÓN</th><th class="text-center">DURACIÓN</th><th>VENCIMIENTO</th><th class="text-end" style="padding-right:25px;">GESTIÓN</th>';
                    echo '</tr></thead>';
                    echo '<tbody>';

                    if(!empty($currentBans)) {
                        foreach($currentBans as $ban) {
                            if(!is_array($ban) || !isset($ban['name'])) continue;
                            $isPerm = (!isset($ban['type']) || $ban['type'] === 'permanent');
                            $banDays = isset($ban['days']) ? (int)$ban['days'] : 0;
                            $banReason = isset($ban['reason']) ? $ban['reason'] : '';
                            $expTs = (!$isPerm && !empty($ban['expire_date'])) ? strtotime($ban['expire_date']) : 0;

                            if($isPerm) {
                                $expireTxt = '<span class="text-infinity">&infin;</span>';
                            } elseif($expTs && $expTs < time()) {
                                $expireTxt = '<span class="badge-expired">EXPIRADO</span>';
                            } else {
                                $expireTxt = $expTs ? date('d/m/Y', $expTs) : '-';
                            }

                            echo '<tr>';
                                echo '<td style="padding-left:25px;"><div class="char-info"><div class="char-avatar"><i class="fa fa-user"></i></div><span class="char-name">' . $bnH($ban['name']) . '</span></div></td>';
                                echo '<td class="text-reason" title="' . $bnH($banReason) . '">' . $bnH($banReason) . '</td>';
                                echo '<td class="text-center">' . ($isPerm ? '<span class="badge-perm">PERMANENTE</span>' : '<span class="badge-temp">' . $banDays . ' DÍAS</span>') . '</td>';
                                echo '<td class="text-date">' . $expireTxt . '</td>';
                                echo '<td class="text-end" style="padding-right:25px;"><div class="action-buttons">';

                                    echo '<button type="button" class="btn-icon btn-edit js-ban-edit" title="Editar datos"'
                                       . ' data-name="' . $bnH($ban['name']) . '" data-reason="' . $bnH($banReason) . '"'
                                       . ' data-type="' . ($isPerm ? 'permanent' : 'temporal') . '" data-days="' . $banDays . '">'
                                       . '<i class="fa fa-pencil"></i></button>';

                                    echo '<form method="post" action="' . $bnH($bnUrl) . '" class="bn-revoke-form" data-name="' . $bnH($ban['name']) . '">'
                                       . '<input type="hidden" name="revoke_char" value="' . $bnH($ban['name']) . '">'
                                       . '<button type="submit" class="btn-icon btn-revoke" title="Revocar Sanción"><i class="fa fa-unlock"></i></button>'
                                       . '</form>';

                                echo '</div></td>';
                            echo '</tr>';
                        }
                    } else {
                        echo '<tr><td colspan="5" class="empty-state"><i class="fa fa-shield"></i><p>No hay sanciones registradas</p></td></tr>';
                    }

                    echo '</tbody>';
                echo '</table>';
            echo '</div>';
        echo '</div>';
    echo '</div>';

echo '</div>';

// Ventana de confirmacion propia
echo '<div id="bnModal" class="bnm-overlay" role="dialog" aria-modal="true">';
    echo '<div class="bnm-box">';
        echo '<div class="bnm-icon"><i class="fa fa-unlock"></i></div>';
        echo '<h4>Revocar sanción</h4>';
        echo '<p id="bnmText"></p>';
        echo '<div class="bnm-btns">';
            echo '<button type="button" class="bnm-no" id="bnmNo">Cancelar</button>';
            echo '<button type="button" class="bnm-yes" id="bnmYes">Desbloquear</button>';
        echo '</div>';
    echo '</div>';
echo '</div>';

// SCRIPTS
echo '<script>
function toggleDurationField() {
    var temporal = document.getElementById("ban_type_select").value === "temporal";
    var c = document.getElementById("duration_container");
    c.style.display = temporal ? "block" : "none";
    if(temporal) c.classList.add("fade-in");
}

document.addEventListener("click", function(e) {
    var b = e.target.closest(".js-ban-edit");
    if(!b) return;
    document.getElementById("ban_char").value = b.getAttribute("data-name");
    document.getElementById("ban_reason").value = b.getAttribute("data-reason");
    document.getElementById("ban_type_select").value = b.getAttribute("data-type");
    document.getElementById("ban_days").value = b.getAttribute("data-days");
    toggleDurationField();
    window.scrollTo({ top: 0, behavior: "smooth" });
    var card = document.querySelector(".col-md-4 .settings-card");
    if(card) { card.style.borderColor = "#f39c12"; setTimeout(function(){ card.style.borderColor = ""; }, 1000); }
});

(function(){
    var modal = document.getElementById("bnModal"), pendingForm = null;
    function closeModal(){ modal.classList.remove("is-open"); pendingForm = null; }
    document.addEventListener("submit", function(e){
        var f = e.target;
        if(!f.classList || !f.classList.contains("bn-revoke-form")) return;
        if(f.getAttribute("data-ok") === "1") return;
        e.preventDefault();
        pendingForm = f;
        document.getElementById("bnmText").textContent = "\u00BFConfirm\u00E1s desbloquear a " + f.getAttribute("data-name") + "?";
        modal.classList.add("is-open");
    }, true);
    document.getElementById("bnmNo").onclick = closeModal;
    document.getElementById("bnmYes").onclick = function(){
        var f = pendingForm;
        closeModal();
        if(!f) return;
        f.setAttribute("data-ok", "1");
        if(f.requestSubmit) f.requestSubmit(); else f.submit();
    };
    modal.addEventListener("click", function(e){ if(e.target === modal) closeModal(); });
    document.addEventListener("keydown", function(e){ if(e.key === "Escape" && modal.classList.contains("is-open")) closeModal(); });
})();
</script>';

// ESTILOS
echo '<style>
    .settings-card { background: var(--ad-surface, #0f1115); border: 1px solid var(--ad-line, #1f2733); border-radius: 10px; margin-bottom: 25px; overflow: hidden; box-shadow: none; transition: border-color 0.3s; }
    .card-header-custom { padding: 18px 22px; background: transparent; border-bottom: 1px solid var(--ad-line, #1f2733); display: flex; align-items: center; gap: 14px; }
    .header-dark { background: transparent; }
    .header-icon { width: 44px; height: 44px; background: rgba(239, 68, 68, 0.1); color: #ef4444; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 19px; border: 1px solid rgba(239, 68, 68, 0.2); flex-shrink: 0; }
    .header-icon.icon-yellow { background: rgba(245, 158, 11, 0.1); color: #f59e0b; border-color: rgba(245, 158, 11, 0.2); }
    .header-text h3 { margin: 0; font-size: 15px; font-weight: 700; color: #fff; text-transform: uppercase; letter-spacing: 0.5px; }
    .header-text p { margin: 3px 0 0 0; font-size: 12px; color: var(--ad-muted, #8b95a7); font-weight: 500; }
    .card-body-custom { padding: 22px; display: block; }
    .card-body-custom .form-group { margin-bottom: 16px; }
    .input-label { display: block; color: var(--ad-muted, #8b95a7); font-size: 11px; font-weight: 700; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px; }
    .input-group-custom { display: flex; align-items: center; background: var(--ad-field, #0b0d12); border: 1px solid var(--ad-line-strong, #2a3545); border-radius: 8px; padding: 0; transition: 0.3s; overflow: hidden; }
    .input-group-custom:focus-within { border-color: #ef4444; box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.15); }
    .input-icon { width: 44px; height: 44px; display: flex; align-items: center; justify-content: center; color: var(--ad-muted, #8b95a7); font-size: 14px; border-right: 1px solid var(--ad-line, #1f2733); background: transparent; flex-shrink: 0; }
    .form-input { flex: 1; background: transparent; border: none; color: #fff; padding: 0 14px; font-size: 14px; font-weight: 500; width: 100%; height: 44px; outline: none; }
    .form-input::placeholder { color: #566074; }
    .select-wrapper { position: relative; }
    .form-select-custom { width: 100%; background: var(--ad-field, #0b0d12); border: 1px solid var(--ad-line-strong, #2a3545); border-radius: 8px; padding: 0 14px; color: #fff; -webkit-appearance: none; appearance: none; font-size: 14px; cursor: pointer; outline: none; transition: 0.3s; height: 44px; }
    .form-select-custom:focus { border-color: #ef4444; }
    .form-select-custom option { background: #0b0d12; color: #fff; }
    .select-arrow { position: absolute; right: 15px; top: 50%; transform: translateY(-50%); color: var(--ad-muted, #8b95a7); pointer-events: none; font-size: 12px; }
    .group-joined .form-input { border-radius: 0; }
    .input-addon { background: transparent; color: #f59e0b; font-size: 11px; font-weight: 800; padding: 0 15px; display: flex; align-items: center; justify-content: center; border-left: 1px solid var(--ad-line, #1f2733); letter-spacing: 0.5px; height: 44px; }
    .btn-submit-ban { width: 100%; margin-top: 8px; background: linear-gradient(135deg, #ef4444, #c0392b); border: none; color: #fff; padding: 13px; border-radius: 8px; font-weight: 700; font-size: 13px; text-transform: uppercase; letter-spacing: 1px; cursor: pointer; transition: 0.3s; box-shadow: 0 4px 15px rgba(239, 68, 68, 0.25); }
    .btn-submit-ban:hover { transform: translateY(-1px); box-shadow: 0 6px 20px rgba(239, 68, 68, 0.45); }
    .table-bans { width: 100%; border-collapse: separate; border-spacing: 0; }
    .table-bans th { background: transparent; color: var(--ad-muted, #8b95a7); font-size: 11px; font-weight: 700; text-transform: uppercase; padding: 14px 15px; border-bottom: 1px solid var(--ad-line-strong, #2a3545); letter-spacing: 0.5px; text-align: left; white-space: nowrap; }
    .table-bans td { padding: 14px 15px; border-bottom: 1px solid var(--ad-line, #1f2733); vertical-align: middle; color: var(--ad-text, #e5e7eb); font-size: 13px; }
    .table-bans tr:last-child td { border-bottom: none; }
    .table-bans tr:hover td { background: rgba(255,255,255,0.02); }
    .char-info { display: flex; align-items: center; gap: 12px; }
    .char-avatar { width: 32px; height: 32px; background: var(--ad-field, #0b0d12); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: var(--ad-muted, #8b95a7); border: 1px solid var(--ad-line-strong, #2a3545); flex-shrink: 0; }
    .char-name { font-weight: 700; color: #fff; }
    .text-reason { color: var(--ad-muted, #8b95a7); font-style: italic; font-size: 12px; max-width: 150px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .badge-perm { background: rgba(239, 68, 68, 0.15); color: #ef4444; padding: 5px 10px; border-radius: 999px; font-size: 10px; font-weight: 800; border: 1px solid rgba(239, 68, 68, 0.3); white-space: nowrap; }
    .badge-temp { background: rgba(245, 158, 11, 0.15); color: #f59e0b; padding: 5px 10px; border-radius: 999px; font-size: 10px; font-weight: 800; border: 1px solid rgba(245, 158, 11, 0.3); white-space: nowrap; }
    .badge-expired { color: var(--ad-muted, #8b95a7); font-weight: bold; font-size: 10px; border: 1px solid var(--ad-line-strong, #2a3545); padding: 2px 6px; border-radius: 4px; }
    .text-infinity { font-size: 18px; line-height: 0; position: relative; top: 2px; color: #ef4444; }
    .action-buttons { display: flex; justify-content: flex-end; gap: 8px; }
    .action-buttons form { margin: 0; display: inline-flex; }
    #page-wrapper .table-bans .btn-icon { width: 34px; height: 34px; padding: 0; border-radius: 8px; display: inline-flex; align-items: center; justify-content: center; cursor: pointer; transition: 0.2s; font-size: 14px; text-decoration: none; float: none; }
    #page-wrapper .table-bans .btn-icon.btn-edit { background: transparent; border: 1px solid var(--ad-line-strong, #2a3545); color: var(--ad-text, #e5e7eb); }
    #page-wrapper .table-bans .btn-icon.btn-edit:hover { border-color: var(--ad-info, #3b82f6); color: #fff; }
    #page-wrapper .table-bans .btn-icon.btn-revoke { background: rgba(16, 185, 129, 0.15); border: 1px solid rgba(16, 185, 129, 0.3); color: #10b981; }
    #page-wrapper .table-bans .btn-icon.btn-revoke:hover { background: #10b981; color: #fff; }
    .empty-state { text-align: center; padding: 40px !important; color: var(--ad-muted, #8b95a7); }
    .empty-state i { font-size: 36px; margin-bottom: 10px; opacity: 0.5; }
    .alert-custom { display: flex; align-items: center; padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; background: var(--ad-surface, #0f1115); border: 1px solid var(--ad-line, #1f2733); color: var(--ad-text, #e5e7eb); animation: fadeIn 0.5s ease; }
    .alert-icon { width: 30px; font-size: 16px; margin-right: 8px; text-align: center; }
    .alert-content { font-weight: 500; font-size: 14px; }
    .alert-custom.alert-success-custom { border-left: 3px solid #10b981; }
    .alert-custom.alert-success-custom .alert-icon { color: #10b981; }
    .alert-custom.alert-warning-custom { border-left: 3px solid #f59e0b; }
    .alert-custom.alert-warning-custom .alert-icon { color: #f59e0b; }
    .alert-custom.alert-error-custom { border-left: 3px solid #ef4444; }
    .alert-custom.alert-error-custom .alert-icon { color: #ef4444; }
    .bnm-overlay { display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; z-index: 2000; padding: 16px; background: rgba(0,0,0,0.7); align-items: center; justify-content: center; }
    .bnm-overlay.is-open { display: flex; }
    .bnm-box { width: 100%; max-width: 380px; background: #0f1115; border: 1px solid #2a3545; border-radius: 10px; padding: 26px 22px; text-align: center; box-shadow: 0 20px 60px rgba(0,0,0,0.6); }
    .bnm-icon { width: 52px; height: 52px; margin: 0 auto 14px; border-radius: 50%; display: flex; align-items: center; justify-content: center; background: rgba(16,185,129,0.12); color: #10b981; font-size: 22px; }
    .bnm-box h4 { color: #fff; font-size: 16px; font-weight: 700; margin: 0 0 6px; }
    .bnm-box p { color: #8b95a7; font-size: 13px; margin: 0; }
    .bnm-btns { display: flex; justify-content: center; gap: 10px; margin-top: 22px; }
    .bnm-btns button { height: 38px; padding: 0 18px; border-radius: 8px; font-size: 13px; font-weight: 600; cursor: pointer; }
    .bnm-no { background: transparent; border: 1px solid #2a3545; color: #e5e7eb; }
    .bnm-no:hover { border-color: #8b95a7; }
    .bnm-yes { background: #10b981; border: 0; color: #fff; }
    .bnm-yes:hover { background: #0ea371; }
    @keyframes fadeIn { from { opacity:0; transform:translateY(-10px); } to { opacity:1; transform:translateY(0); } }
    .fade-in { animation: fadeIn 0.3s ease; }
    input[type=number]::-webkit-outer-spin-button,
    input[type=number]::-webkit-inner-spin-button { -webkit-appearance: none; margin: 0; }
    input[type=number] { -moz-appearance: textfield; }
</style>';