<?php /** Change Own Password */ ?>
<div style="max-width:500px; margin:0 auto;">
    <h2 style="margin-bottom:1.5rem;">Change My Password</h2>
    <form method="POST" action="<?= APP_URL ?>/profile/password">
        <?= CSRF::field() ?>
        <div style="background:var(--bg-card); border:1px solid var(--border-color); border-radius:var(--radius-lg); padding:1.5rem;">
            <div class="form-group">
                <label class="form-label">Current Password *</label>
                <input type="password" name="old_password" class="form-input" required placeholder="Enter your current password">
            </div>
            <div class="form-group">
                <label class="form-label">New Password *</label>
                <input type="password" name="new_password" class="form-input" required minlength="6" placeholder="At least 6 characters">
            </div>
            <div class="form-group">
                <label class="form-label">Confirm New Password *</label>
                <input type="password" name="confirm_password" class="form-input" required minlength="6" placeholder="Repeat new password">
            </div>
        </div>
        <div style="display:flex; gap:0.75rem; justify-content:flex-end; margin-top:1.25rem;">
            <a href="<?= APP_URL ?>/dashboard" class="btn btn-secondary">Cancel</a>
            <button type="submit" class="btn btn-primary">Update Password</button>
        </div>
    </form>
</div>
