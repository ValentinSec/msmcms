<?php
/**
 * Forgot Password Module
 * El enlace del correo abre un formulario: el usuario elige su contrasena nueva.
 */

if(isLoggedIn()) redirect();

// Titulo de la pagina
echo '<div class="page-title"><span>' . lang('module_titles_txt_15',true) . '</span></div>';

try {

    if(!mconfig('active')) throw new Exception(lang('error_47',true));

    // CASO 1: ENLACE DEL CORREO -> formulario de nueva contrasena
    if(isset($_GET['ui']) && isset($_GET['ue']) && isset($_GET['key'])) {

        $Account = new Account();
        $fpDone = false;

        // Guardado: solo ocurre cuando el usuario envia el formulario
        if(isset($_POST['msmPass_submit'])) {
            try {
                $Account->passwordRecoveryComplete(
                    $_GET['ui'], $_GET['ue'], $_GET['key'],
                    isset($_POST['msmPass_new']) ? $_POST['msmPass_new'] : null,
                    isset($_POST['msmPass_confirm']) ? $_POST['msmPass_confirm'] : null
                );
                $fpDone = true;
                message('success', 'Tu contrase&ntilde;a fue cambiada. Ya pod&eacute;s <a href="' . __BASE_URL__ . 'login">iniciar sesi&oacute;n</a>.');
            } catch (Exception $ex) {
                message('error', $ex->getMessage());
            }
        }

        if(!$fpDone) {
            try {
                // Abrir el enlace NO cambia nada: solo se valida y se muestra el formulario
                $Account->passwordRecoveryCheck($_GET['ui'], $_GET['ue'], $_GET['key']);

                $fpCfg = muservermakerConfigs();
                $fpMin = (isset($fpCfg['password_min_len']) && (int)$fpCfg['password_min_len'] > 0) ? (int)$fpCfg['password_min_len'] : 4;
                $fpMax = (isset($fpCfg['password_max_len']) && (int)$fpCfg['password_max_len'] > 0) ? (int)$fpCfg['password_max_len'] : 20;

                echo '<div class="col-xs-8 col-xs-offset-2" style="margin-top:30px;">';
                    echo '<form class="form-horizontal" action="" method="post" autocomplete="off">';
                        // Marca para que los estilos de cada template reconozcan esta pagina (no se usa)
                        echo '<input type="hidden" name="msmEmail_current" value="">';

                        echo '<div class="form-group">';
                            echo '<label for="msmPassNew" class="col-sm-4 control-label">Nueva contrase&ntilde;a</label>';
                            echo '<div class="col-sm-8">';
                                echo '<input type="password" class="form-control" id="msmPassNew" name="msmPass_new" minlength="' . $fpMin . '" maxlength="' . $fpMax . '" autocomplete="new-password" required>';
                            echo '</div>';
                        echo '</div>';

                        echo '<div class="form-group">';
                            echo '<label for="msmPassConfirm" class="col-sm-4 control-label">Repetir contrase&ntilde;a</label>';
                            echo '<div class="col-sm-8">';
                                echo '<input type="password" class="form-control" id="msmPassConfirm" name="msmPass_confirm" minlength="' . $fpMin . '" maxlength="' . $fpMax . '" autocomplete="new-password" required>';
                                echo '<small style="display:block; margin-top:6px; opacity:0.7;">' . sprintf(lang('forgotpass_new_3', true), $fpMin, $fpMax) . '</small>';
                            echo '</div>';
                        echo '</div>';

                        echo '<div class="form-group">';
                            echo '<div class="col-sm-offset-4 col-sm-8">';
                                echo '<button type="submit" name="msmPass_submit" value="submit" class="btn btn-primary">Guardar contrase&ntilde;a</button>';
                            echo '</div>';
                        echo '</div>';

                    echo '</form>';
                echo '</div>';

            } catch (Exception $ex) {
                message('error', 'El enlace no es v&aacute;lido o ya venci&oacute;. <a href="' . __BASE_URL__ . 'forgotpassword">Ped&iacute; uno nuevo</a>.');
            }
        }

    } else {

        // CASO 2: SOLICITUD DE RECUPERACION (Formulario)
        if(isset($_POST['msmEmail_submit'])) {
            try {
                $Account = new Account();
                $Account->passwordRecoveryProcess($_POST['msmEmail_current'], $_SERVER['REMOTE_ADDR']);
            } catch (Exception $ex) {
                message('error', $ex->getMessage());
            }
        }

        // Renderizado del Formulario
        echo '<div class="col-xs-8 col-xs-offset-2" style="margin-top:30px;">';
            echo '<form class="form-horizontal" action="" method="post">';

                // Input Email
                echo '<div class="form-group">';
                    echo '<label for="msmEmail" class="col-sm-4 control-label">' . lang('forgotpass_txt_1',true) . '</label>';
                    echo '<div class="col-sm-8">';
                        echo '<input type="text" class="form-control" id="msmEmail" name="msmEmail_current" required>';
                    echo '</div>';
                echo '</div>';

                // Boton Submit
                echo '<div class="form-group">';
                    echo '<div class="col-sm-offset-4 col-sm-8">';
                        echo '<button type="submit" name="msmEmail_submit" value="submit" class="btn btn-primary">' . lang('forgotpass_txt_2',true) . '</button>';
                    echo '</div>';
                echo '</div>';

            echo '</form>';
        echo '</div>';
    }

} catch(Exception $ex) {
    message('error', $ex->getMessage());
}