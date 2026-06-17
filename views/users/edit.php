<?php /** Edit User (Admin) */ ?>
<div style="max-width:600px; margin:0 auto;">
    <a href="<?= APP_URL ?>/users" style="color:var(--text-muted); font-size:0.85rem;">← Back to Users</a>
    <h2 style="margin:1rem 0;">Edit User</h2>

    <!-- ─── Main Edit Form ─────────────────────────────────────────────────── -->
    <form method="POST" action="<?= APP_URL ?>/users/<?= $editUser['id'] ?>/edit">
        <?= CSRF::field() ?>
        <div style="background:var(--bg-card); border:1px solid var(--border-color); border-radius:var(--radius-lg); padding:1.5rem; margin-bottom:1.5rem;">
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Full Name *</label>
                    <input type="text" name="name" class="form-input" value="<?= htmlspecialchars($editUser['name']) ?>" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Employee ID *</label>
                    <input type="text" name="employee_id" class="form-input" value="<?= htmlspecialchars($editUser['employee_id'] ?? '') ?>" required placeholder="e.g. EMP12345">
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Email <span style="color:var(--text-muted); font-weight:400;">(optional)</span></label>
                <input type="email" name="email" class="form-input" value="<?= htmlspecialchars($editUser['email'] ?? '') ?>" placeholder="user@company.com">
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Department *</label>
                    <input type="text" name="department_name" class="form-input" value="<?= htmlspecialchars($editUser['department_name'] ?? '') ?>" required placeholder="e.g. Sales, Marketing, IT">
                </div>
                <div class="form-group">
                    <label class="form-label">Role</label>
                    <select name="role" class="form-select">
                        <option value="user"  <?= $editUser['role'] === 'user'  ? 'selected' : '' ?>>User</option>
                        <option value="admin" <?= $editUser['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                    </select>
                </div>
            </div>
        </div>
        <div style="display:flex; gap:0.75rem; justify-content:flex-end;">
            <a href="<?= APP_URL ?>/users" class="btn btn-secondary">Cancel</a>
            <button type="submit" class="btn btn-primary">Save Changes</button>
        </div>
    </form>

    <!-- ─── Reset Password Section ────────────────────────────────────────── -->
    <div style="background:var(--bg-card); border:1px solid var(--border-color); border-radius:var(--radius-lg); padding:1.5rem; margin-top:1.5rem;">
        <h3 style="margin-bottom:0.35rem; font-size:1rem;">🔑 Reset Password</h3>
        <p style="color:var(--text-muted); font-size:0.83rem; margin-bottom:1rem;">
            Set a new temporary password for <strong><?= htmlspecialchars($editUser['name']) ?></strong>.
            Share it with the user and ask them to change it after logging in.
        </p>
        <form method="POST" action="<?= APP_URL ?>/users/<?= $editUser['id'] ?>/reset-password" id="reset-password-form">
            <?= CSRF::field() ?>
            <div style="display:flex; gap:0.75rem; align-items:flex-end; flex-wrap:wrap;">
                <div class="form-group" style="flex:1; min-width:180px; margin:0;">
                    <label class="form-label">New Password <span style="color:var(--text-muted); font-weight:400;">(min. 6 chars)</span></label>
                    <div style="position:relative;">
                        <input type="text" id="reset_password_input" name="new_password" class="form-input" minlength="6" required placeholder="Enter new password" style="padding-right:2.5rem;">
                        <button type="button" onclick="generatePassword()" title="Generate a random password"
                            style="position:absolute; right:0.5rem; top:50%; transform:translateY(-50%); background:none; border:none; cursor:pointer; color:var(--accent); font-size:1rem; padding:0;">🎲</button>
                    </div>
                </div>
                <button type="submit" class="btn btn-secondary" onclick="return confirm('Reset password for <?= htmlspecialchars($editUser['name']) ?>?')">
                    Reset Password
                </button>
            </div>
        </form>
    </div>

    <!-- ─── Danger Zone ───────────────────────────────────────────────────── -->
    <?php if ($editUser['id'] !== $currentUser['id']): ?>
    <div style="background:var(--bg-card); border:1px solid #fecaca; border-radius:var(--radius-lg); padding:1.5rem; margin-top:1.5rem;">
        <h3 style="margin-bottom:0.25rem; font-size:1rem; color:#dc2626;">Danger Zone</h3>
        <p style="color:var(--text-muted); font-size:0.85rem; margin-bottom:1rem;">
            Deactivating a user prevents them from logging in. Their cards remain in the database.
        </p>
        <form method="POST" action="<?= APP_URL ?>/users/<?= $editUser['id'] ?>/deactivate">
            <?= CSRF::field() ?>
            <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Deactivate <?= htmlspecialchars($editUser['name']) ?>? They will not be able to login.')">
                Deactivate User
            </button>
        </form>
    </div>
    <?php endif; ?>
</div>

<script>
function generatePassword() {
    const chars = 'ABCDEFGHJKMNPQRSTUVWXYZabcdefghjkmnpqrstuvwxyz23456789@#$!';
    let pass = '';
    for (let i = 0; i < 10; i++) {
        pass += chars.charAt(Math.floor(Math.random() * chars.length));
    }
    document.getElementById('reset_password_input').value = pass;
    document.getElementById('reset_password_input').type = 'text';
}
</script>
