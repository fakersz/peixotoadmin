<?php
$clientName = $document['client_name'] ?: $document['temporary_client_name'] ?: 'Cliente';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<title><?= e($document['title']) ?></title>
<style>
    @page { margin: 40px; }
    body { font-family: 'DejaVu Sans', sans-serif; font-size: 11px; color: #172033; line-height: 1.55; }
    .header { border-bottom: 3px solid #1d4ed8; padding-bottom: 12px; margin-bottom: 22px; }
    .brand { font-size: 22px; font-weight: bold; color: #1d4ed8; }
    .muted { color: #64748b; }
    h1 { font-size: 18px; color: #1e3a8a; margin: 0 0 8px; }
    .box { background: #f8fafc; border: 1px solid #e2e8f0; border-left: 3px solid #60a5fa; padding: 12px; margin: 16px 0; }
    .content { white-space: pre-line; }
    .sign { margin-top: 60px; width: 100%; }
    .sign td { width: 50%; text-align: center; padding: 0 24px; }
    .line { border-top: 1px solid #475569; padding-top: 6px; color: #475569; }
</style>
</head>
<body>
<div class="header">
    <div class="brand">PEIXOTO TECH</div>
    <div class="muted">Serviços digitais · Documento gerado pelo iFixAdmin</div>
</div>

<h1><?= e($document['title']) ?></h1>
<div class="muted">Projeto: <?= e($document['project_name']) ?> · Cliente: <?= e($clientName) ?> · Data: <?= date('d/m/Y') ?></div>

<div class="box content"><?= e($document['content']) ?></div>

<table class="sign">
    <tr>
        <td><div class="line">Peixoto Tech</div></td>
        <td><div class="line"><?= e($clientName) ?></div></td>
    </tr>
</table>
</body>
</html>
