<section class="card card-form">
    <form method="post">
        <?= csrf_field() ?>
        <div class="form-grid">
            <div class="field">
                <label for="type">Tipo *</label>
                <select id="type" name="type" required>
                    <option value="entrada">Entrada (recebimento)</option>
                    <option value="saida">Saída (despesa)</option>
                </select>
            </div>
            <div class="field">
                <label for="entry_date">Data *</label>
                <input type="date" id="entry_date" name="entry_date" required value="<?= e(date('Y-m-d')) ?>">
            </div>
            <div class="field span-2">
                <label for="description">Descrição *</label>
                <input type="text" id="description" name="description" required maxlength="255"
                       placeholder="Ex: Pagamento OS #12, compra de estanho, aluguel…">
            </div>
            <div class="field">
                <label for="amount">Valor (R$) *</label>
                <input type="number" step="0.01" min="0.01" id="amount" name="amount" required placeholder="0,00">
            </div>
            <div class="field">
                <label for="category">Categoria</label>
                <input type="text" id="category" name="category" maxlength="60" list="fin-categories"
                       placeholder="Serviço, Peça, Aluguel…">
                <datalist id="fin-categories">
                    <option value="Serviço"><option value="Peça"><option value="Ferramenta">
                    <option value="Aluguel"><option value="Energia"><option value="Internet">
                    <option value="Marketing"><option value="Outros">
                </datalist>
            </div>
            <div class="field">
                <label for="reference_id">Nº da OS (opcional)</label>
                <input type="number" id="reference_id" name="reference_id" min="1" placeholder="Vincular a uma OS">
            </div>
        </div>
        <div class="form-actions">
            <a href="/financial" class="btn btn-ghost">Cancelar</a>
            <button type="submit" class="btn btn-primary">Registrar lançamento</button>
        </div>
    </form>
</section>
