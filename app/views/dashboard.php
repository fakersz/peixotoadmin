<div class="stats-grid">
    <a href="/orders" class="stat-card">
        <div class="stat-label">OS abertas</div>
        <div class="stat-value"><?= $openCount ?></div>
        <div class="stat-hint">Em andamento na bancada</div>
    </a>
    <a href="/orders?status=aguardando_peca" class="stat-card">
        <div class="stat-label">Aguardando peça</div>
        <div class="stat-value stat-warn"><?= $waitingParts ?></div>
        <div class="stat-hint">OS paradas por peça</div>
    </a>
    <a href="/orders?status=entregue" class="stat-card">
        <div class="stat-label">OS finalizadas</div>
        <div class="stat-value stat-ok"><?= $finishedCount ?></div>
        <div class="stat-hint">Entregues ao cliente</div>
    </a>
    <a href="/inventory?low=1" class="stat-card">
        <div class="stat-label">Estoque baixo</div>
        <div class="stat-value <?= $lowStockCount > 0 ? 'stat-danger' : 'stat-ok' ?>"><?= $lowStockCount ?></div>
        <div class="stat-hint">Itens no mínimo ou abaixo</div>
    </a>
</div>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-label">Receita do mês</div>
        <div class="stat-value stat-accent"><?= money($summary['income']) ?></div>
        <div class="stat-hint"><?= e(date('m/Y')) ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Entradas</div>
        <div class="stat-value stat-ok"><?= money($summary['income']) ?></div>
        <div class="stat-hint">Recebimentos do mês</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Saídas</div>
        <div class="stat-value stat-danger"><?= money($summary['expense']) ?></div>
        <div class="stat-hint">Despesas do mês</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Lucro estimado</div>
        <div class="stat-value <?= $summary['profit'] >= 0 ? 'stat-ok' : 'stat-danger' ?>"><?= money($summary['profit']) ?></div>
        <div class="stat-hint">Entradas − saídas</div>
    </div>
</div>

<div class="panel-grid">
    <section class="card">
        <div class="card-header">
            <h2>Últimas ordens de serviço</h2>
            <a href="/orders/create" class="btn btn-sm btn-primary">+ Nova OS</a>
        </div>
        <?php if (!$recentOrders): ?>
            <p class="empty">Nenhuma OS cadastrada ainda. <a href="/orders/create">Criar a primeira</a>.</p>
        <?php else: ?>
            <div class="table-wrap">
                <table>
                    <thead><tr><th>OS</th><th>Cliente</th><th>Modelo</th><th>Status</th><th class="num">Total</th></tr></thead>
                    <tbody>
                    <?php foreach ($recentOrders as $os): ?>
                        <tr>
                            <td>
                                <a href="/orders/view?id=<?= $os['id'] ?>" class="link">#<?= $os['id'] ?></a>
                                <?php if (!empty($os['is_fast_order'])): ?><span class="badge badge-fast">⚡</span><?php endif; ?>
                            </td>
                            <td><?= e($os['client_name']) ?></td>
                            <td><?= e($os['model_code'] ?: '—') ?></td>
                            <td><?= status_badge($os['status']) ?></td>
                            <td class="num"><?= money($os['total_price']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>

    <section class="card">
        <div class="card-header">
            <h2>Estoque baixo</h2>
            <a href="/inventory?low=1" class="btn btn-sm btn-ghost">Ver tudo</a>
        </div>
        <?php if (!$lowStock): ?>
            <p class="empty">Nenhum item abaixo do mínimo. 👌</p>
        <?php else: ?>
            <div class="table-wrap">
                <table>
                    <thead><tr><th>Item</th><th class="num">Qtd.</th><th class="num">Mín.</th></tr></thead>
                    <tbody>
                    <?php foreach ($lowStock as $item): ?>
                        <tr>
                            <td><a href="/inventory/edit?id=<?= $item['id'] ?>" class="link"><?= e($item['name']) ?></a></td>
                            <td class="num text-danger"><?= $item['quantity'] ?></td>
                            <td class="num"><?= $item['min_quantity'] ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>
</div>
