<?php
/** View do PDF da OS. Recebe: $order, $client, $device. Renderizada pelo dompdf (sem flexbox/grid). */
$number    = str_pad((string) $order['id'], 6, '0', STR_PAD_LEFT);
$discount  = (float) ($order['discount'] ?? 0);
$remaining = max((float) $order['total_price'] - (float) $order['paid_amount'], 0);
$isFast    = !empty($order['is_fast_order']);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<title>OS #<?= $number ?> — Peixoto Tech</title>
<style>
    @page { margin: 34px 40px 70px 40px; }

    /* Reset por elemento: o seletor universal (*) impede o dompdf de
       renderizar o rodapé com position: fixed. */
    body, div, table, td, span {
        margin: 0;
        padding: 0;
    }

    body {
        font-family: 'DejaVu Sans', sans-serif;
        font-size: 10px;
        color: #1e293b;
        line-height: 1.45;
    }

    /* ---- Cabeçalho ---- */
    .header { width: 100%; border-bottom: 3px solid #1d4ed8; padding-bottom: 12px; }
    .header td { vertical-align: bottom; }
    .brand-name { font-size: 22px; font-weight: bold; color: #1d4ed8; letter-spacing: 1px; }
    .brand-sub  { font-size: 10px; color: #64748b; margin-top: 2px; }
    .os-box     { text-align: right; }
    .os-label   { font-size: 9px; color: #64748b; text-transform: uppercase; letter-spacing: 2px; }
    .os-number  { font-size: 18px; font-weight: bold; color: #1e3a8a; }
    .os-meta    { font-size: 9px; color: #475569; margin-top: 3px; }
    .os-status {
        display: inline-block; background: #dbeafe; color: #1e40af;
        font-size: 8.5px; font-weight: bold; text-transform: uppercase; letter-spacing: 1px;
        padding: 3px 10px; border-radius: 9px; margin-top: 4px;
    }

    /* ---- Seções ---- */
    .section { margin-top: 16px; }
    .section-title {
        font-size: 9px; font-weight: bold; color: #1d4ed8;
        text-transform: uppercase; letter-spacing: 1.5px;
        border-bottom: 1px solid #bfdbfe; padding-bottom: 4px; margin-bottom: 8px;
    }

    .info-table { width: 100%; border-collapse: collapse; }
    .info-table td { padding: 3px 0; vertical-align: top; }
    .info-label { width: 22%; color: #64748b; font-size: 9px; }
    .info-value { width: 28%; font-weight: bold; color: #0f172a; }

    .text-box {
        background: #f8fafc; border: 1px solid #e2e8f0; border-left: 3px solid #93c5fd;
        border-radius: 4px; padding: 8px 10px; color: #334155;
    }

    /* ---- Valores ---- */
    .money-table { width: 100%; border-collapse: collapse; }
    .money-table td {
        padding: 6px 12px; border-bottom: 1px solid #e2e8f0; font-size: 10px;
    }
    .money-table .amount { text-align: right; font-weight: bold; width: 30%; }
    .money-table .row-total td {
        background: #1d4ed8; color: #ffffff; font-size: 11px; font-weight: bold;
        border-bottom: none;
    }
    .money-table .row-paid .amount { color: #15803d; }
    .money-table .row-remaining .amount { color: #b91c1c; }
    .money-table .row-remaining.settled .amount { color: #15803d; }

    /* ---- Assinaturas ---- */
    .signatures { width: 100%; margin-top: 44px; }
    .signatures td { width: 50%; text-align: center; padding: 0 18px; }
    .sign-line { border-top: 1px solid #475569; padding-top: 5px; font-size: 9px; color: #475569; }

    /* ---- Rodapé ---- */
    .footer {
        position: fixed; bottom: -50px; left: 0; right: 0;
        text-align: center; font-size: 8px; color: #94a3b8;
        border-top: 1px solid #e2e8f0; padding-top: 7px;
    }
</style>
</head>
<body>

<div class="footer">
    Peixoto Tech &bull; Sistema iFixAdmin &bull; Documento gerado automaticamente
</div>

<table class="header">
    <tr>
        <td>
            <div class="brand-name">PEIXOTO TECH</div>
            <div class="brand-sub">Assistência Especializada Apple</div>
        </td>
        <td class="os-box">
            <div class="os-label">Ordem de Serviço</div>
            <div class="os-number">#<?= $number ?></div>
            <div class="os-meta">Abertura: <?= br_date($order['opened_at'], true) ?></div>
            <span class="os-status"><?= $isFast ? 'FAST OS · ' : '' ?><?= e(status_label($order['status'])) ?></span>
        </td>
    </tr>
</table>

<div class="section">
    <div class="section-title">1. Dados do Cliente</div>
    <table class="info-table">
        <tr>
            <td class="info-label">Nome</td>
            <td class="info-value"><?= e($client['name'] ?? $order['client_name']) ?></td>
            <td class="info-label">Telefone</td>
            <td class="info-value"><?= e(($client['phone'] ?? $order['client_phone']) ?: '—') ?></td>
        </tr>
        <?php if (!empty($client['email']) || !empty($client['document'])): ?>
        <tr>
            <?php if (!empty($client['email'])): ?>
                <td class="info-label">E-mail</td>
                <td class="info-value"><?= e($client['email']) ?></td>
            <?php else: ?>
                <td></td><td></td>
            <?php endif; ?>
            <?php if (!empty($client['document'])): ?>
                <td class="info-label">CPF</td>
                <td class="info-value"><?= e($client['document']) ?></td>
            <?php else: ?>
                <td></td><td></td>
            <?php endif; ?>
        </tr>
        <?php endif; ?>
    </table>
</div>

<div class="section">
    <div class="section-title">2. Equipamento</div>
    <table class="info-table">
        <tr>
            <td class="info-label">Tipo</td>
            <td class="info-value"><?= e(($device['category'] ?? $order['device_category']) ?: '—') ?></td>
            <td class="info-label">Marca</td>
            <td class="info-value"><?= e(($device['brand'] ?? $order['quick_brand'] ?? '') ?: '—') ?></td>
        </tr>
        <tr>
            <td class="info-label">Modelo</td>
            <td class="info-value"><?= e(($device['model'] ?? $order['device_model'] ?? $order['quick_device_name']) ?: '—') ?></td>
            <td class="info-label">Nº de Série</td>
            <td class="info-value"><?= e(($device['serial_number'] ?? $order['serial_number'] ?? null) ?: '—') ?></td>
        </tr>
        <tr>
            <td class="info-label">Código Interno</td>
            <td class="info-value"><?= e(($device['model_code'] ?? $order['model_code']) ?: '—') ?></td>
            <td></td><td></td>
        </tr>
    </table>
</div>

<?php if ($isFast): ?>
<div class="section">
    <div class="section-title">3. Dados rápidos</div>
    <table class="info-table">
        <tr>
            <td class="info-label">Equipamento</td>
            <td class="info-value"><?= e($order['quick_device_name'] ?: '—') ?></td>
            <td class="info-label">Serviço previsto</td>
            <td class="info-value"><?= e($order['quick_service_description'] ?: '—') ?></td>
        </tr>
        <?php if (!empty($order['quick_accessories']) || !empty($order['quick_due_date'])): ?>
        <tr>
            <td class="info-label">Acessórios</td>
            <td class="info-value"><?= e($order['quick_accessories'] ?: '—') ?></td>
            <td class="info-label">Prazo</td>
            <td class="info-value"><?= br_date($order['quick_due_date'] ?? null) ?></td>
        </tr>
        <?php endif; ?>
        <?php if (!empty($order['quick_notes'])): ?>
        <tr>
            <td class="info-label">Observações</td>
            <td class="info-value" colspan="3"><?= nl2br(e($order['quick_notes'])) ?></td>
        </tr>
        <?php endif; ?>
    </table>
</div>
<?php endif; ?>

<div class="section">
    <div class="section-title"><?= $isFast ? '4' : '3' ?>. Defeito Relatado</div>
    <div class="text-box"><?= $order['reported_issue'] ? nl2br(e($order['reported_issue'])) : '—' ?></div>
</div>

<div class="section">
    <div class="section-title"><?= $isFast ? '5' : '4' ?>. Diagnóstico</div>
    <div class="text-box"><?= $order['diagnosis'] ? nl2br(e($order['diagnosis'])) : '—' ?></div>
</div>

<div class="section">
    <div class="section-title"><?= $isFast ? '6' : '5' ?>. Solução</div>
    <div class="text-box"><?= $order['solution'] ? nl2br(e($order['solution'])) : '—' ?></div>
</div>

<div class="section">
    <div class="section-title"><?= $isFast ? '7' : '6' ?>. Valores</div>
    <table class="money-table">
        <tr>
            <td>Mão de obra</td>
            <td class="amount"><?= money($order['labor_price']) ?></td>
        </tr>
        <tr>
            <td>Peças</td>
            <td class="amount"><?= money($order['parts_price']) ?></td>
        </tr>
        <?php if ($discount > 0): ?>
        <tr>
            <td>Desconto</td>
            <td class="amount">- <?= money($discount) ?></td>
        </tr>
        <?php endif; ?>
        <tr class="row-total">
            <td>TOTAL</td>
            <td class="amount"><?= money($order['total_price']) ?></td>
        </tr>
        <tr class="row-paid">
            <td>Pago</td>
            <td class="amount"><?= money($order['paid_amount']) ?></td>
        </tr>
        <tr class="row-remaining<?= $remaining > 0 ? '' : ' settled' ?>">
            <td>Restante</td>
            <td class="amount"><?= money($remaining) ?></td>
        </tr>
    </table>
</div>

<div class="section">
    <div class="section-title"><?= $isFast ? '8' : '7' ?>. Assinaturas</div>
    <table class="signatures">
        <tr>
            <td><div class="sign-line">Cliente</div></td>
            <td><div class="sign-line">Responsável Técnico</div></td>
        </tr>
    </table>
</div>

</body>
</html>
