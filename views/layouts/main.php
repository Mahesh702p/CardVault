<?php
/**
 * Main Layout Template
 */
// Prevent Varnish / proxy caches from storing authenticated pages
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Pragma: no-cache");
$currentUser = AuthMiddleware::user();

$currentRoute = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/') ?: 'dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#7B2D26">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <title><?= htmlspecialchars($pageTitle ?? 'CardVault') ?> — CardVault</title>
    <meta name="description" content="Enterprise Visiting Card Management System with AI-powered card scanning">
    <link rel="manifest" href="<?= APP_URL ?>/manifest-v3.json">
    <link rel="apple-touch-icon" href="<?= APP_URL ?>/img/icon-192-v3.png">
    <link rel="stylesheet" href="<?= APP_URL ?>/css/style.css?v=2.1.1">
    <!-- Anti-flash: apply saved theme before page renders -->
    <script>try{var t=localStorage.getItem('cv_theme');if(t)document.documentElement.setAttribute('data-theme',t);}catch(e){}</script>
    <script>
    window.pwaDeferredPrompt = null;
    window.addEventListener('beforeinstallprompt', (e) => {
        e.preventDefault();
        window.pwaDeferredPrompt = e;
    });
    </script>
</head>
<body>
<div class="app-layout">
    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-brand" style="flex-direction: column; align-items: center; justify-content: center; text-align: center; gap: 0.5rem; padding: 1.5rem 1rem;">
            <img src="<?= APP_URL ?>/img/logo.png?v=2.0.7" alt="CardVault Logo" style="width:56px; height:56px; border-radius:12px; object-fit:cover; flex-shrink:0;">
            <h1 style="font-size: 1.25rem; font-weight: 700; margin: 0;">CardVault</h1>
        </div>

        <nav class="sidebar-nav">
            <div class="nav-section">Main</div>
            <a href="<?= APP_URL ?>/dashboard" class="nav-item <?= $currentRoute === 'dashboard' ? 'active' : '' ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
                Dashboard
            </a>
            <a href="<?= APP_URL ?>/cards/upload" class="nav-item <?= $currentRoute === 'cards/upload' ? 'active' : '' ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M23 19a2 2 0 01-2 2H3a2 2 0 01-2-2V8a2 2 0 012-2h4l2-3h6l2 3h4a2 2 0 012 2z"/><circle cx="12" cy="13" r="4"/></svg>
                Scan Card
            </a>
            <a href="<?= APP_URL ?>/search" class="nav-item <?= $currentRoute === 'search' ? 'active' : '' ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>
                Search
            </a>
            <a href="<?= APP_URL ?>/help" class="nav-item <?= $currentRoute === 'help' ? 'active' : '' ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:18px; height:18px; color: var(--text-muted);"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 015.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                Help & Support
            </a>


            <?php if ($currentUser && $currentUser['role'] === 'admin'): ?>
            <div class="nav-section">Admin</div>
            <a href="<?= APP_URL ?>/users" class="nav-item <?= $currentRoute === 'users' ? 'active' : '' ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87"/><path d="M16 3.13a4 4 0 010 7.75"/></svg>
                Users
            </a>
            <?php endif; ?>
        </nav>

        <!-- Sidebar Install App Button -->
        <div class="sidebar-install-wrap" id="sidebarInstallWrap" style="display: none; border-top: 1px solid var(--border-color); margin-top: auto; padding: 0.25rem 0;">
            <a href="#" class="nav-item install-app-btn" id="btnSidebarInstall" style="margin: 0; cursor: pointer;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18" style="color: var(--accent); flex-shrink: 0;"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                <span style="font-weight: 500;">Install App</span>
            </a>
        </div>

        <?php if ($currentUser): ?>
        <div class="sidebar-user">
            <?php
            $nameParts = array_filter(explode(' ', $currentUser['name'] ?? 'U'));
            $initials  = strtoupper(implode('', array_map(fn($w) => $w[0], $nameParts)));
            $initials  = substr($initials, 0, 2);
            ?>
            <a href="<?= APP_URL ?>/profile" style="display:flex; align-items:center; gap:0.75rem; flex:1; min-width:0; text-decoration:none; color:inherit;" title="My Profile & Privacy Settings">
                <div class="avatar"><?= htmlspecialchars($initials) ?></div>
                <div class="user-info">
                    <div class="user-name"><?= htmlspecialchars($currentUser['name']) ?></div>
                    <div class="user-dept"><?= htmlspecialchars($currentUser['department_name']) ?></div>
                </div>
            </a>
            <div style="display:flex; gap:0.5rem; align-items:center; flex-shrink:0;">
                <a href="<?= APP_URL ?>/auth/logout" title="Logout" style="color:var(--text-muted); display:flex; align-items:center;" onclick="event.stopPropagation();">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                </a>
            </div>
        </div>
        <?php endif; ?>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
        <div class="topbar">
            <button class="menu-toggle" id="menuToggle" onclick="toggleSidebar()">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
            </button>
            <h2><?= htmlspecialchars($pageTitle ?? 'CardVault') ?></h2>
            <form action="<?= APP_URL ?>/search" method="GET" class="search-global">
                <svg class="search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>
                <input type="text" name="q" placeholder="Search cards, companies, products..." value="<?= htmlspecialchars($_GET['q'] ?? '') ?>" autocomplete="off" id="globalSearch">
                <button type="button" class="search-clear-btn <?= !empty($_GET['q']) ? 'visible' : '' ?>" id="searchClearBtn" title="Clear search">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
                <div class="search-suggestions" id="searchSuggestions"></div>
            </form>
            <!-- Dark Mode Toggle -->
            <button class="dark-mode-btn" id="darkModeBtn" title="Toggle dark mode" onclick="toggleDarkMode()">
                <svg class="icon-moon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>
                <svg class="icon-sun" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/></svg>
            </button>
        </div>

        <div class="page-content">
            <?php if (!empty($flash)): ?>
            <div class="alert alert-<?= $flash['type'] === 'error' ? 'error' : ($flash['type'] ?? 'info') ?>">
                <?= htmlspecialchars($flash['message'] ?? '') ?>
            </div>
            <?php endif; ?>

            <?php include VIEW_PATH . '/' . $view . '.php'; ?>
        </div>
    </main>
</div>

<!-- Sidebar overlay (mobile) -->
<div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>

<!-- Loading Overlay -->
<div class="loading-overlay" id="loadingOverlay">
    <div class="spinner"></div>
    <p id="loadingText">Processing card with AI...</p>
</div>

<script src="<?= APP_URL ?>/js/app.js?v=2.0.4"></script>
<script>
function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');
    sidebar.classList.toggle('open');
    overlay.classList.toggle('active');
}

function toggleDarkMode() {
    const html = document.documentElement;
    const isDark = html.getAttribute('data-theme') === 'dark';
    const next = isDark ? 'light' : 'dark';
    html.setAttribute('data-theme', next);
    try { localStorage.setItem('cv_theme', next); } catch(e) {}
}
</script>
<script>
if ('serviceWorker' in navigator) {
    navigator.serviceWorker.register('<?= APP_URL ?>/sw.js')
        .then(reg => console.log('SW registered:', reg.scope))
        .catch(err => console.warn('SW registration failed:', err));
}
</script>

<!-- PWA Install Prompt Modal -->
<div class="pwa-install-modal-overlay" id="pwaInstallModal" style="display: none;">
    <div class="pwa-install-modal-content">
        <button class="pwa-install-modal-close" onclick="closePwaInstallModal()">&times;</button>
        <div class="pwa-install-logo-container">
            <img src="<?= APP_URL ?>/img/logo.png?v=2.0.7" alt="CardVault Logo">
        </div>
        <h3>Get the CardVault App</h3>
        <p>Install CardVault on your home screen for quick offline access, a full-screen experience, and instant card scanning.</p>
        <div class="pwa-install-actions">
            <button class="pwa-btn-primary" id="btnPwaInstall">Install Now</button>
            <button class="pwa-btn-secondary" onclick="closePwaInstallModal()">Maybe Later</button>
        </div>
        <div class="pwa-install-instructions" id="pwaInstructions" style="display: none;">
            <p class="instructions-title">How to Install Manually:</p>
            <ol>
                <li class="ios-only" style="display: none;">Tap the <strong>Share</strong> button <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display:inline-block; vertical-align:middle; margin: 0 2px;"><path d="M4 12v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8"/><polyline points="16 6 12 2 8 6"/><line x1="12" y1="2" x2="12" y2="15"/></svg> at the bottom, then select <strong>Add to Home Screen</strong>.</li>
                <li class="android-only" style="display: none;">Tap the browser menu icon <strong style="font-size:1.1rem;">⋮</strong>, then select <strong>Add to Home Screen</strong> or <strong>Install app</strong>.</li>
                <li class="other-browsers">Click the menu icon or share button and select <strong>Add to Home Screen</strong> or <strong>Install app</strong>.</li>
            </ol>
        </div>
    </div>
</div>

<script>
function showPwaInstallModal() {
    const modal = document.getElementById('pwaInstallModal');
    if (modal) {
        modal.style.display = 'flex';
        modal.classList.remove('fade-out');
        const btn = document.getElementById('btnPwaInstall');
        if (btn) btn.style.display = 'block';
        const inst = document.getElementById('pwaInstructions');
        if (inst) inst.style.display = 'none';
    }
}

function closePwaInstallModal() {
    const modal = document.getElementById('pwaInstallModal');
    if (modal) {
        modal.classList.add('fade-out');
        setTimeout(() => {
            modal.style.display = 'none';
        }, 300);
    }
}

document.addEventListener('DOMContentLoaded', () => {
    // Detect OS for instructions
    const ua = navigator.userAgent.toLowerCase();
    const isIOS = /iphone|ipad|ipod/.test(ua);
    const isAndroid = /android/.test(ua);

    if (isIOS) {
        document.querySelectorAll('.ios-only').forEach(el => el.style.display = 'block');
        document.querySelectorAll('.other-browsers').forEach(el => el.style.display = 'none');
    } else if (isAndroid) {
        document.querySelectorAll('.android-only').forEach(el => el.style.display = 'block');
        document.querySelectorAll('.other-browsers').forEach(el => el.style.display = 'none');
    }

    // Standalone check
    const isStandalone = window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;
    const sidebarWrap = document.getElementById('sidebarInstallWrap');
    if (isStandalone) {
        if (sidebarWrap) sidebarWrap.style.display = 'none';
    } else {
        if (sidebarWrap) sidebarWrap.style.display = 'block';
    }

    // Sidebar Install Click
    const sidebarBtn = document.getElementById('btnSidebarInstall');
    sidebarBtn?.addEventListener('click', (e) => {
        e.preventDefault();
        showPwaInstallModal();
    });

    // Modal Install Click
    const btn = document.getElementById('btnPwaInstall');
    btn?.addEventListener('click', async () => {
        if (window.pwaDeferredPrompt) {
            window.pwaDeferredPrompt.prompt();
            const { outcome } = await window.pwaDeferredPrompt.userChoice;
            console.log('User response to PWA prompt:', outcome);
            window.pwaDeferredPrompt = null;
            closePwaInstallModal();
        } else {
            btn.style.display = 'none';
            document.getElementById('pwaInstructions').style.display = 'block';
        }
    });

    // Auto-prompt after registration if requested
    <?php if (!empty($_SESSION['show_pwa_install_prompt'])): ?>
        <?php unset($_SESSION['show_pwa_install_prompt']); ?>
        setTimeout(showPwaInstallModal, 600);
    <?php endif; ?>
});
</script>
</body>
</html>
