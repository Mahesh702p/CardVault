<?php /** Dashboard */
if (!function_exists('getInitials')) {
    function getInitials($name) {
        $words = preg_split('/[\s,\-\.]+/', trim($name));
        $initials = '';
        foreach ($words as $w) {
            if (!empty($w)) {
                $initials .= strtoupper(substr($w, 0, 1));
            }
        }
        return substr($initials, 0, 2);
    }
}
?>
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg></div>
        <div class="stat-value"><?= $stats['total_cards'] ?></div>
        <div class="stat-label">Total Cards</div>
    </div>

    <div class="stat-card">
        <div class="stat-icon"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="4" y="2" width="16" height="20" rx="2" ry="2"></rect><path d="M9 22v-4h6v4"></path></svg></div>
        <div class="stat-value"><?= $stats['total_companies'] ?></div>
        <div class="stat-label">Companies</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg></div>
        <div class="stat-value"><?= $stats['my_cards'] ?></div>
        <div class="stat-label">My Cards</div>
    </div>
</div>

<div style="margin-top:1.25rem;">
    <!-- Recent Cards -->
    <div style="background:var(--bg-card); border:1px solid var(--border-color); border-radius:var(--radius-lg); padding:1.25rem;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem;">
            <h3 style="font-size:0.95rem;">Recent Cards</h3>
            <a href="<?= APP_URL ?>/cards" class="btn btn-secondary btn-sm">View All</a>
        </div>
        <?php if (empty($stats['recent_cards'])): ?>
            <div class="empty-state" style="padding:1.5rem;">
                <p style="font-size:0.8rem;">No cards yet. <a href="<?= APP_URL ?>/cards/upload">Scan your first card!</a></p>
            </div>
        <?php else: ?>
            <?php foreach ($stats['recent_cards'] as $card): ?>
            <a href="<?= APP_URL ?>/cards/<?= $card['id'] ?>" style="display:flex; align-items:center; gap:0.75rem; padding:0.5rem 0; border-bottom:1px solid var(--border-color); text-decoration:none; color:inherit;">
                <?php if (!empty($card['card_front_image']) && $card['card_front_image'] !== 'NA'): ?>
                <div style="width:32px;height:32px;border-radius:var(--radius-sm);overflow:hidden;flex-shrink:0;border:1px solid var(--border-color);background:var(--bg-card-hover);">
                    <img src="<?= APP_URL ?>/<?= htmlspecialchars($card['card_front_image']) ?>" alt="<?= htmlspecialchars($card['name']) ?>" style="width:100%; height:100%; object-fit:cover; display:block;">
                </div>
                <?php else: ?>
                <div style="width:32px;height:32px;border-radius:var(--radius-sm);background:linear-gradient(135deg,var(--accent),#c49a3c);display:flex;align-items:center;justify-content:center;font-weight:600;font-size:0.75rem;color:#fff;flex-shrink:0;">
                    <?= getInitials($card['name']) ?>
                </div>
                <?php endif; ?>
                <div style="flex:1;min-width:0;">
                    <div style="font-size:0.82rem;font-weight:500;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?= htmlspecialchars($card['name']) ?></div>
                    <div style="font-size:0.7rem;color:var(--text-muted);"><?= htmlspecialchars($card['company_name'] ?? '') ?></div>
                </div>
                <div style="font-size:0.68rem;color:var(--text-muted);"><?= date('M j', strtotime($card['created_at'])) ?></div>
            </a>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

</div>
