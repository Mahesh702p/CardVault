<?php
/** Export My Data Page */
// Generate one-time VCF download tokens stored in DB (iOS download manager has no session cookie)
$myVcfToken   = bin2hex(random_bytes(16));
$teamVcfToken = bin2hex(random_bytes(16));
$_vcfDb = Database::getConnection();
// Auto-create table if missing
$_vcfDb->exec("CREATE TABLE IF NOT EXISTS `vcf_download_tokens` (
    `token`      VARCHAR(64)  NOT NULL,
    `user_id`    INT UNSIGNED NOT NULL,
    `type`       VARCHAR(16)  NOT NULL,
    `expires_at` INT UNSIGNED NOT NULL,
    PRIMARY KEY (`token`),
    KEY `expires_at` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
// Purge expired tokens
$_vcfDb->exec("DELETE FROM vcf_download_tokens WHERE expires_at < " . time());
// Insert new tokens
$_vcfStmt = $_vcfDb->prepare("INSERT INTO vcf_download_tokens (token, user_id, type, expires_at) VALUES (?,?,?,?)");
$_vcfStmt->execute([$myVcfToken,   $currentUser['id'], 'my',   time() + 300]);
$_vcfStmt->execute([$teamVcfToken, $currentUser['id'], 'team', time() + 300]);
?>

<style>
/* ── Export Page ────────────────────────────────────── */
.exp-page { max-width: 560px; margin: 0 auto; }

.exp-header {
    display: flex; align-items: center; gap: 1rem; margin-bottom: 2rem;
}
.exp-header-icon {
    width: 48px; height: 48px; border-radius: var(--radius-md); flex-shrink: 0;
    background: var(--accent-glow); color: var(--accent);
    display: flex; align-items: center; justify-content: center;
}
.exp-header h2 { font-size: 1.3rem; font-weight: 700; margin: 0; }
.exp-header p  { font-size: 0.82rem; color: var(--text-muted); margin: 0.2rem 0 0; }

/* ── Filter box ─────────────────────────────────────── */
.exp-filter-box {
    background: var(--bg-card);
    border: 1px solid var(--border-card);
    border-radius: var(--radius-lg);
    padding: 1.1rem 1.25rem;
    margin-bottom: 1.5rem;
}
.exp-filter-label {
    font-size: 0.72rem; font-weight: 700; text-transform: uppercase;
    letter-spacing: 0.07em; color: var(--text-muted);
    display: flex; align-items: center; gap: 0.4rem; margin-bottom: 0.85rem;
}
.exp-filter-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
@media (max-width: 500px) { .exp-filter-grid { grid-template-columns: 1fr; } }

.exp-field-label {
    display: block; font-size: 0.73rem; font-weight: 600;
    color: var(--text-muted); margin-bottom: 0.35rem;
}
.exp-input-row { display: flex; align-items: center; gap: 0.4rem; }
.exp-input {
    flex: 1; height: 36px; padding: 0 0.65rem; font-size: 0.85rem;
    border-radius: var(--radius-sm);
    border: 1px solid var(--border-color);
    background: var(--bg-input); color: var(--text-primary);
    outline: none; transition: border-color 0.15s;
}
.exp-input:focus { border-color: var(--border-focus); box-shadow: 0 0 0 3px var(--accent-glow); }
.exp-clear-btn {
    height: 36px; width: 32px; flex-shrink: 0;
    border: 1px solid var(--border-color);
    background: var(--bg-input); color: var(--text-muted);
    border-radius: var(--radius-sm); cursor: pointer; font-size: 0.9rem;
    display: flex; align-items: center; justify-content: center;
    transition: all 0.15s;
}
.exp-clear-btn:hover { color: var(--accent); border-color: var(--accent); }

.exp-badge {
    display: none; align-items: center; gap: 0.5rem;
    margin-top: 0.75rem; padding: 0.45rem 0.85rem;
    background: var(--accent-glow);
    border: 1px solid color-mix(in srgb, var(--accent) 30%, transparent);
    border-radius: var(--radius-sm); font-size: 0.78rem; color: var(--accent); font-weight: 600;
}
.exp-badge.on { display: flex; }
.exp-badge-dot { width: 7px; height: 7px; border-radius: 50%; background: var(--accent); animation: expPulse 1.5s infinite; }
.exp-badge-clear { margin-left: auto; border: none; background: none; color: var(--accent); cursor: pointer; font-size: 0.75rem; font-weight: 600; text-decoration: underline; padding: 0; }

/* ── Accordion list ─────────────────────────────────── */
.exp-list {
    display: flex; flex-direction: column; gap: 0.85rem;
}

/* Each accordion item wrapper */
.exp-accordion {
    background: var(--bg-card);
    border: 1px solid var(--border-card);
    border-radius: var(--radius-lg);
    overflow: hidden;
}

/* Clickable trigger row */
.exp-trigger {
    display: flex; align-items: center; gap: 0.85rem;
    padding: 1rem 1.15rem;
    cursor: pointer;
    user-select: none;
    transition: background 0.15s;
    /* no border-bottom by default — appears when open */
}
.exp-trigger:hover { background: var(--bg-card-hover); }
.exp-accordion.open .exp-trigger {
    border-bottom: 1px solid var(--border-color);
}

.exp-trigger-icon {
    width: 40px; height: 40px; border-radius: var(--radius-sm); flex-shrink: 0;
    display: flex; align-items: center; justify-content: center;
}
.exp-trigger-icon.personal { background: var(--accent-glow); color: var(--accent); }
.exp-trigger-icon.team     { background: rgba(35,165,90,0.12); color: #23a55a; }
[data-theme="dark"] .exp-trigger-icon.team { background: rgba(35,165,90,0.18); }

.exp-trigger-text strong { display: block; font-size: 0.92rem; font-weight: 700; }
.exp-trigger-text span   { font-size: 0.72rem; color: var(--text-muted); }

/* Chevron */
.exp-chevron {
    margin-left: auto; flex-shrink: 0; color: var(--text-muted);
    transition: transform 0.25s cubic-bezier(0.4,0,0.2,1);
}
.exp-accordion.open .exp-chevron { transform: rotate(180deg); color: var(--accent); }

/* Dropdown panel — hidden by default */
.exp-panel {
    display: grid;
    grid-template-rows: 0fr;          /* collapsed */
    transition: grid-template-rows 0.28s cubic-bezier(0.4,0,0.2,1);
}
.exp-accordion.open .exp-panel {
    grid-template-rows: 1fr;          /* expanded */
}
.exp-panel-inner { overflow: hidden; }

/* Individual export rows inside panel */
.exp-option {
    display: flex; align-items: center; gap: 0.85rem;
    padding: 0.8rem 1.15rem;
    text-decoration: none; color: var(--text-primary);
    transition: background 0.15s;
    border-bottom: 1px solid var(--border-color);
}
.exp-option:last-child { border-bottom: none; }
.exp-option:hover { background: var(--bg-card-hover); }

.exp-format-badge {
    width: 36px; height: 36px; border-radius: var(--radius-sm); flex-shrink: 0;
    display: flex; align-items: center; justify-content: center;
}
.exp-format-badge.xls { background: rgba(34,197,94,0.12); color: #16a34a; }
.exp-format-badge.vcf { background: rgba(99,102,241,0.12); color: #6366f1; }
[data-theme="dark"] .exp-format-badge.xls { background: rgba(34,197,94,0.15); color: #4ade80; }
[data-theme="dark"] .exp-format-badge.vcf { background: rgba(99,102,241,0.16); color: #818cf8; }

.exp-option-text strong { display: block; font-size: 0.875rem; font-weight: 600; }
.exp-option-text span   { font-size: 0.72rem; color: var(--text-muted); }

.exp-option-arrow {
    margin-left: auto; color: var(--text-muted); flex-shrink: 0;
    transition: transform 0.15s;
}
.exp-option:hover .exp-option-arrow { transform: translateX(3px); color: var(--accent); }

/* Locked state */
.exp-locked {
    padding: 1.25rem 1.15rem; text-align: center;
}
.exp-locked p { font-size: 0.78rem; color: var(--text-muted); margin: 0.5rem 0 0; line-height: 1.5; }
.exp-locked a { color: var(--accent); font-weight: 600; }

@keyframes expPulse {
    0%, 100% { opacity: 0.4; }
    50%       { opacity: 1;   }
}
</style>

<div class="exp-page">

    <!-- Header -->
    <div class="exp-header">
        <div class="exp-header-icon">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                <polyline points="7 10 12 15 17 10"/>
                <line x1="12" y1="15" x2="12" y2="3"/>
            </svg>
        </div>
        <div>
            <h2>Export My Data</h2>
        </div>
    </div>

    <!-- Date / Time Filter -->
    <div class="exp-filter-box">
        <div class="exp-filter-label">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                <rect x="3" y="4" width="18" height="18" rx="2"/>
                <line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/>
                <line x1="3" y1="10" x2="21" y2="10"/>
            </svg>
            Filter by date &amp; time
        </div>
        <div class="exp-filter-grid">
            <div>
                <label class="exp-field-label" for="expSinceDate">Cards added since</label>
                <div class="exp-input-row">
                    <input type="date" id="expSinceDate" class="exp-input">
                    <button id="expClearDate" type="button" class="exp-clear-btn" title="Clear date">✕</button>
                </div>
            </div>
            <div>
                <label class="exp-field-label" for="expSinceTime">Start time on that day</label>
                <div class="exp-input-row">
                    <input type="time" id="expSinceTime" class="exp-input">
                    <button id="expClearTime" type="button" class="exp-clear-btn" title="Clear time">✕</button>
                </div>
            </div>
        </div>
        <div id="expFilterBadge" class="exp-badge">
            <span class="exp-badge-dot"></span>
            <span id="expFilterLabel">Filter active</span>
            <button class="exp-badge-clear" onclick="clearAllFilters()">Clear all</button>
        </div>
    </div>

    <!-- Accordion Export List -->
    <div class="exp-list">

        <!-- ── MY CARDS ── -->
        <div class="exp-accordion" id="accMyCards">
            <div class="exp-trigger" onclick="toggleAccordion('accMyCards')">
                <div class="exp-trigger-icon personal">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="7" r="4"/>
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                    </svg>
                </div>
                <div class="exp-trigger-text">
                    <strong>My Cards</strong>
                    <span>Cards you personally scanned</span>
                </div>
                <svg class="exp-chevron" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <polyline points="6 9 12 15 18 9"/>
                </svg>
            </div>
            <div class="exp-panel">
                <div class="exp-panel-inner">
                    <a href="<?= APP_URL ?>/my-cards/export" id="expBtnMyCsv" class="exp-option">
                        <div class="exp-format-badge xls">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="3" y="3" width="18" height="18" rx="2"/>
                                <line x1="3" y1="9" x2="21" y2="9"/>
                                <line x1="3" y1="15" x2="21" y2="15"/>
                                <line x1="9" y1="3" x2="9" y2="21"/>
                                <line x1="15" y1="3" x2="15" y2="21"/>
                            </svg>
                        </div>
                        <div class="exp-option-text">
                            <strong>Export as Excel</strong>
                            <span>Spreadsheet with all contact fields</span>
                        </div>
                        <svg class="exp-option-arrow" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"/></svg>
                    </a>
                    <a href="<?= APP_URL ?>/my-cards/vcf?_t=<?= urlencode($myVcfToken) ?>" id="expBtnMyVcf" class="exp-option">
                        <div class="exp-format-badge vcf">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="2" y="5" width="20" height="14" rx="2"/>
                                <circle cx="9" cy="12" r="2.5"/>
                                <path d="M5 19c0-2 1.8-3.5 4-3.5s4 1.5 4 3.5"/>
                                <line x1="15" y1="10" x2="19" y2="10"/>
                                <line x1="15" y1="14" x2="19" y2="14"/>
                            </svg>
                        </div>
                        <div class="exp-option-text">
                            <strong>Export as VCF</strong>
                            <span>Save all contacts to your phonebook</span>
                        </div>
                        <svg class="exp-option-arrow" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"/></svg>
                    </a>
                </div>
            </div>
        </div>

        <!-- ── TEAM CARDS ── -->
        <div class="exp-accordion" id="accTeamCards">
            <div class="exp-trigger" onclick="toggleAccordion('accTeamCards')">
                <div class="exp-trigger-icon team">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                        <circle cx="9" cy="7" r="4"/>
                        <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
                        <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                    </svg>
                </div>
                <div class="exp-trigger-text">
                    <strong>Team Cards</strong>
                    <span>All cards shared within your team</span>
                </div>
                <svg class="exp-chevron" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <polyline points="6 9 12 15 18 9"/>
                </svg>
            </div>
            <div class="exp-panel">
                <div class="exp-panel-inner">
                    <?php if (!empty($userRow['team_id'])): ?>
                    <a href="<?= APP_URL ?>/my-team/export" id="expBtnTeamCsv" class="exp-option">
                        <div class="exp-format-badge xls">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="3" y="3" width="18" height="18" rx="2"/>
                                <line x1="3" y1="9" x2="21" y2="9"/>
                                <line x1="3" y1="15" x2="21" y2="15"/>
                                <line x1="9" y1="3" x2="9" y2="21"/>
                                <line x1="15" y1="3" x2="15" y2="21"/>
                            </svg>
                        </div>
                        <div class="exp-option-text">
                            <strong>Export as Excel</strong>
                            <span>All team contacts in one spreadsheet</span>
                        </div>
                        <svg class="exp-option-arrow" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"/></svg>
                    </a>
                    <a href="<?= APP_URL ?>/my-team/vcf?_t=<?= urlencode($teamVcfToken) ?>" id="expBtnTeamVcf" class="exp-option">
                        <div class="exp-format-badge vcf">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="2" y="5" width="20" height="14" rx="2"/>
                                <circle cx="9" cy="12" r="2.5"/>
                                <path d="M5 19c0-2 1.8-3.5 4-3.5s4 1.5 4 3.5"/>
                                <line x1="15" y1="10" x2="19" y2="10"/>
                                <line x1="15" y1="14" x2="19" y2="14"/>
                            </svg>
                        </div>
                        <div class="exp-option-text">
                            <strong>Export as VCF</strong>
                            <span>Import all team contacts to your phone</span>
                        </div>
                        <svg class="exp-option-arrow" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"/></svg>
                    </a>
                    <?php else: ?>
                    <div class="exp-locked">
                        <svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="color:var(--text-muted);opacity:0.4;display:block;margin:0 auto;">
                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                            <circle cx="9" cy="7" r="4"/>
                            <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
                            <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                        </svg>
                        <p>You're not part of a team yet.<br>
                        <a href="<?= APP_URL ?>/team">Join or create a team</a> to unlock team exports.</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

    </div><!-- /.exp-list -->

</div><!-- /.exp-page -->

<script>
/* ── Accordion toggle ─────────────────────────── */
function toggleAccordion(id) {
    const el = document.getElementById(id);
    const isOpen = el.classList.contains('open');
    // Close all first
    document.querySelectorAll('.exp-accordion').forEach(a => a.classList.remove('open'));
    // Open clicked one if it was closed
    if (!isOpen) el.classList.add('open');
}

/* ── Filter + link updater ────────────────────── */
document.addEventListener('DOMContentLoaded', function () {
    const dateInput = document.getElementById('expSinceDate');
    const timeInput = document.getElementById('expSinceTime');
    const badge     = document.getElementById('expFilterBadge');
    const label     = document.getElementById('expFilterLabel');

    const linkIds = ['expBtnMyCsv','expBtnMyVcf','expBtnTeamCsv','expBtnTeamVcf'];
    const links   = linkIds.map(id => document.getElementById(id)).filter(Boolean);

    dateInput.value = localStorage.getItem('cv_export_since_date') || '';
    timeInput.value = localStorage.getItem('cv_export_since_time') || '';

    function updateLinks() {
        const dateVal = dateInput.value;
        const timeVal = timeInput.value;

        dateVal ? localStorage.setItem('cv_export_since_date', dateVal)
                : localStorage.removeItem('cv_export_since_date');
        timeVal ? localStorage.setItem('cv_export_since_time', timeVal)
                : localStorage.removeItem('cv_export_since_time');

        links.forEach(link => {
            const url = new URL(link.getAttribute('href'), window.location.origin);
            if (dateVal) {
                url.searchParams.set('since', dateVal);
                timeVal ? url.searchParams.set('time', timeVal)
                        : url.searchParams.delete('time');
            } else {
                url.searchParams.delete('since');
                url.searchParams.delete('time');
            }
            link.href = url.pathname + url.search;
        });

        if (dateVal) {
            badge.classList.add('on');
            const nice = new Date(dateVal + 'T00:00').toLocaleDateString('en-IN',
                { day:'2-digit', month:'short', year:'numeric' });
            label.textContent = `Showing cards added on/after ${nice}${timeVal ? ' · ' + timeVal : ''}`;
        } else {
            badge.classList.remove('on');
        }
    }

    window.clearAllFilters = function () {
        dateInput.value = ''; timeInput.value = '';
        localStorage.removeItem('cv_export_since_date');
        localStorage.removeItem('cv_export_since_time');
        updateLinks();
    };

    document.getElementById('expClearDate').addEventListener('click', () => {
        dateInput.value = ''; timeInput.value = '';
        localStorage.removeItem('cv_export_since_date');
        localStorage.removeItem('cv_export_since_time');
        updateLinks();
    });
    document.getElementById('expClearTime').addEventListener('click', () => {
        timeInput.value = '';
        localStorage.removeItem('cv_export_since_time');
        updateLinks();
    });

    dateInput.addEventListener('change', function () {
        if (!this.value) { timeInput.value = ''; localStorage.removeItem('cv_export_since_time'); }
        updateLinks();
    });
    timeInput.addEventListener('change', updateLinks);

    updateLinks();
});
</script>
