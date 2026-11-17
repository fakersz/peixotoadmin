<?php $repair = $repair ?? null; ?>
<section class="card card-form knowledge-wide-form">
    <form method="post" data-validate>
        <?= csrf_field() ?>
        <div class="form-grid">
            <div class="field span-2">
                <label for="title">Título *</label>
                <input type="text" id="title" name="title" required maxlength="180" value="<?= e($repair['title'] ?? '') ?>">
            </div>
            <div class="field">
                <label for="service_order_id">OS vinculada</label>
                <select id="service_order_id" name="service_order_id">
                    <option value="">Sem OS</option>
                    <?php foreach ($orders as $order): ?>
                        <option value="<?= (int) $order['id'] ?>" <?= (int) ($repair['service_order_id'] ?? 0) === (int) $order['id'] ? 'selected' : '' ?>>#<?= (int) $order['id'] ?> - <?= e($order['title']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field">
                <label for="client_id">Cliente</label>
                <select id="client_id" name="client_id">
                    <option value="">Sem cliente</option>
                    <?php foreach ($clients as $client): ?>
                        <option value="<?= (int) $client['id'] ?>" <?= (int) ($repair['client_id'] ?? 0) === (int) $client['id'] ? 'selected' : '' ?>><?= e($client['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field">
                <label for="device_id">Equipamento</label>
                <select id="device_id" name="device_id">
                    <option value="">Sem equipamento</option>
                    <?php foreach ($devices as $device): ?>
                        <option value="<?= (int) $device['id'] ?>" data-client="<?= (int) $device['client_id'] ?>" <?= (int) ($repair['device_id'] ?? 0) === (int) $device['id'] ? 'selected' : '' ?>>
                            <?= e(trim($device['category'] . ' ' . ($device['brand'] ?? '') . ' ' . ($device['model_code'] ?: $device['model']))) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field">
                <label for="manufacturer_id">Fabricante</label>
                <select id="manufacturer_id" name="manufacturer_id">
                    <option value="">Selecione...</option>
                    <?php foreach ($manufacturers as $manufacturer): ?>
                        <option value="<?= (int) $manufacturer['id'] ?>" <?= (int) ($repair['manufacturer_id'] ?? 0) === (int) $manufacturer['id'] ? 'selected' : '' ?>><?= e($manufacturer['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field">
                <label for="model_id">Modelo</label>
                <select id="model_id" name="model_id">
                    <option value="">Selecione...</option>
                    <?php foreach ($models as $model): ?>
                        <option value="<?= (int) $model['id'] ?>" data-manufacturer="<?= (int) $model['manufacturer_id'] ?>" <?= (int) ($repair['model_id'] ?? 0) === (int) $model['id'] ? 'selected' : '' ?>><?= e($model['manufacturer_name'] . ' - ' . ($model['code'] ?: $model['name'])) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field">
                <label for="status">Status</label>
                <select id="status" name="status">
                    <?php foreach (KNOWLEDGE_ARTICLE_STATUSES as $status): ?>
                        <option value="<?= e($status) ?>" <?= ($repair['status'] ?? 'rascunho') === $status ? 'selected' : '' ?>><?= e($status) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field">
                <label for="result">Resultado</label>
                <select id="result" name="result">
                    <?php foreach (KNOWLEDGE_REPAIR_RESULTS as $result): ?>
                        <option value="<?= e($result) ?>" <?= ($repair['result'] ?? 'resolvido') === $result ? 'selected' : '' ?>><?= e($result) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field"><label for="time_minutes">Tempo gasto (min)</label><input type="number" min="0" id="time_minutes" name="time_minutes" value="<?= e($repair['time_minutes'] ?? '') ?>"></div>
            <div class="field"><label for="cost">Custo</label><input type="number" min="0" step="0.01" id="cost" name="cost" value="<?= e($repair['cost'] ?? '0.00') ?>"></div>
            <div class="field"><label for="repair_date">Data</label><input type="date" id="repair_date" name="repair_date" value="<?= e($repair['repair_date'] ?? date('Y-m-d')) ?>"></div>
            <div class="field span-2"><label for="problem">Problema relatado</label><textarea id="problem" name="problem" rows="2"><?= e($repair['problem'] ?? '') ?></textarea></div>
            <div class="field span-2"><label for="symptoms">Sintomas</label><textarea id="symptoms" name="symptoms" rows="2"><?= e($repair['symptoms'] ?? '') ?></textarea></div>
            <div class="field span-2"><label for="diagnosis">Diagnóstico</label><textarea id="diagnosis" name="diagnosis" rows="2"><?= e($repair['diagnosis'] ?? '') ?></textarea></div>
            <div class="field span-2"><label for="tests_performed">Testes realizados</label><textarea id="tests_performed" name="tests_performed" rows="2"><?= e($repair['tests_performed'] ?? '') ?></textarea></div>
            <div class="field span-2"><label for="solution">Solução</label><textarea id="solution" name="solution" rows="2"><?= e($repair['solution'] ?? '') ?></textarea></div>
            <div class="field"><label for="parts">Peças utilizadas</label><textarea id="parts" name="parts" rows="3"><?= e($repair['parts'] ?? '') ?></textarea></div>
            <div class="field"><label for="tools">Ferramentas</label><textarea id="tools" name="tools" rows="3"><?= e($repair['tools'] ?? '') ?></textarea></div>
            <div class="field span-2"><label for="observations">Observações</label><textarea id="observations" name="observations" rows="3"><?= e($repair['observations'] ?? '') ?></textarea></div>
            <div class="field span-2"><label for="tags">Tags</label><input type="text" id="tags" name="tags" maxlength="255" value="<?= e($repair['tags'] ?? '') ?>"></div>
        </div>
        <div class="form-actions">
            <a href="/knowledge/repairs" class="btn btn-ghost">Cancelar</a>
            <button type="submit" name="form_action" value="save_draft" class="btn btn-ghost">Salvar rascunho</button>
            <button type="submit" name="form_action" value="save" class="btn btn-primary">Salvar</button>
        </div>
    </form>
</section>
