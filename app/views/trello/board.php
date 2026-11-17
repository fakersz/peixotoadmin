<div class="board" id="kanban" data-csrf="<?= e(csrf_token()) ?>">
    <?php foreach ($columns as $column): ?>
        <?php $cards = $cardsByColumn[(int) $column['id']] ?? []; ?>
        <section class="bcol" data-column="<?= $column['id'] ?>">
            <header class="bcol-head">
                <form method="post" action="/trello/column_rename" class="bcol-rename">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" value="<?= $column['id'] ?>">
                    <input type="text" name="name" value="<?= e($column['name']) ?>" maxlength="80"
                           aria-label="Renomear lista" title="Renomear (Enter para salvar)" autocomplete="off">
                </form>
                <span class="bcol-count"><?= count($cards) ?></span>
                <form method="post" action="/trello/column_delete" class="inline"
                      data-confirm="Excluir a lista «<?= e($column['name']) ?>» e todos os seus cartões?">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" value="<?= $column['id'] ?>">
                    <button type="submit" class="bcol-x" aria-label="Excluir lista" title="Excluir lista">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M3 6h18M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2m3 0v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/></svg>
                    </button>
                </form>
            </header>

            <div class="bcol-cards" data-column="<?= $column['id'] ?>">
                <?php foreach ($cards as $card): ?>
                    <?php $tags = trello_parse_tags($card['tags']); ?>
                    <a href="/trello/card?id=<?= $card['id'] ?>" class="bcard" draggable="true"
                       data-card="<?= $card['id'] ?>">
                        <?php if ($tags): ?>
                            <div class="bcard-labels">
                                <?php foreach ($tags as $tag): ?>
                                    <span class="lbl lbl-<?= trello_tag_variant($tag) ?>"><?= e($tag) ?></span>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                        <div class="bcard-title"><?= e($card['title']) ?></div>
                        <?php $hasBody = trim((string) $card['body']) !== ''; ?>
                        <?php if ($hasBody || (int) $card['comment_count'] > 0): ?>
                            <div class="bcard-meta">
                                <?php if ($hasBody): ?>
                                    <span class="bcard-meta-item" title="Tem descrição">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M4 6h16M4 12h16M4 18h10"/></svg>
                                    </span>
                                <?php endif; ?>
                                <?php if ((int) $card['comment_count'] > 0): ?>
                                    <span class="bcard-meta-item" title="Comentários">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                                        <?= (int) $card['comment_count'] ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </a>
                <?php endforeach; ?>
            </div>

            <div class="bcol-add">
                <button type="button" class="bcol-add-btn">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>
                    Adicionar um cartão
                </button>
                <form method="post" action="/trello/create_card" class="bcol-add-form" hidden>
                    <?= csrf_field() ?>
                    <input type="hidden" name="column_id" value="<?= $column['id'] ?>">
                    <textarea name="title" rows="2" maxlength="200"
                              placeholder="Insira um título para este cartão…" required></textarea>
                    <div class="bcol-add-actions">
                        <button type="submit" class="btn btn-primary btn-sm">Adicionar cartão</button>
                        <button type="button" class="bcol-add-cancel" aria-label="Cancelar">×</button>
                    </div>
                </form>
            </div>
        </section>
    <?php endforeach; ?>

    <section class="bcol bcol-new">
        <button type="button" class="bcol-new-btn">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>
            Adicionar outra lista
        </button>
        <form method="post" action="/trello/column_create" class="bcol-add-form" hidden>
            <?= csrf_field() ?>
            <input type="text" name="name" maxlength="80" placeholder="Nome da lista…" required autocomplete="off">
            <div class="bcol-add-actions">
                <button type="submit" class="btn btn-primary btn-sm">Adicionar lista</button>
                <button type="button" class="bcol-add-cancel" aria-label="Cancelar">×</button>
            </div>
        </form>
    </section>
</div>

<div class="modal-overlay" id="cardModal" hidden>
    <div class="modal-panel" id="cardModalPanel" role="dialog" aria-modal="true"></div>
</div>
