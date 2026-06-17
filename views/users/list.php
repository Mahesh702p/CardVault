<?php /** Users List (Admin) */ ?>
<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem; flex-wrap:wrap; gap:0.75rem;">
    <h3>All Users</h3>
    <div style="display:flex; gap:0.75rem;">
        <a href="<?= APP_URL ?>/cards/export" class="btn btn-secondary btn-sm">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
            Export All Cards (CSV)
        </a>
        <a href="<?= APP_URL ?>/users/create" class="btn btn-primary btn-sm">+ Add User</a>
    </div>
</div>
<div class="table-container">
    <table>
        <thead>
            <tr>
                <th>Name</th>
                <th>Employee ID</th>
                <th>Department</th>
                <th>Role</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($users as $u): ?>
        <tr>
            <td style="font-weight:500;" data-label="Name"><?= htmlspecialchars($u['name']) ?></td>
            <td data-label="Employee ID"><?= htmlspecialchars($u['employee_id'] ?? '—') ?></td>
            <td data-label="Department"><?= htmlspecialchars($u['department_name']) ?></td>
            <td data-label="Role"><span class="badge <?= $u['role'] === 'admin' ? 'badge-warning' : '' ?>"><?= $u['role'] ?></span></td>
            <td data-label="Actions">
                <a href="<?= APP_URL ?>/users/<?= $u['id'] ?>/edit" class="btn btn-secondary btn-sm">Edit</a>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
