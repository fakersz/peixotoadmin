<div class="page-actions">
    <form method="get" action="/financial" class="search-form">
        <input type="month" name="month" value="<?= e($month) ?>" onchange="this.form.submit()">
    </form>
    <a href="/financial/create" class="btn btn-primary">+ Novo lançamento</a>
</div>

<div class="stats-grid stats-grid-3">
    <div class="stat-card">
        <div class="stat-label">Entradas</div>
        <div class="stat-value stat-ok"><?= money($summary['income']) ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Saídas</div>
        <div class="stat-value stat-danger"><?= money($summary['expense']) ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Lucro estimado</div>
        <div class="stat-value <?= $summary['profit'] >= 0 ? 'stat-ok' : 'stat-danger' ?>"><?= money($summary['profit']) ?></div>
    </div>
</div>

<section class="card">
    <div class="card-header"><h2>Lançamentos de <?= e(date('m/Y', strtotime($month . '-01'))) ?></h2></div>
    <?php if (!$entries): ?>
        <p class="empty">Nenhum lançamento neste mês.</p>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr><th>Data</th><th>Tipo</th><th>Descrição</th><th>Categoria</th><th>OS</th><th class="num">Valor</th><th class="actions-col"></th></tr>
                </thead>
                <tbody>
                <?php foreach ($entries as $entry): ?>
                    <tr>
                        <td><?= br_date($entry['entry_date']) ?></td>
                        <td>
                            <span class="badge <?= $entry['type'] === 'entrada' ? 'badge-in' : 'badge-out' ?>">
                                <?= $entry['type'] === 'entrada' ? 'Entrada' : 'Saída' ?>
                            </span>
                        </td>
                        <td><?= e($entry['description']) ?></td>
                        <td><?= e($entry['category'] ?: '—') ?></td>
                        <td>
                            <?php if ($entry['reference_type'] === 'service_order' && $entry['reference_id']): ?>
                                <a href="/orders/view?id=<?= $entry['reference_id'] ?>" class="link">#<?= $entry['reference_id'] ?></a>
                            <?php else: ?>—<?php endif; ?>
                        </td>
                        <td class="num <?= $entry['type'] === 'entrada' ? 'text-ok' : 'text-danger' ?>">
                            <?= ($entry['type'] === 'entrada' ? '+' : '−') . ' ' . money($entry['amount']) ?>
                        </td>
                        <td class="actions-col">
                            <form method="post" action="/financial/delete" class="inline" data-confirm="Excluir este lançamento?">
                                <?= csrf_field() ?>
                                <input type="hidden" name="id" value="<?= $entry['id'] ?>">
                                <input type="hidden" name="month" value="<?= e($month) ?>">
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
