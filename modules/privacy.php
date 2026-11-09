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
            <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-shield" style="color: rgb(255, 138, 59);">
                <path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z"></path>
            </svg>
        </div>
    </div>

    <div class="text-center mb-5">
        <h1 class="privacy-title" style="font-family: 'Cinzel', serif; color: #ea6a32; font-size: 2.5rem; font-weight: 700;">
            Política de Privacidad
        </h1>
        <p style="color: #B1957B; font-family: 'Inter', sans-serif;">
            Última actualización: <?php echo date('d/m/Y'); ?>
        </p>
    </div>
    <div class="privacy-card">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><ellipse cx="12" cy="5" rx="9" ry="3"></ellipse><path d="M3 5V19A9 3 0 0 0 21 19V5"></path><path d="M3 12A9 3 0 0 0 21 12"></path></svg>
        <div class="privacy-card-content">
            <h2>1. Información que Recopilamos</h2>
            <p>En <?php echo $server_name; ?>, recopilamos únicamente la información necesaria para proporcionar nuestros servicios de juego:</p>
            <ul>
                <li><strong>Información de cuenta:</strong> Nombre de usuario, correo electrónico y contraseña encriptada.</li>
                <li><strong>Datos de juego:</strong> Estadísticas de personaje, progreso, inventario y logros.</li>
                <li><strong>Información de pago:</strong> Procesada de forma segura. No almacenamos datos de tarjetas de crédito.</li>
                <li><strong>Datos técnicos:</strong> Dirección IP, tipo de navegador y sistema operativo para seguridad.</li>
            </ul>
        </div>
    </div>

    <div class="privacy-card">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2.062 12.348a1 1 0 0 1 0-.696 10.75 10.75 0 0 1 19.876 0 1 1 0 0 1 0 .696 10.75 10.75 0 0 1-19.876 0"></path><circle cx="12" cy="12" r="3"></circle></svg>
        <div class="privacy-card-content">
            <h2>2. Uso de la Información</h2>
            <p>Utilizamos tu información para:</p>
            <ul>
                <li>Proporcionar y mejorar nuestros servicios de juego</li>
                <li>Procesar donaciones y transacciones</li>
                <li>Comunicar actualizaciones, eventos y noticias importantes</li>
                <li>Prevenir fraudes y garantizar la seguridad del servidor</li>
                <li>Cumplir con obligaciones legales</li>
            </ul>
        </div>
    </div>

    <div class="privacy-card">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="11" x="3" y="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
        <div class="privacy-card-content">
            <h2>3. Protección de Datos</h2>
            <p>La seguridad de tus datos es nuestra prioridad:</p>
            <ul>
                <li>Todas las contraseñas están encriptadas usando algoritmos de seguridad modernos</li>
                <li>Conexiones HTTPS para proteger la transmisión de datos</li>
                <li>Servidores seguros con medidas de protección contra ataques</li>
                <li>Acceso limitado a datos personales solo para personal autorizado</li>
                <li>Backups regulares de información</li>
            </ul>
        </div>
    </div>

    <div class="privacy-card">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" x2="12" y1="8" y2="12"></line><line x1="12" x2="12.01" y1="16" y2="16"></line></svg>
        <div class="privacy-card-content">
            <h2>4. Tus Derechos</h2>
            <p>Como usuario, tienes derecho a:</p>
            <ul>
                <li>Acceder a tu información personal</li>
                <li>Corregir datos inexactos</li>
                <li>Solicitar la eliminación de tu cuenta</li>
                <li>Oponerte al procesamiento de tus datos</li>
                <li>Recibir una copia de tus datos en formato portable</li>
            </ul>
            <p style="margin-top: 1rem;">Para ejercer estos derechos, contáctanos en <a href="mailto:<?php echo $contact_email; ?>" class="link-highlight"><?php echo $contact_email; ?></a></p>
        </div>
    </div>

    <div class="privacy-card">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="16" x="2" y="4" rx="2"></rect><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"></path></svg>
        <div class="privacy-card-content">
            <h2>5. Contacto</h2>
            <p>Si tienes preguntas sobre esta política de privacidad o sobre cómo manejamos tus datos, no dudes en contactarnos:</p>
            <div style="margin-top: 1rem;">
                <p><strong>Email:</strong> <?php echo $contact_email; ?></p>
                <p><strong>Discord:</strong> <a href="<?php echo $discord_link; ?>" target="_blank" class="link-highlight">Comunidad Oficial</a></p>
            </div>
        </div>
    </div>

    <div class="privacy-footer-note">
        <p>Esta política puede ser actualizada periódicamente. Te notificaremos sobre cambios significativos a través de nuestra <a href="<?php echo $discord_link; ?>" target="_blank" class="link-highlight">Comunidad Oficial</a>.</p>
    </div>
</div>