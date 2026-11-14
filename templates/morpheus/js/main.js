// --- 1. TOOLTIPS & UI HELPERS ---
function idx_show_tt(el) { 
    var tt = document.getElementById('global-tt'); 
    if(tt) {
        tt.innerHTML = el.getAttribute('data-tooltip'); 
        tt.style.display = 'block'; 
    }
}

function idx_move_tt(e) { 
    var tt = document.getElementById('global-tt'); 
    if(tt) {
        tt.style.left = (e.clientX + 15) + 'px'; 
        tt.style.top = (e.clientY + 15) + 'px'; 
    }
}

function idx_hide_tt() { 
    var tt = document.getElementById('global-tt'); 
    if(tt) tt.style.display = 'none'; 
}

function showAlertModal(title, msg) {
    const titleEl = document.getElementById('alertTitle');
    const bodyEl = document.getElementById('alertBody');
    const modalEl = document.getElementById('alertModal');
    
    if(titleEl && bodyEl && modalEl) {
        titleEl.innerText = title;
        bodyEl.innerHTML = msg;
        modalEl.style.display = 'flex';
    }
}

// --- 2. MARKET SYSTEM ---
var bm_buy_item_id = 0; 

function loadMarket() {
    if(!document.getElementById('market-widget-content')) return;

    let fd = new FormData();
    fd.append('market_ajax', 'true');
    fd.append('action', 'load_widget');
    
    fetch(window.location.href, { method: 'POST', body: fd })
    .then(r => r.json())
    .then(res => {
        let html = '';
        if(res.status === 'success' && res.data.length > 0) {
            res.data.forEach(i => {
                html += `<div class="market-card" onmousemove="idx_move_tt(event)" onmouseover="idx_show_tt(this)" onmouseout="idx_hide_tt()" data-tooltip="${i.tooltip}" onclick="openDirectBuy(${i.id}, '${i.price} ${i.currency}')">
                    <div class="mc-id-corner">${i.id}</div>
                    <div class="mc-image-box"><img src="${i.image}"></div>
                    <div class="mc-info">
                        <span class="mc-label">Black Market</span>
                        <span class="mc-name">${i.name} ${i.level}</span>
                        <div class="mc-price-box"><span class="mc-price-val">${i.price}</span> <span class="mc-currency-badge">${i.currency}</span></div>
                    </div>
                </div>`;
            });
        } else { html = '<div class="text-center p-3 text-muted">Mercado vacío</div>'; }
        document.getElementById('market-widget-content').innerHTML = html;
    })
    .catch(err => console.error('Market Error:', err));
}

// openDirectBuy() y executeBuy() están definidas en templates/morpheus/index.php
// (con el aviso "PROCESANDO COMPRA..."). No volver a declararlas aquí.

// --- 3. RANKING WIDGET ---
function switchRanking(type) {
    const rankContent = document.getElementById('rankListContent');
    if(!rankContent) return;

    let fd = new FormData();
    fd.append('ranking_ajax', 'true');
    fd.append('type', type);
    
    const colMid = document.getElementById('rankColMid');
    const colVal = document.getElementById('rankColVal');
    
    if(colMid && colVal) {
        if(type === 'Guilds') { colMid.innerText = 'Logo'; colVal.innerText = 'Score'; }
        else if(type === 'Killers') { colMid.innerText = 'Avatar'; colVal.innerText = 'Kills'; }
        else if(type === 'Gens') { colMid.innerText = 'Family'; colVal.innerText = 'Points'; }
        else { colMid.innerText = 'Avatar'; colVal.innerText = type; }
    }

    rankContent.innerHTML = '<div class="p-4 text-center text-muted">Cargando...</div>';

    fetch(window.location.href, { method: 'POST', body: fd })
    .then(r => r.json())
    .then(res => {
        let html = '';
        if(res.status === 'success' && res.data.length > 0) {
            res.data.forEach((p, idx) => {
                html += `<div class="rank-row">
                    <div class="r-nick"><span class="r-name">${p.name}</span></div>
                    <div class="r-flag">${p.race_html}</div>
                    <div class="r-val">${p.val}</div>
                </div>`;
            });
        } else { html = '<div class="text-center p-3 text-muted">Sin datos</div>'; }
        rankContent.innerHTML = html;
    })
    .catch(err => console.error('Ranking Error:', err));
}

// --- 4. DOWNLOADS PAGE LOGIC ---
function initDownloadsPage() {
    const dlPage = document.getElementById('downloads-page-balduneta');
    
    if (dlPage) {
        document.body.classList.add('downloads-mode');

        const firstLink = dlPage.querySelector('a.btn-dl-gradient');
        if (firstLink) {
            const url = firstLink.getAttribute('href');
            const sName = (typeof SERVER_NAME !== 'undefined') ? SERVER_NAME : 'Mu Online';
            
            const heroHTML = `
                <div class="dl-injected-hero">
                    <h1 class="dl-injected-title">Download ${sName}</h1>
                    <a href="${url}" target="_blank" class="dl-injected-btn">
                        <span class="dl-big-text">CLIENTE FULL</span>
                        <span class="dl-small-text">Descarga Directa Recomendada</span>
                    </a>
                </div>
            `;
            dlPage.insertAdjacentHTML('afterbegin', heroHTML);
        }
        
        const headers = dlPage.querySelectorAll('h2, h3');
        headers.forEach(h => {
            const txt = h.innerText.toUpperCase();
            if(txt.includes('CLIENTE')) h.innerText = 'Mirrors';
            if(txt.includes('HERRAMIENTAS') || txt.includes('TOOLS')) h.innerText = 'Descargas Adicionales';
            if(txt.includes('REQUISITOS') || txt.includes('REQUERIMIENTOS')) h.innerText = 'Requerimientos';
        });
    }
}

// --- 5. RANKING PAGE LOGIC ---
function initRankingPage() {
    const wrapper = document.querySelector('.rankings-wrapper');
    
    if (wrapper) {
        document.body.classList.add('rankings-mode');

        if (!document.querySelector('.js-rank-hero')) {
            const navButtons = wrapper.querySelectorAll('.rank-nav-btn');
            let optionsHTML = '';
            let activeTitle = 'General';

            navButtons.forEach(btn => {
                const text = btn.innerText.trim();
                const onClickAttr = btn.getAttribute('onclick');
                let type = 'general';
                if(onClickAttr) {
                    const match = onClickAttr.match(/'([^']+)'/);
                    if(match) type = match[1];
                }
                
                const isSelected = btn.classList.contains('active');
                if(isSelected) activeTitle = text;

                optionsHTML += `<option value="${type}" ${isSelected ? 'selected' : ''}>${text}</option>`;
            });

            const injectedHTML = `
                <div class="js-rank-hero">
                    <span class="js-rank-subtitle">Ranking</span>
                    <h1 class="js-rank-title" id="jsDynamicTitle">${activeTitle}</h1>
                </div>
                <div class="js-rank-toolbar">
                    <div class="js-search-wrap">
                        <i class="fa fa-search js-search-icon"></i>
                        <input type="text" id="jsRankSearch" class="js-rank-input" placeholder="Buscar personaje..." onkeyup="clientSideRankFilter()">
                    </div>
                    <div class="js-dropdown-wrap">
                        <select id="jsRankDropdown" onchange="triggerOriginalRank(this.value)">
                            ${optionsHTML}
                        </select>
                    </div>
                </div>
            `;
            const tableCard = wrapper.querySelector('.rank-table-card');
            if(tableCard) tableCard.insertAdjacentHTML('beforebegin', injectedHTML);
        }
    }
}

function triggerOriginalRank(type) {
    const dropdown = document.getElementById('jsRankDropdown');
    const selectedText = dropdown.options[dropdown.selectedIndex].text;
    document.getElementById('jsDynamicTitle').innerText = selectedText;
    if (typeof loadRank === 'function') loadRank(type, null); 
}

function clientSideRankFilter() {
    const input = document.getElementById('jsRankSearch');
    if (!input) return;
    const filter = input.value.toUpperCase();
    const tbody = document.getElementById('rank-content'); 
    if (!tbody) return;
    const tr = tbody.getElementsByTagName('tr');
    for (let i = 0; i < tr.length; i++) {
        if (tr[i].innerText.toUpperCase().indexOf(filter) > -1) tr[i].style.display = "";
        else tr[i].style.display = "none";
    }
}

// --- 6. INFO / GUIDES PAGE LOGIC ---
function filterGuides() {
    let input = document.getElementById('guideSearch').value.toLowerCase();
    let cards = document.querySelectorAll('.guide-card');
    cards.forEach(card => {
        let title = card.querySelector('.guide-h').innerText.toLowerCase();
        if (title.includes(input)) card.style.display = "flex"; 
        else card.style.display = "none";
    });
}

function openGuideReader(guide) {
    document.getElementById('gmTitle').innerText = guide.title;
    document.getElementById('gmDate').innerText = "Publicado el: " + guide.date;
    document.getElementById('gmBody').innerHTML = guide.content;
    
    const banner = document.getElementById('gmBanner');
    if(guide.image && guide.image !== "") {
        banner.src = (typeof baseUrl !== 'undefined' ? baseUrl : '') + guide.image;
        banner.style.display = "block";
    } else { banner.style.display = "none"; }

    document.getElementById('guideReader').style.display = 'flex';
    document.body.style.overflow = 'hidden'; 
}

function closeReader() {
    const reader = document.getElementById('guideReader');
    if(reader) {
        reader.style.display = 'none';
        document.body.style.overflow = 'auto';
    }
}

// --- 7. LOGIN MODAL SYSTEM ---
function openLoginModal() {
    let modal = document.getElementById('jsLoginModal');
    if (!modal) {
        const modalHTML = `
            <div id="jsLoginModal" class="login-overlay">
                <div class="login-box">
                    <span class="login-close" onclick="closeLoginModal()">&times;</span>
                    <h2 class="login-title">Login</h2>
                    <form action="" method="POST" onsubmit="showLoginSpinner(this)">
                        <input type="hidden" name="msmLogin_submit" value="submit">
                        <div class="login-form-group">
                            <input type="text" name="msmLogin_user" class="login-input" placeholder="Usuario" required autocomplete="username">
                        </div>
                        <div class="login-form-group">
                            <input type="password" name="msmLogin_pwd" class="login-input" placeholder="Contraseña" required autocomplete="current-password">
                        </div>
                        <button type="submit" class="login-btn-enter" id="btnLoginSubmit">Entrar</button>
                        <div class="login-options">
                            <input type="checkbox" id="chkRemember" name="msmLogin_remember" value="1" class="login-checkbox">
                            <label for="chkRemember">Remember me</label>
                        </div>
                        <div class="login-footer">
                            <a href="${baseUrl}forgotpassword" class="login-link">Recovery password</a>
                            <a href="${baseUrl}register" class="login-link-gold">Sign Up</a>
                        </div>
                    </form>
                </div>
            </div>
        `;
        document.body.insertAdjacentHTML('beforeend', modalHTML);
        modal = document.getElementById('jsLoginModal');
        modal.addEventListener('click', function(e) {
            if (e.target === modal) closeLoginModal();
        });
    }
    modal.style.display = 'flex';
    setTimeout(() => { modal.classList.add('active'); }, 10);
    document.body.style.overflow = 'hidden';
}

function closeLoginModal() {
    const modal = document.getElementById('jsLoginModal');
    if (modal) {
        modal.classList.remove('active');
        setTimeout(() => {
            modal.style.display = 'none';
            document.body.style.overflow = 'auto';
            const btn = document.getElementById('btnLoginSubmit');
            if(btn) { btn.innerHTML = 'ENTRAR'; btn.style.opacity = '1'; }
        }, 300);
    }
}

function showLoginSpinner(form) {
    const btn = document.getElementById('btnLoginSubmit');
    if(btn) {
        btn.innerHTML = '<i class="fa fa-circle-notch fa-spin"></i> CARGANDO...';
        btn.style.opacity = '0.7';
    }
    return true; 
}

// --- 8. REGISTER PAGE HERO INJECTION (FIX DOBLE EJECUCIÓN) ---
function initRegisterHero() {
    // 0. FRENO DE MANO: Si ya existe el Hero, no hacemos NADA.
    if (document.querySelector('.js-register-hero')) {
        console.log("DEBUG: El Hero ya existe. Deteniendo segunda ejecución.");
        return;
    }

    console.log("--- INICIANDO REGISTER HERO ---");

    // 1. Detección del Formulario
    const originalForm = document.querySelector('.register-form') || 
                         document.querySelector('form[action*="register"]') || 
                         document.querySelector('#register-page-balduneta form');
    
    if (!originalForm) return;

    // 2. Detección del Contenedor Padre
    const originalContainer = document.getElementById('register-page-balduneta') || 
                              originalForm.closest('.register-main-container') || 
                              originalForm.closest('.container');

    try {
        // 3. Preparación del Formulario
        originalForm.style.visibility = 'visible';
        originalForm.style.opacity = '1';
        originalForm.classList.add('js-grid-form');

        // 4. Limpieza y Estructura de Inputs
        const groups = originalForm.querySelectorAll('div, .form-group');
        const iconsMap = { 'user': 'fa-user', 'email': 'fa-envelope', 'pwd': 'fa-lock', 'pwdc': 'fa-lock', 'id_code': 'fa-id-card', 'default': 'fa-pen' };

        groups.forEach(div => {
            // Limpieza de iconos viejos
            div.querySelectorAll('.input-icon, i:not(.fa)').forEach(icon => icon.remove());
            div.style.marginBottom = "0";

            // Envolver inputs
            const input = div.querySelector('input:not([type="checkbox"]):not([type="hidden"])');
            // Verificamos que no sea el input hidden del login o tokens
            if (input && !input.parentElement.classList.contains('js-input-group')) {
                let iconClass = iconsMap.default;
                for (const [key, val] of Object.entries(iconsMap)) {
                    if (input.name.toLowerCase().includes(key)) iconClass = val;
                }
                
                const wrapper = document.createElement('div');
                wrapper.className = 'js-input-group';
                
                const iconBox = document.createElement('div');
                iconBox.className = 'js-input-icon-box';
                iconBox.innerHTML = `<i class="fa ${iconClass}"></i>`;

                input.parentNode.insertBefore(wrapper, input);
                wrapper.appendChild(iconBox);
                wrapper.appendChild(input);
            }

            // Full Width elements
            const hasCheckbox = div.querySelector('input[type="checkbox"]');
            const isButton = div.querySelector('button');
            if (hasCheckbox || isButton || div.classList.contains('g-recaptcha')) {
                div.classList.add('js-full-width');
            }
        });

        const btn = originalForm.querySelector('button[type="submit"]');
        if(btn && btn.parentElement) btn.parentElement.classList.add('js-full-width');

        // 5. Creación del Hero
        const heroSection = document.createElement('div');
        heroSection.className = 'js-register-hero';
        
        // Usamos la URL que vimos en tu LOG que sabemos que es correcta
        // Ojo: Asegurate que esa imagen exista. Si da 404, verás negro.
        const bgUrl = (typeof baseUrl !== 'undefined' ? baseUrl : '/') + 'img/misc/register.jpg';
        heroSection.style.backgroundImage = `url('${bgUrl}')`;

        const serverName = (document.title.split('-')[0] || "MU ONLINE").trim();

        heroSection.innerHTML = `
            <div class="js-reg-overlay"></div>
            <div class="js-reg-content">
                <div class="js-reg-titles">
                    <span class="js-reg-sub">BIENVENIDO A ${serverName}</span>
                    <h1 class="js-reg-main-title">REGISTRO</h1>
                </div>
                <div class="js-reg-card" id="jsFormSlot"></div>
            </div>
        `;

        // 6. Inyección: antes del pie, para que el pie siga visible debajo
        const footerEl = document.querySelector('.morph-footer');
        if (footerEl && footerEl.parentNode) {
            footerEl.parentNode.insertBefore(heroSection, footerEl);
        } else {
            document.body.appendChild(heroSection);
        }

        const formSlot = document.getElementById('jsFormSlot');

        // Avisos del registro (errores, invitación por referido): se muestran arriba del formulario
        if (originalContainer) {
            originalContainer.querySelectorAll('.alert').forEach(function (al) {
                formSlot.appendChild(al);
            });
            originalContainer.style.display = 'none';
        }

        // Movemos el formulario
        formSlot.appendChild(originalForm);

    } catch (err) {
        console.error("ERROR CRITICO:", err);
    }
}

// --- 9. NEWS PAGE HERO INJECTION (FONDO PANTALLA COMPLETA) ---
function initNewsHero() {
    // 1. Verificar si estamos en la página de noticias
    const newsContainer = document.querySelector('.news-container');
    if (!newsContainer) return; // Si no existe el contenedor de noticias, no hacemos nada.

    // 2. Verificar si ya se inyectó para no duplicar
    if (document.querySelector('.js-news-hero-bg')) return;

    console.log("--- INICIANDO NEWS HERO ---");

    // 3. Crear los elementos del fondo
    const heroBg = document.createElement('div');
    heroBg.className = 'js-news-hero-bg';
    
    const heroOverlay = document.createElement('div');
    heroOverlay.className = 'js-news-hero-overlay';

    // 4. Configurar estilos CSS directamente en el JS (para asegurar que pise todo)
    const bgUrl = (typeof baseUrl !== 'undefined' ? baseUrl : '/') + 'img/misc/news.png';
    
    Object.assign(heroBg.style, {
        position: 'fixed',
        top: '0',
        left: '0',
        width: '100vw',
        height: '100vh',
        zIndex: '0', // Nivel 0: Detrás del contenido (z-index 10) y del Navbar (z-index 9999)
        backgroundImage: `url('${bgUrl}')`,
        backgroundSize: 'cover',
        backgroundPosition: 'center',
        backgroundRepeat: 'no-repeat',
        backgroundAttachment: 'fixed',
        backgroundColor: '#111' // Color de respaldo
    });

    Object.assign(heroOverlay.style, {
        position: 'absolute',
        top: '0',
        left: '0',
        width: '100%',
        height: '100%',
        // Degradado oscuro para que el texto resalte sobre la imagen
        background: 'linear-gradient(to bottom, rgba(10, 10, 15, 0.8) 0%, rgba(10, 10, 15, 0.95) 100%)',
        zIndex: '1'
    });

    // 5. Ensamblar e inyectar al principio del BODY
    heroBg.appendChild(heroOverlay);
    // Insertamos antes del primer hijo del body para que quede al fondo de todo
    document.body.insertBefore(heroBg, document.body.firstChild);
    
    console.log("DEBUG: Fondo de noticias inyectado correctamente.");
}

// --- 10. GUIDES PAGE HERO INJECTION ---
function initGuidesHero() {
    const guidesContainer = document.querySelector('.guides-container');
    if (!guidesContainer) return;

    if (document.querySelector('.js-guides-hero-bg')) return;

    console.log("--- INICIANDO GUIDES HERO ---");

    const heroBg = document.createElement('div');
    heroBg.className = 'js-guides-hero-bg';
    
    const heroOverlay = document.createElement('div');
    heroOverlay.className = 'js-guides-hero-overlay';

    // RUTA DE IMAGEN: Asegurate de tener guides.png o guide.jpg en img/misc/
    const bgUrl = (typeof baseUrl !== 'undefined' ? baseUrl : '/') + 'img/misc/guides.jpg';
    
    Object.assign(heroBg.style, {
        position: 'fixed', top: '0', left: '0', width: '100vw', height: '100vh',
        zIndex: '0', 
        backgroundImage: `url('${bgUrl}')`,
        backgroundSize: 'cover', backgroundPosition: 'center', backgroundRepeat: 'no-repeat', backgroundAttachment: 'fixed',
        backgroundColor: '#111'
    });

    Object.assign(heroOverlay.style, {
        position: 'absolute', top: '0', left: '0', width: '100%', height: '100%',
        background: 'linear-gradient(to bottom, rgba(10, 10, 15, 0.8) 0%, rgba(10, 10, 15, 0.95) 100%)',
        zIndex: '1'
    });

    heroBg.appendChild(heroOverlay);
    document.body.insertBefore(heroBg, document.body.firstChild);
    console.log("DEBUG: Fondo de guías inyectado.");
}

// --- 11. FORGOT PASSWORD HERO INJECTION ---
function initForgotHero() {
    // Detectamos si estamos en el módulo por el nombre único del input
    const forgotInput = document.querySelector('input[name="msmEmail_current"]');
    
    if (!forgotInput) return; // Si no está el input, no es esta página
    if (document.querySelector('.js-forgot-hero-bg')) return; // Evitar duplicados

    console.log("--- INICIANDO FORGOT PASS HERO ---");

    const heroBg = document.createElement('div');
    heroBg.className = 'js-forgot-hero-bg';
    
    const heroOverlay = document.createElement('div');
    heroOverlay.className = 'js-forgot-hero-overlay';

    // RUTA DE IMAGEN: img/misc/forgot.jpg
    const bgUrl = (typeof baseUrl !== 'undefined' ? baseUrl : '/') + 'img/misc/forgot.jpg';
    
    // Aplicamos estilos fijos
    Object.assign(heroBg.style, {
        position: 'fixed', top: '0', left: '0', width: '100vw', height: '100vh',
        zIndex: '0', 
        backgroundImage: `url('${bgUrl}')`,
        backgroundSize: 'cover', backgroundPosition: 'center', backgroundRepeat: 'no-repeat', backgroundAttachment: 'fixed',
        backgroundColor: '#111'
    });

    // Overlay oscuro para leer bien el formulario
    Object.assign(heroOverlay.style, {
        position: 'absolute', top: '0', left: '0', width: '100%', height: '100%',
        background: 'linear-gradient(to bottom, rgba(10, 10, 15, 0.7) 0%, rgba(10, 10, 15, 0.95) 100%)',
        zIndex: '1'
    });

    heroBg.appendChild(heroOverlay);
    document.body.insertBefore(heroBg, document.body.firstChild);
    
    // Opcional: Agregar clase al contenedor para asegurar z-index
    const mainContainer = forgotInput.closest('.container') || forgotInput.closest('.content');
    if(mainContainer) {
        mainContainer.style.position = 'relative';
        mainContainer.style.zIndex = '10';
    }

    console.log("DEBUG: Fondo Forgot Password inyectado.");
}

// Global Escape Close
document.addEventListener('keydown', function(e) {
    if (e.key === "Escape") {
        if(typeof closeReader === 'function') closeReader(); 
        if(typeof closeLoginModal === 'function') closeLoginModal(); 
    }
});

// --- INITIALIZATION ---
document.addEventListener('DOMContentLoaded', () => {
    loadMarket();
    
    const select = document.getElementById('rankFilterSelect');
    if(select && select.options.length > 0) {
        switchRanking(select.options[0].value);
    }

    initDownloadsPage();
    initRankingPage();
    initRegisterHero(); 
    initNewsHero();
    initGuidesHero();
    initForgotHero();

    const btnBuy = document.getElementById('btnDoBuy');
    if(btnBuy) {
        btnBuy.onclick = function() { executeBuy(); };
    }

    window.onclick = function(event) {
        let modal = document.getElementById('guideReader');
        if (modal && event.target == modal) closeReader();
    };
});