<div class="page-actions">
    <form method="get" action="/inventory" class="search-form">
        <input type="search" name="q" value="<?= e($search) ?>" placeholder="Buscar por nome, categoria, modelo compatível ou fornecedor…">
        <label class="check-inline">
            <input type="checkbox" name="low" value="1" <?= $lowOnly ? 'checked' : '' ?> onchange="this.form.submit()">
            Só estoque baixo
        </label>
        <button type="submit" class="btn btn-ghost">Buscar</button>
    </form>
    <a href="/inventory/create" class="btn btn-primary">+ Novo item</a>
</div>

<section class="card">
    <?php if (!$items): ?>
        <p class="empty">Nenhum item encontrado.</p>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr><th>Item</th><th>Categoria</th><th>Compatível com</th><th class="num">Qtd.</th><th class="num">Mín.</th><th class="num">Custo</th><th class="num">Venda</th><th class="actions-col"></th></tr>
                </thead>
                <tbody>
                <?php foreach ($items as $item): ?>
                    <?php $isLow = $item['quantity'] <= $item['min_quantity']; ?>
                    <tr class="<?= $isLow ? 'row-low' : '' ?>">
                        <td>
                            <a href="/inventory/edit?id=<?= $item['id'] ?>" class="link"><?= e($item['name']) ?></a>
                            <?php if ($isLow): ?><span class="badge badge-low">baixo</span><?php endif; ?>
                        </td>
                        <td><?= e($item['category'] ?: '—') ?></td>
                        <td><?= e($item['compatible_models'] ?: '—') ?></td>
                        <td class="num qty-cell">
                            <form method="post" action="/inventory/adjust" class="inline">
                                <?= csrf_field() ?>
                                <input type="hidden" name="id" value="<?= $item['id'] ?>">
                                <button type="submit" name="delta" value="-1" class="qty-btn" aria-label="Diminuir">−</button>
                                <span class="<?= $isLow ? 'text-danger' : '' ?>"><?= $item['quantity'] ?></span>
                                <button type="submit" name="delta" value="1" class="qty-btn" aria-label="Aumentar">+</button>
                            </form>
                        </td>
                        <td class="num"><?= $item['min_quantity'] ?></td>
                        <td class="num"><?= money($item['cost_price']) ?></td>
                        <td class="num"><?= money($item['sale_price']) ?></td>
                        <td class="actions-col">
                            <a href="/inventory/edit?id=<?= $item['id'] ?>" class="btn btn-sm btn-ghost">Editar</a>
                            <form method="post" action="/inventory/delete" class="inline" data-confirm="Excluir <?= e($item['name']) ?> do estoque?">
                                <?= csrf_field() ?>
                                <input type="hidden" name="id" value="<?= $item['id'] ?>">
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
