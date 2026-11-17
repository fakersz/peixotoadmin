<?php
$remaining = max((float) $order['total_price'] - (float) $order['paid_amount'], 0);
$isFast = !empty($order['is_fast_order']);
$passwordMask = static function (?string $password): string {
    $password = trim((string) $password);
    if ($password === '') {
        return '—';
    }
    return mb_substr($password, 0, 1) . str_repeat('•', max(mb_strlen($password) - 1, 3));
};
?>
<div class="page-actions">
    <a href="/orders" class="btn btn-ghost">← Ordens de serviço</a>
    <div class="spacer"></div>
    <?php if (!in_array($order['status'], ['entregue', 'cancelada'], true)): ?>
        <form method="post" action="/orders/status" class="inline"
              data-knowledge-finalize>
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= $order['id'] ?>">
            <input type="hidden" name="status" value="entregue">
            <input type="hidden" name="create_knowledge" value="0">
            <input type="hidden" name="create_article" value="0">
            <button type="submit" class="btn btn-success">✓ Marcar como entregue</button>
        </form>
    <?php endif; ?>
    <a href="/orders/pdf?id=<?= $order['id'] ?>" target="_blank" class="btn btn-ghost">📄 Baixar PDF</a>
    <?php if ($isFast && (!$order['client_id'] || !$order['device_id'])): ?>
        <a href="/orders/fast?id=<?= $order['id'] ?>" class="btn btn-ghost">Editar</a>
    <?php else: ?>
        <a href="/orders/edit?id=<?= $order['id'] ?>" class="btn btn-ghost">Editar</a>
    <?php endif; ?>
    <?php if ($isFast && (!$order['client_id'] || !$order['device_id']) && order_can('fast_convert', $order)): ?>
        <a href="/orders/fast-convert?id=<?= $order['id'] ?>" class="btn btn-fast">Converter para cadastro completo</a>
    <?php endif; ?>
    <form method="post" action="/orders/delete" class="inline" data-confirm="Excluir definitivamente a OS #<?= $order['id'] ?>?">
        <?= csrf_field() ?>
        <input type="hidden" name="id" value="<?= $order['id'] ?>">
        <button type="submit" class="btn btn-danger-ghost">Excluir</button>
    </form>
</div>

<?php if (!empty($knowledgeRelated)): ?>
    <section class="card knowledge-os-card">
        <div class="card-header">
            <h2>Conhecimento relacionado</h2>
            <div class="badge-row">
                <a class="btn btn-sm btn-ghost" href="/knowledge?q=<?= urlencode($knowledgeRelated['query']) ?>">Abrir pesquisa filtrada</a>
                <a class="btn btn-sm btn-ghost" href="/knowledge/repairs/create?order_id=<?= (int) $order['id'] ?>">Criar reparo</a>
                <a class="btn btn-sm btn-ghost" href="/knowledge/articles/create?source_order_id=<?= (int) $order['id'] ?>">Criar artigo rascunho</a>
                <a class="btn btn-sm btn-ghost" href="/knowledge/schematics?q=<?= urlencode($order['model_code'] ?: $knowledgeRelated['query']) ?>">Vincular esquema</a>
            </div>
        </div>
        <div class="stats-grid stats-grid-5">
            <div class="stat-card"><div class="stat-label">Artigos</div><div class="stat-value"><?= (int) $knowledgeRelated['articles'] ?></div></div>
            <div class="stat-card"><div class="stat-label">Esquemas</div><div class="stat-value"><?= (int) $knowledgeRelated['schematics'] ?></div></div>
            <div class="stat-card"><div class="stat-label">Reparos</div><div class="stat-value"><?= (int) $knowledgeRelated['repairs'] ?></div></div>
            <div class="stat-card"><div class="stat-label">Boardviews</div><div class="stat-value"><?= (int) $knowledgeRelated['boardviews'] ?></div></div>
            <div class="stat-card"><div class="stat-label">Datasheets</div><div class="stat-value"><?= (int) $knowledgeRelated['datasheets'] ?></div></div>
        </div>
    </section>
<?php endif; ?>

<div class="panel-grid">
    <section class="card">
        <div class="card-header">
            <h2><?= e($order['title']) ?></h2>
            <div class="badge-row">
                <?php if ($isFast): ?><span class="badge badge-fast">⚡ Fast OS</span><?php endif; ?>
                <?= status_badge($order['status']) ?>
                <?= priority_badge($order['priority']) ?>
                <?= payment_badge($order['payment_status']) ?>
            </div>
        </div>
        <dl class="detail-grid">
            <div><dt>Cliente</dt><dd>
                <?php if ($order['client_id']): ?>
                    <a href="/clients/view?id=<?= $order['client_id'] ?>" class="link"><?= e($order['client_name']) ?></a>
                <?php else: ?>
                    <?= e($order['client_name'] ?: '—') ?>
                <?php endif; ?>
            </dd></div>
            <div><dt>Telefone</dt><dd><?= e($order['client_phone'] ?: '—') ?></dd></div>
            <div><dt>Equipamento</dt><dd><?= e(trim($order['device_category'] . ' ' . ($order['device_model'] ?: ''))) ?></dd></div>
            <div><dt>Código / Série</dt><dd><?= e(trim(($order['model_code'] ?: '—') . ' · ' . ($order['serial_number'] ?: '—'), ' ·') ?: '—') ?></dd></div>
            <div><dt>Abertura</dt><dd><?= br_date($order['opened_at'], true) ?></dd></div>
            <div><dt>Fechamento</dt><dd><?= br_date($order['closed_at'], true) ?></dd></div>
            <div class="span-all"><dt>Defeito relatado</dt><dd><?= $order['reported_issue'] ? nl2br(e($order['reported_issue'])) : '—' ?></dd></div>
            <div class="span-all"><dt>Diagnóstico</dt><dd><?= $order['diagnosis'] ? nl2br(e($order['diagnosis'])) : '—' ?></dd></div>
            <div class="span-all"><dt>Solução</dt><dd><?= $order['solution'] ? nl2br(e($order['solution'])) : '—' ?></dd></div>
        </dl>

        <div class="card-header"><h2>Alterar status</h2></div>
        <form method="post" action="/orders/status" class="inline-form">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= $order['id'] ?>">
            <select name="status">
                <?php foreach (os_statuses() as $value => $label): ?>
                    <option value="<?= $value ?>" <?= $order['status'] === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn btn-ghost">Aplicar</button>
        </form>

        <div class="card-header"><h2>Portal do cliente</h2></div>
        <form method="post" action="/orders/portal" class="inline-form">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= $order['id'] ?>">
            <button type="submit" class="btn btn-primary btn-sm">Gerar link de acompanhamento</button>
        </form>
        <?php if (!empty($newPortalToken)): ?>
            <div class="alert alert-success" style="margin-top:.75rem;">
                <p style="margin:0 0 .35rem;"><strong>Copie agora — não será mostrado de novo:</strong></p>
                <p style="margin:0 0 .35rem; word-break:break-all;">Token: <code><?= e($newPortalToken) ?></code></p>
                <p style="margin:0; word-break:break-all;">Link: <code>https://ifixadmin.site/acompanhar/?token=<?= e($newPortalToken) ?></code></p>
            </div>
        <?php endif; ?>
        <?php if (!empty($portalTokens)): ?>
            <div class="portal-token-list">
                <?php foreach ($portalTokens as $portalToken): ?>
                    <p class="hint">
                        Criado em <?= br_date($portalToken['created_at'], true) ?>
                        <?= $portalToken['revoked_at'] ? ' · revogado' : '' ?>
                    </p>
                <?php endforeach; ?>
            </div>
            <p class="hint">O token completo aparece só no momento em que é gerado (mensagem de sucesso acima) — copie e envie ao cliente, pois não fica salvo em texto puro por segurança.</p>
        <?php else: ?>
            <p class="hint">Nenhum link gerado ainda para esta OS.</p>
        <?php endif; ?>
    </section>

    <?php if ($isFast): ?>
        <section class="card">
            <div class="card-header">
                <h2>Dados rápidos</h2>
                <?php if (!empty($order['fast_order_converted_at'])): ?>
                    <span class="badge badge-in">Convertida em <?= br_date($order['fast_order_converted_at'], true) ?></span>
                <?php endif; ?>
            </div>
            <dl class="detail-grid">
                <div><dt>Nome informado</dt><dd><?= e($order['quick_customer_name'] ?: '—') ?></dd></div>
                <div><dt>Telefone</dt><dd><?= e($order['quick_customer_phone'] ?: '—') ?></dd></div>
                <div><dt>Equipamento</dt><dd><?= e($order['quick_device_name'] ?: '—') ?></dd></div>
                <div><dt>Marca</dt><dd><?= e($order['quick_brand'] ?: '—') ?></dd></div>
                <div><dt>Modelo</dt><dd><?= e($order['quick_model'] ?: '—') ?></dd></div>
                <div><dt>Serviço previsto</dt><dd><?= e($order['quick_service_description'] ?: '—') ?></dd></div>
                <div><dt>Acessórios</dt><dd><?= e($order['quick_accessories'] ?: '—') ?></dd></div>
                <div><dt>Senha</dt><dd><?= e($passwordMask($order['quick_password'] ?? null)) ?></dd></div>
                <div><dt>Prazo estimado</dt><dd><?= br_date($order['quick_due_date'] ?? null) ?></dd></div>
                <div class="span-all"><dt>Observações</dt><dd><?= $order['quick_notes'] ? nl2br(e($order['quick_notes'])) : '—' ?></dd></div>
            </dl>
        </section>
    <?php endif; ?>

    <section class="card">
        <div class="card-header"><h2>Valores</h2></div>
        <dl class="money-list">
            <div><dt>Mão de obra</dt><dd><?= money($order['labor_price']) ?></dd></div>
            <div><dt>Peças</dt><dd><?= money($order['parts_price']) ?></dd></div>
            <div class="money-total"><dt>Total</dt><dd><?= money($order['total_price']) ?></dd></div>
            <div><dt>Pago</dt><dd class="text-ok"><?= money($order['paid_amount']) ?></dd></div>
            <div><dt>Restante</dt><dd class="<?= $remaining > 0 ? 'text-danger' : 'text-ok' ?>"><?= money($remaining) ?></dd></div>
        </dl>

        <?php if ($remaining > 0): ?>
            <div class="card-header"><h2>Registrar pagamento</h2></div>
            <form method="post" action="/orders/pay" class="inline-form">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= $order['id'] ?>">
                <input type="number" step="0.01" min="0.01" max="<?= e(number_format($remaining, 2, '.', '')) ?>"
                       name="amount" value="<?= e(number_format($remaining, 2, '.', '')) ?>" required>
                <button type="submit" class="btn btn-primary">Receber</button>
            </form>
            <small class="hint">O valor é somado ao pago e lançado como entrada no financeiro.</small>
        <?php endif; ?>
    </section>
</div>

<div class="modal-overlay" id="knowledgeFinalizeModal" hidden>
    <div class="modal-panel knowledge-modal" role="dialog" aria-modal="true" aria-labelledby="knowledgeFinalizeTitle">
        <div class="card-header">
            <h2 id="knowledgeFinalizeTitle">Transformar este reparo em conhecimento?</h2>
        </div>
        <p class="hint">Se confirmar, o iFixAdmin cria um reparo estruturado com problema, diagnóstico, solução, modelo, cliente, equipamento, autor e data. O artigo técnico continua como rascunho opcional.</p>
        <label class="check-inline"><input type="checkbox" id="knowledgeCreateArticle"> Criar também artigo rascunho</label>
        <div class="form-actions">
            <button class="btn btn-ghost" type="button" data-knowledge-no>Não</button>
            <button class="btn btn-primary" type="button" data-knowledge-yes>Sim, criar reparo</button>
        </div>
    </div>
</div>
