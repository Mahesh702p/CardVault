<?php /** Change Own Password */ ?>
<div style="max-width:500px; margin:0 auto;">
    <h2 style="margin-bottom:1.5rem;"><?= ($passwordIsTemp ?? false) ? 'Set My Password' : 'Change My Password' ?></h2>
    
    <?php if ($passwordIsTemp ?? false): ?>
        <p style="font-size:0.88rem; color:var(--text-muted); margin-bottom:1.5rem; line-height:1.5;">
            Since you logged in via SSO, you do not have a password set for direct login yet. Set a password below so you can log in directly using your Employee ID.
        </p>
    <?php endif; ?>

    <form method="POST" action="<?= APP_URL ?>/profile/password">
        <?= CSRF::field() ?>
        <div style="background:var(--bg-card); border:1px solid var(--border-color); border-radius:var(--radius-lg); padding:1.5rem;">
            <?php if (!($passwordIsTemp ?? false)): ?>
                <div class="form-group">
                    <label class="form-label">Current Password *</label>
                    <input type="password" name="old_password" class="form-input" required placeholder="Enter your current password">
                </div>
            <?php endif; ?>
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
            <a href="<?= APP_URL ?>/profile" class="btn btn-secondary">Cancel</a>
            <button type="submit" class="btn btn-primary"><?= ($passwordIsTemp ?? false) ? 'Set Password' : 'Update Password' ?></button>
        </div>
    </form>
</div>
