<?php $activeKnowledgeSection = 'manufacturers'; ?>
<div class="page-actions">
    <form method="get" action="/knowledge/manufacturers" class="search-form">
        <input type="search" name="q" value="<?= e($q) ?>" placeholder="Buscar fabricante por nome...">
        <button class="btn btn-ghost" type="submit">Buscar</button>
    </form>
    <?php if (knowledge_can('manufacturer_manage')): ?><a href="/knowledge/manufacturers/create" class="btn btn-primary">Novo fabricante</a><?php endif; ?>
</div>
<?php require APP_PATH . '/views/knowledge/_tabs.php'; ?>
<section class="card">
    <?php if (!$manufacturers): ?>
        <p class="empty">Nenhum fabricante encontrado.</p>
    <?php else: ?>
        <div class="table-wrap"><table>
            <thead><tr><th>Fabricante</th><th>Slug</th><th>Modelos</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($manufacturers as $manufacturer): ?>
                <tr>
                    <td><a class="link link-plain" href="/knowledge/manufacturers/view?id=<?= (int) $manufacturer['id'] ?>"><?= e($manufacturer['name']) ?></a></td>
                    <td><?= e($manufacturer['slug']) ?></td>
                    <td><?= (int) ($manufacturer['models_count'] ?? 0) ?></td>
                    <td class="actions-col"><?php if (knowledge_can('manufacturer_manage')): ?><a href="/knowledge/manufacturers/edit?id=<?= (int) $manufacturer['id'] ?>" class="btn btn-sm btn-ghost">Editar</a><?php endif; ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table></div>
    <?php endif; ?>
</section>
