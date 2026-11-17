<?php
/** Middleware de autenticação. */

function is_logged_in(): bool
{
    return !empty($_SESSION['user_id']);
}

function require_login(): void
{
    if (!is_logged_in()) {
        redirect('/login');
    }
}

function current_user(): ?array
{
    if (!is_logged_in()) {
        return null;
    }
    static $user = null;
    if ($user === null) {
        $stmt = db()->prepare('SELECT id, name, email, role, created_at FROM users WHERE id = ?');
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch() ?: null;
        if ($user === null) {
            // Usuário removido do banco: derruba a sessão.
            session_destroy();
            redirect('/login');
        }
    }
    return $user;
}

function user_can(string $permission): bool
{
    $role = current_user()['role'] ?? 'admin';
    $permissions = [
        'admin' => [
            'publish','edit','delete','migrate','import','create','view',
            'order_view','order_fast_create','order_fast_convert',
            'digital_service_view','digital_service_create','digital_service_edit','digital_service_archive','digital_service_delete',
            'digital_service_finance','digital_service_files','digital_service_links','digital_service_assign',
            'knowledge_view','knowledge_search','knowledge_favorite',
            'knowledge_article_create','knowledge_article_edit','knowledge_article_publish','knowledge_article_archive','knowledge_article_delete',
            'knowledge_repair_create','knowledge_repair_edit','knowledge_repair_delete',
            'knowledge_manufacturer_manage','knowledge_model_manage','knowledge_asset_manage','knowledge_asset_upload','knowledge_history_restore',
        ],
        'tecnico' => [
            'create','edit_own','draft','view',
            'order_view','order_fast_create','order_fast_convert',
            'digital_service_view','digital_service_create','digital_service_edit_own','digital_service_status','digital_service_checklist',
            'digital_service_notes','digital_service_files',
            'knowledge_view','knowledge_search','knowledge_favorite',
            'knowledge_article_create','knowledge_article_edit_own_draft','knowledge_article_restore_own',
            'knowledge_repair_create','knowledge_repair_edit_own',
            'knowledge_asset_upload',
        ],
        'usuario' => [
            'view',
            'order_view',
            'digital_service_view',
            'knowledge_view','knowledge_search','knowledge_favorite',
        ],
    ];
    return in_array($permission, $permissions[$role] ?? ['view'], true);
}

function digital_service_can(string $action, ?array $service = null): bool
{
    if (user_is_admin()) {
        return true;
    }

    $userId = (int) (current_user()['id'] ?? 0);
    $ownerId = (int) ($service['created_by'] ?? $service['assigned_user_id'] ?? 0);
    $own = $ownerId === 0 || $ownerId === $userId;

    return match ($action) {
        'view' => user_can('digital_service_view'),
        'create' => user_can('digital_service_create'),
        'edit' => user_can('digital_service_edit_own') && $own,
        'status' => (user_can('digital_service_status') || user_can('digital_service_edit_own')) && $own,
        'checklist' => user_can('digital_service_checklist') && $own,
        'notes' => user_can('digital_service_notes') && $own,
        'files' => user_can('digital_service_files') && $own,
        'links' => user_can('digital_service_links') && $own,
        'archive', 'delete', 'finance', 'assign' => false,
        default => false,
    };
}

function order_can(string $action, ?array $order = null): bool
{
    if (user_is_admin()) {
        return true;
    }

    return match ($action) {
        'view' => user_can('order_view'),
        'fast_create' => user_can('order_fast_create'),
        'fast_convert' => user_can('order_fast_convert') && user_role() === 'tecnico',
        default => false,
    };
}

function user_role(): string
{
    return current_user()['role'] ?? 'usuario';
}

function user_is_admin(): bool
{
    return user_role() === 'admin';
}

function knowledge_can(string $action, ?array $record = null): bool
{
    if (user_is_admin()) {
        return true;
    }

    $userId = (int) (current_user()['id'] ?? 0);
    $ownerId = (int) ($record['author_user_id'] ?? $record['user_id'] ?? 0);
    $status = (string) ($record['status'] ?? '');

    return match ($action) {
        'view', 'search', 'favorite' => user_can('knowledge_' . $action),
        'article_create' => user_can('knowledge_article_create'),
        'article_edit' => user_can('knowledge_article_edit_own_draft') && $ownerId === $userId && $status !== 'publicado',
        'article_publish', 'article_archive', 'article_delete', 'manufacturer_manage', 'model_manage', 'asset_manage' => false,
        'repair_create' => user_can('knowledge_repair_create'),
        'repair_edit' => user_can('knowledge_repair_edit_own') && $ownerId === $userId,
        'repair_delete' => false,
        'asset_upload' => user_can('knowledge_asset_upload'),
        'history_restore' => user_can('knowledge_history_restore') || (user_can('knowledge_article_restore_own') && $ownerId === $userId && $status !== 'publicado'),
        default => false,
    };
}
