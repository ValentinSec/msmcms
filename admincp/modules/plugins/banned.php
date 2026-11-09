<?php
/**
 * Banned Manager Plugin - UI Remastered v2 (High Contrast)
 * PREPARED FOR OBFUSCATION (Pure PHP Version)
 */

$bannedFile = __PATH_INCLUDES__ . 'config/banned_list.json';
$currentBans = file_exists($bannedFile) ? json_decode(file_get_contents($bannedFile), true) : [];
if(!is_array($currentBans)) $currentBans = [];

// --- 1. LÓGICA DE BANNEO (INTACTA) ---
if(isset($_POST['submit_ban'])) {
    try {
        $charName = $_POST['ban_char'];
        $reason   = $_POST['ban_reason'];
        $type     = $_POST['ban_type']; 
        $days     = ($type == 'temporal') ? (int)$_POST['ban_days'] : 0;
        
        $db = Connection::Database('MuOnline');
        
        $check = $db->query_fetch_single("SELECT Name FROM Character WHERE Name = ?", [$charName]);
        if(!$check) throw new Exception("El personaje no existe.");

        $db->query("UPDATE Character SET CtlCode = 1 WHERE Name = ?", [$charName]);

        $expireDate = null;
        if($type == 'temporal' && $days > 0) {
            $expireDate = date('Y-m-d H:i:s', strtotime("+$days days"));
        }

        $currentBans[$charName] = [
            'name' => $charName,
            'reason' => $reason,
            'type' => $type,
            'days' => $days,
            'date_banned' => date('Y-m-d H:i:s'),
            'expire_date' => $expireDate
        ];

        file_put_contents($bannedFile, json_encode($currentBans, JSON_PRETTY_PRINT));
        header("Location: index.php?module=my_plugins&load_plugin=banned&success=1");
        exit;

    } catch (Exception $e) {
        $errorMsg = $e->getMessage();
    }
}

// --- 2. LÓGICA DE REVOCAR (INTACTA) ---
if(isset($_GET['revoke'])) {
    try {
        $target = $_GET['revoke'];
        $db = Connection::Database('MuOnline');
        $db->query("UPDATE Character SET CtlCode = 0 WHERE Name = ?", [$target]);

        if(isset($currentBans[$target])) {
            unset($currentBans[$target]);
            file_put_contents($bannedFile, json_encode($currentBans, JSON_PRETTY_PRINT));
        }

        header("Location: index.php?module=my_plugins&load_plugin=banned&revoked=1");
        exit;

    } catch (Exception $e) {
        $errorMsg = "Error al revocar: " . $e->getMessage();
    }
}

// --- RENDERIZADO HTML (PHP PURO) ---

echo '<div class="row">';
    
    // COLUMNA IZQUIERDA (FORMULARIO)
    echo '<div class="col-md-4">';
        
        // Alertas
        if(isset($_GET['success'])) {
            echo '<div class="alert-custom alert-success-custom">';
            echo '<div class="alert-icon"><i class="fa fa-check"></i></div>';
            echo '<div class="alert-content">Sanción aplicada correctamente.</div>';
            echo '</div>';
        }

        if(isset($_GET['revoked'])) {
            echo '<div class="alert-custom alert-warning-custom">';
            echo '<div class="alert-icon"><i class="fa fa-unlock"></i></div>';
            echo '<div class="alert-content">Sanción revocada y personaje liberado.</div>';
            echo '</div>';
        }

        if(isset($errorMsg)) {
            echo '<div class="alert-custom alert-error-custom">';
            echo '<div class="alert-icon"><i class="fa fa-exclamation-triangle"></i></div>';
            echo '<div class="alert-content">' . $errorMsg . '</div>';
            echo '</div>';
        }

        // Tarjeta Formulario
        echo '<div class="settings-card">';
            echo '<div class="card-header-custom">';
                echo '<div class="header-icon"><i class="fa fa-gavel"></i></div>';
                echo '<div class="header-text">';
                    echo '<h3>Sancionar Personaje</h3>';
                    echo '<p>Bloqueo de acceso (CtlCode)</p>';
                echo '</div>';
            echo '</div>';
            
            echo '<form action="" method="post" class="card-body-custom">';
                
                // Input Nombre
                echo '<div class="form-group mb-3">';
                    echo '<label class="input-label">NOMBRE DEL PERSONAJE</label>';
                    echo '<div class="input-group-custom">';
                        echo '<span class="input-icon"><i class="fa fa-user"></i></span>';
                        echo '<input type="text" name="ban_char" id="ban_char" class="form-input" placeholder="Ej: PlayerOne" required>';
                    echo '</div>';
                echo '</div>';

                // Input Razón
                echo '<div class="form-group mb-3">';
                    echo '<label class="input-label">RAZÓN / MOTIVO</label>';
                    echo '<div class="input-group-custom">';
                        echo '<span class="input-icon"><i class="fa fa-comment-dots"></i></span>';
                        echo '<input type="text" name="ban_reason" id="ban_reason" class="form-input" placeholder="Ej: Uso de hacks..." required>';
                    echo '</div>';
                echo '</div>';

                // Select Tipo
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

                // Input Duración (Hidden by default)
                echo '<div class="form-group mb-3" id="duration_container" style="display: none;">';
                    echo '<label class="input-label">DURACIÓN DEL BLOQUEO</label>';
                    echo '<div class="input-group-custom group-joined">';
                        echo '<span class="input-icon"><i class="fa fa-clock"></i></span>';
                        echo '<input type="number" name="ban_days" id="ban_days" class="form-input" placeholder="Cant.">';
                        echo '<span class="input-addon">DÍAS</span>';
                    echo '</div>';
                echo '</div>';

                // Submit Button
                echo '<div class="mt-4">';
                    echo '<button type="submit" name="submit_ban" class="btn-submit-ban">';
                    echo 'EJECUTAR SANCIÓN <i class="fa fa-arrow-right ms-2"></i>';
                    echo '</button>';
                echo '</div>';

            echo '</form>';
        echo '</div>'; // End settings-card
    echo '</div>'; // End col-md-4

    // COLUMNA DERECHA (TABLA)
    echo '<div class="col-md-8">';
        echo '<div class="settings-card">';
            echo '<div class="card-header-custom header-dark">';
                echo '<div class="header-icon icon-yellow"><i class="fa fa-list-ul"></i></div>';
                echo '<div class="header-text">';
                    echo '<h3>Historial de Sanciones</h3>';
                    echo '<p>Lista de personajes bloqueados actualmente</p>';
                echo '</div>';
            echo '</div>';

            echo '<div class="table-responsive">';
                echo '<table class="table-bans">';
                    echo '<thead>';
                        echo '<tr>';
                            echo '<th style="padding-left:25px;">PERSONAJE</th>';
                            echo '<th>RAZÓN</th>';
                            echo '<th class="text-center">DURACIÓN</th>';
                            echo '<th>VENCIMIENTO</th>';
                            echo '<th class="text-end" style="padding-right:25px;">GESTIÓN</th>';
                        echo '</tr>';
                    echo '</thead>';
                    echo '<tbody>';
                        
                        if(!empty($currentBans)) {
                            foreach($currentBans as $ban) {
                                $isPerm = ($ban['type'] === 'permanent');
                                $expireTxt = $isPerm ? '<span class="text-infinity">∞</span>' : date('d/m/Y', strtotime($ban['expire_date']));
                                
                                // Verificar expirado visualmente
                                if(!$isPerm && strtotime($ban['expire_date']) < time()) {
                                    $expireTxt = "<span class='badge-expired'>EXPIRADO</span>";
                                }

                                echo '<tr>';
                                    echo '<td style="padding-left:25px;">';
                                        echo '<div class="char-info">';
                                            echo '<div class="char-avatar"><i class="fa fa-user"></i></div>';
                                            echo '<span class="char-name">' . htmlspecialchars($ban['name']) . '</span>';
                                        echo '</div>';
                                    echo '</td>';
                                    echo '<td class="text-reason">' . htmlspecialchars($ban['reason']) . '</td>';
                                    echo '<td class="text-center">';
                                        if($isPerm) {
                                            echo '<span class="badge-perm">PERMANENTE</span>';
                                        } else {
                                            echo '<span class="badge-temp">' . $ban['days'] . ' DÍAS</span>';
                                        }
                                    echo '</td>';
                                    echo '<td class="text-date">' . $expireTxt . '</td>';
                                    echo '<td class="text-end" style="padding-right:25px;">';
                                        echo '<div class="action-buttons">';
                                            
                                            // Botón Editar (Carga datos al form)
                                            echo '<button type="button" class="btn-icon btn-edit" ';
                                            echo 'onclick="editBan(\'' . $ban['name'] . '\', \'' . $ban['reason'] . '\', \'' . $ban['type'] . '\', \'' . $ban['days'] . '\')" ';
                                            echo 'data-bs-toggle="tooltip" title="Editar datos">';
                                            echo '<i class="fa fa-pencil-alt"></i>';
                                            echo '</button>';
                                            
                                            // Botón Revocar
                                            echo '<a href="index.php?module=my_plugins&load_plugin=banned&revoke=' . $ban['name'] . '" ';
                                            echo 'class="btn-icon btn-revoke" ';
                                            echo 'onclick="return confirm(\'¿Confirmas desbloquear a ' . $ban['name'] . '?\');" ';
                                            echo 'data-bs-toggle="tooltip" title="Revocar Sanción">';
                                            echo '<i class="fa fa-unlock"></i>';
                                            echo '</a>';

                                        echo '</div>';
                                    echo '</td>';
                                echo '</tr>';
                            }
                        } else {
                            echo '<tr>';
                                echo '<td colspan="5" class="empty-state">';
                                    echo '<i class="fa fa-shield-alt"></i>';
                                    echo '<p>No hay sanciones registradas</p>';
                                echo '</td>';
                            echo '</tr>';
                        }

                    echo '</tbody>';
                echo '</table>';
            echo '</div>'; // End table-responsive
        echo '</div>'; // End settings-card
    echo '</div>'; // End col-md-8

echo '</div>'; // End row

// SCRIPTS JS
echo '<script>
function toggleDurationField() {
    var select = document.getElementById("ban_type_select");
    var container = document.getElementById("duration_container");
    
    if(select.value === "temporal") {
        container.style.display = "block";
        container.classList.add("fade-in");
    } else {
        container.style.display = "none";
    }
}

function editBan(name, reason, type, days) {
    document.getElementById("ban_char").value = name;
    document.getElementById("ban_reason").value = reason;
    document.getElementById("ban_type_select").value = type;
    document.getElementById("ban_days").value = days;
    toggleDurationField();
    window.scrollTo({ top: 0, behavior: "smooth" });
    
    // Efecto visual
    const card = document.querySelector(".col-md-4 .settings-card");
    card.style.borderColor = "#f39c12";
    setTimeout(() => { card.style.borderColor = "#2d323e"; }, 1000);
}

document.addEventListener("DOMContentLoaded", function(){
    if(typeof bootstrap !== "undefined" && bootstrap.Tooltip) {
        var tooltipTriggerList = [].slice.call(document.querySelectorAll(\'[data-bs-toggle="tooltip"]\'));
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    }
});
</script>';

// ESTILOS CSS
echo '<style>
    /* TARJETAS */
    .settings-card { background: #1c1f26; border: 1px solid #2d323e; border-radius: 12px; margin-bottom: 25px; overflow: hidden; box-shadow: 0 10px 25px rgba(0,0,0,0.3); transition: border-color 0.3s; }
    
    /* CABECERA */
    .card-header-custom { padding: 20px 25px; background: rgba(30, 33, 42, 0.5); border-bottom: 1px solid #2d323e; display: flex; align-items: center; gap: 15px; }
    .header-dark { background: #15171c; }
    
    .header-icon { width: 45px; height: 45px; background: rgba(231, 76, 60, 0.1); color: #e74c3c; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 20px; border: 1px solid rgba(231, 76, 60, 0.2); }
    .header-icon.icon-yellow { background: rgba(243, 156, 18, 0.1); color: #f39c12; border-color: rgba(243, 156, 18, 0.2); }
    
    .header-text h3 { margin: 0; font-size: 16px; font-weight: 700; color: #fff; text-transform: uppercase; letter-spacing: 0.5px; }
    .header-text p { margin: 3px 0 0 0; font-size: 12px; color: #7f8fa4; font-weight: 500; }

    /* FORMULARIO */
    .card-body-custom { padding: 25px; }
    
    .input-label { display: block; color: #8a8d93; font-size: 11px; font-weight: 700; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px; }
    
    .input-group-custom { display: flex; align-items: center; background: #15171c; border: 1px solid #2d323e; border-radius: 8px; padding: 0; transition: 0.3s; overflow: hidden; }
    .input-group-custom:focus-within { border-color: #e74c3c; box-shadow: 0 0 0 3px rgba(231, 76, 60, 0.15); }
    
    .input-icon { width: 45px; display: flex; align-items: center; justify-content: center; color: #7f8fa4; font-size: 14px; border-right: 1px solid #2d323e; background: #232730; height: 45px; }
    
    .form-input { flex: 1; background: transparent; border: none; color: #fff; padding: 0 15px; font-size: 14px; font-weight: 500; width: 100%; height: 45px; outline: none; }
    .form-input::placeholder { color: #444; }

    /* Select */
    .select-wrapper { position: relative; }
    .form-select-custom { width: 100%; background: #15171c; border: 1px solid #2d323e; border-radius: 8px; padding: 0 15px; color: #fff; appearance: none; font-size: 14px; cursor: pointer; outline: none; transition: 0.3s; height: 45px; }
    .form-select-custom:focus { border-color: #e74c3c; }
    .select-arrow { position: absolute; right: 15px; top: 50%; transform: translateY(-50%); color: #555; pointer-events: none; font-size: 12px; }

    /* Input Addon */
    .group-joined .form-input { border-radius: 0; }
    .input-addon { background: #232730; color: #f39c12; font-size: 11px; font-weight: 800; padding: 0 15px; display: flex; align-items: center; justify-content: center; border-left: 1px solid #2d323e; letter-spacing: 0.5px; height: 45px; }

    /* Botón */
    .btn-submit-ban { width: 100%; background: linear-gradient(135deg, #e74c3c, #c0392b); border: none; color: #fff; padding: 14px; border-radius: 8px; font-weight: 700; font-size: 13px; text-transform: uppercase; letter-spacing: 1px; cursor: pointer; transition: 0.3s; box-shadow: 0 4px 15px rgba(231, 76, 60, 0.3); }
    .btn-submit-ban:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(231, 76, 60, 0.5); }

    /* TABLA */
    .table-bans { width: 100%; border-collapse: separate; border-spacing: 0; }
    .table-bans th { background: #15171c; color: #5c6270; font-size: 11px; font-weight: 700; text-transform: uppercase; padding: 15px; border-bottom: 2px solid #2d323e; letter-spacing: 0.5px; text-align: left; }
    .table-bans td { padding: 15px; border-bottom: 1px solid #242830; vertical-align: middle; color: #ccc; font-size: 13px; }
    .table-bans tr:last-child td { border-bottom: none; }
    .table-bans tr:hover td { background: rgba(255,255,255,0.02); }

    /* Elementos Tabla */
    .char-info { display: flex; align-items: center; gap: 12px; }
    .char-avatar { width: 32px; height: 32px; background: #232730; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #777; border: 1px solid #2d323e; }
    .char-name { font-weight: 700; color: #fff; }
    .text-reason { color: #888; font-style: italic; font-size: 12px; max-width: 150px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    
    /* Badges */
    .badge-perm { background: rgba(231, 76, 60, 0.15); color: #e74c3c; padding: 5px 10px; border-radius: 4px; font-size: 10px; font-weight: 800; border: 1px solid rgba(231, 76, 60, 0.3); }
    .badge-temp { background: rgba(243, 156, 18, 0.15); color: #f39c12; padding: 5px 10px; border-radius: 4px; font-size: 10px; font-weight: 800; border: 1px solid rgba(243, 156, 18, 0.3); }
    .badge-expired { color: #555; font-weight: bold; font-size: 10px; border: 1px solid #333; padding: 2px 6px; border-radius: 4px; }
    .text-infinity { font-size: 18px; line-height: 0; position: relative; top: 2px; color: #e74c3c; }

    /* Action Buttons */
    .action-buttons { display: flex; justify-content: flex-end; gap: 8px; }
    .btn-icon { width: 34px; height: 34px; border-radius: 6px; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: 0.2s; font-size: 14px; text-decoration: none; border:none; }
    
    .btn-edit { background: rgba(52, 152, 219, 0.15); color: #3498db; }
    .btn-edit:hover { background: #3498db; color: #fff; transform: translateY(-2px); box-shadow: 0 4px 10px rgba(52, 152, 219, 0.3); }
    
    .btn-revoke { background: rgba(46, 204, 113, 0.15); color: #2ecc71; }
    .btn-revoke:hover { background: #2ecc71; color: #fff; transform: translateY(-2px); box-shadow: 0 4px 10px rgba(46, 204, 113, 0.3); }

    /* Empty State */
    .empty-state { text-align: center; padding: 40px !important; color: #444; }
    .empty-state i { font-size: 40px; margin-bottom: 10px; opacity: 0.5; }
    
    /* Alertas */
    .alert-custom { display: flex; align-items: center; padding: 15px; border-radius: 8px; margin-bottom: 20px; border: 1px solid transparent; animation: fadeIn 0.5s ease; }
    .alert-icon { width: 30px; font-size: 18px; margin-right: 10px; text-align: center; }
    .alert-content { font-weight: 500; font-size: 14px; }
    
    .alert-success-custom { background: #1b1b28; border-left: 4px solid #00d084; color: #fff; padding: 15px; margin-bottom: 20px; border-radius: 4px; box-shadow: 0 4px 10px rgba(0,0,0,0.2); }
    .alert-warning-custom { background: rgba(243, 156, 18, 0.1); border-color: rgba(243, 156, 18, 0.3); color: #f39c12; }
    .alert-error-custom { background: rgba(231, 76, 60, 0.1); border-color: rgba(231, 76, 60, 0.3); color: #e74c3c; }

    @keyframes fadeIn { from { opacity:0; transform:translateY(-10px); } to { opacity:1; transform:translateY(0); } }
    .fade-in { animation: fadeIn 0.3s ease; }
    
    /* FIX NUMBERS */
    input[type=number]::-webkit-outer-spin-button,
    input[type=number]::-webkit-inner-spin-button { -webkit-appearance: none; margin: 0; }
    input[type=number] { -moz-appearance: textfield; }
</style>';
?>