<?php
/** Model: Trello (quadro kanban — colunas, cards e comentários) */

// ---------- Colunas ----------

function trello_columns_all(): array
{
    return db()->query('SELECT * FROM trello_columns ORDER BY position, id')->fetchAll();
}

function trello_column_create_col(string $name): int
{
    $stmt = db()->query('SELECT COALESCE(MAX(position) + 1, 0) FROM trello_columns');
    $pos = (int) $stmt->fetchColumn();

    $stmt = db()->prepare('INSERT INTO trello_columns (name, position) VALUES (?, ?) RETURNING id');
    $stmt->execute([$name, $pos]);
    return (int) $stmt->fetchColumn();
}

function trello_column_rename_col(int $id, string $name): void
{
    $stmt = db()->prepare('UPDATE trello_columns SET name = ? WHERE id = ?');
    $stmt->execute([$name, $id]);
}

function trello_column_delete_col(int $id): void
{
    $stmt = db()->prepare('DELETE FROM trello_columns WHERE id = ?');
    $stmt->execute([$id]);
}

/** Cria as colunas padrão quando o quadro está vazio. */
function trello_seed_columns(): void
{
    if ((int) db()->query('SELECT COUNT(*) FROM trello_columns')->fetchColumn() > 0) {
        return;
    }
    $stmt = db()->prepare('INSERT INTO trello_columns (name, position) VALUES (?, ?)');
    foreach (['A Fazer', 'Em Andamento', 'Concluído'] as $i => $name) {
        $stmt->execute([$name, $i]);
    }
}

// ---------- Cards ----------

/** Retorna todos os cards agrupados por column_id, já ordenados. */
function trello_cards_by_column(): array
{
    $stmt = db()->prepare(
        'SELECT c.*, (SELECT COUNT(*) FROM trello_comments t WHERE t.card_id = c.id) AS comment_count
         FROM trello_cards c ORDER BY c.position, c.id'
    );
    $stmt->execute();

    $grouped = [];
    foreach ($stmt->fetchAll() as $row) {
        $grouped[(int) $row['column_id']][] = $row;
    }
    return $grouped;
}

function trello_card_find(int $id): ?array
{
    $stmt = db()->prepare('SELECT * FROM trello_cards WHERE id = ?');
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}

function trello_card_create(int $columnId, string $title): int
{
    $stmt = db()->prepare('SELECT 1 FROM trello_columns WHERE id = ?');
    $stmt->execute([$columnId]);
    if (!$stmt->fetchColumn()) {
        throw new RuntimeException('Coluna inválida.');
    }

    $stmt = db()->prepare('SELECT COALESCE(MAX(position) + 1, 0) FROM trello_cards WHERE column_id = ?');
    $stmt->execute([$columnId]);
    $pos = (int) $stmt->fetchColumn();

    $stmt = db()->prepare(
        'INSERT INTO trello_cards (column_id, title, position) VALUES (?, ?, ?) RETURNING id'
    );
    $stmt->execute([$columnId, $title, $pos]);
    return (int) $stmt->fetchColumn();
}

function trello_card_update(int $id, string $title, ?string $body, ?string $tags): void
{
    $stmt = db()->prepare(
        'UPDATE trello_cards SET title = ?, body = ?, tags = ?, updated_at = NOW()
         WHERE id = ?'
    );
    $stmt->execute([$title, $body, $tags, $id]);
}

function trello_card_delete(int $id): void
{
    $stmt = db()->prepare('DELETE FROM trello_cards WHERE id = ?');
    $stmt->execute([$id]);
}

/**
 * Move um card para uma coluna e reordena essa coluna conforme a lista de ids.
 */
function trello_card_move(int $cardId, int $columnId, array $order): void
{
    $pdo = db();

    $stmt = $pdo->prepare('SELECT 1 FROM trello_columns WHERE id = ?');
    $stmt->execute([$columnId]);
    if (!$stmt->fetchColumn()) {
        throw new RuntimeException('Coluna inválida.');
    }

    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare('UPDATE trello_cards SET column_id = ? WHERE id = ?');
        $stmt->execute([$columnId, $cardId]);

        $upd = $pdo->prepare(
            'UPDATE trello_cards SET position = ? WHERE id = ? AND column_id = ?'
        );
        $pos = 0;
        foreach ($order as $id) {
            $id = (int) $id;
            if ($id > 0) {
                $upd->execute([$pos++, $id, $columnId]);
            }
        }
        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}

// ---------- Comentários ----------

function trello_comments_for(int $cardId): array
{
    $stmt = db()->prepare(
        'SELECT tc.* FROM trello_comments tc
         JOIN trello_cards c ON c.id = tc.card_id
         WHERE tc.card_id = ?
         ORDER BY tc.created_at'
    );
    $stmt->execute([$cardId]);
    return $stmt->fetchAll();
}

function trello_comment_create(int $cardId, string $body): void
{
    if (!trello_card_find($cardId)) {
        return;
    }
    $stmt = db()->prepare('INSERT INTO trello_comments (card_id, body) VALUES (?, ?)');
    $stmt->execute([$cardId, $body]);
}

function trello_comment_delete(int $id): void
{
    $stmt = db()->prepare(
        'DELETE FROM trello_comments tc USING trello_cards c
         WHERE tc.id = ? AND c.id = tc.card_id'
    );
    $stmt->execute([$id]);
}

// ---------- Utilidades ----------

/** Converte uma string "a, b, c" numa lista de etiquetas limpas e únicas. */
function trello_parse_tags(?string $raw): array
{
    if (!$raw) {
        return [];
    }
    $tags = array_map('trim', explode(',', $raw));
    $tags = array_filter($tags, static fn ($t) => $t !== '');
    return array_values(array_unique($tags));
}
