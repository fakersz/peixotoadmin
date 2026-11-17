<?php $selectedClient = $device['client_id'] ?? $preselectedClient; ?>
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
                <label for="category">Categoria *</label>
                <select id="category" name="category" required>
                    <?php foreach (device_categories() as $category): ?>
                        <option value="<?= e($category) ?>" <?= ($device['category'] ?? 'MacBook') === $category ? 'selected' : '' ?>>
                            <?= e($category) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field">
                <label for="model">Modelo</label>
                <input type="text" id="model" name="model" maxlength="120"
                       value="<?= e($device['model'] ?? '') ?>" placeholder='Ex: MacBook Pro 13" Retina 2015'>
            </div>
            <div class="field">
                <label for="model_code">Código Apple</label>
                <input type="text" id="model_code" name="model_code" maxlength="20" class="uppercase"
                       value="<?= e($device['model_code'] ?? '') ?>" placeholder="A1502, A1466, A1708, A1989…">
            </div>
            <div class="field">
                <label for="serial_number">Número de série</label>
                <input type="text" id="serial_number" name="serial_number" maxlength="60"
                       value="<?= e($device['serial_number'] ?? '') ?>" placeholder="C02…">
            </div>
            <div class="field">
                <label for="year">Ano</label>
                <input type="number" id="year" name="year" min="1998" max="2100"
                       value="<?= e($device['year'] ?? '') ?>" placeholder="2015">
            </div>
            <div class="field">
                <label for="processor">Processador</label>
                <input type="text" id="processor" name="processor" maxlength="80"
                       value="<?= e($device['processor'] ?? '') ?>" placeholder="Intel i5 2.7GHz, Apple M1…">
            </div>
            <div class="field">
                <label for="memory">Memória</label>
                <input type="text" id="memory" name="memory" maxlength="40"
                       value="<?= e($device['memory'] ?? '') ?>" placeholder="8 GB">
            </div>
            <div class="field">
                <label for="storage">Armazenamento</label>
                <input type="text" id="storage" name="storage" maxlength="40"
                       value="<?= e($device['storage'] ?? '') ?>" placeholder="SSD 256 GB">
            </div>
            <div class="field">
                <label for="brand">Marca</label>
                <input type="text" id="brand" name="brand" maxlength="60"
                       value="<?= e($device['brand'] ?? 'Apple') ?>">
            </div>
            <div class="field span-2">
                <label for="condition_notes">Estado / avarias na entrada</label>
                <textarea id="condition_notes" name="condition_notes" rows="3"
                          placeholder="Riscos, amassados, tela trincada, sem carregador…"><?= e($device['condition_notes'] ?? '') ?></textarea>
            </div>
        </div>
        <div class="form-actions">
            <a href="/devices" class="btn btn-ghost">Cancelar</a>
            <button type="submit" class="btn btn-primary"><?= $device ? 'Salvar alterações' : 'Cadastrar equipamento' ?></button>
        </div>
    </form>
</section>
