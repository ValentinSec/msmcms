/**
 * MAIN.JS - BALDUNETA MU (Lógica: Selección -> Confirmación)
 */

(function(window) {
    console.log("%c[DEBUG] Sistema de Donaciones Inicializado", "color: #ff9800; font-weight: bold;");

    // 1. Filtrar por moneda
    window.filterPacks = function(btn, methodId, coinName) {
        console.log("Filtrando:", methodId, coinName);
        const parent = btn.parentElement;
        if (parent) {
            parent.querySelectorAll('.btn-filter-coin').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
        }

        const items = document.querySelectorAll('.pack-item-' + methodId);
        items.forEach(item => {
            const itemCoin = item.getAttribute('data-coin');
            if (coinName === 'all' || (itemCoin && itemCoin.toLowerCase() === coinName.toLowerCase())) {
                item.style.setProperty('display', 'flex', 'important');
            } else {
                item.style.setProperty('display', 'none', 'important');
            }
        });
    };

    // 2. SELECCIONAR PAQUETE (Solo visual + Habilitar botón)
    window.selectDonationPack = function(btn, methodId, packId) {
        console.log("Pack Seleccionado (Esperando confirmación):", packId);
        
        // A. Efecto visual de selección en la carta
        const grid = document.getElementById('grid-' + methodId);
        if (grid) {
            grid.querySelectorAll('.donation-pack-btn').forEach(p => p.classList.remove('selected-pack'));
        }
        btn.classList.add('selected-pack');

        // B. Habilitar el Botón de Pago Inferior
        const payBtn = document.getElementById('btn-pay-' + methodId);
        if (payBtn) {
            payBtn.disabled = false;
            // Actualizamos el texto para dar feedback
            payBtn.innerHTML = '<i class="fa fa-check-circle me-2"></i> CONTINUAR CON EL PAGO';
            
            // C. Asignamos el evento de redirección AL BOTÓN (no a la carta)
            payBtn.onclick = function() {
                if(typeof baseUrl !== 'undefined') {
                    window.location.href = baseUrl + 'donate/process/' + methodId + '/' + packId;
                }
            };
        }
    };
})(window);

document.addEventListener("DOMContentLoaded", function() {
    
    // Escuchador Global
    document.body.addEventListener("click", function(e) {
        
        // Click en Pestañas de Pago
        let methodBtn = e.target.closest(".payment-card-premium");
        if (methodBtn) {
            document.querySelectorAll(".payment-card-premium").forEach(b => b.classList.remove("active"));
            methodBtn.classList.add("active");

            const emptyState = document.getElementById('pack-empty');
            if (emptyState) emptyState.classList.remove('show', 'active');

            // Auto-select moneda
            const targetId = methodBtn.getAttribute('data-bs-target');
            if(targetId) {
                setTimeout(() => {
                    const targetPane = document.querySelector(targetId);
                    if(targetPane) {
                        const firstCoinFilter = targetPane.querySelector('.btn-filter-coin');
                        if(firstCoinFilter) firstCoinFilter.click();
                    }
                }, 50);
            }
        }

        // Navegación AJAX
        let link = e.target.closest("a");
        if (link && link.href && link.href.includes(baseUrl) && !link.href.includes("#")) {
            if (link.href.includes("logout")) return;
            e.preventDefault();
            loadPage(link.href);
        }
    });

    window.onpopstate = function(e) {
        if(e.state) loadPage(document.location.href, false);
    };
});

function loadPage(url, pushState = true) {
    const container = document.getElementById("ajax-container");
    if (!container) return;
    container.style.opacity = "0.5";

    let fetchUrl = new URL(url);
    fetchUrl.searchParams.append("ajax_content", "1");

    fetch(fetchUrl)
        .then(response => response.text())
        .then(html => {
            if (html.includes("<!DOCTYPE html>")) {
                window.location.href = url;
                return;
            }
            container.innerHTML = html;
            container.style.opacity = "1";
            window.scrollTo({ top: 0, behavior: 'smooth' });
            if(pushState) window.history.pushState({path: url}, "", url);
        })
        .catch(err => console.error(err));
}

// Countdowns
function updateEventCountdowns() {
    const timers = document.querySelectorAll('.js-countdown');
    timers.forEach(timer => {
        let seconds = parseInt(timer.getAttribute('data-seconds')) || 0;
        const isActive = timer.getAttribute('data-active') === '1';
        if (seconds > 0) {
            seconds--;
            timer.setAttribute('data-seconds', seconds);
            const h = Math.floor(seconds / 3600).toString().padStart(2, '0');
            const m = Math.floor((seconds % 3600) / 60).toString().padStart(2, '0');
            const s = (seconds % 60).toString().padStart(2, '0');
            timer.innerHTML = isActive ? "ACTIVO" : h + ":" + m + ":" + s;
            timer.style.color = isActive ? "#2bdf49" : "#c8a165";
        }
    });
}
setInterval(updateEventCountdowns, 1000);

/* ==========================================================================
   SISTEMA VIP (TABS & MODAL)
   ========================================================================== */

// Función para cambiar pestañas en el Modal VIP
// Esta función es llamada por el onclick del PHP: openVipTab(event, 'tab_ID')
window.openVipTab = function(evt, tabName) {
    var i, tabcontent, tablinks;

    // 1. Ocultar todos los contenidos de las pestañas
    tabcontent = document.getElementsByClassName("vip-tab-pane");
    for (i = 0; i < tabcontent.length; i++) {
        tabcontent[i].style.display = "none";
    }

    // 2. Quitar la clase 'active' de todos los botones
    tablinks = document.getElementsByClassName("vip-tab-link");
    for (i = 0; i < tablinks.length; i++) {
        tablinks[i].className = tablinks[i].className.replace(" active", "");
    }

    // 3. Mostrar la pestaña actual y activar el botón
    document.getElementById(tabName).style.display = "block";
    if(evt) {
        evt.currentTarget.className += " active";
    }
};

// Función para abrir el Modal VIP (puedes vincular esto a un botón en tu web)
window.openVipModal = function() {
    var myModal = new bootstrap.Modal(document.getElementById('modalBuyVip'));
    myModal.show();
};

/* ==========================================================================
   CASINO GOBLIN - FINAL LAYOUT (Transparent Container + Reorder)
   ========================================================================== */

(function() {
    // 1. DEFINIR ESTILOS (Inyectados con Fuerza Bruta)
    var css = `
        /* --- ELIMINAR VISUALMENTE EL CONTENEDOR 'FEO' --- */
        .casino-container {
            display: flex !important;
            flex-direction: column !important;
            align-items: center !important;
            
            /* Hacemos el contenedor invisible */
            background: transparent !important; 
            border: none !important;
            box-shadow: none !important;
            padding: 0 !important;
            margin: 0 !important;
            min-height: 0 !important;
        }

        /* 'contents' hace que el div intermedio desaparezca para el layout */
        .casino-content, #selection-screen {
            display: contents !important;
        }

        .casino-intro {
            order: 1 !important;
            text-align: center !important;
            width: 100% !important;
            margin-bottom: 40px !important;
            border-bottom: 1px solid #2a2e35 !important; /* var(--cfg-border) */
            padding-bottom: 20px !important;
        }
        .casino-intro h2 {
            font-family: 'Cinzel', serif !important;
            color: #ffffff !important; /* var(--cfg-title) */
            font-size: 28px !important; /* Mismo tamaño que fw-bold */
            font-weight: bold !important;
            text-transform: uppercase !important;
            letter-spacing: 1px !important;
            margin-bottom: 5px !important;
            text-shadow: none !important; /* Quitamos el brillo dorado viejo */
        }
        .casino-intro p {
            color: #a0a0a0 !important; /* var(--cfg-text) */
            font-size: 12px !important; /* Mismo tamaño que .small */
            text-transform: uppercase !important;
            letter-spacing: 1px !important;
            max-width: 800px !important;
            margin: 0 auto !important;
        }

        /* --- 2. IMAGEN BANNER (Orden 2) --- */
        .casino-cover {
            order: 2 !important;
            flex: none !important;
            width: 100% !important;
            max-width: 850px !important;
            height: 180px !important;
            margin-bottom: 50px !important; /* Espacio antes de las cajas */
        }
        .ornamental-frame {
            min-height: 0 !important;
            height: 100% !important;
            border: 1px solid #c8a165 !important;
            box-shadow: 0 0 40px rgba(0,0,0,0.8) inset, 0 0 20px rgba(200, 161, 101, 0.2) !important;
            background-size: cover !important;
            background-position: center 35% !important;
            border-radius: 4px !important;
            background-image: url('/img/boxes/casibal.png') !important; /* <--- LÍNEA AGREGADA */
        }
        /* Ocultar adornos viejos */
        .ornament-corner { display: none !important; }

        /* --- 3. GRILLA DE CAJAS (Orden 3) --- */
        .box-selector-grid {
            order: 3 !important;
            display: grid !important;
            grid-template-columns: repeat(3, 1fr) !important; /* 3 Columnas Fijas */
            gap: 30px !important;
            width: 100% !important;
            max-width: 1100px !important;
        }
        
        /* Ocultar título redundante */
        #selection-screen > h4 { display: none !important; }

        /* --- ESTILO DE LAS CARTAS (Sobrio y Limpio) --- */
        .loot-box-card {
            background: #111418 !important; /* Fondo carta */
            border: 1px solid #2d323e !important;
            border-radius: 8px !important;
            padding: 30px 20px !important;
            display: flex !important;
            flex-direction: column !important;
            align-items: center !important;
            transition: all 0.3s ease !important;
            position: relative !important;
        }
        .loot-box-card:hover {
            transform: translateY(-8px) !important;
            border-color: #c8a165 !important;
            box-shadow: 0 10px 40px rgba(0,0,0,0.6) !important;
            z-index: 10 !important;
        }

        .lb-img {
            width: 90px !important;
            height: 90px !important;
            object-fit: contain !important;
            margin-bottom: 20px !important;
            filter: drop-shadow(0 0 15px rgba(255,255,255,0.15)) !important;
        }

        .lb-title {
            font-family: 'Cinzel', serif !important;
            color: #fff !important;
            font-size: 18px !important;
            font-weight: 700 !important;
            text-transform: uppercase !important;
            letter-spacing: 1px !important;
            margin-bottom: 15px !important;
        }

        .lb-cost {
            background: rgba(200, 161, 101, 0.1) !important;
            color: #c8a165 !important;
            border: 1px solid #c8a165 !important;
            padding: 6px 25px !important;
            border-radius: 30px !important;
            font-size: 13px !important;
            font-weight: 800 !important;
            margin-top: auto !important;
        }

        .lb-contents {
            font-size: 12px !important;
            color: #555 !important;
            margin-top: 15px !important;
            text-transform: uppercase !important;
            letter-spacing: 0.5px !important;
        }

        /* --- RESPONSIVE --- */
        @media (max-width: 992px) {
            .box-selector-grid { grid-template-columns: repeat(2, 1fr) !important; }
        }
        @media (max-width: 600px) {
            .box-selector-grid { grid-template-columns: 1fr !important; }
            .casino-cover { height: 140px !important; }
            .casino-intro h2 { font-size: 28px !important; }
        }
    `;

    // 2. INYECTAR CSS
    var styleEl = document.createElement('style');
    styleEl.innerHTML = css;
    document.head.appendChild(styleEl);

    // 3. INTERVALO DE AJUSTE (Watchdog)
    var checkInterval = setInterval(function() {
        var container = document.querySelector('.casino-container');
        if (container) {
            // Ajustar el área de juego si está activa
            var gameArea = document.getElementById('game-screen');
            if(gameArea) {
                gameArea.style.cssText = "order: 4 !important; width: 100% !important; margin-top: 40px !important;";
            }
            clearInterval(checkInterval);
        }
    }, 200);

})();

/* ==========================================================================
   BLACK MARKET - INJECT HEADER (TITLE & DESC)
   ========================================================================== */

(function() {
    // 1. CSS para el Header inyectado (Estilo Canibal)
    var bmCss = `
        /* Estilos del Header Inyectado */
        .bm-injected-header {
            text-align: center !important;
            margin-bottom: 40px !important;
            border-bottom: 1px solid #2a2e35 !important;
            padding-bottom: 20px !important;
            width: 100% !important;
        }
        
        .bm-injected-title {
            font-family: 'Cinzel', serif !important;
            color: #ffffff !important;
            font-size: 28px !important;
            font-weight: bold !important;
            text-transform: uppercase !important;
            letter-spacing: 1px !important;
            margin-bottom: 5px !important;
        }

        .bm-injected-desc {
            color: #a0a0a0 !important;
            font-size: 12px !important;
            text-transform: uppercase !important;
            letter-spacing: 1px !important;
            max-width: 800px !important;
            margin: 0 auto !important;
        }

        /* Ajustes menores al contenedor original para que encaje bien */
        .bm-wrap {
            padding-top: 10px !important;
        }
        
        /* Estilizar las pestañas para que parezcan del Canibal */
        .bm-head {
            background: transparent !important;
            border-bottom: 1px solid #2d323e !important;
            padding: 0 !important;
            margin-bottom: 30px !important;
        }
        .bm-btn-tab {
            background: transparent !important;
            color: #888 !important;
            font-family: 'Roboto', sans-serif !important;
            text-transform: uppercase !important;
            font-size: 13px !important;
            font-weight: bold !important;
            border: none !important;
            border-bottom: 3px solid transparent !important;
            transition: 0.3s !important;
        }
        .bm-btn-tab:hover {
            color: #fff !important;
        }
        .bm-btn-tab.active {
            background: transparent !important;
            color: #c8a165 !important;
            border-bottom: 3px solid #c8a165 !important;
        }
    `;

    var styleEl = document.createElement('style');
    styleEl.innerHTML = bmCss;
    document.head.appendChild(styleEl);

    // 2. Watchdog: Esperar a que el Black Market cargue e inyectar el HTML
    var bmInterval = setInterval(function() {
        var bmContainer = document.querySelector('.bm-wrap');
        
        if (bmContainer && !document.querySelector('.bm-injected-header')) {
            // El Black Market está cargado y aún no inyectamos el header.
            
            // Creamos el contenedor del header
            var headerDiv = document.createElement('div');
            headerDiv.className = 'bm-injected-header';
            
            // Título y Descripción estáticos (Puedes cambiarlos aquí)
            headerDiv.innerHTML = `
                <h1 class="bm-injected-title">Black Market</h1>
                <p class="bm-injected-desc">El comercio clandestino de MuOnline. Compra y vende objetos únicos bajo tu propio riesgo.</p>
            `;
            
            // Insertamos el header JUSTO ANTES del primer elemento dentro de .bm-wrap
            bmContainer.insertBefore(headerDiv, bmContainer.firstChild);
            
            // Detenemos el intervalo porque ya hicimos el trabajo
            clearInterval(bmInterval);
        }
    }, 300); // Revisa cada 300ms
})();

/* ==========================================================================
   CHARACTERS MODULE - FULL WIDTH & CUSTOM LAYOUT (2x2 STATS, SPLIT BUTTONS)
   ========================================================================== */

(function() {
    var charCss = `
        /* --- CONTENEDOR PRINCIPAL (100% Ancho, Lista Vertical) --- */
        .characters-fixed-container {
            max-width: 100% !important; 
            padding: 10px !important;
            background: transparent !important;
        }
        
        .characters-fixed-row {
            display: flex !important;
            flex-direction: column !important; /* Apila las tarjetas verticalmente */
            margin: 0 !important;
            gap: 25px !important; /* Espacio entre cada personaje */
        }

        .characters-fixed-row > .col-md-6 {
            display: block !important;
            width: 100% !important;
            max-width: 100% !important;
            flex: 0 0 100% !important;
            padding: 0 !important;
            margin-bottom: 20px !important; /* <--- ACÁ LE DAMOS LA SEPARACIÓN */
        }

        /* --- TARJETA DE PERSONAJE --- */
        .char-card-premium {
            background-color: #111418 !important; 
            border: 1px solid #2d323e !important;
            border-radius: 8px !important;
            box-shadow: 0 5px 15px rgba(0,0,0,0.4) !important;
            padding: 30px !important;
            display: flex !important;
            flex-direction: column !important;
            width: 100% !important;
            transition: all 0.3s ease !important;
        }

        .char-card-premium:hover {
            border-color: #c8a165 !important;
            box-shadow: 0 10px 30px rgba(200, 161, 101, 0.2) !important;
            transform: translateY(-3px) !important;
        }

        /* --- HEADER (AVATAR Y TEXTOS) --- */
        .char-card-premium > .d-flex.align-items-center.mb-4 {
            border-bottom: 1px solid #2d323e !important;
            padding-bottom: 20px !important;
            margin-bottom: 20px !important;
        }

        .char-avatar-container {
            width: 85px !important;
            height: 85px !important;
            border-radius: 6px !important;
            border: 2px solid #2d323e !important;
            background: #0b0d10 !important;
            padding: 3px !important;
            flex-shrink: 0 !important;
            position: relative !important;
        }

        .char-img-rank {
            width: 100% !important;
            height: 100% !important;
            object-fit: cover !important;
            border-radius: 4px !important;
        }

        .char-level-badge {
            position: absolute !important;
            bottom: -10px !important;
            left: 50% !important;
            transform: translateX(-50%) !important;
            background: #c8a165 !important;
            color: #000 !important;
            font-size: 11px !important;
            font-weight: 900 !important;
            padding: 3px 12px !important;
            border-radius: 10px !important;
            white-space: nowrap !important;
            box-shadow: 0 2px 5px rgba(0,0,0,0.8) !important;
        }

        .char-info-header h4 {
            font-family: 'Cinzel', serif !important;
            color: #c8a165 !important;
            font-size: 24px !important;
            margin-bottom: 2px !important;
            font-weight: bold !important;
        }

        .char-info-header p {
            color: #8b949e !important;
            font-size: 14px !important;
            margin-bottom: 8px !important;
            letter-spacing: 0.5px !important;
        }

        /* --- ESTADÍSTICAS (FORZAR 2 COLUMNAS Y 2 FILAS) --- */
        .char-card-premium .row.g-2 {
            display: flex !important;
            flex-wrap: wrap !important;
            margin: 0 -10px 25px -10px !important;
        }
        
        .char-card-premium .row.g-2 > .col-6 {
            width: 50% !important;
            max-width: 50% !important;
            flex: 0 0 50% !important;
            padding: 0 10px 15px 10px !important; /* Espacio entre las cajas */
        }
        
        .stat-card-modern {
            background: #0b0d10 !important;
            border: 1px solid #1f232a !important;
            padding: 12px 15px !important;
            border-radius: 8px !important;
            display: flex !important;
            align-items: center !important;
            height: 100% !important;
        }
        
        .stat-icon-wrapper {
            width: 40px !important;
            height: 40px !important;
            font-size: 16px !important;
            border-radius: 6px !important;
            margin-right: 15px !important;
        }
        
        .stat-label { font-size: 11px !important; color: #888 !important; letter-spacing: 1px !important; }
        .stat-value { font-size: 16px !important; color: #fff !important; }

        /* --- BOTONES (VER PERFIL ARRIBA, RESET/DESATASCAR ABAJO) --- */
        .char-card-premium .gap-2 {
            display: flex !important;
            flex-wrap: wrap !important; /* Permite que los elementos salten a la línea de abajo */
            gap: 15px !important;
            margin-top: auto !important;
        }

        .char-card-premium .gap-2 > a,
        .char-card-premium .gap-2 > button {
            padding: 14px 10px !important;
            font-size: 14px !important;
            font-weight: bold !important;
            text-transform: uppercase !important;
            letter-spacing: 1px !important;
            border-radius: 6px !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            margin: 0 !important;
        }

        /* "Ver Perfil" ocupa toda la fila (Orden 1) */
        .char-card-premium .gap-2 > a {
            width: 100% !important;
            flex: 0 0 100% !important;
            order: 1 !important;
        }

        /* "Reset" y "Desatascar" ocupan 50% cada uno (Orden 2) */
        .char-card-premium .gap-2 > button {
            width: calc(50% - 7.5px) !important; /* Le resta la mitad del gap para que cuadren perfecto */
            flex: 1 1 calc(50% - 7.5px) !important;
            order: 2 !important;
        }

        /* --- RESPONSIVE MÓVILES --- */
        @media (max-width: 576px) {
            .char-card-premium .gap-2 > button,
            .char-card-premium .row.g-2 > .col-6 {
                width: 100% !important;
                max-width: 100% !important;
                flex: 0 0 100% !important;
            }
        }
    `;

    var styleEl = document.createElement('style');
    styleEl.innerHTML = charCss;
    document.head.appendChild(styleEl);

    var charInterval = setInterval(function() {
        var charCards = document.querySelectorAll('.char-card-premium');
        if (charCards.length > 0) {
            var mainContainer = charCards[0].closest('.donation-main-container');
            if (mainContainer) {
                mainContainer.classList.add('characters-fixed-container');
                var row = mainContainer.querySelector('.row');
                if (row) {
                    row.classList.add('characters-fixed-row');
                }
            }
            clearInterval(charInterval);
        }
    }, 200);
})();

/* ==========================================================================
   VOTE MODULE - GRID FIX (SIDE-BY-SIDE CARDS)
   ========================================================================== */

(function() {
    var voteCss = `
        /* --- REVERTIR EL BLOQUEO DE DONATE.CSS --- */
        .vote-fixed-container {
            max-width: 100% !important;
        }

        /* Forzar que la fila vuelva a comportarse como Flexbox */
        .vote-fixed-container .vote-fixed-row {
            display: flex !important;
            flex-direction: row !important;
            flex-wrap: wrap !important;
            margin: 0 -10px !important;
        }

        /* Forzar a las columnas a ocupar 1/3 del espacio (3 por fila) */
        .vote-fixed-container .vote-fixed-row > div[class*="col-"] {
            display: flex !important;
            width: 33.3333% !important;
            max-width: 33.3333% !important;
            flex: 0 0 33.3333% !important;
            padding: 10px !important;
            margin: 0 !important;
        }

        /* Estética Canibal para las tarjetas de votos */
        .vote-fixed-container .settings-form-box {
            width: 100% !important;
            background-color: #111418 !important; /* Fondo más oscuro */
            transition: all 0.3s ease !important;
        }

        .vote-fixed-container .settings-form-box:hover {
            border-color: #c8a165 !important; /* Borde dorado al pasar el mouse */
            box-shadow: 0 10px 30px rgba(200, 161, 101, 0.2) !important;
            transform: translateY(-5px) !important;
        }

        /* --- RESPONSIVE (MÓVILES Y TABLETS) --- */
        @media (max-width: 992px) {
            .vote-fixed-container .vote-fixed-row > div[class*="col-"] {
                width: 50% !important;
                max-width: 50% !important;
                flex: 0 0 50% !important;
            }
        }

        @media (max-width: 576px) {
            .vote-fixed-container .vote-fixed-row > div[class*="col-"] {
                width: 100% !important;
                max-width: 100% !important;
                flex: 0 0 100% !important;
            }
        }
    `;

    // Inyectar el CSS
    var styleEl = document.createElement('style');
    styleEl.innerHTML = voteCss;
    document.head.appendChild(styleEl);

    // Watchdog: Vigilar si estamos en la página de votos
    var voteInterval = setInterval(function() {
        // Buscamos los títulos para saber en qué módulo estamos
        var titles = document.querySelectorAll('.module-title h2');
        var isVotePage = false;
        
        for (var i = 0; i < titles.length; i++) {
            if (titles[i].innerText.includes('VOTOS')) {
                isVotePage = true;
                break;
            }
        }
        
        // Si estamos en la página de votos, aplicamos la cura
        if (isVotePage) {
            var container = document.querySelector('.donation-main-container');
            if (container && !container.classList.contains('vote-fixed-container')) {
                container.classList.add('vote-fixed-container');
                var row = container.querySelector('.row.g-3'); // El div que envuelve las cartas
                if (row) {
                    row.classList.add('vote-fixed-row');
                }
            }
        }
    }, 300);
})();

/* ==========================================================================
   PLAYER PROFILE MODULE - REWORK (CANIBAL THEME + ANTI-FLICKER + ALIGNMENT FIX)
   ========================================================================== */

(function() {
    var profileCss = `
        /* --- ANTI-FLICKER --- */
        .profile-wrapper {
            opacity: 0 !important;
            visibility: hidden !important;
        }
        .profile-wrapper.c-ready {
            opacity: 1 !important;
            visibility: visible !important;
            transition: opacity 0.4s ease !important;
        }
        .profile-wrapper > .row.g-4 {
            display: none !important;
        }

        /* --- CONTENEDOR PRINCIPAL NUEVO --- */
        .c-profile-container {
            max-width: 950px !important;
            margin: 0 auto !important;
            font-family: Arial, sans-serif !important;
            padding: 20px 0 !important;
            box-sizing: border-box !important;
        }

        /* HEADER DEL PERSONAJE */
        .c-profile-header {
            display: flex !important;
            align-items: flex-end !important;
            margin-bottom: 20px !important;
            border-bottom: 1px solid #2d323e !important;
            padding-bottom: 10px !important;
        }
        .c-profile-name {
            font-family: 'Cinzel', serif !important;
            color: #c8a165 !important;
            font-size: 32px !important;
            font-weight: bold !important;
            margin: 0 !important;
            letter-spacing: 2px !important;
            text-transform: uppercase !important;
            text-shadow: 0 0 15px rgba(200, 161, 101, 0.3) !important;
        }

        /* --- CAJA PRINCIPAL (IMAGEN + STATS) --- */
        .c-profile-main-box {
            background: #111418 !important;
            border: 1px solid #2d323e !important;
            border-radius: 8px !important;
            display: flex !important;
            overflow: hidden !important;
            box-shadow: 0 10px 40px rgba(0,0,0,0.6) !important;
            margin-bottom: 30px !important;
            width: 100% !important;
            box-sizing: border-box !important;
        }

        /* IZQUIERDA: Imagen de Fondo */
        .c-profile-left {
            width: 40% !important;
            min-width: 300px !important;
            flex-shrink: 0 !important;
            background-image: url('/img/loren.png') !important;
            background-size: cover !important;
            background-position: center center !important;
            background-repeat: no-repeat !important;
            border-right: 1px solid #2d323e !important;
            position: relative !important;
            min-height: 450px !important;
        }
        
        #character-3d-container { display: none !important; }

        /* DERECHA: Lista de Estadísticas */
        .c-profile-right {
            flex: 1 !important;
            background: #0b0d10 !important;
            padding: 30px !important;
            display: flex !important;
            flex-direction: column !important;
            justify-content: center !important;
            box-sizing: border-box !important;
        }
        
        .c-stat-list {
            list-style: none !important;
            padding: 0 !important;
            margin: 0 !important;
            border: 1px solid #1a1d24 !important;
            border-radius: 6px !important;
            overflow: hidden !important;
            width: 100% !important;
        }

        .c-stat-item {
            display: flex !important;
            justify-content: space-between !important;
            align-items: center !important;
            padding: 14px 20px !important;
            border-bottom: 1px solid #1a1d24 !important;
            background: rgba(255,255,255,0.01) !important;
        }
        .c-stat-item:nth-child(even) { background: transparent !important; }
        .c-stat-item:last-child { border-bottom: none !important; }

        .c-stat-label {
            font-weight: 700 !important;
            color: #888 !important;
            font-size: 13px !important;
            text-transform: uppercase !important;
            letter-spacing: 1px !important;
        }

        .c-stat-value {
            color: #fff !important;
            font-size: 14px !important;
            font-weight: bold !important;
            display: flex !important;
            align-items: center !important;
            gap: 10px !important;
            text-align: right !important;
        }
        .c-stat-value b, .c-stat-value .text-orange { color: #c8a165 !important; font-weight: bold !important; }
        .c-stat-value img { vertical-align: middle !important; }

        /* --- SECCIÓN INFERIOR (Inventario & PvP) --- */
        .c-profile-extras {
            display: flex !important;
            gap: 30px !important;
            width: 100% !important;
            box-sizing: border-box !important;
        }
        
        .c-extra-box {
            flex: 1 1 0 !important; /* REPARACIÓN: Ocupa el espacio sin desbordar */
            min-width: 0 !important; /* Previene desborde en Flexbox */
            max-width: calc(50% - 15px) !important; /* Límite matemático estricto */
            background: #111418 !important;
            border: 1px solid #2d323e !important;
            border-radius: 8px !important;
            padding: 25px !important;
            box-shadow: 0 10px 30px rgba(0,0,0,0.4) !important;
            box-sizing: border-box !important;
        }
        
        .c-extra-title {
            color: #c8a165 !important;
            font-family: 'Cinzel', serif !important;
            font-weight: bold !important;
            font-size: 20px !important;
            border-bottom: 1px solid #2d323e !important;
            padding-bottom: 15px !important;
            margin-top: 0 !important;
            margin-bottom: 20px !important;
            text-transform: uppercase !important;
            letter-spacing: 1px !important;
        }
        
        .c-profile-extras .c-extra-box:first-child > div:nth-of-type(1) {
             margin-left: -19px !important; /* Ajustá este número si querés moverlo más o menos */
             position: relative !important;
        }

        /* --- HISTORIAL PVP --- */
        .c-extra-box .activity-log {
            padding-right: 15px !important;
            height: 300px !important;
            overflow-y: auto !important;
            overflow-x: hidden !important;
        }
        
        .c-extra-box .log-item {
            padding: 12px 0 !important;
            border-bottom: 1px dashed rgba(255, 255, 255, 0.08) !important;
            display: block !important; 
            width: 100% !important;
        }

        .c-extra-box .log-flex-container {
            display: flex !important;
            justify-content: space-between !important;
            align-items: center !important;
            width: 100% !important;
            font-family: 'Roboto', 'Arial', sans-serif !important; 
            font-size: 13px !important;
            color: #aaa !important;
            text-transform: none !important;
        }

        .c-extra-box .log-action-text {
            white-space: nowrap !important;
            overflow: hidden !important;
            text-overflow: ellipsis !important;
            flex: 1 !important;
            padding-right: 15px !important;
        }

        .c-extra-box .log-item b {
            color: #c8a165 !important;
            font-weight: 700 !important;
            letter-spacing: 0.5px !important;
        }

        .c-extra-box .log-item span.text-muted {
            flex-shrink: 0 !important;
            color: #666 !important;
            font-size: 11px !important;
            font-style: italic !important;
            text-align: right !important;
        }

        .c-extra-box .activity-log::-webkit-scrollbar { width: 5px !important; }
        .c-extra-box .activity-log::-webkit-scrollbar-track { background: #0b0d10 !important; border-radius: 4px !important; }
        .c-extra-box .activity-log::-webkit-scrollbar-thumb { background: #2d323e !important; border-radius: 4px !important; }
        .c-extra-box .activity-log::-webkit-scrollbar-thumb:hover { background: #c8a165 !important; }

        /* Responsive */
        @media (max-width: 992px) {
            .c-profile-main-box { flex-direction: column !important; }
            .c-profile-left { width: 100% !important; min-width: auto !important; border-right: none !important; border-bottom: 1px solid #2d323e !important; min-height: 300px !important; }
            .c-profile-right { padding: 20px !important; }
            .c-profile-extras { flex-direction: column !important; gap: 20px !important; }
            .c-extra-box { max-width: 100% !important; width: 100% !important; }
        }
    `;

    var styleEl = document.createElement('style');
    styleEl.innerHTML = profileCss;
    document.head.appendChild(styleEl);

    var profileInterval = setInterval(function() {
        var wrapper = document.querySelector('.profile-wrapper');
        if (!wrapper) return;

        if (wrapper.querySelector('.c-profile-container')) {
            clearInterval(profileInterval);
            return;
        }

        var oldRow = wrapper.querySelector('.row.g-4');
        if (!oldRow) return;

        // EXTRAER DATOS
        var nameEl = wrapper.querySelector('h2.trajan-title');
        var charName = nameEl ? nameEl.innerText : 'Personaje';

        var levelText = 'Desconocido';
        var smallText = wrapper.querySelector('small.cinzel-text');
        if(smallText) {
            var match = smallText.innerText.match(/Nivel (\d+)/i);
            if(match) levelText = match[1];
        }

        var guildName = 'Ninguno';
        var guildImgHtml = '';
        var guildEl = wrapper.querySelector('.text-white-50.trajan-title');
        if(guildEl) {
            guildName = guildEl.innerText;
            var gImg = wrapper.querySelector('img[alt="Guild Logo"]');
            if(gImg) guildImgHtml = gImg.outerHTML;
        }

        var statsObj = {};
        wrapper.querySelectorAll('.stat-line').forEach(function(line) {
            var labelSpan = line.querySelector('span:first-child');
            var valSpan = line.querySelector('span:last-child');
            if(labelSpan && valSpan) {
                var label = labelSpan.innerText.replace(':', '').trim();
                statsObj[label] = valSpan.innerHTML; 
            }
        });

        var eloVal = '0';
        var eloSpan = wrapper.querySelector('.elo-badge span.text-orange');
        if(eloSpan) eloVal = eloSpan.innerText;

        var inventoryCol = wrapper.querySelectorAll('.col-lg-4')[1];
        var inventoryNode = inventoryCol ? inventoryCol.querySelector('.w-100.d-flex.align-items-stretch.flex-grow-1') : null;
        var pvpLogsNode = wrapper.querySelector('.activity-log');

        // CONSTRUIR NUEVO HTML
        var newContainer = document.createElement('div');
        newContainer.className = 'c-profile-container';

        newContainer.innerHTML += `
            <div class="c-profile-header">
                <h2 class="c-profile-name">${charName}</h2>
            </div>
        `;

        var mainBox = document.createElement('div');
        mainBox.className = 'c-profile-main-box';

        var leftBox = document.createElement('div');
        leftBox.className = 'c-profile-left';

        var rightBox = document.createElement('div');
        rightBox.className = 'c-profile-right';

        var htmlStats = '<ul class="c-stat-list">';
        htmlStats += `<li class="c-stat-item"><span class="c-stat-label">Nivel</span><span class="c-stat-value">${levelText}</span></li>`;
        htmlStats += `<li class="c-stat-item"><span class="c-stat-label">Guild</span><span class="c-stat-value">${guildImgHtml} ${guildName}</span></li>`;
        
        var statMap = {'STR':'Fuerza', 'AGI':'Agilidad', 'VIT':'Vitalidad', 'ENE':'Energía', 'CMD':'Comando'};
        for(var key in statsObj) {
            var displayKey = statMap[key] || key;
            htmlStats += `<li class="c-stat-item"><span class="c-stat-label">${displayKey}</span><span class="c-stat-value">${statsObj[key]}</span></li>`;
        }
        
        htmlStats += `<li class="c-stat-item"><span class="c-stat-label">PvP Elo</span><span class="c-stat-value text-orange">${eloVal}</span></li>`;
        htmlStats += '</ul>';
        
        rightBox.innerHTML = htmlStats;

        mainBox.appendChild(leftBox);
        mainBox.appendChild(rightBox);
        newContainer.appendChild(mainBox);

        // SECCIÓN INFERIOR
        var extrasBox = document.createElement('div');
        extrasBox.className = 'c-profile-extras';

        // Caja de Inventario
        var invBox = document.createElement('div');
        invBox.className = 'c-extra-box';
        invBox.innerHTML = '<h3 class="c-extra-title">Equipamiento</h3>';
        
        if(inventoryNode) {
            // ANIQUILACIÓN DEL TÍTULO DUPLICADO
            var innerTitles = inventoryNode.querySelectorAll('.module-title, h1, h2, h3, h4, h5, h6');
            innerTitles.forEach(function(t) {
                if(t.innerText.toLowerCase().includes('equipamiento') || t.innerText.toLowerCase().includes('inventory')) {
                    t.remove(); // Se borra del DOM, ya no hay duplicados
                }
            });
            invBox.appendChild(inventoryNode);
        }

        // Caja de Logs
        var logBox = document.createElement('div');
        logBox.className = 'c-extra-box';
        logBox.innerHTML = '<h3 class="c-extra-title">Historial PvP</h3>';
        
        if(pvpLogsNode) {
            var logs = pvpLogsNode.querySelectorAll('.log-item');
            logs.forEach(function(log) {
                var cText = log.querySelector('.cinzel-text');
                if (cText) {
                    var timeSpan = cText.querySelector('.text-muted');
                    if (timeSpan) {
                        cText.removeChild(timeSpan);
                        
                        var textContent = document.createElement('div');
                        textContent.className = 'log-action-text';
                        textContent.innerHTML = cText.innerHTML;
                        
                        cText.innerHTML = '';
                        cText.classList.remove('cinzel-text');
                        cText.classList.add('log-flex-container');
                        
                        cText.appendChild(textContent);
                        cText.appendChild(timeSpan);
                    }
                }
            });
            logBox.appendChild(pvpLogsNode);
        }

        extrasBox.appendChild(invBox);
        extrasBox.appendChild(logBox);
        newContainer.appendChild(extrasBox);

        wrapper.appendChild(newContainer);
        
        setTimeout(function() {
            wrapper.classList.add('c-ready');
        }, 50);

        clearInterval(profileInterval);

    }, 50);
})();

/* ==========================================================================
   FIX AISLADO PARA LA PÁGINA DE REGISTRO (SOLO ÍCONOS EN INPUTS)
   ========================================================================== */
document.addEventListener("DOMContentLoaded", function() {
    // Verificamos ÚNICA Y EXCLUSIVAMENTE que exista la caja del formulario de registro
    if (document.getElementById('register-page-balduneta')) {
        
        // 1. INYECTAMOS EL LINK DE LOGIN SIN TOCAR EL PHP (Y SIN REDES SOCIALES)
        const registerForm = document.querySelector('#register-page-balduneta .register-form');
        if (registerForm && !document.getElementById('injected-login-link')) {
            const extraSection = document.createElement('div');
            extraSection.id = 'injected-login-link';
            extraSection.className = 'register-extra-section text-center mt-3';
            const loginLink = (typeof baseUrl !== 'undefined' ? baseUrl : '/') + 'login';
            
            extraSection.innerHTML = `
                <a href="${loginLink}" class="btn-mini-link">¿Ya tienes una cuenta? <span style="color:#00d084;">Ingresar</span></a>
            `;
            registerForm.appendChild(extraSection);
        }

        // 2. INYECTAMOS LOS ESTILOS BLINDADOS
        const registerStyle = document.createElement('style');
        registerStyle.innerHTML = `
            /* --- ESTILOS BASE DEL FORMULARIO --- */
            #downloads-page-balduneta { max-width: 550px !important; margin: 60px auto !important; }
            #downloads-page-balduneta .rank-header { text-align: center !important; margin-bottom: 30px !important; }
            #downloads-page-balduneta .rank-title { 
                font-size: 18px !important; font-weight: 400 !important; color: #ffffff !important; 
                padding-bottom: 15px !important; position: relative !important; text-transform: none !important; 
                border-bottom: 1px solid rgba(255, 255, 255, 0.2) !important; display: block !important; width: 100% !important;
            }
            #downloads-page-balduneta .rank-subtitle { display: none !important; }
            
            #register-page-balduneta .register-main-container { background: transparent !important; border: none !important; box-shadow: none !important; padding: 0 !important; }
            #register-page-balduneta .form-group { margin-bottom: 15px !important; }
            
            /* Ocultamos texto de label y checkbox de TOS */
            #register-page-balduneta .register-label, 
            #register-page-balduneta .checkbox-container { display: none !important; }

            /* --- CONTENEDOR DEL INPUT --- */
            #register-page-balduneta .input-container { 
                position: relative !important; 
                width: 100% !important; 
            }

            /* --- ÍCONOS INDICATIVOS (LOS "BOTONCITOS" ADENTRO DEL INPUT) --- */
            /* IMPORTANTE: Los volvemos visibles (block/flex) porque antes estaban en 'none' */
            #register-page-balduneta .input-icon { 
                display: flex !important; 
                align-items: center !important; 
                justify-content: center !important; 
                position: absolute !important; 
                left: 1px !important; 
                top: 1px !important; 
                bottom: 1px !important; 
                width: 45px !important; 
                background: rgba(255, 255, 255, 0.03) !important; 
                border-right: 1px solid #2a2a2a !important; 
                border-radius: 4px 0 0 4px !important; 
                color: #666 !important; 
                font-size: 14px !important; 
                transition: all 0.3s ease !important;
                pointer-events: none !important; /* Para que el click traspase al input */
            }

            /* Efecto del ícono cuando hacés click para escribir */
            #register-page-balduneta .input-container:focus-within .input-icon {
                color: #00d084 !important; /* Letra verde */
                background: rgba(0, 208, 132, 0.05) !important; /* Fondo sutil verde */
                border-right: 1px solid rgba(0, 208, 132, 0.3) !important;
            }

            /* --- INPUTS TEXTO --- */
            #register-page-balduneta .form-control-balduneta { 
                width: 100% !important; background-color: #0a0a0a !important; border: 1px solid #2a2a2a !important;
                border-radius: 4px !important; 
                padding: 12px 15px 12px 60px !important; /* padding-left en 60px para hacerle espacio al ícono */
                color: #ffffff !important; 
                font-size: 14px !important; outline: none !important; box-shadow: none !important;
                transition: all 0.3s ease !important;
            }
            #register-page-balduneta .form-control-balduneta::placeholder { color: #666666 !important; opacity: 1 !important; }
            #register-page-balduneta .form-control-balduneta:focus { border-color: rgba(0, 208, 132, 0.5) !important; }

            /* --- BOTÓN PRINCIPAL --- */
            #register-page-balduneta .btn-register-balduneta { 
                width: 100% !important; margin-top: 25px !important; display: block !important; 
                background-color: #000000 !important; color: #ffffff !important; 
                border: 1px solid #00d084 !important; border-radius: 4px !important; 
                padding: 14px 0 !important; font-size: 14px !important; font-weight: 600 !important; 
                text-transform: uppercase !important; cursor: pointer !important; transition: all 0.3s ease !important; 
            }
            #register-page-balduneta .btn-register-balduneta:hover { background-color: rgba(0, 208, 132, 0.1) !important; }

            /* --- LINK INFERIOR (YA TIENES CUENTA) --- */
            #register-page-balduneta .register-extra-section { margin-top: 15px !important; text-align: center !important; }
            #register-page-balduneta .btn-mini-link {
                color: #888 !important; text-decoration: none !important; font-size: 13px !important; 
                transition: color 0.3s ease !important; display: inline-block !important; font-family: var(--body-font, "Poppins", sans-serif);
            }
            #register-page-balduneta .btn-mini-link:hover { color: #ffffff !important; }
            
            /* Móvil */
            @media (max-width: 768px) { #downloads-page-balduneta { padding: 0 20px !important; } }
        `;
        document.head.appendChild(registerStyle);
    }
});