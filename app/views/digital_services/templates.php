<div class="page-actions">
    <a href="/digital-services" class="btn btn-ghost">← Serviços Digitais</a>
</div>

<div class="panel-grid">
    <section class="card">
        <div class="card-header"><h2>Novo template</h2></div>
        <form method="post" action="/digital-services/template-store">
            <?= csrf_field() ?>
            <label>Nome <input name="name" required maxlength="140"></label>
            <label>Tipo
                <select name="service_type">
                    <option value="">Todos</option>
                    <?php foreach (DIGITAL_SERVICE_TYPES as $key => $label): ?>
                        <option value="<?= e($key) ?>"><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Descrição <textarea name="description" rows="2"></textarea></label>
            <label>Tarefas, uma por linha <textarea name="items" rows="10" required></textarea></label>
            <div class="form-actions"><button class="btn btn-primary" type="submit">Criar template</button></div>
        </form>
    </section>

    <section class="card">
        <div class="card-header"><h2>Templates próprios</h2></div>
        <?php if (!$templates): ?>
            <p class="empty">Nenhum template próprio criado.</p>
        <?php else: ?>
            <div class="table-wrap">
                <table>
                    <thead><tr><th>Nome</th><th>Tipo</th><th>Tarefas</th><th>Criado</th></tr></thead>
                    <tbody>
                    <?php foreach ($templates as $template): ?>
                        <tr>
                            <td><?= e($template['name']) ?></td>
                            <td><?= e(DIGITAL_SERVICE_TYPES[$template['service_type']] ?? 'Todos') ?></td>
                            <td><?= (int) $template['item_count'] ?></td>
                            <td><?= br_date($template['created_at'], true) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>
</div>
