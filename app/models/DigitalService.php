<?php
/** Model: Serviços Digitais */

const DIGITAL_SERVICE_TYPES = [
    'site_institucional' => 'Site institucional',
    'ecommerce' => 'E-commerce',
    'loja_virtual' => 'Loja virtual',
    'landing_page' => 'Landing page',
    'sistema_web' => 'Sistema web',
    'saas' => 'SaaS',
    'aplicativo' => 'Aplicativo',
    'automacao' => 'Automação',
    'integracao' => 'Integração',
    'hospedagem' => 'Hospedagem',
    'dominio' => 'Domínio',
    'manutencao' => 'Manutenção',
    'suporte' => 'Suporte',
    'seo' => 'SEO',
    'consultoria' => 'Consultoria',
    'outro' => 'Outro',
];

const DIGITAL_SERVICE_STATUSES = [
    'lead' => 'Lead',
    'orcamento' => 'Orçamento',
    'aguardando_aprovacao' => 'Aguardando aprovação',
    'planejamento' => 'Planejamento',
    'design' => 'Design',
    'em_desenvolvimento' => 'Em desenvolvimento',
    'em_testes' => 'Em testes',
    'aguardando_cliente' => 'Aguardando cliente',
    'pausado' => 'Pausado',
    'publicado' => 'Publicado',
    'em_manutencao' => 'Em manutenção',
    'concluido' => 'Concluído',
    'cancelado' => 'Cancelado',
];

const DIGITAL_SERVICE_PRIORITIES = [
    'baixa' => 'Baixa',
    'normal' => 'Normal',
    'alta' => 'Alta',
    'urgente' => 'Urgente',
];

const DIGITAL_SERVICE_CHECKLIST_TEMPLATES = [
    'site_institucional' => ['briefing','domínio','identidade visual','wireframe','layout','desenvolvimento','responsividade','conteúdo','formulário','SEO básico','testes','publicação','entrega'],
    'ecommerce' => ['briefing','catálogo','categorias','produtos','estoque','carrinho','checkout','pagamento','frete','políticas','e-mails','responsividade','testes','publicação'],
    'loja_virtual' => ['briefing','catálogo','categorias','produtos','estoque','carrinho','checkout','pagamento','frete','políticas','e-mails','responsividade','testes','publicação'],
    'sistema_web' => ['levantamento','banco de dados','autenticação','usuários','permissões','módulos','testes','homologação','deploy','documentação','treinamento'],
    'saas' => ['levantamento','banco de dados','autenticação','usuários','permissões','módulos','testes','homologação','deploy','documentação','treinamento'],
];

function digital_service_select_sql(): string
{
    return "SELECT ds.*,
                   c.name AS client_name,
                   u.name AS assigned_user_name,
                   creator.name AS created_by_name,
                   COALESCE(ds.amount_received, 0) AS amount_received_value,
                   GREATEST(COALESCE(ds.total_value, 0) - COALESCE(ds.amount_received, 0), 0) AS balance_due,
                   (ds.due_date IS NOT NULL AND ds.due_date < CURRENT_DATE AND ds.status NOT IN ('publicado','concluido','cancelado') AND ds.deleted_at IS NULL) AS is_overdue,
                   (SELECT COUNT(*) FROM digital_service_tasks t WHERE t.digital_service_id = ds.id) AS task_count,
                   (SELECT COUNT(*) FROM digital_service_tasks t WHERE t.digital_service_id = ds.id AND t.status = 'concluida') AS task_done_count,
                   (SELECT COUNT(*) FROM digital_service_links l WHERE l.digital_service_id = ds.id) AS link_count,
                   (SELECT COUNT(*) FROM digital_service_files f WHERE f.digital_service_id = ds.id) AS file_count,
                   (SELECT COUNT(*) FROM digital_service_notes n WHERE n.digital_service_id = ds.id) AS note_count,
                   tc.title AS kanban_card_title
            FROM digital_services ds
            LEFT JOIN clients c ON c.id = ds.client_id
            LEFT JOIN users u ON u.id = ds.assigned_user_id
            LEFT JOIN users creator ON creator.id = ds.created_by
            LEFT JOIN trello_cards tc ON tc.id = ds.kanban_card_id";
}

function digital_services_all(array $filters = []): array
{
    $sql = digital_service_select_sql() . ' WHERE ds.deleted_at IS NULL';
    $params = [];

    if (empty($filters['archived'])) {
        $sql .= ' AND ds.is_archived = FALSE';
    } elseif (($filters['archived'] ?? '') === 'only') {
        $sql .= ' AND ds.is_archived = TRUE';
    }
    if (!empty($filters['q'])) {
        $sql .= ' AND (ds.project_name ILIKE :q OR ds.description ILIKE :q OR ds.temporary_client_name ILIKE :q OR c.name ILIKE :q OR ds.domain ILIKE :q)';
        $params['q'] = '%' . $filters['q'] . '%';
    }
    foreach (['client_id','assigned_user_id'] as $intFilter) {
        if (!empty($filters[$intFilter])) {
            $sql .= ' AND ds.' . $intFilter . ' = :' . $intFilter;
            $params[$intFilter] = (int) $filters[$intFilter];
        }
    }
    foreach (['service_type','status','priority'] as $filter) {
        if (!empty($filters[$filter])) {
            $sql .= ' AND ds.' . $filter . ' = :' . $filter;
            $params[$filter] = $filters[$filter];
        }
    }
    if (!empty($filters['overdue'])) {
        $sql .= " AND ds.due_date < CURRENT_DATE AND ds.status NOT IN ('publicado','concluido','cancelado')";
    }
    if (!empty($filters['balance_due'])) {
        $sql .= ' AND COALESCE(ds.total_value, 0) > COALESCE(ds.amount_received, 0)';
    }
    if (!empty($filters['completed'])) {
        $sql .= " AND ds.status IN ('publicado','concluido')";
    }
    if (!empty($filters['period']) && preg_match('/^\d{4}-\d{2}$/', (string) $filters['period'])) {
        $sql .= ' AND ds.created_at >= :period_from AND ds.created_at < (:period_from::date + INTERVAL \'1 month\')';
        $params['period_from'] = $filters['period'] . '-01';
    }

    $sql .= ' ORDER BY ' . digital_services_sort_sql($filters['sort'] ?? 'recent');
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function digital_services_sort_sql(string $sort): string
{
    return match ($sort) {
        'due_date' => 'ds.due_date ASC NULLS LAST, ds.created_at DESC',
        'progress' => 'ds.progress DESC, ds.updated_at DESC',
        'client' => 'COALESCE(c.name, ds.temporary_client_name, \'\') ASC, ds.project_name ASC',
        'value' => 'ds.total_value DESC NULLS LAST, ds.created_at DESC',
        'priority' => "CASE ds.priority WHEN 'urgente' THEN 1 WHEN 'alta' THEN 2 WHEN 'normal' THEN 3 ELSE 4 END, ds.due_date ASC NULLS LAST",
        'status' => 'ds.status ASC, ds.due_date ASC NULLS LAST',
        default => 'ds.created_at DESC',
    };
}

function digital_service_find(int $id, bool $includeDeleted = false): ?array
{
    $sql = digital_service_select_sql() . ' WHERE ds.id = ?';
    if (!$includeDeleted) {
        $sql .= ' AND ds.deleted_at IS NULL';
    }
    $stmt = db()->prepare($sql);
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}

function digital_service_create(array $data): int
{
    $stmt = db()->prepare(
        'INSERT INTO digital_services
            (client_id, temporary_client_name, project_name, service_type, description, status, priority, progress, progress_mode,
             assigned_user_id, start_date, due_date, delivered_at, next_review_at, total_value, down_payment, amount_received,
             estimated_cost, payment_method, installments, recurring_value, recurring_interval, stack, language, database_name,
             framework, server_name, hosting, domain, ssl_status, repository_url, environment, version, site_url, admin_url,
             staging_url, github_url, figma_url, documentation_url, domain_url, hosting_url, internal_notes, client_notes,
             pending_items, blockers, important_decisions, created_by)
         VALUES
            (:client_id, :temporary_client_name, :project_name, :service_type, :description, :status, :priority, :progress, :progress_mode,
             :assigned_user_id, :start_date, :due_date, :delivered_at, :next_review_at, :total_value, :down_payment, :amount_received,
             :estimated_cost, :payment_method, :installments, :recurring_value, :recurring_interval, :stack, :language, :database_name,
             :framework, :server_name, :hosting, :domain, :ssl_status, :repository_url, :environment, :version, :site_url, :admin_url,
             :staging_url, :github_url, :figma_url, :documentation_url, :domain_url, :hosting_url, :internal_notes, :client_notes,
             :pending_items, :blockers, :important_decisions, :created_by)
         RETURNING id'
    );
    $stmt->execute(digital_service_bind($data));
    $id = (int) $stmt->fetchColumn();
    digital_service_history_create($id, 'create', null, $data);
    return $id;
}

function digital_service_update(int $id, array $data): void
{
    $old = digital_service_find($id);
    $stmt = db()->prepare(
        'UPDATE digital_services SET
            client_id = :client_id, temporary_client_name = :temporary_client_name, project_name = :project_name,
            service_type = :service_type, description = :description, status = :status, priority = :priority,
            progress = :progress, progress_mode = :progress_mode, assigned_user_id = :assigned_user_id,
            start_date = :start_date, due_date = :due_date, delivered_at = :delivered_at, next_review_at = :next_review_at,
            total_value = :total_value, down_payment = :down_payment, amount_received = :amount_received,
            estimated_cost = :estimated_cost, payment_method = :payment_method, installments = :installments,
            recurring_value = :recurring_value, recurring_interval = :recurring_interval, stack = :stack, language = :language,
            database_name = :database_name, framework = :framework, server_name = :server_name, hosting = :hosting,
            domain = :domain, ssl_status = :ssl_status, repository_url = :repository_url, environment = :environment,
            version = :version, site_url = :site_url, admin_url = :admin_url, staging_url = :staging_url, github_url = :github_url,
            figma_url = :figma_url, documentation_url = :documentation_url, domain_url = :domain_url,
            hosting_url = :hosting_url, internal_notes = :internal_notes, client_notes = :client_notes, pending_items = :pending_items,
            blockers = :blockers, important_decisions = :important_decisions, updated_at = NOW()
         WHERE id = :id'
    );
    $bind = digital_service_bind($data);
    unset($bind['created_by']);
    $bind['id'] = $id;
    $stmt->execute($bind);
    digital_service_history_create($id, 'update', $old, $data);
}

function digital_service_bind(array $data): array
{
    $fields = [
        'client_id','temporary_client_name','project_name','service_type','description','status','priority','progress','progress_mode',
        'assigned_user_id','start_date','due_date','delivered_at','next_review_at','total_value','down_payment','amount_received',
        'estimated_cost','payment_method','installments','recurring_value','recurring_interval','stack','language','database_name',
        'framework','server_name','hosting','domain','ssl_status','repository_url','environment','version','site_url','admin_url',
        'staging_url','github_url','figma_url','documentation_url','domain_url','hosting_url','internal_notes','client_notes',
        'pending_items','blockers','important_decisions','created_by',
    ];
    $bind = [];
    foreach ($fields as $field) {
        $bind[$field] = $data[$field] ?? null;
    }
    $bind['progress'] = max(0, min(100, (int) ($bind['progress'] ?? 0)));
    $bind['status'] = array_key_exists((string) $bind['status'], DIGITAL_SERVICE_STATUSES) ? $bind['status'] : 'lead';
    $bind['priority'] = array_key_exists((string) $bind['priority'], DIGITAL_SERVICE_PRIORITIES) ? $bind['priority'] : 'normal';
    $bind['service_type'] = array_key_exists((string) $bind['service_type'], DIGITAL_SERVICE_TYPES) ? $bind['service_type'] : 'outro';
    $bind['progress_mode'] = in_array($bind['progress_mode'], ['manual','checklist'], true) ? $bind['progress_mode'] : 'manual';
    return $bind;
}

function digital_service_duplicate(int $id): int
{
    $service = digital_service_find($id);
    if (!$service) {
        throw new RuntimeException('Projeto não encontrado.');
    }
    $service['project_name'] .= ' (cópia)';
    $service['status'] = 'lead';
    $service['progress'] = 0;
    $service['created_by'] = (int) current_user()['id'];
    $newId = digital_service_create($service);
    foreach (digital_service_tasks($id) as $task) {
        digital_service_task_create($newId, ['title' => $task['title'], 'description' => $task['description'], 'assigned_user_id' => $task['assigned_user_id'], 'due_date' => $task['due_date']]);
    }
    return $newId;
}

function digital_service_archive(int $id, bool $archived): void
{
    $stmt = db()->prepare('UPDATE digital_services SET is_archived = ?, updated_at = NOW() WHERE id = ? AND deleted_at IS NULL');
    $stmt->execute([$archived ? 'true' : 'false', $id]);
    digital_service_history_create($id, $archived ? 'archive' : 'restore', null, ['is_archived' => $archived]);
}

function digital_service_delete(int $id): void
{
    $stmt = db()->prepare('UPDATE digital_services SET deleted_at = NOW(), updated_at = NOW() WHERE id = ?');
    $stmt->execute([$id]);
    digital_service_history_create($id, 'delete', null, null);
}

function digital_service_update_status(int $id, string $status): void
{
    if (!array_key_exists($status, DIGITAL_SERVICE_STATUSES)) {
        return;
    }
    $stmt = db()->prepare('UPDATE digital_services SET status = ?, updated_at = NOW() WHERE id = ? AND deleted_at IS NULL');
    $stmt->execute([$status, $id]);
    digital_service_history_create($id, 'status', null, ['status' => $status]);
}

function digital_service_update_progress(int $id, int $progress): void
{
    $progress = max(0, min(100, $progress));
    $stmt = db()->prepare("UPDATE digital_services SET progress = ?, progress_mode = 'manual', updated_at = NOW() WHERE id = ? AND deleted_at IS NULL");
    $stmt->execute([$progress, $id]);
    digital_service_history_create($id, 'progress', null, ['progress' => $progress]);
}

function digital_service_set_kanban_card(int $id, int $cardId): void
{
    $stmt = db()->prepare('UPDATE digital_services SET kanban_card_id = ?, updated_at = NOW() WHERE id = ? AND deleted_at IS NULL');
    $stmt->execute([$cardId, $id]);
    digital_service_history_create($id, 'kanban_link', null, ['kanban_card_id' => $cardId]);
}

function digital_service_recalculate_progress(int $id): void
{
    $service = digital_service_find($id);
    if (!$service || $service['progress_mode'] !== 'checklist') {
        return;
    }
    $tasks = digital_service_tasks($id);
    $total = count($tasks);
    $done = count(array_filter($tasks, static fn ($task) => $task['status'] === 'concluida'));
    $progress = $total > 0 ? (int) round(($done / $total) * 100) : 0;
    $stmt = db()->prepare('UPDATE digital_services SET progress = ?, updated_at = NOW() WHERE id = ?');
    $stmt->execute([$progress, $id]);
}

function digital_service_tasks(int $serviceId): array
{
    $stmt = db()->prepare(
        'SELECT t.*, u.name AS assigned_user_name
         FROM digital_service_tasks t
         LEFT JOIN users u ON u.id = t.assigned_user_id
         WHERE t.digital_service_id = ?
         ORDER BY t.order_index, t.id'
    );
    $stmt->execute([$serviceId]);
    return $stmt->fetchAll();
}

function digital_service_task_create(int $serviceId, array $data): int
{
    $stmt = db()->prepare('SELECT COALESCE(MAX(order_index) + 1, 0) FROM digital_service_tasks WHERE digital_service_id = ?');
    $stmt->execute([$serviceId]);
    $position = (int) $stmt->fetchColumn();
    $stmt = db()->prepare(
        'INSERT INTO digital_service_tasks (digital_service_id, title, description, order_index, assigned_user_id, due_date)
         VALUES (?, ?, ?, ?, ?, ?) RETURNING id'
    );
    $stmt->execute([$serviceId, $data['title'], $data['description'] ?? null, $position, $data['assigned_user_id'] ?? null, $data['due_date'] ?? null]);
    digital_service_recalculate_progress($serviceId);
    digital_service_history_create($serviceId, 'task_create', null, ['title' => $data['title']]);
    return (int) $stmt->fetchColumn();
}

function digital_service_task_toggle(int $taskId): void
{
    $stmt = db()->prepare('SELECT * FROM digital_service_tasks WHERE id = ?');
    $stmt->execute([$taskId]);
    $task = $stmt->fetch();
    if (!$task) {
        return;
    }
    $done = $task['status'] !== 'concluida';
    $stmt = db()->prepare('UPDATE digital_service_tasks SET status = ?, completed_at = ?, updated_at = NOW() WHERE id = ?');
    $stmt->execute([$done ? 'concluida' : 'pendente', $done ? date('Y-m-d H:i:s') : null, $taskId]);
    digital_service_recalculate_progress((int) $task['digital_service_id']);
    digital_service_history_create((int) $task['digital_service_id'], 'task_toggle', null, ['task_id' => $taskId, 'status' => $done ? 'concluida' : 'pendente']);
}

function digital_service_apply_template(int $serviceId, string $type): void
{
    $template = DIGITAL_SERVICE_CHECKLIST_TEMPLATES[$type] ?? [];
    foreach ($template as $title) {
        digital_service_task_create($serviceId, ['title' => ucfirst($title)]);
    }
}

function digital_service_links(int $serviceId): array
{
    $stmt = db()->prepare('SELECT * FROM digital_service_links WHERE digital_service_id = ? ORDER BY created_at DESC, id DESC');
    $stmt->execute([$serviceId]);
    return $stmt->fetchAll();
}

function digital_service_link_create(int $serviceId, array $data): int
{
    $stmt = db()->prepare('INSERT INTO digital_service_links (digital_service_id, label, url, type) VALUES (?, ?, ?, ?) RETURNING id');
    $stmt->execute([$serviceId, $data['label'], $data['url'], $data['type'] ?? null]);
    digital_service_history_create($serviceId, 'link_create', null, $data);
    return (int) $stmt->fetchColumn();
}

function digital_service_notes(int $serviceId): array
{
    $stmt = db()->prepare(
        'SELECT n.*, u.name AS user_name
         FROM digital_service_notes n
         LEFT JOIN users u ON u.id = n.user_id
         WHERE n.digital_service_id = ?
         ORDER BY n.created_at DESC'
    );
    $stmt->execute([$serviceId]);
    return $stmt->fetchAll();
}

function digital_service_note_create(int $serviceId, string $note, bool $clientVisible): int
{
    $stmt = db()->prepare('INSERT INTO digital_service_notes (digital_service_id, user_id, note, is_client_visible) VALUES (?, ?, ?, ?) RETURNING id');
    $stmt->execute([$serviceId, current_user()['id'] ?? null, $note, $clientVisible ? 'true' : 'false']);
    digital_service_history_create($serviceId, 'note_create', null, ['client_visible' => $clientVisible]);
    return (int) $stmt->fetchColumn();
}

function digital_service_files(int $serviceId): array
{
    $stmt = db()->prepare('SELECT * FROM digital_service_files WHERE digital_service_id = ? ORDER BY created_at DESC, id DESC');
    $stmt->execute([$serviceId]);
    return $stmt->fetchAll();
}

function digital_service_file_find(int $id): ?array
{
    $stmt = db()->prepare('SELECT * FROM digital_service_files WHERE id = ?');
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}

function digital_service_file_by_hash(int $serviceId, string $hash): ?array
{
    $stmt = db()->prepare('SELECT * FROM digital_service_files WHERE digital_service_id = ? AND file_hash = ? LIMIT 1');
    $stmt->execute([$serviceId, $hash]);
    return $stmt->fetch() ?: null;
}

function digital_service_file_create(int $serviceId, array $data): int
{
    $stmt = db()->prepare(
        'INSERT INTO digital_service_files (digital_service_id, original_name, stored_name, path, mime_type, size_bytes, file_hash, uploaded_by)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?) RETURNING id'
    );
    $stmt->execute([$serviceId, $data['original_name'], $data['stored_name'], $data['path'], $data['mime_type'], $data['size_bytes'], $data['file_hash'], current_user()['id'] ?? null]);
    digital_service_history_create($serviceId, 'file_create', null, ['original_name' => $data['original_name']]);
    return (int) $stmt->fetchColumn();
}

function digital_service_history(int $serviceId): array
{
    $stmt = db()->prepare(
        'SELECT h.*, u.name AS user_name
         FROM digital_service_history h
         LEFT JOIN users u ON u.id = h.user_id
         WHERE h.digital_service_id = ?
         ORDER BY h.created_at DESC, h.id DESC'
    );
    $stmt->execute([$serviceId]);
    return $stmt->fetchAll();
}

function digital_service_history_create(int $serviceId, string $action, mixed $old, mixed $new): void
{
    $stmt = db()->prepare('INSERT INTO digital_service_history (digital_service_id, user_id, action, old_data, new_data) VALUES (?, ?, ?, ?, ?)');
    $stmt->execute([
        $serviceId,
        current_user()['id'] ?? null,
        $action,
        $old !== null ? json_encode($old, JSON_UNESCAPED_UNICODE) : null,
        $new !== null ? json_encode($new, JSON_UNESCAPED_UNICODE) : null,
    ]);
}

function digital_service_summary(): array
{
    $stmt = db()->query(
        "SELECT
            COUNT(*) FILTER (WHERE deleted_at IS NULL AND is_archived = FALSE AND status NOT IN ('concluido','cancelado')) AS active,
            COUNT(*) FILTER (WHERE deleted_at IS NULL AND is_archived = FALSE AND status = 'aguardando_cliente') AS waiting_client,
            COUNT(*) FILTER (WHERE deleted_at IS NULL AND is_archived = FALSE AND due_date < CURRENT_DATE AND status NOT IN ('publicado','concluido','cancelado')) AS overdue,
            COUNT(*) FILTER (WHERE deleted_at IS NULL AND is_archived = FALSE AND status = 'em_desenvolvimento') AS development,
            COUNT(*) FILTER (WHERE deleted_at IS NULL AND is_archived = FALSE AND status = 'publicado') AS published,
            COALESCE(SUM(GREATEST(COALESCE(total_value, 0) - COALESCE(amount_received, 0), 0)) FILTER (WHERE deleted_at IS NULL AND is_archived = FALSE), 0) AS balance_due,
            COALESCE(SUM(COALESCE(total_value, 0)) FILTER (WHERE deleted_at IS NULL AND is_archived = FALSE), 0) AS forecast,
            COALESCE(SUM(COALESCE(recurring_value, 0)) FILTER (WHERE deleted_at IS NULL AND is_archived = FALSE), 0) AS recurring
         FROM digital_services"
    );
    return $stmt->fetch() ?: [];
}

function digital_service_users_all(): array
{
    return db()->query('SELECT id, name FROM users ORDER BY name')->fetchAll();
}

function digital_service_templates_all(): array
{
    return db()->query(
        'SELECT t.*, COUNT(i.id) AS item_count
         FROM digital_service_checklist_templates t
         LEFT JOIN digital_service_checklist_template_items i ON i.template_id = t.id
         GROUP BY t.id
         ORDER BY t.name'
    )->fetchAll();
}

function digital_service_template_find(int $id): ?array
{
    $stmt = db()->prepare('SELECT * FROM digital_service_checklist_templates WHERE id = ?');
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}

function digital_service_template_items(int $templateId): array
{
    $stmt = db()->prepare('SELECT * FROM digital_service_checklist_template_items WHERE template_id = ? ORDER BY order_index, id');
    $stmt->execute([$templateId]);
    return $stmt->fetchAll();
}

function digital_service_template_create(array $data, array $items): int
{
    $stmt = db()->prepare(
        'INSERT INTO digital_service_checklist_templates (name, service_type, description, created_by)
         VALUES (?, ?, ?, ?) RETURNING id'
    );
    $stmt->execute([$data['name'], $data['service_type'] ?? null, $data['description'] ?? null, current_user()['id'] ?? null]);
    $id = (int) $stmt->fetchColumn();
    $insert = db()->prepare(
        'INSERT INTO digital_service_checklist_template_items (template_id, title, description, order_index)
         VALUES (?, ?, ?, ?)'
    );
    foreach (array_values($items) as $index => $title) {
        $title = trim((string) $title);
        if ($title !== '') {
            $insert->execute([$id, mb_substr($title, 0, 220), null, $index]);
        }
    }
    return $id;
}

function digital_service_apply_custom_template(int $serviceId, int $templateId): void
{
    foreach (digital_service_template_items($templateId) as $item) {
        digital_service_task_create($serviceId, [
            'title' => $item['title'],
            'description' => $item['description'],
        ]);
    }
    digital_service_history_create($serviceId, 'template_apply', null, ['template_id' => $templateId]);
}

function digital_service_documents(int $serviceId): array
{
    $stmt = db()->prepare('SELECT * FROM digital_service_documents WHERE digital_service_id = ? ORDER BY created_at DESC, id DESC');
    $stmt->execute([$serviceId]);
    return $stmt->fetchAll();
}

function digital_service_document_find(int $id): ?array
{
    $stmt = db()->prepare(
        'SELECT d.*, ds.project_name, ds.temporary_client_name, c.name AS client_name
         FROM digital_service_documents d
         JOIN digital_services ds ON ds.id = d.digital_service_id
         LEFT JOIN clients c ON c.id = ds.client_id
         WHERE d.id = ?'
    );
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}

function digital_service_document_create(int $serviceId, string $type, ?string $content = null): int
{
    $service = digital_service_find($serviceId);
    if (!$service) {
        throw new RuntimeException('Projeto não encontrado.');
    }
    $label = $type === 'contrato' ? 'Contrato' : 'Proposta';
    $content ??= digital_service_default_document_content($service, $type);
    $stmt = db()->prepare(
        'INSERT INTO digital_service_documents (digital_service_id, type, title, content, created_by)
         VALUES (?, ?, ?, ?, ?) RETURNING id'
    );
    $stmt->execute([$serviceId, $type, $label . ' - ' . $service['project_name'], $content, current_user()['id'] ?? null]);
    $id = (int) $stmt->fetchColumn();
    digital_service_history_create($serviceId, 'document_create', null, ['type' => $type, 'document_id' => $id]);
    return $id;
}

function digital_service_default_document_content(array $service, string $type): string
{
    $client = $service['client_name'] ?: $service['temporary_client_name'] ?: 'Cliente';
    $title = $type === 'contrato' ? 'Contrato de Prestação de Serviços Digitais' : 'Proposta Comercial';
    return $title . "\n\nProjeto: " . $service['project_name'] .
        "\nCliente: " . $client .
        "\nTipo: " . (DIGITAL_SERVICE_TYPES[$service['service_type']] ?? $service['service_type']) .
        "\nDescrição: " . ($service['description'] ?: '-') .
        "\nValor total: " . money($service['total_value']) .
        "\nPrazo estimado: " . br_date($service['due_date']) .
        "\n\nEscopo e condições podem ser ajustados antes da assinatura.";
}

function digital_service_billing_all(int $serviceId): array
{
    $stmt = db()->prepare('SELECT * FROM digital_service_billing WHERE digital_service_id = ? ORDER BY created_at DESC, id DESC');
    $stmt->execute([$serviceId]);
    return $stmt->fetchAll();
}

function digital_service_financial_entry(int $serviceId): ?array
{
    $stmt = db()->prepare("SELECT * FROM financial_entries WHERE reference_type = 'digital_service' AND reference_id = ? ORDER BY id DESC LIMIT 1");
    $stmt->execute([$serviceId]);
    return $stmt->fetch() ?: null;
}

function digital_service_billing_create(int $serviceId, array $data): int
{
    $stmt = db()->prepare(
        'INSERT INTO digital_service_billing (digital_service_id, description, amount, due_date, created_by)
         VALUES (?, ?, ?, ?, ?) RETURNING id'
    );
    $stmt->execute([$serviceId, $data['description'], $data['amount'], $data['due_date'] ?? null, current_user()['id'] ?? null]);
    $id = (int) $stmt->fetchColumn();
    digital_service_history_create($serviceId, 'billing_create', null, ['billing_id' => $id, 'amount' => $data['amount']]);
    return $id;
}

function digital_service_billing_link_financial(int $billingId, int $entryId): void
{
    $stmt = db()->prepare("UPDATE digital_service_billing SET financial_entry_id = ?, status = 'lancado', updated_at = NOW() WHERE id = ?");
    $stmt->execute([$entryId, $billingId]);
}

function digital_service_portal_tokens(int $serviceId): array
{
    $stmt = db()->prepare('SELECT * FROM digital_service_portal_tokens WHERE digital_service_id = ? ORDER BY created_at DESC');
    $stmt->execute([$serviceId]);
    return $stmt->fetchAll();
}

function digital_service_portal_token_create(int $serviceId, ?string $label = null): string
{
    $token = bin2hex(random_bytes(24));
    $stmt = db()->prepare(
        'INSERT INTO digital_service_portal_tokens (digital_service_id, token_hash, label, created_by)
         VALUES (?, ?, ?, ?)'
    );
    $stmt->execute([$serviceId, hash('sha256', $token), $label, current_user()['id'] ?? null]);
    digital_service_history_create($serviceId, 'portal_token_create', null, ['label' => $label]);
    return $token;
}

function digital_service_find_by_portal_token(string $token): ?array
{
    $stmt = db()->prepare(
        "SELECT ds.*
         FROM digital_service_portal_tokens t
         JOIN digital_services ds ON ds.id = t.digital_service_id
         WHERE t.token_hash = ? AND t.revoked_at IS NULL AND (t.expires_at IS NULL OR t.expires_at > NOW()) AND ds.deleted_at IS NULL
         LIMIT 1"
    );
    $stmt->execute([hash('sha256', $token)]);
    return $stmt->fetch() ?: null;
}

function digital_service_automation_logs(int $serviceId): array
{
    $stmt = db()->prepare('SELECT * FROM digital_service_automation_logs WHERE digital_service_id = ? ORDER BY created_at DESC, id DESC');
    $stmt->execute([$serviceId]);
    return $stmt->fetchAll();
}

function digital_service_automation_log_create(int $serviceId, string $action, string $message): int
{
    $stmt = db()->prepare(
        'INSERT INTO digital_service_automation_logs (digital_service_id, action, status, message, created_by)
         VALUES (?, ?, ?, ?, ?) RETURNING id'
    );
    $stmt->execute([$serviceId, $action, 'registrado', $message, current_user()['id'] ?? null]);
    $id = (int) $stmt->fetchColumn();
    digital_service_history_create($serviceId, 'automation_log', null, ['action' => $action, 'message' => $message]);
    return $id;
}
