<?php
/** Controller: financeiro */

require APP_PATH . '/models/FinancialEntry.php';

function financial_index(): void
{
    $month = (string) ($_GET['month'] ?? date('Y-m'));
    if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
        $month = date('Y-m');
    }

    render('financial/index', [
        'title'   => 'Financeiro',
        'month'   => $month,
        'entries' => financial_entries($month),
        'summary' => financial_summary($month),
    ]);
}

function financial_create(): void
{
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $type   = post_str('type', 'entrada');
        $date   = post_str('entry_date', date('Y-m-d'));
        $refId  = post_int('reference_id');

        $data = [
            'type'           => in_array($type, ['entrada', 'saida'], true) ? $type : 'entrada',
            'description'    => post_str('description'),
            'amount'         => post_num('amount'),
            'category'       => post_str('category'),
            'reference_type' => $refId ? 'service_order' : null,
            'reference_id'   => $refId,
            'entry_date'     => preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $date)
                                ? $date : date('Y-m-d'),
        ];

        if (!$data['description'] || $data['amount'] <= 0) {
            flash('error', 'Descrição e valor (maior que zero) são obrigatórios.');
        } else {
            financial_entry_create($data);
            flash('success', 'Lançamento registrado.');
            redirect('/financial?month=' . substr($data['entry_date'], 0, 7));
        }
    }

    render('financial/form', ['title' => 'Novo lançamento']);
}

function financial_delete(): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        redirect('/financial');
    }
    financial_entry_delete(post_int('id', 0));
    flash('success', 'Lançamento excluído.');
    redirect('/financial?month=' . (string) ($_POST['month'] ?? date('Y-m')));
}
