<?php
/**
 * module_events.php - PREPARED FOR OBFUSCATION
 * Pure PHP Version (Concatenation Safe)
 */

// 1. Configuración de Rutas y Archivo de Datos
$eventsFile = __PATH_INCLUDES__ . 'config/server_events.json';

if (!file_exists($eventsFile)) {
    file_put_contents($eventsFile, json_encode([]));
}

$eventsData = json_decode(file_get_contents($eventsFile), true);

// 2. Lógica de Procesamiento (Guardar / Actualizar)
if (isset($_POST['save_event'])) {
    $eId = $_POST['event_id']; 
    $newEvent = [
        'name'  => $_POST['event_name'],
        'type'  => $_POST['event_type'],
        'times' => $_POST['event_times'],
        // Campos nuevos para Weekly
        'day_start'  => isset($_POST['day_start']) ? $_POST['day_start'] : '',
        'time_start' => isset($_POST['time_start']) ? $_POST['time_start'] : '',
        'day_end'    => isset($_POST['day_end']) ? $_POST['day_end'] : '',
        'time_end'   => isset($_POST['time_end']) ? $_POST['time_end'] : ''
    ];

    if ($eId !== "") {
        $eventsData[$eId] = $newEvent;
    } else {
        $eventsData[uniqid()] = $newEvent;
    }
    
    file_put_contents($eventsFile, json_encode($eventsData));
    header("Location: " . admincp_base('module_events&status=success'));
    exit;
}

// Lógica para borrar
if (isset($_GET['delete'])) {
    $deleteId = $_GET['delete'];
    if (isset($eventsData[$deleteId])) {
        unset($eventsData[$deleteId]);
        file_put_contents($eventsFile, json_encode($eventsData));
        header("Location: " . admincp_base('module_events&status=deleted'));
        exit;
    }
}

// Cargar datos para edición
$editEvent = ['id' => '', 'name' => '', 'type' => 'Daily', 'times' => '', 'day_start' => 'Lunes', 'time_start' => '', 'day_end' => 'Domingo', 'time_end' => ''];
if (isset($_GET['edit'])) {
    $editId = $_GET['edit'];
    if (isset($eventsData[$editId])) {
        $editEvent = $eventsData[$editId];
        $editEvent['id'] = $editId;
    }
}

// Variables visuales
$formAction = admincp_base('module_events');
$title = ($editEvent['id'] !== "") ? 'Editar Evento' : 'Agregar Nuevo Evento';
$cancelLink = admincp_base('module_events');

// --- INICIO RENDERIZADO HTML ---

echo '<div class="container-fluid">';
    echo '<div class="row">';
        echo '<div class="col-lg-12">';
            
            // Alerta Status
            if(isset($_GET['status'])) {
                echo '<div class="alert alert-success">Operación realizada con éxito.</div>';
            }

            // Sección Formulario
            echo '<div class="settings-section">';
                echo '<h3 class="settings-title">' . $title . '</h3>';
                
                echo '<form action="' . $formAction . '" method="post">';
                    echo '<input type="hidden" name="event_id" value="' . $editEvent['id'] . '">';
                    
                    echo '<div class="row">';
                        
                        // Input Nombre
                        echo '<div class="col-md-4">';
                            echo '<div class="form-group">';
                                echo '<label class="settings-label">Nombre del Evento</label>';
                                echo '<input type="text" name="event_name" class="form-control-dark" value="' . $editEvent['name'] . '" required>';
                            echo '</div>';
                        echo '</div>';

                        // Select Tipo
                        echo '<div class="col-md-3">';
                            echo '<div class="form-group">';
                                echo '<label class="settings-label">Tipo de Cronograma</label>';
                                echo '<select name="event_type" id="event_type" class="form-control-dark" onchange="toggleWeeklyOptions()">';
                                    echo '<option value="Daily" ' . ($editEvent['type'] == 'Daily' ? 'selected' : '') . '>Daily (Diario)</option>';
                                    echo '<option value="Weekly" ' . ($editEvent['type'] == 'Weekly' ? 'selected' : '') . '>Weekly (Rango Semanal)</option>';
                                    echo '<option value="Special" ' . ($editEvent['type'] == 'Special' ? 'selected' : '') . '>Special</option>';
                                echo '</select>';
                            echo '</div>';
                        echo '</div>';

                        // Input Horarios (Daily)
                        echo '<div class="col-md-5" id="container-daily">';
                            echo '<div class="form-group">';
                                echo '<label class="settings-label">Horarios (Ej: 15:00, 20:00)</label>';
                                echo '<input type="text" name="event_times" class="form-control-dark" value="' . $editEvent['times'] . '">';
                            echo '</div>';
                        echo '</div>';
                    echo '</div>';

                    // Container Weekly
                    echo '<div id="container-weekly" style="display: none; background: rgba(255,138,59,0.05); padding: 20px; border-radius: 8px; margin-bottom: 20px; border: 1px dashed #FF8A3B;">';
                        echo '<div class="row">';
                            $dias = ['Lunes','Martes','Miércoles','Jueves','Viernes','Sábado','Domingo'];
                            
                            // Dia Inicio
                            echo '<div class="col-md-3">';
                                echo '<label class="settings-label">Día de Inicio</label>';
                                echo '<select name="day_start" class="form-control-dark">';
                                foreach($dias as $d) {
                                    $sel = ($editEvent['day_start'] == $d) ? 'selected' : '';
                                    echo '<option value="' . $d . '" ' . $sel . '>' . $d . '</option>';
                                }
                                echo '</select>';
                            echo '</div>';

                            // Hora Inicio
                            echo '<div class="col-md-3">';
                                echo '<label class="settings-label">Hora de Inicio</label>';
                                echo '<input type="time" name="time_start" class="form-control-dark" value="' . $editEvent['time_start'] . '">';
                            echo '</div>';

                            // Dia Fin
                            echo '<div class="col-md-3">';
                                echo '<label class="settings-label">Día de Fin</label>';
                                echo '<select name="day_end" class="form-control-dark">';
                                foreach($dias as $d) {
                                    $sel = ($editEvent['day_end'] == $d) ? 'selected' : '';
                                    echo '<option value="' . $d . '" ' . $sel . '>' . $d . '</option>';
                                }
                                echo '</select>';
                            echo '</div>';

                            // Hora Fin
                            echo '<div class="col-md-3">';
                                echo '<label class="settings-label">Hora de Fin</label>';
                                echo '<input type="time" name="time_end" class="form-control-dark" value="' . $editEvent['time_end'] . '">';
                            echo '</div>';

                        echo '</div>';
                    echo '</div>';

                    // Botones Guardar
                    echo '<div class="row">';
                        echo '<div class="col-md-12">';
                            $btnIcon = ($editEvent['id'] !== "") ? 'fa-save' : 'fa-plus';
                            $btnText = ($editEvent['id'] !== "") ? ' Actualizar Evento' : ' Agregar Evento';
                            
                            echo '<button type="submit" name="save_event" class="btn-save-green">';
                            echo '<i class="fa ' . $btnIcon . '"></i> ' . $btnText;
                            echo '</button>';
                            
                            if($editEvent['id'] !== "") {
                                echo ' <a href="' . $cancelLink . '" class="btn btn-default" style="color:#fff;">Cancelar</a>';
                            }
                        echo '</div>';
                    echo '</div>';
                echo '</form>';
            echo '</div>'; // Fin settings-section

            // Sección Tabla
            echo '<div class="settings-section mt-4">';
                echo '<h3 class="settings-title">Cronograma Actual</h3>';
                echo '<table class="table table-dark-custom">';
                    echo '<thead><tr><th>Evento</th><th class="text-center">Tipo</th><th>Configuración de Horario</th><th class="text-right">Acciones</th></tr></thead>';
                    echo '<tbody>';
                        
                        foreach($eventsData as $id => $data) {
                            $typeLabel = $data['type'];
                            $timeDisplay = '';
                            
                            if($data['type'] == 'Weekly') {
                                $dStart = isset($data['day_start']) ? $data['day_start'] : '';
                                $tStart = isset($data['time_start']) ? $data['time_start'] : '';
                                $dEnd = isset($data['day_end']) ? $data['day_end'] : '';
                                $tEnd = isset($data['time_end']) ? $data['time_end'] : '';
                                $timeDisplay = "Desde " . $dStart . " " . $tStart . " hs hasta " . $dEnd . " " . $tEnd . " hs";
                            } else {
                                $timeDisplay = $data['times'];
                            }

                            $editUrl = admincp_base('module_events&edit='.$id);
                            $delUrl = admincp_base('module_events&delete='.$id);

                            echo '<tr>';
                                echo '<td><div class="event-name-box">' . $data['name'] . '</div></td>';
                                echo '<td class="text-center"><span class="badge-event-type">' . $typeLabel . '</span></td>';
                                echo '<td><span style="color:#cbd5e1; font-size:13px;">' . $timeDisplay . '</span></td>';
                                echo '<td class="text-right">';
                                    echo '<a href="' . $editUrl . '" class="btn-edit-blue">Editar</a> ';
                                    echo '<a href="javascript:void(0);" class="btn-delete-red" onclick="openDeleteModal(\'' . $delUrl . '\')">Borrar</a>';
                                echo '</td>';
                            echo '</tr>';
                        }

                    echo '</tbody>';
                echo '</table>';
            echo '</div>'; // Fin Tabla

        echo '</div>'; // Fin col
    echo '</div>'; // Fin row
echo '</div>'; // Fin container

// --- MODALES Y CSS/JS ---

// Modal Delete
echo '<div id="customDeleteModal" class="modal-overlay">';
    echo '<div class="modal-box">';
        echo '<div class="modal-header"><i class="fa fa-exclamation-triangle"></i> Confirmar Eliminación</div>';
        echo '<div class="modal-body">';
            echo '<p>¿Estás seguro de que deseas eliminar este evento?</p>';
            echo '<span class="modal-subtext">Esta acción no se puede deshacer.</span>';
        echo '</div>';
        echo '<div class="modal-footer">';
            echo '<button class="btn-modal-cancel" onclick="closeDeleteModal()">Cancelar</button>';
            echo '<a href="#" id="btnConfirmDelete" class="btn-modal-confirm">Eliminar</a>';
        echo '</div>';
    echo '</div>';
echo '</div>';

// CSS
echo '<style>
    .modal-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.7); z-index: 9999; justify-content: center; align-items: center; backdrop-filter: blur(2px); }
    .modal-box { background: #1b1b28; border: 1px solid #2b2b40; border-radius: 8px; width: 400px; max-width: 90%; box-shadow: 0 10px 30px rgba(0,0,0,0.5); animation: fadeInModal 0.2s ease-out; overflow: hidden; }
    .modal-header { padding: 15px 20px; border-bottom: 1px solid #2b2b40; font-weight: bold; color: #fff; font-size: 16px; display: flex; align-items: center; gap: 10px; }
    .modal-header i { color: #f59e0b; }
    .modal-body { padding: 20px; color: #a1a5b7; text-align: center; }
    .modal-subtext { display: block; font-size: 12px; color: #565674; margin-top: 5px; }
    .modal-footer { padding: 15px 20px; background: #151521; display: flex; justify-content: flex-end; gap: 10px; border-top: 1px solid #2b2b40; }
    .btn-modal-cancel { background: transparent; border: 1px solid #323248; color: #a1a5b7; padding: 8px 16px; border-radius: 4px; cursor: pointer; transition: all 0.2s; font-size: 13px; }
    .btn-modal-cancel:hover { background: #323248; color: #fff; }
    .btn-modal-confirm { background: #f1416c; border: none; color: #fff; padding: 8px 16px; border-radius: 4px; text-decoration: none; font-size: 13px; font-weight: 500; transition: background 0.2s; }
    .btn-modal-confirm:hover { background: #d9214e; color: #fff; }
    @keyframes fadeInModal { from { opacity: 0; transform: translateY(-20px); } to { opacity: 1; transform: translateY(0); } }
</style>';

// JS
echo '<script>
    function toggleWeeklyOptions() {
        var type = document.getElementById("event_type").value;
        var weekly = document.getElementById("container-weekly");
        var daily = document.getElementById("container-daily");

        if(type === "Weekly") {
            weekly.style.display = "block";
            daily.style.display = "none";
        } else {
            weekly.style.display = "none";
            daily.style.display = "block";
        }
    }
    
    document.addEventListener("DOMContentLoaded", toggleWeeklyOptions);

    function openDeleteModal(url) {
        document.getElementById("btnConfirmDelete").href = url;
        document.getElementById("customDeleteModal").style.display = "flex";
    }

    function closeDeleteModal() {
        document.getElementById("customDeleteModal").style.display = "none";
    }

    window.onclick = function(event) {
        var modal = document.getElementById("customDeleteModal");
        if (event.target == modal) {
            modal.style.display = "none";
        }
    }
</script>';
?>