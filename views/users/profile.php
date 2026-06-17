<?php /** Profile Page — privacy settings, password change */ ?>

<div class="detail-grid" style="max-width:680px;">
    <div class="detail-info" style="grid-column:1/-1;">

        <!-- Profile Header -->
        <div style="display:flex; align-items:center; gap:1.25rem; margin-bottom:2rem; padding-bottom:1.5rem; border-bottom:1px solid var(--border-color);">
            <?php
            $nameParts = array_filter(explode(' ', $userRow['name'] ?? 'U'));
            $initials  = strtoupper(implode('', array_map(fn($w) => $w[0], $nameParts)));
            $initials  = substr($initials, 0, 2);
            ?>
            <div class="avatar" style="width:56px; height:56px; font-size:1.4rem; flex-shrink:0;"><?= htmlspecialchars($initials) ?></div>
            <div>
                <div style="font-size:1.2rem; font-weight:700; color:var(--text-primary);"><?= htmlspecialchars($userRow['name']) ?></div>
                <div style="font-size:0.85rem; color:var(--text-muted);"><?= htmlspecialchars($userRow['department_name'] ?? '') ?> · <?= htmlspecialchars(ucfirst($userRow['role'])) ?></div>
                <div style="font-size:0.8rem; color:var(--text-muted); margin-top:0.2rem;">Employee ID: <strong><?= htmlspecialchars($userRow['employee_id'] ?? '—') ?></strong></div>
            </div>
        </div>

        <!-- Privacy / Visibility Setting -->
        <div class="detail-section">
            <h3 style="display:flex; align-items:center; gap:0.5rem;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                Card Privacy
            </h3>
            <p style="font-size:0.85rem; color:var(--text-muted); margin-bottom:1.25rem; line-height:1.6;">
                Control who can see the cards you've uploaded.<br>
                <strong>Public</strong> — your cards appear in everyone's search results and card list.<br>
                <strong>Private</strong> — only you and admins can see your cards. Others won't find them.
            </p>

            <form method="POST" action="<?= APP_URL ?>/profile">
                <?= CSRF::field() ?>
                <div style="display:flex; flex-direction:column; gap:0.75rem; margin-bottom:1.25rem;">

                    <label style="display:flex; align-items:flex-start; gap:0.85rem; padding:1rem 1.1rem; border:2px solid <?= ($userRow['cards_visibility'] ?? 'public') === 'public' ? 'var(--accent)' : 'var(--border-color)' ?>; border-radius:10px; cursor:pointer; transition:border 0.2s;" id="label-public">
                        <input type="radio" name="cards_visibility" value="public"
                               <?= ($userRow['cards_visibility'] ?? 'public') === 'public' ? 'checked' : '' ?>
                               style="margin-top:2px; accent-color:var(--accent);" onchange="updateLabel()">
                        <div>
                            <div style="font-weight:600; margin-bottom:0.15rem; display:flex; align-items:center; gap:0.4rem;">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>
                                Public
                            </div>
                            <div style="font-size:0.8rem; color:var(--text-muted);">All users can see your cards</div>
                        </div>
                    </label>

                    <label style="display:flex; align-items:flex-start; gap:0.85rem; padding:1rem 1.1rem; border:2px solid <?= ($userRow['cards_visibility'] ?? 'public') === 'private' ? 'var(--accent)' : 'var(--border-color)' ?>; border-radius:10px; cursor:pointer; transition:border 0.2s;" id="label-private">
                        <input type="radio" name="cards_visibility" value="private"
                               <?= ($userRow['cards_visibility'] ?? 'public') === 'private' ? 'checked' : '' ?>
                               style="margin-top:2px; accent-color:var(--accent);" onchange="updateLabel()">
                        <div>
                            <div style="font-weight:600; margin-bottom:0.15rem; display:flex; align-items:center; gap:0.4rem;">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                                Private
                            </div>
                            <div style="font-size:0.8rem; color:var(--text-muted);">Only you and admins can see your cards</div>
                        </div>
                    </label>

                </div>
                <button type="submit" class="btn btn-primary">Save Privacy Setting</button>
            </form>
        </div>

        <!-- Change Password -->
        <div class="detail-section" style="margin-top:2rem;">
            <h3 style="display:flex; align-items:center; gap:0.5rem;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                Security
            </h3>
            <a href="<?= APP_URL ?>/profile/password" class="btn btn-secondary btn-sm">Change Password</a>
        </div>

    </div>
</div>

<script>
function updateLabel() {
    var pubInput  = document.querySelector('input[value="public"]');
    var privInput = document.querySelector('input[value="private"]');
    var pubLabel  = document.getElementById('label-public');
    var privLabel = document.getElementById('label-private');
    if (!pubInput || !privInput) return;
    pubLabel.style.borderColor  = pubInput.checked  ? 'var(--accent)' : 'var(--border-color)';
    privLabel.style.borderColor = privInput.checked ? 'var(--accent)' : 'var(--border-color)';
}
document.querySelectorAll('input[name="cards_visibility"]').forEach(function(r) {
    r.addEventListener('change', updateLabel);
});
</script>
