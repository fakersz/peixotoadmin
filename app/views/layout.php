<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex, nofollow">
<title><?= e($title ?? 'Painel') ?> · <?= e(APP_NAME) ?></title>
<link rel="icon" type="image/png" href="<?= e(asset('/assets/img/logo.png')) ?>">
<link rel="stylesheet" href="<?= e(asset('/assets/css/app.css')) ?>">
</head>
<body>
<div class="app">
    <?php require APP_PATH . '/views/partials/sidebar.php'; ?>

    <div class="main">
        <header class="topbar">
            <button class="icon-btn menu-toggle" id="menuToggle" aria-label="Abrir menu">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M4 7h16M4 12h16M4 17h16"/></svg>
            </button>
            <h1 class="topbar-title"><?= e($title ?? '') ?></h1>
            <div class="topbar-user">
                <div class="avatar"><?= e(mb_strtoupper(mb_substr(current_user()['name'] ?? '?', 0, 1))) ?></div>
                <span class="topbar-username"><?= e(current_user()['name'] ?? '') ?></span>
            </div>
        </header>

        <main class="content">
            <?php if ($flash = get_flash()): ?>
                <div class="alert alert-<?= e($flash['type']) ?>" data-autohide>
                    <?= e($flash['message']) ?>
                </div>
            <?php endif; ?>

            <?= $content ?>
        </main>
    </div>
</div>
<div class="sidebar-overlay" id="sidebarOverlay"></div>
<script src="<?= e(asset('/assets/js/app.js')) ?>"></script>
</body>
</html>
