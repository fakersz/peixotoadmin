<?php $activeKnowledgeSection = 'history'; ?>
<div class="page-actions"><a href="/knowledge" class="btn btn-ghost">← Centro</a></div>
<?php require APP_PATH . '/views/knowledge/_tabs.php'; ?>
<section class="card">
    <div class="card-header"><h2>Versões de artigo</h2></div>
    <?php if (!$articleId): ?>
        <p class="empty">Abra um artigo e use o link de histórico para visualizar versões anteriores.</p>
    <?php elseif (!$history): ?>
        <p class="empty">Sem versões registradas para este artigo.</p>
    <?php else: ?>
        <div class="table-wrap"><table>
            <thead><tr><th>Alteração</th><th>Usuário</th><th>Data</th><th>Versão anterior</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($history as $version): ?>
                <tr>
                    <td><span class="badge badge-code"><?= e($version['change_type']) ?></span></td>
                    <td><?= e($version['user_name'] ?: 'Sistema') ?></td>
                    <td><?= e(br_date($version['created_at'], true)) ?></td>
                    <td><pre class="history-json"><?= e(mb_substr((string) $version['previous_version'], 0, 420)) ?></pre></td>
                    <td class="actions-col">
                        <?php if ($version['previous_version'] && knowledge_can('history_restore', ['author_user_id' => null, 'status' => 'rascunho'])): ?>
                            <form method="post" action="/knowledge/articles/restore-version" class="inline" data-confirm="Restaurar esta versão anterior?">
                                <?= csrf_field() ?><input type="hidden" name="history_id" value="<?= (int) $version['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-ghost">Restaurar</button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table></div>
    <?php endif; ?>
</section>
