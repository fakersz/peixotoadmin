<div class="page-actions">
    <a href="/knowledge/manufacturers" class="btn btn-ghost">← Fabricantes</a>
    <span class="spacer"></span>
    <?php if (knowledge_can('model_manage')): ?><a href="/knowledge/models/create?manufacturer_id=<?= (int) $manufacturer['id'] ?>" class="btn btn-primary">Novo modelo</a><?php endif; ?>
    <?php if (knowledge_can('manufacturer_manage')): ?><a href="/knowledge/manufacturers/edit?id=<?= (int) $manufacturer['id'] ?>" class="btn btn-ghost">Editar</a><?php endif; ?>
</div>
<section class="card">
    <div class="card-header"><h2><?= e($manufacturer['name']) ?></h2><span class="badge badge-code"><?= e($manufacturer['slug']) ?></span></div>
    <?php if (!$models): ?>
        <p class="empty">Nenhum modelo cadastrado para este fabricante.</p>
    <?php else: ?>
        <div class="table-wrap"><table>
            <thead><tr><th>Modelo</th><th>Código</th><th>Tags</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($models as $model): ?>
                <tr>
                    <td><a class="link link-plain" href="/knowledge/models/view?id=<?= (int) $model['id'] ?>"><?= e($model['name']) ?></a></td>
                    <td><?= e($model['code'] ?: '-') ?></td>
                    <td><?= e($model['tags'] ?: '-') ?></td>
                    <td class="actions-col"><?php if (knowledge_can('model_manage')): ?><a href="/knowledge/models/edit?id=<?= (int) $model['id'] ?>" class="btn btn-sm btn-ghost">Editar</a><?php endif; ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table></div>
    <?php endif; ?>
</section>
