<?php
$isEdit = (bool) $service;
$value = static fn (string $key, mixed $default = ''): string => e((string) ($service[$key] ?? $default));
?>
<div class="page-actions">
    <a href="<?= $isEdit ? '/digital-services/view?id=' . (int) $service['id'] : '/digital-services' ?>" class="btn btn-ghost">Voltar</a>
</div>

<section class="card">
    <form method="post" action="<?= $isEdit ? '/digital-services/update' : '/digital-services/store' ?>" class="digital-form">
        <?= csrf_field() ?>
        <?php if ($isEdit): ?><input type="hidden" name="id" value="<?= (int) $service['id'] ?>"><?php endif; ?>

        <div class="form-grid">
            <label>Nome do projeto
                <input name="project_name" required maxlength="180" value="<?= $value('project_name') ?>">
            </label>
            <label>Cliente cadastrado
                <select name="client_id">
                    <option value="">Sem cliente cadastrado</option>
                    <?php foreach ($clients as $client): ?>
                        <option value="<?= (int) $client['id'] ?>" <?= (int) ($service['client_id'] ?? 0) === (int) $client['id'] ? 'selected' : '' ?>><?= e($client['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Nome temporário do cliente
                <input name="temporary_client_name" maxlength="160" value="<?= $value('temporary_client_name') ?>">
            </label>
            <label>Tipo
                <select name="service_type">
                    <?php foreach (DIGITAL_SERVICE_TYPES as $key => $label): ?>
                        <option value="<?= e($key) ?>" <?= ($service['service_type'] ?? '') === $key ? 'selected' : '' ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Status
                <select name="status">
                    <?php foreach (DIGITAL_SERVICE_STATUSES as $key => $label): ?>
                        <option value="<?= e($key) ?>" <?= ($service['status'] ?? 'lead') === $key ? 'selected' : '' ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Prioridade
                <select name="priority">
                    <?php foreach (DIGITAL_SERVICE_PRIORITIES as $key => $label): ?>
                        <option value="<?= e($key) ?>" <?= ($service['priority'] ?? 'normal') === $key ? 'selected' : '' ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Responsável
                <select name="assigned_user_id">
                    <option value="">Sem responsável</option>
                    <?php foreach ($users as $user): ?>
                        <option value="<?= (int) $user['id'] ?>" <?= (int) ($service['assigned_user_id'] ?? 0) === (int) $user['id'] ? 'selected' : '' ?>><?= e($user['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Modo de progresso
                <select name="progress_mode">
                    <option value="manual" <?= ($service['progress_mode'] ?? 'manual') === 'manual' ? 'selected' : '' ?>>Manual</option>
                    <option value="checklist" <?= ($service['progress_mode'] ?? '') === 'checklist' ? 'selected' : '' ?>>Checklist</option>
                </select>
            </label>
            <label>Progresso (%)
                <input type="number" name="progress" min="0" max="100" value="<?= $value('progress', '0') ?>">
            </label>
            <label class="span-2">Descrição
                <textarea name="description" rows="3"><?= $value('description') ?></textarea>
            </label>
        </div>

        <h2 class="section-mini-title">Datas</h2>
        <div class="form-grid">
            <label>Início <input type="date" name="start_date" value="<?= $value('start_date') ?>"></label>
            <label>Prazo <input type="date" name="due_date" value="<?= $value('due_date') ?>"></label>
            <label>Entrega <input type="date" name="delivered_at" value="<?= $value('delivered_at') ?>"></label>
            <label>Próxima revisão <input type="date" name="next_review_at" value="<?= $value('next_review_at') ?>"></label>
        </div>

        <h2 class="section-mini-title">Financeiro</h2>
        <div class="form-grid">
            <label>Valor total <input name="total_value" inputmode="decimal" value="<?= $value('total_value') ?>"></label>
            <label>Entrada <input name="down_payment" inputmode="decimal" value="<?= $value('down_payment') ?>"></label>
            <label>Recebido <input name="amount_received" inputmode="decimal" value="<?= $value('amount_received') ?>"></label>
            <label>Custo estimado <input name="estimated_cost" inputmode="decimal" value="<?= $value('estimated_cost') ?>"></label>
            <label>Forma de pagamento <input name="payment_method" value="<?= $value('payment_method') ?>"></label>
            <label>Parcelas <input type="number" name="installments" min="0" value="<?= $value('installments') ?>"></label>
            <label>Recorrência mensal <input name="recurring_value" inputmode="decimal" value="<?= $value('recurring_value') ?>"></label>
            <label>Intervalo recorrente <input name="recurring_interval" value="<?= $value('recurring_interval') ?>"></label>
        </div>

        <h2 class="section-mini-title">Tecnologia</h2>
        <div class="form-grid">
            <?php foreach (['stack' => 'Stack','language' => 'Linguagem','database_name' => 'Banco','framework' => 'Framework','server_name' => 'Servidor','hosting' => 'Hospedagem','domain' => 'Domínio','ssl_status' => 'SSL','repository_url' => 'Repositório','environment' => 'Ambiente','version' => 'Versão'] as $key => $label): ?>
                <label><?= e($label) ?><input name="<?= e($key) ?>" value="<?= $value($key) ?>"></label>
            <?php endforeach; ?>
        </div>

        <h2 class="section-mini-title">Links principais</h2>
        <div class="form-grid">
            <?php foreach (['site_url' => 'Site','admin_url' => 'Painel','staging_url' => 'Staging','github_url' => 'GitHub','figma_url' => 'Figma','documentation_url' => 'Documentação','domain_url' => 'Domínio','hosting_url' => 'Hospedagem'] as $key => $label): ?>
                <label><?= e($label) ?><input name="<?= e($key) ?>" value="<?= $value($key) ?>"></label>
            <?php endforeach; ?>
        </div>

        <h2 class="section-mini-title">Observações</h2>
        <div class="form-grid">
            <label class="span-2">Notas internas<textarea name="internal_notes" rows="3"><?= $value('internal_notes') ?></textarea></label>
            <label>Observações do cliente<textarea name="client_notes" rows="3"><?= $value('client_notes') ?></textarea></label>
            <label>Pendências<textarea name="pending_items" rows="3"><?= $value('pending_items') ?></textarea></label>
            <label>Bloqueios<textarea name="blockers" rows="3"><?= $value('blockers') ?></textarea></label>
            <label>Decisões importantes<textarea name="important_decisions" rows="3"><?= $value('important_decisions') ?></textarea></label>
        </div>

        <?php if (!$isEdit): ?>
            <label class="check-inline"><input type="checkbox" name="apply_template" value="1" checked> Aplicar checklist padrão do tipo escolhido</label>
        <?php endif; ?>

        <div class="form-actions">
            <button class="btn btn-primary" type="submit"><?= $isEdit ? 'Salvar alterações' : 'Criar serviço' ?></button>
        </div>
    </form>
</section>
