<?php
/** Model: schematics (catálogo de placas) */

/** Ordenação de catálogo: categoria (ordem fixa) → ano → modelo. */
const SCHEMATICS_ORDER = "
    CASE device_category
        WHEN 'MacBook Pro'    THEN 1
        WHEN 'MacBook Air'    THEN 2
        WHEN 'MacBook (12\")' THEN 3
        WHEN 'iMac'           THEN 4
        WHEN 'Mac mini'       THEN 5
        WHEN 'Mac Pro'        THEN 6
        ELSE 7
    END,
    year NULLS LAST, model_code, title";

function schematics_all(?string $search = null, ?string $category = null): array
{
    $sql = 'SELECT * FROM schematics';
    $params = [];

    if ($search) {
        $sql .= ' WHERE (title ILIKE :q OR model_code ILIKE :q OR board_code ILIKE :q
                       OR device_category ILIKE :q OR notes ILIKE :q)';
        $params['q'] = "%$search%";
    }
    if ($category) {
        $sql .= $search ? ' AND device_category = :cat' : ' WHERE device_category = :cat';
        $params['cat'] = $category;
    }

    $stmt = db()->prepare($sql . ' ORDER BY ' . SCHEMATICS_ORDER);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

/** Contagem por categoria (para os filtros da listagem). */
function schematics_category_counts(): array
{
    $stmt = db()->prepare(
        'SELECT device_category, COUNT(*) AS n FROM schematics
         WHERE device_category IS NOT NULL GROUP BY device_category'
    );
    $stmt->execute();

    $counts = [];
    foreach ($stmt->fetchAll() as $row) {
        $counts[$row['device_category']] = (int) $row['n'];
    }
    return $counts;
}

function schematic_find(int $id): ?array
{
    $stmt = db()->prepare('SELECT * FROM schematics WHERE id = ?');
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}

function schematic_create(array $data): int
{
    $stmt = db()->prepare(
        'INSERT INTO schematics
            (title, device_category, model_code, board_code, year, file_path, external_link, notes)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?) RETURNING id'
    );
    $stmt->execute([
        $data['title'], $data['device_category'], $data['model_code'],
        $data['board_code'], $data['year'],
        $data['file_path'], $data['external_link'], $data['notes'],
    ]);
    return (int) $stmt->fetchColumn();
}

function schematic_update(int $id, array $data): void
{
    $stmt = db()->prepare(
        'UPDATE schematics
         SET title = ?, device_category = ?, model_code = ?, board_code = ?,
             year = ?, file_path = ?, external_link = ?, notes = ?
         WHERE id = ?'
    );
    $stmt->execute([
        $data['title'], $data['device_category'], $data['model_code'],
        $data['board_code'], $data['year'],
        $data['file_path'], $data['external_link'], $data['notes'],
        $id,
    ]);
}

/** Limpa apenas a referência do arquivo (o registro permanece). */
function schematic_clear_file(int $id): void
{
    $stmt = db()->prepare('UPDATE schematics SET file_path = NULL WHERE id = ?');
    $stmt->execute([$id]);
}

function schematic_delete(int $id): void
{
    $schematic = schematic_find($id); // já valida a empresa
    if ($schematic && $schematic['file_path']) {
        $file = UPLOADS_PATH . '/' . basename($schematic['file_path']);
        if (is_file($file)) {
            unlink($file);
        }
    }
    $stmt = db()->prepare('DELETE FROM schematics WHERE id = ?');
    $stmt->execute([$id]);
}
