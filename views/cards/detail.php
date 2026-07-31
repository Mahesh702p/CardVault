<?php /** Card Detail View */
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

<div style="margin-bottom:1.25rem;">
    <a href="<?= htmlspecialchars($_SESSION['last_cards_list_url'] ?? (APP_URL . '/cards')) ?>" style="color:var(--text-muted); font-size:0.85rem;" onclick="if(document.referrer && (document.referrer.includes('/cards') || document.referrer.includes('/search')) && !document.referrer.includes('/edit') && !document.referrer.includes('/delete') && !document.referrer.includes('/upload')){ window.history.back(); return false; }">← Back to Cards</a>
</div>

<div class="detail-grid">
    <!-- Card Images -->
    <div class="card-images">
        <?php 
        $hasFront = !empty($contact['card_front_image']) && $contact['card_front_image'] !== 'NA';
        $hasBack = !empty($contact['card_back_image']) && $contact['card_back_image'] !== 'NA';
        ?>
        <?php if ($hasFront): ?>
        <div class="card-image-wrapper">
            <div class="label">Front Side</div>
            <img src="<?= APP_URL ?>/<?= htmlspecialchars($contact['card_front_image']) ?>" alt="Card Front">
        </div>
        <?php endif; ?>
        <?php if ($hasBack): ?>
        <div class="card-image-wrapper">
            <div class="label">Back Side</div>
            <img src="<?= APP_URL ?>/<?= htmlspecialchars($contact['card_back_image']) ?>" alt="Card Back">
        </div>
        <?php endif; ?>
        <?php if (!$hasFront && !$hasBack): ?>
        <!-- Render a beautiful stylized mockup visiting card -->
        <div class="mock-visiting-card">
            <div class="mock-card-logo">
                <div class="mock-logo-circle"><?= getInitials($contact['name']) ?></div>
            </div>
            <div class="mock-card-details">
                <div class="mock-name"><?= htmlspecialchars($contact['name']) ?></div>
                <div class="mock-designation"><?= htmlspecialchars($contact['designation'] ?? '') ?></div>
                <div class="mock-divider"></div>
                <div class="mock-company"><?= htmlspecialchars($contact['company_name'] ?? '') ?></div>
                <div class="mock-meta-row">
                    <?php if (!empty($contact['phone_primary'])): ?>
                    <span>📞 <?= htmlspecialchars($contact['phone_primary']) ?></span>
                    <?php endif; ?>
                    <?php if (!empty($contact['email_primary'])): ?>
                    <span>✉️ <?= htmlspecialchars($contact['email_primary']) ?></span>
                    <?php endif; ?>                </div>
            </div>
        </div>
        <?php endif; ?>

        <?php 
        $canEdit = ($currentUser['role'] === 'admin' || $contact['added_by_user_id'] === $currentUser['id']);
        $isAdmin = ($currentUser['role'] === 'admin');

        // Phone normalization for WhatsApp Link
        $rawPhone = $contact['phone_primary'] ?? '';
        $cleanPhone = preg_replace('/[^0-9]/', '', $rawPhone);
        if (strlen($cleanPhone) === 10) {
            $cleanPhone = '91' . $cleanPhone;
        }
        $whatsappUrl = !empty($cleanPhone) ? "https://wa.me/{$cleanPhone}" : "#";
        $emailUrl = !empty($contact['email_primary']) ? "mailto:" . htmlspecialchars($contact['email_primary']) : "#";
        $vcardUrl = APP_URL . "/cards/" . $contact['id'] . "/vcard";
        ?>

        <!-- Sleek Quick Action Panel at the top of detail actions -->
        <div style="display:flex; gap:0.75rem; align-items:center; margin-top:1rem; margin-bottom:1rem; padding-bottom:1rem; border-bottom:1px solid var(--border-color); width: 100%;">
            <?php if (!empty($contact['phone_primary'])): ?>
            <a href="<?= $whatsappUrl ?>" target="_blank" class="quick-action-btn wa-btn" title="Message via WhatsApp">
                <svg width="20" height="20" fill="currentColor" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946C.06 5.348 5.397.01 12.008.01c3.202.001 6.212 1.246 8.477 3.514 2.266 2.268 3.507 5.28 3.505 8.484-.004 6.657-5.34 11.997-11.953 11.997-2.005-.001-3.973-.502-5.73-1.45L0 24zm6.59-4.846c1.6.95 3.188 1.449 4.825 1.451 5.436 0 9.86-4.37 9.864-9.799.002-2.63-1.023-5.101-2.885-6.965C16.588 1.977 14.128.953 11.997.953c-5.444 0-9.866 4.373-9.87 9.802-.001 1.77.466 3.498 1.354 5.021l-.995 3.634 3.738-.971zm11.367-7.233c-.3-.15-1.771-.875-2.028-.969-.258-.094-.446-.14-.633.14-.187.281-.726.969-.889 1.157-.163.188-.327.21-.627.06-1.554-.78-2.618-1.353-3.662-3.143-.275-.473-.273-.768-.125-.919.135-.136.3-.349.45-.524.15-.175.2-.3.3-.5.1-.2.05-.375-.025-.525-.075-.15-.633-1.528-.867-2.091-.228-.547-.46-.473-.633-.482-.163-.008-.35-.01-.537-.01-.187 0-.49.07-.747.35-.258.281-.983.961-.983 2.342 0 1.381 1.004 2.713 1.144 2.9.14.187 1.977 3.019 4.79 4.231.67.289 1.192.462 1.6.591.674.214 1.288.184 1.774.11.542-.082 1.771-.723 2.022-1.42.252-.697.252-1.295.176-1.42-.076-.125-.276-.201-.576-.351z"/></svg>
            </a>
            <?php endif; ?>

            <?php if (!empty($contact['email_primary'])): ?>
            <a href="<?= $emailUrl ?>" class="quick-action-btn email-btn" title="Draft Email">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
            </a>
            <?php endif; ?>

        <?php
        // Build the share text
        $shareLines = [];
        $shareLines[] = '👤 ' . ($contact['name'] ?? '');
        if (!empty($contact['designation']))     $shareLines[] = '💼 ' . $contact['designation'];
        if (!empty($contact['company_name']))    $shareLines[] = '🏢 ' . $contact['company_name'];
        if (!empty($contact['phone_primary']))   $shareLines[] = '📞 ' . $contact['phone_primary'];
        if (!empty($contact['phone_secondary'])) $shareLines[] = '📞 ' . $contact['phone_secondary'] . ' (Alt)';
        if (!empty($contact['email_primary']))   $shareLines[] = '✉️ '  . $contact['email_primary'];
        if (!empty($contact['email_secondary'])) $shareLines[] = '✉️ '  . $contact['email_secondary'] . ' (Alt)';
        if (!empty($contact['website']))         $shareLines[] = '🌐 ' . $contact['website'];
        $shareText = implode("\n", $shareLines);
        $shareUrl  = APP_URL . '/cards/' . $contact['id'];
        $shareTitle = ($contact['name'] ?? 'Contact') . ' — CardVault';
        ?>

            <a href="<?= $vcardUrl ?>" class="quick-action-btn vcf-btn" title="Export as vCard (VCF)">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="8.5" cy="7" r="4"/><polyline points="17 11 19 13 23 9"/></svg>
            </a>

            <!-- Share Button -->
            <button type="button" class="quick-action-btn share-btn" id="shareContactBtn" title="Share this contact"
                data-share-title="<?= htmlspecialchars($shareTitle) ?>"
                data-share-text="<?= htmlspecialchars($shareText) ?>"
                data-share-url="<?= htmlspecialchars($shareUrl) ?>">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/>
                    <line x1="8.59" y1="13.51" x2="15.42" y2="17.49"/>
                    <line x1="15.41" y1="6.51" x2="8.59" y2="10.49"/>
                </svg>
            </button>

            <!-- Copy-to-clipboard toast -->
            <div id="shareToast" style="display:none; position:fixed; bottom:2rem; left:50%; transform:translateX(-50%); background:var(--accent); color:#fff; padding:0.65rem 1.25rem; border-radius:8px; font-size:0.9rem; font-weight:600; z-index:9999; box-shadow:0 4px 16px rgba(0,0,0,0.4); pointer-events:none;">
                ✅ Contact info copied to clipboard!
            </div>
        </div>

        <div style="display:flex; gap:0.5rem; flex-wrap:wrap; width: 100%; align-items:center;">
            <?php if ($canEdit): ?>
            <a href="<?= APP_URL ?>/cards/<?= $contact['id'] ?>/edit" class="btn btn-secondary btn-sm">Edit</a>
            <form method="POST" action="<?= APP_URL ?>/cards/<?= $contact['id'] ?>/delete" style="display:inline;" onsubmit="return confirm('Delete this card?');">
                <?= CSRF::field() ?>
                <button type="submit" class="btn btn-danger btn-sm">Delete</button>
            </form>
            <?php endif; ?>
        </div>
    </div>

    <!-- Details -->
    <div class="detail-info">
        <div class="detail-section">
            <h3>Person</h3>
            <div class="detail-row"><div class="detail-label">Name</div><div class="detail-value"><?= htmlspecialchars($contact['name']) ?></div></div>
            <div class="detail-row"><div class="detail-label">Designation</div><div class="detail-value"><?= htmlspecialchars($contact['designation'] ?? '—') ?></div></div>
            <div class="detail-row"><div class="detail-label">Department</div><div class="detail-value"><?= htmlspecialchars($contact['department_in_company'] ?? '—') ?></div></div>
            <?php if (!empty($contact['linkedin_url'])): ?>
            <div class="detail-row"><div class="detail-label">LinkedIn</div><div class="detail-value"><a href="<?= htmlspecialchars($contact['linkedin_url']) ?>" target="_blank"><?= htmlspecialchars($contact['linkedin_url']) ?></a></div></div>
            <?php endif; ?>
        </div>

        <!-- Rating Widget -->
        <div class="detail-section">
            <h3>Rating</h3>
            <div class="rating-widget" id="ratingWidget" data-contact-id="<?= $contact['id'] ?>">
                <div class="rating-stars" id="ratingStars">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                    <button class="star-btn <?= ($userRating && $i <= $userRating) ? 'active' : '' ?>"
                            data-value="<?= $i ?>" title="Rate <?= $i ?> star<?= $i > 1 ? 's' : '' ?>">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="<?= ($userRating && $i <= $userRating) ? 'currentColor' : 'none' ?>" stroke="currentColor" stroke-width="1.5">
                            <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                        </svg>
                    </button>
                    <?php endfor; ?>
                    <?php if ($userRating): ?>
                    <button class="star-clear-btn" id="clearRating" title="Remove your rating">✕</button>
                    <?php endif; ?>
                </div>
                <div class="rating-summary" id="ratingSummary">
                    <?php if ($ratingCount > 0): ?>
                        <span class="rating-avg"><?= $ratingAvg ?></span>
                        <span class="rating-count">(<?= $ratingCount ?> rating<?= $ratingCount !== 1 ? 's' : '' ?>)</span>
                    <?php else: ?>
                        <span class="rating-none">Not yet rated</span>
                    <?php endif; ?>
                    <?php if ($userRating): ?>
                        <span class="rating-yours">Your rating: <?= $userRating ?>/5</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="detail-section">
            <h3>Contact</h3>
            <div class="detail-row"><div class="detail-label">Phone</div><div class="detail-value"><?= htmlspecialchars($contact['phone_primary'] ?? '—') ?></div></div>
            <?php if (!empty($contact['phone_secondary'])): ?>
            <div class="detail-row"><div class="detail-label">Phone (Alt)</div><div class="detail-value"><?= htmlspecialchars($contact['phone_secondary']) ?></div></div>
            <?php endif; ?>
            <div class="detail-row"><div class="detail-label">Email</div><div class="detail-value"><?= htmlspecialchars($contact['email_primary'] ?? '—') ?></div></div>
            <?php if (!empty($contact['email_secondary'])): ?>
            <div class="detail-row"><div class="detail-label">Email (Alt)</div><div class="detail-value"><?= htmlspecialchars($contact['email_secondary']) ?></div></div>
            <?php endif; ?>
        </div>

        <div class="detail-section">
            <h3>Company</h3>
            <div class="detail-row"><div class="detail-label">Company</div><div class="detail-value" style="font-weight:600;color:var(--accent);"><?= htmlspecialchars($contact['company_name'] ?? '—') ?></div></div>
            <div class="detail-row"><div class="detail-label">Industry</div><div class="detail-value"><?= htmlspecialchars($contact['industry'] ?? '—') ?></div></div>
            <?php if (!empty($contact['website'])): ?>
            <div class="detail-row"><div class="detail-label">Website</div><div class="detail-value"><a href="<?= htmlspecialchars($contact['website']) ?>" target="_blank"><?= htmlspecialchars($contact['website']) ?></a></div></div>
            <?php endif; ?>
            <div class="detail-row"><div class="detail-label">Address</div><div class="detail-value"><?= htmlspecialchars($contact['company_address'] ?? '—') ?>, <?= htmlspecialchars($contact['company_city'] ?? '') ?></div></div>
            <?php if (!empty($contact['gst_number'])): ?>
            <div class="detail-row"><div class="detail-label">GST No.</div><div class="detail-value"><?= htmlspecialchars($contact['gst_number']) ?></div></div>
            <?php endif; ?>
        </div>

        <?php if (!empty($products)): ?>
        <div class="detail-section">
            <h3>Products & Services</h3>
            <div style="display:flex; flex-wrap:wrap; gap:0.5rem;">
                <?php foreach ($products as $p): ?>
                <span class="badge"><?= htmlspecialchars($p['name']) ?></span>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <?php if (!empty($contact['tags'])): ?>
        <div class="detail-section">
            <h3>Tags</h3>
            <div style="display:flex; flex-wrap:wrap; gap:0.5rem;">
                <?php foreach (explode(', ', $contact['tags']) as $tag): ?>
                <span class="badge badge-info"><?= htmlspecialchars($tag) ?></span>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <div class="detail-section" style="margin-bottom:0;">
            <h3>Metadata</h3>
            <?php if ($isAdmin): ?>
            <div class="detail-row"><div class="detail-label">Added By</div><div class="detail-value"><?= htmlspecialchars($contact['added_by_name'] ?? '—') ?> (<?= htmlspecialchars($contact['added_by_dept_name'] ?? '') ?>)</div></div>
            <?php endif; ?>
            <div class="detail-row"><div class="detail-label">AI Confidence</div><div class="detail-value"><?= $contact['ai_confidence_score'] ? round($contact['ai_confidence_score'] * 100) . '%' : '—' ?></div></div>
            <div class="detail-row"><div class="detail-label">Added On</div><div class="detail-value"><?= date('M j, Y g:i A', strtotime($contact['created_at'])) ?></div></div>
        </div>
    </div>
<!-- Lightbox Modal for Zooming -->
<div id="lightbox" class="lightbox-modal" onclick="closeLightbox()">
    <span class="lightbox-close">&times;</span>
    <img class="lightbox-content" id="lightboxImg" onclick="event.stopPropagation()">
</div>

<script>
(function() {
    var widget = document.getElementById('ratingWidget');
    if (!widget) return;

    var contactId = widget.dataset.contactId;
    var stars = widget.querySelectorAll('.star-btn');
    var summaryEl = document.getElementById('ratingSummary');
    var APP_URL = <?= json_encode(APP_URL) ?>;

    function updateStars(value, filled) {
        stars.forEach(function(s) {
            var v = parseInt(s.dataset.value);
            var svg = s.querySelector('svg');
            if (v <= value) {
                s.classList.add(filled ? 'active' : 'hover');
                svg.setAttribute('fill', 'currentColor');
            } else {
                s.classList.remove('active', 'hover');
                svg.setAttribute('fill', 'none');
            }
        });
    }

    function resetToActive() {
        stars.forEach(function(s) {
            s.classList.remove('hover');
            var svg = s.querySelector('svg');
            svg.setAttribute('fill', s.classList.contains('active') ? 'currentColor' : 'none');
        });
    }

    function submitRating(value) {
        fetch(APP_URL + '/cards/' + contactId + '/rate', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ rating: value })
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.success) {
                updateStars(value, true);
                stars.forEach(function(s) {
                    var v = parseInt(s.dataset.value);
                    if (v <= value) s.classList.add('active');
                    else s.classList.remove('active');
                });

                var html = '';
                if (data.rating_count > 0) {
                    html += '<span class="rating-avg">' + data.rating_avg + '</span>';
                    html += '<span class="rating-count">(' + data.rating_count + ' rating' + (data.rating_count !== 1 ? 's' : '') + ')</span>';
                } else {
                    html += '<span class="rating-none">Not yet rated</span>';
                }
                if (value > 0) {
                    html += '<span class="rating-yours">Your rating: ' + value + '/5</span>';
                    if (!document.getElementById('clearRating')) {
                        var clearBtn = document.createElement('button');
                        clearBtn.className = 'star-clear-btn';
                        clearBtn.id = 'clearRating';
                        clearBtn.title = 'Remove your rating';
                        clearBtn.textContent = '\u2715';
                        clearBtn.onclick = function(e) { e.preventDefault(); submitRating(0); };
                        document.getElementById('ratingStars').appendChild(clearBtn);
                    }
                } else {
                    var cb = document.getElementById('clearRating');
                    if (cb) cb.remove();
                    stars.forEach(function(s) { s.classList.remove('active'); s.querySelector('svg').setAttribute('fill', 'none'); });
                }
                summaryEl.innerHTML = html;
            }
        });
    }

    stars.forEach(function(star) {
        star.addEventListener('mouseenter', function() {
            updateStars(parseInt(this.dataset.value), false);
        });
        star.addEventListener('mouseleave', resetToActive);
        star.addEventListener('click', function(e) {
            e.preventDefault();
            submitRating(parseInt(this.dataset.value));
        });
    });

    var clearBtn = document.getElementById('clearRating');
    if (clearBtn) {
        clearBtn.addEventListener('click', function(e) {
            e.preventDefault();
            submitRating(0);
        });
    }

    // Auto-detect image orientation and assign layout classes
    document.querySelectorAll('.card-image-wrapper img').forEach(function(img) {
        function checkOrientation() {
            var wrapper = img.closest('.card-image-wrapper');
            if (!wrapper) return;
            if (img.naturalHeight > img.naturalWidth) {
                wrapper.classList.add('is-vertical');
            } else {
                wrapper.classList.add('is-horizontal');
            }
        }
        if (img.complete) {
            checkOrientation();
        } else {
            img.addEventListener('load', checkOrientation);
        }
    });

    // Lightbox Modal functions
    window.openLightbox = function(src) {
        var modal = document.getElementById('lightbox');
        var modalImg = document.getElementById('lightboxImg');
        if (!modal || !modalImg) return;
        modalImg.src = src;
        modal.classList.add('open');
    };
    window.closeLightbox = function() {
        var modal = document.getElementById('lightbox');
        if (modal) modal.classList.remove('open');
    };

    // Attach click events to card images to open Lightbox
    document.querySelectorAll('.card-image-wrapper img').forEach(function(img) {
        img.style.cursor = 'zoom-in';
        img.addEventListener('click', function() {
            window.openLightbox(img.src);
        });
    });
})();

// ── Share Contact Button ─────────────────────────────────────────────────────
(function() {
    var btn   = document.getElementById('shareContactBtn');
    var toast = document.getElementById('shareToast');
    if (!btn) return;

    var title = btn.dataset.shareTitle;
    var text  = btn.dataset.shareText;
    var url   = btn.dataset.shareUrl;

    function showToast(msg) {
        if (!toast) return;
        toast.textContent = msg;
        toast.style.display = 'block';
        toast.style.opacity = '1';
        setTimeout(function() {
            toast.style.transition = 'opacity 0.4s';
            toast.style.opacity    = '0';
            setTimeout(function() {
                toast.style.display = 'none';
                toast.style.transition = '';
                toast.style.opacity    = '1';
            }, 400);
        }, 2500);
    }

    btn.addEventListener('click', function() {
        // Full share payload: text + URL on new line
        var fullText = text + '\n\n🔗 ' + url;

        // Use native Web Share API if available (mobile / modern browsers)
        if (navigator.share) {
            navigator.share({ title: title, text: text, url: url })
                .catch(function() { /* user cancelled — do nothing */ });
            return;
        }

        // Fallback: copy to clipboard
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(fullText).then(function() {
                showToast('✅ Contact info copied to clipboard!');
                btn.classList.add('copied');
                setTimeout(function() { btn.classList.remove('copied'); }, 2000);
            }).catch(function() {
                showToast('❌ Could not copy. Please copy manually.');
            });
        } else {
            // Very old browser fallback
            var ta = document.createElement('textarea');
            ta.value = fullText;
            ta.style.position = 'fixed';
            ta.style.opacity  = '0';
            document.body.appendChild(ta);
            ta.select();
            try { document.execCommand('copy'); showToast('✅ Contact info copied!'); }
            catch(e) { showToast('❌ Copy not supported in this browser.'); }
            document.body.removeChild(ta);
        }
    });
})();
</script>
