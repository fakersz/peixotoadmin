<section class="card card-form">
    <form method="post">
        <?= csrf_field() ?>
        <div class="form-grid">
            <div class="field span-2">
                <label for="name">Nome do item *</label>
                <input type="text" id="name" name="name" required maxlength="160"
                       value="<?= e($item['name'] ?? '') ?>" placeholder="Ex: Bateria A1502 / SSD 256GB / Álcool isopropílico">
            </div>
            <div class="field">
                <label for="category">Categoria</label>
                <input type="text" id="category" name="category" maxlength="60" list="categories"
                       value="<?= e($item['category'] ?? '') ?>" placeholder="Bateria, Tela, SSD, Flex, Consumível…">
                <datalist id="categories">
                    <option value="Bateria"><option value="Tela"><option value="SSD">
                    <option value="Memória"><option value="Flex"><option value="Teclado">
                    <option value="Parafusos"><option value="Consumível"><option value="Ferramenta">
                </datalist>
            </div>
            <div class="field">
                <label for="compatible_models">Modelos compatíveis</label>
                <input type="text" id="compatible_models" name="compatible_models" maxlength="255" class="uppercase"
                       value="<?= e($item['compatible_models'] ?? '') ?>" placeholder="A1502, A1466…">
            </div>
            <div class="field">
                <label for="quantity">Quantidade</label>
                <input type="number" id="quantity" name="quantity" min="0"
                       value="<?= e($item['quantity'] ?? '0') ?>">
            </div>
            <div class="field">
                <label for="min_quantity">Quantidade mínima</label>
                <input type="number" id="min_quantity" name="min_quantity" min="0"
                       value="<?= e($item['min_quantity'] ?? '1') ?>">
                <small class="hint">Abaixo disso o item aparece como “estoque baixo”.</small>
            </div>
            <div class="field">
                <label for="cost_price">Preço de custo (R$)</label>
                <input type="number" step="0.01" min="0" id="cost_price" name="cost_price"
                       value="<?= e($item['cost_price'] ?? '0.00') ?>">
            </div>
            <div class="field">
                <label for="sale_price">Preço de venda (R$)</label>
                <input type="number" step="0.01" min="0" id="sale_price" name="sale_price"
                       value="<?= e($item['sale_price'] ?? '0.00') ?>">
            </div>
            <div class="field">
                <label for="supplier">Fornecedor</label>
                <input type="text" id="supplier" name="supplier" maxlength="160"
                       value="<?= e($item['supplier'] ?? '') ?>">
            </div>
            <div class="field span-2">
                <label for="notes">Observações</label>
                <textarea id="notes" name="notes" rows="3"><?= e($item['notes'] ?? '') ?></textarea>
            </div>
        </div>
        <div class="form-actions">
            <a href="/inventory" class="btn btn-ghost">Cancelar</a>
            <button type="submit" class="btn btn-primary"><?= $item ? 'Salvar alterações' : 'Cadastrar item' ?></button>
        </div>
    </form>
</section>
