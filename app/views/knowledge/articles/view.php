<div class="page-actions">
    <a href="/knowledge/articles" class="btn btn-ghost">← Artigos</a>
    <span class="spacer"></span>
    <form method="post" action="/knowledge/favorite" class="inline">
        <?= csrf_field() ?>
        <input type="hidden" name="target_type" value="article">
        <input type="hidden" name="target_id" value="<?= (int) $article['id'] ?>">
        <input type="hidden" name="back" value="/knowledge/articles/view?id=<?= (int) $article['id'] ?>">
        <button type="submit" class="btn btn-ghost"><?= !empty($article['user_favorite']) ? 'Remover favorito' : 'Favoritar' ?></button>
    </form>
    <?php if (knowledge_can('article_edit', $article)): ?><a href="/knowledge/articles/edit?id=<?= (int) $article['id'] ?>" class="btn btn-ghost">Editar</a><?php endif; ?>
    <?php if ($article['status'] === 'arquivado' && knowledge_can('article_edit', $article)): ?>
        <form method="post" action="/knowledge/articles/restore" class="inline">
            <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $article['id'] ?>">
            <button type="submit" class="btn btn-ghost">Restaurar</button>
        </form>
    <?php endif; ?>
    <?php if (knowledge_can('article_archive', $article) && $article['status'] !== 'arquivado'): ?>
        <form method="post" action="/knowledge/articles/archive" class="inline" data-confirm="Arquivar este artigo?">
            <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $article['id'] ?>">
            <button type="submit" class="btn btn-ghost">Arquivar</button>
        </form>
    <?php endif; ?>
    <?php if (knowledge_can('article_delete', $article)): ?>
        <form method="post" action="/knowledge/articles/delete" class="inline" data-confirm="Excluir este artigo da listagem ativa?">
            <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $article['id'] ?>">
            <button type="submit" class="btn btn-danger-ghost">Excluir</button>
        </form>
    <?php endif; ?>
</div>

<section class="card knowledge-doc">
    <div class="card-header">
        <div>
            <h2><?= e($article['title']) ?></h2>
            <p class="hint"><?= e(trim(($article['manufacturer_name'] ?? '') . ' ' . ($article['model_code'] ?: $article['model_name'] ?: '')) ?: 'Sem modelo vinculado') ?></p>
        </div>
        <div class="badge-row">
            <span class="badge <?= $article['status'] === 'publicado' ? 'badge-in' : 'badge-low' ?>"><?= e($article['status']) ?></span>
            <span class="badge badge-code"><?= e($article['type']) ?></span>
            <?php if ($article['category']): ?><span class="badge badge-code"><?= e($article['category']) ?></span><?php endif; ?>
        </div>
    </div>
    <?php if ($article['summary']): ?><p class="knowledge-summary"><?= e($article['summary']) ?></p><?php endif; ?>
    <dl class="detail-grid knowledge-detail">
        <div><dt>Dificuldade</dt><dd><?= e($article['difficulty'] ?: '-') ?></dd></div>
        <div><dt>Tempo médio</dt><dd><?= $article['avg_time_minutes'] ? e($article['avg_time_minutes'] . ' min') : '-' ?></dd></div>
        <div class="span-all"><dt>Sintomas</dt><dd><?= $article['symptoms'] ? nl2br(e($article['symptoms'])) : '-' ?></dd></div>
        <div class="span-all"><dt>Diagnóstico</dt><dd><?= $article['diagnosis'] ? nl2br(e($article['diagnosis'])) : '-' ?></dd></div>
        <div class="span-all"><dt>Procedimento</dt><dd><?= $article['procedure'] ? nl2br(e($article['procedure'])) : '-' ?></dd></div>
        <div class="span-all"><dt>Solução</dt><dd><?= $article['solution'] ? nl2br(e($article['solution'])) : '-' ?></dd></div>
        <div><dt>Ferramentas</dt><dd><?= $article['tools'] ? nl2br(e($article['tools'])) : '-' ?></dd></div>
        <div><dt>Peças</dt><dd><?= $article['parts_used'] ? nl2br(e($article['parts_used'])) : '-' ?></dd></div>
    </dl>
    <div class="markdown-body"><?= md($article['content']) ?></div>
    <?php if ($article['notes']): ?><div class="knowledge-summary"><?= nl2br(e($article['notes'])) ?></div><?php endif; ?>
    <?php if ($article['tags']): ?><div class="ai-sources"><strong>Tags:</strong><span><?= e($article['tags']) ?></span></div><?php endif; ?>
</section>

<section class="card">
    <div class="card-header">
        <h2>Histórico de alterações</h2>
        <a class="btn btn-sm btn-ghost" href="/knowledge/history?article_id=<?= (int) $article['id'] ?>">Ver histórico</a>
    </div>
    <?php if (!$history): ?>
        <p class="empty">Sem versões registradas.</p>
    <?php else: ?>
        <div class="knowledge-result-list">
            <?php foreach (array_slice($history, 0, 5) as $version): ?>
                <div class="knowledge-result">
                    <span class="badge badge-code"><?= e($version['change_type']) ?></span>
                    <strong><?= e($version['user_name'] ?: 'Sistema') ?></strong>
                    <span><?= e(br_date($version['created_at'], true)) ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
