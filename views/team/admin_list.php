<?php /** Teams List (Admin) */ ?>
<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem; flex-wrap:wrap; gap:0.75rem;">
    <h3>All Teams</h3>
</div>

<div class="table-container">
    <table>
        <thead>
            <tr>
                <th>Team Name</th>
                <th>Team ID (Slug)</th>
                <th>Creator / Admin</th>
                <th>Members</th>
                <th>Created At</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php if (empty($teams)): ?>
            <tr>
                <td colspan="6" style="text-align:center; color:var(--text-muted); padding:2rem;">No teams have been created yet.</td>
            </tr>
        <?php else: ?>
            <?php foreach ($teams as $t): ?>
            <tr>
                <td style="font-weight:500;" data-label="Team Name"><?= htmlspecialchars($t['team_name']) ?></td>
                <td data-label="Team ID (Slug)"><code style="background:var(--card-bg-hover); padding:0.15rem 0.4rem; border-radius:4px; font-size:0.85rem; color:var(--accent);"><?= htmlspecialchars($t['team_code']) ?></code></td>
                <td data-label="Creator / Admin"><?= htmlspecialchars($t['creator_name'] ?? 'System / Unknown') ?></td>
                <td data-label="Members">
                    <span class="badge" style="background:var(--card-bg-hover); color:var(--text-color); border:1px solid var(--border-color);">
                        <?= (int)$t['member_count'] ?> <?= (int)$t['member_count'] === 1 ? 'member' : 'members' ?>
                    </span>
                </td>
                <td data-label="Created At"><?= date('M d, Y', strtotime($t['created_at'])) ?></td>
                <td data-label="Actions">
                    <form action="<?= APP_URL ?>/admin/teams/disband/<?= $t['id'] ?>" method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to disband & delete the team &quot;<?= htmlspecialchars($t['team_name']) ?>&quot;? This will remove all members from the team.');">
                        <?= CSRF::field() ?>
                        <button type="submit" class="btn btn-secondary btn-sm" style="color:var(--danger); border-color:var(--danger); background:transparent; transition: all 0.2s ease;" onmouseover="this.style.background='var(--danger)'; this.style.color='#fff';" onmouseout="this.style.background='transparent'; this.style.color='var(--danger)';">
                            Disband
                        </button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>
</div>
