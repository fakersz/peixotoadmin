<?php
/** Controller público: portal do cliente */

require_once APP_PATH . '/models/DigitalService.php';
require_once APP_PATH . '/models/ServiceOrder.php';

function portal_digital_service(): void
{
    $token = trim((string) ($_GET['token'] ?? ''));
    $service = $token ? digital_service_find_by_portal_token($token) : null;
    if (!$service) {
        http_response_code(404);
        render('errors/404', ['title' => 'Portal não encontrado']);
    }
    render('digital_services/portal', [
        'title' => 'Portal do cliente',
        'service' => $service,
        'tasks' => digital_service_tasks((int) $service['id']),
        'documents' => digital_service_documents((int) $service['id']),
        'billing' => digital_service_billing_all((int) $service['id']),
    ]);
}

/**
 * API pública (JSON, somente leitura) para o site ifixadmin.site consultar
 * o andamento de uma OS a partir de um token de acompanhamento.
 */
function portal_service_order_status(): void
{
    $allowedOrigins = ['https://ifixadmin.site', 'https://www.ifixadmin.site'];
    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
    if (in_array($origin, $allowedOrigins, true)) {
        header('Access-Control-Allow-Origin: ' . $origin);
        header('Vary: Origin');
    }
    header('Content-Type: application/json; charset=utf-8');

    $token = trim((string) ($_GET['token'] ?? ''));
    if ($token === '') {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'token_ausente']);
        return;
    }

    try {
        $order = order_find_by_portal_token($token);
    } catch (Throwable $exception) {
        error_log('[iFixAdmin] portal_service_order_status: ' . $exception->getMessage());
        http_response_code(500);
        echo json_encode(['ok' => false, 'error' => 'erro_interno']);
        return;
    }

    if (!$order) {
        http_response_code(404);
        echo json_encode(['ok' => false, 'error' => 'nao_encontrado']);
        return;
    }

    echo json_encode([
        'ok' => true,
        'order' => [
            'status' => $order['status'],
            'statusLabel' => status_label($order['status']),
            'priority' => $order['priority'],
            'deviceLabel' => trim(($order['device_brand'] ?: '') . ' ' . ($order['device_model'] ?: $order['device_category'] ?: '')) ?: null,
            'openedAt' => $order['opened_at'],
            'closedAt' => $order['closed_at'],
            'diagnosis' => $order['diagnosis'] ?: null,
            'solution' => $order['solution'] ?: null,
        ],
    ]);
}
