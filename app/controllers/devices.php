<?php
/** Controller: equipamentos */

require APP_PATH . '/models/Device.php';
require APP_PATH . '/models/Client.php';

function devices_form_data(): array
{
    return [
        'client_id'       => post_int('client_id'),
        'category'        => post_str('category', 'MacBook'),
        'brand'           => post_str('brand', 'Apple'),
        'model'           => post_str('model'),
        'model_code'      => strtoupper((string) post_str('model_code', '')) ?: null,
        'serial_number'   => post_str('serial_number'),
        'year'            => post_int('year'),
        'processor'       => post_str('processor'),
        'memory'          => post_str('memory'),
        'storage'         => post_str('storage'),
        'condition_notes' => post_str('condition_notes'),
    ];
}

function devices_index(): void
{
    $filters = [
        'q'         => trim((string) ($_GET['q'] ?? '')),
        'client_id' => get_int('client_id'),
    ];
    render('devices/index', [
        'title'   => 'Equipamentos',
        'devices' => devices_all($filters),
        'filters' => $filters,
        'clients' => clients_all(),
    ]);
}

function devices_create(): void
{
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $data = devices_form_data();
        if (!$data['client_id'] || !$data['category']) {
            flash('error', 'Cliente e categoria são obrigatórios.');
        } else {
            device_create($data);
            flash('success', 'Equipamento cadastrado.');
            redirect('/devices');
        }
    }
    render('devices/form', [
        'title'   => 'Novo equipamento',
        'device'  => null,
        'clients' => clients_all(),
        'preselectedClient' => get_int('client_id'),
    ]);
}

function devices_edit(): void
{
    $device = device_find(get_int('id', 0));
    if (!$device) {
        flash('error', 'Equipamento não encontrado.');
        redirect('/devices');
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $data = devices_form_data();
        if (!$data['client_id'] || !$data['category']) {
            flash('error', 'Cliente e categoria são obrigatórios.');
        } else {
            device_update((int) $device['id'], $data);
            flash('success', 'Equipamento atualizado.');
            redirect('/devices');
        }
    }

    render('devices/form', [
        'title'   => 'Editar equipamento',
        'device'  => $device,
        'clients' => clients_all(),
        'preselectedClient' => null,
    ]);
}

function devices_delete(): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        redirect('/devices');
    }
    try {
        device_delete(post_int('id', 0));
        flash('success', 'Equipamento excluído.');
    } catch (PDOException) {
        flash('error', 'Não foi possível excluir: o equipamento possui ordens de serviço vinculadas.');
    }
    redirect('/devices');
}
