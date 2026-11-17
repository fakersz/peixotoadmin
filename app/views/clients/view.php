<div class="page-actions">
    <a href="/clients" class="btn btn-ghost">← Clientes</a>
    <div class="spacer"></div>
    <a href="/devices/create?client_id=<?= $client['id'] ?>" class="btn btn-ghost">+ Equipamento</a>
    <a href="/orders/create?client_id=<?= $client['id'] ?>" class="btn btn-primary">+ Nova OS</a>
    <a href="/clients/edit?id=<?= $client['id'] ?>" class="btn btn-ghost">Editar</a>
</div>

<section class="card">
    <div class="card-header"><h2>Dados do cliente</h2></div>
    <dl class="detail-grid">
        <div><dt>Nome</dt><dd><?= e($client['name']) ?></dd></div>
        <div><dt>Telefone</dt><dd><?= e($client['phone'] ?: '—') ?></dd></div>
        <div><dt>E-mail</dt><dd><?= e($client['email'] ?: '—') ?></dd></div>
        <div><dt>Documento</dt><dd><?= e($client['document'] ?: '—') ?></dd></div>
        <div><dt>Endereço</dt><dd><?= e($client['address'] ?: '—') ?></dd></div>
        <div><dt>Cliente desde</dt><dd><?= br_date($client['created_at']) ?></dd></div>
        <?php if ($client['notes']): ?>
            <div class="span-all"><dt>Observações</dt><dd><?= nl2br(e($client['notes'])) ?></dd></div>
        <?php endif; ?>
    </dl>
</section>

<section class="card">
    <div class="card-header">
        <h2>Equipamentos (<?= count($devices) ?>)</h2>
    </div>
    <?php if (!$devices): ?>
        <p class="empty">Nenhum equipamento cadastrado para este cliente.</p>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Categoria</th><th>Modelo</th><th>Código</th><th>Série</th><th>Ano</th><th class="actions-col"></th></tr></thead>
                <tbody>
                <?php foreach ($devices as $device): ?>
                    <tr>
                        <td><?= e($device['category']) ?></td>
                        <td><?= e($device['model'] ?: '—') ?></td>
                        <td><?= e($device['model_code'] ?: '—') ?></td>
                        <td><?= e($device['serial_number'] ?: '—') ?></td>
                        <td><?= e($device['year'] ?: '—') ?></td>
                        <td class="actions-col">
                            <a href="/devices/edit?id=<?= $device['id'] ?>" class="btn btn-sm btn-ghost">Editar</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

<section class="card">
    <div class="card-header"><h2>Histórico de ordens de serviço (<?= count($orders) ?>)</h2></div>
    <?php if (!$orders): ?>
        <p class="empty">Nenhuma OS registrada para este cliente.</p>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead><tr><th>OS</th><th>Título</th><th>Equipamento</th><th>Status</th><th>Pagamento</th><th class="num">Total</th><th>Abertura</th></tr></thead>
                <tbody>
                <?php foreach ($orders as $os): ?>
                    <tr>
                        <td><a href="/orders/view?id=<?= $os['id'] ?>" class="link">#<?= $os['id'] ?></a></td>
                        <td><?= e($os['title']) ?></td>
                        <td><?= e(trim($os['device_category'] . ' ' . ($os['model_code'] ?: ''))) ?></td>
                        <td><?= status_badge($os['status']) ?></td>
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
