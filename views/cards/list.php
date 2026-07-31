<?php /** Card List View */
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

<div class="filter-bar">
    <div class="scope-tabs">
        <a href="<?= APP_URL ?>/cards?scope=all" class="scope-tab <?= ($scope ?? 'all') === 'all' ? 'active' : '' ?>">All Cards</a>
        <a href="<?= APP_URL ?>/cards?scope=mine" class="scope-tab <?= ($scope ?? '') === 'mine' ? 'active' : '' ?>">My Cards</a>
        <?php if (!empty($currentUser['team_id'])): ?>
            <a href="<?= APP_URL ?>/cards?scope=team" class="scope-tab <?= ($scope ?? '') === 'team' ? 'active' : '' ?>">My Team</a>
        <?php endif; ?>
    </div>

    <div style="display:flex; align-items:center; gap:0.75rem; margin-left:auto;">
        <!-- View Toggle -->
        <div class="view-toggle" id="viewToggle">
            <button class="view-toggle-btn active" id="btnGrid" title="Grid view" onclick="setView('grid')">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
            </button>
            <button class="view-toggle-btn" id="btnList" title="List view" onclick="setView('list')">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>
            </button>
        </div>
        <a href="<?= APP_URL ?>/cards/upload" class="btn btn-primary btn-sm">+ Add Card</a>
    </div>
</div>

<script>
function setView(mode) {
    var grid = document.getElementById('cardsGrid');
    var btnGrid = document.getElementById('btnGrid');
    var btnList = document.getElementById('btnList');
    if (!grid) return;
    if (mode === 'list') {
        grid.classList.add('list-view');
        if (btnGrid) btnGrid.classList.remove('active');
        if (btnList) btnList.classList.add('active');
    } else {
        grid.classList.remove('list-view');
        if (btnGrid) btnGrid.classList.add('active');
        if (btnList) btnList.classList.remove('active');
    }
    try { localStorage.setItem('cv_card_view', mode); } catch(e) {}
}

// Apply saved view once DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    var saved = localStorage.getItem('cv_card_view') || 'grid';
    setView(saved);
});
</script>


<?php if (empty($result['data'])): ?>
<div class="empty-state">
    <div class="empty-icon"><svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect></svg></div>
    <h3>No cards found</h3>
    <p>Start by scanning your first visiting card.</p>
    <a href="<?= APP_URL ?>/cards/upload" class="btn btn-primary">Scan Card</a>
</div>
<?php else: ?>
<div class="cards-grid" id="cardsGrid">
    <?php foreach ($result['data'] as $card): ?>
    <div class="contact-card" onclick="window.location.href='<?= APP_URL ?>/cards/<?= $card['id'] ?>'" style="cursor:pointer;">
        <?php
        $rawPhone   = $card['phone_primary'] ?? '';
        $cleanPhone = preg_replace('/[^0-9]/', '', $rawPhone);
        if (strlen($cleanPhone) === 10) $cleanPhone = '91' . $cleanPhone;
        $rawEmail   = $card['email_primary'] ?? '';
        ?>

        <div class="card-header">
            <?php if (!empty($card['card_front_image']) && $card['card_front_image'] !== 'NA'): ?>
            <div class="card-avatar card-avatar-img">
                <img src="<?= APP_URL ?>/<?= htmlspecialchars($card['card_front_image']) ?>" alt="<?= htmlspecialchars($card['name']) ?>" loading="lazy">
            </div>
            <?php else: ?>
            <div class="card-avatar"><?= getInitials($card['name']) ?></div>
            <?php endif; ?>
            <div>
                <div class="card-name"><?= htmlspecialchars($card['name']) ?></div>
                <div class="card-designation"><?= htmlspecialchars($card['designation'] ?? '') ?></div>
            </div>
        </div>
        <div class="card-company-wrapper" style="display:flex; align-items:center; gap:0.5rem; margin-bottom:0.5rem;">
            <div class="card-company" style="margin-bottom:0;"><?= htmlspecialchars($card['company_name'] ?? 'Unknown Company') ?></div>
            <?php if (!empty($card['rating_count']) && $card['rating_count'] > 0): ?>
            <span class="card-rating">
                <svg viewBox="0 0 24 24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                <?= round((float)$card['rating_avg'], 1) ?>
                <span class="rating-ct">(<?= $card['rating_count'] ?>)</span>
            </span>
            <?php endif; ?>
        </div>
        <div class="card-meta">
            <?php if (!empty($card['phone_primary'])): ?>
            <span class="meta-phone"><?= htmlspecialchars($card['phone_primary']) ?></span>
            <?php endif; ?>
            <?php if (!empty($card['company_city'])): ?>
            <span class="meta-city"><?= htmlspecialchars($card['company_city']) ?></span>
            <?php endif; ?>
            <?php if (!empty($card['industry'])): ?>
            <span class="meta-industry"><?= htmlspecialchars($card['industry']) ?></span>
            <?php endif; ?>
        </div>
        <?php if (!empty($card['products_services'])): ?>
        <div class="card-products">
            <?php foreach (array_slice(explode(', ', $card['products_services']), 0, 4) as $p): ?>
            <span class="badge"><?= htmlspecialchars($p) ?></span>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        <!-- Action menu: ⋮ toggle opens dropdown left on desktop, down on mobile -->
        <div class="card-actions">
            <button class="card-actions-toggle" onclick="toggleCardActions(event, this)" title="Actions">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor">
                    <circle cx="12" cy="5" r="2"/><circle cx="12" cy="12" r="2"/><circle cx="12" cy="19" r="2"/>
                </svg>
            </button>
            <div class="card-actions-dropdown">
                <?php if (!empty($cleanPhone)): ?>
                <a href="https://wa.me/<?= $cleanPhone ?>" target="_blank" class="card-action-btn card-action-wa" title="WhatsApp" onclick="event.stopPropagation();">
                    <svg width="15" height="15" fill="currentColor" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946C.06 5.348 5.397.01 12.008.01c3.202.001 6.212 1.246 8.477 3.514 2.266 2.268 3.507 5.28 3.505 8.484-.004 6.657-5.34 11.997-11.953 11.997-2.005-.001-3.973-.502-5.73-1.45L0 24zm6.59-4.846c1.6.95 3.188 1.449 4.825 1.451 5.436 0 9.86-4.37 9.864-9.799.002-2.63-1.023-5.101-2.885-6.965C16.588 1.977 14.128.953 11.997.953c-5.444 0-9.866 4.373-9.87 9.802-.001 1.77.466 3.498 1.354 5.021l-.995 3.634 3.738-.971zm11.367-7.233c-.3-.15-1.771-.875-2.028-.969-.258-.094-.446-.14-.633.14-.187.281-.726.969-.889 1.157-.163.188-.327.21-.627.06-1.554-.78-2.618-1.353-3.662-3.143-.275-.473-.273-.768-.125-.919.135-.136.3-.349.45-.524.15-.175.2-.3.3-.5.1-.2.05-.375-.025-.525-.075-.15-.633-1.528-.867-2.091-.228-.547-.46-.473-.633-.482-.163-.008-.35-.01-.537-.01-.187 0-.49.07-.747.35-.258.281-.983.961-.983 2.342 0 1.381 1.004 2.713 1.144 2.9.14.187 1.977 3.019 4.79 4.231.67.289 1.192.462 1.6.591.674.214 1.288.184 1.774.11.542-.082 1.771-.723 2.022-1.42.252-.697.252-1.295.176-1.42-.076-.125-.276-.201-.576-.351z"/></svg>
                </a>
                <?php endif; ?>
                <?php if (!empty($rawEmail)): ?>
                <a href="mailto:<?= htmlspecialchars($rawEmail) ?>" class="card-action-btn card-action-email" title="Send Email" onclick="event.stopPropagation();">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                </a>
                <?php endif; ?>
                <a href="<?= APP_URL ?>/cards/<?= $card['id'] ?>/vcard" class="card-action-btn card-action-vcf" title="Download vCard" onclick="event.stopPropagation();">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                </a>
            </div>
        </div>
    </div>

    <?php endforeach; ?>
</div>

<!-- Pagination -->
<?php if ($result['total_pages'] > 1): ?>
<div class="pagination">
    <?php foreach (Pagination::getPages($result['page'], $result['total_pages']) as $p): ?>
        <?php if ($p === '...'): ?>
            <span style="border:none; color:var(--text-muted); cursor:default; background:none;">...</span>
        <?php elseif ($p == $result['page']): ?>
            <span class="active"><?= $p ?></span>
        <?php else: ?>
            <a href="<?= APP_URL ?>/cards?scope=<?= $scope ?? 'all' ?>&page=<?= $p ?>"><?= $p ?></a>
        <?php endif; ?>
    <?php endforeach; ?>
</div>
<?php endif; ?>
<?php endif; ?>
