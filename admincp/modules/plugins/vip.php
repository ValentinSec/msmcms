<?php
/**
 * Módulo de Gestión de Packs VIP (In-Game Currency Version)
 * Fix: Botones Borrar/Ordenar, Limpieza de Buffer, Modales y Warnings de Array
 */

// ====================================================================================
// 1. CARGA INTELIGENTE DE CONFIGURACIONES
// ====================================================================================

function findConfigFile($filename) {
    $candidates = [
        defined('__PATH_INCLUDES__') ? __PATH_INCLUDES__ . 'config/' . $filename : '',
        $_SERVER['DOCUMENT_ROOT'] . '/includes/config/' . $filename,
        __DIR__ . '/../../includes/config/' . $filename,
        __DIR__ . '/../../../includes/config/' . $filename
    ];

    foreach ($candidates as $path) {
        if (!empty($path) && file_exists($path)) {
            return $path;
        }
    }
    
    // Si no existe pero tenemos la constante principal, devolvemos esa ruta para poder CREARLO
    if (defined('__PATH_INCLUDES__')) {
        return __PATH_INCLUDES__ . 'config/' . $filename;
    }
    return __DIR__ . '/../../includes/config/' . $filename;
}

$settingsPath = findConfigFile('custom_settings.json');
$activeCurrencies = [];

if ($settingsPath && file_exists($settingsPath)) {
    $settings = json_decode(file_get_contents($settingsPath), true);
    for ($i = 1; $i <= 3; $i++) {
        $keyActive = 'active_coin_' . $i;
        $keyColumn = 'coin_' . $i . '_column';
        if (isset($settings[$keyActive])) {
            $val = $settings[$keyActive];
            $isTrue = ($val === true || $val === 1 || $val === '1' || strtolower((string)$val) === 'on' || strtolower((string)$val) === 'true');
            if ($isTrue) {
                $colName = isset($settings[$keyColumn]) ? trim($settings[$keyColumn]) : '';
                if (!empty($colName)) {
                    $activeCurrencies[] = $colName;
                }
            }
        }
    }
}

if (empty($activeCurrencies)) {
    $activeCurrencies[] = 'WCoinC';
}

// Asegurarnos de tener una ruta válida de escritura y lectura
$vipConfigPath = findConfigFile('vip_packages.json');

$vipPackages = [];
if (file_exists($vipConfigPath)) {
    $data = file_get_contents($vipConfigPath);
    if($data) {
        $vipPackages = json_decode($data, true);
        if (is_array($vipPackages)) {
            usort($vipPackages, function($a, $b) { return ($a['order'] ?? 0) <=> ($b['order'] ?? 0); });
        } else {
            $vipPackages = [];
        }
    }
}

// ====================================================================================
// 2. PROCESAR ACCIONES (POST)
// ====================================================================================
if($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    if(isset($_POST['action']) && $_POST['action'] === 'add_vip') {
        $newPack = [
            'id' => uniqid('vip_'),
            'vip_level' => $_POST['vip_level'],
            'days' => (int)$_POST['days'],
            'cost' => (int)$_POST['cost'],
            'currency' => isset($_POST['currency']) ? $_POST['currency'] : $activeCurrencies[0],
            'order' => count($vipPackages)
        ];
        $vipPackages[] = $newPack;
        
        // Creamos la carpeta si por alguna razón extraña no existe
        $dir = dirname($vipConfigPath);
        if (!is_dir($dir)) { @mkdir($dir, 0755, true); }
        
        file_put_contents($vipConfigPath, json_encode($vipPackages, JSON_PRETTY_PRINT));
        echo '<script>window.location.href="index.php?module=vip&success=created";</script>';
        exit;
    }

    if(isset($_POST['action']) && $_POST['action'] === 'delete_vip') {
        while(ob_get_level()) ob_end_clean();
        $idToDelete = $_POST['pack_id'];
        $vipPackages = array_filter($vipPackages, function($p) use ($idToDelete) { return $p['id'] !== $idToDelete; });
        $vipPackages = array_values($vipPackages); // Reindexar
        if(file_put_contents($vipConfigPath, json_encode($vipPackages, JSON_PRETTY_PRINT))) {
            echo 'ok';
        } else {
            echo 'error_write';
        }
        exit;
    }

    if(isset($_POST['action']) && $_POST['action'] === 'reorder_vip') {
        while(ob_get_level()) ob_end_clean();
        $newOrder = $_POST['order']; 
        $reorderedList = [];
        if(is_array($newOrder)) {
            foreach($newOrder as $index => $id) {
                foreach($vipPackages as $pack) {
                    if($pack['id'] === $id) {
                        $pack['order'] = $index;
                        $reorderedList[] = $pack;
                        break;
                    }
                }
            }
            if(count($reorderedList) === count($vipPackages)) {
                file_put_contents($vipConfigPath, json_encode($reorderedList, JSON_PRETTY_PRINT));
                echo 'ok';
            }
        }
        exit;
    }
}

$vipNames = [
    1 => ['name' => 'BRONZE', 'color' => '#cd7f32', 'bg' => 'rgba(205, 127, 50, 0.2)'],
    2 => ['name' => 'SILVER', 'color' => '#e0e0e0', 'bg' => 'rgba(224, 224, 224, 0.2)'],
    3 => ['name' => 'GOLD',    'color' => '#FFD700', 'bg' => 'rgba(255, 215, 0, 0.2)']
];

// --- RENDERIZADO HTML ---

echo '<style>
    #vip-admin-wrapper { background-color: #0f1217 !important; color: #ffffff !important; padding: 25px !important; border-radius: 8px !important; font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif !important; }
    #vip-admin-wrapper h3 { color: #ffffff !important; font-weight: 700 !important; margin-top: 0 !important; text-transform: uppercase !important; }
    .btn-vip-add { background: #f39c12 !important; border: 1px solid #e67e22 !important; color: #fff !important; padding: 10px 20px !important; font-weight: bold !important; text-transform: uppercase !important; border-radius: 5px !important; cursor: pointer !important; display: inline-block !important; transition: all 0.3s ease !important; }
    .btn-vip-add:hover { background: #d35400 !important; box-shadow: 0 0 10px rgba(243, 156, 18, 0.5) !important; text-decoration: none !important; color: #fff !important; }
    .vip-table-container { background: #15171c !important; border: 1px solid #2d323e !important; border-radius: 8px !important; margin-top: 20px !important; overflow: hidden !important; }
    .vip-table { width: 100% !important; border-collapse: collapse !important; }
    .vip-table th { background: rgba(0,0,0,0.3) !important; color: #888 !important; font-weight: 700 !important; text-transform: uppercase !important; font-size: 11px !important; padding: 15px !important; border-bottom: 2px solid #2d323e !important; text-align: left !important; }
    .vip-table td { padding: 15px !important; border-bottom: 1px solid rgba(255,255,255,0.05) !important; color: #ccc !important; vertical-align: middle !important; }
    .vip-badge { padding: 4px 8px !important; border-radius: 4px !important; font-weight: bold !important; font-size: 11px !important; border: 1px solid !important; }
    .btn-vip-action { width: 30px !important; height: 30px !important; border: none !important; border-radius: 4px !important; color: #fff !important; cursor: pointer !important; margin-left: 2px !important; display: inline-flex !important; align-items: center !important; justify-content: center !important; }
    .b-up { background: #3498db !important; } .b-down { background: #3498db !important; } .b-del { background: #e74c3c !important; }
    .custom-dark-modal .modal-content { background-color: #1a1d24 !important; border: 1px solid #f39c12 !important; color: #fff !important; box-shadow: 0 0 50px rgba(0,0,0,0.7) !important; }
    .custom-dark-modal .modal-header { border-bottom: 1px solid #2d323e !important; }
    .custom-dark-modal .modal-footer { border-top: 1px solid #2d323e !important; }
    .custom-dark-modal .close { color: #fff !important; opacity: 1 !important; text-shadow: none !important; }
    .custom-dark-modal input, .custom-dark-modal select { background: #0f1217 !important; border: 1px solid #2d323e !important; color: #fff !important; height: 40px !important; }
    .custom-dark-modal input:focus, .custom-dark-modal select:focus { border-color: #f39c12 !important; }
    .alert-success-custom { background: #1b1b28 !important; border-left: 4px solid #00d084 !important; color: #fff !important; padding: 15px !important; margin-bottom: 20px !important; border-radius: 4px !important; box-shadow: 0 4px 10px rgba(0,0,0,0.2) !important; }
</style>';

echo '<div id="vip-admin-wrapper">';
    
    if(isset($_GET['success']) && $_GET['success'] == 'created') {
        echo '<div class="alert-success-custom">Cambios guardados correctamente.</div>';
    }

    echo '<div style="display: flex; justify-content: space-between; align-items: center;">';
        echo '<div>';
            echo '<h3><i class="fa fa-crown" style="color: #f39c12; margin-right: 8px;"></i> VIP Manager</h3>';
            echo '<p style="margin: 0; color: #777; font-size: 13px;">Gestiona los paquetes de venta (Moneda del Juego).</p>';
        echo '</div>';
        echo '<button type="button" class="btn-vip-add" onclick="openVipModal()">';
            echo '<i class="fa fa-plus-circle"></i> Nuevo Pack';
        echo '</button>';
    echo '</div>';

    echo '<div class="vip-table-container">';
        echo '<table class="vip-table">';
            echo '<thead>';
                echo '<tr>';
                    echo '<th>Nivel</th>';
                    echo '<th>Días</th>';
                    echo '<th>Precio / Moneda</th>';
                    echo '<th style="text-align: right;">Acciones</th>';
                echo '</tr>';
            echo '</thead>';
            echo '<tbody id="vip-table-body">';
                
                if(empty($vipPackages)) {
                    echo '<tr><td colspan="4" style="text-align: center; padding: 40px; color: #555;">No hay paquetes configurados.</td></tr>';
                } else {
                    foreach($vipPackages as $pack) {
                        $pLvl = isset($pack['vip_level']) ? $pack['vip_level'] : 1;
                        $vInfo = isset($vipNames[$pLvl]) ? $vipNames[$pLvl] : ['name' => '?', 'color' => '#fff', 'bg' => '#000'];
                        $pDays = isset($pack['days']) ? $pack['days'] : 0;
                        $pCost = isset($pack['cost']) ? $pack['cost'] : 0;
                        // FIX TERNARIO: Evita el Undefined array key "currency"
                        $pCurrency = isset($pack['currency']) ? htmlspecialchars($pack['currency']) : 'Desconocida';
                        $pId = isset($pack['id']) ? $pack['id'] : '';

                        echo '<tr data-id="' . $pId . '">';
                            echo '<td><span class="vip-badge" style="color: ' . $vInfo['color'] . '; background: ' . $vInfo['bg'] . '; border-color: ' . $vInfo['color'] . ';">' . $vInfo['name'] . '</span></td>';
                            echo '<td><strong>' . $pDays . '</strong> Días</td>';
                            echo '<td><strong style="color: #f39c12;">' . number_format($pCost) . '</strong> <span style="font-size: 11px; color: #aaa;">' . $pCurrency . '</span></td>';
                            echo '<td style="text-align: right;">';
                                echo '<button class="btn-vip-action b-up" onclick="movePack(\'' . $pId . '\', \'up\')"><i class="fa fa-chevron-up"></i></button>';
                                echo '<button class="btn-vip-action b-down" onclick="movePack(\'' . $pId . '\', \'down\')"><i class="fa fa-chevron-down"></i></button>';
                                echo '<button class="btn-vip-action b-del" onclick="askDeletePack(\'' . $pId . '\')"><i class="fa fa-trash"></i></button>';
                            echo '</td>';
                        echo '</tr>';
                    }
                }
            echo '</tbody>';
        echo '</table>';
    echo '</div>'; 
echo '</div>'; 

// Modal Agregar
echo '<div class="modal fade custom-dark-modal" id="modalAddVipManual" tabindex="-1" role="dialog" aria-hidden="true" style="z-index: 9999;">';
    echo '<div class="modal-dialog modal-dialog-centered" role="document">';
        echo '<div class="modal-content">';
            echo '<div class="modal-header">';
                echo '<h5 class="modal-title" style="color: #f39c12; font-weight: bold;">AGREGAR VIP</h5>';
                echo '<button type="button" class="close" onclick="closeVipModal()"><span aria-hidden="true">&times;</span></button>';
            echo '</div>';
            echo '<form method="post" action="">';
                echo '<input type="hidden" name="action" value="add_vip">';
                echo '<div class="modal-body">';
                    echo '<div class="form-group" style="margin-bottom: 15px;"><label>Tipo de VIP</label><select name="vip_level" class="form-control"><option value="1">BRONZE (Nivel 1)</option><option value="2">SILVER (Nivel 2)</option><option value="3">GOLD (Nivel 3)</option></select></div>';
                    echo '<div class="form-group" style="margin-bottom: 15px;"><label>Días</label><input type="number" name="days" class="form-control" placeholder="30" required></div>';
                    echo '<div class="row"><div class="col-md-6 col-xs-6"><div class="form-group"><label>Costo</label><input type="number" name="cost" class="form-control" placeholder="1000" required></div></div>';
                    echo '<div class="col-md-6 col-xs-6"><div class="form-group"><label>Moneda</label><select name="currency" class="form-control">';
                    foreach($activeCurrencies as $coin) { echo '<option value="' . $coin . '">' . $coin . '</option>'; }
                    echo '</select></div></div></div>';
                echo '</div>';
                echo '<div class="modal-footer"><button type="button" class="btn btn-secondary" onclick="closeVipModal()">Cancelar</button><button type="submit" class="btn btn-success" style="background: #f39c12; border:none;">Guardar</button></div>';
            echo '</form>';
        echo '</div>';
    echo '</div>';
echo '</div>';

// Modal Eliminar
echo '<div class="modal fade custom-dark-modal" id="modalDeleteVip" tabindex="-1" role="dialog" aria-hidden="true" style="z-index: 10000;">';
    echo '<div class="modal-dialog modal-sm modal-dialog-centered" role="document">';
        echo '<div class="modal-content text-center">';
            echo '<div class="modal-body py-4">';
                echo '<i class="fa fa-exclamation-triangle text-danger fa-3x mb-3"></i>';
                echo '<h5 class="text-white fw-bold">¿Eliminar Paquete?</h5>';
                echo '<p class="text-muted" style="font-size: 13px;">Esta acción no se puede deshacer.</p>';
                echo '<input type="hidden" id="delete_pack_id">';
                echo '<div class="d-flex justify-content-center gap-2 mt-4">';
                    echo '<button type="button" class="btn btn-secondary btn-sm px-4" onclick="closeDeleteModal()">No</button>';
                    echo '<button type="button" class="btn btn-danger btn-sm px-4" onclick="confirmDeletePack()">Sí, Eliminar</button>';
                echo '</div>';
            echo '</div>';
        echo '</div>';
    echo '</div>';
echo '</div>';

// JavaScript
echo '<script>
function openVipModal() {
    if (typeof jQuery != "undefined") { jQuery("#modalAddVipManual").modal("show"); } 
    else { 
        var m = document.getElementById("modalAddVipManual"); m.style.display = "block"; m.classList.add("show"); document.body.classList.add("modal-open");
        var bd = document.createElement("div"); bd.className = "modal-backdrop fade show"; bd.id = "manual-backdrop"; document.body.appendChild(bd);
    }
}
function closeVipModal() {
    if (typeof jQuery != "undefined") { jQuery("#modalAddVipManual").modal("hide"); } 
    else { 
        var m = document.getElementById("modalAddVipManual"); m.style.display = "none"; m.classList.remove("show"); document.body.classList.remove("modal-open");
        var bd = document.getElementById("manual-backdrop"); if(bd) bd.remove();
    }
}

function askDeletePack(id) {
    document.getElementById("delete_pack_id").value = id;
    if (typeof jQuery != "undefined") { jQuery("#modalDeleteVip").modal("show"); } 
    else {
        var m = document.getElementById("modalDeleteVip"); m.style.display = "block"; m.classList.add("show"); document.body.classList.add("modal-open");
        var bd = document.createElement("div"); bd.className = "modal-backdrop fade show"; bd.id = "delete-backdrop"; document.body.appendChild(bd);
    }
}

function closeDeleteModal() {
    if (typeof jQuery != "undefined") { jQuery("#modalDeleteVip").modal("hide"); } 
    else {
        var m = document.getElementById("modalDeleteVip"); m.style.display = "none"; m.classList.remove("show"); document.body.classList.remove("modal-open");
        var bd = document.getElementById("delete-backdrop"); if(bd) bd.remove();
    }
}

function confirmDeletePack() {
    var id = document.getElementById("delete_pack_id").value;
    var fd = new FormData(); fd.append("action", "delete_vip"); fd.append("pack_id", id);
    fetch(window.location.href, {method:"POST", body:fd})
        .then(r => r.text())
        .then(d => { if(d.trim() == "ok") window.location.reload(); });
}

function movePack(id, dir) {
    const rows = Array.from(document.querySelectorAll("#vip-table-body tr"));
    const idx = rows.findIndex(r => r.getAttribute("data-id") === id);
    if(idx === -1) return;
    if(dir === "up" && idx === 0) return;
    if(dir === "down" && idx === rows.length - 1) return;
    const curr = rows[idx];
    const tgt = dir === "up" ? rows[idx-1] : rows[idx+1];
    dir === "up" ? tgt.before(curr) : tgt.after(curr);
    const newOrder = Array.from(document.querySelectorAll("#vip-table-body tr")).map(r => r.getAttribute("data-id"));
    var fd = new FormData(); fd.append("action", "reorder_vip"); newOrder.forEach(i => fd.append("order[]", i));
    fetch(window.location.href, {method:"POST", body:fd});
}
</script>';
?>