<?php
/** Controller: dashboard */

require APP_PATH . '/models/Inventory.php';
require APP_PATH . '/models/FinancialEntry.php';

function dashboard_index(): void
{
    $pdo = db();

    $statusCounts = [];
    $stmt = $pdo->query('SELECT status, COUNT(*) AS total FROM service_orders GROUP BY status');
    foreach ($stmt as $row) {
        $statusCounts[$row['status']] = (int) $row['total'];
    }

    $openStatuses = ['aberta', 'diagnostico', 'aguardando_aprovacao', 'em_reparo', 'pronto'];
    $openCount = 0;
    foreach ($openStatuses as $status) {
        $openCount += $statusCounts[$status] ?? 0;
    }

    $month   = date('Y-m');
    $summary = financial_summary($month);

    $stmt = $pdo->prepare(
        'SELECT so.id, so.title, so.status, so.priority, so.total_price, so.opened_at,
                so.is_fast_order, so.quick_customer_name, so.quick_device_name,
                COALESCE(c.name, so.quick_customer_name) AS client_name,
                COALESCE(d.model_code, so.quick_device_name) AS model_code
         FROM service_orders so
         LEFT JOIN clients c ON c.id = so.client_id
         LEFT JOIN devices d ON d.id = so.device_id
         ORDER BY so.opened_at DESC
         LIMIT 8'
    );
    $stmt->execute();
    $recentOrders = $stmt->fetchAll();

    $stmt = $pdo->prepare(
        'SELECT id, name, quantity, min_quantity FROM inventory
         WHERE quantity <= min_quantity ORDER BY quantity LIMIT 8'
    );
    $stmt->execute();
    $lowStock = $stmt->fetchAll();

    render('dashboard', [
        'title'         => 'Dashboard',
        'openCount'     => $openCount,
        'waitingParts'  => $statusCounts['aguardando_peca'] ?? 0,
        'finishedCount' => ($statusCounts['entregue'] ?? 0),
        'summary'       => $summary,
        'lowStockCount' => inventory_low_count(),
        'recentOrders'  => $recentOrders,
        'lowStock'      => $lowStock,
    ]);
}
