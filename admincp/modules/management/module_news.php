<?php
/**
 * AdminCP Module: News Manager
 * PREPARED FOR OBFUSCATION (Pure PHP)
 */
if(!defined('access')) die();

$myJsonFile = __PATH_INCLUDES__ . 'config/custom_settings.json';
$newsFile = __PATH_INCLUDES__ . 'config/server_news.json';

$conf = file_exists($myJsonFile) ? json_decode(file_get_contents($myJsonFile), true) : [];
$newsData = file_exists($newsFile) ? json_decode(file_get_contents($newsFile), true) : [];
if(!is_array($newsData)) $newsData = [];

$moduleUrl = "index.php?module=module_news"; 

if(isset($_GET['delete'])) {
    $deleteId = $_GET['delete'];
    $newsData = array_filter($newsData, function($n) use ($deleteId) { return $n['id'] !== $deleteId; });
    file_put_contents($newsFile, json_encode(array_values($newsData), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    header("Location: ".$moduleUrl."&status=deleted");
    exit;
}

if(isset($_POST['save_layout'])) {
    $conf['news_layout_type'] = $_POST['news_layout_type'];
    file_put_contents($myJsonFile, json_encode($conf, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    header("Location: ".$moduleUrl."&status=layout_success");
    exit;
}

if(isset($_POST['save_news'])) {
    $newsId = !empty($_POST['edit_id']) ? $_POST['edit_id'] : uniqid();
    $imagePath = !empty($_POST['old_img']) ? $_POST['old_img'] : '';

    // --- INICIO PARCHE DE SEGURIDAD ---
    if (!empty($_FILES['news_img']['name'])) {
        $targetDir = __DIR__ . "/../../../img/news/";
        if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);
        
        $fileTmp = $_FILES['news_img']['tmp_name'];
        $fileNameOriginal = $_FILES['news_img']['name'];
        $fileExt = strtolower(pathinfo($fileNameOriginal, PATHINFO_EXTENSION));
        
        // 1. Validamos extensiones permitidas (Whitelist)
        $allowedExts = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        
        if (in_array($fileExt, $allowedExts)) {
            $newFileName = $newsId . '_' . uniqid() . '.' . $fileExt;
            $targetFilePath = $targetDir . $newFileName;
            
            if (move_uploaded_file($fileTmp, $targetFilePath)) {
                $imagePath = "img/news/" . $newFileName;
            }
        }
    }
    // --- FIN PARCHE DE SEGURIDAD ---

    $entry = [
        'id' => $newsId,
        'title' => htmlspecialchars($_POST['news_title']), 
        'content' => $_POST['news_content_hidden'], 
        'date' => date('Y-m-d'),
        'image' => $imagePath,
        'slug' => strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $_POST['news_title'])))
    ];

    if(!empty($_POST['edit_id'])) {
        foreach($newsData as $key => $val) {
            if($val['id'] === $_POST['edit_id']) $newsData[$key] = $entry;
        }
    } else {
        array_unshift($newsData, $entry);
    }

    file_put_contents($newsFile, json_encode(array_values($newsData), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    header("Location: ".$moduleUrl."&status=news_success");
    exit;
}

$currentLayout = isset($conf['news_layout_type']) ? $conf['news_layout_type'] : 'cascada';

// --- INICIO RENDERIZADO HTML ---

echo '<link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">';
echo '<script src="https://cdn.quilljs.com/1.3.6/quill.js"></script>';

// Estilos CSS
echo '<style>
    .alert-success-custom { background: #1b1b28; border-left: 4px solid #00d084; color: #fff; padding: 15px; margin-bottom: 20px; border-radius: 4px; box-shadow: 0 4px 10px rgba(0,0,0,0.2); }
    .modal-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.7); z-index: 99999; justify-content: center; align-items: center; backdrop-filter: blur(2px); }
    .modal-box { background: #1b1b28; border: 1px solid #2b2b40; border-radius: 8px; width: 400px; max-width: 90%; box-shadow: 0 10px 30px rgba(0,0,0,0.5); animation: fadeInModal 0.2s ease-out; overflow: hidden; }
    .modal-header-c { padding: 15px 20px; border-bottom: 1px solid #2b2b40; font-weight: bold; color: #fff; font-size: 16px; display: flex; align-items: center; gap: 10px; }
    .modal-header-c i { color: #f59e0b; }
    .modal-body-c { padding: 20px; color: #a1a5b7; text-align: center; }
    .modal-subtext { display: block; font-size: 12px; color: #565674; margin-top: 5px; }
    .modal-footer-c { padding: 15px 20px; background: #151521; display: flex; justify-content: flex-end; gap: 10px; border-top: 1px solid #2b2b40; }
    .btn-modal-cancel { background: transparent; border: 1px solid #323248; color: #a1a5b7; padding: 8px 16px; border-radius: 4px; cursor: pointer; transition: all 0.2s; font-size: 13px; }
    .btn-modal-cancel:hover { background: #323248; color: #fff; }
    .btn-modal-confirm { background: #f1416c; border: none; color: #fff; padding: 8px 16px; border-radius: 4px; text-decoration: none; font-size: 13px; font-weight: 500; transition: background 0.2s; }
    .btn-modal-confirm:hover { background: #d9214e; color: #fff; }
    @keyframes fadeInModal { from { opacity: 0; transform: translateY(-20px); } to { opacity: 1; transform: translateY(0); } }
</style>';

echo '<div class="container-fluid">';

// Alertas de Estado
if(isset($_GET['status'])) {
    if($_GET['status'] == 'news_success') {
        echo '<div class="alert-success-custom"><i class="fa fa-check"></i> ¡Noticia publicada/editada con éxito!</div>';
    } elseif($_GET['status'] == 'layout_success') {
        echo '<div class="alert-success-custom"><i class="fa fa-check"></i> ¡Diseño actualizado correctamente!</div>';
    } elseif($_GET['status'] == 'deleted') {
        echo '<div class="alert-success-custom"><i class="fa fa-check"></i> ¡Noticia eliminada correctamente!</div>';
    }
}

echo '<div class="row">';

    // COLUMNA IZQUIERDA: LISTADO DE NOTICIAS
    echo '<div class="col-lg-8">';
        echo '<div class="settings-section">';
            
            // Header con botón Crear
            echo '<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">';
                echo '<h3 class="settings-title" style="border:none; margin:0; padding:0;">Sistema de Noticias</h3>';
                echo '<button type="button" class="btn-create-news" onclick="openNewsModal()" style="border:none; cursor:pointer;">Crear una Nueva Noticia</button>';
            echo '</div>';

            // Tabla
            echo '<div class="table-responsive">';
                echo '<table class="table" style="margin-bottom: 0;">';
                    echo '<thead><tr><th style="width: 50px;">Imagen</th><th>Título</th><th class="text-center">Fecha</th><th class="text-center">Acciones</th></tr></thead>';
                    echo '<tbody>';
                    
                    if(!empty($newsData)) {
                        foreach($newsData as $news) {
                            $thumb = (!empty($news['image'])) ? __BASE_URL__ . $news['image'] : 'https://placehold.co/40x40/1e293b/FFF?text=IMG';
                            
                            echo '<tr>';
                                echo '<td><img src="' . $thumb . '" class="news-thumb" style="width:40px; height:40px; object-fit:cover; border-radius:4px;"></td>';
                                echo '<td style="vertical-align: middle;"><span class="news-title">' . htmlspecialchars($news['title']) . '</span></td>';
                                echo '<td class="text-center" style="vertical-align: middle; color: #818cf8; font-size: 12px; font-weight: 500;">' . $news['date'] . '</td>';
                                echo '<td class="text-center" style="vertical-align: middle;">';
                                    echo '<a href="javascript:void(0)" onclick=\'editNews(' . json_encode($news) . ')\' style="color:#64748b; font-size: 18px; margin-right: 15px;"><i class="fa fa-pencil"></i></a>';
                                    echo '<a href="javascript:void(0)" onclick="openDeleteModal(\'' . $moduleUrl . '&delete=' . $news['id'] . '\')" style="color:#64748b; font-size: 18px;"><i class="fa fa-trash"></i></a>';
                                echo '</td>';
                            echo '</tr>';
                        }
                    } else {
                        echo '<tr><td colspan="4" class="text-center" style="padding:20px; color:#666;">No hay noticias creadas aún.</td></tr>';
                    }

                    echo '</tbody>';
                echo '</table>';
            echo '</div>'; // Fin table-responsive
        echo '</div>'; // Fin settings-section
    echo '</div>'; // Fin col-lg-8

    // COLUMNA DERECHA: DISEÑO
    echo '<div class="col-lg-4">';
        echo '<div class="settings-section">';
            echo '<h3 class="settings-title" style="border:none; margin-bottom: 20px;">Diseño de Noticias</h3>';
            
            echo '<form action="' . $moduleUrl . '" method="post" id="layoutForm">';
                echo '<div class="layout-options-container">';
                    
                    // Opción Cascada
                    $activeCascada = ($currentLayout == 'cascada') ? 'active' : '';
                    echo '<div class="layout-option ' . $activeCascada . '" onclick="selectLayout(\'cascada\')">';
                        echo '<div class="layout-icon-box" style="color: #a855f7;"><i class="fa fa-th-list"></i></div>';
                        echo '<span class="layout-name">Cascada</span>';
                        echo '<div class="custom-radio"></div>';
                    echo '</div>';

                    // Opción Full
                    $activeFull = ($currentLayout == 'full') ? 'active' : '';
                    echo '<div class="layout-option ' . $activeFull . '" onclick="selectLayout(\'full\')">';
                        echo '<div class="layout-icon-box" style="color: #f97316;"><i class="fa fa-clipboard"></i></div>';
                        echo '<span class="layout-name">Full</span>';
                        echo '<div class="custom-radio"></div>';
                    echo '</div>';

                    // Opción Expandida
                    $activeExp = ($currentLayout == 'expandida') ? 'active' : '';
                    echo '<div class="layout-option ' . $activeExp . '" onclick="selectLayout(\'expandida\')">';
                        echo '<div class="layout-icon-box" style="color: #eab308;"><i class="fa fa-ticket"></i></div>';
                        echo '<span class="layout-name">Comprimida</span>';
                        echo '<div class="custom-radio"></div>';
                    echo '</div>';

                    echo '<input type="hidden" name="news_layout_type" id="news_layout_type" value="' . $currentLayout . '">';
                    echo '<input type="hidden" name="save_layout" value="1">';
                echo '</div>';

                echo '<div style="text-align: center;">';
                    echo '<button type="submit" class="btn-save-green" style="float: none; width: 100%; border:none; cursor:pointer;">';
                        echo '<i class="fa fa-save"></i> Guardar Cambios';
                    echo '</button>';
                echo '</div>';
            echo '</form>';
        echo '</div>';
    echo '</div>'; // Fin col-lg-4

echo '</div>'; // Fin row principal
echo '</div>'; // Fin container-fluid

// --- MODAL DE EDICIÓN ---
echo '<div id="modal-news" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.8); z-index:9999; justify-content:center; align-items:center; padding: 20px;">';
    echo '<div class="settings-section" style="width:100%; max-width:800px; background:#0d1117; border: 1px solid #30363d; padding:30px;">';
        echo '<h3 id="modal-title" class="settings-title" style="font-family:\'Cinzel\', serif; color:#FF8A3B; margin-bottom: 20px;">Redactar Noticia</h3>';
        
        echo '<form action="' . $moduleUrl . '" method="post" enctype="multipart/form-data" onsubmit="document.getElementById(\'news_content_hidden\').value = quill.root.innerHTML;">';
            echo '<input type="hidden" name="edit_id" id="edit_id">';
            echo '<input type="hidden" name="old_img" id="old_img">';
            
            echo '<div class="form-group">';
                echo '<label class="settings-label">Título</label>';
                echo '<input type="text" name="news_title" id="news_title_input" class="form-control-dark" style="font-family:\'Cinzel\', serif;" required>';
            echo '</div>';
            
            echo '<div class="form-group">';
                echo '<label class="settings-label">Contenido (Roboto)</label>';
                echo '<div id="editor" style="height:250px; background:#fff; color:#000; border-radius: 4px;"></div>';
                echo '<input type="hidden" name="news_content_hidden" id="news_content_hidden">';
            echo '</div>';
            
            echo '<div class="form-group">';
                echo '<label class="settings-label">Imagen (Opcional)</label>';
                echo '<div id="edit-img-preview" style="margin-bottom:10px; display:none;">';
                    echo '<img src="" style="width:60px; height:60px; border:1px solid #333; border-radius:4px; object-fit:cover;">';
                    echo '<p style="font-size:10px; color:#888;">Imagen actual conservada</p>';
                echo '</div>';
                echo '<input type="file" name="news_img" class="form-control-dark">';
            echo '</div>';
            
            echo '<div style="display:flex; justify-content: flex-end; gap: 10px; margin-top:20px;">';
                echo '<button type="button" class="btn btn-dark" onclick="document.getElementById(\'modal-news\').style.display=\'none\'">Cancelar</button>';
                echo '<button type="submit" name="save_news" class="btn-save-green">Publicar Noticia</button>';
            echo '</div>';
        echo '</form>';
    echo '</div>';
echo '</div>';

// --- MODAL DELETE CUSTOM ---
echo '<div id="customDeleteModal" class="modal-overlay">';
    echo '<div class="modal-box">';
        echo '<div class="modal-header-c"><i class="fa fa-exclamation-triangle"></i> Confirmar Eliminación</div>';
        echo '<div class="modal-body-c">';
            echo '<p>¿Estás seguro de que deseas eliminar esta noticia?</p>';
            echo '<span class="modal-subtext">Esta acción no se puede deshacer.</span>';
        echo '</div>';
        echo '<div class="modal-footer-c">';
            echo '<button class="btn-modal-cancel" onclick="closeDeleteModal()">Cancelar</button>';
            echo '<a href="#" id="btnConfirmDelete" class="btn-modal-confirm">Eliminar</a>';
        echo '</div>';
    echo '</div>';
echo '</div>';

// --- JAVASCRIPT ---
echo '<script>
    var quill = new Quill("#editor", { theme: "snow", modules: { toolbar: [["bold", "italic", "underline"], [{ "size": ["small", false, "large", "huge"] }], [{ "color": [] }, { "background": [] }], [{ "align": [] }], ["clean"]] } });

    function selectLayout(val) {
        document.querySelectorAll(".layout-option").forEach(el => el.classList.remove("active"));
        event.currentTarget.classList.add("active");
        document.getElementById("news_layout_type").value = val;
    }

    function openNewsModal() {
        document.getElementById("modal-title").innerText = "Redactar Noticia";
        document.getElementById("edit_id").value = "";
        document.getElementById("old_img").value = "";
        document.getElementById("news_title_input").value = "";
        document.getElementById("edit-img-preview").style.display = "none";
        quill.root.innerHTML = "";
        document.getElementById("modal-news").style.display = "flex";
    }

    function editNews(data) {
        document.getElementById("modal-title").innerText = "Editar Noticia";
        document.getElementById("edit_id").value = data.id;
        document.getElementById("old_img").value = data.image;
        document.getElementById("news_title_input").value = data.title;
        if(data.image) {
            document.querySelector("#edit-img-preview img").src = "' . __BASE_URL__ . '" + data.image;
            document.getElementById("edit-img-preview").style.display = "block";
        } else {
            document.getElementById("edit-img-preview").style.display = "none";
        }
        quill.root.innerHTML = data.content;
        document.getElementById("modal-news").style.display = "flex";
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
        var modalNews = document.getElementById("modal-news");
        
        if (event.target == modal) {
            modal.style.display = "none";
        }
        if (event.target == modalNews) {
            modalNews.style.display = "none";
        }
    }
</script>';
?>