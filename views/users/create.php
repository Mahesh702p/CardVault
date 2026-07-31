<?php /** Create User (Admin) */ ?>
<div style="max-width:600px; margin:0 auto;">
    <a href="<?= htmlspecialchars($_SESSION['last_users_list_url'] ?? (APP_URL . '/users')) ?>" style="color:var(--text-muted); font-size:0.85rem;" onclick="if(document.referrer && document.referrer.includes('/users') && !document.referrer.includes('/create') && !document.referrer.includes('/edit')){ window.history.back(); return false; }">← Back to Users</a>
    <h2 style="margin:1rem 0;">Add New User</h2>
    <form method="POST" action="<?= APP_URL ?>/users/create">
        <?= CSRF::field() ?>
        <div style="background:var(--bg-card); border:1px solid var(--border-color); border-radius:var(--radius-lg); padding:1.5rem;">
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Full Name *</label>
                    <input type="text" name="name" class="form-input" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Employee ID *</label>
                    <input type="text" name="employee_id" class="form-input" required placeholder="e.g. EMP12345">
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Initial Password *</label>
                <input type="password" name="password" class="form-input" required minlength="6" placeholder="Min. 6 characters">
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Email Address</label>
                    <input type="email" name="email" class="form-input" placeholder="e.g. name@company.com">
                </div>
                <div class="form-group">
                    <label class="form-label">Mobile Number</label>
                    <input type="text" name="mobile" class="form-input" placeholder="e.g. 9876543210">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Designation</label>
                    <input type="text" name="designation" class="form-input" placeholder="e.g. Software Engineer">
                </div>
                <div class="form-group">
                    <label class="form-label">Work Location</label>
                    <input type="text" name="work_location" class="form-input" placeholder="e.g. Powai">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Department *</label>
                    <input type="text" name="department_name" class="form-input" required placeholder="e.g. Sales, Marketing, IT">
                </div>
                <div class="form-group">
                    <label class="form-label">Role</label>
                    <select name="role" class="form-select">
                        <option value="user">User</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>
            </div>
        </div>
        <div style="display:flex; gap:0.75rem; justify-content:flex-end; margin-top:1.25rem;">
            <a href="<?= htmlspecialchars($_SESSION['last_users_list_url'] ?? (APP_URL . '/users')) ?>" class="btn btn-secondary">Cancel</a>
            <button type="submit" class="btn btn-primary">Create User</button>
        </div>
    </form>
</div>
