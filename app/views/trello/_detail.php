<?php
/** Partial: detalhe do card — usado no modal (isModal=true) e na página /trello/card. */
$tags    = trello_parse_tags($card['tags']);
$isModal = $isModal ?? false;
$initial = mb_strtoupper(mb_substr(current_user()['name'] ?? '?', 0, 1));
?>
<div class="cd">
    <header class="cd-head">
        <svg class="cd-head-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 10h18"/></svg>
        <span class="cd-head-hint">Cartão · criado em <?= br_date($card['created_at']) ?></span>
        <form method="post" action="/trello/delete_card" class="inline"
              data-confirm="Excluir o cartão «<?= e($card['title']) ?>»?">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= $card['id'] ?>">
            <button type="submit" class="cd-icon-btn cd-danger" title="Excluir cartão" aria-label="Excluir cartão">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M3 6h18M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2m3 0v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/></svg>
            </button>
        </form>
        <?php if ($isModal): ?>
            <button type="button" class="cd-icon-btn" data-close aria-label="Fechar" title="Fechar">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M18 6 6 18M6 6l12 12"/></svg>
            </button>
        <?php else: ?>
            <a href="/trello" class="cd-icon-btn" aria-label="Voltar ao quadro" title="Voltar ao quadro">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M18 6 6 18M6 6l12 12"/></svg>
            </a>
        <?php endif; ?>
    </header>

    <form method="post" action="/trello/update_card" class="cd-form">
        <?= csrf_field() ?>
        <input type="hidden" name="id" value="<?= $card['id'] ?>">
        <?php if ($isModal): ?><input type="hidden" name="back" value="board"><?php endif; ?>

        <input type="text" name="title" class="cd-title" maxlength="200" required
               value="<?= e($card['title']) ?>" aria-label="Título do cartão">

        <div class="cd-section">
            <div class="cd-label">Etiquetas</div>
            <?php if ($tags): ?>
                <div class="cd-labels">
                    <?php foreach ($tags as $tag): ?>
                        <span class="lbl lbl-lg lbl-<?= trello_tag_variant($tag) ?>"><?= e($tag) ?></span>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <input type="text" name="tags" maxlength="255" value="<?= e($card['tags']) ?>"
                   placeholder="urgente, hardware, cliente… (separadas por vírgula)" autocomplete="off">
        </div>

        <div class="cd-section">
            <div class="cd-label">Descrição <span class="cd-label-hint">Markdown</span></div>
            <textarea name="body" rows="8" class="cd-body mono"
                      placeholder="Adicione uma descrição mais detalhada…&#10;&#10;# Título · **negrito** · *itálico* · `código` · - lista · [link](https://…)"><?= e($card['body']) ?></textarea>
        </div>

        <div class="cd-actions">
            <button type="submit" class="btn btn-primary">Salvar</button>
        </div>
    </form>

    <?php if (trim((string) $card['body']) !== ''): ?>
        <div class="cd-section">
            <div class="cd-label">Pré-visualização</div>
            <div class="cd-preview markdown"><?= md($card['body']) ?></div>
        </div>
    <?php endif; ?>

    <div class="cd-section">
        <div class="cd-label">Comentários <span class="bcol-count"><?= count($comments) ?></span></div>

        <form method="post" action="/trello/add_comment" class="cd-comment-form">
            <?= csrf_field() ?>
            <input type="hidden" name="card_id" value="<?= $card['id'] ?>">
            <?php if ($isModal): ?><input type="hidden" name="back" value="board"><?php endif; ?>
            <div class="cd-comment-row">
                <div class="avatar cd-avatar"><?= e($initial) ?></div>
                <textarea name="body" rows="2" placeholder="Escrever um comentário… (aceita Markdown)" required></textarea>
            </div>
            <div class="cd-actions">
                <button type="submit" class="btn btn-primary btn-sm">Comentar</button>
            </div>
        </form>

        <?php if ($comments): ?>
            <ul class="cd-comments">
                <?php foreach (array_reverse($comments) as $comment): ?>
                    <li class="cd-comment">
                        <div class="avatar cd-avatar"><?= e($initial) ?></div>
                        <div class="cd-comment-main">
                            <div class="cd-comment-head">
                                <span class="cd-comment-date"><?= br_date($comment['created_at'], true) ?></span>
                                <form method="post" action="/trello/delete_comment" class="inline"
                                      data-confirm="Excluir este comentário?">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="id" value="<?= $comment['id'] ?>">
                                    <input type="hidden" name="card_id" value="<?= $card['id'] ?>">
                                    <?php if ($isModal): ?><input type="hidden" name="back" value="board"><?php endif; ?>
                                    <button type="submit" class="cd-comment-del" aria-label="Excluir comentário">×</button>
                                </form>
                            </div>
                            <div class="cd-comment-body markdown"><?= md($comment['body']) ?></div>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</div>
