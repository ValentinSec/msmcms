<?php
/**
 * Settings: Downloads Manager - PREPARED FOR OBFUSCATION
 * Pure PHP Version (Concatenation Safe)
 */

$jsonFile = __PATH_INCLUDES__ . 'config/modules/downloads.json';

// 1. Cargar datos actuales
$downloads = file_exists($jsonFile) ? json_decode(file_get_contents($jsonFile), true) : [];

// 2. Lógica para BORRAR
if(isset($_GET['delete'])) {
    $id = $_GET['delete'];
    if(isset($downloads[$id])) {
        unset($downloads[$id]);
        // Reindexamos
        $downloads = array_values($downloads);
        file_put_contents($jsonFile, json_encode($downloads, JSON_PRETTY_PRINT));
    }
    // REDIRECCIÓN EXACTA:
    header("Location: index.php?module=settings_download"); 
    exit;
}

// 3. Lógica para AGREGAR
if(isset($_POST['add_download'])) {
    $newDownload = [
        'download_link' => $_POST['download_link'],
        'download_type' => $_POST['download_type'],
        'download_hosting' => $_POST['download_hosting'],
        'download_title' => '1.0' // Versión por defecto
    ];
    $downloads[] = $newDownload;
    file_put_contents($jsonFile, json_encode($downloads, JSON_PRETTY_PRINT));
    
    header("Location: index.php?module=settings_download");
    exit;
}

// --- RENDERIZADO HTML (PHP PURO) ---

echo '<div class="container-fluid">';
    echo '<div class="row">';
        echo '<div class="col-lg-12">';
            
            echo '<div class="settings-section">';
                echo '<h3 class="settings-title" style="border:none; margin-bottom: 20px;">Configurar Descargas del Juego</h3>';
                
                // Formulario Agregar
                echo '<form action="" method="post">';
                    echo '<div class="row">';
                        
                        // Input Link
                        echo '<div class="col-md-5">';
                            echo '<div class="form-group">';
                                echo '<label class="settings-label">Link</label>';
                                echo '<input type="text" name="download_link" class="form-control-dark" placeholder="https://...">';
                            echo '</div>';
                        echo '</div>';

                        // Select Tipo
                        echo '<div class="col-md-3">';
                            echo '<div class="form-group">';
                                echo '<label class="settings-label">Tipo</label>';
                                echo '<select name="download_type" class="form-control-dark">';
                                    echo '<option value="1">Cliente (Lite)</option>';
                                    echo '<option value="2">Cliente (Full)</option>';
                                    echo '<option value="3">Herramienta (Tool)</option>';
                                echo '</select>';
                            echo '</div>';
                        echo '</div>';

                        // Select Hosting
                        echo '<div class="col-md-2">';
                            echo '<div class="form-group">';
                                echo '<label class="settings-label">Alojamiento</label>';
                                echo '<select name="download_hosting" class="form-control-dark">';
                                    echo '<option value="Mediafire">Mediafire</option>';
                                    echo '<option value="MEGA">MEGA</option>';
                                    echo '<option value="Drive">Google Drive</option>';
                                echo '</select>';
                            echo '</div>';
                        echo '</div>';

                        // Botón Agregar
                        echo '<div class="col-md-2">';
                            echo '<div class="form-group">';
                                echo '<label class="settings-label">&nbsp;</label>';
                                echo '<button type="submit" name="add_download" class="btn-add-download btn-block">';
                                    echo '<i class="fa fa-plus"></i> Agregar';
                                echo '</button>';
                            echo '</div>';
                        echo '</div>';

                    echo '</div>';
                echo '</form>';

                echo '<hr style="border-color: #1e293b; margin: 30px 0;">';

                echo '<h3 class="settings-title" style="border:none; margin-bottom: 15px;">Descargas Añadidas</h3>';
                
                // Tabla Descargas
                echo '<div class="table-responsive">';
                    echo '<table class="downloads-table">';
                        echo '<thead>';
                            echo '<tr>';
                                echo '<th style="width: 40%;">Link</th>';
                                echo '<th style="width: 20%;">Tipo</th>';
                                echo '<th style="width: 20%;">Alojamiento</th>';
                                echo '<th style="width: 20%; text-align: right;">Acciones</th>';
                            echo '</tr>';
                        echo '</thead>';
                        echo '<tbody>';
                            
                            if(!empty($downloads)) {
                                foreach($downloads as $key => $dl) {
                                    $types = [1 => 'Cliente Lite', 2 => 'Cliente Full', 3 => 'Herramienta'];
                                    $colors = [1 => '#3b82f6', 2 => '#10b981', 3 => '#f59e0b'];
                                    $label = isset($types[$dl['download_type']]) ? $types[$dl['download_type']] : 'Desconocido';
                                    $color = isset($colors[$dl['download_type']]) ? $colors[$dl['download_type']] : '#666';
                                    
                                    echo '<tr>';
                                        echo '<td>' . htmlspecialchars($dl['download_link']) . '</td>';
                                        echo '<td><span class="badge" style="background:' . $color . ';">' . $label . '</span></td>';
                                        echo '<td>' . htmlspecialchars($dl['download_hosting']) . '</td>';
                                        echo '<td style="text-align: right;">';
                                            echo '<a href="javascript:void(0);" class="action-btn delete" onclick="openDeleteModal(\'index.php?module=settings_download&delete=' . $key . '\')">';
                                                echo '<i class="fa fa-trash"></i>';
                                            echo '</a>';
                                        echo '</td>';
                                    echo '</tr>';
                                }
                            } else {
                                echo '<tr><td colspan="4" style="text-align:center; padding: 20px; color: #64748b;">No hay descargas configuradas.</td></tr>';
                            }
                            
                        echo '</tbody>';
                    echo '</table>';
                echo '</div>'; // End table-responsive

            echo '</div>'; // End settings-section

        echo '</div>'; // End col-12
    echo '</div>'; // End row
echo '</div>'; // End container

// Modal Delete
echo '<div id="customDeleteModal" class="modal-overlay">';
    echo '<div class="modal-box">';
        echo '<div class="modal-header"><i class="fa fa-exclamation-triangle"></i> Confirmar Eliminación</div>';
        echo '<div class="modal-body">';
            echo '<p>¿Estás seguro de que deseas eliminar esta descarga?</p>';
            echo '<span class="modal-subtext">Esta acción no se puede deshacer.</span>';
        echo '</div>';
        echo '<div class="modal-footer">';
            echo '<button class="btn-modal-cancel" onclick="closeDeleteModal()">Cancelar</button>';
            echo '<a href="#" id="btnConfirmDelete" class="btn-modal-confirm">Eliminar</a>';
        echo '</div>';
    echo '</div>';
echo '</div>';

// CSS Styles
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

// JS Scripts
echo '<script>
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