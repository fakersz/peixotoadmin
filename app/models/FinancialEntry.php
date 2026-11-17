<?php
/** Model: financial_entries (entradas e saídas) */

function financial_entries(string $month): array
{
    $stmt = db()->prepare(
        "SELECT * FROM financial_entries
         WHERE TO_CHAR(entry_date, 'YYYY-MM') = ?
         ORDER BY entry_date DESC, id DESC"
    );
    $stmt->execute([$month]);
    return $stmt->fetchAll();
}

function financial_summary(string $month): array
{
    $stmt = db()->prepare(
        "SELECT
            COALESCE(SUM(amount) FILTER (WHERE type = 'entrada'), 0) AS income,
            COALESCE(SUM(amount) FILTER (WHERE type = 'saida'), 0)   AS expense
         FROM financial_entries
         WHERE TO_CHAR(entry_date, 'YYYY-MM') = ?"
    );
    $stmt->execute([$month]);
    $row = $stmt->fetch();
    $row['profit'] = (float) $row['income'] - (float) $row['expense'];
    return $row;
}

function financial_entry_create(array $data): int
{
    $stmt = db()->prepare(
        'INSERT INTO financial_entries
            (type, description, amount, category, reference_type, reference_id, entry_date)
         VALUES (?, ?, ?, ?, ?, ?, ?) RETURNING id'
    );
    $stmt->execute([
        $data['type'], $data['description'], $data['amount'], $data['category'],
        $data['reference_type'], $data['reference_id'], $data['entry_date'],
    ]);
    return (int) $stmt->fetchColumn();
}

function financial_entry_delete(int $id): void
{
    $stmt = db()->prepare('DELETE FROM financial_entries WHERE id = ?');
    $stmt->execute([$id]);
}
