<?php
/** Controller: Serviços Digitais */

require_once APP_PATH . '/models/DigitalService.php';
require_once APP_PATH . '/models/Client.php';
require_once APP_PATH . '/models/Trello.php';
require_once APP_PATH . '/models/FinancialEntry.php';

const DIGITAL_SERVICE_UPLOAD_MAX_BYTES = 25 * 1024 * 1024;

function digital_services_require(string $action, ?array $service = null): void
{
    if (!digital_service_can($action, $service)) {
        flash('error', 'Você não tem permissão para esta ação.');
        redirect('/digital-services');
    }
}

function digital_services_filters(): array
{
    return [
        'q' => trim((string) ($_GET['q'] ?? '')),
        'client_id' => get_int('client_id'),
        'service_type' => trim((string) ($_GET['service_type'] ?? '')),
        'status' => trim((string) ($_GET['status'] ?? '')),
        'priority' => trim((string) ($_GET['priority'] ?? '')),
        'assigned_user_id' => get_int('assigned_user_id'),
        'overdue' => !empty($_GET['overdue']),
        'balance_due' => !empty($_GET['balance_due']),
        'completed' => !empty($_GET['completed']),
        'archived' => trim((string) ($_GET['archived'] ?? '')),
        'period' => trim((string) ($_GET['period'] ?? '')),
        'sort' => trim((string) ($_GET['sort'] ?? 'recent')),
    ];
}

function digital_services_form_data(?array $current = null): array
{
    $serviceType = post_str('service_type', $current['service_type'] ?? 'site_institucional');
    $status = post_str('status', $current['status'] ?? 'lead');
    $priority = post_str('priority', $current['priority'] ?? 'normal');
    $progressMode = post_str('progress_mode', $current['progress_mode'] ?? 'manual');

    return [
        'client_id' => post_int('client_id'),
        'temporary_client_name' => digital_services_limit(post_str('temporary_client_name'), 160),
        'project_name' => digital_services_limit(post_str('project_name'), 180),
        'service_type' => array_key_exists((string) $serviceType, DIGITAL_SERVICE_TYPES) ? $serviceType : 'outro',
        'description' => digital_services_limit(post_str('description'), 6000),
        'status' => array_key_exists((string) $status, DIGITAL_SERVICE_STATUSES) ? $status : 'lead',
        'priority' => array_key_exists((string) $priority, DIGITAL_SERVICE_PRIORITIES) ? $priority : 'normal',
        'progress' => post_int('progress', (int) ($current['progress'] ?? 0)),
        'progress_mode' => in_array($progressMode, ['manual','checklist'], true) ? $progressMode : 'manual',
        'assigned_user_id' => post_int('assigned_user_id'),
        'start_date' => digital_services_date(post_str('start_date')),
        'due_date' => digital_services_date(post_str('due_date')),
        'delivered_at' => digital_services_date(post_str('delivered_at')),
        'next_review_at' => digital_services_date(post_str('next_review_at')),
        'total_value' => post_num('total_value'),
        'down_payment' => post_num('down_payment'),
        'amount_received' => post_num('amount_received'),
        'estimated_cost' => post_num('estimated_cost'),
        'payment_method' => digital_services_limit(post_str('payment_method'), 80),
        'installments' => post_int('installments'),
        'recurring_value' => post_num('recurring_value'),
        'recurring_interval' => digital_services_limit(post_str('recurring_interval'), 30),
        'stack' => digital_services_limit(post_str('stack'), 4000),
        'language' => digital_services_limit(post_str('language'), 2000),
        'database_name' => digital_services_limit(post_str('database_name'), 2000),
        'framework' => digital_services_limit(post_str('framework'), 2000),
        'server_name' => digital_services_limit(post_str('server_name'), 2000),
        'hosting' => digital_services_limit(post_str('hosting'), 2000),
        'domain' => digital_services_limit(post_str('domain'), 2000),
        'ssl_status' => digital_services_limit(post_str('ssl_status'), 40),
        'repository_url' => digital_services_url(post_str('repository_url')),
        'environment' => digital_services_limit(post_str('environment'), 80),
        'version' => digital_services_limit(post_str('version'), 80),
        'site_url' => digital_services_url(post_str('site_url')),
        'admin_url' => digital_services_url(post_str('admin_url')),
        'staging_url' => digital_services_url(post_str('staging_url')),
        'github_url' => digital_services_url(post_str('github_url')),
        'figma_url' => digital_services_url(post_str('figma_url')),
        'documentation_url' => digital_services_url(post_str('documentation_url')),
        'domain_url' => digital_services_url(post_str('domain_url')),
        'hosting_url' => digital_services_url(post_str('hosting_url')),
        'internal_notes' => digital_services_limit(post_str('internal_notes'), 8000),
        'client_notes' => digital_services_limit(post_str('client_notes'), 8000),
        'pending_items' => digital_services_limit(post_str('pending_items'), 8000),
        'blockers' => digital_services_limit(post_str('blockers'), 8000),
        'important_decisions' => digital_services_limit(post_str('important_decisions'), 8000),
        'created_by' => (int) current_user()['id'],
    ];
}

function digital_services_validate(array $data): array
{
    $errors = [];
    if (!$data['project_name']) {
        $errors[] = 'O nome do projeto é obrigatório.';
    }
    if (!$data['client_id'] && !$data['temporary_client_name']) {
        $errors[] = 'Selecione um cliente ou informe um nome temporário.';
    }
    if ($data['progress'] < 0 || $data['progress'] > 100) {
        $errors[] = 'O progresso deve ficar entre 0 e 100.';
    }
    foreach (['total_value','down_payment','amount_received','estimated_cost','recurring_value'] as $moneyField) {
        if ((float) ($data[$moneyField] ?? 0) < 0) {
            $errors[] = 'Valores financeiros não podem ser negativos.';
        }
    }
    if ($data['due_date'] && $data['start_date'] && strtotime($data['due_date']) < strtotime($data['start_date'])) {
        $errors[] = 'O prazo não pode ser anterior ao início.';
    }
    return $errors;
}

function digital_services_limit(?string $value, int $limit): ?string
{
    $value = trim((string) $value);
    return $value === '' ? null : mb_substr($value, 0, $limit);
}

function digital_services_date(?string $value): ?string
{
    return $value && preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) ? $value : null;
}

function digital_services_url(?string $value): ?string
{
    $value = trim((string) $value);
    if ($value === '') {
        return null;
    }
    if (!preg_match('#^https?://#i', $value)) {
        $value = 'https://' . $value;
    }
    return filter_var($value, FILTER_VALIDATE_URL) ? mb_substr($value, 0, 500) : null;
}

function digital_services_index(): void
{
    digital_services_require('view');
    $filters = digital_services_filters();
    render('digital_services/index', [
        'title' => 'Serviços Digitais',
        'filters' => $filters,
        'services' => digital_services_all($filters),
        'summary' => digital_service_summary(),
        'clients' => clients_all(),
        'users' => digital_service_users_all(),
    ]);
}

function digital_services_view(): void
{
    $service = digital_service_find(get_int('id', 0));
    if (!$service || !digital_service_can('view', $service)) {
        flash('error', 'Projeto não encontrado.');
        redirect('/digital-services');
    }
    render('digital_services/view', digital_services_view_payload($service));
}

function digital_services_view_payload(array $service): array
{
    return [
        'title' => $service['project_name'],
        'service' => $service,
        'tasks' => digital_service_tasks((int) $service['id']),
        'links' => digital_service_links((int) $service['id']),
        'files' => digital_service_files((int) $service['id']),
        'notes' => digital_service_notes((int) $service['id']),
        'history' => digital_service_history((int) $service['id']),
        'documents' => digital_service_documents((int) $service['id']),
        'billing' => digital_service_billing_all((int) $service['id']),
        'financialEntry' => digital_service_financial_entry((int) $service['id']),
        'portalTokens' => digital_service_portal_tokens((int) $service['id']),
        'automationLogs' => digital_service_automation_logs((int) $service['id']),
        'templates' => digital_service_templates_all(),
        'clients' => clients_all(),
        'users' => digital_service_users_all(),
    ];
}

function digital_services_create(): void
{
    digital_services_require('create');
    render('digital_services/form', [
        'title' => 'Novo serviço digital',
        'service' => null,
        'clients' => clients_all(),
        'users' => digital_service_users_all(),
    ]);
}

function digital_services_store(): void
{
    require_post('/digital-services');
    digital_services_require('create');
    $data = digital_services_form_data();
    $errors = digital_services_validate($data);
    if ($errors) {
        flash('error', implode(' ', $errors));
        redirect('/digital-services/create');
    }
    $id = digital_service_create($data);
    if (!empty($_POST['apply_template'])) {
        digital_service_apply_template($id, $data['service_type']);
        digital_service_recalculate_progress($id);
    }
    flash('success', 'Serviço digital criado.');
    redirect('/digital-services/view?id=' . $id);
}

function digital_services_edit(): void
{
    $service = digital_service_find(get_int('id', 0));
    if (!$service || !digital_service_can('edit', $service)) {
        flash('error', 'Você não pode editar este projeto.');
        redirect('/digital-services');
    }
    render('digital_services/form', [
        'title' => 'Editar serviço digital',
        'service' => $service,
        'clients' => clients_all(),
        'users' => digital_service_users_all(),
    ]);
}

function digital_services_update(): void
{
    require_post('/digital-services');
    $service = digital_service_find(post_int('id', 0));
    if (!$service || !digital_service_can('edit', $service)) {
        flash('error', 'Você não pode editar este projeto.');
        redirect('/digital-services');
    }
    $data = digital_services_form_data($service);
    $errors = digital_services_validate($data);
    if ($errors) {
        flash('error', implode(' ', $errors));
        redirect('/digital-services/edit?id=' . (int) $service['id']);
    }
    digital_service_update((int) $service['id'], $data);
    if ($data['progress_mode'] === 'checklist') {
        digital_service_recalculate_progress((int) $service['id']);
    }
    flash('success', 'Serviço digital atualizado.');
    redirect('/digital-services/view?id=' . (int) $service['id']);
}

function digital_services_duplicate(): void
{
    require_post('/digital-services');
    $service = digital_service_find(post_int('id', 0));
    if (!$service || !digital_service_can('create')) {
        flash('error', 'Não foi possível duplicar o projeto.');
        redirect('/digital-services');
    }
    $id = digital_service_duplicate((int) $service['id']);
    flash('success', 'Projeto duplicado.');
    redirect('/digital-services/view?id=' . $id);
}

function digital_services_archive(): void
{
    digital_services_toggle_archive(true);
}

function digital_services_restore(): void
{
    digital_services_toggle_archive(false);
}

function digital_services_toggle_archive(bool $archived): void
{
    require_post('/digital-services');
    $service = digital_service_find(post_int('id', 0));
    if (!$service || !digital_service_can('archive', $service)) {
        flash('error', 'Você não pode alterar o arquivamento deste projeto.');
        redirect('/digital-services');
    }
    digital_service_archive((int) $service['id'], $archived);
    flash('success', $archived ? 'Projeto arquivado.' : 'Projeto restaurado.');
    redirect('/digital-services/view?id=' . (int) $service['id']);
}

function digital_services_delete(): void
{
    require_post('/digital-services');
    $service = digital_service_find(post_int('id', 0));
    if (!$service || !digital_service_can('delete', $service)) {
        flash('error', 'Você não pode excluir este projeto.');
        redirect('/digital-services');
    }
    digital_service_delete((int) $service['id']);
    flash('success', 'Projeto excluído.');
    redirect('/digital-services');
}

function digital_services_status(): void
{
    require_post('/digital-services');
    $service = digital_service_find(post_int('id', 0));
    if ($service && digital_service_can('status', $service)) {
        digital_service_update_status((int) $service['id'], post_str('status', 'lead'));
        flash('success', 'Status atualizado.');
    }
    redirect('/digital-services/view?id=' . (int) ($service['id'] ?? 0));
}

function digital_services_progress(): void
{
    require_post('/digital-services');
    $service = digital_service_find(post_int('id', 0));
    if ($service && digital_service_can('status', $service)) {
        digital_service_update_progress((int) $service['id'], post_int('progress', 0));
        flash('success', 'Progresso atualizado.');
    }
    redirect('/digital-services/view?id=' . (int) ($service['id'] ?? 0));
}

function digital_services_checklist(): void
{
    require_post('/digital-services');
    $service = digital_service_find(post_int('id', 0));
    if (!$service || !digital_service_can('checklist', $service)) {
        flash('error', 'Você não pode alterar a checklist.');
        redirect('/digital-services');
    }
    $taskId = post_int('task_id');
    if ($taskId) {
        digital_service_task_toggle($taskId);
        flash('success', 'Checklist atualizada.');
    } else {
        $title = digital_services_limit(post_str('title'), 220);
        if ($title) {
            digital_service_task_create((int) $service['id'], [
                'title' => $title,
                'description' => digital_services_limit(post_str('description'), 2000),
                'assigned_user_id' => post_int('assigned_user_id'),
                'due_date' => digital_services_date(post_str('due_date')),
            ]);
            flash('success', 'Tarefa adicionada.');
        }
    }
    redirect('/digital-services/view?id=' . (int) $service['id']);
}

function digital_services_link(): void
{
    require_post('/digital-services');
    $service = digital_service_find(post_int('id', 0));
    if (!$service || !digital_service_can('links', $service)) {
        flash('error', 'Você não pode adicionar links.');
        redirect('/digital-services');
    }
    $label = digital_services_limit(post_str('label'), 120);
    $url = digital_services_url(post_str('url'));
    if (!$label || !$url) {
        flash('error', 'Informe um rótulo e uma URL válida.');
    } else {
        digital_service_link_create((int) $service['id'], ['label' => $label, 'url' => $url, 'type' => digital_services_limit(post_str('type'), 40)]);
        flash('success', 'Link adicionado.');
    }
    redirect('/digital-services/view?id=' . (int) $service['id']);
}

function digital_services_note(): void
{
    require_post('/digital-services');
    $service = digital_service_find(post_int('id', 0));
    if (!$service || !digital_service_can('notes', $service)) {
        flash('error', 'Você não pode adicionar observações.');
        redirect('/digital-services');
    }
    $note = digital_services_limit(post_str('note'), 6000);
    if ($note) {
        digital_service_note_create((int) $service['id'], $note, !empty($_POST['is_client_visible']));
        flash('success', 'Observação adicionada.');
    }
    redirect('/digital-services/view?id=' . (int) $service['id']);
}

function digital_services_file(): void
{
    require_post('/digital-services');
    $service = digital_service_find(post_int('id', 0));
    if (!$service || !digital_service_can('files', $service)) {
        flash('error', 'Você não pode enviar arquivos.');
        redirect('/digital-services');
    }
    try {
        $upload = digital_services_handle_upload((int) $service['id']);
        digital_service_file_create((int) $service['id'], $upload);
        flash('success', 'Arquivo enviado.');
    } catch (Throwable $exception) {
        flash('error', $exception->getMessage());
    }
    redirect('/digital-services/view?id=' . (int) $service['id']);
}

function digital_services_kanban(): void
{
    require_post('/digital-services');
    $service = digital_service_find(post_int('id', 0));
    if (!$service || !digital_service_can('edit', $service)) {
        flash('error', 'Você não pode vincular Kanban neste projeto.');
        redirect('/digital-services');
    }
    if (!empty($service['kanban_card_id'])) {
        flash('error', 'Este projeto já possui cartão Kanban vinculado.');
        redirect('/digital-services/view?id=' . (int) $service['id']);
    }
    trello_seed_columns();
    $columns = trello_columns_all();
    $columnId = (int) ($columns[0]['id'] ?? 0);
    if (!$columnId) {
        flash('error', 'Não foi possível localizar uma coluna do Kanban.');
        redirect('/digital-services/view?id=' . (int) $service['id']);
    }
    $cardId = trello_card_create($columnId, 'Digital: ' . $service['project_name']);
    trello_card_update($cardId, 'Digital: ' . $service['project_name'], 'Projeto digital #' . $service['id'] . "\n\n" . ($service['description'] ?? ''), null);
    digital_service_set_kanban_card((int) $service['id'], $cardId);
    flash('success', 'Cartão Kanban criado e vinculado.');
    redirect('/digital-services/view?id=' . (int) $service['id']);
}

function digital_services_templates(): void
{
    digital_services_require('create');
    render('digital_services/templates', [
        'title' => 'Templates de checklist',
        'templates' => digital_service_templates_all(),
    ]);
}

function digital_services_template_store(): void
{
    require_post('/digital-services/templates');
    digital_services_require('create');
    $name = digital_services_limit(post_str('name'), 140);
    $items = preg_split('/\R+/', (string) post_str('items', '')) ?: [];
    if (!$name || !array_filter(array_map('trim', $items))) {
        flash('error', 'Informe nome e ao menos uma tarefa.');
        redirect('/digital-services/templates');
    }
    digital_service_template_create([
        'name' => $name,
        'service_type' => post_str('service_type'),
        'description' => digital_services_limit(post_str('description'), 1000),
    ], $items);
    flash('success', 'Template criado.');
    redirect('/digital-services/templates');
}

function digital_services_template_apply(): void
{
    require_post('/digital-services');
    $service = digital_service_find(post_int('id', 0));
    if (!$service || !digital_service_can('checklist', $service)) {
        flash('error', 'Você não pode aplicar template neste projeto.');
        redirect('/digital-services');
    }
    $templateId = post_int('template_id', 0);
    if (digital_service_template_find($templateId)) {
        digital_service_apply_custom_template((int) $service['id'], $templateId);
        flash('success', 'Template aplicado.');
    }
    redirect('/digital-services/view?id=' . (int) $service['id']);
}

function digital_services_finance(): void
{
    require_post('/digital-services');
    $service = digital_service_find(post_int('id', 0));
    if (!$service || !digital_service_can('finance', $service)) {
        flash('error', 'Você não pode gerar financeiro neste projeto.');
        redirect('/digital-services');
    }
    if (digital_service_financial_entry((int) $service['id'])) {
        flash('error', 'Já existe lançamento financeiro vinculado a este projeto.');
        redirect('/digital-services/view?id=' . (int) $service['id']);
    }
    $amount = (float) post_num('amount');
    if ($amount <= 0) {
        $amount = (float) ($service['balance_due'] ?: $service['total_value']);
    }
    if ($amount <= 0) {
        flash('error', 'Informe um valor maior que zero.');
        redirect('/digital-services/view?id=' . (int) $service['id']);
    }
    $entryId = financial_entry_create([
        'type' => 'entrada',
        'description' => 'Serviço digital: ' . $service['project_name'],
        'amount' => $amount,
        'category' => 'Serviços Digitais',
        'reference_type' => 'digital_service',
        'reference_id' => (int) $service['id'],
        'entry_date' => date('Y-m-d'),
    ]);
    $billingId = digital_service_billing_create((int) $service['id'], [
        'description' => 'Cobrança gerada para ' . $service['project_name'],
        'amount' => $amount,
        'due_date' => $service['due_date'] ?: date('Y-m-d'),
    ]);
    digital_service_billing_link_financial($billingId, $entryId);
    flash('success', 'Lançamento financeiro criado.');
    redirect('/digital-services/view?id=' . (int) $service['id']);
}

function digital_services_billing(): void
{
    require_post('/digital-services');
    $service = digital_service_find(post_int('id', 0));
    if (!$service || !digital_service_can('finance', $service)) {
        flash('error', 'Você não pode criar cobrança neste projeto.');
        redirect('/digital-services');
    }
    $amount = post_num('amount');
    $description = digital_services_limit(post_str('description'), 255);
    if (!$description || $amount <= 0) {
        flash('error', 'Descrição e valor são obrigatórios.');
    } else {
        digital_service_billing_create((int) $service['id'], [
            'description' => $description,
            'amount' => $amount,
            'due_date' => digital_services_date(post_str('due_date')),
        ]);
        flash('success', 'Cobrança interna criada.');
    }
    redirect('/digital-services/view?id=' . (int) $service['id']);
}

function digital_services_document(): void
{
    require_post('/digital-services');
    $service = digital_service_find(post_int('id', 0));
    if (!$service || !digital_service_can('edit', $service)) {
        flash('error', 'Você não pode gerar documentos neste projeto.');
        redirect('/digital-services');
    }
    $type = post_str('type', 'proposta');
    $type = in_array($type, ['proposta','contrato'], true) ? $type : 'proposta';
    $docId = digital_service_document_create((int) $service['id'], $type);
    flash('success', ucfirst($type) . ' gerada.');
    redirect('/digital-services/document-pdf?id=' . $docId);
}

function digital_services_document_pdf(): void
{
    $document = digital_service_document_find(get_int('id', 0));
    if (!$document) {
        flash('error', 'Documento não encontrado.');
        redirect('/digital-services');
    }
    $service = digital_service_find((int) $document['digital_service_id']);
    if (!$service || !digital_service_can('view', $service)) {
        http_response_code(403);
        exit('Acesso negado.');
    }
    $html = render_html('digital_services/pdf', ['document' => $document, 'service' => $service]);
    require_once BASE_PATH . '/vendor/autoload.php';
    $options = new Dompdf\Options();
    $options->setIsRemoteEnabled(false);
    $options->setDefaultFont('DejaVu Sans');
    $dompdf = new Dompdf\Dompdf($options);
    $dompdf->loadHtml($html, 'UTF-8');
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();
    $dompdf->stream($document['type'] . '-' . $document['id'] . '.pdf', ['Attachment' => false]);
    exit;
}

function digital_services_portal(): void
{
    require_post('/digital-services');
    $service = digital_service_find(post_int('id', 0));
    if (!$service || !digital_service_can('edit', $service)) {
        flash('error', 'Você não pode gerar portal neste projeto.');
        redirect('/digital-services');
    }
    $token = digital_service_portal_token_create((int) $service['id'], post_str('label', 'Portal do cliente'));
    flash('success', 'Portal criado: /portal/digital-service?token=' . $token);
    redirect('/digital-services/view?id=' . (int) $service['id']);
}

function digital_services_automation(): void
{
    require_post('/digital-services');
    $service = digital_service_find(post_int('id', 0));
    if (!$service || !digital_service_can('edit', $service)) {
        flash('error', 'Você não pode registrar automações neste projeto.');
        redirect('/digital-services');
    }
    $action = digital_services_limit(post_str('action'), 80) ?: 'manual';
    $message = digital_services_limit(post_str('message'), 2000) ?: 'Automação registrada manualmente.';
    digital_service_automation_log_create((int) $service['id'], $action, $message);
    flash('success', 'Automação registrada.');
    redirect('/digital-services/view?id=' . (int) $service['id']);
}

function digital_services_file_download(): void
{
    $file = digital_service_file_find(get_int('id', 0));
    if (!$file) {
        http_response_code(404);
        render('errors/404', ['title' => 'Arquivo não encontrado']);
    }
    $service = digital_service_find((int) $file['digital_service_id']);
    if (!$service || !digital_service_can('view', $service)) {
        http_response_code(403);
        exit('Acesso negado.');
    }
    $path = realpath(UPLOADS_PATH . '/' . $file['path']);
    $base = realpath(UPLOADS_PATH . '/digital-services');
    if (!$path || !$base || !str_starts_with($path, $base) || !is_file($path)) {
        http_response_code(404);
        exit('Arquivo indisponível.');
    }
    header('Content-Type: ' . ($file['mime_type'] ?: 'application/octet-stream'));
    header('Content-Length: ' . filesize($path));
    header('Content-Disposition: attachment; filename="' . basename((string) $file['original_name']) . '"');
    readfile($path);
    exit;
}

function digital_services_handle_upload(int $serviceId): array
{
    $file = $_FILES['file'] ?? null;
    if (!$file || $file['error'] === UPLOAD_ERR_NO_FILE) {
        throw new RuntimeException('Selecione um arquivo.');
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Falha no upload.');
    }
    if ((int) $file['size'] <= 0 || (int) $file['size'] > DIGITAL_SERVICE_UPLOAD_MAX_BYTES) {
        throw new RuntimeException('Arquivo fora do limite permitido.');
    }
    $original = basename((string) $file['name']);
    if ($original !== $file['name'] || preg_match('/[\\\\\\/\\x00]/', (string) $file['name'])) {
        throw new RuntimeException('Nome de arquivo inválido.');
    }
    $ext = strtolower(pathinfo($original, PATHINFO_EXTENSION));
    $blocked = ['php','phtml','phar','exe','sh','bat','cmd','js','html','htm','cgi','pl'];
    if ($ext === '' || in_array($ext, $blocked, true)) {
        throw new RuntimeException('Tipo de arquivo bloqueado.');
    }
    $allowed = ['png','jpg','jpeg','webp','gif','pdf','doc','docx','xls','xlsx','ppt','pptx','txt','md','zip','rar','fig','svg'];
    if (!in_array($ext, $allowed, true)) {
        throw new RuntimeException('Extensão não permitida.');
    }
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']) ?: 'application/octet-stream';
    if (str_contains($mime, 'x-php') || str_contains($mime, 'executable')) {
        throw new RuntimeException('MIME type bloqueado.');
    }
    $hash = hash_file('sha256', $file['tmp_name']);
    if (digital_service_file_by_hash($serviceId, $hash)) {
        throw new RuntimeException('Este arquivo já foi enviado neste projeto.');
    }
    $dir = UPLOADS_PATH . '/digital-services/' . $serviceId . '/' . date('Y/m');
    if (!is_dir($dir) && !mkdir($dir, 0775, true)) {
        throw new RuntimeException('Não foi possível preparar o upload.');
    }
    $stored = bin2hex(random_bytes(16)) . '.' . $ext;
    $target = $dir . '/' . $stored;
    $moved = is_uploaded_file($file['tmp_name']) ? move_uploaded_file($file['tmp_name'], $target) : rename($file['tmp_name'], $target);
    if (!$moved) {
        throw new RuntimeException('Não foi possível salvar o arquivo.');
    }
    return [
        'original_name' => $original,
        'stored_name' => $stored,
        'path' => 'digital-services/' . $serviceId . '/' . date('Y/m') . '/' . $stored,
        'mime_type' => $mime,
        'size_bytes' => (int) $file['size'],
        'file_hash' => $hash,
    ];
}
