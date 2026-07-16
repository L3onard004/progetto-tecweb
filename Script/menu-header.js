document.addEventListener("DOMContentLoaded", function () {
    if (!('querySelector' in document) || !('addEventListener' in window) || !('fetch' in window)) {
        return;
    }

    /* ==========================================
       1. SELETTORI DOM
       ========================================== */
    // Menu Mobile
    const header = document.querySelector("header");
    const toggle = document.querySelector(".menu-toggle");
    const navbar = document.getElementById("navbar");

    // Elementi Globali e Ricerca
    const overlay = document.getElementById('overlay');
    const searchBar = document.getElementById('search-bar');
    const searchInput = document.getElementById('search-input');
    const btnSearch = document.getElementById('btn-search');
    
    // Carrello
    const btnCart = document.getElementById('btn-cart');
    const cartDrawer = document.getElementById('cart-drawer');
    const btnDrawerClose = document.getElementById('btn-drawer-close');

    if (header) header.classList.add("menu-ready");
    // Nasconde la search bar via JS: senza JS resta visibile
    if (searchBar) searchBar.setAttribute('hidden', '');

    /* ==========================================
       2. FUNZIONI DI CHIUSURA E APERTURA
       ========================================== */
    
    // Overlay
    function toggleOverlay(show) {
        if (!overlay) return;
        if (show) overlay.removeAttribute('hidden');
        else overlay.setAttribute('hidden', '');
    }

    // Menu Mobile
    function isMobileView() { return window.innerWidth <= 768; }

    function closeMenu() {
        if (!header || !toggle) return;
        header.classList.remove("menu-open");
        toggle.setAttribute("aria-expanded", "false");
        toggle.setAttribute("aria-label", "Apri il menu di navigazione");
    }

    function openMenu() {
        if (!header || !toggle) return;
        header.classList.add("menu-open");
        toggle.setAttribute("aria-expanded", "true");
        toggle.setAttribute("aria-label", "Chiudi il menu di navigazione");
    }

    // Ricerca
    function closeSearch() {
        if (!searchBar || !btnSearch) return;
        searchBar.setAttribute('hidden', '');
        btnSearch.setAttribute('aria-expanded', 'false');
    }

    function openSearch() {
        if (!searchBar || !btnSearch) return;
        searchBar.removeAttribute('hidden');
        btnSearch.setAttribute('aria-expanded', 'true');
        // Sposta il cursore automaticamente sull'input
        if (searchInput) setTimeout(() => searchInput.focus(), 50);
    }

    //Carrello Drawer
    const drawerItems = document.getElementById('drawer-items');
    const cartTotal   = document.getElementById('cart-total');
    const cartBadge   = document.getElementById('cart-badge');

    function formatPrice(num) {
        return num.toFixed(2).replace('.', ',');
    }

    function renderDrawer(data) {
        if (!drawerItems) return;

        if (!data.logged_in) {
            drawerItems.innerHTML = '<p class="drawer-empty">Accedi per vedere il tuo carrello. <a href="login.php">Vai al login</a></p>';
            if (cartTotal) cartTotal.textContent = '€0,00';
        } else if (data.items.length === 0) {
            drawerItems.innerHTML = '<p class="drawer-empty">Il tuo carrello è vuoto.</p>';
            if (cartTotal) cartTotal.textContent = '€0,00';
        } else {
            drawerItems.innerHTML = data.items.map(item => `
                <article class="drawer-item">
                    ${item.img_src
                        ? `<img src="${item.img_src}" alt="${item.img_alt}" class="drawer-item-img" loading="lazy">`
                        : ''}
                    <div class="drawer-item-info">
                        <p class="drawer-item-name">${item.nome}</p>
                        <p class="drawer-item-qty">x${item.quantita} — €${formatPrice(item.subtotale)}</p>
                    </div>
                    <button class="drawer-remove" data-id="${item.id}" aria-label="Rimuovi dal carrello">
                        <img src="immagini/icone/x.png" alt="" aria-hidden="true" class="close-icon-img" />
                    </button>
                </article>
            `).join('');
            if (cartTotal) cartTotal.textContent = '€' + formatPrice(data.total);
        }

        if (cartBadge) cartBadge.textContent = data.count;
    }

    function loadCartData(callback) {
        fetch('dati_carrello.php')
            .then(r => {
                if (!r.ok) throw new Error('HTTP ' + r.status);
                return r.json();
            })
            .then(data => {
                if (callback) callback(data);
                if (cartBadge) cartBadge.textContent = data.count;
            })
            .catch(() => {
                if (callback && drawerItems) {
                    drawerItems.innerHTML = '<p class="drawer-empty">Impossibile caricare il carrello.</p>';
                }
                if (cartBadge) cartBadge.textContent = '0';
            });
    }

    function closeCart() {
        if (!cartDrawer || !btnCart) return;
        cartDrawer.setAttribute('hidden', '');
        cartDrawer.setAttribute('aria-hidden', 'true');
        btnCart.setAttribute('aria-expanded', 'false');
    }

    function openCart() {
        if (!cartDrawer || !btnCart) return;
        cartDrawer.removeAttribute('hidden');
        cartDrawer.setAttribute('aria-hidden', 'false');
        btnCart.setAttribute('aria-expanded', 'true');
        if (drawerItems) drawerItems.innerHTML = '<p class="drawer-loading">Caricamento...</p>';
        loadCartData(renderDrawer);
    }

    loadCartData(null);

    // Rimozione prodotto direttamente dal drawer
    if (drawerItems) {
        drawerItems.addEventListener('click', (e) => {
            const btn = e.target.closest('.drawer-remove');
            if (!btn) return;
            const formData = new FormData();
            formData.append('azione', 'rimuovi');
            formData.append('id_prodotto', btn.dataset.id);
            fetch('carrello.php', { method: 'POST', body: formData })
                .finally(() => loadCartData(renderDrawer));
        });
    }

    function closeAll() {
        closeMenu();
        closeSearch();
        closeCart();
        toggleOverlay(false);
    }

    /* ==========================================
       3. GESTIONE DEGLI EVENTI (CLICK)
       ========================================== */

    // Hamburger Menu
    if (toggle) {
        toggle.addEventListener("click", function () {
            if (header.classList.contains("menu-open")) {
                closeMenu();
            } else {
                closeAll();
                openMenu();
            }
        });
    }

    // Icona Ricerca
    if (btnSearch) {
        btnSearch.addEventListener('click', () => {
            const isHidden = searchBar.hasAttribute('hidden');
            if (isHidden) {
                closeAll();
                openSearch();
            } else {
                closeSearch();
            }
        });
    }

    // Icona Carrello
    if (btnCart && cartDrawer) {
        btnCart.addEventListener('click', (e) => {
            e.preventDefault();
            const isHidden = cartDrawer.hasAttribute('hidden');
            if (isHidden) {
                closeAll();
                openCart();
                toggleOverlay(true); 
            } else {
                closeCart();
                toggleOverlay(false);
            }
        });
    }

    // Chiusura Carrello
    if (btnDrawerClose) {
        btnDrawerClose.addEventListener('click', () => {
            closeCart();
            toggleOverlay(false);
        });
    }

    // Chiusura cliccando lo sfondo
    if (overlay) {
        overlay.addEventListener('click', () => {
            closeCart();
            toggleOverlay(false);
        });
    }

    // Continua lo shopping: chiude il drawer
    const btnContinue = document.getElementById('btn-continue-shopping');
    if (btnContinue) {
        btnContinue.addEventListener('click', () => {
            closeCart();
            toggleOverlay(false);
        });
    }

    /* ==========================================
       4. ACCESSIBILITÀ E CLICK ESTERNI
       ========================================== */

    // Click fuori dal menu mobile
    document.addEventListener("click", function (event) {
        if (isMobileView() && header && header.classList.contains("menu-open")) {
            if (!header.contains(event.target)) closeMenu();
        }
    });

    // Pressione del tasto Esc sulla tastiera (Chiude tutto)
    document.addEventListener("keydown", function (event) {
        if (event.key === "Escape") {
            closeAll();
            
            // Riporta il focus al pulsante se il menu era aperto
            if (header && header.classList.contains("menu-open") && toggle) toggle.focus();
        }
    });

    // Chiusura automatica menu mobile quando clicchi un link interno
    if (navbar) {
        navbar.querySelectorAll("a").forEach(function (link) {
            link.addEventListener("click", function () {
                if (isMobileView()) closeMenu();
            });
        });
    }

    // Controllo ridimensionamento della finestra
    window.addEventListener("resize", function () {
        if (!isMobileView()) closeMenu();
    });
});