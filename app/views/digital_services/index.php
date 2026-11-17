<?php
$clientName = static fn (array $service): string => $service['client_name'] ?: $service['temporary_client_name'] ?: 'Cliente temporário';
$moneyValue = static fn ($value): string => money((float) ($value ?? 0));
?>
<div class="page-actions">
    <form method="get" action="/digital-services" class="search-form search-form-wide">
        <input type="search" name="q" placeholder="Buscar projeto, cliente, domínio..." value="<?= e($filters['q']) ?>">
        <select name="service_type">
            <option value="">Tipo</option>
            <?php foreach (DIGITAL_SERVICE_TYPES as $value => $label): ?>
                <option value="<?= e($value) ?>" <?= $filters['service_type'] === $value ? 'selected' : '' ?>><?= e($label) ?></option>
            <?php endforeach; ?>
        </select>
        <select name="status">
            <option value="">Status</option>
            <?php foreach (DIGITAL_SERVICE_STATUSES as $value => $label): ?>
                <option value="<?= e($value) ?>" <?= $filters['status'] === $value ? 'selected' : '' ?>><?= e($label) ?></option>
            <?php endforeach; ?>
        </select>
        <select name="priority">
            <option value="">Prioridade</option>
            <?php foreach (DIGITAL_SERVICE_PRIORITIES as $value => $label): ?>
                <option value="<?= e($value) ?>" <?= $filters['priority'] === $value ? 'selected' : '' ?>><?= e($label) ?></option>
            <?php endforeach; ?>
        </select>
        <select name="sort">
            <?php foreach (['recent' => 'Mais recente','due_date' => 'Prazo','progress' => 'Progresso','client' => 'Cliente','value' => 'Valor','priority' => 'Prioridade','status' => 'Status'] as $value => $label): ?>
                <option value="<?= e($value) ?>" <?= $filters['sort'] === $value ? 'selected' : '' ?>><?= e($label) ?></option>
            <?php endforeach; ?>
        </select>
        <label class="check-inline"><input type="checkbox" name="overdue" value="1" <?= $filters['overdue'] ? 'checked' : '' ?>> Atrasados</label>
        <label class="check-inline"><input type="checkbox" name="balance_due" value="1" <?= $filters['balance_due'] ? 'checked' : '' ?>> Saldo pendente</label>
        <button class="btn btn-ghost" type="submit">Filtrar</button>
    </form>
    <?php if (digital_service_can('create')): ?>
        <a href="/digital-services/create" class="btn btn-primary">Novo serviço</a>
    <?php endif; ?>
</div>

<div class="stats-grid stats-grid-5 digital-summary">
    <div class="stat-card"><div class="stat-label">Ativos</div><div class="stat-value"><?= (int) $summary['active'] ?></div></div>
    <div class="stat-card"><div class="stat-label">Aguardando cliente</div><div class="stat-value stat-warn"><?= (int) $summary['waiting_client'] ?></div></div>
    <div class="stat-card"><div class="stat-label">Atrasados</div><div class="stat-value stat-danger"><?= (int) $summary['overdue'] ?></div></div>
    <div class="stat-card"><div class="stat-label">Em desenvolvimento</div><div class="stat-value stat-accent"><?= (int) $summary['development'] ?></div></div>
    <div class="stat-card"><div class="stat-label">Publicados</div><div class="stat-value stat-ok"><?= (int) $summary['published'] ?></div></div>
    <div class="stat-card"><div class="stat-label">Saldo pendente</div><div class="stat-value stat-warn"><?= $moneyValue($summary['balance_due']) ?></div></div>
    <div class="stat-card"><div class="stat-label">Receita prevista</div><div class="stat-value stat-ok"><?= $moneyValue($summary['forecast']) ?></div></div>
    <div class="stat-card"><div class="stat-label">Recorrente</div><div class="stat-value stat-accent"><?= $moneyValue($summary['recurring']) ?></div></div>
</div>

<?php if (!$services): ?>
    <section class="card"><p class="empty">Nenhum serviço digital encontrado.</p></section>
<?php else: ?>
    <section class="digital-grid">
        <?php foreach ($services as $service): ?>
            <?php
            $status = DIGITAL_SERVICE_STATUSES[$service['status']] ?? $service['status'];
            $priority = DIGITAL_SERVICE_PRIORITIES[$service['priority']] ?? $service['priority'];
            ?>
            <details class="digital-card priority-<?= e($service['priority']) ?>" data-digital-card>
                <summary>
                    <div class="digital-card-top">
                        <div>
                            <h2><?= e($service['project_name']) ?></h2>
                            <p><?= e($clientName($service)) ?> · <?= e(DIGITAL_SERVICE_TYPES[$service['service_type']] ?? $service['service_type']) ?></p>
                        </div>
                        <span class="badge badge-priority-<?= e($service['priority']) ?>"><?= e($priority) ?></span>
                    </div>
                    <div class="digital-meta">
                        <span class="badge badge-status-<?= e($service['status']) ?>"><?= e($status) ?></span>
                        <span><?= br_date($service['due_date']) ?></span>
                        <span><?= e($service['assigned_user_name'] ?: 'Sem responsável') ?></span>
                        <?php if ($service['is_overdue']): ?><span class="badge badge-out">Atrasado</span><?php endif; ?>
                    </div>
                    <div class="progress-line"><span style="width: <?= (int) $service['progress'] ?>%"></span></div>
                    <div class="digital-card-foot">
                        <strong><?= (int) $service['progress'] ?>%</strong>
                        <span><?= $service['total_value'] !== null ? $moneyValue($service['total_value']) : 'Sem valor' ?></span>
                    </div>
                </summary>
                <div class="digital-expanded">
                    <div class="detail-grid">
                        <div><dt>Descrição</dt><dd><?= nl2br(e($service['description'] ?: '—')) ?></dd></div>
                        <div><dt>Início</dt><dd><?= br_date($service['start_date']) ?></dd></div>
                        <div><dt>Próxima revisão</dt><dd><?= br_date($service['next_review_at']) ?></dd></div>
                        <div><dt>Saldo pendente</dt><dd><?= $moneyValue($service['balance_due']) ?></dd></div>
                        <div><dt>Stack</dt><dd><?= e($service['stack'] ?: '—') ?></dd></div>
                        <div><dt>Domínio</dt><dd><?= e($service['domain'] ?: '—') ?></dd></div>
                    </div>
                    <div class="digital-mini-row">
                        <span><?= (int) $service['task_done_count'] ?>/<?= (int) $service['task_count'] ?> tarefas</span>
                        <span><?= (int) $service['link_count'] ?> links</span>
                        <span><?= (int) $service['file_count'] ?> arquivos</span>
                        <span><?= (int) $service['note_count'] ?> notas</span>
                        <span><?= !empty($service['kanban_card_id']) ? '1 cartão Kanban' : 'sem Kanban' ?></span>
                    </div>
                    <div class="form-actions">
                        <a class="btn btn-sm btn-primary" href="/digital-services/view?id=<?= (int) $service['id'] ?>">Abrir</a>
                        <?php if (digital_service_can('edit', $service)): ?><a class="btn btn-sm btn-ghost" href="/digital-services/edit?id=<?= (int) $service['id'] ?>">Editar</a><?php endif; ?>
                    </div>
                </div>
            </details>
        <?php endforeach; ?>
    </section>
<?php endif; ?>
