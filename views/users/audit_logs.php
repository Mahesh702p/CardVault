<?php /** Audit Logs View (Admin) */ ?>
<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem; flex-wrap:wrap; gap:0.75rem;">
    <h3>System Audit Logs</h3>
    <span style="font-size:0.9rem; color:var(--text-muted);">Total Logs: <strong><?= (int)$total ?></strong></span>
</div>

<div class="table-container">
    <table>
        <thead>
            <tr>
                <th>Timestamp</th>
                <th>User</th>
                <th>Action</th>
                <th>Entity Type</th>
                <th>Entity ID</th>
                <th>IP Address</th>
                <th>Details</th>
            </tr>
        </thead>
        <tbody>
        <?php if (empty($logs)): ?>
            <tr>
                <td colspan="7" style="text-align:center; color:var(--text-muted);">No audit logs found.</td>
            </tr>
        <?php else: ?>
            <?php foreach ($logs as $log): ?>
            <tr>
                <td data-label="Timestamp" style="white-space:nowrap; font-size:0.8rem; color:var(--text-muted);">
                    <?= date('Y-m-d H:i:s', strtotime($log['created_at'])) ?>
                </td>
                <td data-label="User">
                    <?php if ($log['user_name']): ?>
                        <div style="font-weight:500; color:var(--text-primary);"><?= htmlspecialchars($log['user_name']) ?></div>
                        <div style="font-size:0.75rem; color:var(--text-muted);"><?= htmlspecialchars($log['user_email']) ?></div>
                    <?php else: ?>
                        <span style="color:var(--text-muted); font-style:italic;">System / Guest</span>
                    <?php endif; ?>
                </td>
                <td data-label="Action">
                    <?php
                    $actionClass = '';
                    switch($log['action']) {
                        case 'create': $actionClass = 'badge-success'; break;
                        case 'update': $actionClass = 'badge-warning'; break;
                        case 'delete': $actionClass = 'badge-danger'; break;
                        case 'login':  $actionClass = 'badge-info'; break;
                        default:       $actionClass = ''; break;
                    }
                    ?>
                    <span class="badge <?= $actionClass ?>"><?= htmlspecialchars(strtoupper($log['action'])) ?></span>
                </td>
                <td data-label="Entity Type" style="font-family:monospace; font-size:0.85rem;">
                    <?= htmlspecialchars($log['entity_type'] ?: '—') ?>
                </td>
                <td data-label="Entity ID" style="font-family:monospace; font-size:0.85rem;">
                    <?= $log['entity_id'] ?: '—' ?>
                </td>
                <td data-label="IP Address" style="font-family:monospace; font-size:0.8rem; color:var(--text-muted);">
                    <?= htmlspecialchars($log['ip_address'] ?: '—') ?>
                </td>
                <td data-label="Details" style="font-size:0.8rem; max-width: 250px;">
                    <?php
                    $old = $log['old_values'] ? json_decode($log['old_values'], true) : [];
                    $new = $log['new_values'] ? json_decode($log['new_values'], true) : [];
                    
                    if (!empty($new)): ?>
                        <div style="max-height:80px; overflow-y:auto; font-family:monospace; background:var(--bg-primary); padding:0.4rem; border-radius:4px; border:1px solid var(--border-color);">
                            <?php foreach ($new as $key => $val): ?>
                                <div>
                                    <strong style="color:var(--accent);"><?= htmlspecialchars($key) ?>:</strong> 
                                    <span>
                                        <?php 
                                        if (is_array($val)) {
                                            echo htmlspecialchars(json_encode($val));
                                        } else {
                                            echo htmlspecialchars(strlen($val) > 40 ? substr($val, 0, 37) . '...' : $val);
                                        }
                                        ?>
                                    </span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <span style="color:var(--text-muted);">—</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Pagination -->
<?php if ($totalPages > 1): ?>
<div style="display:flex; justify-content:center; align-items:center; gap:0.5rem; margin-top:2rem;">
    <?php if ($page > 1): ?>
        <a href="<?= APP_URL ?>/admin/audit-logs?page=<?= $page - 1 ?>" class="btn btn-secondary btn-sm" style="padding:0.4rem 0.8rem;">&laquo; Prev</a>
    <?php endif; ?>
    
    <span style="font-size:0.9rem; color:var(--text-muted);">Page <?= $page ?> of <?= $totalPages ?></span>
    
    <?php if ($page < $totalPages): ?>
        <a href="<?= APP_URL ?>/admin/audit-logs?page=<?= $page + 1 ?>" class="btn btn-secondary btn-sm" style="padding:0.4rem 0.8rem;">Next &raquo;</a>
    <?php endif; ?>
</div>
<?php endif; ?>
