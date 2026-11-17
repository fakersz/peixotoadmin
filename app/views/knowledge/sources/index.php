<?php $activeKnowledgeSection = 'sources'; ?>
<div class="page-actions">
    <form method="get" action="/knowledge/sources" class="search-form">
        <input type="search" name="q" value="<?= e($q) ?>" placeholder="Buscar por título, organização ou URL...">
        <button class="btn btn-ghost" type="submit">Buscar</button>
    </form>
    <?php if (knowledge_can('model_manage')): ?><a href="/knowledge/sources/create" class="btn btn-primary">Nova fonte</a><?php endif; ?>
</div>
<?php require APP_PATH . '/views/knowledge/_tabs.php'; ?>
<section class="card">
    <?php if (!$sources): ?>
        <p class="empty">Nenhuma fonte cadastrada.</p>
    <?php else: ?>
        <div class="table-wrap"><table>
            <thead><tr><th>Título</th><th>Organização</th><th>Tipo</th><th>Confiança</th><th>Disponibilidade</th><th>Verificada em</th></tr></thead>
            <tbody>
            <?php foreach ($sources as $source): ?>
                <tr>
                    <td><a class="link link-plain" href="<?= e($source['url']) ?>" target="_blank" rel="noopener"><?= e($source['title']) ?></a></td>
                    <td><?= e($source['organization'] ?: '-') ?></td>
                    <td><?= e($source['source_type'] ?: '-') ?></td>
                    <td><span class="badge badge-confidence-<?= e(str_replace('_', '-', $source['confidence_level'])) ?>"><?= e($source['confidence_level']) ?></span></td>
                    <td><span class="badge badge-confidence-<?= e(str_replace('_', '-', $source['availability_status'])) ?>"><?= e($source['availability_status']) ?></span></td>
                    <td><?= e($source['checked_at'] ?: '-') ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table></div>
    <?php endif; ?>
</section>
