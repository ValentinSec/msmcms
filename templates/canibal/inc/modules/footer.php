<style>
    .canibal-footer {
        background: transparent; /* Sin fondo específico, toma el de la web */
        border-top: 1px solid rgba(255, 255, 255, 0.05); /* Línea divisoria muy sutil arriba */
        padding: 60px 0 20px 0;
        margin-top: 40px;
        font-family: var(--body-font, 'Poppins', sans-serif);
        color: #888;
        position: relative;
        z-index: 10;
    }

    .canibal-footer .footer-heading {
        color: #c8a165; /* Dorado/Bronce del template */
        font-family: 'Cinzel', serif;
        font-size: 16px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 1px;
        margin-bottom: 20px;
        border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        padding-bottom: 10px;
        display: inline-block;
    }

    .canibal-footer .footer-text {
        font-size: 13px;
        line-height: 1.6;
        color: #6a7382;
    }

    .canibal-footer .footer-links {
        list-style: none;
        padding: 0;
        margin: 0;
    }

    .canibal-footer .footer-links li {
        margin-bottom: 12px;
    }

    .canibal-footer .footer-links a {
        color: #6a7382;
        text-decoration: none;
        font-size: 13px;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .canibal-footer .footer-links a i {
        font-size: 10px;
        color: #333;
        transition: color 0.3s ease;
    }

    /* Efecto al pasar el mouse (Se ilumina en verde/blanco) */
    .canibal-footer .footer-links a:hover {
        color: #ffffff;
        transform: translateX(5px); /* Pequeño deslizamiento a la derecha */
    }

    .canibal-footer .footer-links a:hover i {
        color: #00d084; /* El verde esmeralda del registro */
    }

    .canibal-footer .footer-bottom {
        border-top: 1px solid rgba(255, 255, 255, 0.05);
        margin-top: 40px;
        padding-top: 20px;
        text-align: center;
        font-size: 12px;
        color: #555;
    }

    .canibal-footer .footer-bottom span {
        color: #c8a165;
    }
</style>

<footer class="canibal-footer">
    <div class="container">
        <div class="row">
            
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="footer-heading"><?php echo custom_config('server_name'); ?></div>
                <p class="footer-text">
                    <?php echo nl2br(custom_config('server_desc')); ?>
                </p>
            </div>

            <div class="col-lg-4 col-md-6 mb-4">
                <div class="footer-heading">Navegación</div>
                <ul class="footer-links">
                    <li><a href="<?php echo __BASE_URL__; ?>"><i class="fa fa-chevron-right"></i> Inicio</a></li>
                    <li><a href="<?php echo __BASE_URL__; ?>register"><i class="fa fa-chevron-right"></i> Crear una Cuenta</a></li>
                    <li><a href="<?php echo __BASE_URL__; ?>downloads"><i class="fa fa-chevron-right"></i> Descargas</a></li>
                    <li><a href="<?php echo __BASE_URL__; ?>rankings"><i class="fa fa-chevron-right"></i> Top Jugadores</a></li>
                </ul>
            </div>

            <div class="col-lg-4 col-md-12 mb-4">
                <div class="footer-heading">Soporte y Reglas</div>
                <ul class="footer-links">
                    <li><a href="<?php echo __BASE_URL__; ?>tos"><i class="fa fa-chevron-right"></i> Términos y Condiciones</a></li>
                    <li><a href="<?php echo __BASE_URL__; ?>privacy"><i class="fa fa-chevron-right"></i> Políticas de privacidad</a></li>
                    <?php 
                    $dcLink = custom_config('social_discord');
                    if(!empty($dcLink)): 
                    ?>
                    <li><a href="<?php echo $dcLink; ?>" target="_blank"><i class="fab fa-discord" style="font-size: 12px;"></i> Soporte en Discord</a></li>
                    <?php endif; ?>
                </ul>
            </div>

        </div>

        <div class="footer-bottom">
            &copy; <?php echo date('Y'); ?> <span><?php echo custom_config('server_name'); ?></span>. Todos los derechos reservados.<br>
            Powered by MuServerMaker CMS
        </div>
    </div>
</footer>