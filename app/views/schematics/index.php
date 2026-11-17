<?php $schematicsBase = $schematicsBase ?? '/schematics'; ?>
<div class="page-actions">
    <form method="get" action="<?= e($schematicsBase) ?>" class="search-form">
        <?php if ($category): ?><input type="hidden" name="cat" value="<?= e($category) ?>"><?php endif; ?>
        <input type="search" name="q" value="<?= e($search) ?>" placeholder="Buscar por título, modelo (A1502…), placa (820-…), defeito ou medição…">
        <button type="submit" class="btn btn-ghost">Buscar</button>
    </form>
    <a href="<?= e($schematicsBase) ?>/create" class="btn btn-primary">+ Novo esquema</a>
</div>

<?php $total = array_sum($counts); ?>
<div class="badge-row">
    <a href="<?= e($schematicsBase) ?><?= $search ? '?q=' . urlencode($search) : '' ?>"
       class="chip<?= $category === '' ? ' active' : '' ?>">Todos <span class="chip-count"><?= $total ?></span></a>
    <?php foreach (schematic_categories() as $cat): ?>
        <?php if (empty($counts[$cat])) continue; ?>
        <a href="<?= e($schematicsBase) ?>?cat=<?= urlencode($cat) ?><?= $search ? '&q=' . urlencode($search) : '' ?>"
           class="chip<?= $category === $cat ? ' active' : '' ?>">
            <?= e($cat) ?> <span class="chip-count"><?= $counts[$cat] ?></span>
        </a>
    <?php endforeach; ?>
</div>

<section class="card">
    <?php if (!$schematics): ?>
        <p class="empty">Nenhum esquema encontrado.</p>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr><th>Título</th><th>Modelo</th><th>Placa</th><th>Ano</th><th>Arquivo</th><th class="actions-col"></th></tr>
                </thead>
                <tbody>
                <?php $lastCategory = null; ?>
                <?php foreach ($schematics as $schematic): ?>
                    <?php if (!$category && $schematic['device_category'] !== $lastCategory): ?>
                        <?php $lastCategory = $schematic['device_category']; ?>
                        <tr class="group-row">
                            <td colspan="6"><?= e($lastCategory ?: 'Sem categoria') ?></td>
                        </tr>
                    <?php endif; ?>
                    <tr>
                        <td>
                            <a href="<?= e($schematicsBase) ?>/view?id=<?= $schematic['id'] ?>" class="link link-plain"><?= e($schematic['title']) ?></a>
                        </td>
                        <td><?= $schematic['model_code'] ? '<span class="badge badge-code">' . e($schematic['model_code']) . '</span>' : '—' ?></td>
                        <td><?= $schematic['board_code'] ? '<span class="badge badge-board">' . e($schematic['board_code']) . '</span>' : '<span class="board-missing" title="Board a confirmar na serigrafia da placa">a confirmar</span>' ?></td>
                        <td><?= e($schematic['year'] ?: '—') ?></td>
                        <td>
                            <?php if ($schematic['file_path']): ?>
                                <?php $fileExt = strtoupper(pathinfo($schematic['file_path'], PATHINFO_EXTENSION)); ?>
                                <a href="<?= e($schematicsBase) ?>/file?id=<?= $schematic['id'] ?>" target="_blank" class="link">
                                    <?= $fileExt === 'PDF' ? '📄' : '📎' ?> <?= e($fileExt) ?>
                                </a>
                            <?php endif; ?>
                            <?php if ($schematic['external_link']): ?>
                                <a href="<?= e($schematic['external_link']) ?>" target="_blank" rel="noopener noreferrer" class="link">🔗 Link</a>
                            <?php endif; ?>
                            <?php if (!$schematic['file_path'] && !$schematic['external_link']): ?><span class="text-faint">—</span><?php endif; ?>
                        </td>
                        <td class="actions-col">
                            <a href="<?= e($schematicsBase) ?>/edit?id=<?= $schematic['id'] ?>" class="btn btn-sm btn-ghost">Editar</a>
                            <form method="post" action="<?= e($schematicsBase) ?>/delete" class="inline" data-confirm="Excluir este esquema? O PDF também será apagado.">
                                <?= csrf_field() ?>
                                <input type="hidden" name="id" value="<?= $schematic['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-danger-ghost">Excluir</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
