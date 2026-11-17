<section class="card card-form">
    <form method="post">
        <?= csrf_field() ?>
        <div class="form-grid">
            <div class="field span-2">
                <label for="name">Nome *</label>
                <input type="text" id="name" name="name" required maxlength="160"
                       value="<?= e($client['name'] ?? '') ?>" placeholder="Nome completo">
            </div>
            <div class="field">
                <label for="phone">Telefone / WhatsApp</label>
                <input type="text" id="phone" name="phone" maxlength="30"
                       value="<?= e($client['phone'] ?? '') ?>" placeholder="(71) 9 9999-9999">
            </div>
            <div class="field">
                <label for="email">E-mail</label>
                <input type="email" id="email" name="email" maxlength="160"
                       value="<?= e($client['email'] ?? '') ?>" placeholder="cliente@email.com">
            </div>
            <div class="field">
                <label for="document">CPF / CNPJ</label>
                <input type="text" id="document" name="document" maxlength="30"
                       value="<?= e($client['document'] ?? '') ?>">
            </div>
            <div class="field">
                <label for="address">Endereço</label>
                <input type="text" id="address" name="address"
                       value="<?= e($client['address'] ?? '') ?>" placeholder="Rua, número, bairro, cidade">
            </div>
            <div class="field span-2">
                <label for="notes">Observações</label>
                <textarea id="notes" name="notes" rows="3"><?= e($client['notes'] ?? '') ?></textarea>
            </div>
        </div>
        <div class="form-actions">
            <a href="/clients" class="btn btn-ghost">Cancelar</a>
            <button type="submit" class="btn btn-primary"><?= $client ? 'Salvar alterações' : 'Cadastrar cliente' ?></button>
        </div>
    </form>
</section>
