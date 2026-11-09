<?php
/**
 * Editor de Personajes - AdminCP
 * PREPARED FOR OBFUSCATION (Pure PHP Version)
 * Versión Final: AJAX, Mapas, Guild, CtlCode, Reset y Estilo Success Estático
 */
if(!defined('access')) die();

// 1. LÓGICA DE GUARDADO AJAX (API JSON)
if (isset($_POST['ajax_update']) && $_POST['ajax_update'] == 1) {
    if (ob_get_length()) ob_clean();
    header('Content-Type: application/json');

    try {
        $db = Connection::Database('Me_MuOnline');
        $charName = $_POST['name'];

        $updateQuery = "UPDATE Character SET 
            cLevel = ?, 
            ResetCount = ?,
            Strength = ?, 
            Dexterity = ?, 
            Vitality = ?, 
            Energy = ?, 
            Leadership = ?, 
            LevelUpPoint = ?, 
            Money = ?,
            PkLevel = ?,
            CtlCode = ?,
            Class = ?
            WHERE Name = ?";
        
        $params = [
            $_POST['cLevel'], 
            $_POST['ResetCount'], 
            $_POST['Strength'], 
            $_POST['Dexterity'], 
            $_POST['Vitality'], 
            $_POST['Energy'], 
            $_POST['Leadership'], 
            $_POST['LevelUpPoint'], 
            $_POST['Money'],
            $_POST['PkLevel'],
            $_POST['CtlCode'],    
            $_POST['Class'], 
            $charName
        ];

        $db->query($updateQuery, $params);
        
        echo json_encode(['status' => 'success', 'message' => 'Cambios guardados correctamente.']);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    die();
}

// 2. LÓGICA DE VISUALIZACIÓN
$name = isset($_GET['name']) ? $_GET['name'] : (isset($_POST['name']) ? $_POST['name'] : null);

if (!$name) {
    echo '<div class="alert alert-danger">No se especificó un nombre de personaje.</div>';
    return;
}

try {
    $db = Connection::Database('Me_MuOnline');
    
    // Consulta con JOIN para obtener el Guild
    $char = $db->query_fetch_single("
        SELECT c.*, gm.G_Name 
        FROM Character c
        LEFT JOIN GuildMember gm ON c.Name = gm.Name
        WHERE c.Name = ?", [$name]);
    
    if (!$char) throw new Exception("Personaje no encontrado.");

    $charData = ['Name' => $char['Name'], 'Inventory' => $char['Inventory']];

    // Lógica visual para el modelo 3D
    $class = $char['Class'];
    $baseName = 'dw'; 
    if (($class >= 0 && $class <= 9)) { $baseName = 'dw'; } 
    elseif (($class >= 16 && $class <= 25)) { $baseName = 'dk'; } 
    elseif (($class >= 32 && $class <= 42)) { $baseName = 'elf'; } 
    elseif (($class >= 48 && $class <= 58)) { $baseName = 'mg'; } 
    elseif (($class >= 64 && $class <= 77)) { $baseName = 'dl'; } 
    elseif (($class >= 80 && $class <= 90)) { $baseName = 'sum'; } 
    elseif (($class >= 96 && $class <= 106)) { $baseName = 'rf'; } 
    elseif (($class >= 112 && $class <= 122)) { $baseName = 'gl'; } 
    elseif (($class >= 128 && $class <= 138)) { $baseName = 'rw'; } 
    elseif (($class >= 144 && $class <= 154)) { $baseName = 'sl'; }
    elseif (($class >= 160 && $class <= 170)) { $baseName = 'gc'; }

    $modelPath = __BASE_URL__ . 'img/models/Faces/' . $baseName . '.gltf';
    $avatarPath = __BASE_URL__ . 'templates/default/img/character-avatars/' . $baseName . '.jpg';

    // Lista de Mapas
    $mapNames = [
        0 => "Lorencia", 1 => "Dungeon", 2 => "Devias", 3 => "Noria", 4 => "Lost Tower",
        6 => "Arena", 7 => "Atlans", 8 => "Tarkan", 9 => "Devil Square", 10 => "Icarus",
        11 => "Blood Castle 1", 12 => "Blood Castle 2", 13 => "Blood Castle 3", 14 => "Blood Castle 4",
        15 => "Blood Castle 5", 16 => "Blood Castle 6", 17 => "Blood Castle 7",
        18 => "Chaos Castle 1", 19 => "Chaos Castle 2", 20 => "Chaos Castle 3", 21 => "Chaos Castle 4",
        22 => "Chaos Castle 5", 23 => "Chaos Castle 6",
        24 => "Kalima 1", 25 => "Kalima 2", 26 => "Kalima 3", 27 => "Kalima 4", 28 => "Kalima 5", 29 => "Kalima 6",
        30 => "Valley of Loren", 31 => "Land of Trials", 32 => "Devil Square", 33 => "Aida",
        34 => "Crywolf Fortress", 36 => "Kalima 7", 37 => "Kanturu", 38 => "Kanturu 2", 39 => "Kanturu 3",
        40 => "Silent Map", 41 => "B barracks", 42 => "Refuge",
        45 => "Illusion Temple 1", 46 => "Illusion Temple 2", 47 => "Illusion Temple 3",
        48 => "Illusion Temple 4", 49 => "Illusion Temple 5", 50 => "Illusion Temple 6",
        51 => "Elbeland", 52 => "Blood Castle 8", 53 => "Chaos Castle 7",
        56 => "Swamp of Calmness", 57 => "Raklion", 58 => "Raklion Boss",
        62 => "Santa's Village", 63 => "Vulcanus", 64 => "Duel Arena",
        65 => "Doppelganger", 66 => "Doppelganger", 67 => "Doppelganger", 68 => "Doppelganger",
        69 => "Empire Fortress", 70 => "Empire Fortress", 71 => "Empire Fortress", 72 => "Empire Fortress",
        79 => "Loren Market", 80 => "Karutan 1", 81 => "Karutan 2"
    ];

    // Lista de Razas
    $raceList = [
        "Dark Wizard / SM" => [0 => "Dark Wizard", 1 => "Soul Master", 2 => "Grand Master", 3 => "Soul Wizard (S17)"],
        "Dark Knight / BK" => [16 => "Dark Knight", 17 => "Blade Knight", 18 => "Blade Master", 19 => "Dragon Knight (S17)"],
        "Fairy Elf / ME" => [32 => "Fairy Elf", 33 => "Muse Elf", 34 => "High Elf", 35 => "Noble Elf (S17)"],
        "Magic Gladiator / DM" => [48 => "Magic Gladiator", 50 => "Duel Master", 49 => "Magic Knight (S17)"],
        "Dark Lord / LE" => [64 => "Dark Lord", 66 => "Lord Emperor", 65 => "Empire Lord (S17)"],
        "Summoner / BS" => [80 => "Summoner", 81 => "Bloody Summoner", 82 => "Dimension Master", 83 => "Dimension Summoner (S17)"],
        "Rage Fighter / FM" => [96 => "Rage Fighter", 98 => "Fist Master", 97 => "Fist Blazer (S17)"],
        "Grow Lancer / ML" => [112 => "Grow Lancer", 114 => "Mirage Lancer", 113 => "Shining Lancer (S17)"]
    ];

    // 1. CARGA DE LIBRERÍAS DE INVENTARIO
    $invDataPath = $_SERVER['DOCUMENT_ROOT'] . '/includes/config/inventory_data.php';
    if(file_exists($invDataPath)) {
        require_once($invDataPath);
    }

    // 2. CONFIGURACIÓN VISUAL
    $ancho_mapa = 260; 
    $alto_mapa  = 270; 
    $escala_items = 1;
    
    // 3. PREPARAR DATOS
    $inventory_hex = !empty($charData['Inventory']) ? bin2hex($charData['Inventory']) : "";

    // --- RENDERIZADO HTML (PHP PURO) ---

    // CSS
    echo '<style>
        /* Reset Inputs */
        input::-webkit-outer-spin-button, input::-webkit-inner-spin-button { -webkit-appearance: none; margin: 0; }
        input[type=number] { -moz-appearance: textfield; }

        /* ESTILO TARJETA DE ÉXITO */
        .alert-success-custom { 
            background: #1b1b28; 
            border-left: 4px solid #00d084; 
            color: #fff; 
            padding: 15px; 
            margin-bottom: 20px; 
            border-radius: 4px; 
            box-shadow: 0 4px 10px rgba(0,0,0,0.2); 
            display: none; 
            font-family: "Segoe UI", sans-serif;
            font-size: 14px;
            font-weight: 500;
        }
        .alert-error-custom { border-left: 4px solid #e74c3c; }

        /* Layout */
        .editor-container { display: flex; gap: 20px; color: #fff; font-family: "Segoe UI", sans-serif; align-items: stretch; flex-wrap: wrap; }
        .editor-column { background: #151515; border: 1px solid #333; border-radius: 8px; overflow: hidden; display: flex; flex-direction: column; flex: 1; min-width: 300px; }
        .panel-header { background: #00b894; color: #000; font-weight: 700; padding: 10px 15px; text-transform: uppercase; font-size: 12px; display: flex; align-items: center; justify-content: space-between; }
        
        .form-group-custom { padding: 12px 15px; border-bottom: 1px solid #222; }
        .form-group-custom label { display: block; color: #aaa; font-size: 11px; font-weight: 600; margin-bottom: 5px; text-transform: uppercase; }
        .input-dark-edit { width: 100%; background: #000; border: 1px solid #333; border-radius: 4px; padding: 8px 10px; color: #fff; font-size: 13px; outline: none; transition: 0.3s; }
        .input-dark-edit:focus { border-color: #00b894; }
        
        /* Optgroup para selects */
        option { background: #111; color: #fff; }
        optgroup { background: #222; color: #aaa; font-style: normal; font-weight: bold; }

        #character-3d-container { height: 350px; background: radial-gradient(circle, #2a2a2a 0%, #000 100%); position: relative; overflow: hidden; }
        .inventory-wrapper { background: #111; border-top: 1px solid #333; padding: 10px; display: flex; justify-content: center; overflow: hidden; }
        
        .btn-save-main { width: 100%; background: #00b894; border: none; color: #000; padding: 15px; font-weight: 800; text-transform: uppercase; cursor: pointer; transition: 0.3s; margin-top: auto; }
        .btn-save-main:hover { background: #00a180; }
        .btn-save-main:disabled { background: #555; cursor: not-allowed; }

        .info-box-util { background: rgba(255,255,255,0.05); border: 1px solid #333; border-radius: 4px; padding: 10px; margin-bottom: 8px; font-size: 12px; color: #888; }
        .info-box-util span { color: #00b894; font-weight: 600; float: right; }
        .util-title { color: #00b894; font-size: 11px; font-weight: 800; text-transform: uppercase; margin: 15px 15px 10px; display: block; }

        .money-input-wrapper { position: relative; }
        .btn-max-zen {
            position: absolute; right: 5px; top: 50%; transform: translateY(-50%);
            background: #333; border: 1px solid #444; color: #00b894; font-size: 10px; font-weight: bold;
            padding: 2px 8px; border-radius: 3px; cursor: pointer; text-transform: uppercase; transition: 0.2s;
        }
        .btn-max-zen:hover { background: #00b894; color: #000; border-color: #00b894; }

        /* ESTILOS INVENTARIO */
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
            z-index: 5;
        }
        .inv-slot img {
            display: block;
            transform: scale(' . $escala_items . ');
            transform-origin: center;
            filter: drop-shadow(0 0 2px rgba(0,0,0,0.7));
        }
    </style>';

    echo '<div id="js-alert-container"></div>';

    echo '<form id="charEditForm" action="" method="POST">';
        echo '<input type="hidden" name="ajax_update" value="1">';
        echo '<input type="hidden" name="name" value="' . $name . '">';

        echo '<div class="editor-container">';
            
            // Columna 1: Visual
            echo '<div class="editor-column">';
                echo '<div class="panel-header">Visual & Inventario</div>';
                echo '<div id="character-3d-container"></div>';
                
                echo '<div class="inventory-wrapper">';
                    echo '<div class="inventory-master-container" style="width: 100%; text-align: center;">';
                        echo '<div class="equipment-map">';
                            if(!empty($inventory_hex) && class_exists('InventoryData')) {
                                echo InventoryData::renderInventory($inventory_hex); 
                            } else {
                                if(empty($inventory_hex)) echo '<span style="color:#666; font-size:11px; display:block; padding-top:130px;">Inventario Vacío o Hex Inválido</span>';
                                if(!class_exists('InventoryData')) echo '<span style="color:red; font-size:11px; display:block; padding-top:130px;">Error: Clase InventoryData no cargada</span>';
                            }
                        echo '</div>';
                    echo '</div>';
                echo '</div>';
            echo '</div>';

            // Columna 2: Stats
            echo '<div class="editor-column">';
                echo '<div class="panel-header">Estadísticas</div>';
                echo '<div class="form-group-custom"><label>Level</label><input type="number" name="cLevel" class="input-dark-edit" value="' . $char['cLevel'] . '"></div>';
                echo '<div class="form-group-custom"><label>Reset</label><input type="number" name="ResetCount" class="input-dark-edit" value="' . (isset($char['ResetCount']) ? $char['ResetCount'] : 0) . '"></div>';
                echo '<div class="form-group-custom"><label>Fuerza</label><input type="number" name="Strength" class="input-dark-edit" value="' . $char['Strength'] . '"></div>';
                echo '<div class="form-group-custom"><label>Agilidad</label><input type="number" name="Dexterity" class="input-dark-edit" value="' . $char['Dexterity'] . '"></div>';
                echo '<div class="form-group-custom"><label>Vitalidad</label><input type="number" name="Vitality" class="input-dark-edit" value="' . $char['Vitality'] . '"></div>';
                echo '<div class="form-group-custom"><label>Energia</label><input type="number" name="Energy" class="input-dark-edit" value="' . $char['Energy'] . '"></div>';
                echo '<div class="form-group-custom"><label>Comando</label><input type="number" name="Leadership" class="input-dark-edit" value="' . (isset($char['Leadership']) ? $char['Leadership'] : 0) . '"></div>';
                echo '<div class="form-group-custom"><label style="color:#00b894;">Puntos Libres</label><input type="number" name="LevelUpPoint" class="input-dark-edit" value="' . $char['LevelUpPoint'] . '" style="border-color:#00b894;"></div>';
                
                echo '<button type="submit" id="btnSave" class="btn-save-main">Guardar Cambios</button>';
            echo '</div>';

            // Columna 3: Info Extra
            echo '<div class="editor-column">';
                echo '<div class="panel-header">Información Extra</div>';
                
                // CtlCode
                echo '<div class="form-group-custom">';
                    echo '<label>Tipo de Personaje / Estado</label>';
                    echo '<select name="CtlCode" class="input-dark-edit">';
                        echo '<option value="0" ' . ($char['CtlCode']==0 ? 'selected' : '') . '>Normal Player</option>';
                        echo '<option value="8" ' . ($char['CtlCode']==8 ? 'selected' : '') . '>Game Master</option>';
                        echo '<option value="32" ' . ($char['CtlCode']==32 ? 'selected' : '') . '>Administrador</option>';
                        echo '<option value="1" ' . ($char['CtlCode']==1 ? 'selected' : '') . '>Banned (Bloqueado)</option>';
                    echo '</select>';
                echo '</div>';

                // Raza
                echo '<div class="form-group-custom">';
                    echo '<label>Raza / Clase</label>';
                    echo '<select name="Class" class="input-dark-edit">';
                        foreach($raceList as $groupName => $classes) {
                            echo '<optgroup label="' . $groupName . '">';
                            foreach($classes as $code => $name) {
                                $sel = ($char['Class'] == $code) ? 'selected' : '';
                                echo '<option value="' . $code . '" ' . $sel . '>' . $name . '</option>';
                            }
                            echo '</optgroup>';
                        }
                    echo '</select>';
                    echo '<small style="color:#555; font-size:10px;">Cambiar la raza puede requerir re-loguear.</small>';
                echo '</div>';

                // Zen
                echo '<div class="form-group-custom">';
                    echo '<label>Zen (Dinero)</label>';
                    echo '<div class="money-input-wrapper">';
                        echo '<input type="number" name="Money" id="inputMoney" class="input-dark-edit" value="' . $char['Money'] . '">';
                        echo '<button type="button" class="btn-max-zen" onclick="setMaxMoney()">FULL</button>';
                    echo '</div>';
                echo '</div>';

                // PK Level
                echo '<div class="form-group-custom">';
                    echo '<label>Estado PK</label>';
                    echo '<select name="PkLevel" class="input-dark-edit">';
                        echo '<option value="3" ' . ($char['PkLevel']==3 ? 'selected' : '') . '>Commoner (Normal)</option>';
                        echo '<option value="4" ' . ($char['PkLevel']==4 ? 'selected' : '') . '>Pk Stage 1</option>';
                        echo '<option value="5" ' . ($char['PkLevel']==5 ? 'selected' : '') . '>Pk Stage 2</option>';
                        echo '<option value="6" ' . ($char['PkLevel']==6 ? 'selected' : '') . '>Murderer (Rojo)</option>';
                        echo '<option value="7" ' . ($char['PkLevel']==7 ? 'selected' : '') . '>Phonomania (Full)</option>';
                    echo '</select>';
                echo '</div>';

                // Stats Sistema
                echo '<span class="util-title">Datos del Sistema</span>';
                echo '<div style="padding: 0 15px;">';
                    echo '<div class="info-box-util">Cuenta <span>' . $char['AccountID'] . '</span></div>';
                    
                    $mapIndex = isset($char['MapNumber']) ? (int)$char['MapNumber'] : -1;
                    $mapName = isset($mapNames[$mapIndex]) ? $mapNames[$mapIndex] : "Desconocido (" . $mapIndex . ")";
                    echo '<div class="info-box-util">Mapa Actual <span>' . $mapName . '</span></div>';
                    
                    $guildName = isset($char['G_Name']) && !empty($char['G_Name']) ? $char['G_Name'] : 'Ninguno';
                    echo '<div class="info-box-util">Guild <span>' . $guildName . '</span></div>';
                    
                    echo '<div class="info-box-util">PK Count <span>' . (isset($char['PkCount']) ? $char['PkCount'] : 0) . '</span></div>';
                echo '</div>';

            echo '</div>'; // End Column 3

        echo '</div>'; // End Editor Container
    echo '</form>';

    // JavaScript
    echo '<script type="importmap">
    {
      "imports": {
        "three": "https://unpkg.com/three@0.150.1/build/three.module.js",
        "three/addons/": "https://unpkg.com/three@0.150.1/examples/jsm/"
      }
    }
    </script>';

    echo '<script type="module">
    import * as THREE from "three";
    import { GLTFLoader } from "three/addons/loaders/GLTFLoader.js";

    // AJAX Update
    document.getElementById("charEditForm").addEventListener("submit", function(e) {
        e.preventDefault(); 
        const btn = document.getElementById("btnSave");
        const originalText = btn.innerText;
        btn.disabled = true;
        btn.innerText = "Guardando...";

        const formData = new FormData(this);

        fetch(window.location.href, {
            method: "POST",
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            btn.disabled = false;
            btn.innerText = originalText;
            showStaticAlert(data.message, data.status === "success");
        })
        .catch(error => {
            console.error("Error:", error);
            btn.disabled = false;
            btn.innerText = originalText;
            showStaticAlert("Error de conexión con el servidor.", false);
        });
    });

    window.showStaticAlert = function(msg, success = true) {
        const container = document.getElementById("js-alert-container");
        const borderColor = success ? "#00d084" : "#e74c3c";
        
        container.innerHTML = `
            <div class="alert-success-custom" style="display:block; border-left-color: ${borderColor};">
                ${msg}
            </div>
        `;
        
        setTimeout(() => { 
            const alertBox = container.querySelector(".alert-success-custom");
            if(alertBox) alertBox.style.display = "none"; 
        }, 4000);
    }

    // 3D Visualizer
    document.addEventListener("DOMContentLoaded", function() {
        const container = document.getElementById("character-3d-container");
        if (!container) return;

        const scene = new THREE.Scene();
        const camera = new THREE.PerspectiveCamera(45, container.clientWidth / container.clientHeight, 0.1, 1000);
        const renderer = new THREE.WebGLRenderer({ antialias: true, alpha: true });
        
        renderer.setSize(container.clientWidth, container.clientHeight);
        renderer.setPixelRatio(window.devicePixelRatio);
        container.appendChild(renderer.domElement);

        const ambientLight = new THREE.AmbientLight(0xffffff, 2.0);
        scene.add(ambientLight);
        
        const dirLight = new THREE.DirectionalLight(0xffffff, 1.5);
        dirLight.position.set(5, 10, 7);
        scene.add(dirLight);

        const loader = new GLTFLoader();
        const modelUrl = "' . $modelPath . '";

        loader.load(modelUrl, function(gltf) {
            const model = gltf.scene;
            const box = new THREE.Box3().setFromObject(model);
            const size = box.getSize(new THREE.Vector3());
            model.position.y = -(size.y / 2); 
            scene.add(model);

            const maxDim = Math.max(size.x, size.y, size.z);
            camera.position.z = maxDim * 2.2; 
            camera.position.y = 0;
            camera.lookAt(0, 0, 0);

            function animate() {
                requestAnimationFrame(animate);
                renderer.render(scene, camera);
            }
            animate();

        }, undefined, function(error) {
            console.error("Error cargando GLTF:", error);
            container.innerHTML = "<div style=\'display:flex;justify-content:center;align-items:center;height:100%;\'><img src=\'' . $avatarPath . '\' style=\'border-radius:50%; border:2px solid #00b894;\'></div>";
        });

        window.addEventListener("resize", () => {
            if(container && renderer) {
                camera.aspect = container.clientWidth / container.clientHeight;
                camera.updateProjectionMatrix();
                renderer.setSize(container.clientWidth, container.clientHeight);
            }
        });
    });
    </script>';

    echo '<script>
    function setMaxMoney() {
        document.getElementById("inputMoney").value = "2000000000";
    }
    </script>';

    echo '<link rel="stylesheet" href="' . __BASE_URL__ . 'templates/misc/css/inventory_viewer.css">';
    echo '<script src="' . __BASE_URL__ . 'templates/misc/js/inventory_tooltip.js"></script>';

} catch (Exception $e) {
    echo '<div class="alert alert-danger">Error: '.$e->getMessage().'</div>';
    return;
}
?>