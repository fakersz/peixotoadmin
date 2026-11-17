<?php $activeKnowledgeSection = 'models'; ?>
<div class="page-actions">
    <form method="get" action="/knowledge/models" class="search-form search-form-wide">
        <input type="search" name="q" value="<?= e($q) ?>" placeholder="Buscar modelo por nome, código, placa ou tag...">
        <select name="manufacturer_id">
            <option value="">Fabricante</option>
            <?php foreach ($manufacturers as $manufacturer): ?>
                <option value="<?= (int) $manufacturer['id'] ?>" <?= (int) $manufacturerId === (int) $manufacturer['id'] ? 'selected' : '' ?>><?= e($manufacturer['name']) ?></option>
            <?php endforeach; ?>
        </select>
        <button class="btn btn-ghost" type="submit">Buscar</button>
    </form>
    <?php if (knowledge_can('model_manage')): ?><a href="/knowledge/models/create" class="btn btn-primary">Novo modelo</a><?php endif; ?>
</div>
<?php require APP_PATH . '/views/knowledge/_tabs.php'; ?>
<section class="card">
    <?php if (!$models): ?>
        <p class="empty">Nenhum modelo encontrado.</p>
    <?php else: ?>
        <div class="table-wrap"><table>
            <thead><tr><th>Modelo</th><th>Fabricante</th><th>Código</th><th>Tags</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($models as $model): ?>
                <tr>
                    <td><a class="link link-plain" href="/knowledge/models/view?id=<?= (int) $model['id'] ?>"><?= e($model['name']) ?></a></td>
                    <td><?= e($model['manufacturer_name']) ?></td>
                    <td><?= e($model['code'] ?: '-') ?></td>
                    <td><?= e($model['tags'] ?: '-') ?></td>
                    <td class="actions-col"><?php if (knowledge_can('model_manage')): ?><a href="/knowledge/models/edit?id=<?= (int) $model['id'] ?>" class="btn btn-sm btn-ghost">Editar</a><?php endif; ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table></div>
    <?php endif; ?>
</section>
