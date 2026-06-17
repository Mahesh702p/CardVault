<?php /** Edit Card */ ?>
<div style="max-width:700px; margin:0 auto;">
    <a href="<?= APP_URL ?>/cards/<?= $contact['id'] ?>" style="color:var(--text-muted); font-size:0.85rem;">← Back to Card</a>
    <h2 style="margin:1rem 0;">Edit Card: <?= htmlspecialchars($contact['name']) ?></h2>
    <form method="POST" action="<?= APP_URL ?>/cards/<?= $contact['id'] ?>/edit">
        <?= CSRF::field() ?>
        <div style="background:var(--bg-card); border:1px solid var(--border-color); border-radius:var(--radius-lg); padding:1.5rem; margin-bottom:1.25rem;">
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Full Name *</label>
                    <input type="text" name="person_name" class="form-input" value="<?= htmlspecialchars($contact['name']) ?>" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Designation</label>
                    <input type="text" name="designation" class="form-input" value="<?= htmlspecialchars($contact['designation'] ?? '') ?>">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Department</label>
                    <input type="text" name="department" class="form-input" value="<?= htmlspecialchars($contact['department_in_company'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">LinkedIn</label>
                    <input type="text" name="linkedin_url" class="form-input" value="<?= htmlspecialchars($contact['linkedin_url'] ?? '') ?>">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Phone (Primary)</label>
                    <input type="tel" name="phone_primary" class="form-input" value="<?= htmlspecialchars($contact['phone_primary'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Phone (Secondary)</label>
                    <input type="tel" name="phone_secondary" class="form-input" value="<?= htmlspecialchars($contact['phone_secondary'] ?? '') ?>">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Email (Primary)</label>
                    <input type="email" name="email_primary" class="form-input" value="<?= htmlspecialchars($contact['email_primary'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Email (Secondary)</label>
                    <input type="email" name="email_secondary" class="form-input" value="<?= htmlspecialchars($contact['email_secondary'] ?? '') ?>">
                </div>
            </div>
        </div>
        <div style="display:flex; gap:0.75rem; justify-content:flex-end;">
            <a href="<?= APP_URL ?>/cards/<?= $contact['id'] ?>" class="btn btn-secondary">Cancel</a>
            <button type="submit" class="btn btn-primary">💾 Save Changes</button>
        </div>
    </form>
</div>
