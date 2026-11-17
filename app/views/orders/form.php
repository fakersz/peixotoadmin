<?php
$selectedClient = $order['client_id'] ?? $preselectedClient;
$selectedDevice = $order['device_id'] ?? $preselectedDevice;
?>
<section class="card card-form">
    <form method="post">
        <?= csrf_field() ?>
        <div class="form-grid">
            <div class="field">
                <label for="client_id">Cliente *</label>
                <select id="client_id" name="client_id" required>
                    <option value="">Selecione…</option>
                    <?php foreach ($clients as $client): ?>
                        <option value="<?= $client['id'] ?>" <?= $selectedClient == $client['id'] ? 'selected' : '' ?>>
                            <?= e($client['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field">
                <label for="device_id">Equipamento *</label>
                <select id="device_id" name="device_id" required>
                    <option value="">Selecione…</option>
                    <?php foreach ($devices as $device): ?>
                        <option value="<?= $device['id'] ?>" data-client="<?= $device['client_id'] ?>"
                                <?= $selectedDevice == $device['id'] ? 'selected' : '' ?>>
                            <?= e($device['client_name'] . ' — ' . trim($device['category'] . ' ' . ($device['model_code'] ?: $device['model'] ?: ''))) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <small class="hint">A lista é filtrada pelo cliente selecionado.</small>
            </div>
            <div class="field span-2">
                <label for="title">Título da OS *</label>
                <input type="text" id="title" name="title" required maxlength="160"
                       value="<?= e($order['title'] ?? '') ?>" placeholder="Ex: Troca de bateria — MacBook Pro A1502">
            </div>
            <div class="field span-2">
                <label for="reported_issue">Defeito relatado pelo cliente</label>
                <textarea id="reported_issue" name="reported_issue" rows="2"><?= e($order['reported_issue'] ?? '') ?></textarea>
            </div>
            <div class="field span-2">
                <label for="diagnosis">Diagnóstico técnico</label>
                <textarea id="diagnosis" name="diagnosis" rows="2"><?= e($order['diagnosis'] ?? '') ?></textarea>
            </div>
            <div class="field span-2">
                <label for="solution">Solução aplicada</label>
                <textarea id="solution" name="solution" rows="2"><?= e($order['solution'] ?? '') ?></textarea>
            </div>
            <div class="field">
                <label for="status">Status</label>
                <select id="status" name="status">
                    <?php foreach (os_statuses() as $value => $label): ?>
                        <option value="<?= $value ?>" <?= ($order['status'] ?? 'aberta') === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field">
                <label for="priority">Prioridade</label>
                <select id="priority" name="priority">
                    <?php foreach (os_priorities() as $value => $label): ?>
                        <option value="<?= $value ?>" <?= ($order['priority'] ?? 'normal') === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field">
                <label for="labor_price">Mão de obra (R$)</label>
                <input type="number" step="0.01" min="0" id="labor_price" name="labor_price"
                       value="<?= e($order['labor_price'] ?? '0.00') ?>" data-calc>
            </div>
            <div class="field">
                <label for="parts_price">Peças (R$)</label>
                <input type="number" step="0.01" min="0" id="parts_price" name="parts_price"
                       value="<?= e($order['parts_price'] ?? '0.00') ?>" data-calc>
            </div>
            <div class="field">
                <label>Total</label>
                <div class="total-preview" id="totalPreview"><?= money(($order['labor_price'] ?? 0) + ($order['parts_price'] ?? 0)) ?></div>
            </div>
            <div class="field">
                <label for="paid_amount">Valor já pago (R$)</label>
                <input type="number" step="0.01" min="0" id="paid_amount" name="paid_amount"
                       value="<?= e($order['paid_amount'] ?? '0.00') ?>">
                <small class="hint">O status de pagamento é calculado automaticamente.</small>
            </div>
        </div>
        <div class="form-actions">
            <a href="/orders" class="btn btn-ghost">Cancelar</a>
            <button type="submit" class="btn btn-primary"><?= $order ? 'Salvar alterações' : 'Criar OS' ?></button>
        </div>
    </form>
</section>
