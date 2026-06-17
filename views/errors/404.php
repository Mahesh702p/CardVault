<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 — Page Not Found</title>
    <link rel="stylesheet" href="<?= APP_URL ?? '' ?>/css/style.css">
</head>
<body>
<div style="min-height:100vh; display:flex; align-items:center; justify-content:center;">
    <div class="empty-state">
        <div class="empty-icon"><svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg></div>
        <h3>Page Not Found</h3>
        <p>The page you're looking for doesn't exist.</p>
        <a href="<?= APP_URL ?? '' ?>/dashboard" class="btn btn-primary">Go to Dashboard</a>
    </div>
</div>
</body>
</html>
