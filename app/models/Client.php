<?php
/** Model: clients */

function clients_all(?string $search = null): array
{
    $sql = 'SELECT * FROM clients';
    $params = [];

    if ($search) {
        $sql .= ' WHERE (name ILIKE :q OR phone ILIKE :q OR email ILIKE :q OR document ILIKE :q)';
        $params['q'] = "%$search%";
    }

    $stmt = db()->prepare($sql . ' ORDER BY name');
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function client_find(int $id): ?array
{
    $stmt = db()->prepare('SELECT * FROM clients WHERE id = ?');
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}

function client_create(array $data): int
{
    $stmt = db()->prepare(
        'INSERT INTO clients (name, phone, email, document, address, notes)
         VALUES (?, ?, ?, ?, ?, ?) RETURNING id'
    );
    $stmt->execute([
        $data['name'], $data['phone'], $data['email'],
        $data['document'], $data['address'], $data['notes'],
    ]);
    return (int) $stmt->fetchColumn();
}

function client_update(int $id, array $data): void
{
    $stmt = db()->prepare(
        'UPDATE clients
         SET name = ?, phone = ?, email = ?, document = ?, address = ?, notes = ?
         WHERE id = ?'
    );
    $stmt->execute([
        $data['name'], $data['phone'], $data['email'],
        $data['document'], $data['address'], $data['notes'],
        $id,
    ]);
}

function client_delete(int $id): void
{
    $stmt = db()->prepare('DELETE FROM clients WHERE id = ?');
    $stmt->execute([$id]);
}

function client_devices(int $clientId): array
{
    $stmt = db()->prepare('SELECT * FROM devices WHERE client_id = ? ORDER BY created_at DESC');
    $stmt->execute([$clientId]);
    return $stmt->fetchAll();
}

function client_orders(int $clientId): array
{
    $stmt = db()->prepare(
        'SELECT so.*, d.category AS device_category, d.model AS device_model, d.model_code
         FROM service_orders so
         JOIN devices d ON d.id = so.device_id
         WHERE so.client_id = ?
         ORDER BY so.opened_at DESC'
    );
    $stmt->execute([$clientId]);
    return $stmt->fetchAll();
}
