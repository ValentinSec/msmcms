<?php
/**
 * Boss Rankings Configuration Module - PREPARED FOR OBFUSCATION
 * Pure PHP Version (Concatenation Safe)
 */

// Buffering para evitar errores de headers
ob_start();

// 1. CONFIGURACIÓN Y RUTAS
$configFile = __PATH_INCLUDES__ . 'config/bosses.json';
$uploadDir  = __ROOT_DIR__ . 'img/bosses/';

// Crear carpeta si no existe
if (!file_exists($uploadDir)) {
    if (!mkdir($uploadDir, 0777, true)) {
        echo '<div class="alert alert-danger">Error: No se pudo crear la carpeta img/bosses/. Revisa permisos.</div>';
    }
}

// Cargar datos actuales
$bossData = [];
if (file_exists($configFile)) {
    $bossData = json_decode(file_get_contents($configFile), true);
}

// 2. DETECCIÓN DE ERRORES DE SERVIDOR (POST MAX SIZE)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && empty($_POST) && empty($_FILES) && $_SERVER['CONTENT_LENGTH'] > 0) {
    $maxPost = ini_get('post_max_size');
    echo '<div class="alert alert-danger">
            <strong>ERROR CRÍTICO:</strong> El archivo que intentaste subir es demasiado grande.<br>
            El límite de tu servidor (post_max_size) es: <strong>'.$maxPost.'</strong>.<br>
            La operación fue cancelada por el servidor. Sube una imagen más liviana o aumenta el límite en tu php.ini.
          </div>';
}

// 3. LÓGICA DE PROCESAMIENTO

// A. GUARDAR SWITCHES
if(isset($_POST['save_changes'])) {
    foreach($bossData as $id => $data) {
        $isActive = isset($_POST['active'][$id]) ? true : false;
        $bossData[$id]['active'] = $isActive;
    }
    
    file_put_contents($configFile, json_encode($bossData, JSON_PRETTY_PRINT));
    header('Location: index.php?module=module_bosses&success=1');
    exit; 
}

// B. AGREGAR / EDITAR BOSS
if(isset($_POST['process_boss'])) {
    try {
        $monsterIndex = (int)$_POST['monster_index'];
        $name = htmlspecialchars($_POST['boss_name']);
        $rewardImg = $_POST['reward_img'];
        $showReward = (isset($_POST['show_reward']) && $_POST['show_reward'] == '1') ? true : false;
        
        $imageFilename = isset($_POST['current_image']) ? basename($_POST['current_image']) : '';
        
        // Procesar Imagen
        if (isset($_FILES['boss_image']) && $_FILES['boss_image']['error'] !== UPLOAD_ERR_NO_FILE) {
            
            switch ($_FILES['boss_image']['error']) {
                case UPLOAD_ERR_OK: break;
                case UPLOAD_ERR_INI_SIZE:
                case UPLOAD_ERR_FORM_SIZE: throw new Exception("El archivo excede el tamaño permitido por el servidor.");
                case UPLOAD_ERR_PARTIAL: throw new Exception("El archivo se subió parcialmente.");
                case UPLOAD_ERR_NO_TMP_DIR: throw new Exception("Falta la carpeta temporal en el servidor.");
                default: throw new Exception("Error desconocido al subir archivo.");
            }

            $ext = strtolower(pathinfo($_FILES['boss_image']['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
            
            if (!in_array($ext, $allowed)) {
                throw new Exception("Formato no permitido. Solo JPG, PNG, WEBP, GIF.");
            }

            $newFileName = $monsterIndex . '_' . uniqid() . '.' . $ext;
            $targetFile = $uploadDir . $newFileName;
            
            if (move_uploaded_file($_FILES['boss_image']['tmp_name'], $targetFile)) {
                if (!empty($imageFilename) && file_exists($uploadDir . $imageFilename)) {
                    @unlink($uploadDir . $imageFilename);
                }
                $imageFilename = $newFileName;
            } else {
                throw new Exception("No se pudo mover el archivo a img/bosses/. Verifica permisos de escritura.");
            }
        }

        $isActive = isset($bossData[$monsterIndex]['active']) ? $bossData[$monsterIndex]['active'] : true;

        $bossData[$monsterIndex] = [
            'name' => $name,
            'reward_img' => $rewardImg,
            'custom_image' => $imageFilename,
            'show_reward' => $showReward,
            'active' => $isActive
        ];

        file_put_contents($configFile, json_encode($bossData, JSON_PRETTY_PRINT));
        header('Location: index.php?module=module_bosses&success=1');
        exit;

    } catch (Exception $e) {
        echo '<div class="alert alert-danger"><strong>Error:</strong> '.$e->getMessage().'</div>';
    }
}

// C. BORRAR BOSS
if(isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    if(isset($bossData[$id])) {
        if(!empty($bossData[$id]['custom_image'])) {
            $fileToDelete = basename($bossData[$id]['custom_image']);
            if(file_exists($uploadDir . $fileToDelete)) {
                @unlink($uploadDir . $fileToDelete);
            }
        }
        unset($bossData[$id]);
        file_put_contents($configFile, json_encode($bossData, JSON_PRETTY_PRINT));
        header('Location: index.php?module=module_bosses&success=1');
        exit;
    }
}

function isBossActive($data) { return (isset($data['active']) && $data['active'] === true) ? 'checked' : ''; }

// Variables para HTML
$baseUrl = __BASE_URL__;

// --- INICIO RENDERIZADO HTML ---

echo '<div class="container-fluid">';
    
    // Alerta Success
    if(isset($_GET['success'])) {
        echo '<div class="alert-success-custom">Cambios guardados correctamente.</div>';
    }

    echo '<form action="index.php?module=module_bosses" method="post" id="mainForm">';
        
        // Header con Botones
        echo '<div class="row" style="margin-bottom: 20px;">';
            echo '<div class="col-xs-6">';
                echo '<h3 style="color:#fff; font-weight:600;">Configuración de Bosses</h3>';
            echo '</div>';
            echo '<div class="col-xs-6 text-right">';
                echo '<button type="button" class="btn btn-primary" data-toggle="modal" data-target="#bossModal" onclick="clearModal()" style="margin-right: 10px;">';
                echo '<i class="fa fa-plus"></i> Agregar Boss';
                echo '</button>';
                echo '<button type="submit" name="save_changes" class="btn-save-green">Guardar Cambios</button>';
            echo '</div>';
        echo '</div>';

        // Main Grid
        echo '<div class="row row-flex">';
            echo '<div class="col-md-12">';
                echo '<div class="settings-section">';
                    echo '<h3 class="settings-title">Gestión de Bosses & Rewards</h3>';
                    echo '<p style="color: #888; font-size: 12px; margin-bottom: 20px;">Administra los bosses. <b>Nota:</b> Si la subida falla, asegúrate que tu imagen pese menos de 2MB o ajusta el <code>upload_max_filesize</code> en tu php.ini.</p>';

                    echo '<div class="row">';
                        if(empty($bossData)) {
                            echo '<div class="col-md-12 text-center" style="color: #666; padding: 30px;">No hay bosses configurados.</div>';
                        } else {
                            foreach($bossData as $index => $boss) {
                                // Cache Buster
                                $ts = time();
                                $imgShow = (!empty($boss['custom_image'])) 
                                    ? $baseUrl . 'img/bosses/' . $boss['custom_image'] . '?v=' . $ts
                                    : $baseUrl . 'img/dorados/' . $index . '.png';
                                
                                $showReward = isset($boss['show_reward']) ? $boss['show_reward'] : true;
                                $bossNameSafe = htmlspecialchars($boss['name'], ENT_QUOTES);
                                $rewardImgSafe = $boss['reward_img'];
                                $customImgSafe = isset($boss['custom_image']) ? $boss['custom_image'] : '';
                                $activeCheck = isBossActive($boss);

                                echo '<div class="col-md-3" style="margin-bottom: 20px;">';
                                echo '<div style="background: #151515; border: 1px solid #333; border-radius: 5px; padding: 15px; position: relative;">';
                                    
                                    // ID y Switch
                                    echo '<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">';
                                        echo '<span class="badge badge-secondary">ID: ' . $index . '</span>';
                                        echo '<div class="switch-container" style="margin: 0;">';
                                            echo '<label class="switch" style="transform: scale(0.8);">';
                                                echo '<input type="checkbox" name="active[' . $index . ']" ' . $activeCheck . '>';
                                                echo '<span class="slider"></span>';
                                            echo '</label>';
                                        echo '</div>';
                                    echo '</div>';

                                    // Imagen y Info
                                    echo '<div class="text-center" style="margin-bottom: 10px;">';
                                        echo '<img src="' . $imgShow . '" style="height: 60px; object-fit: contain; margin-bottom: 5px;" onerror="this.src=\'' . $baseUrl . 'img/dorados/trophy.png\'">';
                                        echo '<h5 style="color: #e0e0e0; font-size: 14px; margin: 5px 0; font-weight: bold;">' . $boss['name'] . '</h5>';
                                        
                                        echo '<div style="margin-top: 5px;">';
                                        if($showReward) {
                                            echo '<small class="text-success"><i class="fa fa-gift"></i> Reward Visible</small>';
                                            echo '<br><small style="color: #666; font-size: 10px;">' . $boss['reward_img'] . '</small>';
                                        } else {
                                            echo '<small class="text-warning"><i class="fa fa-eye-slash"></i> Reward Oculto</small>';
                                            echo '<br><small style="color: #666; font-size: 10px;">Muestra TOTAL</small>';
                                        }
                                        echo '</div>';
                                    echo '</div>';

                                    // Botones Card
                                    echo '<div style="display: flex; gap: 5px; justify-content: center; margin-top: 10px; border-top: 1px solid #222; padding-top: 10px;">';
                                        
                                        // Edit Button
                                        echo '<button type="button" class="btn btn-xs btn-info" onclick="editBoss(' . $index . ', \'' . $bossNameSafe . '\', \'' . $rewardImgSafe . '\', \'' . $customImgSafe . '\', ' . ($showReward ? 1 : 0) . ')">';
                                        echo '<i class="fa fa-pencil"></i> Editar';
                                        echo '</button>';
                                        
                                        // Delete Button
                                        echo '<a href="javascript:void(0);" class="btn btn-xs btn-danger" onclick="openDeleteModal(\'index.php?module=module_bosses&delete=' . $index . '\')">';
                                        echo '<i class="fa fa-trash"></i>';
                                        echo '</a>';

                                    echo '</div>';

                                echo '</div>';
                                echo '</div>';
                            }
                        }
                    echo '</div>'; // End Row Bosses
                echo '</div>'; // End Settings Section
            echo '</div>'; // End Col 12
        echo '</div>'; // End Row Flex
    echo '</form>';
echo '</div>';

// MODAL AGREGAR / EDITAR
echo '<div class="modal fade" id="bossModal" tabindex="-1" role="dialog" aria-hidden="true">';
    echo '<div class="modal-dialog" role="document">';
        echo '<div class="modal-content" style="background-color: #1e1e1e; color: #fff; border: 1px solid #333;">';
            echo '<form action="index.php?module=module_bosses" method="post" enctype="multipart/form-data" id="modalForm">';
                echo '<input type="hidden" name="process_boss" value="1">';
                echo '<input type="hidden" name="current_image" id="current_image" value="">';

                echo '<div class="modal-header" style="border-bottom: 1px solid #333;">';
                    echo '<h5 class="modal-title" id="bossModalLabel">Configurar Boss</h5>';
                    echo '<button type="button" class="close" data-dismiss="modal" aria-label="Close" style="color: #fff;"><span aria-hidden="true">&times;</span></button>';
                echo '</div>';
                
                echo '<div class="modal-body">';
                    echo '<div class="form-group">';
                        echo '<label>Monster Index (ID)</label>';
                        echo '<input type="number" name="monster_index" id="monster_index" class="form-control form-control-dark" required placeholder="Ej: 79">';
                    echo '</div>';

                    echo '<div class="form-group">';
                        echo '<label>Nombre del Boss</label>';
                        echo '<input type="text" name="boss_name" id="boss_name" class="form-control form-control-dark" required placeholder="Ej: Golden Dragon">';
                    echo '</div>';

                    echo '<div class="form-group">';
                        echo '<label>Imagen del Boss (Max 2MB)</label>';
                        echo '<input type="file" name="boss_image" id="boss_image" class="form-control-dark" style="padding: 5px; height: auto;" onchange="validateSize(this)">';
                        echo '<small style="color: #777;">Se recomienda PNG Transparente. Si pesa más de 2MB, el servidor podría rechazarla.</small>';
                    echo '</div>';
                    
                    echo '<div class="row">';
                        echo '<div class="col-md-6">';
                            echo '<div class="form-group">';
                                echo '<label>¿Mostrar Reward?</label>';
                                echo '<select name="show_reward" id="show_reward" class="form-control form-control-dark">';
                                    echo '<option value="1">SÍ (Mostrar Caja)</option>';
                                    echo '<option value="0">NO (Mostrar Total)</option>';
                                echo '</select>';
                            echo '</div>';
                        echo '</div>';
                        echo '<div class="col-md-6">';
                            echo '<div class="form-group">';
                                echo '<label>Reward Image</label>';
                                echo '<select name="reward_img" id="reward_img" class="form-control form-control-dark">';
                                    echo '<option value="box_gm.webp">Default (GM Box)</option>';
                                    echo '<option value="k1.webp">Kundun +1</option>';
                                    echo '<option value="k2.webp">Kundun +2</option>';
                                    echo '<option value="k3.webp">Kundun +3</option>';
                                    echo '<option value="k4.webp">Kundun +4</option>';
                                    echo '<option value="k5.webp">Kundun +5</option>';
                                echo '</select>';
                            echo '</div>';
                        echo '</div>';
                    echo '</div>';
                echo '</div>';

                echo '<div class="modal-footer" style="border-top: 1px solid #333;">';
                    echo '<button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>';
                    echo '<button type="submit" class="btn-save-green" id="btnSubmit">Guardar Boss</button>';
                echo '</div>';
            echo '</form>';
        echo '</div>';
    echo '</div>';
echo '</div>';

// MODAL ELIMINAR (Custom)
echo '<div id="customDeleteModal" class="modal-overlay">';
    echo '<div class="modal-box">';
        echo '<div class="modal-header-custom"><i class="fa fa-exclamation-triangle"></i> Confirmar Eliminación</div>';
        echo '<div class="modal-body-custom">';
            echo '<p>¿Estás seguro de que deseas eliminar este Boss?</p>';
            echo '<span class="modal-subtext">Esta acción borrará la configuración y la imagen asociada.</span>';
        echo '</div>';
        echo '<div class="modal-footer-custom">';
            echo '<button class="btn-modal-cancel" onclick="closeDeleteModal()">Cancelar</button>';
            echo '<a href="#" id="btnConfirmDelete" class="btn-modal-confirm">Eliminar</a>';
        echo '</div>';
    echo '</div>';
echo '</div>';

// ESTILOS
echo '<style>
    .modal-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.7); z-index: 9999; justify-content: center; align-items: center; backdrop-filter: blur(2px); }
    .modal-box { background: #1b1b28; border: 1px solid #2b2b40; border-radius: 8px; width: 400px; max-width: 90%; box-shadow: 0 10px 30px rgba(0,0,0,0.5); animation: fadeInModal 0.2s ease-out; overflow: hidden; }
    .modal-header-custom { padding: 15px 20px; border-bottom: 1px solid #2b2b40; font-weight: bold; color: #fff; font-size: 16px; display: flex; align-items: center; gap: 10px; }
    .modal-header-custom i { color: #f59e0b; }
    .modal-body-custom { padding: 20px; color: #a1a5b7; text-align: center; }
    .modal-subtext { display: block; font-size: 12px; color: #565674; margin-top: 5px; }
    .modal-footer-custom { padding: 15px 20px; background: #151521; display: flex; justify-content: flex-end; gap: 10px; border-top: 1px solid #2b2b40; }
    .btn-modal-cancel { background: transparent; border: 1px solid #323248; color: #a1a5b7; padding: 8px 16px; border-radius: 4px; cursor: pointer; transition: all 0.2s; font-size: 13px; }
    .btn-modal-cancel:hover { background: #323248; color: #fff; }
    .btn-modal-confirm { background: #f1416c; border: none; color: #fff; padding: 8px 16px; border-radius: 4px; text-decoration: none; font-size: 13px; font-weight: 500; transition: background 0.2s; }
    .btn-modal-confirm:hover { background: #d9214e; color: #fff; }
    @keyframes fadeInModal { from { opacity: 0; transform: translateY(-20px); } to { opacity: 1; transform: translateY(0); } }
    .form-control-dark { background-color: #222; border: 1px solid #444; color: #eee; }
    .form-control-dark:focus { background-color: #2a2a2a; color: #fff; border-color: #28a745; }
    .alert-success-custom { background: #1b1b28; border-left: 4px solid #00d084; color: #fff; padding: 15px; margin-bottom: 20px; border-radius: 4px; box-shadow: 0 4px 10px rgba(0,0,0,0.2); }
</style>';

// SCRIPTS
echo '<script>
function clearModal() {
    document.getElementById("bossModalLabel").innerText = "Agregar Nuevo Boss";
    document.getElementById("monster_index").value = "";
    document.getElementById("monster_index").readOnly = false; 
    document.getElementById("boss_name").value = "";
    document.getElementById("reward_img").value = "box_gm.webp";
    document.getElementById("current_image").value = "";
    document.getElementById("show_reward").value = "1";
    document.getElementById("boss_image").value = "";
    document.getElementById("btnSubmit").disabled = false;
    document.getElementById("btnSubmit").innerText = "Guardar Boss";
}

function editBoss(index, name, reward, img, showReward) {
    document.getElementById("bossModalLabel").innerText = "Editar Boss (ID: " + index + ")";
    document.getElementById("monster_index").value = index;
    document.getElementById("boss_name").value = name;
    document.getElementById("reward_img").value = reward;
    document.getElementById("current_image").value = img;
    document.getElementById("show_reward").value = showReward;
    
    $("#bossModal").modal("show");
}

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

function validateSize(input) {
    const fileSize = input.files[0].size / 1024 / 1024;
    if (fileSize > 2) {
        alert("El archivo excede los 2 MB. Es muy probable que tu servidor no lo acepte. Por favor comprímelo o sube uno más liviano.");
    }
}

document.getElementById("modalForm").onsubmit = function() {
    document.getElementById("btnSubmit").disabled = true;
    document.getElementById("btnSubmit").innerText = "Subiendo...";
};
</script>';
?>