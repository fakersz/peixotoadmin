<div class="page-actions">
    <form method="get" action="/orders" class="search-form">
        <input type="search" name="q" value="<?= e($filters['q']) ?>" placeholder="Buscar por nº da OS, título, cliente, telefone ou equipamento...">
        <?php if ($filters['fast'] !== ''): ?>
            <input type="hidden" name="fast" value="<?= e($filters['fast']) ?>">
        <?php endif; ?>
        <button type="submit" class="btn btn-ghost">Buscar</button>
    </form>
    <?php if (order_can('fast_create')): ?>
        <a href="/orders/fast" class="btn btn-fast">⚡ Fast OS</a>
    <?php endif; ?>
    <a href="/orders/create" class="btn btn-primary">+ Nova OS</a>
</div>

<div class="filter-chips">
    <a href="/orders" class="chip<?= $filters['status'] === '' && $filters['fast'] === '' ? ' active' : '' ?>">Todas</a>
    <?php foreach (os_statuses() as $value => $label): ?>
        <a href="/orders?status=<?= $value ?><?= $filters['fast'] !== '' ? '&fast=' . e($filters['fast']) : '' ?>" class="chip<?= $filters['status'] === $value ? ' active' : '' ?>"><?= e($label) ?></a>
    <?php endforeach; ?>
    <a href="/orders?fast=1<?= $filters['status'] !== '' ? '&status=' . e($filters['status']) : '' ?>" class="chip<?= $filters['fast'] === '1' ? ' active' : '' ?>">⚡ Fast OS</a>
</div>

<section class="card">
    <?php if (!$orders): ?>
        <p class="empty">Nenhuma OS encontrada com esse filtro.</p>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr><th>OS</th><th>Título</th><th>Cliente</th><th>Equipamento</th><th>Status</th><th>Prioridade</th><th>Pagamento</th><th class="num">Total</th><th>Abertura</th></tr>
                </thead>
                <tbody>
                <?php foreach ($orders as $os): ?>
                    <tr>
                        <td>
                            <a href="/orders/view?id=<?= $os['id'] ?>" class="link">#<?= $os['id'] ?></a>
                            <?php if (!empty($os['is_fast_order'])): ?><span class="badge badge-fast">⚡</span><?php endif; ?>
                        </td>
                        <td><a href="/orders/view?id=<?= $os['id'] ?>" class="link-plain"><?= e($os['title']) ?></a></td>
                        <td><?= e($os['client_name'] ?: '—') ?></td>
                        <td><?= e(trim(($os['device_category'] ?: '') . ' ' . ($os['model_code'] ?: '')) ?: ($os['quick_device_name'] ?? '—')) ?></td>
                        <td><?= status_badge($os['status']) ?></td>
                        <td><?= priority_badge($os['priority']) ?></td>
                        <td><?= payment_badge($os['payment_status']) ?></td>
                        <td class="num"><?= money($os['total_price']) ?></td>
                        <td><?= br_date($os['opened_at']) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
