<?php
/**
 * module_events.php - Configuracion de eventos del servidor
 */
if(!defined('access') or !access) die();

// 1. Archivo de datos
$eventsFile = __PATH_INCLUDES__ . 'config/server_events.json';
if(!file_exists($eventsFile)) @file_put_contents($eventsFile, json_encode(array()));

$eventsData = json_decode((string)@file_get_contents($eventsFile), true);
if(!is_array($eventsData)) $eventsData = array();

$evH    = function($s) { return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); };
$evCut  = function($s, $len) { $s = trim((string)$s); return function_exists('mb_substr') ? mb_substr($s, 0, $len, 'UTF-8') : substr($s, 0, $len); };
$evTime = function($s) { $s = trim((string)$s); return preg_match('/^([01][0-9]|2[0-3]):[0-5][0-9]$/', $s) ? $s : ''; };
$evTypes = array('Daily', 'Weekly', 'Special');
$dias = array('Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo');

// 2. Guardar / actualizar
if(isset($_POST['save_event'])) {
    $eId = isset($_POST['event_id']) ? (string)$_POST['event_id'] : '';
    if($eId !== '' && !isset($eventsData[$eId])) $eId = '';

    $type   = (isset($_POST['event_type']) && in_array($_POST['event_type'], $evTypes, true)) ? $_POST['event_type'] : 'Daily';
    $dStart = (isset($_POST['day_start']) && in_array($_POST['day_start'], $dias, true)) ? $_POST['day_start'] : '';
    $dEnd   = (isset($_POST['day_end']) && in_array($_POST['day_end'], $dias, true)) ? $_POST['day_end'] : '';

    $newEvent = array(
        'name'       => $evCut(isset($_POST['event_name']) ? $_POST['event_name'] : '', 60),
        'type'       => $type,
        'times'      => $evCut(isset($_POST['event_times']) ? $_POST['event_times'] : '', 200),
        'day_start'  => $dStart,
        'time_start' => $evTime(isset($_POST['time_start']) ? $_POST['time_start'] : ''),
        'day_end'    => $dEnd,
        'time_end'   => $evTime(isset($_POST['time_end']) ? $_POST['time_end'] : '')
    );

    if($newEvent['name'] !== '') {
        $eventsData[($eId !== '') ? $eId : uniqid()] = $newEvent;
        file_put_contents($eventsFile, json_encode($eventsData));
    }
    header('Location: ' . admincp_base('module_events&status=success'));
    exit;
}

// 3. Borrar (solo por POST: queda cubierto por el token anti-CSRF del panel)
if(isset($_POST['delete_event'])) {
    $deleteId = (string)$_POST['delete_event'];
    if(isset($eventsData[$deleteId])) {
        unset($eventsData[$deleteId]);
        file_put_contents($eventsFile, json_encode($eventsData));
    }
    header('Location: ' . admincp_base('module_events&status=deleted'));
    exit;
}

// 4. Cargar datos para edicion (solo lectura)
$editEvent = array('id' => '', 'name' => '', 'type' => 'Daily', 'times' => '', 'day_start' => 'Lunes', 'time_start' => '', 'day_end' => 'Domingo', 'time_end' => '');
if(isset($_GET['edit'])) {
    $editId = (string)$_GET['edit'];
    if(isset($eventsData[$editId]) && is_array($eventsData[$editId])) {
        $editEvent = array_merge($editEvent, $eventsData[$editId]);
        $editEvent['id'] = $editId;
    }
}

$formAction = admincp_base('module_events');
$isEdit = ($editEvent['id'] !== '');
$title = $isEdit ? 'Editar Evento' : 'Agregar Nuevo Evento';

// --- RENDERIZADO ---
echo '<div class="container-fluid">';
    echo '<div class="row">';
        echo '<div class="col-lg-12">';
            echo '<div class="row" style="margin-bottom: 20px;">';
                echo '<div class="col-xs-12"><h3 style="color:#fff; font-weight:600;">Configuraci&oacute;n de Eventos</h3></div>';
            echo '</div>';

            $evMsgs = array('success' => 'Evento guardado correctamente.', 'deleted' => 'Evento eliminado.');
            if(isset($_GET['status']) && isset($evMsgs[$_GET['status']])) {
                echo '<div class="alert-success-custom">' . $evMsgs[$_GET['status']] . '</div>';
            }

            // Formulario
            echo '<div class="settings-section">';
                echo '<h3 class="settings-title">' . $title . '</h3>';
                echo '<form action="' . $evH($formAction) . '" method="post">';
                    echo '<input type="hidden" name="event_id" value="' . $evH($editEvent['id']) . '">';
                    echo '<div class="row">';
                        echo '<div class="col-md-4"><div class="form-group">';
                            echo '<label class="settings-label">Nombre del Evento</label>';
                            echo '<input type="text" name="event_name" class="form-control-dark" maxlength="60" value="' . $evH($editEvent['name']) . '" required>';
                        echo '</div></div>';

                        echo '<div class="col-md-3"><div class="form-group">';
                            echo '<label class="settings-label">Tipo de Cronograma</label>';
                            echo '<select name="event_type" id="event_type" class="form-control-dark" onchange="toggleWeeklyOptions()">';
                                echo '<option value="Daily" ' . ($editEvent['type'] == 'Daily' ? 'selected' : '') . '>Daily (Diario)</option>';
                                echo '<option value="Weekly" ' . ($editEvent['type'] == 'Weekly' ? 'selected' : '') . '>Weekly (Rango Semanal)</option>';
                                echo '<option value="Special" ' . ($editEvent['type'] == 'Special' ? 'selected' : '') . '>Special</option>';
                            echo '</select>';
                        echo '</div></div>';

                        echo '<div class="col-md-5" id="container-daily"><div class="form-group">';
                            echo '<label class="settings-label">Horarios (Ej: 15:00, 20:00)</label>';
                            echo '<input type="text" name="event_times" class="form-control-dark" maxlength="200" value="' . $evH($editEvent['times']) . '">';
                        echo '</div></div>';
                    echo '</div>';

                    // Rango semanal
                    echo '<div id="container-weekly" style="display:none; margin: 4px 0 20px; padding-top: 18px; border-top: 1px solid var(--ad-line, #1f2733);">';
                        echo '<div class="row">';
                            echo '<div class="col-md-3"><label class="settings-label">D&iacute;a de Inicio</label><select name="day_start" class="form-control-dark">';
                            foreach($dias as $d) echo '<option value="' . $evH($d) . '" ' . ($editEvent['day_start'] == $d ? 'selected' : '') . '>' . $evH($d) . '</option>';
                            echo '</select></div>';

                            echo '<div class="col-md-3"><label class="settings-label">Hora de Inicio</label><input type="time" name="time_start" class="form-control-dark" value="' . $evH($editEvent['time_start']) . '"></div>';

                            echo '<div class="col-md-3"><label class="settings-label">D&iacute;a de Fin</label><select name="day_end" class="form-control-dark">';
                            foreach($dias as $d) echo '<option value="' . $evH($d) . '" ' . ($editEvent['day_end'] == $d ? 'selected' : '') . '>' . $evH($d) . '</option>';
                            echo '</select></div>';

                            echo '<div class="col-md-3"><label class="settings-label">Hora de Fin</label><input type="time" name="time_end" class="form-control-dark" value="' . $evH($editEvent['time_end']) . '"></div>';
                        echo '</div>';
                    echo '</div>';

                    echo '<div class="row"><div class="col-md-12">';
                        echo '<button type="submit" name="save_event" class="btn-save-green"><i class="fa ' . ($isEdit ? 'fa-save' : 'fa-plus') . '"></i> ' . ($isEdit ? 'Actualizar Evento' : 'Agregar Evento') . '</button>';
                        if($isEdit) echo ' <a href="' . $evH($formAction) . '" class="btn btn-default" style="color:#fff;">Cancelar</a>';
                    echo '</div></div>';
                echo '</form>';
            echo '</div>';

            // Tabla
            echo '<div class="settings-section mt-4">';
                echo '<h3 class="settings-title">Cronograma Actual</h3>';
                echo '<table class="table table-dark-custom">';
                    echo '<thead><tr><th>Evento</th><th class="text-center">Tipo</th><th>Configuraci&oacute;n de Horario</th><th class="text-right">Acciones</th></tr></thead>';
                    echo '<tbody>';
                    if(empty($eventsData)) {
                        echo '<tr><td colspan="4" class="text-center" style="padding:20px;">No hay eventos cargados.</td></tr>';
                    }
                    foreach($eventsData as $id => $data) {
                        if(!is_array($data)) continue;
                        $type = isset($data['type']) ? $data['type'] : '';
                        if($type == 'Weekly') {
                            $timeDisplay = 'Desde ' . (isset($data['day_start']) ? $data['day_start'] : '') . ' ' . (isset($data['time_start']) ? $data['time_start'] : '')
                                         . ' hs hasta ' . (isset($data['day_end']) ? $data['day_end'] : '') . ' ' . (isset($data['time_end']) ? $data['time_end'] : '') . ' hs';
                        } else {
                            $timeDisplay = isset($data['times']) ? $data['times'] : '';
                        }
                        echo '<tr>';
                            echo '<td><div class="event-name-box">' . $evH(isset($data['name']) ? $data['name'] : '') . '</div></td>';
                            echo '<td class="text-center"><span class="badge-event-type">' . $evH($type) . '</span></td>';
                            echo '<td><span style="color:#cbd5e1; font-size:13px;">' . $evH($timeDisplay) . '</span></td>';
                            echo '<td class="text-right">';
                                echo '<a href="' . $evH(admincp_base('module_events&edit=' . rawurlencode((string)$id))) . '" class="btn-edit-blue">Editar</a> ';
                                echo '<a href="#" class="btn-delete-red" data-id="' . $evH($id) . '" onclick="openDeleteModal(this.getAttribute(\'data-id\')); return false;">Borrar</a>';
                            echo '</td>';
                        echo '</tr>';
                    }
                    echo '</tbody>';
                echo '</table>';
            echo '</div>';

        echo '</div>';
    echo '</div>';
echo '</div>';

// Ventana de confirmacion (envia un POST)
echo '<div id="customDeleteModal" class="evm-overlay">';
    echo '<form method="post" action="' . $evH($formAction) . '" class="evm-box">';
        echo '<input type="hidden" name="delete_event" id="evmDeleteId" value="">';
        echo '<div class="evm-icon"><i class="fa fa-trash"></i></div>';
        echo '<h4>&iquest;Eliminar este evento?</h4>';
        echo '<p>Esta acci&oacute;n no se puede deshacer.</p>';
        echo '<div class="evm-btns">';
            echo '<button type="button" class="evm-no" onclick="closeDeleteModal()">Cancelar</button>';
            echo '<button type="submit" class="evm-yes">Eliminar</button>';
        echo '</div>';
    echo '</form>';
echo '</div>';

echo '<style>
    .evm-overlay { display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; z-index: 2000; padding: 16px; background: rgba(0,0,0,0.7); justify-content: center; align-items: center; }
    .evm-box { width: 100%; max-width: 380px; margin: 0; background: #0f1115; border: 1px solid #2a3545; border-radius: 10px; padding: 26px 22px; text-align: center; box-shadow: 0 20px 60px rgba(0,0,0,0.6); }
    .evm-icon { width: 52px; height: 52px; margin: 0 auto 14px; border-radius: 50%; display: flex; align-items: center; justify-content: center; background: rgba(239,68,68,0.12); color: #ef4444; font-size: 22px; }
    .evm-box h4 { color: #fff; font-size: 16px; font-weight: 700; margin: 0 0 6px; }
    .evm-box p { color: #8b95a7; font-size: 13px; margin: 0; }
    .evm-btns { display: flex; justify-content: center; gap: 10px; margin-top: 22px; }
    .evm-btns button { height: 38px; padding: 0 18px; border-radius: 8px; font-size: 13px; font-weight: 600; cursor: pointer; }
    .evm-no { background: transparent; border: 1px solid #2a3545; color: #e5e7eb; }
    .evm-no:hover { border-color: #8b95a7; }
    .evm-yes { background: #ef4444; border: 0; color: #fff; }
    .evm-yes:hover { background: #dc2626; }
</style>';

echo '<script>
    function toggleWeeklyOptions() {
        var weekly = document.getElementById("event_type").value === "Weekly";
        document.getElementById("container-weekly").style.display = weekly ? "block" : "none";
        document.getElementById("container-daily").style.display = weekly ? "none" : "block";
    }
    document.addEventListener("DOMContentLoaded", toggleWeeklyOptions);

    function openDeleteModal(id) {
        document.getElementById("evmDeleteId").value = id;
        document.getElementById("customDeleteModal").style.display = "flex";
    }
    function closeDeleteModal() {
        document.getElementById("customDeleteModal").style.display = "none";
    }
    document.getElementById("customDeleteModal").addEventListener("click", function(e) {
        if(e.target === this) closeDeleteModal();
    });
    document.addEventListener("keydown", function(e) { if(e.key === "Escape") closeDeleteModal(); });
</script>';