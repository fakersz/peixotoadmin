<?php $activeKnowledgeSection = $section; ?>
<div class="page-actions">
    <form method="get" action="/knowledge/<?= e($section) ?>" class="search-form">
        <input type="search" name="q" value="<?= e($q) ?>" placeholder="Buscar nesta seção...">
        <button class="btn btn-ghost" type="submit">Buscar</button>
    </form>
    <?php if (knowledge_can('asset_upload')): ?><a class="btn btn-primary" href="/knowledge/assets/create?category=<?= e($category) ?>">Enviar</a><?php endif; ?>
</div>
<?php require APP_PATH . '/views/knowledge/_tabs.php'; ?>
<section class="card">
    <?php if (!$assets): ?>
        <p class="empty">Nenhum item cadastrado nesta seção.</p>
    <?php else: ?>
        <div class="table-wrap"><table>
            <thead><tr><th>Título</th><th>Modelo</th><th>Arquivo</th><th>Tamanho</th><th>Tags</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($assets as $asset): ?>
                <tr>
                    <td><a class="link link-plain" href="/knowledge/assets/view?id=<?= (int) $asset['id'] ?>"><?= e($asset['title']) ?></a></td>
                    <td><?= e(trim(($asset['manufacturer_name'] ?? '') . ' ' . ($asset['model_code'] ?: $asset['model_name'] ?: '')) ?: '-') ?></td>
                    <td><?= e($asset['original_name'] ?: ($asset['external_link'] ? 'link externo' : '-')) ?></td>
                    <td><?= $asset['size_bytes'] ? e(number_format(((int) $asset['size_bytes']) / 1024 / 1024, 2, ',', '.') . ' MB') : '-' ?></td>
                    <td><?= e($asset['tags'] ?: '-') ?></td>
                    <td class="actions-col">
                        <?php if ($asset['external_link']): ?><a class="btn btn-sm btn-ghost" href="<?= e($asset['external_link']) ?>" target="_blank" rel="noopener noreferrer">Abrir</a><?php endif; ?>
                        <?php if ($asset['storage_path']): ?><a class="btn btn-sm btn-ghost" href="/knowledge/assets/file?id=<?= (int) $asset['id'] ?>">Baixar</a><?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table></div>
    <?php endif; ?>
</section>
