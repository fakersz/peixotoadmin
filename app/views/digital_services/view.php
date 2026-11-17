<?php
$clientName = $service['client_name'] ?: $service['temporary_client_name'] ?: 'Cliente temporário';
$balance = (float) ($service['balance_due'] ?? 0);
$mainLinks = [
    'Site' => $service['site_url'] ?? null,
    'Painel' => $service['admin_url'] ?? null,
    'Staging' => $service['staging_url'] ?? null,
    'GitHub' => $service['github_url'] ?? null,
    'Figma' => $service['figma_url'] ?? null,
    'Documentação' => $service['documentation_url'] ?? null,
    'Domínio' => $service['domain_url'] ?? null,
    'Hospedagem' => $service['hosting_url'] ?? null,
];
?>
<div class="page-actions">
    <a href="/digital-services" class="btn btn-ghost">← Serviços Digitais</a>
    <div class="spacer"></div>
    <?php if (digital_service_can('edit', $service)): ?><a href="/digital-services/edit?id=<?= (int) $service['id'] ?>" class="btn btn-ghost">Editar</a><?php endif; ?>
    <?php if (digital_service_can('create')): ?>
        <form method="post" action="/digital-services/duplicate" class="inline">
            <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $service['id'] ?>">
            <button class="btn btn-ghost" type="submit">Duplicar</button>
        </form>
    <?php endif; ?>
    <?php if (digital_service_can('archive', $service)): ?>
        <form method="post" action="<?= $service['is_archived'] ? '/digital-services/restore' : '/digital-services/archive' ?>" class="inline">
            <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $service['id'] ?>">
            <button class="btn btn-ghost" type="submit"><?= $service['is_archived'] ? 'Restaurar' : 'Arquivar' ?></button>
        </form>
        <form method="post" action="/digital-services/delete" class="inline" data-confirm="Excluir este projeto?">
            <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $service['id'] ?>">
            <button class="btn btn-danger-ghost" type="submit">Excluir</button>
        </form>
    <?php endif; ?>
</div>

<section class="card digital-hero">
    <div>
        <div class="badge-row">
            <span class="badge badge-status-<?= e($service['status']) ?>"><?= e(DIGITAL_SERVICE_STATUSES[$service['status']] ?? $service['status']) ?></span>
            <span class="badge badge-priority-<?= e($service['priority']) ?>"><?= e(DIGITAL_SERVICE_PRIORITIES[$service['priority']] ?? $service['priority']) ?></span>
            <?php if ($service['is_overdue']): ?><span class="badge badge-out">Atrasado</span><?php endif; ?>
        </div>
        <h2><?= e($service['project_name']) ?></h2>
        <p class="hint"><?= e($clientName) ?> · <?= e(DIGITAL_SERVICE_TYPES[$service['service_type']] ?? $service['service_type']) ?></p>
    </div>
    <div class="digital-progress-box">
        <strong><?= (int) $service['progress'] ?>%</strong>
        <div class="progress-line"><span style="width: <?= (int) $service['progress'] ?>%"></span></div>
        <small><?= $service['progress_mode'] === 'checklist' ? 'Calculado pela checklist' : 'Progresso manual' ?></small>
    </div>
</section>

<div class="digital-actions-grid">
    <form method="post" action="/digital-services/status" class="card">
        <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $service['id'] ?>">
        <label>Status
            <select name="status">
                <?php foreach (DIGITAL_SERVICE_STATUSES as $key => $label): ?>
                    <option value="<?= e($key) ?>" <?= $service['status'] === $key ? 'selected' : '' ?>><?= e($label) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <button class="btn btn-sm btn-primary" type="submit">Atualizar</button>
    </form>
    <form method="post" action="/digital-services/progress" class="card">
        <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $service['id'] ?>">
        <label>Progresso manual
            <input type="number" name="progress" min="0" max="100" value="<?= (int) $service['progress'] ?>">
        </label>
        <button class="btn btn-sm btn-primary" type="submit">Salvar</button>
    </form>
    <div class="card">
        <div class="section-mini-title">Integrações preparadas</div>
        <div class="badge-row">
            <?php if ($financialEntry): ?>
                <a class="btn btn-sm btn-ghost" href="/financial?month=<?= e(substr($financialEntry['entry_date'], 0, 7)) ?>">Abrir financeiro</a>
            <?php else: ?>
                <form method="post" action="/digital-services/finance" class="inline">
                    <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $service['id'] ?>">
                    <input type="hidden" name="amount" value="<?= e((string) ($service['balance_due'] ?: $service['total_value'] ?: 0)) ?>">
                    <button class="btn btn-sm btn-ghost" type="submit">Gerar financeiro</button>
                </form>
            <?php endif; ?>
            <?php if (!empty($service['kanban_card_id'])): ?>
                <a class="btn btn-sm btn-ghost" href="/trello/card?id=<?= (int) $service['kanban_card_id'] ?>">Abrir Kanban</a>
            <?php else: ?>
                <form method="post" action="/digital-services/kanban" class="inline">
                    <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $service['id'] ?>">
                    <button class="btn btn-sm btn-ghost" type="submit">Criar Kanban</button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>

<section class="card">
    <div class="card-header">
        <h2>Portal, documentos e cobranças</h2>
        <a class="btn btn-sm btn-ghost" href="/digital-services/templates">Templates</a>
    </div>
    <div class="digital-actions-grid">
        <div>
            <div class="section-mini-title">Documentos</div>
            <div class="badge-row">
                <form method="post" action="/digital-services/document" class="inline">
                    <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $service['id'] ?>"><input type="hidden" name="type" value="proposta">
                    <button class="btn btn-sm btn-primary" type="submit">Gerar proposta PDF</button>
                </form>
                <form method="post" action="/digital-services/document" class="inline">
                    <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $service['id'] ?>"><input type="hidden" name="type" value="contrato">
                    <button class="btn btn-sm btn-ghost" type="submit">Gerar contrato PDF</button>
                </form>
            </div>
            <?php foreach ($documents as $document): ?>
                <p><a class="link" href="/digital-services/document-pdf?id=<?= (int) $document['id'] ?>" target="_blank"><?= e($document['title']) ?></a></p>
            <?php endforeach; ?>
        </div>
        <div>
            <div class="section-mini-title">Cobranças</div>
            <form method="post" action="/digital-services/billing" class="inline-form">
                <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $service['id'] ?>">
                <input name="description" placeholder="Descrição">
                <input name="amount" inputmode="decimal" placeholder="Valor">
                <input type="date" name="due_date">
                <button class="btn btn-sm btn-primary" type="submit">Criar</button>
            </form>
            <?php foreach ($billing as $bill): ?>
                <p><?= e($bill['description']) ?> · <strong><?= money($bill['amount']) ?></strong> <span class="badge"><?= e($bill['status']) ?></span></p>
            <?php endforeach; ?>
        </div>
        <div>
            <div class="section-mini-title">Portal e automações</div>
            <form method="post" action="/digital-services/portal" class="inline">
                <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $service['id'] ?>">
                <button class="btn btn-sm btn-primary" type="submit">Gerar portal</button>
            </form>
            <?php foreach ($portalTokens as $token): ?>
                <p class="hint">Portal criado em <?= br_date($token['created_at'], true) ?><?= $token['revoked_at'] ? ' · revogado' : '' ?></p>
            <?php endforeach; ?>
            <form method="post" action="/digital-services/automation" class="inline-form">
                <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $service['id'] ?>">
                <input name="action" placeholder="Ação">
                <input name="message" placeholder="Mensagem/log">
                <button class="btn btn-sm btn-ghost" type="submit">Registrar</button>
            </form>
        </div>
    </div>
</section>

<div class="panel-grid">
    <section class="card">
        <div class="card-header"><h2>Resumo</h2></div>
        <dl class="detail-grid">
            <div><dt>Descrição</dt><dd><?= nl2br(e($service['description'] ?: '—')) ?></dd></div>
            <div><dt>Responsável</dt><dd><?= e($service['assigned_user_name'] ?: '—') ?></dd></div>
            <div><dt>Início</dt><dd><?= br_date($service['start_date']) ?></dd></div>
            <div><dt>Prazo</dt><dd><?= br_date($service['due_date']) ?></dd></div>
            <div><dt>Entrega</dt><dd><?= br_date($service['delivered_at']) ?></dd></div>
            <div><dt>Próxima revisão</dt><dd><?= br_date($service['next_review_at']) ?></dd></div>
        </dl>
    </section>

    <section class="card">
        <div class="card-header"><h2>Financeiro</h2></div>
        <dl class="detail-grid">
            <div><dt>Valor total</dt><dd><?= money($service['total_value']) ?></dd></div>
            <div><dt>Entrada</dt><dd><?= money($service['down_payment']) ?></dd></div>
            <div><dt>Recebido</dt><dd><?= money($service['amount_received']) ?></dd></div>
            <div><dt>Saldo</dt><dd><?= money($balance) ?></dd></div>
            <div><dt>Custo estimado</dt><dd><?= money($service['estimated_cost']) ?></dd></div>
            <div><dt>Pagamento</dt><dd><?= e($service['payment_method'] ?: '—') ?></dd></div>
            <div><dt>Parcelas</dt><dd><?= e($service['installments'] ?: '—') ?></dd></div>
            <div><dt>Recorrência</dt><dd><?= $service['recurring_value'] ? money($service['recurring_value']) . ' · ' . e($service['recurring_interval'] ?: 'mensal') : '—' ?></dd></div>
        </dl>
    </section>
</div>

<section class="card">
    <div class="card-header"><h2>Tecnologia e links</h2></div>
    <div class="detail-grid">
        <?php foreach (['stack' => 'Stack','language' => 'Linguagem','database_name' => 'Banco','framework' => 'Framework','server_name' => 'Servidor','hosting' => 'Hospedagem','domain' => 'Domínio','ssl_status' => 'SSL','repository_url' => 'Repositório','environment' => 'Ambiente','version' => 'Versão'] as $key => $label): ?>
            <div><dt><?= e($label) ?></dt><dd><?= e($service[$key] ?: '—') ?></dd></div>
        <?php endforeach; ?>
    </div>
    <div class="badge-row">
        <?php foreach ($mainLinks as $label => $url): ?>
            <?php if ($url): ?><a class="btn btn-sm btn-ghost" href="<?= e($url) ?>" target="_blank" rel="noopener noreferrer"><?= e($label) ?></a><?php endif; ?>
        <?php endforeach; ?>
    </div>
</section>

<div class="panel-grid">
    <section class="card">
        <div class="card-header"><h2>Checklist</h2><span class="badge"><?= count($tasks) ?> tarefas</span></div>
        <?php if (!$tasks): ?><p class="empty">Checklist vazia.</p><?php endif; ?>
        <?php foreach ($tasks as $task): ?>
            <form method="post" action="/digital-services/checklist" class="digital-task">
                <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $service['id'] ?>"><input type="hidden" name="task_id" value="<?= (int) $task['id'] ?>">
                <button class="task-check <?= $task['status'] === 'concluida' ? 'done' : '' ?>" type="submit"><?= $task['status'] === 'concluida' ? '✓' : '' ?></button>
                <div><strong><?= e($task['title']) ?></strong><small><?= br_date($task['due_date']) ?> · <?= e($task['assigned_user_name'] ?: 'sem responsável') ?></small></div>
            </form>
        <?php endforeach; ?>
        <?php if ($templates): ?>
            <form method="post" action="/digital-services/template-apply" class="inline-form">
                <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $service['id'] ?>">
                <select name="template_id">
                    <?php foreach ($templates as $template): ?>
                        <option value="<?= (int) $template['id'] ?>"><?= e($template['name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <button class="btn btn-sm btn-ghost" type="submit">Aplicar template</button>
            </form>
        <?php endif; ?>
        <form method="post" action="/digital-services/checklist" class="inline-form">
            <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $service['id'] ?>">
            <input name="title" placeholder="Nova tarefa">
            <input type="date" name="due_date">
            <button class="btn btn-sm btn-primary" type="submit">Adicionar</button>
        </form>
    </section>

    <section class="card">
        <div class="card-header"><h2>Links extras</h2></div>
        <?php foreach ($links as $link): ?>
            <p><a class="link" href="<?= e($link['url']) ?>" target="_blank" rel="noopener noreferrer"><?= e($link['label']) ?></a> <span class="hint"><?= e($link['type'] ?: '') ?></span></p>
        <?php endforeach; ?>
        <form method="post" action="/digital-services/link" class="inline-form">
            <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $service['id'] ?>">
            <input name="label" placeholder="Rótulo">
            <input name="url" placeholder="https://">
            <button class="btn btn-sm btn-primary" type="submit">Adicionar</button>
        </form>
    </section>
</div>

<div class="panel-grid">
    <section class="card">
        <div class="card-header"><h2>Arquivos</h2></div>
        <?php foreach ($files as $file): ?>
            <p><a class="link" href="/digital-services/file-download?id=<?= (int) $file['id'] ?>"><?= e($file['original_name']) ?></a> <span class="hint"><?= number_format((int) $file['size_bytes'] / 1024, 1, ',', '.') ?> KB</span></p>
        <?php endforeach; ?>
        <form method="post" action="/digital-services/file" enctype="multipart/form-data">
            <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $service['id'] ?>">
            <input type="file" name="file" required>
            <button class="btn btn-sm btn-primary" type="submit">Enviar</button>
        </form>
    </section>

    <section class="card">
        <div class="card-header"><h2>Observações</h2></div>
        <?php foreach ($notes as $note): ?>
            <div class="note-box"><p><?= nl2br(e($note['note'])) ?></p><small><?= e($note['user_name'] ?: 'Sistema') ?> · <?= br_date($note['created_at'], true) ?></small></div>
        <?php endforeach; ?>
        <form method="post" action="/digital-services/note">
            <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $service['id'] ?>">
            <textarea name="note" rows="3" placeholder="Nova observação"></textarea>
            <label class="check-inline"><input type="checkbox" name="is_client_visible" value="1"> Visível ao cliente</label>
            <button class="btn btn-sm btn-primary" type="submit">Adicionar</button>
        </form>
    </section>
</div>

<section class="card">
    <div class="card-header"><h2>Histórico</h2></div>
    <?php foreach ($automationLogs as $log): ?>
        <p class="hint"><?= br_date($log['created_at'], true) ?> · automação · <?= e($log['action']) ?> · <?= e($log['message']) ?></p>
    <?php endforeach; ?>
    <?php foreach ($history as $item): ?>
        <p class="hint"><?= br_date($item['created_at'], true) ?> · <?= e($item['user_name'] ?: 'Sistema') ?> · <?= e($item['action']) ?></p>
    <?php endforeach; ?>
</section>
