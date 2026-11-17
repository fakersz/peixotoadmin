<?php
/** Controller: estoque */

require APP_PATH . '/models/Inventory.php';

function inventory_form_data(): array
{
    return [
        'name'              => post_str('name'),
        'category'          => post_str('category'),
        'compatible_models' => strtoupper((string) post_str('compatible_models', '')) ?: null,
        'quantity'          => post_int('quantity', 0),
        'min_quantity'      => post_int('min_quantity', 1),
        'cost_price'        => post_num('cost_price'),
        'sale_price'        => post_num('sale_price'),
        'supplier'          => post_str('supplier'),
        'notes'             => post_str('notes'),
    ];
}

function inventory_index(): void
{
    $search  = trim((string) ($_GET['q'] ?? ''));
    $lowOnly = !empty($_GET['low']);

    render('inventory/index', [
        'title'   => 'Estoque',
        'items'   => inventory_items_all($search ?: null, $lowOnly),
        'search'  => $search,
        'lowOnly' => $lowOnly,
    ]);
}

function inventory_create(): void
{
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $data = inventory_form_data();
        if (!$data['name']) {
            flash('error', 'O nome do item é obrigatório.');
        } else {
            inventory_item_create($data);
            flash('success', 'Item cadastrado no estoque.');
            redirect('/inventory');
        }
    }
    render('inventory/form', ['title' => 'Novo item', 'item' => null]);
}

function inventory_edit(): void
{
    $item = inventory_item_find(get_int('id', 0));
    if (!$item) {
        flash('error', 'Item não encontrado.');
        redirect('/inventory');
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $data = inventory_form_data();
        if (!$data['name']) {
            flash('error', 'O nome do item é obrigatório.');
        } else {
            inventory_item_update((int) $item['id'], $data);
            flash('success', 'Item atualizado.');
            redirect('/inventory');
        }
    }

    render('inventory/form', ['title' => 'Editar item', 'item' => $item]);
}

function inventory_adjust(): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        redirect('/inventory');
    }
    $id    = post_int('id', 0);
    $delta = (int) ($_POST['delta'] ?? 0);
    if ($id && in_array($delta, [-1, 1], true)) {
        inventory_item_adjust($id, $delta);
    }
    redirect('/inventory');
}

function inventory_delete(): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        redirect('/inventory');
    }
    inventory_item_delete(post_int('id', 0));
    flash('success', 'Item excluído do estoque.');
    redirect('/inventory');
}
