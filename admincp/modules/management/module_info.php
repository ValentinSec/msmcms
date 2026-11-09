<?php
/**
 * module_info.php - PREPARED FOR OBFUSCATION
 * Pure PHP Version (Concatenation Safe)
 */

// 1. Configuración de Rutas y Archivo de Datos
$eventsFile = __PATH_INCLUDES__ . 'config/server_info.json';
$dataFile = $eventsFile; // Alias para compatibilidad

// Crear archivo si no existe
if (!file_exists($dataFile)) {
    file_put_contents($dataFile, json_encode([]));
}

$currentData = json_decode(file_get_contents($dataFile), true);
if (!is_array($currentData)) $currentData = [];

// 1. ELIMINAR ITEM INDIVIDUAL
if (isset($_GET['delete_item']) && isset($_GET['cat'])) {
    $cat = $_GET['cat'];
    $id = $_GET['delete_item'];
    if (isset($currentData[$cat]['data'])) {
        $currentData[$cat]['data'] = array_values(array_filter($currentData[$cat]['data'], function($item) use ($id) {
            return $item['id'] !== $id;
        }));
        file_put_contents($dataFile, json_encode($currentData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        header("Location: index.php?module=module_info&status=deleted");
        exit;
    }
}

// 2. ELIMINAR CATEGORÍA COMPLETA
if (isset($_GET['delete_cat'])) {
    $catToDelete = $_GET['delete_cat'];
    if (isset($currentData[$catToDelete])) {
        unset($currentData[$catToDelete]);
        file_put_contents($dataFile, json_encode($currentData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        header("Location: index.php?module=module_info&status=deleted");
        exit;
    }
}

// 3. MOVER ITEM (ORDEN)
if (isset($_GET['move']) && isset($_GET['cat']) && isset($_GET['id'])) {
    $cat = $_GET['cat'];
    $id = $_GET['id'];
    $direction = $_GET['move'];
    if (isset($currentData[$cat]['data'])) {
        $index = -1;
        foreach ($currentData[$cat]['data'] as $k => $item) {
            if ($item['id'] == $id) { $index = $k; break; }
        }
        if ($index !== -1) {
            if ($direction == 'up' && $index > 0) {
                $tmp = $currentData[$cat]['data'][$index];
                $currentData[$cat]['data'][$index] = $currentData[$cat]['data'][$index - 1];
                $currentData[$cat]['data'][$index - 1] = $tmp;
            } elseif ($direction == 'down' && $index < count($currentData[$cat]['data']) - 1) {
                $tmp = $currentData[$cat]['data'][$index];
                $currentData[$cat]['data'][$index] = $currentData[$cat]['data'][$index + 1];
                $currentData[$cat]['data'][$index + 1] = $tmp;
            }
            file_put_contents($dataFile, json_encode($currentData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        }
        header("Location: index.php?module=module_info");
        exit;
    }
}

// 4. AGREGAR INFO
if (isset($_POST['add_info'])) {
    $categoryInput = trim($_POST['info_type']);
    $targetCategory = (strtolower($categoryInput) == 'server info') ? 'general' : $categoryInput;
    if (!isset($currentData[$targetCategory])) {
        $currentData[$targetCategory] = ['config' => ['icon' => 'fas fa-info-circle'], 'data' => []];
    }
    $currentData[$targetCategory]['data'][] = [
        'id' => uniqid(),
        'title' => trim($_POST['info_title']),
        'desc' => trim($_POST['info_desc'])
    ];
    file_put_contents($dataFile, json_encode($currentData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    header("Location: index.php?module=module_info&status=success");
    exit;
}

// 5. CREAR CATEGORÍA
if (isset($_POST['add_category'])) {
    $newCat = trim($_POST['category_name']);
    $newIcon = trim($_POST['category_icon']);
    if (!empty($newCat) && !isset($currentData[$newCat])) {
        $currentData[$newCat] = [
            'config' => ['icon' => !empty($newIcon) ? $newIcon : 'fas fa-chevron-right'],
            'data' => []
        ];
        file_put_contents($dataFile, json_encode($currentData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        header("Location: index.php?module=module_info&status=success");
        exit;
    }
}

// --- RENDERIZADO HTML (PHP PURO) ---

echo '<div class="container-fluid">';
    
    // SECCIÓN AGREGAR
    echo '<div class="add-info-container" style="background: #0d1117; padding: 25px; border-radius: 8px; border: 1px solid #30363d; margin-bottom: 30px;">';
        echo '<h3 class="settings-title" style="border:none; margin-bottom: 15px;">Gestionar Información</h3>';
        
        // Formulario Agregar Info
        echo '<form action="" method="post">';
            echo '<div class="row">';
                echo '<div class="col-md-4"><label class="settings-label">Título</label><input type="text" name="info_title" class="form-control-dark" required placeholder="Nombre de la característica"></div>';
                echo '<div class="col-md-4"><label class="settings-label">Descripción</label><input type="text" name="info_desc" class="form-control-dark" required placeholder="Descripción de la característica"></div>';
                
                echo '<div class="col-md-3">';
                    echo '<label class="settings-label">Categoría</label>';
                    echo '<select name="info_type" class="form-control-dark">';
                        echo '<option value="Server Info">Server Info (General)</option>';
                        foreach ($currentData as $catName => $val) {
                            if ($catName == 'general') continue;
                            echo '<option value="' . $catName . '">' . ucfirst($catName) . '</option>';
                        }
                    echo '</select>';
                echo '</div>';
                
                echo '<div class="col-md-1"><label>&nbsp;</label><button type="submit" name="add_info" class="btn-save-green" style="width:100%;">Añadir</button></div>';
            echo '</div>';
        echo '</form>';

        echo '<hr style="border-color: #30363d; margin: 25px 0;">';
        
        // Formulario Agregar Categoría
        echo '<h3 class="settings-title" style="border:none; margin-bottom: 15px;">Nueva Categoría</h3>';
        echo '<form action="" method="post">';
            echo '<div class="row">';
                echo '<div class="col-md-6"><input type="text" name="category_name" class="form-control-dark" placeholder="Nombre de tu nueva categoría" required></div>';
                echo '<div class="col-md-5"><input type="text" name="category_icon" class="form-control-dark" placeholder="Icono (Ej: fas fa-skull). Extrae esta información desde FontAwesome"></div>';
                echo '<div class="col-md-1"><button type="submit" name="add_category" class="btn-template-select" style="background:#3b82f6; width:100%; border:none;">Crear</button></div>';
            echo '</div>';
        echo '</form>';
    echo '</div>';

    // SECCIÓN LISTADO
    echo '<div class="row row-flex">';
        foreach ($currentData as $catName => $content) {
            $items = isset($content['data']) ? $content['data'] : [];
            $displayName = ($catName == 'general') ? 'SERVER INFO' : strtoupper($catName);
            
            echo '<div class="col-md-6" style="margin-bottom: 25px;">';
                echo '<div class="info-category-card" style="background: #161b22; border: 1px solid #30363d; border-radius: 8px;">';
                    
                    // Header Card
                    echo '<div class="info-category-header" style="background: #21262d; padding: 12px 20px; display: flex; justify-content: space-between; align-items: center;">';
                        echo '<span style="color: #FF8A3B; font-weight: bold;">' . $displayName . '</span>';
                        if($catName !== 'general') {
                            echo '<a href="javascript:void(0);" onclick="openCatDeleteModal(\'index.php?module=module_info&delete_cat=' . $catName . '\')" style="color: #ef4444; font-size: 12px; text-decoration: none;">';
                            echo '<i class="fa fa-times"></i> Borrar Tab';
                            echo '</a>';
                        }
                    echo '</div>';

                    // Body Card
                    echo '<div class="info-category-body" style="padding: 15px;">';
                        foreach ($items as $item) {
                            echo '<div style="display: flex; align-items: center; padding: 8px 0; border-bottom: 1px solid #30363d;">';
                                echo '<div style="width: 35%; font-weight: bold;">' . $item['title'] . '</div>';
                                echo '<div style="width: 40%; color: #8b949e;">' . $item['desc'] . '</div>';
                                echo '<div style="width: 25%; text-align: right; display: flex; justify-content: flex-end; gap: 5px;">';
                                    echo '<a href="index.php?module=module_info&move=up&cat=' . $catName . '&id=' . $item['id'] . '" class="btn-info-action"><i class="fa fa-chevron-up"></i></a>';
                                    echo '<a href="index.php?module=module_info&move=down&cat=' . $catName . '&id=' . $item['id'] . '" class="btn-info-action"><i class="fa fa-chevron-down"></i></a>';
                                    echo '<a href="javascript:void(0);" class="btn-info-action delete" onclick="openDeleteModal(\'index.php?module=module_info&cat=' . $catName . '&delete_item=' . $item['id'] . '\')">';
                                    echo '<i class="fa fa-trash"></i>';
                                    echo '</a>';
                                echo '</div>';
                            echo '</div>';
                        }
                    echo '</div>'; // Fin body
                echo '</div>'; // Fin card
            echo '</div>'; // Fin col
        }
    echo '</div>'; // Fin row flex
echo '</div>'; // Fin container

// --- MODALES ---
echo '<div id="customDeleteModal" class="modal-overlay">';
    echo '<div class="modal-box">';
        echo '<div class="modal-header"><i class="fa fa-exclamation-triangle"></i> Confirmar Eliminación</div>';
        echo '<div class="modal-body">';
            echo '<p id="modalMessage">¿Estás seguro de que deseas eliminar este ítem?</p>';
            echo '<span class="modal-subtext">Esta acción no se puede deshacer.</span>';
        echo '</div>';
        echo '<div class="modal-footer">';
            echo '<button class="btn-modal-cancel" onclick="closeDeleteModal()">Cancelar</button>';
            echo '<a href="#" id="btnConfirmDelete" class="btn-modal-confirm">Eliminar</a>';
        echo '</div>';
    echo '</div>';
echo '</div>';

// ESTILOS CSS
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

// SCRIPTS JS
echo '<script>
    function openDeleteModal(url) {
        document.getElementById("modalMessage").innerText = "¿Estás seguro de que deseas eliminar este ítem?";
        document.getElementById("btnConfirmDelete").href = url;
        document.getElementById("customDeleteModal").style.display = "flex";
    }

    function openCatDeleteModal(url) {
        document.getElementById("modalMessage").innerText = "⚠️ ¿Estás seguro de borrar esta categoría completa? Se perderán todos sus datos.";
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