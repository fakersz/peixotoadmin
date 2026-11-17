<?php $clientName = $service['client_name'] ?? $service['temporary_client_name'] ?? 'Cliente'; ?>
<section class="card digital-hero">
    <div>
        <div class="badge-row">
            <span class="badge badge-status-<?= e($service['status']) ?>"><?= e(DIGITAL_SERVICE_STATUSES[$service['status']] ?? $service['status']) ?></span>
            <span class="badge badge-priority-<?= e($service['priority']) ?>"><?= e(DIGITAL_SERVICE_PRIORITIES[$service['priority']] ?? $service['priority']) ?></span>
        </div>
        <h2><?= e($service['project_name']) ?></h2>
        <p class="hint"><?= e($clientName) ?> · <?= e(DIGITAL_SERVICE_TYPES[$service['service_type']] ?? $service['service_type']) ?></p>
    </div>
    <div class="digital-progress-box">
        <strong><?= (int) $service['progress'] ?>%</strong>
        <div class="progress-line"><span style="width: <?= (int) $service['progress'] ?>%"></span></div>
        <small>Andamento do projeto</small>
    </div>
</section>

<div class="panel-grid">
    <section class="card">
        <div class="card-header"><h2>Checklist</h2></div>
        <?php foreach ($tasks as $task): ?>
            <div class="digital-task">
                <span class="task-check <?= $task['status'] === 'concluida' ? 'done' : '' ?>"><?= $task['status'] === 'concluida' ? '✓' : '' ?></span>
                <div><strong><?= e($task['title']) ?></strong><small><?= br_date($task['due_date']) ?></small></div>
            </div>
        <?php endforeach; ?>
        <?php if (!$tasks): ?><p class="empty">Nenhuma tarefa publicada.</p><?php endif; ?>
    </section>

    <section class="card">
        <div class="card-header"><h2>Documentos e cobranças</h2></div>
        <?php foreach ($documents as $document): ?>
            <p><?= e($document['title']) ?> <span class="hint"><?= br_date($document['created_at'], true) ?></span></p>
        <?php endforeach; ?>
        <?php foreach ($billing as $bill): ?>
            <p><?= e($bill['description']) ?> · <strong><?= money($bill['amount']) ?></strong> <span class="badge"><?= e($bill['status']) ?></span></p>
        <?php endforeach; ?>
        <?php if (!$documents && !$billing): ?><p class="empty">Nada publicado ainda.</p><?php endif; ?>
    </section>
</div>
