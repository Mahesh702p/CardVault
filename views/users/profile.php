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
            <div class="avatar" style="width:56px; height:56px; font-size:1.4rem; flex-shrink:0; border-radius:50%; display:flex; align-items:center; justify-content:center; font-weight:700; background:linear-gradient(135deg, var(--accent), #c49a3c); color:#fff; line-height:1;"><?= htmlspecialchars($initials) ?></div>
            <div>
                <div style="font-size:1.2rem; font-weight:700; color:var(--text-primary);"><?= htmlspecialchars($userRow['name']) ?></div>
                <div style="font-size:0.85rem; color:var(--text-muted);"><?= htmlspecialchars($userRow['department_name'] ?? '') ?> · <?= htmlspecialchars(ucfirst($userRow['role'])) ?></div>
                <div style="font-size:0.8rem; color:var(--text-muted); margin-top:0.2rem;">Employee ID: <strong><?= htmlspecialchars($userRow['employee_id'] ?? '—') ?></strong></div>
            </div>
        </div>
        
        <!-- Profile Details -->
        <div class="detail-section">
            <h3 style="display:flex; align-items:center; gap:0.5rem; margin-bottom:1rem;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                Employee Profile Details
            </h3>
            <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(200px, 1fr)); gap:1rem; background:var(--bg-card); padding:1rem; border-radius:var(--radius-md); border:1px solid var(--border-color);">
                <div>
                    <div style="font-size:0.75rem; color:var(--text-muted);">Designation</div>
                    <div style="font-size:0.9rem; font-weight:600; color:var(--text-primary);"><?= htmlspecialchars($userRow['designation'] ?? '—') ?></div>
                </div>
                <div>
                    <div style="font-size:0.75rem; color:var(--text-muted);">Work Location</div>
                    <div style="font-size:0.9rem; font-weight:600; color:var(--text-primary);"><?= htmlspecialchars($userRow['work_location'] ?? '—') ?></div>
                </div>
                <div>
                    <div style="font-size:0.75rem; color:var(--text-muted);">Email Address</div>
                    <div style="font-size:0.9rem; font-weight:600; color:var(--text-primary);"><?= htmlspecialchars($userRow['email'] ?? '—') ?></div>
                </div>
                <div>
                    <div style="font-size:0.75rem; color:var(--text-muted);">Mobile Number</div>
                    <div style="font-size:0.9rem; font-weight:600; color:var(--text-primary);"><?= htmlspecialchars($userRow['mobile'] ?? '—') ?></div>
                </div>
            </div>
        </div>

        <!-- Change Password -->
        <div class="detail-section" style="margin-top:2rem;">
            <h3 style="display:flex; align-items:center; gap:0.5rem;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                Security
            </h3>
            <a href="<?= APP_URL ?>/profile/password" class="btn btn-secondary btn-sm" style="margin-top:0.75rem; display:inline-flex;">Change Password</a>
        </div>

    </div>
</div>
