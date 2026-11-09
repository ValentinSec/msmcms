<?php
/**
 * Register Configuration Module (Updated with Referral System & Multi-Account)
 * PREPARED FOR OBFUSCATION (Pure PHP Version)
 */

// 1. Rutas de configuración
$xmlPath = __PATH_CONFIGS__ . 'modules/register.xml';
// Ruta al JSON de tablas
$customConfigPath = __DIR__ . '/../../../includes/config/custom_settings.json';

// Cargar el JSON para usarlo como referencia
$customConfig = [];
if (file_exists($customConfigPath)) {
    $customConfig = json_decode(file_get_contents($customConfigPath), true);
}

// Helper para obtener valores del JSON o default
function getTableVal($key, $default, $config) {
    return isset($config[$key]) ? $config[$key] : $default;
}

// 2. Lógica de Guardado (Procesamiento del Formulario)
if(isset($_POST['submit_settings'])) {
    try {
        if(!file_exists($xmlPath)) throw new Exception("No existe el archivo XML en: " . $xmlPath);
        if(!is_writable($xmlPath)) throw new Exception("El archivo XML no tiene permisos de escritura (CHMOD 777).");

        $xml = simplexml_load_file($xmlPath);

        // Función auxiliar para actualizar o crear nodos
        if (!function_exists('update_xml_node')) {
            function update_xml_node($xml, $node, $value) {
                if (isset($xml->$node)) {
                    $xml->$node = $value;
                } else {
                    $xml->addChild($node, $value);
                }
            }
        }

        // --- ACTUALIZACIÓN DE VALORES ---
        
        // Sección Módulo Registro
        update_xml_node($xml, 'active', (isset($_POST['register_active']) ? 1 : 0));
        update_xml_node($xml, 'register_verify_email', (isset($_POST['register_verify_email']) ? 1 : 0));
        update_xml_node($xml, 'register_enable_idcode', (isset($_POST['register_id_code']) ? 1 : 0));
        
        // [FIX] Multi-Email
        update_xml_node($xml, 'register_same_email', (isset($_POST['register_same_email']) ? 1 : 0));
        
        update_xml_node($xml, 'register_multi_account', (isset($_POST['register_multi_account']) ? 1 : 0));
        update_xml_node($xml, 'send_welcome_email', (isset($_POST['send_welcome_email']) ? 1 : 0));
        
        // Sección Sistema de Referidos
        update_xml_node($xml, 'referral_active', (isset($_POST['referral_active']) ? 1 : 0));
        update_xml_node($xml, 'referral_reward_coin', $_POST['referral_reward_coin']);
        update_xml_node($xml, 'referral_reward_amount', $_POST['referral_reward_amount']);
        update_xml_node($xml, 'referral_condition', $_POST['referral_condition']);
        
        // Sección Opciones de Bienvenida Coins
        update_xml_node($xml, 'register_coins_amount', $_POST['register_coins_amount']);
        update_xml_node($xml, 'register_coins_column', (isset($customConfig['col_coin_1']) ? $customConfig['col_coin_1'] : 'WCoinC'));
        update_xml_node($xml, 'register_coins_table', (isset($customConfig['table_coins']) ? $customConfig['table_coins'] : 'CashShopData'));
        
        // Sección Opciones de Bienvenida VIP
        update_xml_node($xml, 'register_vip_days', $_POST['register_vip_days']);
        update_xml_node($xml, 'register_vip_type', $_POST['register_vip_type']);

        // --- PERSISTENCIA EN JSON ---
        $newJsonData = array_merge($customConfig, [
            'register_coins_amount' => $_POST['register_coins_amount'],
            'register_vip_days'     => $_POST['register_vip_days'],
            'register_vip_type'     => $_POST['register_vip_type'],
            'referral_active'       => (isset($_POST['referral_active']) ? 1 : 0),
            'referral_reward_coin'  => $_POST['referral_reward_coin'],
            'referral_reward_amount' => $_POST['referral_reward_amount'],
            'referral_condition'     => $_POST['referral_condition']
        ]);
        
        file_put_contents($customConfigPath, json_encode($newJsonData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        // Guardar físicamente el archivo XML
        $xml->asXML($xmlPath);

        // Limpiar caché
        $cacheFile = __PATH_CACHE__ . 'mconfig.cache';
        if(file_exists($cacheFile)) { @unlink($cacheFile); }

        header("Location: index.php?module=settings_register&success=1");
        exit;

    } catch (Exception $ex) {
        echo '<div class="alert alert-danger"><b>Error:</b> '.$ex->getMessage().'</div>';
    }
}

// 3. Carga directa del XML para visualización
$view = simplexml_load_file($xmlPath);

// Obtener nombres de columnas de monedas para el selector
$col1 = getTableVal('col_coin_1', 'WCoinC', $customConfig);
$col2 = getTableVal('col_coin_2', 'WCoinP', $customConfig);
$col3 = getTableVal('col_coin_3', 'GoblinPoint', $customConfig);

// --- RENDERIZADO HTML (PHP PURO) ---

echo '<style>
    .alert-success-custom { 
        background: #1b1b28; 
        border-left: 4px solid #00d084; 
        color: #fff; 
        padding: 15px; 
        margin-bottom: 20px; 
        border-radius: 4px; 
        box-shadow: 0 4px 10px rgba(0,0,0,0.2); 
    }
    input[type=number]::-webkit-inner-spin-button, 
    input[type=number]::-webkit-outer-spin-button { 
        -webkit-appearance: none; 
        margin: 0; 
    }
    input[type=number] { -moz-appearance: textfield; }
    
    #referralContainer { transition: all 0.3s ease-in-out; overflow: hidden; }
    .hidden-panel { display: none; opacity: 0; }
    .visible-panel { display: block; opacity: 1; animation: fadeIn 0.5s; }
    @keyframes fadeIn { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }
</style>';

echo '<div class="container-fluid">';
    
    if(isset($_GET['success'])) {
        echo '<div class="alert-success-custom">Cambios guardados correctamente.</div>';
    }

    echo '<form action="" method="post">';
        
        // Header
        echo '<div class="row">';
            echo '<div class="col-lg-12">';
                echo '<div class="row" style="margin-bottom: 20px; align-items: center; display: flex;">';
                    echo '<div class="col-xs-6">';
                        echo '<h3 style="margin:0; color:#fff; font-weight:600;">Configuración de Registro</h3>';
                    echo '</div>';
                    echo '<div class="col-xs-6 text-right">';
                        echo '<button type="submit" name="submit_settings" class="btn-save-green">Guardar Cambios</button>';
                    echo '</div>';
                echo '</div>';
            echo '</div>';
        echo '</div>';

        // Main Grid
        echo '<div class="row row-flex">';
            
            // Columna 1: Módulo Registro
            echo '<div class="col-md-4">';
                echo '<div class="settings-section">';
                    echo '<h3 class="settings-title">Módulo Registro</h3>';
                    echo '<div style="margin-top: 20px;">';
                        
                        echo '<div class="form-group switch-container">';
                            echo '<label class="switch">';
                                echo '<input type="checkbox" name="register_active" ' . (((string)$view->active == "1") ? 'checked' : '') . '>';
                                echo '<span class="slider"></span>';
                            echo '</label>';
                            echo '<span class="switch-label" style="color:#fff;">Módulo Registro Activo</span>';
                        echo '</div>';

                        echo '<div class="form-group switch-container">';
                            echo '<label class="switch">';
                                echo '<input type="checkbox" name="register_verify_email" ' . (((string)$view->register_verify_email == "1") ? 'checked' : '') . '>';
                                echo '<span class="slider"></span>';
                            echo '</label>';
                            echo '<span class="switch-label" style="color:#fff;">Activar Verificar Email</span>';
                        echo '</div>';

                        echo '<div class="form-group switch-container">';
                            echo '<label class="switch">';
                                echo '<input type="checkbox" name="register_id_code" ' . (((string)$view->register_enable_idcode == "1") ? 'checked' : '') . '>';
                                echo '<span class="slider"></span>';
                            echo '</label>';
                            echo '<span class="switch-label" style="color:#fff;">Activar ID Code</span>';
                        echo '</div>';

                        echo '<div class="form-group switch-container">';
                            echo '<label class="switch">';
                                echo '<input type="checkbox" name="register_same_email" ' . (((string)$view->register_same_email == "1") ? 'checked' : '') . '>';
                                echo '<span class="slider"></span>';
                            echo '</label>';
                            echo '<span class="switch-label" style="color:#fff;">Permitir Mismo Email (Multi-Cuentas)</span>';
                        echo '</div>';

                        echo '<div class="form-group switch-container">';
                            echo '<label class="switch">';
                                echo '<input type="checkbox" name="send_welcome_email" id="welcomeEmailSwitch" ' . ($view->send_welcome_email == 1 ? 'checked' : '') . '>';
                                echo '<span class="slider"></span>';
                            echo '</label>';
                            echo '<span class="switch-label" style="color:#fff;">Enviar correo de bienvenida</span>';
                        echo '</div>';

                        echo '<div class="form-group switch-container" style="border-top: 1px solid #2b2b36; padding-top: 15px; margin-top: 15px;">';
                            echo '<label class="switch">';
                                echo '<input type="checkbox" name="referral_active" id="referralSwitch" ' . (((string)$view->referral_active == "1") ? 'checked' : '') . '>';
                                echo '<span class="slider"></span>';
                            echo '</label>';
                            echo '<span class="switch-label" style="color:#ffae00; font-weight:bold;">Activar Sistema de Referidos</span>';
                        echo '</div>';

                    echo '</div>';
                echo '</div>';
            echo '</div>';

            // Columna 2: Bienvenida Coins
            echo '<div class="col-md-4">';
                echo '<div class="settings-section">';
                    echo '<h3 class="settings-title">Opciones de Bienvenida Coins</h3>';
                    echo '<div style="margin-top: 20px;">';
                        
                        echo '<div class="form-group">';
                            echo '<label class="settings-label">Dar Coins en Registro: Cantidad</label>';
                            echo '<input type="number" name="register_coins_amount" class="form-control-dark" value="' . $view->register_coins_amount . '">';
                        echo '</div>';
                
                        echo '<div class="form-group">';
                            echo '<label class="settings-label">Columna Destino (Configurada en Tablas)</label>';
                            echo '<input type="text" name="register_coins_column" class="form-control-dark" value="' . $col1 . '" readonly>';
                            echo '<small style="color: #64748b;">Se edita desde el módulo de Configuración de Tablas.</small>';
                        echo '</div>';
                
                        echo '<div class="form-group">';
                            echo '<label class="settings-label">Tabla Destino (Configurada en Tablas)</label>';
                            echo '<input type="text" name="register_coins_table" class="form-control-dark" value="' . getTableVal('table_coins', 'CashShopData', $customConfig) . '" readonly style="opacity: 0.6; cursor: not-allowed;">';
                        echo '</div>';
                
                    echo '</div>';
                echo '</div>';
            echo '</div>';

            // Columna 3: Bienvenida VIP
            echo '<div class="col-md-4">';
                echo '<div class="settings-section">';
                    echo '<h3 class="settings-title">Opciones de Bienvenida VIP</h3>';
                    echo '<div style="margin-top: 20px;">';
                        
                        echo '<div class="form-group">';
                            echo '<label class="settings-label">Dar VIP en Registro: Días</label>';
                            echo '<input type="number" name="register_vip_days" class="form-control-dark" value="' . $view->register_vip_days . '">';
                        echo '</div>';
                
                        echo '<div class="form-group">';
                            echo '<label class="settings-label">Tipo de VIP</label>';
                            echo '<select name="register_vip_type" class="form-control-dark">';
                                $currentVip = (string)$view->register_vip_type;
                                echo '<option value="0" ' . ($currentVip == "0" ? 'selected' : '') . '>Ninguno</option>';
                                echo '<option value="1" ' . ($currentVip == "1" ? 'selected' : '') . '>VIP Bronze</option>';
                                echo '<option value="2" ' . ($currentVip == "2" ? 'selected' : '') . '>VIP Silver</option>';
                                echo '<option value="3" ' . ($currentVip == "3" ? 'selected' : '') . '>VIP Gold</option>';
                            echo '</select>';
                        echo '</div>';
                
                        echo '<div class="form-group">';
                            echo '<label class="settings-label">Tabla VIP Destino</label>';
                            echo '<input type="text" class="form-control-dark" value="' . getTableVal('table_vip', 'MEMB_INFO', $customConfig) . '" readonly style="opacity: 0.6;">';
                        echo '</div>';
                
                    echo '</div>';
                echo '</div>';
            echo '</div>';

        echo '</div>'; // End Row Flex

        // REFERRAL CONTAINER
        echo '<div class="row" id="referralContainer" style="display:none;">';
            echo '<div class="col-md-12">';
                echo '<div class="settings-section" style="border: 1px solid #ffae00;">';
                    echo '<h3 class="settings-title" style="color: #ffae00;">Configuración Sistema de Referidos</h3>';
                    echo '<p>A través de este módulo, cada persona que refiera a otro recibirá las mismas cantidades de coins que el referido, cuando se cumpla la condición establecida.</p>';
                    echo '<div class="row" style="margin-top: 20px;">';
                        
                        echo '<div class="col-md-3">';
                            echo '<div class="form-group">';
                                echo '<label class="settings-label">Tabla de Coins (Origen)</label>';
                                echo '<input type="text" class="form-control-dark" value="' . getTableVal('table_coins', 'CashShopData', $customConfig) . '" readonly style="opacity:0.7;">';
                                echo '<small style="color:#666;">Configurado en settings_tables</small>';
                            echo '</div>';
                        echo '</div>';

                        echo '<div class="col-md-3">';
                            echo '<div class="form-group">';
                                echo '<label class="settings-label">Moneda de Recompensa</label>';
                                echo '<select name="referral_reward_coin" class="form-control-dark">';
                                    $refCoin = (string)$view->referral_reward_coin;
                                    echo '<option value="' . $col1 . '" ' . ($refCoin == $col1 ? 'selected' : '') . '>' . $col1 . '</option>';
                                    echo '<option value="' . $col2 . '" ' . ($refCoin == $col2 ? 'selected' : '') . '>' . $col2 . '</option>';
                                    echo '<option value="' . $col3 . '" ' . ($refCoin == $col3 ? 'selected' : '') . '>' . $col3 . '</option>';
                                echo '</select>';
                            echo '</div>';
                        echo '</div>';

                        echo '<div class="col-md-3">';
                            echo '<div class="form-group">';
                                echo '<label class="settings-label">Cantidad por Referido</label>';
                                echo '<input type="number" name="referral_reward_amount" class="form-control-dark" value="' . $view->referral_reward_amount . '" placeholder="Ej: 500">';
                            echo '</div>';
                        echo '</div>';

                        echo '<div class="col-md-3">';
                            echo '<div class="form-group">';
                                echo '<label class="settings-label">Condición para Otorgar</label>';
                                echo '<select name="referral_condition" class="form-control-dark">';
                                    $refCond = (string)$view->referral_condition;
                                    echo '<option value="5" ' . ($refCond == "5" ? 'selected' : '') . '>Al llegar a 5 Resets</option>';
                                    echo '<option value="10" ' . ($refCond == "10" ? 'selected' : '') . '>Al llegar a 10 Resets</option>';
                                    echo '<option value="20" ' . ($refCond == "20" ? 'selected' : '') . '>Al llegar a 20 Resets</option>';
                                    echo '<option value="30" ' . ($refCond == "30" ? 'selected' : '') . '>Al llegar a 30 Resets</option>';
                                echo '</select>';
                            echo '</div>';
                        echo '</div>';

                    echo '</div>';
                echo '</div>';
            echo '</div>';
        echo '</div>';

    echo '</form>';
echo '</div>';

echo '<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>';
echo '<script>
document.addEventListener("DOMContentLoaded", function() {
    
    // Lógica Switch Referidos
    const refSwitch = document.getElementById("referralSwitch");
    const refContainer = document.getElementById("referralContainer");

    function toggleReferralPanel() {
        if(refSwitch.checked) {
            refContainer.style.display = "block";
            refContainer.classList.remove("hidden-panel");
            refContainer.classList.add("visible-panel");
        } else {
            refContainer.classList.remove("visible-panel");
            refContainer.classList.add("hidden-panel");
            setTimeout(() => {
                if(!refSwitch.checked) refContainer.style.display = "none";
            }, 300);
        }
    }

    // Inicializar estado
    toggleReferralPanel();

    // Event Listener
    refSwitch.addEventListener("change", toggleReferralPanel);

    // Lógica Welcome Email
    document.getElementById("welcomeEmailSwitch").addEventListener("change", function() {
        if(this.checked) {
            Swal.fire({
                title: "¡Excelente elección!",
                html: `
                    <div style="text-align: left; font-size: 14px; line-height: 1.5;">
                        <p>Para que esto funcione correctamente, sigue estos 2 pasos:</p>
                        <ol>
                            <li><b>Configura el SMTP:</b> Ingresa <a href="' . admincp_base('settings_smtp') . '" style="color: #FF8A3B;">AQUÍ</a>...</li>
                            <li><b>Personaliza el diseño:</b> Ve a <code>/includes/emails/WELCOME_EMAIL.txt</code>...</li>
                        </ol>
                    </div>
                `,
                icon: "info",
                confirmButtonText: "Entendido",
                confirmButtonColor: "#FF8A3B",
                background: "#1a1a1c",
                color: "#fff"
            });
        }
    });
});
</script>';

echo '<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">';
?>