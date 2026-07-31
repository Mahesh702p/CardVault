<?php
/**
 * Login Page
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#7B2D26">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <title>Login — CardVault</title>
    <link rel="manifest" href="<?= APP_URL ?>/manifest-v3.json">
    <link rel="apple-touch-icon" href="<?= APP_URL ?>/img/icon-192-v3.png">
    <link rel="stylesheet" href="<?= APP_URL ?>/css/style.css?v=2.1.1">
    <style>
        * { -webkit-tap-highlight-color: transparent !important; }
    </style>
</head>
<body>
<div class="login-page">
    <div class="login-card">
        <div class="login-brand">
            <div class="login-logo" style="background:none; width:76px; height:76px;">
                <img src="<?= APP_URL ?>/img/logo.png?v=2.0.7" alt="CardVault Logo"
                     style="width:100%; height:100%; object-fit:contain; border-radius:inherit;">
            </div>
            <h1>CardVault</h1>
            <p class="subtitle">Hiranandani Visiting Card Management</p>
        </div>

        <?php if (!empty($flash)): ?>
        <div class="alert alert-<?= $flash['type'] === 'error' ? 'error' : ($flash['type'] ?? 'info') ?>">
            <?= htmlspecialchars($flash['message'] ?? '') ?>
        </div>
        <?php endif; ?>

        <form method="POST" action="<?= APP_URL ?>/auth/login">
            <div class="form-group">
                <label class="form-label" for="employee_id">Employee ID</label>
                <input type="text" id="employee_id" name="employee_id" class="form-input"
                       placeholder="e.g. HGPCPL0623" required autofocus autocomplete="username">
            </div>
            <div class="form-group" style="margin-top:1rem;">
                <label class="form-label" for="password">Password</label>
                <input type="password" id="password" name="password" class="form-input"
                       placeholder="Enter your password" required autocomplete="current-password">
            </div>
            <button type="submit" class="btn btn-primary btn-lg"
                    style="width:100%; justify-content:center; margin-top:1.5rem;">
                Sign In
            </button>
        </form>

        <p style="text-align:center; margin-top:1.5rem; font-size:0.82rem; color:var(--text-muted);">
            Don't have an account?
            <a href="<?= APP_URL ?>/register"
               style="color:var(--accent); font-weight:600; text-decoration:none;">Register</a>
        </p>
        <p style="text-align:center; margin-top:0.5rem; font-size:0.82rem; color:var(--text-muted);">
            Forgot your password? Contact
            <a href="mailto:mahesh2005patel@gmail.com?subject=CardVault%20Password%20Reset%20Request"
               style="color:var(--accent); font-weight:600; text-decoration:none;">System Admin</a>.
        </p>
    </div>
</div>
<script>
if ('serviceWorker' in navigator) {
    navigator.serviceWorker.register('<?= APP_URL ?>/sw.js')
        .then(r => console.log('SW registered:', r.scope))
        .catch(e => console.warn('SW failed:', e));
}
</script>
</body>
</html>
