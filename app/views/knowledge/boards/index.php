<?php $activeKnowledgeSection = 'boards'; ?>
<div class="page-actions">
    <form method="get" action="/knowledge/boards" class="search-form">
        <input type="search" name="q" value="<?= e($q) ?>" placeholder="Buscar por código, board ID ou descrição...">
        <button class="btn btn-ghost" type="submit">Buscar</button>
    </form>
    <?php if (knowledge_can('model_manage')): ?><a href="/knowledge/boards/create" class="btn btn-primary">Nova placa</a><?php endif; ?>
</div>
<?php require APP_PATH . '/views/knowledge/_tabs.php'; ?>
<section class="card">
    <?php if (!$boards): ?>
        <p class="empty">Nenhuma placa lógica cadastrada.</p>
    <?php else: ?>
        <div class="table-wrap"><table>
            <thead><tr><th>Código</th><th>Revisão</th><th>Ano</th><th>Arquitetura</th><th>Status</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($boards as $board): ?>
                <tr>
                    <td><a class="link link-plain" href="/knowledge/boards/view?id=<?= (int) $board['id'] ?>"><span class="badge badge-code"><?= e($board['full_code'] ?: $board['board_code']) ?></span></a></td>
                    <td><?= e($board['revision'] ?: '-') ?></td>
                    <td><?= e((string) ($board['year'] ?: '-')) ?></td>
                    <td><?= e($board['architecture'] ?: '-') ?></td>
                    <td><span class="badge badge-confidence-<?= e(str_replace('_', '-', $board['verification_status'])) ?>"><?= e($board['verification_status']) ?></span></td>
                    <td class="actions-col"></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table></div>
    <?php endif; ?>
</section>
