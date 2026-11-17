<?php
/** Model: inventory (estoque de peças e consumíveis) */

function inventory_items_all(?string $search = null, bool $lowOnly = false): array
{
    $sql = 'SELECT * FROM inventory';
    $params = [];

    if ($search) {
        $sql .= ' WHERE (name ILIKE :q OR category ILIKE :q OR compatible_models ILIKE :q
                       OR supplier ILIKE :q)';
        $params['q'] = "%$search%";
    }
    if ($lowOnly) {
        $sql .= $search ? ' AND quantity <= min_quantity' : ' WHERE quantity <= min_quantity';
    }

    $sql .= ' ORDER BY (quantity <= min_quantity) DESC, name';
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function inventory_item_find(int $id): ?array
{
    $stmt = db()->prepare('SELECT * FROM inventory WHERE id = ?');
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}

function inventory_item_create(array $data): int
{
    $stmt = db()->prepare(
        'INSERT INTO inventory (name, category, compatible_models, quantity,
                                min_quantity, cost_price, sale_price, supplier, notes)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?) RETURNING id'
    );
    $stmt->execute([
        $data['name'], $data['category'], $data['compatible_models'],
        $data['quantity'], $data['min_quantity'],
        $data['cost_price'], $data['sale_price'], $data['supplier'], $data['notes'],
    ]);
    return (int) $stmt->fetchColumn();
}

function inventory_item_update(int $id, array $data): void
{
    $stmt = db()->prepare(
        'UPDATE inventory
         SET name = ?, category = ?, compatible_models = ?, quantity = ?, min_quantity = ?,
             cost_price = ?, sale_price = ?, supplier = ?, notes = ?
         WHERE id = ?'
    );
    $stmt->execute([
        $data['name'], $data['category'], $data['compatible_models'],
        $data['quantity'], $data['min_quantity'],
        $data['cost_price'], $data['sale_price'], $data['supplier'], $data['notes'],
        $id,
    ]);
}

function inventory_item_delete(int $id): void
{
    $stmt = db()->prepare('DELETE FROM inventory WHERE id = ?');
    $stmt->execute([$id]);
}

function inventory_item_adjust(int $id, int $delta): void
{
    $stmt = db()->prepare(
        'UPDATE inventory SET quantity = GREATEST(quantity + ?, 0)
         WHERE id = ?'
    );
    $stmt->execute([$delta, $id]);
}

function inventory_low_count(): int
{
    return (int) db()->query('SELECT COUNT(*) FROM inventory WHERE quantity <= min_quantity')->fetchColumn();
}
