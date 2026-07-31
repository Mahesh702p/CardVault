<?php /** Join or Manage Team View */ ?>
<style>
/* Modals Overlay */
.modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.4);
    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
    opacity: 0;
    pointer-events: none;
    transition: opacity 0.3s ease;
}
.modal-overlay.active {
    opacity: 1;
    pointer-events: auto;
}
.modal-content {
    background: var(--bg-secondary);
    border: 1px solid var(--border-card);
    border-radius: var(--radius-xl);
    width: 95%;
    max-width: 440px;
    padding: 2rem;
    box-shadow: var(--shadow-lg);
    transform: translateY(20px) scale(0.95);
    transition: transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
    position: relative;
}
.modal-overlay.active .modal-content {
    transform: translateY(0) scale(1);
}
.modal-close-btn {
    position: absolute;
    top: 1.25rem;
    right: 1.25rem;
    background: transparent;
    border: none;
    color: var(--text-muted);
    cursor: pointer;
    padding: 0.25rem;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s ease;
}
.modal-close-btn:hover {
    background: var(--bg-card-hover);
    color: var(--text-primary);
}
.modal-title {
    font-size: 1.35rem;
    font-weight: 700;
    color: var(--text-primary);
    margin: 0 0 0.5rem 0;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}
.modal-description {
    font-size: 0.8rem;
    color: var(--text-muted);
    margin-bottom: 1.5rem;
    line-height: 1.4;
}
.modal-content .btn {
    justify-content: center;
}
.team-option-btn {
    flex: 1;
    background: var(--bg-secondary);
    border: 1px solid var(--border-card);
    border-radius: var(--radius-xl);
    padding: 2.5rem 1.5rem;
    text-align: center;
    cursor: pointer;
    transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 1rem;
}
.team-option-btn:hover {
    border-color: var(--accent);
    background: var(--bg-card-hover);
    transform: translateY(-4px);
    box-shadow: var(--shadow-md);
}
.team-option-icon {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    background: var(--accent-glow);
    color: var(--accent);
    display: flex;
    align-items: center;
    justify-content: center;
}
.team-option-title {
    font-size: 1.05rem;
    font-weight: 700;
    color: var(--text-primary);
    margin: 0;
}
.team-option-desc {
    font-size: 0.75rem;
    color: var(--text-muted);
    margin: 0;
    line-height: 1.4;
}
.team-options-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
    gap: 2rem;
    max-width: 720px;
    margin: 0 auto;
}
@media (max-width: 768px) {
    .team-options-grid {
        grid-template-columns: 1fr !important;
        gap: 1.25rem !important;
    }
    .detail-info {
        padding: 2rem 1rem !important;
    }
    .detail-info h2 {
        font-size: 1.4rem !important;
        line-height: 1.3 !important;
    }
    .detail-info p {
        font-size: 0.85rem !important;
    }
    .team-option-btn {
        padding: 1.75rem 1.25rem !important;
    }
    .team-header-container {
        flex-direction: column !important;
        align-items: stretch !important;
        gap: 1.25rem !important;
    }
    .team-header-actions {
        width: 100% !important;
        flex-direction: column !important;
        align-items: stretch !important;
        gap: 0.5rem !important;
    }
    .team-header-actions form,
    .team-header-actions button,
    .team-header-actions a {
        width: 100% !important;
        flex: none !important;
        margin: 0 !important;
        text-align: center !important;
        justify-content: center !important;
    }

    /* Responsive Member Card Styling */
    .member-card {
        flex-direction: column !important;
        align-items: stretch !important;
        gap: 0.75rem !important;
    }
    .member-card-right {
        justify-content: flex-start !important;
        border-top: 1px solid var(--border-color);
        padding-top: 0.75rem;
        width: 100% !important;
        flex-wrap: wrap;
    }
}
</style>
<div class="detail-grid" style="max-width: 900px; margin: 0 auto;">
    <?php if ($team): ?>
        <!-- === TEAM IS ACTIVE === -->
        <div class="detail-info" style="grid-column: 1/-1; padding: 2rem;">
            <!-- Team Header -->
            <div class="team-header-container" style="display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:1.5rem; margin-bottom:2rem; padding-bottom:1.5rem; border-bottom:1px solid var(--border-color);">
                <div style="display:flex; align-items:center; gap:1.25rem;">
                    <div class="avatar" style="width:64px; height:64px; font-size:1.6rem; background:linear-gradient(135deg, var(--accent) 0%, #a83f39 100%); flex-shrink:0; border-radius:50%; display:flex; align-items:center; justify-content:center; color:#fff; font-weight:700; line-height:1; box-shadow:0 4px 10px rgba(0,0,0,0.15);">
                        <?= htmlspecialchars(strtoupper(substr($team['team_name'], 0, 2))) ?>
                    </div>
                    <div>
                        <h2 style="margin:0; font-size:1.5rem; font-weight:700; color:var(--text-primary);"><?= htmlspecialchars($team['team_name']) ?></h2>
                        <div style="font-size:0.9rem; color:var(--text-muted); margin-top:0.25rem;">
                            Team ID (Slug): <strong style="color:var(--text-primary); font-family:monospace;"><?= htmlspecialchars($team['team_code']) ?></strong>
                        </div>
                    </div>
                </div>

                <!-- Admin Action Area -->
                <div class="team-header-actions" style="display:flex; gap:0.75rem; align-items:center;">
                    <?php if ($userDb['is_team_admin']): ?>
                        <button type="button" class="btn" onclick="openModal('inviteMemberModal')" style="background:var(--accent); color:#fff; border:none; padding:0.6rem 1rem; border-radius:8px; font-weight:600; cursor:pointer; transition: all 0.2s ease;">
                            Invite Member
                        </button>
                        <button type="button" class="btn" onclick="openModal('editTeamDetailsModal')" style="background:transparent; border:1px solid var(--border-color); color:var(--text-primary); padding:0.6rem 1rem; border-radius:8px; font-weight:600; cursor:pointer; transition: all 0.2s ease;" onmouseover="this.style.borderColor='var(--accent)';" onmouseout="this.style.borderColor='var(--border-color)';">
                            Edit Details
                        </button>
                        <button type="button" class="btn" onclick="openModal('changeTeamPasswordModal')" style="background:transparent; border:1px solid var(--border-color); color:var(--text-primary); padding:0.6rem 1rem; border-radius:8px; font-weight:600; cursor:pointer; transition: all 0.2s ease;" onmouseover="this.style.borderColor='var(--accent)';" onmouseout="this.style.borderColor='var(--border-color)';">
                            Change Password
                        </button>
                        <form method="POST" action="<?= APP_URL ?>/team/disband" onsubmit="return confirm('WARNING: Disbanding the team will remove all members from this team. This action cannot be undone. Are you sure?');" style="margin:0; display:inline-block;">
                            <?= CSRF::field() ?>
                            <button type="submit" class="btn" style="background:#dc3545; color:#fff; border:none; padding:0.6rem 1rem; border-radius:8px; font-weight:600; cursor:pointer; width:100%;">
                                Disband Team
                            </button>
                        </form>
                    <?php else: ?>
                        <form method="POST" action="<?= APP_URL ?>/team/leave" onsubmit="return confirm('Are you sure you want to leave this team?');" style="margin:0; display:inline-block;">
                            <?= CSRF::field() ?>
                            <button type="submit" class="btn" style="background:transparent; color:#dc3545; border:1px solid #dc3545; padding:0.6rem 1rem; border-radius:8px; font-weight:600; cursor:pointer; transition: all 0.2s ease; width:100%;" onmouseover="this.style.background='var(--accent)'; this.style.color='#fff'; this.style.borderColor='var(--accent)';" onmouseout="this.style.background='transparent'; this.style.color='#dc3545'; this.style.borderColor='#dc3545';">
                                Leave Team
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Members List -->
            <div>
                <h3 style="font-size:1.1rem; font-weight:700; margin-bottom:1.25rem; display:flex; align-items:center; gap:0.5rem; color:var(--text-primary);">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                    Team Members (<?= count($members) ?>)
                </h3>

                <div class="team-members-list" style="display: flex; flex-direction: column; gap: 0.75rem; margin-top: 1rem;">
                    <?php foreach ($members as $member): ?>
                        <div class="member-card" style="display: flex; justify-content: space-between; align-items: center; padding: 0.85rem 1rem; background: var(--bg-secondary); border: 1px solid var(--border-card); border-radius: 12px;">
                            <!-- Left part: Avatar + Name + Emp ID + Department -->
                            <div style="display: flex; align-items: center; gap: 0.75rem; min-width: 0;">
                                <?php
                                $mParts = array_filter(explode(' ', $member['name']));
                                $mInitials = strtoupper(implode('', array_map(fn($w) => $w[0], $mParts)));
                                $mInitials = substr($mInitials, 0, 2);
                                ?>
                                <div class="avatar" style="width: 36px; height: 36px; font-size: 0.9rem; background: var(--bg-card); border: 1px solid var(--border-card); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 600; color: var(--text-primary); flex-shrink: 0;">
                                    <?= htmlspecialchars($mInitials) ?>
                                </div>
                                <div style="min-width: 0;">
                                    <div style="font-weight: 600; color: var(--text-primary); font-size: 0.9rem; display: flex; align-items: center; gap: 0.4rem; flex-wrap: wrap;">
                                        <span><?= htmlspecialchars($member['name']) ?></span>
                                    </div>
                                    <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 0.15rem;">Emp ID: <?= htmlspecialchars($member['employee_id']) ?></div>
                                </div>
                            </div>

                            <!-- Right part: Role badge & Actions -->
                            <div class="member-card-right" style="display: flex; align-items: center; gap: 0.75rem; flex-shrink: 0;">
                                <!-- Role Badges -->
                                <div style="display: flex; gap: 0.4rem; align-items: center;">
                                    <?php if ($member['id'] == $team['created_by_user_id']): ?>
                                        <span style="background: rgba(196, 154, 60, 0.15); color: #c49a3c; padding: 0.25rem 0.6rem; border-radius: 12px; font-size: 0.75rem; font-weight: 600; border: 1px solid rgba(196, 154, 60, 0.3);">Creator</span>
                                    <?php endif; ?>
                                    <?php if ($member['is_team_admin']): ?>
                                        <span style="background: rgba(123, 45, 38, 0.15); color: var(--accent); padding: 0.25rem 0.6rem; border-radius: 12px; font-size: 0.75rem; font-weight: 600;">Admin</span>
                                    <?php else: ?>
                                        <span style="background: var(--bg-card); color: var(--text-muted); padding: 0.25rem 0.6rem; border-radius: 12px; font-size: 0.75rem; font-weight: 500; border: 1px solid var(--border-card);">Member</span>
                                    <?php endif; ?>
                                </div>

                                <!-- Actions (Admin only) -->
                                <?php if ($userDb['is_team_admin'] && $member['id'] != $currentUser['id']): ?>
                                    <!-- Toggle Admin status (Cannot toggle original creator) -->
                                    <?php if ($member['id'] != $team['created_by_user_id']): ?>
                                        <form method="POST" action="<?= APP_URL ?>/team/toggle-admin/<?= (int)$member['id'] ?>" style="margin: 0; display: inline-block;">
                                            <?= CSRF::field() ?>
                                            <button type="submit" style="background: transparent; border: none; color: var(--accent); font-weight: 600; cursor: pointer; font-size: 0.85rem; padding: 0.25rem 0.5rem; transition: opacity 0.2s; margin-right: 0.5rem;" onmouseover="this.style.opacity=0.8" onmouseout="this.style.opacity=1">
                                                <?= $member['is_team_admin'] ? 'Dismiss Admin' : 'Make Admin' ?>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                    <!-- Remove Member -->
                                    <?php if ($member['id'] != $team['created_by_user_id']): ?>
                                    <form method="POST" action="<?= APP_URL ?>/team/remove-member/<?= (int)$member['id'] ?>" style="margin: 0; display: inline-block;" onsubmit="return confirm('Are you sure you want to remove <?= htmlspecialchars($member['name']) ?> from the team?');">
                                        <?= CSRF::field() ?>
                                        <button type="submit" style="background: transparent; border: none; color: #dc3545; font-weight: 600; cursor: pointer; font-size: 0.85rem; padding: 0.25rem 0.5rem; transition: opacity 0.2s;" onmouseover="this.style.opacity=0.8" onmouseout="this.style.opacity=1">
                                            Remove
                                        </button>
                                    </form>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Sent Pending Invitations (Admins only) -->
            <?php if ($userDb['is_team_admin'] && !empty($pendingTeamInvites)): ?>
                <div style="margin-top: 2.5rem; padding-top: 1.5rem; border-top: 1px solid var(--border-color);">
                    <h3 style="font-size:1.1rem; font-weight:700; margin-bottom:1.25rem; display:flex; align-items:center; gap:0.5rem; color:var(--text-primary);">
                        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M22 2L11 13M22 2l-7 20-4-9-9-4 20-7z"/></svg>
                        Pending Invitations (<?= count($pendingTeamInvites) ?>)
                    </h3>
                    <div class="team-members-list" style="display: flex; flex-direction: column; gap: 0.75rem;">
                        <?php foreach ($pendingTeamInvites as $invite): ?>
                            <div class="member-card" style="display: flex; justify-content: space-between; align-items: center; padding: 0.85rem 1rem; background: var(--bg-secondary); border: 1px solid var(--border-card); border-radius: 12px; opacity: 0.85;">
                                <div style="display: flex; align-items: center; gap: 0.75rem;">
                                    <div class="avatar" style="width: 36px; height: 36px; font-size: 0.9rem; background: var(--bg-card); border: 1px dashed var(--border-card); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 600; color: var(--text-muted);">
                                        ?
                                    </div>
                                    <div>
                                        <div style="font-weight: 600; color: var(--text-primary); font-size: 0.9rem;">
                                            <?= htmlspecialchars($invite['user_name']) ?>
                                        </div>
                                        <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 0.15rem;">
                                            Emp ID: <?= htmlspecialchars($invite['employee_id']) ?> • Sent <?= date('M d, Y', strtotime($invite['created_at'])) ?>
                                        </div>
                                    </div>
                                </div>
                                <div>
                                    <form method="POST" action="<?= APP_URL ?>/team/cancel-invite/<?= (int)$invite['id'] ?>" style="margin: 0; display: inline-block;" onsubmit="return confirm('Are you sure you want to withdraw the invitation for <?= htmlspecialchars($invite['user_name']) ?>?');">
                                        <?= CSRF::field() ?>
                                        <button type="submit" style="background: transparent; border: none; color: #dc3545; font-weight: 600; cursor: pointer; font-size: 0.85rem; padding: 0.25rem 0.5rem; transition: opacity 0.2s;" onmouseover="this.style.opacity=0.8" onmouseout="this.style.opacity=1">
                                            Withdraw
                                        </button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Invite Member Modal -->
        <div id="inviteMemberModal" class="modal-overlay" onclick="closeModalOnOverlay(event, 'inviteMemberModal')">
            <div class="modal-content">
                <button type="button" class="modal-close-btn" onclick="closeModal('inviteMemberModal')" aria-label="Close modal">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                </button>
                <h3 class="modal-title">
                    <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M19 8v6M16 11h6"/></svg>
                    Invite Team Member
                </h3>
                <p class="modal-description">Select an active coworker who doesn't belong to any team to send them an invitation.</p>
                
                <form method="POST" action="<?= APP_URL ?>/team/invite-member">
                    <?= CSRF::field() ?>
                    <div class="form-group" style="margin-bottom: 1.75rem; text-align: left;">
                        <label style="font-weight:600; font-size:0.8rem; color:var(--text-muted); display:block; margin-bottom:0.4rem;">Select Coworker</label>
                        <?php if (empty($usersWithoutTeam)): ?>
                            <div style="padding: 1rem; border: 1px dashed var(--border-color); border-radius: 8px; text-align: center; color: var(--text-muted); font-size: 0.85rem;">
                                No coworkers found without a team or pending invites.
                            </div>
                        <?php else: ?>
                            <select name="user_id" required style="width:100%; padding:0.75rem; border-radius:8px; border:1px solid var(--border-color); background:var(--bg-primary); color:var(--text-primary); box-sizing: border-box; cursor: pointer;">
                                <option value="">-- Choose a Coworker --</option>
                                <?php foreach ($usersWithoutTeam as $u): ?>
                                    <option value="<?= $u['id'] ?>">
                                        <?= htmlspecialchars($u['name']) ?> (Emp ID: <?= htmlspecialchars($u['employee_id']) ?>) <?= !empty($u['department_name']) ? '— ' . htmlspecialchars($u['department_name']) : '' ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        <?php endif; ?>
                    </div>
                    <?php if (!empty($usersWithoutTeam)): ?>
                        <button type="submit" class="btn btn-primary" style="width:100%; padding:0.8rem;">Send Invite</button>
                    <?php else: ?>
                        <button type="button" class="btn" onclick="closeModal('inviteMemberModal')" style="width:100%; padding:0.8rem; background: var(--bg-card); color: var(--text-muted); border: 1px solid var(--border-color);">Close</button>
                    <?php endif; ?>
                </form>
            </div>
        </div>

        <!-- Edit Team Details Modal -->
        <div id="editTeamDetailsModal" class="modal-overlay" onclick="closeModalOnOverlay(event, 'editTeamDetailsModal')">
            <div class="modal-content">
                <button type="button" class="modal-close-btn" onclick="closeModal('editTeamDetailsModal')" aria-label="Close modal">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                </button>
                <h3 class="modal-title">
                    <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 20h9M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
                    Edit Team Details
                </h3>
                <p class="modal-description">Modify your team name and unique slug/ID.</p>
                
                <form method="POST" action="<?= APP_URL ?>/team/update-details">
                    <?= CSRF::field() ?>
                    <div class="form-group" style="margin-bottom: 1rem; text-align: left;">
                        <label style="font-weight:600; font-size:0.8rem; color:var(--text-muted); display:block; margin-bottom:0.4rem;">Team Name</label>
                        <input type="text" name="team_name" value="<?= htmlspecialchars($team['team_name']) ?>" required style="width:100%; padding:0.75rem; border-radius:8px; border:1px solid var(--border-color); background:var(--bg-primary); color:var(--text-primary); box-sizing: border-box;">
                    </div>
                    <div class="form-group" style="margin-bottom: 1.75rem; text-align: left;">
                        <label style="font-weight:600; font-size:0.8rem; color:var(--text-muted); display:block; margin-bottom:0.4rem;">Team ID (Unique Slug)</label>
                        <input type="text" name="team_code" id="editTeamCodeInput" value="<?= htmlspecialchars($team['team_code']) ?>" required style="width:100%; padding:0.75rem; border-radius:8px; border:1px solid var(--border-color); background:var(--bg-primary); color:var(--text-primary); font-family:monospace; box-sizing: border-box;">
                    </div>
                    <button type="submit" class="btn btn-primary" style="width:100%; padding:0.8rem;">Save Changes</button>
                </form>
            </div>
        </div>

        <!-- Change Team Password Modal -->
        <div id="changeTeamPasswordModal" class="modal-overlay" onclick="closeModalOnOverlay(event, 'changeTeamPasswordModal')">
            <div class="modal-content">
                <button type="button" class="modal-close-btn" onclick="closeModal('changeTeamPasswordModal')" aria-label="Close modal">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                </button>
                <h3 class="modal-title">
                    <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                    Change Team Password
                </h3>
                <p class="modal-description">Set a new password for members joining this team.</p>
                
                <form method="POST" action="<?= APP_URL ?>/team/change-password">
                    <?= CSRF::field() ?>
                    <div class="form-group" style="margin-bottom: 1rem; text-align: left;">
                        <label style="font-weight:600; font-size:0.8rem; color:var(--text-muted); display:block; margin-bottom:0.4rem;">Current Team Password</label>
                        <input type="password" name="current_password" placeholder="••••••••" required style="width:100%; padding:0.75rem; border-radius:8px; border:1px solid var(--border-color); background:var(--bg-primary); color:var(--text-primary); box-sizing: border-box;">
                    </div>
                    <div class="form-group" style="margin-bottom: 1.75rem; text-align: left;">
                        <label style="font-weight:600; font-size:0.8rem; color:var(--text-muted); display:block; margin-bottom:0.4rem;">New Team Password</label>
                        <input type="password" name="team_password" placeholder="••••••••" required style="width:100%; padding:0.75rem; border-radius:8px; border:1px solid var(--border-color); background:var(--bg-primary); color:var(--text-primary); box-sizing: border-box;">
                    </div>
                    <button type="submit" class="btn btn-primary" style="width:100%; padding:0.8rem;">Update Password</button>
                </form>
            </div>
        </div>
    <?php else: ?>
        <!-- === PENDING INVITATIONS RECEIVED === -->
        <?php if (!empty($pendingUserInvites)): ?>
            <div class="detail-info" style="grid-column: 1/-1; padding: 2rem; margin-bottom: 2rem; border-radius: var(--radius-xl); background: var(--bg-card); border: 1px solid var(--border-card); box-shadow: var(--shadow-sm);">
                <h3 style="font-size: 1.2rem; font-weight: 700; color: var(--text-primary); margin-bottom: 1.25rem; display: flex; align-items: center; gap: 0.5rem;">
                    <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9M13.73 21a2 2 0 0 1-3.46 0"/></svg>
                    Pending Team Invitations (<?= count($pendingUserInvites) ?>)
                </h3>
                <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                    <?php foreach ($pendingUserInvites as $invite): ?>
                        <div style="display: flex; justify-content: space-between; align-items: center; padding: 1rem; background: var(--bg-secondary); border: 1px solid var(--border-card); border-radius: 12px; flex-wrap: wrap; gap: 1rem;">
                            <div>
                                <div style="font-weight: 700; color: var(--text-primary); font-size: 1rem;">
                                    <?= htmlspecialchars($invite['team_name']) ?>
                                </div>
                                <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 0.25rem;">
                                    Invited by: <strong><?= htmlspecialchars($invite['inviter_name']) ?></strong> • <?= date('M d, Y', strtotime($invite['created_at'])) ?>
                                </div>
                            </div>
                            <div style="display: flex; gap: 0.5rem; align-items: center;">
                                <form method="POST" action="<?= APP_URL ?>/team/accept-invite/<?= (int)$invite['id'] ?>" style="margin: 0;">
                                    <?= CSRF::field() ?>
                                    <button type="submit" class="btn btn-primary" style="padding: 0.5rem 1rem; font-size: 0.85rem; background: #28a745; border-color: #28a745; font-weight: 600; color: #fff; cursor: pointer; border-radius: 6px;">
                                        Accept
                                    </button>
                                </form>
                                <form method="POST" action="<?= APP_URL ?>/team/decline-invite/<?= (int)$invite['id'] ?>" style="margin: 0;">
                                    <?= CSRF::field() ?>
                                    <button type="submit" class="btn" style="padding: 0.5rem 1rem; font-size: 0.85rem; background: transparent; border: 1px solid var(--border-card); color: var(--text-muted); font-weight: 600; cursor: pointer; border-radius: 6px;" onmouseover="this.style.borderColor='var(--accent)';" onmouseout="this.style.borderColor='var(--border-card)';">
                                        Decline
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- === JOIN / CREATE TEAM SELECTION === -->
        <div class="detail-info" style="grid-column: 1/-1; padding: 3rem 2rem; border-radius: var(--radius-xl); text-align: center; background: var(--bg-card); box-shadow: var(--shadow-sm); border: 1px solid var(--border-card);">
            <div style="max-width: 600px; margin: 0 auto 3rem auto;">
                <h2 style="font-size:1.8rem; font-weight:700; color:var(--text-primary); margin-bottom:0.75rem;">Get Started with Teams</h2>
                <p style="color:var(--text-muted); font-size:0.95rem; line-height:1.6; margin:0;">
                    Collaborate with your coworkers. Joining a team allows you to share private business cards inside your team scope securely.
                </p>
            </div>

            <!-- Two Buttons/Cards Layout -->
            <div class="team-options-grid">
                <!-- Join Team Card -->
                <div class="team-option-btn" onclick="openModal('joinTeamModal')">
                    <div class="team-option-icon">
                        <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M19 8v6M16 11h6"/></svg>
                    </div>
                    <h3 class="team-option-title">Join a Team</h3>
                    <p class="team-option-desc">Enter a Team ID and password shared by your manager to instantly join.</p>
                </div>

                <!-- Create Team Card -->
                <div class="team-option-btn" onclick="openModal('createTeamModal')">
                    <div class="team-option-icon">
                        <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                    </div>
                    <h3 class="team-option-title">Create a Team</h3>
                    <p class="team-option-desc">Set up a new workspace for your group and manage it as Team Admin.</p>
                </div>
            </div>
        </div>

        <!-- ================= MODALS ================= -->
        
        <!-- Join Team Modal -->
        <div id="joinTeamModal" class="modal-overlay" onclick="closeModalOnOverlay(event, 'joinTeamModal')">
            <div class="modal-content">
                <button type="button" class="modal-close-btn" onclick="closeModal('joinTeamModal')" aria-label="Close modal">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                </button>
                <h3 class="modal-title">
                    <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M19 8v6M16 11h6"/></svg>
                    Join a Team
                </h3>
                <p class="modal-description">Enter the unique credentials shared by your team admin.</p>
                
                <form method="POST" action="<?= APP_URL ?>/team/join">
                    <?= CSRF::field() ?>
                    <div class="form-group" style="margin-bottom: 1.25rem; text-align: left;">
                        <label style="font-weight:600; font-size:0.8rem; color:var(--text-muted); display:block; margin-bottom:0.4rem;">Team ID (Slug)</label>
                        <input type="text" name="team_code" placeholder="e.g. west-sales" required style="width:100%; padding:0.75rem; border-radius:8px; border:1px solid var(--border-color); background:var(--bg-primary); color:var(--text-primary); box-sizing: border-box;">
                    </div>
                    <div class="form-group" style="margin-bottom: 1.75rem; text-align: left;">
                        <label style="font-weight:600; font-size:0.8rem; color:var(--text-muted); display:block; margin-bottom:0.4rem;">Team Password</label>
                        <input type="password" name="team_password" placeholder="••••••••" required style="width:100%; padding:0.75rem; border-radius:8px; border:1px solid var(--border-color); background:var(--bg-primary); color:var(--text-primary); box-sizing: border-box;">
                    </div>
                    <button type="submit" class="btn btn-primary" style="width:100%; padding:0.8rem;">Join Team</button>
                </form>
            </div>
        </div>

        <!-- Create Team Modal -->
        <div id="createTeamModal" class="modal-overlay" onclick="closeModalOnOverlay(event, 'createTeamModal')">
            <div class="modal-content">
                <button type="button" class="modal-close-btn" onclick="closeModal('createTeamModal')" aria-label="Close modal">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                </button>
                <h3 class="modal-title">
                    <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                    Create a Team
                </h3>
                <p class="modal-description">Create a new collaborative group. You will become the admin.</p>
                
                <form method="POST" action="<?= APP_URL ?>/team/create">
                    <?= CSRF::field() ?>
                    <div class="form-group" style="margin-bottom: 1rem; text-align: left;">
                        <label style="font-weight:600; font-size:0.8rem; color:var(--text-muted); display:block; margin-bottom:0.4rem;">Team Name</label>
                        <input type="text" name="team_name" placeholder="e.g. West Coast Sales" required style="width:100%; padding:0.75rem; border-radius:8px; border:1px solid var(--border-color); background:var(--bg-primary); color:var(--text-primary); box-sizing: border-box;">
                    </div>
                    <div class="form-group" style="margin-bottom: 1rem; text-align: left;">
                        <label style="font-weight:600; font-size:0.8rem; color:var(--text-muted); display:block; margin-bottom:0.4rem;">Team ID (Unique Slug)</label>
                        <input type="text" name="team_code" id="teamCodeInput" placeholder="e.g. west-sales" required style="width:100%; padding:0.75rem; border-radius:8px; border:1px solid var(--border-color); background:var(--bg-primary); color:var(--text-primary); font-family:monospace; box-sizing: border-box;">
                    </div>
                    <div class="form-group" style="margin-bottom: 1.5rem; text-align: left;">
                        <label style="font-weight:600; font-size:0.8rem; color:var(--text-muted); display:block; margin-bottom:0.4rem;">Team Password (For members)</label>
                        <input type="password" name="team_password" placeholder="••••••••" required style="width:100%; padding:0.75rem; border-radius:8px; border:1px solid var(--border-color); background:var(--bg-primary); color:var(--text-primary); box-sizing: border-box;">
                    </div>
                    <button type="submit" class="btn btn-primary" style="width:100%; padding:0.8rem;">Create Team</button>
                </form>
            </div>
        </div>
    <?php endif; ?>

    <script>
        // Modal Open/Close handlers
        function openModal(modalId) {
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.classList.add('active');
                document.body.style.overflow = 'hidden';
            }
        }
        function closeModal(modalId) {
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.classList.remove('active');
                document.body.style.overflow = '';
            }
        }
        function closeModalOnOverlay(e, modalId) {
            if (e.target.id === modalId) {
                closeModal(modalId);
            }
        }

        // ESC key closes modals
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeModal('joinTeamModal');
                closeModal('createTeamModal');
                closeModal('changeTeamPasswordModal');
                closeModal('inviteMemberModal');
                closeModal('editTeamDetailsModal');
            }
        });

        // Automatically make the team code slug-friendly (lowercase, replace spaces/specials with dashes)
        const nameInput = document.getElementsByName('team_name')[0];
        const codeInput = document.getElementById('teamCodeInput');
        if (nameInput && codeInput) {
            nameInput.addEventListener('input', function() {
                if (!codeInput.dataset.edited) {
                    codeInput.value = nameInput.value
                        .toLowerCase()
                        .replace(/[^a-z0-9\s-]/g, '')
                        .replace(/\s+/g, '-')
                        .replace(/-+/g, '-');
                }
            });
            codeInput.addEventListener('input', function() {
                codeInput.dataset.edited = "true";
            });
        }

        // Handle slug auto-generation for editing team details
        const editNameInput = document.querySelector('#editTeamDetailsModal [name="team_name"]');
        const editCodeInput = document.getElementById('editTeamCodeInput');
        if (editNameInput && editCodeInput) {
            editNameInput.addEventListener('input', function() {
                if (!editCodeInput.dataset.edited) {
                    editCodeInput.value = editNameInput.value
                        .toLowerCase()
                        .replace(/[^a-z0-9\s-]/g, '')
                        .replace(/\s+/g, '-')
                        .replace(/-+/g, '-');
                }
            });
            editCodeInput.addEventListener('input', function() {
                editCodeInput.dataset.edited = "true";
            });
        }
    </script>
</div>
