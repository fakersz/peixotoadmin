<?php $activeKnowledgeSection = 'articles'; ?>
<div class="page-actions">
    <form method="get" action="/knowledge/articles" class="search-form search-form-wide">
        <input type="search" name="q" value="<?= e($filters['q']) ?>" placeholder="Buscar artigo, sintoma, diagnóstico, solução, placa, tag...">
        <select name="manufacturer_id">
            <option value="">Fabricante</option>
            <?php foreach ($manufacturers as $manufacturer): ?>
                <option value="<?= (int) $manufacturer['id'] ?>" <?= (int) ($filters['manufacturer_id'] ?? 0) === (int) $manufacturer['id'] ? 'selected' : '' ?>><?= e($manufacturer['name']) ?></option>
            <?php endforeach; ?>
        </select>
        <select name="status">
            <option value="">Status</option>
            <?php foreach (KNOWLEDGE_ARTICLE_STATUSES as $status): ?>
                <option value="<?= e($status) ?>" <?= ($filters['status'] ?? '') === $status ? 'selected' : '' ?>><?= e($status) ?></option>
            <?php endforeach; ?>
        </select>
        <button class="btn btn-ghost" type="submit">Filtrar</button>
    </form>
    <?php if (knowledge_can('article_create')): ?><a href="/knowledge/articles/create" class="btn btn-primary">Novo artigo</a><?php endif; ?>
</div>
<?php require APP_PATH . '/views/knowledge/_tabs.php'; ?>

<section class="card">
    <?php if (!$articles): ?>
        <p class="empty">Nenhum artigo cadastrado. Revise os filtros ou crie um rascunho técnico.</p>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Título</th><th>Modelo</th><th>Categoria</th><th>Status</th><th>Dificuldade</th><th>Atualizado</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($articles as $article): ?>
                    <tr>
                        <td>
                            <a class="link link-plain" href="/knowledge/articles/view?id=<?= (int) $article['id'] ?>"><?= e($article['title']) ?></a>
                            <?php if (!empty($article['user_favorite'])): ?><span class="badge badge-code">favorito</span><?php endif; ?>
                        </td>
                        <td><?= e(trim(($article['manufacturer_name'] ?? '') . ' ' . ($article['model_code'] ?: $article['model_name'] ?: '')) ?: '-') ?></td>
                        <td><?= e($article['category'] ?: '-') ?></td>
                        <td><span class="badge <?= $article['status'] === 'publicado' ? 'badge-in' : 'badge-low' ?>"><?= e($article['status']) ?></span></td>
                        <td><?= e($article['difficulty'] ?: '-') ?></td>
                        <td><?= e(br_date($article['updated_at'], true)) ?></td>
                        <td class="actions-col">
                            <?php if (knowledge_can('article_edit', $article)): ?><a href="/knowledge/articles/edit?id=<?= (int) $article['id'] ?>" class="btn btn-sm btn-ghost">Editar</a><?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
