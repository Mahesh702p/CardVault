<?php
/**
 * Register Page — Employee Self-Registration
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
    <title>Register — CardVault</title>
    <link rel="manifest" href="<?= APP_URL ?>/manifest-v3.json">
    <link rel="apple-touch-icon" href="<?= APP_URL ?>/img/icon-192-v3.png">
    <link rel="stylesheet" href="<?= APP_URL ?>/css/style.css?v=2.1.1">
    <style>
        * {
            -webkit-tap-highlight-color: transparent !important;
            -webkit-tap-highlight-color: rgba(0,0,0,0) !important;
        }
    </style>
</head>
<body>
<div class="login-page">
    <div class="login-card">
        <div class="login-brand">
            <div class="login-logo" style="background:none; width:76px; height:76px;"><img src="<?= APP_URL ?>/img/logo.png?v=2.0.7" alt="CardVault Logo" style="width:100%; height:100%; object-fit:contain; border-radius:inherit;"></div>
            <h1>Register</h1>
            <p class="subtitle">Create your CardVault Account</p>
        </div>

        <?php if (!empty($flash)): ?>
        <div class="alert alert-<?= $flash['type'] === 'error' ? 'error' : ($flash['type'] ?? 'info') ?>">
            <?= htmlspecialchars($flash['message'] ?? '') ?>
        </div>
        <?php endif; ?>

        <form method="POST" action="<?= APP_URL ?>/auth/register">
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="first_name">First Name</label>
                    <input type="text" id="first_name" name="first_name" class="form-input" placeholder="First Name" required autofocus autocomplete="given-name">
                </div>
                <div class="form-group">
                    <label class="form-label" for="last_name">Last Name</label>
                    <input type="text" id="last_name" name="last_name" class="form-input" placeholder="Last Name" required autocomplete="family-name">
                </div>
            </div>

            <div class="form-group">
                <label class="form-label" for="employee_id">Employee ID</label>
                <input type="text" id="employee_id" name="employee_id" class="form-input" placeholder="e.g. EMP12345" required autocomplete="username">
            </div>

            <div class="form-group">
                <label class="form-label" for="department_name">Department</label>
                <input type="text" id="department_name" name="department_name" class="form-input" placeholder="e.g. Finance, Sales, IT" required>
            </div>

            <div class="form-group">
                <label class="form-label" for="password">Password</label>
                <input type="password" id="password" name="password" class="form-input" placeholder="Min. 6 characters" minlength="6" required autocomplete="new-password">
            </div>

            <div class="form-group">
                <label class="form-label" for="confirm_password">Confirm Password</label>
                <input type="password" id="confirm_password" name="confirm_password" class="form-input" placeholder="Repeat your password" minlength="6" required autocomplete="new-password">
            </div>

            <div class="form-group" style="margin-top: 1rem; margin-bottom: 1.25rem;">
                <label class="form-label" style="font-weight: 600; color: var(--text-dark); margin-bottom: 0.5rem; display: block;">Default Card Privacy</label>
                <div class="privacy-options" style="display: flex; flex-direction: column; gap: 0.75rem;">
                    <label class="privacy-option-card" style="display: flex; align-items: flex-start; gap: 0.75rem; padding: 0.75rem; border: 1px solid var(--border-color); border-radius: 8px; cursor: pointer; background: var(--bg-card); transition: border-color 0.2s;">
                        <input type="radio" name="cards_visibility" value="private_user" checked style="margin-top: 0.2rem; accent-color: var(--accent);">
                        <div>
                            <span style="display: block; font-weight: 600; font-size: 0.9rem; color: var(--text-dark);">Private to Me</span>
                            <span style="display: block; font-size: 0.75rem; color: var(--text-muted);">Only you can see cards you upload</span>
                        </div>
                    </label>
                    <label class="privacy-option-card" style="display: flex; align-items: flex-start; gap: 0.75rem; padding: 0.75rem; border: 1px solid var(--border-color); border-radius: 8px; cursor: pointer; background: var(--bg-card); transition: border-color 0.2s;">
                        <input type="radio" name="cards_visibility" value="private_team" style="margin-top: 0.2rem; accent-color: var(--accent);">
                        <div>
                            <span style="display: block; font-weight: 600; font-size: 0.9rem; color: var(--text-dark);">Private to Team</span>
                            <span style="display: block; font-size: 0.75rem; color: var(--text-muted);">Only you, your team, and admins can see them</span>
                        </div>
                    </label>
                    <label class="privacy-option-card" style="display: flex; align-items: flex-start; gap: 0.75rem; padding: 0.75rem; border: 1px solid var(--border-color); border-radius: 8px; cursor: pointer; background: var(--bg-card); transition: border-color 0.2s;">
                        <input type="radio" name="cards_visibility" value="public" style="margin-top: 0.2rem; accent-color: var(--accent);">
                        <div>
                            <span style="display: block; font-weight: 600; font-size: 0.9rem; color: var(--text-dark);">Public</span>
                            <span style="display: block; font-size: 0.75rem; color: var(--text-muted);">All registered users can see them</span>
                        </div>
                    </label>
                </div>
            </div>

            <button type="submit" class="btn btn-primary btn-lg" style="width:100%; justify-content:center; margin-top:0.5rem;">
                Register
            </button>
        </form>

        <p style="text-align:center; margin-top:1.5rem; font-size:0.82rem; color:var(--text-muted);">
            Already have an account? <a href="<?= APP_URL ?>/login" style="color:var(--accent); font-weight:600; text-decoration:none;">Sign In</a>
        </p>
    </div>
</div>
</body>
</html>
