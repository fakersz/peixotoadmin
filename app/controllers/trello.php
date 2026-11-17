<?php
/** Controller: Trello (quadro kanban) */

require APP_PATH . '/models/Trello.php';

function trello_index(): void
{
    render('trello/board', [
        'title'          => 'Trello',
        'columns'        => trello_columns_all(),
        'cardsByColumn'  => trello_cards_by_column(),
    ]);
}

function trello_card(): void
{
    $id = get_int('id');
    $card = $id ? trello_card_find($id) : null;
    if (!$card) {
        http_response_code(404);
        render('errors/404', ['title' => 'Card não encontrado']);
    }

    render('trello/card', [
        'title'    => $card['title'],
        'card'     => $card,
        'comments' => trello_comments_for((int) $card['id']),
    ]);
}

/** Fragmento HTML do card para o modal do quadro (sem layout). */
function trello_card_modal(): void
{
    $id = get_int('id');
    $card = $id ? trello_card_find($id) : null;
    if (!$card) {
        http_response_code(404);
        exit('Cartão não encontrado.');
    }

    render_bare('trello/_detail', [
        'card'     => $card,
        'comments' => trello_comments_for((int) $card['id']),
        'isModal'  => true,
    ]);
}

/** Redireciona de volta pro quadro (modal) ou pra página do card. */
function trello_back(int $cardId): never
{
    redirect(post_str('back') === 'board' ? '/trello' : '/trello/card?id=' . $cardId);
}

// ---------- Colunas ----------

function trello_column_create(): void
{
    require_post();
    $name = post_str('name');
    if ($name) {
        trello_column_create_col($name);
        flash('success', 'Coluna criada.');
    } else {
        flash('error', 'Informe um nome para a coluna.');
    }
    redirect('/trello');
}

function trello_column_rename(): void
{
    require_post();
    $id   = post_int('id');
    $name = post_str('name');
    if ($id && $name) {
        trello_column_rename_col($id, $name);
        flash('success', 'Coluna renomeada.');
    }
    redirect('/trello');
}

function trello_column_delete(): void
{
    require_post();
    $id = post_int('id');
    if ($id) {
        trello_column_delete_col($id);
        flash('success', 'Coluna excluída.');
    }
    redirect('/trello');
}

// ---------- Cards ----------

function trello_create_card(): void
{
    require_post();
    $columnId = post_int('column_id');
    $title    = post_str('title');
    if ($columnId && $title) {
        // Textareas podem trazer quebras de linha — título é sempre uma linha só.
        trello_card_create($columnId, preg_replace('/\s+/u', ' ', $title));
    } else {
        flash('error', 'Informe um título para o card.');
    }
    redirect('/trello');
}

function trello_update_card(): void
{
    require_post();
    $id    = post_int('id');
    $title = post_str('title');
    if (!$id || !$title) {
        flash('error', 'O card precisa de um título.');
        redirect('/trello');
    }
    $tags = trello_parse_tags(post_str('tags'));
    trello_card_update($id, preg_replace('/\s+/u', ' ', $title), post_str('body'), $tags ? implode(', ', $tags) : null);
    flash('success', 'Cartão atualizado.');
    trello_back($id);
}

function trello_delete_card(): void
{
    require_post();
    $id = post_int('id');
    if ($id) {
        trello_card_delete($id);
        flash('success', 'Card excluído.');
    }
    redirect('/trello');
}

/** Recebe drag-and-drop via fetch — responde JSON. */
function trello_move(): void
{
    header('Content-Type: application/json');
    $cardId   = post_int('card_id');
    $columnId = post_int('column_id');
    $order    = $_POST['order'] ?? [];

    if (!$cardId || !$columnId || !is_array($order)) {
        http_response_code(422);
        echo json_encode(['ok' => false]);
        return;
    }

    trello_card_move($cardId, $columnId, $order);
    echo json_encode(['ok' => true]);
}

// ---------- Comentários ----------

function trello_add_comment(): void
{
    require_post();
    $cardId = post_int('card_id');
    $body   = post_str('body');
    if ($cardId && $body) {
        trello_comment_create($cardId, $body);
        flash('success', 'Comentário adicionado.');
    }
    trello_back((int) $cardId);
}

function trello_delete_comment(): void
{
    require_post();
    $id     = post_int('id');
    $cardId = post_int('card_id');
    if ($id) {
        trello_comment_delete($id);
    }
    trello_back((int) $cardId);
}
