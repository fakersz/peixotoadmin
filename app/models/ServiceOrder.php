<?php
/** Model: service_orders (ordens de serviço) */

function orders_all(array $filters = []): array
{
    $sql = 'SELECT so.*,
                   c.name AS registered_client_name, c.phone AS registered_client_phone,
                   COALESCE(c.name, so.quick_customer_name) AS client_name,
                   COALESCE(c.phone, so.quick_customer_phone) AS client_phone,
                   d.category AS registered_device_category, d.model AS registered_device_model,
                   d.brand AS registered_device_brand, d.model_code AS registered_model_code,
                   d.serial_number AS registered_serial_number,
                   COALESCE(d.category, so.quick_device_name) AS device_category,
                   COALESCE(d.model, so.quick_model) AS device_model,
                   COALESCE(d.brand, so.quick_brand) AS device_brand,
                   d.model_code,
                   d.serial_number
            FROM service_orders so
            LEFT JOIN clients c ON c.id = so.client_id
            LEFT JOIN devices d ON d.id = so.device_id
            WHERE 1 = 1';
    $params = [];

    if (!empty($filters['status'])) {
        $sql .= ' AND so.status = :status';
        $params['status'] = $filters['status'];
    }
    if (($filters['fast'] ?? '') === '1') {
        $sql .= ' AND so.is_fast_order = TRUE';
    } elseif (($filters['fast'] ?? '') === '0') {
        $sql .= ' AND so.is_fast_order = FALSE';
    }
    if (!empty($filters['q'])) {
        $sql .= ' AND (so.title ILIKE :q OR c.name ILIKE :q OR d.model_code ILIKE :q
                       OR d.model ILIKE :q OR d.brand ILIKE :q
                       OR so.quick_customer_name ILIKE :q OR so.quick_customer_phone ILIKE :q
                       OR so.quick_device_name ILIKE :q OR so.quick_brand ILIKE :q
                       OR so.quick_model ILIKE :q OR so.quick_service_description ILIKE :q
                       OR so.reported_issue ILIKE :q OR CAST(so.id AS TEXT) = :id_exact)';
        $params['q'] = '%' . $filters['q'] . '%';
        $params['id_exact'] = trim($filters['q'], '# ');
    }

    $sql .= " ORDER BY CASE so.priority
                  WHEN 'urgente' THEN 0 WHEN 'alta' THEN 1
                  WHEN 'normal' THEN 2 ELSE 3 END,
              so.opened_at DESC";
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function order_find(int $id): ?array
{
    $stmt = db()->prepare(
        'SELECT so.*,
                c.name AS registered_client_name, c.phone AS registered_client_phone,
                COALESCE(c.name, so.quick_customer_name) AS client_name,
                COALESCE(c.phone, so.quick_customer_phone) AS client_phone,
                d.category AS registered_device_category, d.model AS registered_device_model,
                d.brand AS registered_device_brand, d.model_code AS registered_model_code,
                d.serial_number AS registered_serial_number,
                COALESCE(d.category, so.quick_device_name) AS device_category,
                COALESCE(d.model, so.quick_model) AS device_model,
                COALESCE(d.brand, so.quick_brand) AS device_brand,
                d.model_code, d.serial_number
         FROM service_orders so
         LEFT JOIN clients c ON c.id = so.client_id
         LEFT JOIN devices d ON d.id = so.device_id
         WHERE so.id = ?'
    );
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}

/** Deriva o status de pagamento a partir dos valores. */
function order_payment_status(float $total, float $paid): string
{
    if ($total > 0 && $paid >= $total) {
        return 'pago';
    }
    return $paid > 0 ? 'parcial' : 'pendente';
}

function order_create(array $data): int
{
    if (!client_find((int) $data['client_id']) || !device_find((int) $data['device_id'])) {
        throw new RuntimeException('Cliente ou equipamento inválido.');
    }

    $total = $data['labor_price'] + $data['parts_price'];
    $stmt = db()->prepare(
        'INSERT INTO service_orders
            (client_id, device_id, title, reported_issue, diagnosis, solution,
             status, priority, labor_price, parts_price, total_price,
             paid_amount, payment_status)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) RETURNING id'
    );
    $stmt->execute([
        $data['client_id'], $data['device_id'], $data['title'],
        $data['reported_issue'], $data['diagnosis'], $data['solution'],
        $data['status'], $data['priority'],
        $data['labor_price'], $data['parts_price'], $total,
        $data['paid_amount'], order_payment_status($total, $data['paid_amount']),
    ]);
    return (int) $stmt->fetchColumn();
}

function order_update(int $id, array $data): void
{
    if (!client_find((int) $data['client_id']) || !device_find((int) $data['device_id'])) {
        throw new RuntimeException('Cliente ou equipamento inválido.');
    }

    $total = $data['labor_price'] + $data['parts_price'];
    $stmt = db()->prepare(
        'UPDATE service_orders
         SET client_id = ?, device_id = ?, title = ?, reported_issue = ?,
             diagnosis = ?, solution = ?, status = ?, priority = ?,
             labor_price = ?, parts_price = ?, total_price = ?,
             paid_amount = ?, payment_status = ?,
             closed_at = CASE WHEN ? IN (\'entregue\', \'cancelada\')
                              THEN COALESCE(closed_at, NOW()) ELSE NULL END
         WHERE id = ?'
    );
    $stmt->execute([
        $data['client_id'], $data['device_id'], $data['title'],
        $data['reported_issue'], $data['diagnosis'], $data['solution'],
        $data['status'], $data['priority'],
        $data['labor_price'], $data['parts_price'], $total,
        $data['paid_amount'], order_payment_status($total, $data['paid_amount']),
        $data['status'], $id,
    ]);
}

function order_normalize_phone(?string $phone): ?string
{
    $digits = preg_replace('/\D+/', '', (string) $phone);
    if ($digits === '') {
        return null;
    }
    if (str_starts_with($digits, '55') && strlen($digits) > 11) {
        $digits = substr($digits, 2);
    }
    return substr($digits, 0, 11);
}

function order_limit(?string $value, int $limit): ?string
{
    $value = trim((string) $value);
    if ($value === '') {
        return null;
    }
    return mb_substr($value, 0, $limit);
}

function order_fast_title(array $data): string
{
    $service = $data['quick_service_description'] ?: 'Fast OS';
    $device = $data['quick_device_name'] ?: 'Equipamento';
    return mb_substr($service . ' - ' . $device, 0, 160);
}

function order_fast_create(array $data): int
{
    $estimated = max((float) ($data['estimated_value'] ?? 0), 0);
    $status = array_key_exists((string) ($data['status'] ?? ''), os_statuses()) ? $data['status'] : 'aberta';
    $priority = array_key_exists((string) ($data['priority'] ?? ''), os_priorities()) ? $data['priority'] : 'normal';
    $userId = (int) (current_user()['id'] ?? 0) ?: null;

    $stmt = db()->prepare(
        'INSERT INTO service_orders
            (client_id, device_id, title, reported_issue, diagnosis, solution,
             status, priority, labor_price, parts_price, total_price, paid_amount, payment_status,
             is_fast_order, quick_customer_name, quick_customer_phone, quick_device_name,
             quick_brand, quick_model, quick_service_description, quick_accessories,
             quick_password, quick_notes, quick_due_date, fast_order_created_by)
         VALUES
            (NULL, NULL, ?, ?, ?, ?, ?, ?, ?, 0, ?, 0, ?,
             TRUE, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) RETURNING id'
    );
    $stmt->execute([
        order_fast_title($data),
        $data['reported_issue'],
        $data['diagnosis'] ?? null,
        $data['solution'] ?? null,
        $status,
        $priority,
        $estimated,
        $estimated,
        order_payment_status($estimated, 0),
        $data['quick_customer_name'],
        order_normalize_phone($data['quick_customer_phone'] ?? null),
        $data['quick_device_name'],
        $data['quick_brand'] ?? null,
        $data['quick_model'] ?? null,
        $data['quick_service_description'] ?? null,
        $data['quick_accessories'] ?? null,
        $data['quick_password'] ?? null,
        $data['quick_notes'] ?? null,
        $data['quick_due_date'] ?? null,
        $userId,
    ]);
    return (int) $stmt->fetchColumn();
}

function order_fast_update(int $id, array $data): void
{
    $order = order_find($id);
    if (!$order || empty($order['is_fast_order'])) {
        throw new RuntimeException('Fast OS inválida.');
    }

    $estimated = max((float) ($data['estimated_value'] ?? 0), 0);
    $status = array_key_exists((string) ($data['status'] ?? ''), os_statuses()) ? $data['status'] : 'aberta';
    $priority = array_key_exists((string) ($data['priority'] ?? ''), os_priorities()) ? $data['priority'] : 'normal';

    $stmt = db()->prepare(
        'UPDATE service_orders
         SET title = ?, reported_issue = ?, diagnosis = ?, solution = ?,
             status = ?, priority = ?, labor_price = ?, total_price = ?,
             payment_status = ?,
             quick_customer_name = ?, quick_customer_phone = ?, quick_device_name = ?,
             quick_brand = ?, quick_model = ?, quick_service_description = ?,
             quick_accessories = ?, quick_password = ?, quick_notes = ?, quick_due_date = ?,
             closed_at = CASE WHEN ? IN (\'entregue\', \'cancelada\')
                              THEN COALESCE(closed_at, NOW()) ELSE NULL END
         WHERE id = ?'
    );
    $stmt->execute([
        order_fast_title($data),
        $data['reported_issue'],
        $data['diagnosis'] ?? null,
        $data['solution'] ?? null,
        $status,
        $priority,
        $estimated,
        $estimated,
        order_payment_status($estimated, (float) ($order['paid_amount'] ?? 0)),
        $data['quick_customer_name'],
        order_normalize_phone($data['quick_customer_phone'] ?? null),
        $data['quick_device_name'],
        $data['quick_brand'] ?? null,
        $data['quick_model'] ?? null,
        $data['quick_service_description'] ?? null,
        $data['quick_accessories'] ?? null,
        $data['quick_password'] ?? null,
        $data['quick_notes'] ?? null,
        $data['quick_due_date'] ?? null,
        $status,
        $id,
    ]);
}

function order_fast_client_matches(string $name, ?string $phone): array
{
    $phone = order_normalize_phone($phone);
    $sql = 'SELECT * FROM clients WHERE 1 = 1';
    $params = [];

    if ($phone) {
        $sql .= ' AND regexp_replace(COALESCE(phone, \'\'), \'\\D\', \'\', \'g\') = :phone';
        $params['phone'] = $phone;
    } else {
        $sql .= ' AND LOWER(name) = LOWER(:name)';
        $params['name'] = trim($name);
    }

    $stmt = db()->prepare($sql . ' ORDER BY name LIMIT 8');
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function order_fast_device_matches(int $clientId, string $deviceName, ?string $brand, ?string $model): array
{
    $stmt = db()->prepare(
        'SELECT * FROM devices
         WHERE client_id = ?
           AND (
                model ILIKE ?
                OR model_code ILIKE ?
                OR brand ILIKE ?
                OR LOWER(CONCAT_WS(\' \', brand, model, model_code)) = LOWER(?)
           )
         ORDER BY created_at DESC
         LIMIT 8'
    );
    $query = '%' . trim($deviceName) . '%';
    $brandQuery = '%' . trim((string) $brand) . '%';
    $modelQuery = '%' . trim((string) ($model ?: $deviceName)) . '%';
    $stmt->execute([$clientId, $query, $modelQuery, $brandQuery, trim($deviceName)]);
    return $stmt->fetchAll();
}

function order_fast_convert(int $id, array $data): array
{
    $order = order_find($id);
    if (!$order || empty($order['is_fast_order'])) {
        throw new RuntimeException('Fast OS não encontrada.');
    }

    $pdo = db();
    $startedTransaction = !$pdo->inTransaction();
    if ($startedTransaction) {
        $pdo->beginTransaction();
    }
    try {
        $clientId = (int) ($data['client_id'] ?? 0);
        if ($clientId && !client_find($clientId)) {
            throw new RuntimeException('Cliente selecionado inválido.');
        }
        if (!$clientId) {
            $clientId = client_create([
                'name' => order_limit($data['client_name'] ?? $order['quick_customer_name'], 160) ?: 'Cliente balcão',
                'phone' => order_normalize_phone($data['client_phone'] ?? $order['quick_customer_phone']),
                'email' => null,
                'document' => null,
                'address' => null,
                'notes' => 'Criado a partir da Fast OS #' . $id,
            ]);
        }

        $deviceId = (int) ($data['device_id'] ?? 0);
        if ($deviceId) {
            $device = device_find($deviceId);
            if (!$device || (int) $device['client_id'] !== $clientId) {
                throw new RuntimeException('Equipamento selecionado inválido.');
            }
        }
        if (!$deviceId) {
            $deviceName = order_limit($data['device_name'] ?? $order['quick_device_name'], 120) ?: 'Equipamento balcão';
            $deviceId = device_create([
                'client_id' => $clientId,
                'category' => 'Outro',
                'brand' => order_limit($data['brand'] ?? $order['quick_brand'], 60) ?: 'Outro',
                'model' => order_limit($data['model'] ?? $order['quick_model'] ?? $deviceName, 120),
                'model_code' => null,
                'serial_number' => null,
                'year' => null,
                'processor' => null,
                'memory' => null,
                'storage' => null,
                'condition_notes' => 'Vinculado a partir da Fast OS #' . $id . '. Dados originais: ' . $deviceName,
            ]);
        }

        $stmt = $pdo->prepare(
            'UPDATE service_orders
             SET client_id = ?, device_id = ?,
                 fast_order_converted_at = NOW(),
                 fast_order_converted_by = ?
             WHERE id = ?'
        );
        $stmt->execute([$clientId, $deviceId, (int) (current_user()['id'] ?? 0) ?: null, $id]);

        if ($startedTransaction) {
            $pdo->commit();
        }
        return ['client_id' => $clientId, 'device_id' => $deviceId];
    } catch (Throwable $exception) {
        if ($startedTransaction) {
            $pdo->rollBack();
        }
        throw $exception;
    }
}

function order_set_status(int $id, string $status): void
{
    $stmt = db()->prepare(
        'UPDATE service_orders
         SET status = ?,
             closed_at = CASE WHEN ? IN (\'entregue\', \'cancelada\')
                              THEN COALESCE(closed_at, NOW()) ELSE NULL END
         WHERE id = ?'
    );
    $stmt->execute([$status, $status, $id]);
}

/**
 * Registra um pagamento na OS e cria o lançamento financeiro vinculado.
 */
function order_register_payment(int $id, float $amount): void
{
    $order = order_find($id); // já filtrado por empresa
    if (!$order || $amount <= 0) {
        return;
    }

    $pdo = db();
    $pdo->beginTransaction();
    try {
        $paid = (float) $order['paid_amount'] + $amount;
        $stmt = $pdo->prepare(
            'UPDATE service_orders SET paid_amount = ?, payment_status = ?
             WHERE id = ?'
        );
        $stmt->execute([
            $paid, order_payment_status((float) $order['total_price'], $paid),
            $id,
        ]);

        $stmt = $pdo->prepare(
            'INSERT INTO financial_entries
                (type, description, amount, category, reference_type, reference_id, entry_date)
             VALUES (?, ?, ?, ?, ?, ?, CURRENT_DATE)'
        );
        $stmt->execute([
            'entrada',
            'Pagamento OS #' . $id . ' — ' . $order['title'],
            $amount, 'Serviço', 'service_order', $id,
        ]);

        $pdo->commit();
    } catch (Throwable $exception) {
        $pdo->rollBack();
        throw $exception;
    }
}

function order_delete(int $id): void
{
    $stmt = db()->prepare('DELETE FROM service_orders WHERE id = ?');
    $stmt->execute([$id]);
}

/** Portal do cliente: tokens de acompanhamento público da OS. */
function order_portal_tokens(int $orderId): array
{
    $stmt = db()->prepare('SELECT * FROM service_order_portal_tokens WHERE service_order_id = ? ORDER BY created_at DESC');
    $stmt->execute([$orderId]);
    return $stmt->fetchAll();
}

function order_portal_token_create(int $orderId, ?string $label = null): string
{
    $token = bin2hex(random_bytes(24));
    $stmt = db()->prepare(
        'INSERT INTO service_order_portal_tokens (service_order_id, token_hash, label, created_by)
         VALUES (?, ?, ?, ?)'
    );
    $stmt->execute([$orderId, hash('sha256', $token), $label, current_user()['id'] ?? null]);
    return $token;
}

function order_find_by_portal_token(string $token): ?array
{
    $stmt = db()->prepare(
        'SELECT so.*,
                COALESCE(c.name, so.quick_customer_name) AS client_name,
                COALESCE(d.category, so.quick_device_name) AS device_category,
                COALESCE(d.model, so.quick_model) AS device_model,
                COALESCE(d.brand, so.quick_brand) AS device_brand
         FROM service_order_portal_tokens t
         JOIN service_orders so ON so.id = t.service_order_id
         LEFT JOIN clients c ON c.id = so.client_id
         LEFT JOIN devices d ON d.id = so.device_id
         WHERE t.token_hash = ? AND t.revoked_at IS NULL AND (t.expires_at IS NULL OR t.expires_at > NOW())
         LIMIT 1'
    );
    $stmt->execute([hash('sha256', $token)]);
    return $stmt->fetch() ?: null;
}
