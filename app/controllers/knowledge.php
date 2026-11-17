<?php
/** Controller: Centro de Conhecimento */

require APP_PATH . '/models/Knowledge.php';
require APP_PATH . '/models/ServiceOrder.php';
require APP_PATH . '/models/Client.php';
require APP_PATH . '/models/Device.php';
require APP_PATH . '/controllers/schematics.php';

const KNOWLEDGE_UPLOAD_MAX_BYTES = 60 * 1024 * 1024;

function knowledge_require_action(string $action, ?array $record = null, string $redirectTo = '/knowledge'): void
{
    if (!knowledge_can($action, $record)) {
        flash('error', 'Você não tem permissão para esta ação.');
        redirect($redirectTo);
    }
}

function knowledge_status_from_action(?string $requested, string $fallback = 'rascunho'): string
{
    $action = post_str('form_action', '');
    $status = $requested ?: $fallback;
    if ($action === 'save_draft') {
        $status = 'rascunho';
    } elseif ($action === 'publish') {
        $status = 'publicado';
    } elseif ($action === 'archive') {
        $status = 'arquivado';
    }
    return in_array($status, KNOWLEDGE_ARTICLE_STATUSES, true) ? $status : 'rascunho';
}

function knowledge_article_form_data(?array $current = null): array
{
    $status = knowledge_status_from_action(post_str('status', $current['status'] ?? 'rascunho'), $current['status'] ?? 'rascunho');
    if ($status === 'publicado' && !knowledge_can('article_publish', $current)) {
        $status = 'rascunho';
    }
    if ($status === 'arquivado' && !knowledge_can('article_archive', $current)) {
        $status = $current['status'] ?? 'rascunho';
    }
    $type = post_str('type', 'artigo');
    $difficulty = post_str('difficulty', 'media');
    return [
        'manufacturer_id' => post_int('manufacturer_id'),
        'model_id' => post_int('model_id'),
        'title' => post_str('title'),
        'slug' => post_str('slug'),
        'type' => in_array($type, KNOWLEDGE_ARTICLE_TYPES, true) ? $type : 'artigo',
        'status' => $status,
        'category' => post_str('category'),
        'summary' => post_str('summary'),
        'symptoms' => post_str('symptoms'),
        'diagnosis' => post_str('diagnosis'),
        'procedure' => post_str('procedure'),
        'solution' => post_str('solution'),
        'tools' => post_str('tools'),
        'parts_used' => post_str('parts_used'),
        'avg_time_minutes' => post_int('avg_time_minutes'),
        'difficulty' => in_array($difficulty, KNOWLEDGE_DIFFICULTIES, true) ? $difficulty : 'media',
        'notes' => post_str('notes'),
        'content' => post_str('content'),
        'tags' => post_str('tags'),
        'favorite' => isset($_POST['favorite']),
        'author_user_id' => (int) current_user()['id'],
        'source_type' => post_int('source_order_id') ? 'service_order' : null,
        'source_id' => post_int('source_order_id'),
    ];
}

function knowledge_validate_article(array $data): array
{
    $errors = [];
    if (!$data['title']) {
        $errors[] = 'O título do artigo é obrigatório.';
    }
    if ($data['title'] && mb_strlen($data['title']) > 180) {
        $errors[] = 'O título deve ter no máximo 180 caracteres.';
    }
    if ($data['slug'] && !preg_match('/^[a-z0-9-]{2,220}$/', $data['slug'])) {
        $errors[] = 'O slug deve conter apenas letras minúsculas, números e hífens.';
    }
    if ($data['avg_time_minutes'] !== null && $data['avg_time_minutes'] < 0) {
        $errors[] = 'O tempo médio não pode ser negativo.';
    }
    return $errors;
}

function knowledge_repair_form_data(?array $current = null): array
{
    $status = knowledge_status_from_action(post_str('status', $current['status'] ?? 'rascunho'), $current['status'] ?? 'rascunho');
    if ($status === 'publicado' && !knowledge_can('article_publish', $current)) {
        $status = 'rascunho';
    }
    $result = post_str('result', 'resolvido');
    return [
        'manufacturer_id' => post_int('manufacturer_id'),
        'model_id' => post_int('model_id'),
        'service_order_id' => post_int('service_order_id'),
        'client_id' => post_int('client_id'),
        'device_id' => post_int('device_id'),
        'title' => post_str('title'),
        'problem' => post_str('problem'),
        'symptoms' => post_str('symptoms'),
        'diagnosis' => post_str('diagnosis'),
        'tests_performed' => post_str('tests_performed'),
        'solution' => post_str('solution'),
        'parts' => post_str('parts'),
        'tools' => post_str('tools'),
        'time_minutes' => post_int('time_minutes'),
        'cost' => post_num('cost'),
        'observations' => post_str('observations'),
        'result' => in_array($result, KNOWLEDGE_REPAIR_RESULTS, true) ? $result : 'resolvido',
        'repair_date' => post_str('repair_date', date('Y-m-d')),
        'tags' => post_str('tags'),
        'status' => $status,
        'author_user_id' => (int) current_user()['id'],
    ];
}

function knowledge_validate_repair(array $data): array
{
    $errors = [];
    if (!$data['title']) {
        $errors[] = 'O título do reparo é obrigatório.';
    }
    if ($data['title'] && mb_strlen($data['title']) > 180) {
        $errors[] = 'O título deve ter no máximo 180 caracteres.';
    }
    if ($data['time_minutes'] !== null && $data['time_minutes'] < 0) {
        $errors[] = 'O tempo gasto não pode ser negativo.';
    }
    if ((float) $data['cost'] < 0) {
        $errors[] = 'O custo não pode ser negativo.';
    }
    return $errors;
}

function knowledge_filters(): array
{
    $period = (string) ($_GET['period'] ?? '');
    $periodFrom = null;
    if ($period !== '' && preg_match('/^\d{4}-\d{2}$/', $period)) {
        $periodFrom = $period . '-01';
    }
    return [
        'q' => trim((string) ($_GET['q'] ?? '')),
        'type' => trim((string) ($_GET['type'] ?? '')),
        'manufacturer_id' => get_int('manufacturer_id'),
        'model_id' => get_int('model_id'),
        'category' => trim((string) ($_GET['category'] ?? '')),
        'status' => trim((string) ($_GET['status'] ?? '')),
        'favorite' => !empty($_GET['favorite']),
        'period' => $period,
        'period_from' => $periodFrom,
    ];
}

function knowledge_index(): void
{
    $filters = knowledge_filters();
    $query = $filters['q'];
    render('knowledge/index', [
        'title' => 'Centro de Conhecimento',
        'query' => $query,
        'filters' => $filters,
        'stats' => knowledge_dashboard_stats(),
        'sections' => KNOWLEDGE_SECTION_LABELS,
        'results' => $query ? knowledge_search($query, 40, $filters) : [],
        'latest' => knowledge_latest_items(),
        'recentSearches' => knowledge_recent_searches(),
        'frequentSearches' => knowledge_frequent_searches(),
        'manufacturers' => knowledge_manufacturers_all(),
        'models' => knowledge_models_all(),
    ]);
}

function knowledge_articles(): void
{
    $filters = knowledge_filters();
    render('knowledge/articles/index', [
        'title' => 'Artigos',
        'filters' => $filters,
        'articles' => knowledge_articles_all($filters),
        'manufacturers' => knowledge_manufacturers_all(),
        'models' => knowledge_models_all(),
    ]);
}

function knowledge_articles_view(): void
{
    $article = knowledge_article_find(get_int('id', 0));
    if (!$article) {
        flash('error', 'Artigo não encontrado.');
        redirect('/knowledge/articles');
    }
    knowledge_article_increment_view((int) $article['id']);
    render('knowledge/articles/view', [
        'title' => $article['title'],
        'article' => $article,
        'history' => knowledge_article_history_all((int) $article['id']),
    ]);
}

function knowledge_articles_create(): void
{
    knowledge_require_action('article_create');
    $article = knowledge_article_prefill_from_order(get_int('source_order_id'));
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $data = knowledge_article_form_data();
        $errors = knowledge_validate_article($data);
        if ($errors) {
            flash('error', implode(' ', $errors));
            $article = $data;
        } else {
            $id = knowledge_article_create($data);
            if (!empty($data['source_id']) && $data['source_type'] === 'service_order') {
                knowledge_order_link((int) $data['source_id'], 'article', $id);
            }
            flash('success', $data['status'] === 'publicado' ? 'Artigo publicado.' : 'Artigo salvo como rascunho.');
            redirect('/knowledge/articles/view?id=' . $id);
        }
    }
    render('knowledge/articles/form', [
        'title' => 'Novo artigo',
        'article' => $article,
        'manufacturers' => knowledge_manufacturers_all(),
        'models' => knowledge_models_all(),
    ]);
}

function knowledge_article_prefill_from_order(?int $orderId): ?array
{
    if (!$orderId) {
        return null;
    }
    $order = order_find($orderId);
    if (!$order) {
        return null;
    }
    $modelId = knowledge_model_upsert_for_device($order['device_brand'] ?? 'Apple', $order['device_model'] ?? $order['quick_device_name'] ?? null, $order['model_code'] ?? null, $order['device_category'] ?? null);
    $model = $modelId ? knowledge_model_find($modelId) : null;
    return [
        'manufacturer_id' => $model['manufacturer_id'] ?? null,
        'model_id' => $modelId,
        'title' => 'Rascunho técnico - OS #' . $order['id'] . ' - ' . $order['title'],
        'type' => 'procedimento',
        'status' => 'rascunho',
        'category' => $order['device_category'] ?? null,
        'summary' => $order['reported_issue'],
        'symptoms' => $order['reported_issue'],
        'diagnosis' => $order['diagnosis'],
        'procedure' => $order['solution'],
        'solution' => $order['solution'],
        'content' => "## Problema\n" . trim((string) $order['reported_issue']) . "\n\n## Diagnóstico\n" . trim((string) $order['diagnosis']) . "\n\n## Solução\n" . trim((string) $order['solution']),
        'tags' => trim(($order['model_code'] ?? '') . ', os-' . $order['id'], ', '),
        'source_order_id' => (int) $order['id'],
    ];
}

function knowledge_articles_edit(): void
{
    $article = knowledge_article_find(get_int('id', 0));
    if (!$article) {
        flash('error', 'Artigo não encontrado.');
        redirect('/knowledge/articles');
    }
    knowledge_require_action('article_edit', $article, '/knowledge/articles/view?id=' . (int) $article['id']);
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $data = knowledge_article_form_data($article);
        $errors = knowledge_validate_article($data);
        if ($errors) {
            flash('error', implode(' ', $errors));
            $article = array_merge($article, $data);
        } else {
            knowledge_article_update((int) $article['id'], $data);
            flash('success', 'Artigo atualizado.');
            redirect('/knowledge/articles/view?id=' . $article['id']);
        }
    }
    render('knowledge/articles/form', [
        'title' => 'Editar artigo',
        'article' => $article,
        'manufacturers' => knowledge_manufacturers_all(),
        'models' => knowledge_models_all(),
    ]);
}

function knowledge_articles_archive(): void
{
    require_post('/knowledge/articles');
    $article = knowledge_article_find(post_int('id', 0));
    if (!$article) {
        flash('error', 'Artigo não encontrado.');
        redirect('/knowledge/articles');
    }
    knowledge_require_action('article_archive', $article, '/knowledge/articles/view?id=' . (int) $article['id']);
    knowledge_article_archive((int) $article['id'], (int) current_user()['id']);
    flash('success', 'Artigo arquivado.');
    redirect('/knowledge/articles');
}

function knowledge_articles_restore(): void
{
    require_post('/knowledge/articles');
    $article = knowledge_article_find(post_int('id', 0));
    if (!$article) {
        flash('error', 'Artigo não encontrado.');
        redirect('/knowledge/articles');
    }
    knowledge_require_action('article_edit', $article, '/knowledge/articles/view?id=' . (int) $article['id']);
    knowledge_article_restore((int) $article['id'], (int) current_user()['id']);
    flash('success', 'Artigo restaurado como rascunho.');
    redirect('/knowledge/articles/view?id=' . (int) $article['id']);
}

function knowledge_articles_delete(): void
{
    require_post('/knowledge/articles');
    $article = knowledge_article_find(post_int('id', 0));
    if (!$article) {
        flash('error', 'Artigo não encontrado.');
        redirect('/knowledge/articles');
    }
    knowledge_require_action('article_delete', $article, '/knowledge/articles/view?id=' . (int) $article['id']);
    knowledge_article_delete((int) $article['id'], (int) current_user()['id']);
    flash('success', 'Artigo removido da listagem ativa.');
    redirect('/knowledge/articles');
}

function knowledge_articles_restore_version(): void
{
    require_post('/knowledge/history');
    $history = knowledge_article_history_find(post_int('history_id', 0));
    if (!$history) {
        flash('error', 'Versão não encontrada.');
        redirect('/knowledge/history');
    }
    knowledge_require_action('history_restore', $history, '/knowledge/articles/view?id=' . (int) $history['article_id']);
    $articleId = knowledge_article_restore_history((int) $history['id'], (int) current_user()['id']);
    flash('success', 'Versão restaurada.');
    redirect('/knowledge/articles/view?id=' . $articleId);
}

function knowledge_favorite(): void
{
    require_post('/knowledge');
    knowledge_require_action('favorite');
    $type = post_str('target_type', '');
    $id = post_int('target_id', 0);
    if (!$id || !in_array($type, ['article','repair','asset','schematic','model','manufacturer'], true)) {
        flash('error', 'Favorito inválido.');
        redirect('/knowledge');
    }
    $active = knowledge_favorite_toggle((int) current_user()['id'], $type, $id);
    flash('success', $active ? 'Adicionado aos favoritos.' : 'Removido dos favoritos.');
    redirect((string) ($_POST['back'] ?? '/knowledge'));
}

function knowledge_repairs(): void
{
    $filters = knowledge_filters();
    render('knowledge/repairs/index', [
        'title' => 'Reparos',
        'q' => $filters['q'],
        'filters' => $filters,
        'repairs' => knowledge_repairs_all($filters),
        'manufacturers' => knowledge_manufacturers_all(),
        'models' => knowledge_models_all(),
    ]);
}

function knowledge_repairs_view(): void
{
    $repair = knowledge_repair_find(get_int('id', 0));
    if (!$repair) {
        flash('error', 'Reparo não encontrado.');
        redirect('/knowledge/repairs');
    }
    render('knowledge/repairs/view', [
        'title' => $repair['title'],
        'repair' => $repair,
        'assets' => knowledge_assets_by_category('foto', null),
    ]);
}

function knowledge_repair_prefill_from_order(?int $orderId): ?array
{
    if (!$orderId) {
        return null;
    }
    $order = order_find($orderId);
    if (!$order) {
        return null;
    }
    $modelId = knowledge_model_upsert_for_device($order['device_brand'] ?? 'Apple', $order['device_model'], $order['model_code'], $order['device_category']);
    $model = $modelId ? knowledge_model_find($modelId) : null;
    return [
        'manufacturer_id' => $model['manufacturer_id'] ?? null,
        'model_id' => $modelId,
        'service_order_id' => (int) $order['id'],
        'client_id' => $order['client_id'] ? (int) $order['client_id'] : null,
        'device_id' => $order['device_id'] ? (int) $order['device_id'] : null,
        'title' => 'Reparo OS #' . $order['id'] . ' — ' . $order['title'],
        'problem' => $order['reported_issue'],
        'symptoms' => $order['reported_issue'],
        'diagnosis' => $order['diagnosis'],
        'solution' => $order['solution'],
        'cost' => $order['total_price'],
        'repair_date' => date('Y-m-d'),
        'tags' => trim(($order['model_code'] ?? '') . ', os-' . $order['id'], ', '),
        'status' => 'rascunho',
        'result' => 'resolvido',
    ];
}

function knowledge_repairs_create(): void
{
    knowledge_require_action('repair_create');
    $repair = knowledge_repair_prefill_from_order(get_int('order_id'));
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $data = knowledge_repair_form_data();
        $errors = knowledge_validate_repair($data);
        if ($errors) {
            flash('error', implode(' ', $errors));
            $repair = $data;
        } else {
            $id = knowledge_repair_create($data);
            if (!empty($data['service_order_id'])) {
                knowledge_order_link((int) $data['service_order_id'], 'repair', $id);
            }
            flash('success', 'Reparo salvo.');
            redirect('/knowledge/repairs/view?id=' . $id);
        }
    }
    render('knowledge/repairs/form', [
        'title' => 'Novo reparo',
        'repair' => $repair,
        'manufacturers' => knowledge_manufacturers_all(),
        'models' => knowledge_models_all(),
        'orders' => orders_all([]),
        'clients' => clients_all(),
        'devices' => devices_all(),
    ]);
}

function knowledge_repairs_edit(): void
{
    $repair = knowledge_repair_find(get_int('id', 0));
    if (!$repair) {
        flash('error', 'Reparo não encontrado.');
        redirect('/knowledge/repairs');
    }
    knowledge_require_action('repair_edit', $repair, '/knowledge/repairs/view?id=' . (int) $repair['id']);
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $data = knowledge_repair_form_data($repair);
        $errors = knowledge_validate_repair($data);
        if ($errors) {
            flash('error', implode(' ', $errors));
            $repair = array_merge($repair, $data);
        } else {
            knowledge_repair_update((int) $repair['id'], $data);
            if (!empty($data['service_order_id'])) {
                knowledge_order_link((int) $data['service_order_id'], 'repair', (int) $repair['id']);
            }
            flash('success', 'Reparo atualizado.');
            redirect('/knowledge/repairs/view?id=' . $repair['id']);
        }
    }
    render('knowledge/repairs/form', [
        'title' => 'Editar reparo',
        'repair' => $repair,
        'manufacturers' => knowledge_manufacturers_all(),
        'models' => knowledge_models_all(),
        'orders' => orders_all([]),
        'clients' => clients_all(),
        'devices' => devices_all(),
    ]);
}

function knowledge_repairs_delete(): void
{
    require_post('/knowledge/repairs');
    $repair = knowledge_repair_find(post_int('id', 0));
    if (!$repair) {
        flash('error', 'Reparo não encontrado.');
        redirect('/knowledge/repairs');
    }
    knowledge_require_action('repair_delete', $repair, '/knowledge/repairs/view?id=' . (int) $repair['id']);
    knowledge_repair_delete((int) $repair['id']);
    flash('success', 'Reparo arquivado.');
    redirect('/knowledge/repairs');
}

function knowledge_repairs_restore(): void
{
    require_post('/knowledge/repairs');
    $repair = knowledge_repair_find(post_int('id', 0));
    if (!$repair) {
        flash('error', 'Reparo não encontrado.');
        redirect('/knowledge/repairs');
    }
    knowledge_require_action('repair_edit', $repair, '/knowledge/repairs/view?id=' . (int) $repair['id']);
    knowledge_repair_restore((int) $repair['id']);
    flash('success', 'Reparo restaurado como rascunho.');
    redirect('/knowledge/repairs/view?id=' . (int) $repair['id']);
}

function knowledge_repairs_convert_article(): void
{
    require_post('/knowledge/repairs');
    $repair = knowledge_repair_find(post_int('id', 0));
    if (!$repair) {
        flash('error', 'Reparo não encontrado.');
        redirect('/knowledge/repairs');
    }
    knowledge_require_action('article_create');
    $articleId = knowledge_repair_convert_to_article((int) $repair['id'], (int) current_user()['id']);
    flash('success', 'Rascunho de artigo criado a partir do reparo.');
    redirect('/knowledge/articles/edit?id=' . $articleId);
}

function knowledge_manufacturers(): void
{
    knowledge_require_action('view');
    render('knowledge/manufacturers/index', [
        'title' => 'Fabricantes',
        'q' => trim((string) ($_GET['q'] ?? '')),
        'manufacturers' => knowledge_manufacturers_search(trim((string) ($_GET['q'] ?? '')) ?: null),
    ]);
}

function knowledge_manufacturers_view(): void
{
    $manufacturer = knowledge_manufacturer_find(get_int('id', 0));
    if (!$manufacturer) {
        flash('error', 'Fabricante não encontrado.');
        redirect('/knowledge/manufacturers');
    }
    render('knowledge/manufacturers/view', [
        'title' => $manufacturer['name'],
        'manufacturer' => $manufacturer,
        'models' => knowledge_models_all(null, (int) $manufacturer['id']),
    ]);
}

function knowledge_manufacturers_create(): void
{
    knowledge_require_action('manufacturer_manage');
    $manufacturer = null;
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $name = post_str('name');
        if (!$name) {
            flash('error', 'O nome do fabricante é obrigatório.');
            $manufacturer = ['name' => ''];
        } else {
            try {
                $id = knowledge_manufacturer_create(['name' => $name]);
                flash('success', 'Fabricante cadastrado.');
                redirect('/knowledge/manufacturers/view?id=' . $id);
            } catch (RuntimeException $exception) {
                flash('error', $exception->getMessage());
                $manufacturer = ['name' => $name];
            }
        }
    }
    render('knowledge/manufacturers/form', ['title' => 'Novo fabricante', 'manufacturer' => $manufacturer]);
}

function knowledge_manufacturers_edit(): void
{
    knowledge_require_action('manufacturer_manage');
    $manufacturer = knowledge_manufacturer_find(get_int('id', 0));
    if (!$manufacturer) {
        flash('error', 'Fabricante não encontrado.');
        redirect('/knowledge/manufacturers');
    }
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $name = post_str('name');
        if (!$name) {
            flash('error', 'O nome do fabricante é obrigatório.');
        } else {
            try {
                knowledge_manufacturer_update((int) $manufacturer['id'], ['name' => $name]);
                flash('success', 'Fabricante atualizado.');
                redirect('/knowledge/manufacturers/view?id=' . $manufacturer['id']);
            } catch (RuntimeException $exception) {
                flash('error', $exception->getMessage());
                $manufacturer['name'] = $name;
            }
        }
    }
    render('knowledge/manufacturers/form', ['title' => 'Editar fabricante', 'manufacturer' => $manufacturer]);
}

function knowledge_model_form_data(): array
{
    return [
        'manufacturer_id' => post_int('manufacturer_id'),
        'parent_id' => post_int('parent_id'),
        'name' => post_str('name'),
        'code' => post_str('code'),
        'description' => post_str('description'),
        'frequent_issues' => post_str('frequent_issues'),
        'symptoms' => post_str('symptoms'),
        'diagnosis' => post_str('diagnosis'),
        'procedures' => post_str('procedures'),
        'comments' => post_str('comments'),
        'observations' => post_str('observations'),
        'tags' => post_str('tags'),
        'family' => post_str('family'),
        'release_year' => post_int('release_year'),
        'release_period' => post_str('release_period'),
        'architecture' => post_str('architecture'),
        'confidence_level' => post_str('confidence_level', 'nao_verificado'),
    ];
}

function knowledge_models(): void
{
    render('knowledge/models/index', [
        'title' => 'Modelos',
        'q' => trim((string) ($_GET['q'] ?? '')),
        'manufacturerId' => get_int('manufacturer_id'),
        'models' => knowledge_models_all(trim((string) ($_GET['q'] ?? '')) ?: null, get_int('manufacturer_id')),
        'manufacturers' => knowledge_manufacturers_all(),
    ]);
}

function knowledge_models_view(): void
{
    $model = knowledge_model_find(get_int('id', 0));
    if (!$model) {
        flash('error', 'Modelo não encontrado.');
        redirect('/knowledge/models');
    }
    $assets = knowledge_assets_for_model((int) $model['id']);
    render('knowledge/models/view', [
        'title' => trim($model['manufacturer_name'] . ' ' . ($model['code'] ?: $model['name'])),
        'model' => $model,
        'articles' => knowledge_articles_all(['model_id' => (int) $model['id']]),
        'repairs' => knowledge_repairs_all(['model_id' => (int) $model['id']]),
        'schematics' => schematics_all($model['code'] ?: $model['name']),
        'assets' => $assets,
        'identifiers' => knowledge_model_identifiers_for_model((int) $model['id']),
        'variants' => knowledge_model_variants_for_model((int) $model['id']),
        'boards' => knowledge_logic_boards_for_model((int) $model['id']),
        'osCompatibility' => knowledge_os_compatibility_for_model((int) $model['id']),
        'servicePrograms' => knowledge_service_programs_for_model((int) $model['id']),
        'sourceEvidence' => knowledge_source_evidence_for_entity('model', (int) $model['id']),
    ]);
}

function knowledge_models_create(): void
{
    knowledge_require_action('model_manage');
    $model = ['manufacturer_id' => get_int('manufacturer_id')];
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $data = knowledge_model_form_data();
        if (!$data['manufacturer_id'] || !$data['name']) {
            flash('error', 'Fabricante e nome do modelo são obrigatórios.');
            $model = $data;
        } else {
            try {
                $id = knowledge_model_create($data);
                flash('success', 'Modelo cadastrado.');
                redirect('/knowledge/models/view?id=' . $id);
            } catch (RuntimeException $exception) {
                flash('error', $exception->getMessage());
                $model = $data;
            }
        }
    }
    render('knowledge/models/form', [
        'title' => 'Novo modelo',
        'model' => $model,
        'manufacturers' => knowledge_manufacturers_all(),
        'models' => knowledge_models_all(),
    ]);
}

function knowledge_models_edit(): void
{
    knowledge_require_action('model_manage');
    $model = knowledge_model_find(get_int('id', 0));
    if (!$model) {
        flash('error', 'Modelo não encontrado.');
        redirect('/knowledge/models');
    }
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $data = knowledge_model_form_data();
        if (!$data['manufacturer_id'] || !$data['name']) {
            flash('error', 'Fabricante e nome do modelo são obrigatórios.');
            $model = array_merge($model, $data);
        } else {
            try {
                knowledge_model_update((int) $model['id'], $data);
                flash('success', 'Modelo atualizado.');
                redirect('/knowledge/models/view?id=' . $model['id']);
            } catch (RuntimeException $exception) {
                flash('error', $exception->getMessage());
                $model = array_merge($model, $data);
            }
        }
    }
    render('knowledge/models/form', [
        'title' => 'Editar modelo',
        'model' => $model,
        'manufacturers' => knowledge_manufacturers_all(),
        'models' => knowledge_models_all(),
    ]);
}

function knowledge_upload_rules(): array
{
    return [
        'foto' => [['jpg','jpeg','png','gif','webp'], ['image/jpeg','image/png','image/gif','image/webp'], 12 * 1024 * 1024],
        'pdf' => [['pdf'], ['application/pdf'], 40 * 1024 * 1024],
        'boardview' => [['brd','bdv','bv','cad','fz','tvw'], ['application/octet-stream','text/plain'], 40 * 1024 * 1024],
        'esquema' => [['pdf','zip'], ['application/pdf','application/zip','application/x-zip-compressed'], 40 * 1024 * 1024],
        'firmware' => [['bin','rom','ipsw','zip','7z','rar'], ['application/octet-stream','application/zip','application/x-zip-compressed','application/x-7z-compressed','application/vnd.rar'], 60 * 1024 * 1024],
        'zip' => [['zip','7z','rar'], ['application/zip','application/x-zip-compressed','application/x-7z-compressed','application/vnd.rar'], 60 * 1024 * 1024],
        'documento' => [['txt','md','doc','docx','xls','xlsx','csv'], ['text/plain','text/markdown','application/msword','application/vnd.openxmlformats-officedocument.wordprocessingml.document','application/vnd.ms-excel','application/vnd.openxmlformats-officedocument.spreadsheetml.sheet','text/csv'], 20 * 1024 * 1024],
        'video' => [['mp4','mov','webm'], ['video/mp4','video/quicktime','video/webm'], 80 * 1024 * 1024],
        'datasheet' => [['pdf'], ['application/pdf'], 30 * 1024 * 1024],
        'arquivo' => [['pdf','txt','md','zip','jpg','jpeg','png','webp'], ['application/pdf','text/plain','text/markdown','application/zip','application/x-zip-compressed','image/jpeg','image/png','image/webp'], 40 * 1024 * 1024],
    ];
}

function knowledge_asset_category_from_section_or_post(?string $section = null): string
{
    $posted = post_str('category', (string) ($_GET['category'] ?? 'arquivo'));
    $category = $section ? knowledge_section_asset_category($section) : $posted;
    return in_array($category, KNOWLEDGE_ASSET_CATEGORIES, true) ? $category : 'arquivo';
}

function knowledge_asset_form_data(string $category): array
{
    return [
        'manufacturer_id' => post_int('manufacturer_id'),
        'model_id' => post_int('model_id'),
        'article_id' => post_int('article_id'),
        'repair_id' => post_int('repair_id'),
        'schematic_id' => post_int('schematic_id'),
        'category' => $category,
        'title' => post_str('title'),
        'external_link' => post_str('external_link'),
        'description' => post_str('description'),
        'tags' => post_str('tags'),
        'user_id' => (int) current_user()['id'],
    ];
}

function knowledge_asset_validate_link(?string $url): bool
{
    if (!$url) {
        return false;
    }
    return (bool) filter_var($url, FILTER_VALIDATE_URL) && preg_match('/^https?:\/\//i', $url);
}

function knowledge_handle_asset_upload(string $category): ?array
{
    $file = $_FILES['asset_file'] ?? null;
    if (!$file || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Falha no upload do arquivo (código ' . $file['error'] . ').');
    }
    if ((int) $file['size'] <= 0 || (int) $file['size'] > KNOWLEDGE_UPLOAD_MAX_BYTES) {
        throw new RuntimeException('Arquivo muito grande ou vazio.');
    }
    $original = basename((string) $file['name']);
    $ext = strtolower(pathinfo($original, PATHINFO_EXTENSION));
    $blocked = ['php','phtml','phar','cgi','pl','py','rb','sh','bash','exe','bat','cmd','com','msi','js','jar','asp','aspx','jsp','html','htm','svg'];
    if (!$ext || in_array($ext, $blocked, true) || str_contains($original, '..') || preg_match('/[\/\\\\\x00-\x1F]/', $original)) {
        throw new RuntimeException('Nome ou extensão de arquivo não permitidos.');
    }
    $rules = knowledge_upload_rules()[$category] ?? knowledge_upload_rules()['arquivo'];
    [$allowedExt, $allowedMime, $maxBytes] = $rules;
    if ((int) $file['size'] > $maxBytes) {
        throw new RuntimeException('Arquivo acima do limite permitido para este tipo.');
    }
    if (!in_array($ext, $allowedExt, true)) {
        throw new RuntimeException('Extensão não permitida para este tipo de arquivo.');
    }
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']) ?: 'application/octet-stream';
    if (!in_array($mime, $allowedMime, true) && !($category === 'boardview' && $mime === 'application/octet-stream')) {
        throw new RuntimeException('MIME type incompatível com o tipo selecionado.');
    }
    $hash = hash_file('sha256', $file['tmp_name']);
    if (knowledge_asset_by_hash($hash)) {
        throw new RuntimeException('Arquivo duplicado: já existe um upload com o mesmo conteúdo.');
    }
    $dir = UPLOADS_PATH . '/knowledge/' . date('Y/m');
    if (!is_dir($dir) && !mkdir($dir, 0750, true)) {
        throw new RuntimeException('Não foi possível preparar a pasta de upload.');
    }
    $stored = 'knowledge_' . date('Ymd_His') . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
    $path = $dir . '/' . $stored;
    if (!move_uploaded_file($file['tmp_name'], $path)) {
        throw new RuntimeException('Não foi possível salvar o arquivo no servidor.');
    }
    @chmod($path, 0640);
    return [
        'original_name' => $original,
        'stored_name' => $stored,
        'file_hash' => $hash,
        'file_path' => 'knowledge/' . date('Y/m') . '/' . $stored,
        'storage_path' => 'knowledge/' . date('Y/m') . '/' . $stored,
        'mime_type' => $mime,
        'size_bytes' => (int) $file['size'],
    ];
}

function knowledge_assets_section(string $section): void
{
    $category = knowledge_section_asset_category($section);
    if (!$category) {
        render('errors/404', ['title' => 'Seção não encontrada']);
    }
    $q = trim((string) ($_GET['q'] ?? ''));
    render('knowledge/assets/index', [
        'title' => KNOWLEDGE_SECTION_LABELS[$section] ?? 'Arquivos',
        'section' => $section,
        'category' => $category,
        'q' => $q,
        'assets' => knowledge_assets_by_category($category, $q ?: null),
    ]);
}

function knowledge_assets_create(): void
{
    knowledge_require_action('asset_upload');
    $category = knowledge_asset_category_from_section_or_post();
    $asset = ['category' => $category];
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $category = knowledge_asset_category_from_section_or_post();
        $data = knowledge_asset_form_data($category);
        try {
            $upload = knowledge_handle_asset_upload($category);
            if (!$upload && !knowledge_asset_validate_link($data['external_link'])) {
                throw new RuntimeException('Envie um arquivo válido ou informe um link externo http(s).');
            }
            if (!$data['title']) {
                $data['title'] = $upload['original_name'] ?? $data['external_link'];
            }
            if ($data['external_link'] && !knowledge_asset_validate_link($data['external_link'])) {
                throw new RuntimeException('Link externo inválido.');
            }
            $id = knowledge_asset_create(array_merge($data, $upload ?? []));
            flash('success', 'Arquivo cadastrado.');
            redirect('/knowledge/assets/view?id=' . $id);
        } catch (RuntimeException $exception) {
            flash('error', $exception->getMessage());
            $asset = $data ?? $asset;
        }
    }
    render('knowledge/assets/form', [
        'title' => 'Novo arquivo',
        'asset' => $asset,
        'manufacturers' => knowledge_manufacturers_all(),
        'models' => knowledge_models_all(),
        'articles' => knowledge_articles_all([]),
        'repairs' => knowledge_repairs_all([]),
        'categories' => KNOWLEDGE_ASSET_CATEGORIES,
    ]);
}

function knowledge_assets_view(): void
{
    $asset = knowledge_asset_find(get_int('id', 0));
    if (!$asset) {
        flash('error', 'Arquivo não encontrado.');
        redirect('/knowledge/files');
    }
    render('knowledge/assets/view', ['title' => $asset['title'], 'asset' => $asset]);
}

function knowledge_assets_file(): void
{
    $asset = knowledge_asset_find(get_int('id', 0));
    if (!$asset || empty($asset['storage_path'])) {
        http_response_code(404);
        exit('Arquivo não encontrado.');
    }
    $path = realpath(UPLOADS_PATH . '/' . $asset['storage_path']);
    $base = realpath(UPLOADS_PATH);
    if (!$path || !$base || !str_starts_with($path, $base . DIRECTORY_SEPARATOR) || !is_file($path)) {
        http_response_code(404);
        exit('Arquivo não encontrado.');
    }
    header('Content-Type: ' . ($asset['mime_type'] ?: 'application/octet-stream'));
    header('Content-Length: ' . filesize($path));
    header('Content-Disposition: attachment; filename="' . str_replace('"', '', $asset['original_name'] ?: $asset['title']) . '"');
    readfile($path);
    exit;
}

function knowledge_assets_delete(): void
{
    require_post('/knowledge/files');
    knowledge_require_action('asset_manage');
    knowledge_asset_delete(post_int('id', 0));
    flash('success', 'Arquivo arquivado.');
    redirect('/knowledge/files');
}

function knowledge_boardviews(): void { knowledge_assets_section('boardviews'); }
function knowledge_files(): void { knowledge_assets_section('files'); }
function knowledge_photos(): void { knowledge_assets_section('photos'); }
function knowledge_videos(): void { knowledge_assets_section('videos'); }
function knowledge_pdfs(): void { knowledge_assets_section('pdfs'); }
function knowledge_links(): void { knowledge_assets_section('links'); }
function knowledge_datasheets(): void { knowledge_assets_section('datasheets'); }
function knowledge_firmware(): void { knowledge_assets_section('firmware'); }

function knowledge_favorites(): void
{
    render('knowledge/favorites', [
        'title' => 'Favoritos',
        'favorites' => knowledge_favorites_for_user((int) current_user()['id']),
    ]);
}

function knowledge_history(): void
{
    $articleId = get_int('article_id');
    render('knowledge/history', [
        'title' => 'Histórico',
        'articleId' => $articleId,
        'history' => $articleId ? knowledge_article_history_all($articleId) : [],
    ]);
}

function knowledge_tags(): void
{
    render('knowledge/simple', [
        'title' => 'Tags',
        'message' => 'Tags são pesquisáveis em artigos, modelos, reparos, esquemas e arquivos. Use vírgulas para separar termos técnicos, códigos de placa, sintomas e peças.',
    ]);
}

function knowledge_schematics(): void { schematics_catalog(); }
function knowledge_schematics_view(): void { schematics_show(); }
function knowledge_schematics_create(): void { schematics_create_inner(); }
function knowledge_schematics_edit(): void { schematics_edit_inner(); }
function knowledge_schematics_file(): void { schematics_file_stream(); }
function knowledge_schematics_delete_file(): void { schematics_delete_file_inner(); }
function knowledge_schematics_delete(): void { schematics_delete_inner(); }

function knowledge_from_order(): void
{
    require_post('/orders');
    knowledge_require_action('repair_create');
    $order = order_find(post_int('order_id', 0));
    if (!$order) {
        flash('error', 'OS não encontrada.');
        redirect('/orders');
    }
    $repairId = knowledge_repair_from_order($order, (int) current_user()['id']);
    $articleId = null;
    if (post_str('create_article', '0') === '1') {
        $articleId = knowledge_article_draft_from_order($order, (int) current_user()['id']);
    }
    flash('success', $articleId ? 'Reparo e artigo rascunho criados.' : 'Reparo estruturado criado.');
    redirect($articleId ? '/knowledge/articles/edit?id=' . $articleId : '/knowledge/repairs/view?id=' . $repairId);
}

// =============================================================
// Expansão: placas lógicas, fontes e fila de revisão.
// =============================================================

function knowledge_boards(): void
{
    $q = trim((string) ($_GET['q'] ?? ''));
    render('knowledge/boards/index', [
        'title' => 'Placas lógicas',
        'q' => $q,
        'boards' => knowledge_logic_boards_all($q ?: null),
    ]);
}

function knowledge_boards_view(): void
{
    $board = knowledge_logic_board_find(get_int('id', 0));
    if (!$board) {
        flash('error', 'Placa não encontrada.');
        redirect('/knowledge/boards');
    }
    render('knowledge/boards/view', [
        'title' => $board['full_code'] ?: $board['board_code'],
        'board' => $board,
        'models' => knowledge_models_for_board((int) $board['id']),
        'sourceEvidence' => knowledge_source_evidence_for_entity('logic_board', (int) $board['id']),
    ]);
}

function knowledge_board_form_data(): array
{
    return [
        'board_code' => post_str('board_code'),
        'revision' => post_str('revision'),
        'full_code' => post_str('full_code'),
        'board_id' => post_str('board_id'),
        'year' => post_int('year'),
        'architecture' => post_str('architecture'),
        'processor' => post_str('processor'),
        'gpu' => post_str('gpu'),
        'memory' => post_str('memory'),
        'description' => post_str('description'),
        'notes' => post_str('notes'),
        'confidence_level' => post_str('confidence_level', 'nao_verificado'),
        'verification_status' => post_str('verification_status', 'unverified'),
    ];
}

function knowledge_boards_create(): void
{
    knowledge_require_action('model_manage', null, '/knowledge/boards');
    $board = [];
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $data = knowledge_board_form_data();
        if (!$data['board_code']) {
            flash('error', 'O código da placa é obrigatório.');
            $board = $data;
        } else {
            $modelId = post_int('model_id');
            $id = knowledge_logic_board_upsert($data);
            if ($modelId) {
                knowledge_model_logic_board_link($modelId, $id);
            }
            flash('success', 'Placa cadastrada.');
            redirect('/knowledge/boards/view?id=' . $id);
        }
    }
    render('knowledge/boards/form', [
        'title' => 'Nova placa lógica',
        'board' => $board,
        'models' => knowledge_models_all(),
    ]);
}

function knowledge_sources(): void
{
    $q = trim((string) ($_GET['q'] ?? ''));
    render('knowledge/sources/index', [
        'title' => 'Fontes',
        'q' => $q,
        'sources' => knowledge_sources_all($q ?: null),
    ]);
}

function knowledge_sources_create(): void
{
    knowledge_require_action('model_manage', null, '/knowledge/sources');
    $source = [];
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $data = [
            'title' => post_str('title'),
            'organization' => post_str('organization'),
            'url' => post_str('url'),
            'canonical_url' => post_str('canonical_url'),
            'source_type' => post_str('source_type'),
            'published_at' => post_str('published_at') ?: null,
            'checked_at' => post_str('checked_at') ?: date('Y-m-d'),
            'language' => post_str('language'),
            'license' => post_str('license'),
            'availability_status' => post_str('availability_status', 'unverified'),
            'confidence_level' => post_str('confidence_level', 'fonte_secundaria'),
            'notes' => post_str('notes'),
        ];
        if (!$data['title'] || !$data['url'] || !preg_match('/^https?:\/\//i', (string) $data['url'])) {
            flash('error', 'Título e URL (http/https) são obrigatórios.');
            $source = $data;
        } else {
            $id = knowledge_source_upsert($data);
            flash('success', 'Fonte cadastrada.');
            redirect('/knowledge/sources');
        }
    }
    render('knowledge/sources/form', ['title' => 'Nova fonte', 'source' => $source]);
}

function knowledge_review_queue(): void
{
    knowledge_require_action('model_manage', null, '/knowledge');
    render('knowledge/review_queue/index', [
        'title' => 'Fila de revisão',
        'items' => knowledge_review_queue_all('pendente'),
    ]);
}

function knowledge_review_queue_resolve(): void
{
    require_post('/knowledge/review-queue');
    knowledge_require_action('model_manage', null, '/knowledge/review-queue');
    $status = post_str('status', 'resolvido') === 'descartado' ? 'descartado' : 'resolvido';
    knowledge_review_queue_set_status(post_int('id', 0), $status, (int) current_user()['id']);
    flash('success', 'Item da fila atualizado.');
    redirect('/knowledge/review-queue');
}
