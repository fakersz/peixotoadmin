<?php $activeKnowledgeSection = 'review_queue'; ?>
<div class="page-actions"><span class="hint">Registros com dedup ambíguo, confiança baixa ou conflito de fonte caem aqui em vez de serem inseridos automaticamente.</span></div>
<?php require APP_PATH . '/views/knowledge/_tabs.php'; ?>
<section class="card">
    <?php if (!$items): ?>
        <p class="empty">Fila de revisão vazia.</p>
    <?php else: ?>
        <div class="table-wrap"><table>
            <thead><tr><th>Tipo</th><th>Motivo</th><th>Payload</th><th>Criado em</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($items as $item): ?>
                <tr>
                    <td><span class="badge badge-code"><?= e($item['entity_type']) ?></span></td>
                    <td><?= e($item['reason']) ?></td>
                    <td><code class="review-payload"><?= e(mb_substr((string) $item['payload'], 0, 160)) ?></code></td>
                    <td><?= e($item['created_at']) ?></td>
                    <td class="actions-col">
                        <form method="post" action="/knowledge/review-queue/resolve" class="inline-form">
                            <?= csrf_field() ?>
                            <input type="hidden" name="id" value="<?= (int) $item['id'] ?>">
                            <input type="hidden" name="status" value="resolvido">
                            <button type="submit" class="btn btn-sm btn-ghost">Marcar resolvido</button>
                        </form>
                        <form method="post" action="/knowledge/review-queue/resolve" class="inline-form">
                            <?= csrf_field() ?>
                            <input type="hidden" name="id" value="<?= (int) $item['id'] ?>">
                            <input type="hidden" name="status" value="descartado">
                            <button type="submit" class="btn btn-sm btn-ghost">Descartar</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table></div>
    <?php endif; ?>
</section>
