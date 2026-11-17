<section class="card card-form fast-order-form">
    <div class="card-header">
        <div>
            <h2>Converter para cadastro completo</h2>
            <p class="hint">A OS continua marcada como Fast OS e mantém os dados originais registrados.</p>
        </div>
        <a href="/orders/view?id=<?= (int) $order['id'] ?>" class="btn btn-ghost">Voltar</a>
    </div>

    <form method="post" action="/orders/fast-convert?id=<?= (int) $order['id'] ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="id" value="<?= (int) $order['id'] ?>">

        <div class="form-grid">
            <div class="field span-2">
                <label for="client_id">Cliente existente</label>
                <select id="client_id" name="client_id" onchange="this.form.submit()">
                    <option value="">Criar novo cliente</option>
                    <?php foreach ($clientMatches as $client): ?>
                        <option value="<?= (int) $client['id'] ?>" <?= (int) $selectedClientId === (int) $client['id'] ? 'selected' : '' ?>>
                            <?= e($client['name']) ?><?= $client['phone'] ? ' - ' . e($client['phone']) : '' ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if ($clientMatches): ?>
                    <small class="hint">Foram encontrados clientes parecidos. Escolha um deles ou confirme a criação de novo cadastro.</small>
                <?php endif; ?>
            </div>

            <div class="field">
                <label for="client_name">Nome do cliente</label>
                <input id="client_name" name="client_name" maxlength="160" value="<?= e($order['quick_customer_name'] ?? '') ?>">
            </div>
            <div class="field">
                <label for="client_phone">Telefone</label>
                <input id="client_phone" name="client_phone" maxlength="20" data-phone-mask value="<?= e($order['quick_customer_phone'] ?? '') ?>">
            </div>

            <?php if ($selectedClientId): ?>
                <div class="field span-2">
                    <label for="device_id">Equipamento existente deste cliente</label>
                    <select id="device_id" name="device_id">
                        <option value="">Criar novo equipamento</option>
                        <?php foreach ($deviceMatches as $device): ?>
                            <option value="<?= (int) $device['id'] ?>">
                                <?= e(trim(($device['brand'] ?? '') . ' ' . ($device['model'] ?? '') . ' ' . ($device['model_code'] ?? ''))) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endif; ?>

            <div class="field">
                <label for="device_name">Equipamento informado</label>
                <input id="device_name" name="device_name" maxlength="120" value="<?= e($order['quick_device_name'] ?? '') ?>">
            </div>
            <div class="field">
                <label for="brand">Marca</label>
                <input id="brand" name="brand" maxlength="60" value="<?= e($order['quick_brand'] ?? '') ?>">
            </div>
            <div class="field">
                <label for="model">Modelo</label>
                <input id="model" name="model" maxlength="120" value="<?= e($order['quick_model'] ?: $order['quick_device_name']) ?>">
            </div>

            <?php if ($clientMatches && !$selectedClientId): ?>
                <label class="check-inline span-2">
                    <input type="checkbox" name="confirm_new_client" value="1">
                    Criar novo cliente mesmo existindo cliente parecido
                </label>
            <?php endif; ?>
        </div>

        <div class="form-actions">
            <a href="/orders/view?id=<?= (int) $order['id'] ?>" class="btn btn-ghost">Cancelar</a>
            <button type="submit" class="btn btn-primary">Converter e vincular</button>
        </div>
    </form>
</section>
