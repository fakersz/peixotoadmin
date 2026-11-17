<?php
$currentPath = trim(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH), '/') ?: 'dashboard';
$currentResource = explode('/', $currentPath)[0] ?: 'dashboard';
$activeRoute = $currentResource;

$menu = [
    ['dashboard',  'Dashboard',          '<path d="M3 13h8V3H3zM13 21h8V11h-8zM3 21h8v-6H3zM13 8h8V3h-8z"/>'],
    ['clients',    'Clientes',           '<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/>'],
    ['devices',    'Equipamentos',       '<rect x="2" y="4" width="20" height="13" rx="2"/><path d="M8 21h8M12 17v4"/>'],
    ['orders',     'Ordens de Serviço',  '<path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/>'],
    ['inventory',  'Estoque',            '<path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><path d="M3.27 6.96 12 12.01l8.73-5.05M12 22.08V12"/>'],
    ['financial',  'Financeiro',         '<path d="M12 1v22M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>'],
    ['trello',     'Kanban',             '<rect x="3" y="3" width="18" height="18" rx="2"/><rect x="7" y="7" width="3" height="9"/><rect x="14" y="7" width="3" height="5"/>'],
    ['digital-services', 'Serviços Digitais', '<rect x="3" y="4" width="18" height="14" rx="2"/><path d="M8 20h8M12 18v2M8 10l-2 2 2 2M16 10l2 2-2 2M13 9l-2 6"/>'],
    ['knowledge',  'Centro de Conhecimento', '<path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M4 4.5A2.5 2.5 0 0 1 6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5z"/><path d="M8 6h8M8 10h8M8 14h5"/>'],
];
?>
<aside class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <img src="<?= e(asset('/assets/img/logo.png')) ?>" alt="iFixAdmin" class="brand-logo">
        <div>
            <div class="brand-name">iFix<span>Admin</span></div>
            <div class="brand-sub"><?= e(APP_NAME) ?></div>
        </div>
    </div>

    <nav class="sidebar-nav">
        <?php foreach ($menu as [$route, $label, $icon]): ?>
            <a href="/<?= $route ?>" class="nav-item<?= $activeRoute === $route ? ' active' : '' ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><?= $icon ?></svg>
                <span><?= e($label) ?></span>
            </a>
        <?php endforeach; ?>
    </nav>

    <div class="sidebar-footer">
        <a href="/logout" class="nav-item nav-logout">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9"/></svg>
            <span>Sair</span>
        </a>
    </div>
</aside>
