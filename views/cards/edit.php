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

        <!-- Card Privacy -->
        <?php
        $defaultVisibility = $contact['cards_visibility'] ?? 'public';
        ?>
        <div style="background:var(--bg-card); border:1px solid var(--border-color); border-radius:var(--radius-lg); padding:1.5rem; margin-bottom:1.25rem;">
            <h3 style="font-size:0.9rem; color:var(--accent); margin-bottom:1rem;">Card Privacy</h3>
            <div class="privacy-options" style="display: flex; flex-direction: column; gap: 0.75rem;">
                <label class="privacy-option-card" style="display: flex; align-items: flex-start; gap: 0.75rem; padding: 0.75rem; border: 1px solid var(--border-color); border-radius: 8px; cursor: pointer; background: var(--bg-card); transition: border-color 0.2s;">
                    <input type="radio" name="cards_visibility" value="private_user" <?= $defaultVisibility === 'private_user' ? 'checked' : '' ?> style="margin-top: 0.2rem; accent-color: var(--accent);">
                    <div>
                        <span style="display: block; font-weight: 600; font-size: 0.9rem; color: var(--text-dark);">Private to Me</span>
                        <span style="display: block; font-size: 0.75rem; color: var(--text-muted);">Only you can see this card</span>
                    </div>
                </label>
                <label class="privacy-option-card" style="display: flex; align-items: flex-start; gap: 0.75rem; padding: 0.75rem; border: 1px solid var(--border-color); border-radius: 8px; cursor: pointer; background: var(--bg-card); transition: border-color 0.2s;">
                    <input type="radio" name="cards_visibility" value="private_team" <?= $defaultVisibility === 'private_team' ? 'checked' : '' ?> style="margin-top: 0.2rem; accent-color: var(--accent);">
                    <div>
                        <span style="display: block; font-weight: 600; font-size: 0.9rem; color: var(--text-dark);">Private to Team</span>
                        <span style="display: block; font-size: 0.75rem; color: var(--text-muted);">Only you, your team, and admins can see this card</span>
                    </div>
                </label>
                <label class="privacy-option-card" style="display: flex; align-items: flex-start; gap: 0.75rem; padding: 0.75rem; border: 1px solid var(--border-color); border-radius: 8px; cursor: pointer; background: var(--bg-card); transition: border-color 0.2s;">
                    <input type="radio" name="cards_visibility" value="public" <?= $defaultVisibility === 'public' ? 'checked' : '' ?> style="margin-top: 0.2rem; accent-color: var(--accent);">
                    <div>
                        <span style="display: block; font-weight: 600; font-size: 0.9rem; color: var(--text-dark);">Public</span>
                        <span style="display: block; font-size: 0.75rem; color: var(--text-muted);">All registered users can see this card</span>
                    </div>
                </label>
            </div>
        </div>

        <div style="display:flex; gap:0.75rem; justify-content:flex-end;">
            <a href="<?= APP_URL ?>/cards/<?= $contact['id'] ?>" class="btn btn-secondary">Cancel</a>
            <button type="submit" class="btn btn-primary">Save Changes</button>
        </div>
    </form>
</div>
