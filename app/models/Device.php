<?php
/** Model: devices (equipamentos Apple) */

function devices_all(array $filters = []): array
{
    $sql = 'SELECT d.*, c.name AS client_name
            FROM devices d
            JOIN clients c ON c.id = d.client_id
            WHERE 1 = 1';
    $params = [];

    if (!empty($filters['client_id'])) {
        $sql .= ' AND d.client_id = :client_id';
        $params['client_id'] = $filters['client_id'];
    }
    if (!empty($filters['q'])) {
        $sql .= ' AND (d.model ILIKE :q OR d.model_code ILIKE :q OR d.serial_number ILIKE :q
                       OR d.category ILIKE :q OR c.name ILIKE :q)';
        $params['q'] = '%' . $filters['q'] . '%';
    }

    $sql .= ' ORDER BY d.created_at DESC';
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function device_find(int $id): ?array
{
    $stmt = db()->prepare(
        'SELECT d.*, c.name AS client_name
         FROM devices d JOIN clients c ON c.id = d.client_id
         WHERE d.id = ?'
    );
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}

function device_create(array $data): int
{
    if (!client_find((int) $data['client_id'])) {
        throw new RuntimeException('Cliente inválido.');
    }

    $stmt = db()->prepare(
        'INSERT INTO devices (client_id, category, brand, model, model_code,
                              serial_number, year, processor, memory, storage, condition_notes)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) RETURNING id'
    );
    $stmt->execute([
        $data['client_id'], $data['category'], $data['brand'], $data['model'],
        $data['model_code'], $data['serial_number'], $data['year'],
        $data['processor'], $data['memory'], $data['storage'], $data['condition_notes'],
    ]);
    return (int) $stmt->fetchColumn();
}

function device_update(int $id, array $data): void
{
    if (!client_find((int) $data['client_id'])) {
        throw new RuntimeException('Cliente inválido.');
    }

    $stmt = db()->prepare(
        'UPDATE devices
         SET client_id = ?, category = ?, brand = ?, model = ?, model_code = ?,
             serial_number = ?, year = ?, processor = ?, memory = ?, storage = ?,
             condition_notes = ?
         WHERE id = ?'
    );
    $stmt->execute([
        $data['client_id'], $data['category'], $data['brand'], $data['model'],
        $data['model_code'], $data['serial_number'], $data['year'],
        $data['processor'], $data['memory'], $data['storage'], $data['condition_notes'],
        $id,
    ]);
}

function device_delete(int $id): void
{
    $stmt = db()->prepare('DELETE FROM devices WHERE id = ?');
    $stmt->execute([$id]);
}
