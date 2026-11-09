<?php
/**
 * Inventory Data Manager - MU Caníbal
 * PREPARED FOR OBFUSCATION (Pure PHP Version)
 * ARCHIVO CRÍTICO: Renderizado de inventario visual y generación de Tooltips.
 */

// Usamos ruta absoluta del servidor para evitar que PHP se pierda
$managerPath = $_SERVER['DOCUMENT_ROOT'] . '/includes/config/items_manager.php';
if(file_exists($managerPath)) {
    require_once($managerPath);
}

class InventoryData {

    public static function renderInventory($inventory_hex) {
        if (empty($inventory_hex)) return "";
        $HEX_LENGTH = 32; 

        $slots_map = [
            2  => ['name' => 'Casco',     'top' => 25,  'left' => 115], 
            7  => ['name' => 'Alas',      'top' => 20,  'left' => 170], 
            8  => ['name' => 'Pet',       'top' => 20,  'left' => 20],  
            9  => ['name' => 'Collar',    'top' => 83,  'left' => 75],  
            3  => ['name' => 'Pechera',   'top' => 115,  'left' => 115], 
            0  => ['name' => 'Arma Der',  'top' => 115,  'left' => 15],  
            1  => ['name' => 'Arma Izq',  'top' => 115,  'left' => 210], 
            4  => ['name' => 'Pants',     'top' => 210, 'left' => 115], 
            5  => ['name' => 'Gloves',    'top' => 210, 'left' => 15],  
            6  => ['name' => 'Botas',     'top' => 210, 'left' => 210], 
            10 => ['name' => 'Anillo 1',  'top' => 195, 'left' => 80],  
            11 => ['name' => 'Anillo 2',  'top' => 195, 'left' => 175], 
        ];

        $html = "";
        foreach ($slots_map as $slotIndex => $pos) {
            $offset = $slotIndex * $HEX_LENGTH;
            if (strlen($inventory_hex) >= $offset + $HEX_LENGTH) {
                $item_hex = substr($inventory_hex, $offset, $HEX_LENGTH);
                $checkEmpty = strtoupper(substr($item_hex, 0, 8));
                if ($checkEmpty !== 'FFFFFFFF' && $item_hex !== str_repeat('0', $HEX_LENGTH) && !empty($item_hex)) {
                    $id = hexdec(substr($item_hex, 0, 2));
                    $groupByte = hexdec(substr($item_hex, 18, 2));
                    $group = ($groupByte & 0xF0) >> 4;
                    $img_file = __BASE_URL__ . "img/items/" . $group . "-" . $id . ".gif";
                    $tip = self::getTooltip($item_hex);
                    $html .= "<div class='inv-slot' style='position:absolute; top:{$pos['top']}px; left:{$pos['left']}px;' data-tip='{$tip}'>";
                    $html .= "<img src='{$img_file}' onerror=\"this.src='".__BASE_URL__."img/items/no-item.gif'\">";
                    $html .= "</div>";
                }
            }
        }
        return $html;
    }
    
    public static function getTooltip($hex) {
        if(strlen($hex) < 32) return "";
        
        if(!class_exists('ItemsManager')) {
            $path = $_SERVER['DOCUMENT_ROOT'] . '/includes/config/items_manager.php';
            if(file_exists($path)) require_once($path);
        }

        // 1. Decodificación de Bytes
        $id = hexdec(substr($hex, 0, 2)); 
        $byte1 = hexdec(substr($hex, 2, 2)); 
        $byte7 = hexdec(substr($hex, 14, 2)); 
        $byte8 = hexdec(substr($hex, 16, 2)); 
        $byte10 = hexdec(substr($hex, 20, 2)); 
        $byte11 = hexdec(substr($hex, 22, 2)); 
        $groupByte = hexdec(substr($hex, 18, 2)); 
        
        $group = ($groupByte & 0xF0) >> 4; 
        $level = ($byte1 >> 3) & 15; 
        $skill = ($byte1 >> 7) & 1; 
        $luck = ($byte1 >> 2) & 1;
        $option = ($byte1 & 3) + (($byte7 & 64) >> 4); 

        // Sockets (Bytes 11 al 15)
        $socketBytes = [
            hexdec(substr($hex, 22, 2)),
            hexdec(substr($hex, 24, 2)),
            hexdec(substr($hex, 26, 2)),
            hexdec(substr($hex, 28, 2)),
            hexdec(substr($hex, 30, 2))
        ];  
        
        $name = ItemsManager::getItemName($group, $id);
        $isExc = ($byte7 & 63) > 0;
        $isAcc = ($byte8 == 5 || $byte8 == 6 || $byte8 == 9 || $byte8 == 10 || $byte8 == 21 || $byte8 == 26); 
        
        $html = "<div class='tt-container'>";
        
        // 1. Título con Lógica de Color
        if ($isAcc) {
            $accBase = ItemsManager::getAncientName($byte8, $group, $id);
            $prefix = $isExc ? "Excellent " : "";
            $html .= "<span class='tt-name-acc'>{$prefix}{$accBase}'s {$name} +{$level}</span>";
        } else {
            $class = $isExc ? 'tt-name-exc' : 'tt-name-common';
            $prefix = $isExc ? "Excellent " : "";
            $html .= "<span class='{$class}'>{$prefix}{$name} +{$level}</span>";
        }
        
        // 2. Opciones 380
        $options380 = ItemsManager::get380Options($group, $id);
        if (!empty($options380)) {
            foreach ($options380 as $optName) {
                $html .= "<span style='color:#ad81ab; display:block; text-align:center; font-size:11px;'>{$optName}</span>";
            }
        }
        
        // 3. Opción Harmony
        if (($byte10 & 0x7F) > 0) {
            $hType = ($group <= 5) ? 1 : (($group >= 6 && $group <= 11) ? 6 : 5);
            $harmonyOpt = ItemsManager::getHarmonyOption($hType, $byte10);
            $html .= "<span style='color:#FFFF00; display:block; font-size:11px; text-align:center;'>{$harmonyOpt}</span><br>";
        }

        // 4. Opciones Base
        if($skill) $html .= "<span class='tt-opt-blue'>Skill Attack Power</span>";
        if($luck) {
            $html .= "<span class='tt-opt-blue'>Luck (success rate of Jewel of Soul +25%)</span>";
            $html .= "<span class='tt-opt-blue'>Luck (critical damage rate +5%)</span>";
        }
        
        if($option > 0) {
            $addName = ($group <= 5 || $group == 12) ? "Additional Damage" : "Additional Defense";
            $valAdd = $option * 4;
            $html .= "<span class='tt-opt-blue'>{$addName} +{$valAdd}</span>";
        }

        // 5. Opciones Excelentes
        if($isExc) {
            $opts = ItemsManager::getExcellentOptions($byte7, $group, $id);
            foreach($opts as $o) {
                $html .= "<span class='tt-opt-blue'>{$o}</span>";
            }
        }

        // 6. Opciones Ancient
        if($isAcc) {
            $html .= "<div style='margin-top:10px; color:#ff9600; text-align:center; font-size:11px; font-weight:bold;'>Set Item option info</div>";
            $accOptions = ItemsManager::getAncientOptions($byte8, $group, $id); 
            foreach($accOptions as $opt) {
                $html .= "<span style='color:#333333; display:block; text-align:center; font-size:11px;'>{$opt}</span>";
            }
        }

        // 7. Lógica de Sockets
        $hasSockets = false;
        $socketHTML = "";
        foreach ($socketBytes as $sByte) {
            $optText = ItemsManager::getSocketOption($sByte);
            if (!empty($optText)) {
                $hasSockets = true;
                $socketHTML .= "<span class='tt-opt-socket'>{$optText}</span>";
            }
        }

        if ($hasSockets) {
            $html .= "<div style='margin-top:5px; border-top:1px solid #444; padding-top:5px;'>{$socketHTML}</div>";
        }

        $html .= "</div>";
        return htmlspecialchars($html, ENT_QUOTES);
    }
}