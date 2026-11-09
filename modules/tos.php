<?php
/**
 * privacy.php - Estética Unificada Balduneta MU
 */

// 1. CARGA DE CONFIGURACIÓN (Asegura que $conf esté disponible)
$myJsonFile = __PATH_INCLUDES__ . 'config/custom_settings.json';
$conf = [];
if(file_exists($myJsonFile)) {
    $conf = json_decode(file_get_contents($myJsonFile), true);
}
if(!is_array($conf)) $conf = [];

// Helper para obtener valores de configuración
$server_name = isset($conf['server_name']) ? htmlspecialchars($conf['server_name']) : "Balduneta MU";
$contact_email = isset($conf['social_email']) ? htmlspecialchars($conf['social_email']) : "contact@baldunetamu.com";
$discord_link = isset($conf['social_discord']) ? htmlspecialchars($conf['social_discord']) : "#";
?>

<style>
    /* Tipografía y Base */
    @import url('https://fonts.googleapis.com/css2?family=Cinzel:wght@400;700&family=Inter:wght@300;400;600&display=swap');

    .privacy-wrapper {
        padding: 20px;
        display: flex;
        flex-direction: column;
        gap: 20px;
        font-family: 'Inter', sans-serif;
    }

    /* Título Principal */
    .privacy-main-title {
        color: #FF8A3B;
        text-transform: uppercase;
        font-weight: 900;
        margin-bottom: 10px;
        text-align: center;
        font-family: 'Inter', sans-serif;
    }

    /* Contenedores de Puntos (Cards) */
    .privacy-card {
        padding: 2rem;
        border-radius: 0.75rem; /* rounded-xl */
        background: rgba(26, 15, 16, 0.6);
        border: 1px solid rgba(215, 73, 33, 0.2);
        display: flex;
        align-items: flex-start;
        gap: 1rem;
    }

    .privacy-card svg {
        color: rgb(255, 138, 59);
        width: 1.5rem;
        height: 1.5rem;
        margin-top: 0.25rem;
        flex-shrink: 0;
    }

    .privacy-card h2 {
        font-family: 'Cinzel', serif;
        color: rgb(230, 214, 198);
        font-size: 1.5rem; /* text-2xl */
        font-weight: 700;
        margin-bottom: 1rem;
    }

    .privacy-card-content {
        color: rgb(177, 149, 123);
    }

    .privacy-card-content ul {
        margin-top: 0.75rem;
        padding-left: 1rem;
    }

    .privacy-card-content li {
        margin-bottom: 0.5rem;
    }

    .privacy-card-content strong {
        color: rgb(230, 214, 198);
    }

    /* Alerta de Footer */
    .privacy-footer-note {
        padding: 1.5rem;
        border-radius: 0.75rem;
        text-align: center;
        background: rgba(215, 73, 33, 0.1);
        border: 1px solid rgba(215, 73, 33, 0.3);
        color: rgb(177, 149, 123);
        font-size: 0.875rem; /* text-sm */
    }

    .link-highlight {
        color: rgb(255, 138, 59);
        text-decoration: underline;
    }
</style>

<div class="privacy-wrapper">
    
    <div style="display: flex; justify-content: center; margin-bottom: 2rem;">
    <div style="
        box-sizing: border-box;
        border-radius: 1rem;
        padding: 1.5rem;
        background: linear-gradient(135deg, rgba(215, 73, 33, 0.1) 0%, rgba(26, 15, 16, 0.3) 100%);
        border: 2px solid rgba(215, 73, 33, 0.3);
        display: inline-flex;
        justify-content: center;
        align-items: center;
    ">
        <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-file-text" style="color: rgb(255, 138, 59);">
            <path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"></path>
            <path d="M14 2v4a2 2 0 0 0 2 2h4"></path>
            <path d="M10 9H8"></path>
            <path d="M16 13H8"></path>
            <path d="M16 17H8"></path>
        </svg>
    </div>
</div>

    <div class="text-center mb-5">
        <h1 class="privacy-title" style="font-family: 'Cinzel', serif; color: #ea6a32; font-size: 2.5rem; font-weight: 700;">
            Términos de Servicio
        </h1>
        <p style="color: #B1957B; font-family: 'Inter', sans-serif;">
            Última actualización: <?php echo date('d/m/Y'); ?>
        </p>
    </div>
    <div class="privacy-card">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><ellipse cx="12" cy="5" rx="9" ry="3"></ellipse><path d="M3 5V19A9 3 0 0 0 21 19V5"></path><path d="M3 12A9 3 0 0 0 21 12"></path></svg>
        <div class="privacy-card-content">
            <h2>1. Aceptación de Términos</h2>
            <p>Al acceder y utilizar los servicios de <?php echo $server_name; ?>, aceptas estar sujeto a estos términos y condiciones. Si no estás de acuerdo con alguno de estos términos, por favor no uses nuestros servicios.</p>
            <p>Estos términos aplican a todos los usuarios, visitantes y otras personas que accedan o usen el servicio.</p>
        </div>
    </div>

    <div class="privacy-card">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2.062 12.348a1 1 0 0 1 0-.696 10.75 10.75 0 0 1 19.876 0 1 1 0 0 1 0 .696 10.75 10.75 0 0 1-19.876 0"></path><circle cx="12" cy="12" r="3"></circle></svg>
        <div class="privacy-card-content">
            <h2>2. Cuentas de Usuario</h2>
            <p><b>Responsabilidad de la cuenta:</b></p>
            <ul>
                <li>Eres responsable de mantener la confidencialidad de tu cuenta y contraseña</li>
                <li>Debes notificarnos inmediatamente sobre cualquier uso no autorizado de tu cuenta</li>
                <li>Solo se permite una cuenta por persona</li>
                <li>No puedes vender, transferir o compartir tu cuenta</li>
                <li>Debes proporcionar información veraz y actualizada al registrarte</li>
            </ul>
        </div>
    </div>

    <div class="privacy-card">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="11" x="3" y="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
        <div class="privacy-card-content">
            <h2>3. Conducta Prohibida</h2>
            <p>Está estrictamente prohibido:</p>
            <ul>
                <li>Usar hacks, bots, o cualquier software de terceros no autorizado</li>
                <li>Explotar bugs o errores del juego para obtener ventajas</li>
                <li>Comprar, vender o intercambiar cuentas o ítems por dinero real fuera de los canales oficiales</li>
                <li>Acosar, amenazar o insultar a otros jugadores</li>
                <li>Hacer spam o publicidad no autorizada</li>
                <li>Intentar acceder a áreas restringidas del servidor</li>
                <li>Compartir o distribuir malware o contenido malicioso</li>
            </ul>
        </div>
    </div>

    <div class="privacy-card">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" x2="12" y1="8" y2="12"></line><line x1="12" x2="12.01" y1="16" y2="16"></line></svg>
        <div class="privacy-card-content">
            <h2>4. Donaciones y Pagos</h2>
            <p><b>Como usuario, tienes derecho a:</b></p>
            <ul>
                <li>Todas las donaciones son voluntarias y no reembolsables</li>
                <li>Las donaciones otorgan WCoins (moneda virtual) que pueden usarse para comprar ítems premium</li>
                <li>Los WCoins no tienen valor monetario real y no pueden convertirse en dinero</li>
                <li>Nos reservamos el derecho de modificar los precios y contenido premium en cualquier momento</li>
                <li>Las transacciones se procesan a través de PayPal, MercadoPago y cualquier otro medio de pago ofrecido en el panel de usuario.</li>
                <li>Debes tener 18 años o el permiso de un tutor para realizar donaciones</li>
            </ul>
            <p>Importante: Al realizar una donación, confirmas que entiendes que los WCoins son virtuales y no reembolsables, y que cualquier cierre del servidor resultará en la pérdida de estos ítems sin compensación.</p>
            <p style="margin-top: 1rem;">Para ejercer estos derechos, contáctanos en <a href="mailto:<?php echo $contact_email; ?>" class="link-highlight"><?php echo $contact_email; ?></a></p>
        </div>
    </div>

    <div class="privacy-card">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="16" x="2" y="4" rx="2"></rect><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"></path></svg>
        <div class="privacy-card-content">
            <h2>5. Limitación de Responsabilidad</h2>
            <p><?php echo $server_name; ?> se proporciona "tal cual" sin garantías de ningún tipo. No nos hacemos responsables de:</p>
            <ul>
                <li>Pérdida de datos, personajes o ítems debido a errores técnicos</li>
                <li>Interrupciones del servicio, mantenimientos o caídas del servidor.</li>
                <li>Daños derivados del uso o imposibilidad de uso del servicio</li>
                <li>Pérdidas económicas relacionadas con donaciones o compras in-game</li>
            </ul>
            <p>Nos esforzamos por mantener un servicio estable, pero no garantizamos disponibilidad 24/7 ni la conservación permanente de datos.</p>
            
        </div>
    </div>

    <div class="privacy-card">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="16" x="2" y="4" rx="2"></rect><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"></path></svg>
        <div class="privacy-card-content">
            <h2>6. Modificaciones</h2>
            <p>Nos reservamos el derecho de modificar o reemplazar estos términos en cualquier momento. Los cambios significativos serán notificados a través de:</p>
            <ul>
                <li>Anuncios en el sitio web</li>
                <li>Mensajes in-game.</li>
                <li>Correo electrónico a usuarios registrados</li>
            </ul>
            <p>El uso continuado del servicio después de cualquier cambio constituye tu aceptación de los nuevos términos.</p>
        </div>
    </div>
    
    
    <div class="privacy-footer-note">
        <p>Esta política puede ser actualizada periódicamente. Te notificaremos sobre cambios significativos a través de nuestra <a href="<?php echo $discord_link; ?>" target="_blank" class="link-highlight">Comunidad Oficial</a>.</p>
    </div>
</div>