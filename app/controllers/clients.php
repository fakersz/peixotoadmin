<?php
/** Controller: clientes */

require APP_PATH . '/models/Client.php';

function clients_form_data(): array
{
    return [
        'name'     => post_str('name'),
        'phone'    => post_str('phone'),
        'email'    => post_str('email'),
        'document' => post_str('document'),
        'address'  => post_str('address'),
        'notes'    => post_str('notes'),
    ];
}

function clients_index(): void
{
    $search = trim((string) ($_GET['q'] ?? ''));
    render('clients/index', [
        'title'   => 'Clientes',
        'clients' => clients_all($search ?: null),
        'search'  => $search,
    ]);
}

function clients_create(): void
{
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $data = clients_form_data();
        if (!$data['name']) {
            flash('error', 'O nome do cliente é obrigatório.');
        } else {
            $id = client_create($data);
            flash('success', 'Cliente cadastrado com sucesso.');
            redirect('/clients/view?id=' . $id);
        }
    }
    render('clients/form', ['title' => 'Novo cliente', 'client' => null]);
}

function clients_edit(): void
{
    $client = client_find(get_int('id', 0));
    if (!$client) {
        flash('error', 'Cliente não encontrado.');
        redirect('/clients');
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $data = clients_form_data();
        if (!$data['name']) {
            flash('error', 'O nome do cliente é obrigatório.');
        } else {
            client_update((int) $client['id'], $data);
            flash('success', 'Cliente atualizado.');
            redirect('/clients/view?id=' . $client['id']);
        }
    }

    render('clients/form', ['title' => 'Editar cliente', 'client' => $client]);
}

function clients_view(): void
{
    $client = client_find(get_int('id', 0));
    if (!$client) {
        flash('error', 'Cliente não encontrado.');
        redirect('/clients');
    }

    render('clients/view', [
        'title'   => $client['name'],
        'client'  => $client,
        'devices' => client_devices((int) $client['id']),
        'orders'  => client_orders((int) $client['id']),
    ]);
}

function clients_delete(): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        redirect('/clients');
    }
    try {
        client_delete(post_int('id', 0));
        flash('success', 'Cliente excluído.');
    } catch (PDOException) {
        flash('error', 'Não foi possível excluir: o cliente possui ordens de serviço vinculadas.');
    }
    redirect('/clients');
}
