<?php
/** Controller: ordens de serviço */

require APP_PATH . '/models/ServiceOrder.php';
require APP_PATH . '/models/Client.php';
require APP_PATH . '/models/Device.php';
require APP_PATH . '/models/Knowledge.php';
require APP_PATH . '/controllers/order_pdf.php';

function orders_form_data(): array
{
    $status   = post_str('status', 'aberta');
    $priority = post_str('priority', 'normal');

    return [
        'client_id'      => post_int('client_id'),
        'device_id'      => post_int('device_id'),
        'title'          => post_str('title'),
        'reported_issue' => post_str('reported_issue'),
        'diagnosis'      => post_str('diagnosis'),
        'solution'       => post_str('solution'),
        'status'         => array_key_exists($status, os_statuses()) ? $status : 'aberta',
        'priority'       => array_key_exists($priority, os_priorities()) ? $priority : 'normal',
        'labor_price'    => post_num('labor_price'),
        'parts_price'    => post_num('parts_price'),
        'paid_amount'    => post_num('paid_amount'),
    ];
}

function orders_fast_form_data(?array $order = null): array
{
    $status = post_str('status', (string) ($order['status'] ?? 'aberta'));
    $priority = post_str('priority', (string) ($order['priority'] ?? 'normal'));
    $dueDate = post_str('quick_due_date');

    return [
        'quick_customer_name' => order_limit(post_str('quick_customer_name', 'Cliente balcão'), 160),
        'quick_customer_phone' => order_normalize_phone(post_str('quick_customer_phone')),
        'quick_device_name' => order_limit(post_str('quick_device_name'), 160),
        'quick_brand' => order_limit(post_str('quick_brand'), 80),
        'quick_model' => order_limit(post_str('quick_model'), 120),
        'reported_issue' => order_limit(post_str('reported_issue'), 2000),
        'quick_service_description' => order_limit(post_str('quick_service_description'), 2000),
        'estimated_value' => post_num('estimated_value'),
        'quick_accessories' => order_limit(post_str('quick_accessories'), 1200),
        'quick_password' => order_limit(post_str('quick_password'), 160),
        'quick_notes' => order_limit(post_str('quick_notes'), 2000),
        'diagnosis' => order_limit(post_str('diagnosis'), 2000),
        'solution' => order_limit(post_str('solution'), 2000),
        'quick_due_date' => $dueDate && preg_match('/^\d{4}-\d{2}-\d{2}$/', $dueDate) ? $dueDate : null,
        'status' => array_key_exists((string) $status, os_statuses()) ? $status : 'aberta',
        'priority' => array_key_exists((string) $priority, os_priorities()) ? $priority : 'normal',
    ];
}

function orders_fast_validate(array $data): array
{
    $errors = [];
    if (!$data['quick_customer_name']) {
        $errors[] = 'Nome do cliente é obrigatório.';
    }
    if (!$data['quick_device_name']) {
        $errors[] = 'Equipamento é obrigatório.';
    }
    if (!$data['reported_issue']) {
        $errors[] = 'Problema relatado é obrigatório.';
    }
    if ((float) $data['estimated_value'] < 0) {
        $errors[] = 'Valor estimado inválido.';
    }
    return $errors;
}

function orders_index(): void
{
    $filters = [
        'status' => (string) ($_GET['status'] ?? ''),
        'q'      => trim((string) ($_GET['q'] ?? '')),
        'fast'   => (string) ($_GET['fast'] ?? ''),
    ];
    if (!array_key_exists($filters['status'], os_statuses())) {
        $filters['status'] = '';
    }
    if (!in_array($filters['fast'], ['', '1', '0'], true)) {
        $filters['fast'] = '';
    }

    render('orders/index', [
        'title'   => 'Ordens de Serviço',
        'orders'  => orders_all($filters),
        'filters' => $filters,
    ]);
}

function orders_fast(): void
{
    if (!order_can('fast_create')) {
        http_response_code(403);
        flash('error', 'Você não tem permissão para criar Fast OS.');
        redirect('/orders');
    }

    $id = get_int('id', 0);
    $order = $id ? order_find($id) : null;
    if ($id && (!$order || empty($order['is_fast_order']))) {
        flash('error', 'Fast OS não encontrada.');
        redirect('/orders');
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $postId = post_int('id', 0);
        $data = orders_fast_form_data($order);
        $errors = orders_fast_validate($data);
        if ($errors) {
            flash('error', implode(' ', $errors));
            $order = array_merge($order ?? [], $data);
        } else {
            if ($postId) {
                if (!$order || (int) $order['id'] !== $postId) {
                    flash('error', 'Fast OS inválida.');
                    redirect('/orders');
                }
                order_fast_update((int) $order['id'], $data);
                flash('success', 'Fast OS atualizada.');
                redirect('/orders/view?id=' . $order['id']);
            }
            $newId = order_fast_create($data);
            flash('success', 'Fast OS #' . $newId . ' criada com sucesso.');
            redirect('/orders/view?id=' . $newId);
        }
    }

    render('orders/fast', [
        'title' => $order ? 'Editar Fast OS #' . $order['id'] : 'Fast OS',
        'order' => $order,
    ]);
}

function orders_create(): void
{
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $data = orders_form_data();
        if (!$data['client_id'] || !$data['device_id'] || !$data['title']) {
            flash('error', 'Cliente, equipamento e título são obrigatórios.');
        } else {
            $id = order_create($data);
            flash('success', 'OS #' . $id . ' criada com sucesso.');
            redirect('/orders/view?id=' . $id);
        }
    }
    render('orders/form', [
        'title'   => 'Nova OS',
        'order'   => null,
        'clients' => clients_all(),
        'devices' => devices_all(),
        'preselectedClient' => get_int('client_id'),
        'preselectedDevice' => get_int('device_id'),
    ]);
}

function orders_edit(): void
{
    $order = order_find(get_int('id', 0));
    if (!$order) {
        flash('error', 'OS não encontrada.');
        redirect('/orders');
    }
    if (!empty($order['is_fast_order']) && (!$order['client_id'] || !$order['device_id'])) {
        redirect('/orders/fast?id=' . $order['id']);
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $data = orders_form_data();
        if (!$data['client_id'] || !$data['device_id'] || !$data['title']) {
            flash('error', 'Cliente, equipamento e título são obrigatórios.');
        } else {
            order_update((int) $order['id'], $data);
            flash('success', 'OS atualizada.');
            redirect('/orders/view?id=' . $order['id']);
        }
    }

    render('orders/form', [
        'title'   => 'Editar OS #' . $order['id'],
        'order'   => $order,
        'clients' => clients_all(),
        'devices' => devices_all(),
        'preselectedClient' => null,
        'preselectedDevice' => null,
    ]);
}

function orders_view(): void
{
    $order = order_find(get_int('id', 0));
    if (!$order) {
        flash('error', 'OS não encontrada.');
        redirect('/orders');
    }

    $newPortalToken = null;
    if (($_SESSION['last_portal_token_order_id'] ?? null) === (int) $order['id']) {
        $newPortalToken = $_SESSION['last_portal_token'] ?? null;
        unset($_SESSION['last_portal_token'], $_SESSION['last_portal_token_order_id']);
    }

    render('orders/view', [
        'title' => 'OS #' . $order['id'],
        'order' => $order,
        'portalTokens' => order_portal_tokens((int) $order['id']),
        'newPortalToken' => $newPortalToken,
        'knowledgeRelated' => knowledge_related_for_device([
            'brand' => $order['device_brand'] ?: 'Apple',
            'model' => $order['device_model'] ?: $order['quick_device_name'] ?: '',
            'model_code' => $order['model_code'] ?: '',
            'category' => $order['device_category'] ?: '',
        ]),
    ]);
}

function orders_portal(): void
{
    require_post('/orders');
    $order = order_find(post_int('id', 0));
    if (!$order) {
        flash('error', 'OS não encontrada.');
        redirect('/orders');
    }
    $token = order_portal_token_create((int) $order['id'], post_str('label', 'Acompanhamento do cliente'));
    $_SESSION['last_portal_token'] = $token;
    $_SESSION['last_portal_token_order_id'] = (int) $order['id'];
    flash('success', 'Link de acompanhamento gerado — copie abaixo antes de sair da página.');
    redirect('/orders/view?id=' . (int) $order['id']);
}

function orders_fast_convert(): void
{
    $order = order_find(get_int('id', post_int('id', 0) ?? 0));
    if (!$order || empty($order['is_fast_order'])) {
        flash('error', 'Fast OS não encontrada.');
        redirect('/orders');
    }
    if (!order_can('fast_convert', $order)) {
        http_response_code(403);
        flash('error', 'Você não tem permissão para converter Fast OS.');
        redirect('/orders/view?id=' . $order['id']);
    }

    $clientMatches = order_fast_client_matches((string) $order['quick_customer_name'], $order['quick_customer_phone']);
    $selectedClientId = post_int('client_id', 0);
    $deviceMatches = $selectedClientId
        ? order_fast_device_matches($selectedClientId, (string) $order['quick_device_name'], $order['quick_brand'], $order['quick_model'])
        : [];

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $clientId = post_int('client_id', 0);
        $deviceId = post_int('device_id', 0);
        $confirmNewClient = post_str('confirm_new_client', '0') === '1';

        if (!$clientId && $clientMatches && !$confirmNewClient) {
            flash('error', 'Existe cliente parecido. Escolha um cliente existente ou confirme a criação de um novo.');
        } else {
            try {
                order_fast_convert((int) $order['id'], [
                    'client_id' => $clientId,
                    'device_id' => $deviceId,
                    'client_name' => post_str('client_name', $order['quick_customer_name']),
                    'client_phone' => post_str('client_phone', $order['quick_customer_phone']),
                    'device_name' => post_str('device_name', $order['quick_device_name']),
                    'brand' => post_str('brand', $order['quick_brand']),
                    'model' => post_str('model', $order['quick_model']),
                ]);
                flash('success', 'Fast OS convertida e vinculada ao cadastro completo.');
                redirect('/orders/view?id=' . $order['id']);
            } catch (RuntimeException $exception) {
                flash('error', $exception->getMessage());
            }
        }
    }

    render('orders/fast_convert', [
        'title' => 'Converter Fast OS #' . $order['id'],
        'order' => $order,
        'clientMatches' => $clientMatches,
        'deviceMatches' => $deviceMatches,
        'selectedClientId' => $selectedClientId,
    ]);
}

function orders_status(): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        redirect('/orders');
    }
    $id     = post_int('id', 0);
    $status = post_str('status', '');

    if ($id && array_key_exists($status, os_statuses())) {
        order_set_status($id, $status);
        if ($status === 'entregue' && post_str('create_knowledge', '0') === '1') {
            $order = order_find($id);
            if ($order) {
                $repairId = knowledge_repair_from_order($order, (int) current_user()['id']);
                if (post_str('create_article', '0') === '1') {
                    $articleId = knowledge_article_draft_from_order($order, (int) current_user()['id']);
                    flash('success', 'OS entregue, reparo estruturado salvo e artigo rascunho criado.');
                    redirect('/knowledge/articles/edit?id=' . $articleId);
                }
                flash('success', 'OS entregue e reparo estruturado salvo no Centro de Conhecimento.');
                redirect('/knowledge/repairs/view?id=' . $repairId);
            }
        }
        flash('success', 'Status alterado para "' . status_label($status) . '".');
    }
    redirect('/orders/view?id=' . $id);
}

function orders_pay(): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        redirect('/orders');
    }
    $id     = post_int('id', 0);
    $amount = post_num('amount');

    if ($id && $amount > 0) {
        order_register_payment($id, $amount);
        flash('success', 'Pagamento de ' . money($amount) . ' registrado e lançado no financeiro.');
    } else {
        flash('error', 'Informe um valor válido.');
    }
    redirect('/orders/view?id=' . $id);
}

function orders_delete(): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        redirect('/orders');
    }
    order_delete(post_int('id', 0));
    flash('success', 'OS excluída.');
    redirect('/orders');
}
