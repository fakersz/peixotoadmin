<?php
$isEdit = !empty($order['id']);
$estimated = $order['total_price'] ?? 0;
?>
<section class="card card-form fast-order-form">
    <div class="card-header">
        <div>
            <h2><?= $isEdit ? 'Editar atendimento rápido' : 'Atendimento rápido de balcão' ?></h2>
            <p class="hint">Cria uma OS normal sem cadastrar cliente ou equipamento antes.</p>
        </div>
        <a href="/orders" class="btn btn-ghost">Voltar</a>
    </div>

    <form method="post" action="/orders/fast<?= $isEdit ? '?id=' . (int) $order['id'] : '' ?>" data-fast-order-form>
        <?= csrf_field() ?>
        <?php if ($isEdit): ?>
            <input type="hidden" name="id" value="<?= (int) $order['id'] ?>">
        <?php endif; ?>

        <div class="form-grid">
            <div class="field">
                <label for="quick_customer_name">Nome do cliente *</label>
                <input id="quick_customer_name" name="quick_customer_name" maxlength="160" required
                       value="<?= e($order['quick_customer_name'] ?? 'Cliente balcão') ?>">
            </div>
            <div class="field">
                <label for="quick_customer_phone">Telefone</label>
                <input id="quick_customer_phone" name="quick_customer_phone" maxlength="20"
                       inputmode="tel" autocomplete="tel" data-phone-mask
                       value="<?= e($order['quick_customer_phone'] ?? '') ?>">
            </div>

            <div class="field">
                <label for="quick_device_name">Equipamento *</label>
                <input id="quick_device_name" name="quick_device_name" maxlength="160" required
                       placeholder="Notebook Dell, MacBook A2337, iPhone 13..."
                       value="<?= e($order['quick_device_name'] ?? '') ?>">
            </div>
            <div class="field">
                <label for="quick_service_description">Serviço previsto</label>
                <input id="quick_service_description" name="quick_service_description" maxlength="2000"
                       list="fast-service-suggestions" placeholder="Formatação, limpeza, troca de tela..."
                       value="<?= e($order['quick_service_description'] ?? '') ?>">
                <datalist id="fast-service-suggestions">
                    <option value="Formatação"></option>
                    <option value="Limpeza"></option>
                    <option value="Troca de tela"></option>
                    <option value="Diagnóstico"></option>
                    <option value="Orçamento"></option>
                </datalist>
            </div>

            <div class="field span-2">
                <label for="reported_issue">Problema relatado *</label>
                <textarea id="reported_issue" name="reported_issue" maxlength="2000" rows="3" required><?= e($order['reported_issue'] ?? '') ?></textarea>
            </div>

            <div class="field">
                <label for="estimated_value">Valor estimado</label>
                <input id="estimated_value" type="number" step="0.01" min="0" name="estimated_value"
                       inputmode="decimal" value="<?= e(number_format((float) $estimated, 2, '.', '')) ?>">
                <small class="hint">Não gera lançamento financeiro automaticamente.</small>
            </div>
            <div class="field">
                <label for="quick_due_date">Prazo estimado</label>
                <input id="quick_due_date" type="date" name="quick_due_date" value="<?= e($order['quick_due_date'] ?? '') ?>">
            </div>

            <div class="field">
                <label for="quick_brand">Marca</label>
                <input id="quick_brand" name="quick_brand" maxlength="80" value="<?= e($order['quick_brand'] ?? '') ?>">
            </div>
            <div class="field">
                <label for="quick_model">Modelo</label>
                <input id="quick_model" name="quick_model" maxlength="120" value="<?= e($order['quick_model'] ?? '') ?>">
            </div>

            <div class="field">
                <label for="priority">Prioridade</label>
                <select id="priority" name="priority">
                    <?php foreach (os_priorities() as $value => $label): ?>
                        <option value="<?= e($value) ?>" <?= ($order['priority'] ?? 'normal') === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field">
                <label for="status">Status inicial</label>
                <select id="status" name="status">
                    <?php foreach (os_statuses() as $value => $label): ?>
                        <option value="<?= e($value) ?>" <?= ($order['status'] ?? 'aberta') === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="field">
                <label for="quick_password">Senha do equipamento</label>
                <input id="quick_password" name="quick_password" maxlength="160" autocomplete="off"
                       value="<?= e($order['quick_password'] ?? '') ?>">
                <small class="hint">Não aparece em listagens.</small>
            </div>
            <div class="field">
                <label for="quick_accessories">Acessórios deixados</label>
                <input id="quick_accessories" name="quick_accessories" maxlength="1200"
                       value="<?= e($order['quick_accessories'] ?? '') ?>">
            </div>

            <div class="field span-2">
                <label for="quick_notes">Observações</label>
                <textarea id="quick_notes" name="quick_notes" maxlength="2000" rows="3"><?= e($order['quick_notes'] ?? '') ?></textarea>
            </div>
        </div>

        <div class="form-actions">
            <a href="<?= $isEdit ? '/orders/view?id=' . (int) $order['id'] : '/orders' ?>" class="btn btn-ghost">Cancelar</a>
            <button type="submit" class="btn btn-primary"><?= $isEdit ? 'Salvar Fast OS' : 'Criar Fast OS' ?></button>
        </div>
    </form>
</section>
