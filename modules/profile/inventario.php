<?php
/**
 * Character Equipment Viewer - PREPARED FOR OBFUSCATION
 * Pure PHP Version (Concatenation Safe)
 */

// FORZAR DEBUG EN PANTALLA
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (!isset($charData)) return;

$inventory_hex = !empty($charData['Inventory']) ? bin2hex($charData['Inventory']) : "";
$invDataPath = $_SERVER['DOCUMENT_ROOT'] . '/includes/config/inventory_data.php';

if(file_exists($invDataPath)) {
    try {
        require_once($invDataPath);
    } catch (Exception $e) {
        echo "Error cargando inventory_data: " . $e->getMessage();
    }
} else {
    echo "ERROR: No se encuentra el archivo en: " . $invDataPath;
}

$ancho_mapa = 280; 
$alto_mapa  = 280; 
$escala_items = 1; 

// --- RENDERIZADO HTML & CSS (PHP PURO) ---

echo '<style>
    .equipment-map {
        background-image: url("' . __BASE_URL__ . 'img/equipment.png") !important;
        background-size: 100% 100%;
        background-repeat: no-repeat;
        position: relative; 
        width: ' . $ancho_mapa . 'px; 
        height: ' . $alto_mapa . 'px;
        margin: 0 auto;
    }
    .inv-slot {
        position: absolute; 
        display: flex;
        align-items: center;
        justify-content: center;
        width: 40px; 
        height: 40px;
        background: transparent !important;
        border: none !important;
    }
    .inv-slot img {
        display: block;
        transform: scale(' . $escala_items . ');
        transform-origin: center;
        filter: drop-shadow(0 0 2px rgba(0,0,0,0.7));
    }
</style>';

echo '<div class="inventory-master-container">';
    echo '<h4 class="text-orange text-uppercase border-bottom border-secondary border-opacity-25 pb-2 mb-3 trajan-title d-block w-100">';
        echo 'EQUIPAMIENTO';
    echo '</h4>';
    
    echo '<div class="equipment-map">';
        if(!empty($inventory_hex) && class_exists('InventoryData')) {
            echo InventoryData::renderInventory($inventory_hex); 
        }
    echo '</div>';
echo '</div>';

echo '<link rel="stylesheet" href="' . __BASE_URL__ . 'templates/misc/css/inventory_viewer.css">';
echo '<script src="' . __BASE_URL__ . 'templates/misc/js/inventory_tooltip.js"></script>';
?>