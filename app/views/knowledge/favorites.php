<?php $activeKnowledgeSection = 'favorites'; ?>
<div class="page-actions"><a href="/knowledge" class="btn btn-ghost">← Centro</a></div>
<?php require APP_PATH . '/views/knowledge/_tabs.php'; ?>
<section class="card">
    <?php if (!$favorites): ?>
        <p class="empty">Você ainda não favoritou nenhum item.</p>
    <?php else: ?>
        <div class="knowledge-result-list">
            <?php foreach ($favorites as $favorite): ?>
                <a class="knowledge-result" href="<?= e($favorite['url']) ?>">
                    <span class="badge badge-code"><?= e($favorite['type']) ?></span>
                    <strong><?= e($favorite['title']) ?></strong>
                    <span><?= e(mb_substr((string) ($favorite['snippet'] ?? ''), 0, 180)) ?></span>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
